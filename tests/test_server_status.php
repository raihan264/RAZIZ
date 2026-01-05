<?php
// tests/test_server_status.php
require_once __DIR__ . '/../config/database.php';

echo "Setting up test data...\n";
date_default_timezone_set('Asia/Jakarta');

// Create test user and product if needed (assuming user 1 and product 2 exist from previous seeds/tests)
// Insert dummy servers
$dates = [
    'active' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'no_garansi' => date('Y-m-d H:i:s', strtotime('-16 days')),
    'expired' => date('Y-m-d H:i:s', strtotime('-31 days'))
];

foreach ($dates as $key => $date) {
    // Check if exists to avoid cluttering or just insert
    $pdo->prepare("INSERT INTO servers (pterodactyl_id, user_id, product_id, name, status, created_at) VALUES (?, 1, 2, ?, 'Active', ?)")
        ->execute([rand(1000,9999), "Test $key", $date]);
}

echo "Fetching servers...\n";
$ch = curl_init('http://localhost:8000/actions/server_handler.php?action=get_all');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    foreach ($data['servers'] as $s) {
        if (strpos($s['owner'], 'Test') !== false) continue; // Skip real ones if any? Wait, owner name comes from user table.
        // Assuming user 1 is "Riyan Gaming" or similar.
        echo "Server: {$s['status']} (Date: {$s['date']})\n";
    }
} else {
    echo "Error: " . $data['message'];
}
?>
