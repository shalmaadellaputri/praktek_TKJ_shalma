<?php
function h($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function redirect(string $path): never {
    header('Location: ' . (preg_match('#^(https?:)?//#', $path) ? $path : url($path)));
    exit;
}
function back(string $fallback = 'index.php'): never {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    redirect($ref && parse_url($ref, PHP_URL_HOST) === explode(':', $host)[0] ? $ref : $fallback);
}
function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'message' => $msg]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(?string $t): bool { return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t ?? ''); }
/** Hentikan request POST yang tidak membawa token CSRF valid. */
function require_post(string $fallback = 'index.php'): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf($_POST['csrf'] ?? '')) {
        flash('err', 'Sesi formulir tidak valid. Silakan ulangi.');
        redirect($fallback);
    }
}

function status_class(string $s): string {
    return match (true) {
        in_array($s, ['Diterima', 'Selesai'], true) => 'st-ok',
        in_array($s, ['Ditolak', 'Dibatalkan'], true) => 'st-bad',
        in_array($s, ['Diproses', 'Dikirim'], true) => 'st-run',
        default => 'st-wait',
    };
}
function fmt_date(?string $d): string { return $d ? date('d M Y, H:i', strtotime($d)) : '-'; }

/** Simpan gambar upload (jpg/png/webp, maks 5 MB). null = tidak ada file, false = tidak valid. */
function save_upload(?array $file): string|false|null {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5_000_000) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) return false;
    if (@getimagesize($file['tmp_name']) === false) return false;
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], ROOT_PATH . '/uploads/' . $name) ? $name : false;
}
function delete_upload(?string $name): void {
    if ($name && preg_match('/^[a-f0-9]+\.(jpg|jpeg|png|webp)$/', $name)) @unlink(ROOT_PATH . '/uploads/' . $name);
}

/** Pagination sederhana. Mengembalikan [offset, totalPages, page]. */
function paginate(int $total, int $per = PER_PAGE): array {
    $pages = max(1, (int)ceil($total / $per));
    $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    return [($page - 1) * $per, $pages, $page];
}
function pager(int $pages, int $page): string {
    if ($pages <= 1) return '';
    $out = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs = http_build_query(array_merge($_GET, ['page' => $i]));
        $out .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . h($qs) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}
