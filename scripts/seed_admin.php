<?php
// scripts/seed_admin.php
require_once __DIR__ . '/../config/database.php';

echo "Seeding admin...\n";

$username = 'furqon';
$password = 'sholat24434';

// Check if exists
$stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->execute([$username]);

if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);
    echo "Admin '$username' created.\n";
} else {
    echo "Admin '$username' already exists.\n";
}
?>
