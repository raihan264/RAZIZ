<?php
// actions/sc_handler.php
// Suppress output to ensure clean JSON
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Allow read actions without strict admin login (e.g. for members)
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
$isMember = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'];

$action = $_REQUEST['action'] ?? '';

// Ensure no content before this
ob_clean();
header('Content-Type: application/json');

if ($action === 'upload') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    if (empty($name) || !isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'Nama dan File wajib diisi']);
        exit;
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext !== 'zip') {
        echo json_encode(['success' => false, 'message' => 'Hanya file ZIP yang diperbolehkan']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/sc/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat folder upload']);
            exit;
        }
    }

    // Generate unique filename
    $filename = uniqid('sc_') . '_' . time() . '.zip';
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        try {
            // Get max sort order
            $stmt = $pdo->query("SELECT MAX(sort_order) FROM source_codes");
            $maxOrder = $stmt->fetchColumn() ?: 0;
            $newOrder = $maxOrder + 1;

            $stmt = $pdo->prepare("INSERT INTO source_codes (name, file_path, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $filename, $newOrder]);

            echo json_encode(['success' => true, 'message' => 'Upload berhasil']);
        } catch (PDOException $e) {
            // Delete file if DB insert fails
            if (file_exists($targetPath)) unlink($targetPath);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload file (move_uploaded_file error). Cek permission folder.']);
    }

} elseif ($action === 'delete') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID diperlukan']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT file_path FROM source_codes WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetchColumn();

        if ($file) {
            $filePath = __DIR__ . '/../uploads/sc/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $pdo->prepare("DELETE FROM source_codes WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'File dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'reorder') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $id = $_POST['id'] ?? '';
    $order = $_POST['order'] ?? '';

    if (empty($id) || !is_numeric($order)) {
        echo json_encode(['success' => false, 'message' => 'ID dan Urutan diperlukan']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE source_codes SET sort_order = ? WHERE id = ?");
        $stmt->execute([$order, $id]);
        echo json_encode(['success' => true, 'message' => 'Urutan diperbarui']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'list') {
    if (!$isAdmin && !$isMember) {
        echo json_encode(['success' => false, 'message' => 'Login required']);
        exit;
    }

    if ($isMember && !$isAdmin) {
        // Fix: Use $_SESSION['user']['id'] not $_SESSION['user_id']
        $userId = $_SESSION['user']['id'] ?? 0;

        // Count active servers dynamically (Source of Truth)
        // We check for 'Active' status specifically as requested by logic (usually < 30 days)
        // Or just any server? Prompt said "memiliki minimal 1 server aktif" (have at least 1 active server).
        // Since we have dynamic status logic, relying on DB status 'Active' is safer if the cron updates it.
        // But for now, let's count all non-expired servers or just servers in the table that aren't deleted?
        // Let's count servers where status != 'Expired' to be safe, or just count all if 'Active' means 'Exists'.
        // To be strict with "Active":
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servers WHERE user_id = ? AND status = 'Active'");
        $stmt->execute([$userId]);
        $activeServers = $stmt->fetchColumn() ?: 0;

        if ($activeServers < 1) {
            echo json_encode(['success' => true, 'sc' => [], 'message' => 'Need active server']);
            exit;
        }
    }

    try {
        $stmt = $pdo->query("SELECT id, name, sort_order, created_at FROM source_codes ORDER BY sort_order ASC, created_at DESC");
        $sc = $stmt->fetchAll();

        // Format date
        foreach ($sc as &$item) {
            $item['date'] = date('d M Y', strtotime($item['created_at']));
        }

        echo json_encode(['success' => true, 'sc' => $sc]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'download') {
    // Check perm
    if (!$isAdmin && !$isMember) {
        die('Access Denied');
    }

    $id = $_GET['id'] ?? '';
    if (empty($id)) die('Invalid ID');

    // If member, check active servers again to prevent direct link abuse
    if ($isMember && !$isAdmin) {
        $userId = $_SESSION['user']['id'] ?? 0;

        // Check real count primarily
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servers WHERE user_id = ? AND status = 'Active'");
        $stmt->execute([$userId]);
        $activeServers = $stmt->fetchColumn() ?: 0;

        if ($activeServers < 1) die('Active server required to download');
    }

    try {
        $stmt = $pdo->prepare("SELECT name, file_path FROM source_codes WHERE id = ?");
        $stmt->execute([$id]);
        $sc = $stmt->fetch();

        if ($sc) {
            $filePath = __DIR__ . '/../uploads/sc/' . $sc['file_path'];
            if (file_exists($filePath)) {
                // Clear buffer before file output
                ob_clean();
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($sc['name']) . '.zip"');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                die('File missing on server');
            }
        } else {
            die('SC Not Found');
        }
    } catch (PDOException $e) {
        die($e->getMessage());
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
