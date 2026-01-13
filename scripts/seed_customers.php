<?php
// scripts/seed_customers.php
require_once __DIR__ . '/../config/database.php';

echo "Seeding customers...\n";

// Mock data from admin.php
$customers = [
    [
        'name' => "Riyan Gaming",
        'username' => "riyangaming",
        'password' => "password123",
        'wa' => "081234567890",
        'joinDate' => "10 Jan 2024",
        'activeServers' => 2
    ],
    [
        'name' => "Santoso Store",
        'username' => "santoso_store",
        'password' => "santoso123",
        'wa' => "089876543210",
        'joinDate' => "15 Feb 2024",
        'activeServers' => 1
    ],
    [
        'name' => "Budi Santuy",
        'username' => "budisantuy",
        'password' => "budi123",
        'wa' => "085244332211",
        'joinDate' => "01 Mar 2024",
        'activeServers' => 0
    ],
    [
        'name' => "Siti Nurhaliza",
        'username' => "siti_nur",
        'password' => "siti123",
        'wa' => "082133445566",
        'joinDate' => "05 Mar 2024",
        'activeServers' => 1
    ]
];

// Check if data already exists to avoid duplicates
$stmt = $pdo->query("SELECT COUNT(*) FROM customers");
$count = $stmt->fetchColumn();

if ($count == 0) {
    $sql = "INSERT INTO customers (name, username, password, wa, joinDate, activeServers) VALUES (:name, :username, :password, :wa, :joinDate, :activeServers)";
    $stmt = $pdo->prepare($sql);

    foreach ($customers as $customer) {
        $stmt->execute($customer);
    }
    echo "Inserted " . count($customers) . " customers.\n";
} else {
    echo "Customers table already has data. Skipping seed.\n";
}

echo "Done.\n";
