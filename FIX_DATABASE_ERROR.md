# Fix: Error "no such table: products"

## Masalah yang Diperbaiki

**Error di localhost:**
```
Error: Database error: SQLSTATE[HY000]: General error: 1 no such table: products
```

**Error di hosting:**
```
Terjadi kesalahan saat menyimpan produk
```

## Penyebab

Tabel `products` belum dibuat di database SQLite karena user belum menjalankan script `init_db.php`.

## Solusi

Menambahkan **auto-create table** di file `config/database.php` sehingga tabel `products` akan otomatis dibuat saat pertama kali koneksi database dilakukan.

## Perubahan yang Dilakukan

### File: `config/database.php`

Menambahkan query `CREATE TABLE IF NOT EXISTS` setelah koneksi PDO berhasil:

```php
// Auto-create table products jika belum ada
$pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        cpu TEXT NOT NULL,
        ram TEXT NOT NULL,
        disk TEXT NOT NULL,
        stock TEXT NOT NULL,
        price TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
```

## Hasil

✅ **Sekarang database dan tabel akan otomatis dibuat** saat pertama kali aplikasi diakses
✅ **Tidak perlu menjalankan `init_db.php` secara manual** (tetapi masih bisa dijalankan jika mau)
✅ **Error "no such table: products" tidak akan muncul lagi**

## Testing

1. Hapus folder `database/` (jika ada)
2. Akses `http://localhost:8000/admin.php`
3. Klik menu "Produk / Paket"
4. Klik "Tambah Produk"
5. Isi form dan simpan
6. ✅ Produk berhasil disimpan tanpa error!

File `database/toko.db` akan otomatis dibuat dengan tabel `products` di dalamnya.
