<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('admin/products.php');
    $id = (int)($_POST['id'] ?? 0);
    $p = find_product($id, false);
    if ($p && ($_POST['do'] ?? '') === 'toggle') {
        q("UPDATE products SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id = ?", [$id]);
        flash('ok', 'Status produk diperbarui.');
    } elseif ($p && ($_POST['do'] ?? '') === 'delete') {
        // riwayat pesanan tetap aman: order_items menyimpan nama & harga sendiri
        q("UPDATE order_items SET product_id = NULL WHERE product_id = ?", [$id]);
        q("DELETE FROM products WHERE id = ?", [$id]);
        delete_upload($p['photo']);
        flash('ok', 'Produk dihapus.');
    }
    redirect('admin/products.php');
}

$s = trim($_GET['s'] ?? ''); $cat = (int)($_GET['cat'] ?? 0); $f = $_GET['f'] ?? '';
$where = ['1=1']; $params = [];
if ($s !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$s%"; }
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
if ($f === 'low') { $where[] = 'p.stock <= ' . (int)LOW_STOCK; }
if ($f === 'inactive') { $where[] = "p.status = 'inactive'"; }
$cond = implode(' AND ', $where);
$total = (int)q("SELECT COUNT(*) FROM products p WHERE $cond", $params)->fetchColumn();
[$off, $pages, $page] = paginate($total);
$rows = q(PRODUCT_SELECT . " WHERE $cond ORDER BY p.id DESC LIMIT " . PER_PAGE . " OFFSET $off", $params)->fetchAll();
$cats = all_categories();

$pageTitle = 'Produk'; $active = 'products';
require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="page-head"><div><div class="eyebrow">Catalog</div><h1>Produk <span class="muted" style="font-size:1rem">(<?= $total ?>)</span></h1></div><a class="btn" href="product_form.php">+ Tambah produk</a></div>
<form class="filters" method="get">
  <div class="field"><label>Cari</label><input type="search" name="s" value="<?= h($s) ?>" placeholder="Nama produk"></div>
  <div class="field"><label>Kategori</label><select name="cat"><option value="0">Semua</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Tampilkan</label><select name="f"><option value="">Semua</option><option value="low" <?= $f === 'low' ? 'selected' : '' ?>>Stok menipis</option><option value="inactive" <?= $f === 'inactive' ? 'selected' : '' ?>>Nonaktif</option></select></div>
  <button class="btn">Filter</button><a class="btn light" href="products.php">Reset</a>
</form>
<section class="panel"><div class="table-wrap"><table>
  <thead><tr><th></th><th>Produk</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="6" class="muted center">Tidak ada produk.</td></tr><?php endif; ?>
  <?php foreach ($rows as $p): ?>
    <tr><td><?php if ($p['photo']): ?><img class="thumb-sm" src="<?= url('uploads/' . h($p['photo'])) ?>" alt=""><?php else: ?><div class="thumb-sm ph">NO PHOTO</div><?php endif; ?></td>
      <td><b><?= h($p['name']) ?></b><small><?= h($p['category'] ?? 'Tanpa kategori') ?></small></td>
      <td><?= rupiah($p['price']) ?></td>
      <td class="<?= $p['stock'] <= LOW_STOCK ? 'low' : '' ?>"><?= (int)$p['stock'] ?><?= $p['stock'] <= 0 ? ' · habis' : ($p['stock'] <= LOW_STOCK ? ' · menipis' : '') ?></td>
      <td><span class="status <?= $p['status'] === 'active' ? 'st-ok' : 'st-bad' ?>"><?= $p['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
      <td><div class="actions">
        <a class="btn light small" href="product_form.php?id=<?= $p['id'] ?>">Edit</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="toggle"><button class="btn light small"><?= $p['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
        <form method="post" data-confirm="Hapus produk ini? Riwayat pesanan tetap tersimpan."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="delete"><button class="btn danger small">Hapus</button></form>
      </div></td></tr>
  <?php endforeach; ?></tbody></table></div><?= pager($pages, $page) ?></section>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
