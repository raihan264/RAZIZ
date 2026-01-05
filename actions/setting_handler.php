<?php
// actions/setting_handler.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PterodactylService.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get') {
    try {
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();

        if (!$settings) {
            // Default settings structure if empty
            $settings = [
                'panel_domain' => '',
                'plta_key' => '',
                'pltc_key' => '',
                'contact_email' => '',
                'contact_wa' => '',
                'contact_address' => ''
            ];
        }

        echo json_encode(['success' => true, 'settings' => $settings]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'save') {
    $panel_domain = $_POST['panel_domain'] ?? '';
    $plta_key = $_POST['plta_key'] ?? '';
    $pltc_key = $_POST['pltc_key'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_wa = $_POST['contact_wa'] ?? '';
    $contact_address = $_POST['contact_address'] ?? '';

    try {
        // Check if settings exist
        $stmt = $pdo->query("SELECT id FROM settings LIMIT 1");
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $sql = "UPDATE settings SET panel_domain = ?, plta_key = ?, pltc_key = ?, contact_email = ?, contact_wa = ?, contact_address = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$panel_domain, $plta_key, $pltc_key, $contact_email, $contact_wa, $contact_address, $exists]);
        } else {
            $sql = "INSERT INTO settings (panel_domain, plta_key, pltc_key, contact_email, contact_wa, contact_address) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$panel_domain, $plta_key, $pltc_key, $contact_email, $contact_wa, $contact_address]);
        }

        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'test_connection') {
    try {
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();

        if (!$settings || empty($settings['plta_key']) || empty($settings['panel_domain'])) {
            throw new Exception("Domain dan API Key belum disimpan.");
        }

        $ptero = new PterodactylService($settings['panel_domain'], $settings['plta_key']);

        // Try to fetch users (limit 1) to test auth
        // We need to access a protected method or add a public test method to Service.
        // Actually, checkUserExists calls request(), which is protected.
        // We can expose a public 'ping' method in service, or just call checkUserExists with a dummy.
        // Let's modify Service to have a ping or generic request helper if possible?
        // Or just use checkUserExists('random_check_123'). If it returns null (not found) or ID, auth works.
        // If it throws exception, auth failed.

        $ptero->checkUserExists('random_ping_check');

        echo json_encode(['success' => true, 'message' => 'Koneksi ke Panel Berhasil!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Koneksi Gagal: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
