<?php
/** Dimuat paling awal oleh setiap halaman. */
require_once __DIR__ . '/../config/app.php';
define('ROOT_PATH', dirname(__DIR__));

// Base URL otomatis (mis. /tokobaju-php) agar link benar dari root maupun /admin
$__root = str_replace('\\', '/', realpath(ROOT_PATH));
$__doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
define('BASE_URL', rtrim(str_replace($__doc, '', $__root), '/'));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/models.php';
