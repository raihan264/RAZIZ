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
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $wa = $_POST['wa'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username dan Password wajib diisi']);
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
        // Use username as name to satisfy potential DB constraints
        $sql = "INSERT INTO customers (name, username, password, wa, joinDate) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $username, $password, $wa, $joinDate]);

        echo json_encode(['success' => true, 'message' => 'Pelanggan berhasil ditambahkan']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'edit') {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $wa = $_POST['wa'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        exit;
    }

    try {
        // Get existing data first to handle partial updates
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            exit;
        }

        // Use existing values if new ones are empty
        $newUsername = !empty($username) ? $username : $existing['username'];
        $newPassword = !empty($password) ? $password : $existing['password'];
        $newWa = !empty($wa) ? $wa : $existing['wa'];

        // Check duplicate username if changed
        if ($newUsername !== $existing['username']) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ? AND id != ?");
            $stmt->execute([$newUsername, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username sudah digunakan orang lain']);
                exit;
            }
        }

        // Use username as name
        $sql = "UPDATE customers SET name = ?, username = ?, password = ?, wa = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newUsername, $newUsername, $newPassword, $newWa, $id]);

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
