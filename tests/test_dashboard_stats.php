<?php
// tests/test_dashboard_stats.php
require_once __DIR__ . '/../config/database.php';

echo "Testing Dashboard Stats...\n";

// Clear Orders
$pdo->exec("DELETE FROM orders");

// Seed data
// This Week: 10000
$pdo->prepare("INSERT INTO orders (status, amount, created_at) VALUES ('Active', '10000', date('now'))")->execute();
// Last Week: 5000
$pdo->prepare("INSERT INTO orders (status, amount, created_at) VALUES ('Active', '5000', date('now', '-10 days'))")->execute();

$ch = curl_init('http://localhost:8000/actions/dashboard_handler.php?action=get_stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$json = json_decode($response, true);

if ($json['success']) {
    $stats = $json['stats'];
    echo "Total Revenue: {$stats['revenue']}\n"; // Should be 15.000
    echo "This Week vs Last Week %: {$stats['percentage']}\n"; // (10000 - 5000) / 5000 * 100 = 100%

    if (strpos($stats['revenue'], '15.000') !== false && $stats['percentage'] === '100%') {
        echo "SUCCESS: Stats calculated correctly.\n";
    } else {
        echo "FAILURE: Stats mismatch.\n";
    }
} else {
    echo "Error: " . $json['message'];
}
?>
