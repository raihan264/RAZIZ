<?php
// tests/verify_db.php
require_once __DIR__ . '/../config/database.php';

echo "Verifying database tables...\n";

// Tables to check
$tables = ['products', 'customers', 'settings', 'servers', 'orders', 'admins'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT count(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "Table '$table' exists. Rows: $count\n";
    } catch (PDOException $e) {
        echo "Error checking table '$table': " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
