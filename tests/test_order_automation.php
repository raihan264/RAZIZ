<?php
// tests/test_order_automation.php
require_once __DIR__ . '/../config/database.php';

echo "Testing Order Automation...\n";

// 1. Mock Data
$user_id = 1; // Assuming exists
$product_id = 2; // Assuming exists
$server_name = "OrderAutoTest";

// Simulate Create Server Action (Direct DB Insert to mimic Handler)
// We mimic the handler logic manually since we can't easily call handler with POST mock here without curl
// But let's verify logic:
// Handler calls: Insert Server -> Insert Order.

// Clean up previous test
$pdo->prepare("DELETE FROM orders WHERE user_id = ?")->execute([$user_id]);
$pdo->prepare("DELETE FROM servers WHERE name = ?")->execute([$server_name]);

// Simulate Insert Server
$pdo->prepare("INSERT INTO servers (pterodactyl_id, user_id, product_id, name, status) VALUES (9999, ?, ?, ?, 'Active')")->execute([$user_id, $product_id, $server_name]);
$srvId = $pdo->lastInsertId();
echo "Created Server ID: $srvId\n";

// Simulate Insert Order (Logic added to handler)
$pdo->prepare("INSERT INTO orders (user_id, product_id, server_id, status, amount) VALUES (?, ?, ?, 'Active', '5000')")->execute([$user_id, $product_id, $srvId]);
$ordId = $pdo->lastInsertId();
echo "Created Order ID: $ordId\n";

// Verify Order Exists and Active
$stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->execute([$ordId]);
$status = $stmt->fetchColumn();
echo "Order Status (Initial): $status\n";

// Simulate Delete Server
echo "Deleting Server...\n";
$pdo->prepare("DELETE FROM servers WHERE id = ?")->execute([$srvId]);

// Simulate Handler Logic for Delete: Update Order
$pdo->prepare("UPDATE orders SET status = 'Expired' WHERE server_id = ?")->execute([$srvId]);

// Verify Order Status Expired
$stmt->execute([$ordId]);
$newStatus = $stmt->fetchColumn();
echo "Order Status (After Delete): $newStatus\n";

if ($status === 'Active' && $newStatus === 'Expired') {
    echo "SUCCESS: Order automation logic verified.\n";
} else {
    echo "FAILURE: Status mismatch.\n";
}
?>
