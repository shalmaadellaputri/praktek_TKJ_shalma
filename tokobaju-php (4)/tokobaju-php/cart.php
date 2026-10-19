<?php
require_once __DIR__ . '/includes/bootstrap.php';
$items = cart_items();
$total = cart_total($items);
$pageTitle = 'Keranjang — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';

function cart_btn(string $do, int $id, string $label, string $cls = ''): string {
    return '<form method="post" action="' . url('actions/cart.php') . '" style="display:inline">' . csrf_field()
         . '<input type="hidden" name="do" value="' . $do . '"><input type="hidden" name="id" value="' . $id . '">'
         . '<button class="' . $cls . '" aria-label="' . h($do) . '">' . $label . '</button></form>';
}
?>
<div class="page">
  <div class="eyebrow">Shopping bag</div>
  <h1>Keranjang</h1>
  <?php if (!$items): ?>
    <div class="empty"><h3>Keranjang masih kosong</h3><p class="muted">Pilih produk favoritmu dan tambahkan ke keranjang.</p><a class="btn" href="<?= url('index.php') ?>">Mulai belanja</a></div>
  <?php else: ?>
    <?php foreach ($items as $it): $p = $it['product']; ?>
      <div class="cart-row">
        <a class="thumb" href="<?= url('product.php?id=' . $p['id']) ?>"><?php if ($p['photo']): ?><img src="<?= url('uploads/' . h($p['photo'])) ?>" alt=""><?php else: ?><div class="ph"><?= h($p['name']) ?></div><?php endif; ?></a>
        <div><div class="eyebrow" style="margin:0"><?= h($p['category'] ?? '') ?></div><b><?= h($p['name']) ?></b><div class="muted"><?= rupiah($p['price']) ?></div></div>
        <div class="qty"><?= cart_btn('dec', $p['id'], '−') ?><b><?= $it['qty'] ?></b><?= cart_btn('inc', $p['id'], '+') ?></div>
        <b><?= rupiah($it['subtotal']) ?></b>
        <?= cart_btn('remove', $p['id'], 'Hapus', 'link muted small') ?>
      </div>
    <?php endforeach; ?>
    <div class="total-row"><span>Total</span><span><?= rupiah($total) ?></span></div>
    <div class="cart-actions">
      <div class="actions"><a class="btn light" href="<?= url('index.php') ?>">← Lanjut belanja</a>
        <form method="post" action="<?= url('actions/cart.php') ?>" data-confirm="Kosongkan keranjang?"><?= csrf_field() ?><input type="hidden" name="do" value="clear"><button class="btn light">Kosongkan</button></form></div>
      <a class="btn" href="<?= url('checkout.php') ?>">Checkout →</a>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
