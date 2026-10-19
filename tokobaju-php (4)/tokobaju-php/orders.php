<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$orders = q("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC", [$_SESSION['user_id']])->fetchAll();
$pageTitle = 'Pesanan Saya — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>
<div class="page">
  <div class="eyebrow">Account</div>
  <h1>Pesanan saya</h1>
  <?php if (!$orders): ?>
    <div class="empty"><h3>Belum ada pesanan</h3><a class="btn" href="<?= url('index.php') ?>">Belanja sekarang</a></div>
  <?php endif; ?>
  <?php foreach ($orders as $o): ?>
    <div class="panel order-card">
      <div><b><?= h($o['order_code']) ?></b><div class="muted small"><?= fmt_date($o['created_at']) ?></div>
        <div style="margin-top:8px"><b><?= rupiah($o['total']) ?></b> <span class="muted">· <?= h($o['payment_method']) ?></span></div></div>
      <div class="actions" style="align-items:center">
        <span class="status <?= status_class($o['status']) ?>"><?= h($o['status']) ?></span>
        <a class="btn light small" href="<?= url('order_detail.php?id=' . $o['id']) ?>">Detail</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
