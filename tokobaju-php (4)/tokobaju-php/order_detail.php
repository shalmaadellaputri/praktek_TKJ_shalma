<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$o = q("SELECT * FROM orders WHERE id = ? AND user_id = ?", [(int)($_GET['id'] ?? 0), $_SESSION['user_id']])->fetch();
if (!$o) { flash('err', 'Pesanan tidak ditemukan.'); redirect('orders.php'); }
$items = order_items((int)$o['id']);

$steps = ['Menunggu Konfirmasi', 'Diterima', 'Diproses', 'Dikirim', 'Selesai'];
$idx = array_search($o['status'], $steps, true);
$closed = in_array($o['status'], RESTOCK_STATUSES, true);
$canPay = !$closed && $o['status'] === 'Menunggu Konfirmasi';

$pageTitle = 'Detail ' . $o['order_code'] . ' — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>
<div class="page">
  <div class="order-head">
    <div><div class="eyebrow">Order detail</div><h1 style="margin-bottom:8px"><?= h($o['order_code']) ?></h1><div class="muted small"><?= fmt_date($o['created_at']) ?></div></div>
    <span class="status <?= status_class($o['status']) ?>"><?= h($o['status']) ?></span>
  </div>
  <?php if (!$closed): ?>
    <div class="timeline" style="margin-top:24px"><?php foreach ($steps as $i => $s): ?><span class="<?= $idx !== false && $i <= $idx ? 'done' : '' ?>"><?= h($s) ?></span><?php endforeach; ?></div>
  <?php else: ?><div style="height:24px"></div><?php endif; ?>

  
<div class="panel table-wrap">
  <table>
    <thead>
      <tr>
        <th>Produk</th>
        <th>Ukuran</th>
        <th>Harga</th>
        <th>Qty</th>
        <th class="right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $i): ?>
        <tr>
          <td><?= h($i['product_name']) ?></td>
          <td><b><?= h($i['size'] ?? '-') ?></b></td>
          <td><?= rupiah($i['price']) ?></td>
          <td><?= (int)$i['qty'] ?></td>
          <td class="right"><?= rupiah($i['price'] * $i['qty']) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="4" class="right"><b>Total</b></td>
        <td class="right"><b><?= rupiah($o['total']) ?></b></td>
      </tr>
    </tbody>
  </table>
</div>

  <div class="checkout" style="grid-template-columns:1fr 1fr;gap:18px">
    <div class="panel"><h2>Pengiriman</h2>
      <p><b><?= h($o['customer_name']) ?></b><br><?= nl2br(h($o['address'])) ?><br><?= h($o['phone']) ?></p>
      <?php if ($o['tracking_no']): ?><p><span class="eyebrow">No. resi</span><br><b><?= h($o['tracking_no']) ?></b></p><?php endif; ?>
      <?php if ($o['note']): ?><p class="muted"><?= nl2br(h($o['note'])) ?></p><?php endif; ?>
    </div>
    <div class="panel"><h2>Pembayaran</h2>
      <p><?= h($o['payment_method']) ?><br>
      <b><?= h(BANK_ACCOUNT) ?></b> a.n. <?= h(BANK_HOLDER) ?></p>
      <?php if ($o['payment_proof']): ?>
        <div class="eyebrow">Bukti transfer</div><a href="<?= url('uploads/' . h($o['payment_proof'])) ?>" target="_blank"><img class="proof" src="<?= url('uploads/' . h($o['payment_proof'])) ?>" alt="Bukti transfer"></a>
      <?php endif; ?>
      <?php if ($canPay): ?>
        <form method="post" action="<?= url('actions/upload_proof.php') ?>" enctype="multipart/form-data" style="margin-top:14px">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
          <div class="field"><label><?= $o['payment_proof'] ? 'Ganti bukti transfer' : 'Unggah bukti transfer' ?> <small>JPG/PNG/WEBP ≤ 5 MB</small></label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp" required></div>
          <button class="btn small">Kirim bukti</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="actions" style="margin-top:8px">
    <a class="btn light" href="<?= url('orders.php') ?>">← Kembali</a>
    <?php if ($o['status'] === 'Menunggu Konfirmasi'): ?>
      <form method="post" action="<?= url('actions/order_cancel.php') ?>" data-confirm="Batalkan pesanan ini?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="btn danger">Batalkan pesanan</button></form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
