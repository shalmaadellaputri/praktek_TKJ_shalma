<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$u = q("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
if (!$u) redirect('logout.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? ''); $address = trim($_POST['address'] ?? '');
    $new = $_POST['newpass'] ?? '';
    if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Sesi formulir tidak valid.';
    elseif (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Nama dan email harus valid.';
    elseif (q("SELECT id FROM users WHERE email = ? AND id <> ?", [$email, $u['id']])->fetch()) $error = 'Email sudah dipakai akun lain.';
    elseif ($new !== '' && strlen($new) < 6) $error = 'Password baru minimal 6 karakter.';
    elseif ($new !== '' && !password_verify($_POST['oldpass'] ?? '', $u['password'])) $error = 'Password lama salah.';
    else {
        q("UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=?", [$name, $email, $phone, $address, $u['id']]);
        if ($new !== '') q("UPDATE users SET password=? WHERE id=?", [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['user_name'] = $name;
        flash('ok', 'Profil berhasil diperbarui.');
        redirect('profile.php');
    }
    $u = array_merge($u, compact('name', 'email', 'phone', 'address'));
}
$pageTitle = 'Profil — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>
<div class="page narrow">
  <div class="eyebrow">My account</div>
  <h1>Profil saya</h1>
  <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label>Nama</label><input type="text" name="name" value="<?= h($u['name']) ?>" required></div>
    <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($u['email']) ?>" required></div>
    <div class="field"><label>No. HP</label><input type="tel" name="phone" value="<?= h($u['phone']) ?>"></div>
    <div class="field"><label>Alamat pengiriman</label><textarea name="address" rows="4"><?= h($u['address']) ?></textarea></div>
    <h2 style="margin:28px 0 14px;font-size:11px;letter-spacing:.16em;text-transform:uppercase">Ganti password <span class="muted">(opsional)</span></h2>
    <div class="row">
      <div class="field"><label>Password lama</label><input type="password" name="oldpass" autocomplete="current-password"></div>
      <div class="field"><label>Password baru</label><input type="password" name="newpass" minlength="6" autocomplete="new-password"></div>
    </div>
    <button class="btn full">Simpan perubahan</button>
  </form>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
