<?php
function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function current_role(): ?string { return $_SESSION['user_role'] ?? null; }
function is_admin(): bool { return in_array(current_role(), ['admin', 'staff'], true); }
function is_superadmin(): bool { return current_role() === 'admin'; }

function login_user(array $u): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['user_name'] = $u['name'];
    $_SESSION['user_role'] = $u['role'];
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function require_login(): void { if (!is_logged_in()) { flash('err', 'Silakan masuk terlebih dahulu.'); redirect('login.php'); } }
function require_admin(): void { if (!is_admin()) redirect('admin/login.php'); }
function require_superadmin(): void {
    require_admin();
    if (!is_superadmin()) { flash('err', 'Hanya Admin yang boleh membuka halaman itu.'); redirect('admin/index.php'); }
}
