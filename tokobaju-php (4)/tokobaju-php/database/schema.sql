-- NOVA Fashion Store — skema baru (instalasi bersih)
-- Impor lewat phpMyAdmin: buat database `tokobaju`, lalu Import file ini.
CREATE DATABASE IF NOT EXISTS tokobaju CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tokobaju;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 phone VARCHAR(30) NULL,
 address TEXT NULL,
 role ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(role), INDEX(status)
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL UNIQUE,
 description VARCHAR(255) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 category_id INT NULL,
 name VARCHAR(150) NOT NULL,
 price INT NOT NULL DEFAULT 0,
 stock INT NOT NULL DEFAULT 0,
 description TEXT,
 photo VARCHAR(255) NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(category_id), INDEX(status),
 FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_code VARCHAR(30) NOT NULL UNIQUE,
 user_id INT NOT NULL,
 customer_name VARCHAR(100),
 address TEXT,
 phone VARCHAR(30),
 payment_method VARCHAR(50),
 payment_proof VARCHAR(255) NULL,
 tracking_no VARCHAR(80) NULL,
 total INT NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'Menunggu Konfirmasi',
 note TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX(user_id), INDEX(status), INDEX(created_at),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 product_id INT NULL,
 product_name VARCHAR(150),
 price INT,
 qty INT,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 INDEX(product_id)
) ENGINE=InnoDB;

-- Login demo: admin123 (admin & staff), user12345 (customer)
INSERT INTO users(name,email,password,role,status) VALUES
('Administrator','admin@nova.test','$2y$12$lpecMwR4AEk3T1IUWiAQ5eLix0tMcgLATuawSXdBzLv3TXnFEUwym','admin','active'),
('Demo Staff','staff@nova.test','$2y$12$lpecMwR4AEk3T1IUWiAQ5eLix0tMcgLATuawSXdBzLv3TXnFEUwym','staff','active'),
('Demo User','user@nova.test','$2y$12$/tZ1/5y4v.kat6yWDCf0J.RJAtwBo4xtX8b9YmRQNfyIR29CqNv6m','customer','active');

INSERT INTO categories(name,description) VALUES
('Kaos','T-shirt dan atasan casual'),
('Kemeja','Kemeja dan overshirt'),
('Jaket','Outerwear dan jaket'),
('Celana','Celana casual dan formal'),
('Hoodie','Hoodie dan sweater'),
('Dress','Dress dan outfit wanita');

INSERT INTO products(category_id,name,price,stock,description) VALUES
(2,'Kemeja Flanel Heritage',189000,12,'Kemeja flanel tebal dengan bahan nyaman untuk aktivitas sehari-hari.'),
(1,'T-Shirt Basic Onyx',99000,30,'Kaos basic dengan potongan modern dan mudah dipadukan.'),
(3,'Jaket Denim Classic',329000,8,'Jaket denim klasik dengan karakter kuat untuk gaya casual.'),
(4,'Celana Chino Slim',219000,15,'Celana chino berbahan stretch yang nyaman dipakai seharian.'),
(5,'Hoodie Oversize Grey',249000,20,'Hoodie fleece dengan potongan relaxed dan nyaman.'),
(6,'Dress Casual Linen',279000,10,'Dress linen ringan untuk tampilan minimalis dan elegan.'),
(2,'Overshirt Monochrome',229000,14,'Overshirt serbaguna dengan nuansa monokrom.'),
(4,'Cargo Everyday Black',239000,18,'Celana cargo hitam dengan ruang penyimpanan praktis.');
