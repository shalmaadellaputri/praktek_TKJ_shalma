<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (is_admin()) redirect('admin/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Sesi formulir tidak valid.';
    else {
        $u = q("SELECT * FROM users WHERE email = ? AND role IN ('admin','staff') LIMIT 1", [trim($_POST['email'] ?? '')])->fetch();
        if ($u && $u['status'] === 'active' && password_verify($_POST['pass'] ?? '', $u['password'])) { login_user($u); redirect('admin/index.php'); }
        $error = 'Akun admin/staff tidak valid.';
    }
}
$pageTitle = 'Login'; require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="panel">
  <div class="eyebrow"><?= h(APP_NAME) ?> / Control center</div>
  <h1 style="font-size:2rem;margin-bottom:20px">Admin login</h1>
  <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label>Email</label><input type="email" name="email" required></div>
    <div class="field"><label>Password</label><input type="password" name="pass" required></div>
    <button class="btn full">Masuk dashboard</button>
  </form>
  <p class="muted small center">Demo: admin@nova.test / admin123</p>
</div>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
