<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT * FROM source_codes");
    echo "Table exists. Count: " . count($stmt->fetchAll()) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
