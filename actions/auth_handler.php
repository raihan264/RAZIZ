<?php
// actions/auth_handler.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username dan Password wajib diisi']);
        exit;
    }

    // 1. Check Admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        echo json_encode(['success' => true, 'role' => 'admin', 'message' => 'Login Admin Berhasil']);
        exit;
    }

    // 2. Check Customer
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user'] = $user;
        echo json_encode(['success' => true, 'role' => 'user', 'message' => 'Login Berhasil']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Username atau Password salah']);

} elseif ($action === 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $wa = $_POST['wa'] ?? '';

    if (empty($username) || empty($password) || empty($wa)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Check duplicate
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        exit;
    }

    try {
        $joinDate = date('d M Y');
        $stmt = $pdo->prepare("INSERT INTO customers (username, name, password, wa, joinDate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $username, $password, $wa, $joinDate]); // Name = Username default

        echo json_encode(['success' => true, 'message' => 'Registrasi Berhasil. Silakan Login.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mendaftar: ' . $e->getMessage()]);
    }

} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);

} elseif ($action === 'change_password') {
    if (!isset($_SESSION['user_logged_in'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $new_password = $_POST['new_password'] ?? '';
    $user_id = $_SESSION['user']['id'];

    if (empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password baru wajib diisi']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
