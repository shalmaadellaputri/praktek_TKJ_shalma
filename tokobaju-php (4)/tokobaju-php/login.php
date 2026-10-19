<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'index.php');

$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    else {
        $u = q("SELECT * FROM users WHERE email = ? LIMIT 1", [$email])->fetch();
        if ($u && $u['status'] === 'active' && password_verify($_POST['pass'] ?? '', $u['password'])) {
            login_user($u);
            flash('ok', 'Selamat datang kembali, ' . $u['name'] . '!');
            redirect(is_admin() ? 'admin/index.php' : (!empty($_SESSION['cart']) ? 'cart.php' : 'index.php'));
        }
        $error = 'Email/password salah atau akun tidak aktif.';
    }
}
$pageTitle = 'Login — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>
<div class="page narrow">
  <div class="eyebrow">Account</div>
  <h1>Masuk ke <?= h(APP_NAME) ?></h1>
  <p class="muted" style="margin-top:-12px;margin-bottom:24px">Masuk untuk melanjutkan belanja dan melihat pesanan Anda.</p>
  <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= h($email) ?>" autocomplete="email" required></div>
    <div class="field"><label for="pass">Password</label><input id="pass" type="password" name="pass" autocomplete="current-password" required></div>
    <button class="btn full">Masuk</button>
  </form>
  <p class="center muted small" style="margin-top:20px">Belum punya akun? <a href="<?= url('register.php') ?>">Daftar sekarang</a></p>
  <p class="center muted small">Demo: user@nova.test / user12345 · admin@nova.test / admin123</p>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
