<?php
// actions/order_handler.php
require_once __DIR__ . '/../config/database.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_all') {
    header('Content-Type: application/json');
    try {
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';

        $sql = "
            SELECT
                o.*,
                c.name as customer_name,
                c.wa as customer_wa,
                p.name as product_name
            FROM orders o
            JOIN customers c ON o.user_id = c.id
            JOIN products p ON o.product_id = p.id
        ";

        $params = [];
        if ($start_date && $end_date) {
            $sql .= " WHERE date(o.created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        // Format
        $formatted = array_map(function($ord) {
            return [
                'id' => "#ORD-" . $ord['id'],
                'user' => $ord['customer_name'],
                'wa' => $ord['customer_wa'],
                'plan' => $ord['product_name'],
                'status' => $ord['status'],
                'amount' => "Rp " . number_format((float)$ord['amount'], 0, ',', '.'),
                'raw_amount' => $ord['amount'],
                'date' => date('d M Y', strtotime($ord['created_at']))
            ];
        }, $orders);

        echo json_encode(['success' => true, 'orders' => $formatted]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'edit') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $status = $_POST['status'] ?? '';

    // Expected id format "#ORD-XX" or just "XX"
    $dbId = str_replace('#ORD-', '', $id);

    if (empty($dbId) || empty($amount) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'ID, Status, dan Amount harus diisi']);
        exit;
    }

    try {
        // Sanitize Amount
        $amount = preg_replace('/[^0-9]/', '', $amount);

        $pdo->prepare("UPDATE orders SET amount = ?, status = ? WHERE id = ?")->execute([$amount, $status, $dbId]);
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil diperbarui']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'delete') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? '';
    // Expected id format "#ORD-XX" or just "XX"
    $dbId = str_replace('#ORD-', '', $id);

    if (empty($dbId)) {
        echo json_encode(['success' => false, 'message' => 'ID Order diperlukan']);
        exit;
    }

    try {
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$dbId]);
        echo json_encode(['success' => true, 'message' => 'Pesanan dihapus permanen']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'export_csv') {
    // Generate CSV
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    $sql = "
        SELECT o.id, c.name, c.wa, p.name as plan, o.created_at, o.amount
        FROM orders o
        JOIN customers c ON o.user_id = c.id
        JOIN products p ON o.product_id = p.id
    ";

    $params = [];
    if ($start_date && $end_date) {
        $sql .= " WHERE date(o.created_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    }
    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_pesanan.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'PELANGGAN', 'NOMOR WA', 'PAKET', 'TANGGAL', 'HARGA']);

    $total = 0;
    foreach ($orders as $row) {
        fputcsv($output, [
            "#ORD-" . $row['id'],
            $row['name'],
            $row['wa'],
            $row['plan'],
            date('d M Y H:i', strtotime($row['created_at'])),
            $row['amount']
        ]);
        $total += (float)$row['amount'];
    }

    fputcsv($output, []); // Empty row
    fputcsv($output, ['', '', '', '', 'TOTAL PENDAPATAN', $total]);
    fclose($output);
    exit;

} elseif ($action === 'print_view') {
    // Generate simple HTML for printing (PDF)
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    $sql = "
        SELECT o.id, c.name, c.wa, p.name as plan, o.created_at, o.amount
        FROM orders o
        JOIN customers c ON o.user_id = c.id
        JOIN products p ON o.product_id = p.id
    ";

    $params = [];
    if ($start_date && $end_date) {
        $sql .= " WHERE date(o.created_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    }
    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $total = 0;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Pesanan</title>
        <style>
            body { font-family: sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .total { font-weight: bold; text-align: right; }
            .header { text-align: center; margin-bottom: 20px; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <h2>Laporan Pesanan</h2>
            <p>Periode: <?= $start_date ? "$start_date s/d $end_date" : "Semua Data" ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PELANGGAN</th>
                    <th>NOMOR WA</th>
                    <th>PAKET</th>
                    <th>TANGGAL</th>
                    <th>HARGA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): $total += (float)$o['amount']; ?>
                <tr>
                    <td>#ORD-<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['name']) ?></td>
                    <td><?= htmlspecialchars($o['wa']) ?></td>
                    <td><?= htmlspecialchars($o['plan']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                    <td>Rp <?= number_format($o['amount'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="total">TOTAL PENDAPATAN</td>
                    <td>Rp <?= number_format($total, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </body>
    </html>
    <?php
    exit;

} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
