<?php
// tests/test_user_crud.php

echo "Testing Add User 'aziz'...\n";
$ch = curl_init('http://localhost:8000/actions/user_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'add',
    'name' => 'Aziz Gaming',
    'username' => 'aziz',
    'password' => 'secret123',
    'wa' => '0899999'
]));
$response = curl_exec($ch);
echo "Add Response: $response\n";
curl_close($ch);

echo "\nTesting Get All Users...\n";
$ch = curl_init('http://localhost:8000/actions/user_handler.php?action=get_all');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
$found = false;
$azizId = null;
if ($data['success']) {
    foreach ($data['customers'] as $c) {
        if ($c['username'] === 'aziz') {
            $found = true;
            $azizId = $c['id'];
            echo "Found Aziz! ID: " . $c['id'] . "\n";
            break;
        }
    }
}
curl_close($ch);

if ($azizId) {
    echo "\nTesting Delete User 'aziz'...\n";
    $ch = curl_init('http://localhost:8000/actions/user_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'delete',
        'id' => $azizId
    ]));
    $response = curl_exec($ch);
    echo "Delete Response: $response\n";
    curl_close($ch);
}
?>
