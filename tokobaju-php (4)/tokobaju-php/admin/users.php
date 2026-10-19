<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_superadmin();
$error = ''; $me = (int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('admin/users.php');
    $do = $_POST['do'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($do === 'add') {
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['customer', 'staff', 'admin'], true) ? $_POST['role'] : 'customer';
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) $error = 'Data user belum valid (nama, email, password min. 6).';
        elseif (q("SELECT id FROM users WHERE email = ?", [$email])->fetch()) $error = 'Email sudah digunakan.';
        else { q("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?, 'active')", [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]); flash('ok', 'User ditambahkan.'); redirect('admin/users.php'); }
    } elseif ($id && $id !== $me) {   // tidak boleh mengubah/menghapus akun sendiri
        if ($do === 'toggle') { q("UPDATE users SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id = ?", [$id]); flash('ok', 'Status user diperbarui.'); }
        elseif ($do === 'role' && in_array($_POST['role'] ?? '', ['customer', 'staff', 'admin'], true)) { q("UPDATE users SET role = ? WHERE id = ?", [$_POST['role'], $id]); flash('ok', 'Role diperbarui.'); }
        elseif ($do === 'reset') { $new = substr(bin2hex(random_bytes(5)), 0, 8); q("UPDATE users SET password = ? WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $id]); flash('ok', "Password baru: $new (catat dan berikan ke user)."); }
        elseif ($do === 'delete') { q("DELETE FROM users WHERE id = ?", [$id]); flash('ok', 'User dihapus.'); }
        if ($do !== 'add') redirect('admin/users.php');
    } else { flash('err', 'Anda tidak dapat mengubah akun Anda sendiri di sini.'); redirect('admin/users.php'); }
}
$s = trim($_GET['s'] ?? ''); $r = $_GET['role'] ?? '';
$where = ['1=1']; $params = [];
if ($s !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; array_push($params, "%$s%", "%$s%"); }
if (in_array($r, ['customer', 'staff', 'admin'], true)) { $where[] = 'role = ?'; $params[] = $r; }
$cond = implode(' AND ', $where);
$total = (int)q("SELECT COUNT(*) FROM users WHERE $cond", $params)->fetchColumn();
[$off, $pages, $page] = paginate($total);
$users = q("SELECT id,name,email,role,status,created_at FROM users WHERE $cond ORDER BY id DESC LIMIT " . PER_PAGE . " OFFSET $off", $params)->fetchAll();

$pageTitle = 'User'; $active = 'users';
require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="page-head"><div><div class="eyebrow">User management</div><h1>Pengguna & hak akses</h1></div></div>
<div class="two wide-left">
  <section class="panel">
    <form class="filters" method="get">
      <div class="field"><label>Cari</label><input type="search" name="s" value="<?= h($s) ?>" placeholder="Nama / email"></div>
      <div class="field"><label>Role</label><select name="role"><option value="">Semua</option><?php foreach (['customer', 'staff', 'admin'] as $x): ?><option value="<?= $x ?>" <?= $r === $x ? 'selected' : '' ?>><?= ucfirst($x) ?></option><?php endforeach; ?></select></div>
      <button class="btn">Filter</button>
    </form>
    <div class="table-wrap"><table>
      <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach ($users as $u): $self = (int)$u['id'] === $me; ?>
        <tr><td><b><?= h($u['name']) ?></b><?= $self ? ' <span class="muted">(Anda)</span>' : '' ?><small><?= h($u['email']) ?></small></td>
          <td><?php if ($self): ?><?= ucfirst($u['role']) ?><?php else: ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
              <select name="role" onchange="this.form.submit()" style="min-height:34px;width:auto"><?php foreach (['customer', 'staff', 'admin'] as $x): ?><option value="<?= $x ?>" <?= $u['role'] === $x ? 'selected' : '' ?>><?= ucfirst($x) ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
          <td><span class="status <?= $u['status'] === 'active' ? 'st-ok' : 'st-bad' ?>"><?= $u['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td><?php if (!$self): ?><div class="actions">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn light small"><?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
            <form method="post" data-confirm="Reset password user ini?"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn light small">Reset PW</button></form>
            <form method="post" data-confirm="Hapus user ini? Pesanannya juga terhapus."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn danger small">Hapus</button></form>
          </div><?php endif; ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?= pager($pages, $page) ?>
  </section>
  <section class="panel"><h2>Tambah user</h2>
    <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="add">
      <div class="field"><label>Nama</label><input type="text" name="name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Password</label><input type="password" name="password" minlength="6" required></div>
      <div class="field"><label>Role</label><select name="role"><option value="customer">Customer</option><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
      <button class="btn full">+ Tambah</button></form></section>
</div>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
