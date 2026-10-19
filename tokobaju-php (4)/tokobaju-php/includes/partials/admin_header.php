<?php
/** Header admin. Variabel: $pageTitle, $active (nama menu aktif) */
$flash = get_flash();
$active = $active ?? '';
$menu = [
  'dashboard'  => ['Dashboard', 'admin/index.php', false],
  'orders'     => ['Pesanan', 'admin/orders.php', false],
  'products'   => ['Produk', 'admin/products.php', false],
  'categories' => ['Kategori', 'admin/categories.php', true],
  'users'      => ['User', 'admin/users.php', true],
  'reports'    => ['Laporan', 'admin/reports.php', false],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Admin') ?> — <?= h(APP_NAME) ?> Admin</title>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<?php if (is_admin()): ?>
<header class="admin-top">
  <button class="menu-toggle" aria-label="Menu" data-toggle="#adminNav">☰</button>
  <a class="logo" href="<?= url('admin/index.php') ?>"><?= h(APP_NAME) ?> <small>Admin</small></a>
  <div class="top-right"><span><?= h($_SESSION['user_name']) ?> · <?= h(ucfirst(current_role())) ?></span>
    <form method="post" action="<?= url('admin/logout.php') ?>"><?= csrf_field() ?><button class="link">Keluar</button></form></div>
</header>
<div class="admin-layout">
  <aside class="admin-side" id="adminNav">
    <?php foreach ($menu as $k => [$label, $href, $super]): if ($super && !is_superadmin()) continue; ?>
      <a class="<?= $active === $k ? 'on' : '' ?>" href="<?= url($href) ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <a href="<?= url('index.php') ?>" class="muted-link">↗ Lihat toko</a>
  </aside>
  <main class="admin-main">
<?php else: ?>
  <main class="auth-wrap">
<?php endif; ?>
<?php if ($flash): ?><div class="flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
