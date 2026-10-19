<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$valid = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d ?? '') ? $d : null;
$from = $valid($_GET['from'] ?? null) ?? date('Y-m-01');
$to   = $valid($_GET['to'] ?? null) ?? date('Y-m-d');
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];
$ok = "'" . implode("','", SALES_STATUSES) . "'";

$summary = q("SELECT COUNT(*) n, COALESCE(SUM(total),0) gross FROM orders WHERE created_at BETWEEN ? AND ? AND status IN ($ok)", $range)->fetch();
$daily = q("SELECT DATE(created_at) day, COUNT(*) n, SUM(total) sales FROM orders WHERE created_at BETWEEN ? AND ? AND status IN ($ok) GROUP BY DATE(created_at) ORDER BY day DESC", $range)->fetchAll();
$top = q("SELECT oi.product_name, SUM(oi.qty) qty, SUM(oi.price*oi.qty) sales FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.created_at BETWEEN ? AND ? AND o.status IN ($ok) GROUP BY oi.product_name ORDER BY qty DESC LIMIT 10", $range)->fetchAll();
$byStatus = q("SELECT status, COUNT(*) n FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status", $range)->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {   // unduh CSV untuk Excel
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=laporan-$from-sd-$to.csv");
    $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Kode', 'Tanggal', 'Pelanggan', 'Status', 'Pembayaran', 'Total']);
    foreach (q("SELECT * FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY id DESC", $range) as $o)
        fputcsv($out, [$o['order_code'], $o['created_at'], $o['customer_name'], $o['status'], $o['payment_method'], $o['total']]);
    exit;
}
$pageTitle = 'Laporan'; $active = 'reports';
require __DIR__ . '/../includes/partials/admin_header.php';
?>
<div class="page-head"><div><div class="eyebrow">Analytics</div><h1>Laporan penjualan</h1></div></div>
<form class="filters" method="get">
  <div class="field"><label>Dari</label><input type="date" name="from" value="<?= h($from) ?>"></div>
  <div class="field"><label>Sampai</label><input type="date" name="to" value="<?= h($to) ?>"></div>
  <button class="btn">Tampilkan</button>
  <a class="btn light" href="?<?= h(http_build_query(['from' => $from, 'to' => $to, 'export' => 'csv'])) ?>">Unduh CSV</a>
</form>
<div class="stats">
  <div class="stat"><span>Pesanan sah</span><b><?= (int)$summary['n'] ?></b></div>
  <div class="stat"><span>Omzet</span><b class="sm"><?= rupiah($summary['gross']) ?></b></div>
  <div class="stat"><span>Rata-rata / pesanan</span><b class="sm"><?= rupiah($summary['n'] ? $summary['gross'] / $summary['n'] : 0) ?></b></div>
</div>
<div class="two">
  <section class="panel"><h2>Penjualan harian</h2><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Pesanan</th><th class="right">Penjualan</th></tr></thead><tbody>
    <?php if (!$daily): ?><tr><td colspan="3" class="muted center">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($daily as $d): ?><tr><td><?= h($d['day']) ?></td><td><?= (int)$d['n'] ?></td><td class="right"><b><?= rupiah($d['sales']) ?></b></td></tr><?php endforeach; ?></tbody></table></div></section>
  <section class="panel"><h2>Produk terlaris</h2><div class="table-wrap"><table><thead><tr><th>Produk</th><th>Qty</th><th class="right">Omzet</th></tr></thead><tbody>
    <?php if (!$top): ?><tr><td colspan="3" class="muted center">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($top as $t): ?><tr><td><?= h($t['product_name']) ?></td><td><?= (int)$t['qty'] ?></td><td class="right"><?= rupiah($t['sales']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <h2 style="margin-top:24px">Pesanan per status</h2>
    <?php foreach ($byStatus as $b): ?><div style="display:flex;justify-content:space-between;padding:6px 0"><span class="status <?= status_class($b['status']) ?>"><?= h($b['status']) ?></span><b><?= (int)$b['n'] ?></b></div><?php endforeach; ?></section>
</div>
<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>
