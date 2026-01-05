<?php
// tests/test_order_export.php
require_once __DIR__ . '/../config/database.php';

echo "Testing Order Export Logic...\n";

// Assuming orders exist from previous test
$ch = curl_init('http://localhost:8000/actions/order_handler.php?action=get_all');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$json = json_decode($response, true);

if ($json['success']) {
    echo "Get All Orders: " . count($json['orders']) . " items found.\n";
} else {
    echo "Get All Failed: " . $response . "\n";
}

// Test CSV Export
echo "Testing CSV Export...\n";
$ch = curl_init('http://localhost:8000/actions/order_handler.php?action=export_csv');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (strpos($response, 'ID,PELANGGAN') !== false) {
    echo "CSV Headers OK.\n";
} else {
    echo "CSV Export Failed or Empty.\n";
}

echo "Done.\n";
?>
