<?php
// tests/test_customer_count.php
require_once __DIR__ . '/../config/database.php';

// 1. Create a User (if not exists)
$pdo->prepare("INSERT OR IGNORE INTO customers (id, name, username, password, wa, joinDate) VALUES (99, 'Count Test', 'count_test', 'pass', '081', 'Today')")->execute();

// 2. Clear servers for this user
$pdo->prepare("DELETE FROM servers WHERE user_id = 99")->execute();

// 3. Add 2 servers
$pdo->prepare("INSERT INTO servers (user_id, product_id, name, status) VALUES (99, 2, 'Srv 1', 'Active')")->execute();
$pdo->prepare("INSERT INTO servers (user_id, product_id, name, status) VALUES (99, 2, 'Srv 2', 'Active')")->execute();

// 4. Call API
$ch = curl_init('http://localhost:8000/actions/user_handler.php?action=get_all');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

foreach ($data['customers'] as $c) {
    if ($c['id'] == 99) {
        echo "User: {$c['name']}, Active Servers: {$c['activeServers']}\n";
    }
}
?>
