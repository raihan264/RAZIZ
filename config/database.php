<?php
/**
 * Database Connection untuk SQLite
 * File: config/database.php
 */

// Path ke file database SQLite
$db_path = __DIR__ . '/../database/toko.db';

// Buat direktori database jika belum ada
$db_dir = dirname($db_path);
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

try {
    // Buat koneksi PDO ke SQLite
    $pdo = new PDO('sqlite:' . $db_path);

    // Set error mode ke exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-create table products jika belum ada
    $pdo->exec("
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
        )
    ");

    // Auto-create table customers jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            wa TEXT NOT NULL,
            joinDate TEXT NOT NULL,
            activeServers INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Auto-create table settings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            panel_domain TEXT,
            plta_key TEXT,
            pltc_key TEXT,
            contact_email TEXT,
            contact_wa TEXT,
            contact_address TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Auto-create table servers
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pterodactyl_id INTEGER,
            identifier TEXT,
            user_id INTEGER,
            product_id INTEGER,
            name TEXT NOT NULL,
            status TEXT DEFAULT 'Installing',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES customers(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");

} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

return $pdo;
?>
