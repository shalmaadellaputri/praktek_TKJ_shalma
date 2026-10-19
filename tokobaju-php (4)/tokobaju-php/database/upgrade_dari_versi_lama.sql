-- Jalankan HANYA bila Anda sudah punya database `tokobaju` lama dan ingin
-- mempertahankan datanya. (Butuh MariaDB bawaan XAMPP / MySQL 8+.)
USE tokobaju;

ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) NULL AFTER payment_method;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_no VARCHAR(80) NULL AFTER payment_proof;

-- Pastikan setiap produk punya category_id, lalu buang kolom teks `category` yang ganda.
INSERT IGNORE INTO categories(name) SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category<>'';
UPDATE products p JOIN categories c ON c.name=p.category SET p.category_id=c.id WHERE p.category_id IS NULL;
ALTER TABLE products DROP COLUMN IF EXISTS category;
