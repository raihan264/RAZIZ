<?php
/**
 * Product Handler - CRUD Operations untuk Produk
 * File: actions/product_handler.php
 */

// Include database connection
$pdo = require_once __DIR__ . '/../config/database.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Ambil action dari request
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // Ambil semua produk dari database
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            $products = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
            break;

        case 'get_one':
            // Ambil satu produk berdasarkan ID
            $id = $_REQUEST['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if ($product) {
                echo json_encode([
                    'success' => true,
                    'product' => $product
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan'
                ]);
            }
            break;

        case 'add':
            // Tambah produk baru
            $name = $_POST['name'] ?? '';
            $cpu = $_POST['cpu'] ?? '';
            $ram = $_POST['ram'] ?? '';
            $disk = $_POST['disk'] ?? '';
            $stock = $_POST['stock'] ?? '';
            $price = $_POST['price'] ?? '';

            // Validasi input
            if (empty($name) || empty($cpu) || empty($ram) || empty($disk) || empty($stock) || empty($price)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Semua field harus diisi'
                ]);
                exit;
            }

            // Insert ke database
            $stmt = $pdo->prepare("
                INSERT INTO products (name, cpu, ram, disk, stock, price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([$name, $cpu, $ram, $disk, $stock, $price]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Produk berhasil ditambahkan',
                    'product_id' => $pdo->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menambahkan produk'
                ]);
            }
            break;

        case 'edit':
            // Edit produk
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $cpu = $_POST['cpu'] ?? '';
            $ram = $_POST['ram'] ?? '';
            $disk = $_POST['disk'] ?? '';
            $stock = $_POST['stock'] ?? '';
            $price = $_POST['price'] ?? '';

            // Validasi input
            if (empty($id) || empty($name) || empty($cpu) || empty($ram) || empty($disk) || empty($stock) || empty($price)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Semua field harus diisi'
                ]);
                exit;
            }

            // Update database
            $stmt = $pdo->prepare("
                UPDATE products
                SET name = ?, cpu = ?, ram = ?, disk = ?, stock = ?, price = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $result = $stmt->execute([$name, $cpu, $ram, $disk, $stock, $price, $id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Produk berhasil diupdate'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal mengupdate produk'
                ]);
            }
            break;

        case 'delete':
            // Hapus produk
            $id = $_POST['id'] ?? 0;

            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID produk tidak valid'
                ]);
                exit;
            }

            // Hapus dari database
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Produk berhasil dihapus'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menghapus produk'
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak valid'
            ]);
            break;
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
