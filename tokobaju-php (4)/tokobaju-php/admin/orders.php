<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$status = $_GET['status'] ?? ''; $s = trim($_GET['s'] ?? '');
$where = ['1=1']; $params = [];
if (in_array($status, ORDER_STATUSES, true)) { $where[] = 'o.status = ?'; $params[] = $status; }
if ($s !== '') { $where[] = '(o.order_code LIKE ? OR o.customer_name LIKE ? OR u.email LIKE ?)'; array_push($params, "%$s%", "%$s%", "%$s%"); }
$cond = implode(' AND ', $where);
$total = (int)q("SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id WHERE $cond", $params)->fetchColumn();
[$off, $pages, $page] = paginate($total);
$rows = q("SELECT o.*, u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE $cond ORDER BY o.id DESC LIMIT " . PER_PAGE . " OFFSET $off", $params)->fetchAll();

$pageTitle = 'Pesanan'; $active = 'orders';
require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="page-head"><div><div class="eyebrow">Sales</div><h1>Pesanan <span class="muted" style="font-size:1rem">(<?= $total ?>)</span></h1></div></div>
<form class="filters" method="get">
  <div class="field"><label>Cari</label><input type="search" name="s" value="<?= h($s) ?>" placeholder="Kode / nama / email"></div>
  <div class="field"><label>Status</label><select name="status"><option value="">Semua</option><?php foreach (ORDER_STATUSES as $st): ?><option <?= $st === $status ? 'selected' : '' ?>><?= h($st) ?></option><?php endforeach; ?></select></div>
  <button class="btn">Filter</button><a class="btn light" href="orders.php">Reset</a>
</form>
<section class="panel"><div class="table-wrap"><table>
  <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="5" class="muted center">Tidak ada pesanan.</td></tr><?php endif; ?>
  <?php foreach ($rows as $o): ?>
    <tr><td><b><?= h($o['order_code']) ?></b><small><?= fmt_date($o['created_at']) ?></small></td>
      <td><?= h($o['customer_name']) ?><small><?= h($o['email']) ?> · <?= h($o['phone']) ?></small></td>
      <td><b><?= rupiah($o['total']) ?></b><small><?= h($o['payment_method']) ?><?= $o['payment_proof'] ? ' · ✓ bukti' : '' ?></small></td>
      <td><span class="status <?= status_class($o['status']) ?>"><?= h($o['status']) ?></span></td>
      <td><a class="btn light small" href="order.php?id=<?= $o['id'] ?>">Detail</a></td></tr>
  <?php endforeach; ?></tbody></table></div><?= pager($pages, $page) ?></section>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
