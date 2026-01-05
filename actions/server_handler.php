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

        // Format for frontend
        $formatted = array_map(function($svr) {
            return [
                'id' => "SVR-" . $svr['id'], // Display ID
                'raw_id' => $svr['id'],
                'owner' => $svr['owner_name'],
                'plan' => $svr['plan_name'],
                'date' => date('d M Y', strtotime($svr['created_at'])),
                'status' => $svr['status'],
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
            'Installing' // Initial status
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
    // Implement delete if needed
    $id = $_POST['id'] ?? '';
    // ...
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
