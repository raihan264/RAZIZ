<?php
// actions/user_handler.php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_all') {
    try {
        $stmt = $pdo->query("SELECT id, name, username, wa, activeServers, joinDate FROM customers ORDER BY created_at DESC");
        $customers = $stmt->fetchAll();
        echo json_encode(['success' => true, 'customers' => $customers]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
