<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
require_post('orders.php');
$id = (int)($_POST['id'] ?? 0);
$o = q("SELECT status FROM orders WHERE id = ? AND user_id = ?", [$id, $_SESSION['user_id']])->fetch();
if ($o && $o['status'] === 'Menunggu Konfirmasi') {
    change_order_status($id, 'Dibatalkan');
    flash('ok', 'Pesanan dibatalkan dan stok dikembalikan.');
} else {
    flash('err', 'Pesanan ini tidak bisa dibatalkan.');
}
redirect('order_detail.php?id=' . $id);
