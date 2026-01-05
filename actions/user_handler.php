<?php
// actions/user_handler.php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_all') {
    try {
        // Count active servers dynamically
        $sql = "
            SELECT c.id, c.name, c.username, c.wa, c.joinDate, COUNT(s.id) as activeServers
            FROM customers c
            LEFT JOIN servers s ON c.id = s.user_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ";
        $stmt = $pdo->query($sql);
        $customers = $stmt->fetchAll();
        echo json_encode(['success' => true, 'customers' => $customers]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'add') {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $wa = $_POST['wa'] ?? '';

    if (empty($name) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Nama, Username, dan Password wajib diisi']);
        exit;
    }

    try {
        // Check duplicate
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah terdaftar']);
            exit;
        }

        $joinDate = date('d M Y');
        $sql = "INSERT INTO customers (name, username, password, wa, joinDate, activeServers) VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $username, $password, $wa, $joinDate]);

        echo json_encode(['success' => true, 'message' => 'Pelanggan berhasil ditambahkan']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'edit') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $wa = $_POST['wa'] ?? '';

    if (empty($id) || empty($name) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    try {
        // Check duplicate username if changed
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan orang lain']);
            exit;
        }

        $sql = "UPDATE customers SET name = ?, username = ?, password = ?, wa = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $username, $password, $wa, $id]);

        echo json_encode(['success' => true, 'message' => 'Data pelanggan diperbarui']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        exit;
    }

    try {
        // Optional: Check if user has servers. If yes, prevent delete or cascading delete.
        // For now, strict delete.
        $stmt = $pdo->prepare("SELECT count(*) FROM servers WHERE user_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Gagal: Pelanggan ini memiliki server aktif.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Pelanggan dihapus']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
