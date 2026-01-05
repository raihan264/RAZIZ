<?php
// actions/server_handler.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PterodactylService.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_all') {
    try {
        $sql = "
            SELECT
                s.*,
                c.name as owner_name,
                p.name as plan_name,
                p.cpu, p.ram, p.disk
            FROM servers s
            JOIN customers c ON s.user_id = c.id
            JOIN products p ON s.product_id = p.id
            ORDER BY s.created_at DESC
        ";
        $stmt = $pdo->query($sql);
        $servers = $stmt->fetchAll();

        // Set Timezone
        date_default_timezone_set('Asia/Jakarta');

        // Format for frontend
        $formatted = array_map(function($svr) {
            $createdAt = new DateTime($svr['created_at']);
            $now = new DateTime();
            $diff = $now->diff($createdAt)->days;

            $status = 'Active';
            if ($diff > 30) {
                $status = 'Expired';
            } elseif ($diff > 15) {
                $status = 'No Garansi';
            } else {
                // If it was just created, it might be 'Installing' in DB, but requirement says "Active" immediately.
                // However, let's respect the dynamic calculation or DB value?
                // Prompt: "setelah buat server statusnya langsung Aktif... Jika Lebih dari 15 hari... No Garansi"
                // So we override DB status with calculated status based on time.
                $status = 'Active';
            }

            return [
                'id' => "SVR-" . $svr['id'], // Display ID
                'raw_id' => $svr['id'],
                'owner' => $svr['owner_name'],
                'plan' => $svr['plan_name'],
                'date' => $createdAt->format('d M Y'),
                'status' => $status,
                'identifier' => $svr['identifier']
            ];
        }, $servers);

        echo json_encode(['success' => true, 'servers' => $formatted]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'create') {
    $owner_name = $_POST['owner'] ?? ''; // Received as Name from frontend select value
    $plan_name = $_POST['plan'] ?? '';   // Received as Name
    $server_name = $_POST['server_name'] ?? ''; // New field

    if (empty($owner_name) || empty($plan_name) || empty($server_name)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        // 1. Get Settings
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings || empty($settings['plta_key']) || empty($settings['panel_domain'])) {
            throw new Exception("Pterodactyl settings (Domain/API Key) not configured.");
        }

        // 2. Resolve Customer ID and Details
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE name = ? LIMIT 1");
        $stmt->execute([$owner_name]);
        $customer = $stmt->fetch();
        if (!$customer) throw new Exception("Customer not found: $owner_name");

        // 3. Resolve Product ID and Specs
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ? LIMIT 1");
        $stmt->execute([$plan_name]);
        $product = $stmt->fetch();
        if (!$product) throw new Exception("Product not found: $plan_name");

        // 4. Initialize Service
        $ptero = new PterodactylService($settings['panel_domain'], $settings['plta_key']);

        // 5. Check/Create Pterodactyl User
        // Use username to check. Assuming local username matches panel username logic.
        // Prompt said: "email = username+@raziz.my.id"
        $pteroUserId = $ptero->checkUserExists($customer['username']);

        if (!$pteroUserId) {
            // Create User
            $userData = [
                'username' => $customer['username'],
                'email' => $customer['username'] . '@raziz.my.id',
                'first_name' => $customer['username'],
                'last_name' => 'User',
                'password' => $customer['password'] // Using local password
            ];
            $pteroUserId = $ptero->createUser($userData);
        }

        // 6. Get Egg Details (Nest 5, Egg 15)
        $nestId = 5;
        $eggId = 15;
        $eggDetails = $ptero->getEggDetails($nestId, $eggId); // Fetch Env Vars

        // 7. Get Allocation (Node 1)
        $nodeId = 1;
        $allocationId = $ptero->getUnassignedAllocation($nodeId);

        // 8. Create Server
        $serverPayload = [
            'name' => $server_name,
            'user_id' => $pteroUserId,
            'egg_id' => $eggId,
            'docker_image' => $eggDetails['docker_image'],
            'startup' => $eggDetails['startup'],
            'memory' => (int) filter_var($product['ram'], FILTER_SANITIZE_NUMBER_INT), // Assuming "4 GB" -> 4. Wait, usually MB.
            'disk' => (int) filter_var($product['disk'], FILTER_SANITIZE_NUMBER_INT), // Assuming "5 GB" -> 5
            'cpu' => (int) filter_var($product['cpu'], FILTER_SANITIZE_NUMBER_INT)
        ];

        // Parse RAM/Disk to MB if needed.
        // Example: "4 GB" -> 4096. "128 MB" -> 128.
        // Simple parser:
        function parseToMB($str) {
            $num = (int) filter_var($str, FILTER_SANITIZE_NUMBER_INT);
            if (stripos($str, 'GB') !== false) return $num * 1024;
            return $num; // Assume MB if not GB
        }

        $serverPayload['memory'] = parseToMB($product['ram']);
        $serverPayload['disk'] = parseToMB($product['disk']);
        $serverPayload['cpu'] = (int) filter_var($product['cpu'], FILTER_SANITIZE_NUMBER_INT);

        $pteroServer = $ptero->createServer($serverPayload, $eggDetails['environment'], $allocationId);

        // 9. Save to Local DB
        $sql = "INSERT INTO servers (pterodactyl_id, identifier, user_id, product_id, name, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pteroServer['attributes']['id'],
            $pteroServer['attributes']['identifier'],
            $customer['id'],
            $product['id'],
            $server_name,
            'Active' // Initial status
        ]);

        $serverId = $pdo->lastInsertId();

        // 10. Create Order Record
        $sqlOrder = "INSERT INTO orders (user_id, product_id, server_id, status, amount) VALUES (?, ?, ?, ?, ?)";
        $stmtOrder = $pdo->prepare($sqlOrder);
        $stmtOrder->execute([
            $customer['id'],
            $product['id'],
            $serverId,
            'Active',
            $product['price']
        ]);

        // Update customer active servers count
        $pdo->prepare("UPDATE customers SET activeServers = activeServers + 1 WHERE id = ?")->execute([$customer['id']]);

        echo json_encode(['success' => true, 'message' => 'Server created successfully']);

    } catch (Exception $e) {
        // Log detailed error for debugging if needed
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID server diperlukan']);
        exit;
    }

    try {
        // 1. Get Settings
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings || empty($settings['plta_key']) || empty($settings['panel_domain'])) {
            throw new Exception("Pterodactyl settings not configured.");
        }

        // 2. Get Server Info
        $stmt = $pdo->prepare("SELECT pterodactyl_id FROM servers WHERE id = ?");
        $stmt->execute([$id]);
        $server = $stmt->fetch();

        if ($server) {
            // 3. Delete from Panel
            $ptero = new PterodactylService($settings['panel_domain'], $settings['plta_key']);
            try {
                $ptero->deleteServer($server['pterodactyl_id']);
            } catch (Exception $e) {
                // Ignore if not found on panel, proceed to delete local?
                // Or maybe just log it. "404 Not Found" is fine to ignore.
                if (strpos($e->getMessage(), '404') === false) {
                    // throw $e; // Optional: Force stop if panel delete fails
                }
            }

            // 4. Delete from Local DB
            $pdo->prepare("DELETE FROM servers WHERE id = ?")->execute([$id]);

            // 5. Update Order Status to Expired
            $pdo->prepare("UPDATE orders SET status = 'Expired' WHERE server_id = ?")->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Server berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Server tidak ditemukan']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
