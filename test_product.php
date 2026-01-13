<?php
/**
 * File untuk testing manual fungsi produk
 * Jalankan: php test_product.php
 */

echo "=== TEST SISTEM PRODUK RAZIZ ADMIN ===\n\n";

// 1. Test koneksi database
echo "1. Testing koneksi database...\n";
try {
    $pdo = require __DIR__ . '/config/database.php';
    echo "   ✅ Koneksi database berhasil!\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 2. Test tambah produk
echo "2. Testing tambah produk...\n";
try {
    $stmt = $pdo->prepare("
        INSERT INTO products (name, cpu, ram, disk, stock, price)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([
        'Test Product - 4 GB',
        '120%',
        '4 GB',
        '5 GB NVMe',
        '50 Slots',
        '8.000'
    ]);

    if ($result) {
        $productId = $pdo->lastInsertId();
        echo "   ✅ Produk berhasil ditambahkan dengan ID: $productId\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 3. Test get all products
echo "3. Testing get all products...\n";
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();
    echo "   ✅ Berhasil mengambil " . count($products) . " produk\n";

    if (count($products) > 0) {
        echo "\n   Daftar Produk:\n";
        echo "   " . str_repeat("-", 80) . "\n";
        foreach ($products as $p) {
            echo "   ID: {$p['id']} | {$p['name']} | CPU: {$p['cpu']} | RAM: {$p['ram']} | Harga: Rp {$p['price']}\n";
        }
        echo "   " . str_repeat("-", 80) . "\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 4. Test update produk (update produk terakhir)
if (count($products) > 0) {
    echo "4. Testing update produk...\n";
    try {
        $lastProduct = $products[0];
        $stmt = $pdo->prepare("
            UPDATE products
            SET name = ?, price = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $result = $stmt->execute([
            'Updated Product Name',
            '10.000',
            $lastProduct['id']
        ]);

        if ($result) {
            echo "   ✅ Produk ID {$lastProduct['id']} berhasil diupdate\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

// 5. Test delete produk (hapus produk yang baru dibuat)
if (count($products) > 0) {
    echo "5. Testing delete produk...\n";
    try {
        $lastProduct = $products[0];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$lastProduct['id']]);

        if ($result) {
            echo "   ✅ Produk ID {$lastProduct['id']} berhasil dihapus\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

// 6. Verifikasi total produk
echo "6. Verifikasi total produk...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $result = $stmt->fetch();
    echo "   ✅ Total produk di database: {$result['total']}\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "=== TEST SELESAI ===\n";
echo "\nTips: Jalankan 'php -S localhost:8000' untuk test via browser.\n";
?>
