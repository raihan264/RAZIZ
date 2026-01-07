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

            // Status Logic
            $status = 'Active';
            if ($diff > 30) {
                $status = 'Expired';
            } elseif ($diff > 15) {
                $status = 'No Garansi';
            }

            return [
                'id' => "SVR-" . $svr['id'],
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
    $owner_name = $_POST['owner'] ?? '';
    $plan_name = $_POST['plan'] ?? '';
    $server_name = $_POST['server_name'] ?? '';

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

        // 2. Resolve Customer
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE name = ? LIMIT 1");
        $stmt->execute([$owner_name]);
        $customer = $stmt->fetch();
        if (!$customer) throw new Exception("Customer not found: $owner_name");

        // 3. Resolve Product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ? LIMIT 1");
        $stmt->execute([$plan_name]);
        $product = $stmt->fetch();
        if (!$product) throw new Exception("Product not found: $plan_name");

        // 4. Initialize Service
        $ptero = new PterodactylService($settings['panel_domain'], $settings['plta_key']);

        // 5. Check/Create Pterodactyl User
        // Use username to check.
        $pteroUserId = $ptero->checkUserExists($customer['username']);

        if (!$pteroUserId) {
            // Create User
            // Rule: email = username + '@raziz.my.id'
            // Rule: first_name, last_name = username (or last name static 'User')
            $userData = [
                'username' => $customer['username'],
                'email' => $customer['username'] . '@raziz.my.id',
                'first_name' => $customer['username'],
                'last_name' => 'User',
                'password' => $customer['password'] // Sync password
            ];
            $pteroUserId = $ptero->createUser($userData);
        }

        // 6. Get Egg Details (Nest 5, Egg 15)
        $nestId = 5;
        $eggId = 15;
        $eggDetails = $ptero->getEggDetails($nestId, $eggId);

        // 7. Get Allocation (Node 1)
        $nodeId = 1;
        $allocationId = $ptero->getUnassignedAllocation($nodeId);

        // 8. Prepare Server Payload
        // Parse RAM/Disk from Product (Assuming DB stores e.g. "4" for 4GB, or "4096" for MB.
        // Based on assumption, products table has small integers like '1', '3'. Treating as GB.

        $ramRaw = (int) filter_var($product['ram'], FILTER_SANITIZE_NUMBER_INT);
        $diskRaw = (int) filter_var($product['disk'], FILTER_SANITIZE_NUMBER_INT);
        $cpuRaw = (int) filter_var($product['cpu'], FILTER_SANITIZE_NUMBER_INT);

        // Convert GB to MB if value is small (arbitrary threshold < 128 likely GB)
        // Or simply multiply by 1024 as per requirement for "1", "3".
        $ramMB = $ramRaw < 128 ? $ramRaw * 1024 : $ramRaw;
        $diskMB = $diskRaw < 128 ? $diskRaw * 1024 : $diskRaw;

        $serverPayload = [
            'name' => $server_name,
            'user_id' => $pteroUserId,
            'egg_id' => $eggId,
            'docker_image' => $eggDetails['docker_image'],
            'startup' => $eggDetails['startup'],
            'memory' => $ramMB,
            'disk' => $diskMB,
            'cpu' => $cpuRaw
        ];

        // Create on Pterodactyl
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
            'Active'
        ]);

        $serverId = $pdo->lastInsertId();

        // 10. Create Order Record (Automated)
        $sqlOrder = "INSERT INTO orders (user_id, product_id, server_id, status, amount, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
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
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings || empty($settings['plta_key']) || empty($settings['panel_domain'])) {
            throw new Exception("Pterodactyl settings not configured.");
        }

        $stmt = $pdo->prepare("SELECT pterodactyl_id, user_id FROM servers WHERE id = ?");
        $stmt->execute([$id]);
        $server = $stmt->fetch();

        if ($server) {
            $ptero = new PterodactylService($settings['panel_domain'], $settings['plta_key']);
            try {
                $ptero->deleteServer($server['pterodactyl_id']);
            } catch (Exception $e) {
                // Ignore 404
                if (strpos($e->getMessage(), '404') === false) {
                    // Log but continue?
                }
            }

            // Delete Local
            $pdo->prepare("DELETE FROM servers WHERE id = ?")->execute([$id]);

            // Set Order Expired
            $pdo->prepare("UPDATE orders SET status = 'Expired' WHERE server_id = ?")->execute([$id]);

            // Decrement active servers count
            $pdo->prepare("UPDATE customers SET activeServers = MAX(0, activeServers - 1) WHERE id = ?")->execute([$server['user_id']]);

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
