<?php
/** Header toko. Variabel opsional: $pageTitle, $bodyClass, $isHome */
$flash = get_flash();
$navCats = all_categories();
$cartN = cart_count();
$isHome = !empty($isHome);
// Foto menu Shop: ganti file assets/img/menu-shop.jpg dengan foto Anda (rasio 4:5, mis. 800x1000 px)
$menuImgUrl = asset('img/menu-shop.jpg') . '?v=' . (@filemtime(ROOT_PATH . '/assets/img/menu-shop.jpg') ?: 1);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? APP_NAME . ' — Fashion Store') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600&display=swap">
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/store.css') ?>">
</head>
<body class="<?= $isHome ? 'home ' : '' ?><?= h($bodyClass ?? '') ?>">
<div class="topbar"></div>
<header class="site-header" id="siteHeader">
  <button class="menu-toggle" aria-label="Menu" data-toggle="#siteNav">☰</button>
  <nav class="nav-left" id="siteNav">
    <div class="has-mega">
      <a href="<?= url('index.php?all=1#koleksi') ?>">Shop</a>
      <div class="mega">
        <div>
          <h4>Products</h4>
          <ul>
            <li><a href="<?= url('index.php?all=1#koleksi') ?>">All products</a></li>
            <?php foreach ($navCats as $c): ?><li><a href="<?= url('index.php?category_id=' . $c['id'] . '#koleksi') ?>"><?= h($c['name']) ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <div>
          <h4>Focus on</h4>
          <ul>
            <li><a href="<?= url('index.php#featured') ?>">New arrivals</a></li>
            <li><a href="<?= url('index.php?all=1&sort=price_low#koleksi') ?>">Harga terendah</a></li>
            <li><a href="<?= url('index.php?all=1&sort=price_high#koleksi') ?>">Harga tertinggi</a></li>
            <li><a href="<?= url('index.php?all=1&sort=name#koleksi') ?>">Nama A–Z</a></li>
          </ul>
        </div>
        <div><img src="<?= h($menuImgUrl) ?>" alt=""></div>
      </div>
    </div>
    <div class="mobile-cats"><?php foreach ($navCats as $c): ?><a href="<?= url('index.php?category_id=' . $c['id'] . '#koleksi') ?>"><?= h($c['name']) ?></a><?php endforeach; ?></div>
    <a href="<?= url('index.php#featured') ?>">New arrivals</a>
    <a href="<?= url('index.php#about') ?>">The brand</a>
  </nav>
  <a class="logo" href="<?= url('index.php') ?>"><?= h(APP_NAME) ?></a>
  <nav class="nav-right">
    <button class="nav-btn hide-m" type="button" data-toggle="#searchBar" data-focus="#searchBar input">Search</button>
    <?php if (is_admin()): ?><a class="hide-m" href="<?= url('admin/index.php') ?>">Admin</a><?php endif; ?>
    <?php if (is_logged_in()): ?>
      <div class="has-drop"><a href="<?= url('orders.php') ?>">Akun</a>
        <div class="drop"><a href="<?= url('orders.php') ?>">Pesanan saya</a><a href="<?= url('profile.php') ?>">Profil</a><a href="<?= url('logout.php') ?>">Keluar</a></div></div>
    <?php else: ?><a href="<?= url('login.php') ?>">Login</a><?php endif; ?>
    <a href="<?= url('cart.php') ?>">Cart<?= $cartN ? " [$cartN]" : '' ?></a>
  </nav>
  <form class="search-bar" id="searchBar" action="<?= url('index.php#koleksi') ?>" method="get">
    <input type="search" name="q" placeholder="Cari produk…" value="<?= h($_GET['q'] ?? '') ?>" aria-label="Cari produk">
  </form>
</header>
<?php if ($flash): ?>
  <div class="flash <?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div>
<?php endif; ?>
