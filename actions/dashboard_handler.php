<?php
// actions/dashboard_handler.php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_stats') {
    try {
        // 1. Weekly Revenue (This Week: Last 7 days)
        $stmt = $pdo->query("SELECT SUM(CAST(amount AS INTEGER)) FROM orders WHERE status = 'Active' AND created_at >= date('now', '-7 days')");
        $revenue_this_week = $stmt->fetchColumn() ?: 0;

        // 2. Last Week Revenue (7-14 days ago) for Percentage Calculation
        $stmt = $pdo->query("SELECT SUM(CAST(amount AS INTEGER)) FROM orders WHERE status = 'Active' AND created_at BETWEEN date('now', '-14 days') AND date('now', '-7 days')");
        $revenue_last_week = $stmt->fetchColumn() ?: 0;

        // 3. Active Servers (Count all in table)
        $stmt = $pdo->query("SELECT COUNT(*) FROM servers");
        $active_servers = $stmt->fetchColumn() ?: 0;

        // 4. Total Users
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
        $total_users = $stmt->fetchColumn() ?: 0;

        // 5. Total Products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $total_products = $stmt->fetchColumn() ?: 0;

        // Calculate Percentage
        $percentage = 0;
        if ($revenue_last_week > 0) {
            $percentage = (($revenue_this_week - $revenue_last_week) / $revenue_last_week) * 100;
        } else if ($revenue_this_week > 0) {
            $percentage = 100;
        }

        echo json_encode([
            'success' => true,
            'stats' => [
                'revenue' => 'Rp ' . number_format($revenue_this_week, 0, ',', '.'), // Display Weekly Revenue
                'servers' => $active_servers,
                'users' => $total_users,
                'products' => $total_products,
                'percentage' => round($percentage, 1) . '%'
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
