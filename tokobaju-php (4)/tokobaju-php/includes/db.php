<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $dsn = getenv('TOKOBAJU_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, getenv('TOKOBAJU_DSN') ? null : DB_USER, getenv('TOKOBAJU_DSN') ? null : DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Koneksi database gagal. Nyalakan MySQL di XAMPP, impor database/schema.sql, lalu cek config/app.php.');
    }
    return $pdo;
}

/** Jalankan query dan kembalikan statement. */
function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
