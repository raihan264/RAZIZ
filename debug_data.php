<?php
require_once 'config/database.php';

echo "--- CUSTOMERS ---\n";
$stmt = $pdo->query("SELECT id, username, activeServers FROM customers");
$customers = $stmt->fetchAll();
print_r($customers);

echo "\n--- SERVERS ---\n";
$stmt = $pdo->query("SELECT user_id, count(*) as real_count FROM servers GROUP BY user_id");
$server_counts = $stmt->fetchAll();
print_r($server_counts);

echo "\n--- SOURCE CODES ---\n";
$stmt = $pdo->query("SELECT * FROM source_codes");
print_r($stmt->fetchAll());
