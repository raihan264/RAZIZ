<?php
// tests/test_price_formatting.php
require_once __DIR__ . '/../config/database.php';

echo "Testing Price Formatting...\n";

// Add product with dot
$ch = curl_init('http://localhost:8000/actions/product_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'add',
    'name' => 'PriceTest',
    'cpu' => '100', 'ram' => '1', 'disk' => '1', 'stock' => '1',
    'price' => '5.000'
]));
$response = curl_exec($ch);
$json = json_decode($response, true);
$pid = $json['product_id'] ?? 0;
echo "Added ID $pid. Response: " . $json['message'] . "\n";

// Check DB directly
$stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
$stmt->execute([$pid]);
$dbPrice = $stmt->fetchColumn();
echo "Stored in DB: $dbPrice\n";

if ($dbPrice === '5000') {
    echo "SUCCESS: Price stored correctly as integer string.\n";
} else {
    echo "FAILURE: Price stored as $dbPrice.\n";
}

// Cleanup
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
?>
