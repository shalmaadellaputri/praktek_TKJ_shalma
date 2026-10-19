# NOVA Fashion Store (PHP + MySQL / XAMPP)

## Instalasi
1. Salin folder `tokobaju-php` ke `C:\xampp\htdocs\`.
2. Nyalakan **Apache** dan **MySQL** di XAMPP.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/schema.sql` (database `tokobaju` dibuat otomatis).
   - Sudah punya database lama dan ingin menyimpan datanya? Jalankan `database/upgrade_dari_versi_lama.sql` saja.
4. Buka `http://localhost/tokobaju-php/`. Admin: `http://localhost/tokobaju-php/admin/`.
5. Atur koneksi DB dan nomor rekening di `config/app.php`.

## Login demo
| Role | Email | Password |
|---|---|---|
| Admin | admin@nova.test | admin123 |
| Staff | staff@nova.test | admin123 |
| Customer | user@nova.test | user12345 |

## Struktur
```
config/app.php          pengaturan (DB, rekening, status pesanan)
includes/               bootstrap, db, helpers, auth, models (logika bersama)
includes/partials/      header/footer toko & admin
actions/                proses POST: keranjang, batal pesanan, bukti transfer
admin/                  dashboard, pesanan, produk, kategori, user, laporan
assets/css|js|img/      base.css (bersama), store.css, admin.css
database/               schema.sql, upgrade_dari_versi_lama.sql
uploads/                foto produk & bukti transfer
```
Halaman toko (index, product, cart, checkout, login, register, profile, orders, order_detail) ada di root.

## Fitur
**Customer:** katalog + cari/filter/urut + halaman, detail & produk serupa, keranjang, checkout, unggah bukti transfer, lacak status & resi, batalkan pesanan, profil + ganti password.
**Staff/Admin:** dashboard (omzet, grafik 7 hari, stok menipis), pesanan (cari, filter, resi, stok otomatis kembali saat ditolak/dibatalkan), produk (CRUD, foto, aktif/nonaktif), laporan + unduh CSV.
**Admin:** kelola user (role, aktif, reset password, hapus) dan kategori.

## Keamanan
PDO prepared statements, password hash, CSRF di semua aksi POST (termasuk keranjang & logout), kontrol role di tiap halaman admin, validasi upload gambar, folder `uploads` tidak menjalankan PHP.

## Mengganti foto di menu Shop
Ganti file `assets/img/menu-shop.jpg` dengan foto Anda (nama file tetap sama, rasio 4:5, mis. 800×1000 px).
Foto hero beranda: `assets/img/hero.jpg`.
