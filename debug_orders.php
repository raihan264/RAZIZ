<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT id, amount, status, created_at FROM orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
