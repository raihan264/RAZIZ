<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("PRAGMA table_info(orders)");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}
