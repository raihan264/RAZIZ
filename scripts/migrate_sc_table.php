<?php
// scripts/migrate_sc_table.php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Creating source_codes table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS source_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
