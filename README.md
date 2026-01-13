# README - Sistem Manajemen Produk RAZIZ ADMIN

## 📋 Deskripsi
Sistem manajemen produk untuk halaman admin dengan SQLite database. Sistem ini memungkinkan admin untuk menambah, melihat, mengedit, dan menghapus produk dengan tampilan UI yang modern dan responsif.

## 🚀 Cara Instalasi

### 1. Requirement
- PHP 7.4 atau lebih tinggi
- Extension PDO SQLite (biasanya sudah terinstall default)
- Web server (Apache/Nginx) atau PHP Built-in Server

### 2. Setup Database

**OTOMATIS!** Database dan tabel akan dibuat secara otomatis saat pertama kali aplikasi diakses.

Anda juga bisa menjalankan script inisialisasi manual (opsional):

```bash
php init_db.php
```

Script ini akan membuat:
- Folder `database/`
- File database `database/toko.db`
- Tabel `products` dengan kolom: id, name, cpu, ram, disk, stock, price, created_at, updated_at

> **Catatan**: Karena ada auto-create table di `config/database.php`, Anda tidak perlu menjalankan `init_db.php` secara manual. Tabel akan otomatis dibuat saat pertama kali mengakses halaman admin.

### 3. Jalankan Aplikasi

**Opsi A: Menggunakan PHP Built-in Server**
```bash
php -S localhost:8000
```

**Opsi B: Menggunakan XAMPP/WAMP**
- Copy folder V2 ke `htdocs/` atau `www/`
- Akses melalui browser: `http://localhost/V2/admin.php`

### 4. Akses Halaman Admin
Buka browser dan akses:
```
http://localhost:8000/admin.php
```

## 📁 Struktur File

```
V2/
├── admin.php                    # Halaman admin utama (UI lengkap)
├── init_db.php                  # Script inisialisasi database
├── config/
│   └── database.php            # Koneksi database SQLite
├── actions/
│   └── product_handler.php     # Handler CRUD produk (API endpoint)
└── database/
    └── toko.db                 # File database SQLite (dibuat otomatis)
```

## ✨ Fitur Produk

### 1. Tambah Produk
- Klik tombol "Tambah Produk" di halaman produk
- Isi form dengan data:
  - Nama Produk (contoh: "Pro Gaming - 4 GB")
  - CPU (contoh: "120%")
  - RAM (contoh: "4 GB")
  - Disk (contoh: "5 GB NVMe")
  - Stok (contoh: "50 Slots" atau "Unlimited")
  - Harga (contoh: "8.000")
- Klik "Simpan"

### 2. Lihat Produk
- Produk akan otomatis ditampilkan dalam tabel
- Menampilkan: Nama, Specs (CPU/RAM/Disk), Stok, Harga
- Data diambil real-time dari database SQLite

### 3. Edit Produk
- Klik tombol "Edit" (ikon pensil) pada produk yang ingin diubah
- Update data yang diinginkan
- Klik "Simpan"

### 4. Hapus Produk
- Klik tombol "Hapus" (ikon tempat sampah)
- Konfirmasi penghapusan
- Produk akan dihapus dari database

## 🔧 API Endpoint (product_handler.php)

### Get All Products
```
GET/POST: actions/product_handler.php?action=get_all
Response: { success: true, products: [...] }
```

### Get One Product
```
GET/POST: actions/product_handler.php?action=get_one&id=1
Response: { success: true, product: {...} }
```

### Add Product
```
POST: actions/product_handler.php
Body: action=add&name=...&cpu=...&ram=...&disk=...&stock=...&price=...
Response: { success: true, message: "...", product_id: 1 }
```

### Edit Product
```
POST: actions/product_handler.php
Body: action=edit&id=1&name=...&cpu=...&ram=...&disk=...&stock=...&price=...
Response: { success: true, message: "..." }
```

### Delete Product
```
POST: actions/product_handler.php
Body: action=delete&id=1
Response: { success: true, message: "..." }
```

## 🎨 Fitur UI

- ✅ Tampilan modern dengan Tailwind CSS
- ✅ Dark theme dengan aksen hijau
- ✅ Responsive untuk semua device
- ✅ Modal popup untuk form tambah/edit
- ✅ Animasi smooth dan micro-interactions
- ✅ Icon dari Lucide Icons
- ✅ Sidebar navigasi yang collapsible
- ✅ Notifikasi success/error

## 📝 Catatan Penting

1. **Database SQLite** - File database akan dibuat otomatis di folder `database/toko.db`
2. **Permissions** - Pastikan folder `database/` memiliki write permission
3. **Tampilan Dipertahankan** - UI tidak berubah, hanya menambahkan fungsi backend
4. **Produk Only** - Saat ini hanya fitur produk yang terhubung ke database
5. **Fitur Lain** - Orders, Users, Servers masih menggunakan data mock (array JavaScript)

## 🐛 Troubleshooting

### Database Error
Jika muncul error database:
- Pastikan PHP SQLite extension aktif
- Cek permission folder `database/`
- Jalankan ulang `php init_db.php`

### Products Tidak Muncul
- Buka browser console (F12) untuk cek error
- Pastikan path ke `actions/product_handler.php` benar
- Cek apakah file database sudah terbuat

### Form Tidak Submit
- Pastikan JavaScript tidak error (cek console)
- Pastikan koneksi internet untuk CDN (Tailwind & Lucide)
- Clear cache browser

## 🔜 Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:
- [ ] Koneksi database untuk Orders
- [ ] Koneksi database untuk Users/Customers
- [ ] Koneksi database untuk Servers
- [ ] Integrasi API Pterodactyl
- [ ] Authentication/Login admin
- [ ] Upload gambar produk
- [ ] Export/Import data
- [ ] Search & filter produk

---

**Dibuat dengan ❤️ untuk RAZIZ ADMIN DASHBOARD**
