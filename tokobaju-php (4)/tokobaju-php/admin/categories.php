<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_superadmin();
$error = ''; $edit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('admin/categories.php');
    $do = $_POST['do'] ?? 'add'; $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
    try {
        if ($do === 'delete' && $id) {
            q("UPDATE products SET category_id = NULL WHERE category_id = ?", [$id]);
            q("DELETE FROM categories WHERE id = ?", [$id]);
            flash('ok', 'Kategori dihapus.'); redirect('admin/categories.php');
        }
        if ($name === '') $error = 'Nama kategori wajib diisi.';
        elseif ($do === 'edit' && $id) { q("UPDATE categories SET name=?, description=? WHERE id=?", [$name, $desc, $id]); flash('ok', 'Kategori diperbarui.'); redirect('admin/categories.php'); }
        else { q("INSERT INTO categories (name,description) VALUES (?,?)", [$name, $desc]); flash('ok', 'Kategori ditambahkan.'); redirect('admin/categories.php'); }
    } catch (PDOException $e) { $error = 'Nama kategori sudah dipakai.'; }
}
if (isset($_GET['edit'])) $edit = q("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit']])->fetch() ?: null;
$cats = q("SELECT c.*, COUNT(p.id) n FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id, c.name, c.description, c.created_at ORDER BY c.name")->fetchAll();

$pageTitle = 'Kategori'; $active = 'categories';
require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="page-head"><div><div class="eyebrow">Catalog</div><h1>Kategori</h1></div></div>
<div class="two">
  <section class="panel"><h2><?= $edit ? 'Edit kategori' : 'Tambah kategori' ?></h2>
    <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="<?= $edit ? 'edit' : 'add' ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="field"><label>Nama</label><input type="text" name="name" value="<?= h($edit['name'] ?? '') ?>" maxlength="80" required></div>
      <div class="field"><label>Deskripsi</label><input type="text" name="description" value="<?= h($edit['description'] ?? '') ?>" maxlength="255"></div>
      <div class="actions"><button class="btn"><?= $edit ? 'Simpan' : '+ Tambah' ?></button><?php if ($edit): ?><a class="btn light" href="categories.php">Batal</a><?php endif; ?></div>
    </form></section>
  <section class="panel"><h2>Semua kategori</h2><div class="table-wrap"><table>
    <thead><tr><th>Nama</th><th>Produk</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($cats as $c): ?>
      <tr><td><b><?= h($c['name']) ?></b><small><?= h($c['description']) ?></small></td><td><?= (int)$c['n'] ?></td>
        <td><div class="actions"><a class="btn light small" href="categories.php?edit=<?= $c['id'] ?>">Edit</a>
          <form method="post" data-confirm="Hapus kategori? Produknya jadi tanpa kategori."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn danger small">Hapus</button></form></div></td></tr>
    <?php endforeach; ?></tbody></table></div></section>
</div>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
