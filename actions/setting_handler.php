<?php
// actions/setting_handler.php
require_once __DIR__ . '/../config/database.php';

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
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
