<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
require_post('orders.php');
$id = (int)($_POST['id'] ?? 0);
$o = q("SELECT status, payment_proof FROM orders WHERE id = ? AND user_id = ?", [$id, $_SESSION['user_id']])->fetch();
if (!$o || $o['status'] !== 'Menunggu Konfirmasi') { flash('err', 'Bukti transfer tidak bisa diunggah untuk pesanan ini.'); redirect('orders.php'); }
$file = save_upload($_FILES['proof'] ?? null);
if (!$file) { flash('err', 'Foto tidak valid. Gunakan JPG/PNG/WEBP maksimal 5 MB.'); redirect('order_detail.php?id=' . $id); }
delete_upload($o['payment_proof']);
q("UPDATE orders SET payment_proof = ? WHERE id = ?", [$file, $id]);
flash('ok', 'Bukti transfer terkirim. Admin akan segera mengonfirmasi.');
redirect('order_detail.php?id=' . $id);
