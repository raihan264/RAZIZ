<?php
/**
 * Script Inisialisasi Database
 * File: init_db.php
 * Jalankan file ini untuk membuat tabel-tabel yang diperlukan
 */

require_once __DIR__ . '/config/database.php';

echo "✅ Database berhasil diinisialisasi!\n";
echo "✅ Tabel 'products', 'customers', 'settings', 'servers', 'orders' berhasil dibuat!\n";
echo "\n";
echo "Database path: " . __DIR__ . '/database/toko.db' . "\n";
?>
