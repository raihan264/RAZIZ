<?php
/**
 * Customer Handler - CRUD Operations untuk Pelanggan
 * File: actions/customer_handler.php
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
            // Ambil semua pelanggan dari database
            $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
            $customers = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'customers' => $customers
            ]);
            break;

        case 'get_one':
            // Ambil satu pelanggan berdasarkan ID
            $id = $_REQUEST['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();

            if ($customer) {
                echo json_encode([
                    'success' => true,
                    'customer' => $customer
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Pelanggan tidak ditemukan'
                ]);
            }
            break;

        case 'add':
            // Tambah pelanggan baru
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $wa = $_POST['wa'] ?? '';

            // Validasi input
            if (empty($name) || empty($username) || empty($password) || empty($wa)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Semua field harus diisi'
                ]);
                exit;
            }

            // Cek apakah username sudah ada
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username sudah digunakan'
                ]);
                exit;
            }

            // Set join date
            $joinDate = date('d M Y');

            // Insert ke database
            $stmt = $pdo->prepare("
                INSERT INTO customers (name, username, password, wa, joinDate, activeServers)
                VALUES (?, ?, ?, ?, ?, 0)
            ");

            $result = $stmt->execute([$name, $username, $password, $wa, $joinDate]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pelanggan berhasil ditambahkan',
                    'customer_id' => $pdo->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menambahkan pelanggan'
                ]);
            }
            break;

        case 'edit':
            // Edit pelanggan
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $wa = $_POST['wa'] ?? '';

            // Validasi input
            if (empty($id) || empty($name) || empty($username) || empty($password) || empty($wa)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Semua field harus diisi'
                ]);
                exit;
            }

            // Cek apakah username sudah digunakan oleh user lain
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username sudah digunakan oleh pelanggan lain'
                ]);
                exit;
            }

            // Update database
            $stmt = $pdo->prepare("
                UPDATE customers
                SET name = ?, username = ?, password = ?, wa = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $result = $stmt->execute([$name, $username, $password, $wa, $id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Data pelanggan berhasil diupdate'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal mengupdate data pelanggan'
                ]);
            }
            break;

        case 'delete':
            // Hapus pelanggan
            $id = $_POST['id'] ?? 0;

            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID pelanggan tidak valid'
                ]);
                exit;
            }

            // Hapus dari database
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pelanggan berhasil dihapus'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menghapus pelanggan'
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
