<?php
/**
 * Konfigurasi aplikasi NOVA Fashion Store.
 * Sesuaikan DB_* dengan MySQL XAMPP Anda (default: root tanpa password).
 */
define('APP_NAME', 'NOVA');
define('DB_HOST', 'localhost');
define('DB_NAME', 'tokobaju');
define('DB_USER', 'root');
define('DB_PASS', '');

// Rekening tujuan transfer (ditampilkan di halaman pesanan)
define('BANK_NAME', 'BCA');
define('BANK_ACCOUNT', '1234567890');
define('BANK_HOLDER', 'NOVA Fashion Store');

// Batas stok yang dianggap "menipis" di admin
define('LOW_STOCK', 5);
// Jumlah data per halaman pada tabel admin
define('PER_PAGE', 15);

define('ORDER_STATUSES', ['Menunggu Konfirmasi','Diterima','Diproses','Dikirim','Selesai','Ditolak','Dibatalkan']);
// Status yang dihitung sebagai penjualan sah
define('SALES_STATUSES', ['Diterima','Diproses','Dikirim','Selesai']);
// Status yang mengembalikan stok
define('RESTOCK_STATUSES', ['Ditolak','Dibatalkan']);
