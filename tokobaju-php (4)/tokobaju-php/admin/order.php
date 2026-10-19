
<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('admin/order.php?id=' . $id);

    try {
        if (change_order_status($id, $_POST['status'] ?? '')) {
            q(
                "UPDATE orders SET tracking_no = ? WHERE id = ?",
                [trim($_POST['tracking_no'] ?? '') ?: null, $id]
            );
            flash('ok', 'Pesanan diperbarui.');
        } else {
            flash('err', 'Status tidak valid.');
        }
    } catch (RuntimeException $e) {
        flash('err', $e->getMessage());
    }

    redirect('admin/order.php?id=' . $id);
}

$o = q(
    "SELECT o.*, u.email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     WHERE o.id = ?",
    [$id]
)->fetch();

if (!$o) {
    flash('err', 'Pesanan tidak ditemukan.');
    redirect('admin/orders.php');
}

$items = order_items($id);

$pageTitle = $o['order_code'];
$active = 'orders';

require __DIR__ . '/../includes/partials/admin_header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow">Order detail</div>
        <h1><?= h($o['order_code']) ?></h1>
        <span class="muted"><?= fmt_date($o['created_at']) ?></span>
    </div>

    <a class="btn light" href="orders.php">← Semua pesanan</a>
</div>

<div class="two">

    <!-- DATA PELANGGAN -->
    <section class="panel detail-list">
        <h2>Pelanggan</h2>

        <p>
            <b><?= h($o['customer_name']) ?></b><br>
            <?= h($o['email']) ?><br>
            <?= h($o['phone']) ?>
        </p>

        <p><?= nl2br(h($o['address'])) ?></p>

        <?php if ($o['note']): ?>
            <div class="alert info">
                <?= nl2br(h($o['note'])) ?>
            </div>
        <?php endif; ?>

        <p class="muted"><?= h($o['payment_method']) ?></p>

        <?php if ($o['payment_proof']): ?>
            <div class="eyebrow">Bukti transfer</div>
            <a target="_blank"
               href="<?= url('uploads/' . h($o['payment_proof'])) ?>">
                <img class="proof"
                     src="<?= url('uploads/' . h($o['payment_proof'])) ?>"
                     alt="Bukti transfer">
            </a>
        <?php else: ?>
            <p class="muted small">Belum ada bukti transfer.</p>
        <?php endif; ?>
    </section>

    <!-- STATUS PESANAN -->
    <section class="panel">
        <h2>Status pesanan</h2>

        <p>
            <span class="status <?= status_class($o['status']) ?>">
                <?= h($o['status']) ?>
            </span>
        </p>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="field">
                <label>Ubah status</label>
                <select name="status">
                    <?php foreach (ORDER_STATUSES as $st): ?>
                        <option <?= $st === $o['status'] ? 'selected' : '' ?>>
                            <?= h($st) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>
                    No. resi <small>(isi saat dikirim)</small>
                </label>
                <input type="text"
                       name="tracking_no"
                       value="<?= h($o['tracking_no']) ?>"
                       maxlength="80">
            </div>

            <button class="btn full">Simpan</button>

            <p class="muted small">
                Stok otomatis dikembalikan bila pesanan Ditolak / Dibatalkan.
            </p>
        </form>
    </section>

</div>

<!-- ITEM PESANAN DENGAN UKURAN BAJU -->
<section class="panel">
    <h2>Item Pesanan</h2>

    <div class="table-wrap">
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

                        <td>
                            <b><?= h($i['size'] ?? '-') ?></b>
                        </td>

                        <td><?= rupiah($i['price']) ?></td>

                        <td><?= (int)$i['qty'] ?></td>

                        <td class="right">
                            <b>
                                <?= rupiah($i['price'] * $i['qty']) ?>
                            </b>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <td colspan="4" class="right">
                        <b>Total</b>
                    </td>
                    <td class="right">
                        <b><?= rupiah($o['total']) ?></b>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>