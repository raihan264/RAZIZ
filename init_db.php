<?php
/**
 * Script Inisialisasi Database
 * File: init_db.php
 * Jalankan file ini untuk membuat tabel-tabel yang diperlukan
 */

require_once __DIR__ . '/config/database.php';

try {
    // Buat tabel products
    $sql_products = "
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        cpu TEXT NOT NULL,
        ram TEXT NOT NULL,
        disk TEXT NOT NULL,
        stock TEXT NOT NULL,
        price TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql_products);

    echo "✅ Database berhasil diinisialisasi!\n";
    echo "✅ Tabel 'products' berhasil dibuat!\n";
    echo "\n";
    echo "Database path: " . __DIR__ . '/database/toko.db' . "\n";

} catch (PDOException $e) {
    echo "❌ Error saat membuat tabel: " . $e->getMessage() . "\n";
}
?>
