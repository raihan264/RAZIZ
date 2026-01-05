<?php
// tests/test_user_no_name.php
require_once __DIR__ . '/../config/database.php';

echo "Testing Add User without Name...\n";

// Cleanup if exists
$pdo->prepare("DELETE FROM customers WHERE username = 'noname_user'")->execute();

$ch = curl_init('http://localhost:8000/actions/user_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'add',
    // 'name' is omitted
    'username' => 'noname_user',
    'password' => 'secret',
    'wa' => '0812345'
]));
$response = curl_exec($ch);
$json = json_decode($response, true);

if ($json['success']) {
    echo "User Added Successfully.\n";
    // Check DB to see what 'name' became
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE username = 'noname_user'");
    $stmt->execute();
    $name = $stmt->fetchColumn();
    echo "Stored Name: $name (Should be 'noname_user')\n";
    if ($name === 'noname_user') echo "SUCCESS.\n";
    else echo "FAILURE.\n";
} else {
    echo "Add Failed: " . $json['message'] . "\n";
}
?>
