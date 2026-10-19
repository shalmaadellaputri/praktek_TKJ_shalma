<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect('index.php');

$error = ''; $f = ['name' => '', 'email' => '', 'phone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = ['name' => trim($_POST['name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')];
    $pass = $_POST['pass'] ?? '';
    if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    elseif (mb_strlen($f['name']) < 2) $error = 'Nama minimal 2 karakter.';
    elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    elseif (strlen($pass) < 6) $error = 'Password minimal 6 karakter.';
    elseif ($pass !== ($_POST['pass2'] ?? '')) $error = 'Konfirmasi password tidak sama.';
    elseif (q("SELECT id FROM users WHERE email = ?", [$f['email']])->fetch()) $error = 'Email sudah terdaftar.';
    else {
        q("INSERT INTO users (name,email,password,phone,role,status) VALUES (?,?,?,?, 'customer','active')",
          [$f['name'], $f['email'], password_hash($pass, PASSWORD_DEFAULT), $f['phone']]);
        $u = q("SELECT * FROM users WHERE email = ?", [$f['email']])->fetch();
        login_user($u);
        flash('ok', 'Akun berhasil dibuat. Selamat berbelanja!');
        redirect('index.php');
    }
}
$pageTitle = 'Daftar — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>
<div class="page narrow">
  <div class="eyebrow">Account</div>
  <h1>Buat akun</h1>
  <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label>Nama</label><input type="text" name="name" value="<?= h($f['name']) ?>" required></div>
    <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($f['email']) ?>" required></div>
    <div class="field"><label>No. HP <small>(opsional)</small></label><input type="tel" name="phone" value="<?= h($f['phone']) ?>"></div>
    <div class="row">
      <div class="field"><label>Password</label><input type="password" name="pass" minlength="6" required></div>
      <div class="field"><label>Ulangi password</label><input type="password" name="pass2" minlength="6" required></div>
    </div>
    <button class="btn full">Daftar</button>
  </form>
  <p class="center muted small" style="margin-top:20px">Sudah punya akun? <a href="<?= url('login.php') ?>">Masuk</a></p>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
