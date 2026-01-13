<?php
// tests/test_auth_logic.php
echo "Testing Login Logic...\n";

// 1. Test Admin Login
$ch = curl_init('http://localhost:8000/actions/auth_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'username' => 'furqon',
    'password' => 'sholat24434'
]));
$response = curl_exec($ch);
$json = json_decode($response, true);
echo "Admin Login: " . ($json['success'] && $json['role'] === 'admin' ? "SUCCESS" : "FAIL") . "\n";

// 2. Test User Register
$randUser = 'testuser_' . rand(100,999);
$ch = curl_init('http://localhost:8000/actions/auth_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'register',
    'username' => $randUser,
    'password' => 'pass123',
    'wa' => '081111'
]));
$response = curl_exec($ch);
$json = json_decode($response, true);
echo "User Register: " . ($json['success'] ? "SUCCESS" : "FAIL - " . ($json['message'] ?? '')) . "\n";

// 3. Test User Login
$ch = curl_init('http://localhost:8000/actions/auth_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'username' => $randUser,
    'password' => 'pass123'
]));
$response = curl_exec($ch);
$json = json_decode($response, true);
echo "User Login: " . ($json['success'] && $json['role'] === 'user' ? "SUCCESS" : "FAIL") . "\n";
?>
