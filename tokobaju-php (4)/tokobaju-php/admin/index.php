<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$ok = "'" . implode("','", SALES_STATUSES) . "'";
$stats = [
  'sales'   => (int)q("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ($ok)")->fetchColumn(),
  'today'   => (int)q("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ($ok) AND DATE(created_at) = ?", [date('Y-m-d')])->fetchColumn(),
  'orders'  => (int)q("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'pending' => (int)q("SELECT COUNT(*) FROM orders WHERE status = 'Menunggu Konfirmasi'")->fetchColumn(),
  'products'=> (int)q("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
  'low'     => (int)q("SELECT COUNT(*) FROM products WHERE status = 'active' AND stock <= ?", [LOW_STOCK])->fetchColumn(),
  'customers'=> (int)q("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
];
// Penjualan 7 hari terakhir
$days = [];
for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach (q("SELECT DATE(created_at) d, SUM(total) s FROM orders WHERE status IN ($ok) AND created_at >= ? GROUP BY DATE(created_at)", [array_key_first($days) . ' 00:00:00'])->fetchAll() as $r) $days[$r['d']] = (int)$r['s'];
$max = max(1, max($days));
$recent = q("SELECT o.*, u.email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 8")->fetchAll();
$lowList = q("SELECT id, name, stock FROM products WHERE status = 'active' AND stock <= ? ORDER BY stock ASC LIMIT 6", [LOW_STOCK])->fetchAll();

$pageTitle = 'Dashboard'; $active = 'dashboard';
require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="page-head"><div><div class="eyebrow">Control center</div><h1>Dashboard</h1><span class="muted">Halo, <?= h($_SESSION['user_name']) ?></span></div>
  <div class="actions"><a class="btn" href="<?= url('admin/product_form.php') ?>">+ Produk</a><a class="btn light" href="<?= url('admin/reports.php') ?>">Laporan →</a></div></div>

<div class="stats">
  <div class="stat"><span>Omzet total</span><b class="sm"><?= rupiah($stats['sales']) ?></b></div>
  <div class="stat"><span>Omzet hari ini</span><b class="sm"><?= rupiah($stats['today']) ?></b></div>
  <div class="stat <?= $stats['pending'] ? 'alert-stat' : '' ?>"><span>Perlu konfirmasi</span><b><?= $stats['pending'] ?></b></div>
  <div class="stat"><span>Total pesanan</span><b><?= $stats['orders'] ?></b></div>
  <div class="stat"><span>Produk aktif</span><b><?= $stats['products'] ?></b></div>
  <div class="stat <?= $stats['low'] ? 'alert-stat' : '' ?>"><span>Stok menipis</span><b><?= $stats['low'] ?></b></div>
  <div class="stat"><span>Pelanggan</span><b><?= $stats['customers'] ?></b></div>
</div>

<div class="two">
  <section class="panel"><h2>Penjualan 7 hari</h2>
    <?php foreach ($days as $d => $v): ?>
      <div style="display:grid;grid-template-columns:54px 1fr 110px;gap:10px;align-items:center;margin-bottom:8px;font-size:12px">
        <span class="muted"><?= date('d M', strtotime($d)) ?></span><div class="bar" style="margin:0"><i style="width:<?= round($v / $max * 100) ?>%"></i></div><b class="right"><?= rupiah($v) ?></b>
      </div>
    <?php endforeach; ?>
  </section>
  <section class="panel"><h2>Stok menipis</h2>
    <?php if (!$lowList): ?><p class="muted">Semua stok aman.</p><?php endif; ?>
    <?php foreach ($lowList as $p): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line)"><a href="<?= url('admin/product_form.php?id=' . $p['id']) ?>"><?= h($p['name']) ?></a><b class="<?= $p['stock'] <= 0 ? 'low' : '' ?>"><?= $p['stock'] <= 0 ? 'Habis' : $p['stock'] . ' pcs' ?></b></div>
    <?php endforeach; ?>
  </section>
</div>

<section class="panel"><h2>Pesanan terbaru</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if (!$recent): ?><tr><td colspan="5" class="muted center">Belum ada pesanan.</td></tr><?php endif; ?>
    <?php foreach ($recent as $o): ?>
      <tr><td><b><?= h($o['order_code']) ?></b><small><?= fmt_date($o['created_at']) ?></small></td>
        <td><?= h($o['customer_name']) ?><small><?= h($o['email']) ?></small></td>
        <td><b><?= rupiah($o['total']) ?></b><?= $o['payment_proof'] ? '<small>✓ bukti transfer</small>' : '' ?></td>
        <td><span class="status <?= status_class($o['status']) ?>"><?= h($o['status']) ?></span></td>
        <td><a class="btn light small" href="<?= url('admin/order.php?id=' . $o['id']) ?>">Detail</a></td></tr>
    <?php endforeach; ?></tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
