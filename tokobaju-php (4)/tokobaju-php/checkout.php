<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$items = cart_items();
if (!$items) { flash('err', 'Keranjang masih kosong.'); redirect('cart.php'); }
$total = cart_total($items);
$user = q("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
if (!$user) redirect('logout.php');

$f = [
    'name'    => trim($_POST['cname'] ?? $user['name']),
    'phone'   => trim($_POST['phone'] ?? ($user['phone'] ?? '')),
    'address' => trim($_POST['addr'] ?? ($user['address'] ?? '')),
    'note'    => trim($_POST['note'] ?? ''),
    'payment' => 'Transfer Bank ' . BANK_NAME,
];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    elseif ($f['name'] === '' || $f['phone'] === '' || $f['address'] === '') $error = 'Nama, nomor HP, dan alamat wajib diisi.';
    else {
        try {
            [$oid, $code] = create_order((int)$user['id'], $f, $items);
            unset($_SESSION['cart']);
            // simpan alamat/HP ke profil bila masih kosong
            if (empty($user['address']) || empty($user['phone'])) {
                q("UPDATE users SET phone = COALESCE(NULLIF(phone,''), ?), address = COALESCE(NULLIF(address,''), ?) WHERE id = ?", [$f['phone'], $f['address'], $user['id']]);
            }
            flash('ok', "Pesanan $code berhasil dibuat. Silakan lakukan pembayaran.");
            redirect('order_detail.php?id=' . $oid);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Checkout error: ' . $e->getMessage());
            $error = 'Pesanan gagal diproses. Coba lagi beberapa saat.';
        }
    }
}
$pageTitle = 'Checkout — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>
<div class="page">
  <div class="eyebrow">Checkout</div>
  <h1>Selesaikan pesanan</h1>
  <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
  <div class="checkout">
    <form method="post"><?= csrf_field() ?>
      <h2 class="eyebrow">Kontak & pengiriman</h2>
      <div class="row">
        <div class="field"><label>Nama lengkap *</label><input type="text" name="cname" value="<?= h($f['name']) ?>" maxlength="100" required></div>
        <div class="field"><label>No. HP *</label><input type="tel" name="phone" value="<?= h($f['phone']) ?>" maxlength="30" required></div>
      </div>
      <div class="field"><label>Alamat lengkap *</label><textarea name="addr" rows="4" maxlength="2000" placeholder="Jalan, nomor, kelurahan, kota, kode pos" required><?= h($f['address']) ?></textarea></div>
      <div class="field"><label>Catatan <small>(opsional)</small></label><textarea name="note" rows="3" maxlength="2000" placeholder="Contoh: kirim sore hari"><?= h($f['note']) ?></textarea></div>
      <button class="btn full" style="height:50px">Buat pesanan — <?= rupiah($total) ?></button>
    </form>
    <aside class="panel">
      <h2>Ringkasan pesanan</h2>
      <?php foreach ($items as $it): ?>
        <div class="sum-line"><span><?= h($it['product']['name']) ?> <span class="muted">× <?= $it['qty'] ?></span></span><b><?= rupiah($it['subtotal']) ?></b></div>
      <?php endforeach; ?>
      <div class="sum-line total"><span>Total</span><span><?= rupiah($total) ?></span></div>
      <div class="bank">
        <div class="eyebrow">Pembayaran</div>
        Transfer Bank <?= h(BANK_NAME) ?><br><b><?= h(BANK_ACCOUNT) ?></b><br>a.n. <?= h(BANK_HOLDER) ?>
        <p class="muted small" style="margin:10px 0 0">Setelah pesanan dibuat, unggah bukti transfer di halaman pesanan agar cepat dikonfirmasi.</p>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
