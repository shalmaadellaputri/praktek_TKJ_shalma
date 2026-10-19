
<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_post('cart.php');

$do = $_POST['do'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($do === 'clear') {
    unset($_SESSION['cart'], $_SESSION['cart_sizes']);
    flash('ok', 'Keranjang dikosongkan.');
    redirect('cart.php');
}

$p = $id ? find_product($id) : null;

if (!$p) {
    flash('err', 'Produk tidak ditemukan.');
    redirect('cart.php');
}

$cur = (int)($_SESSION['cart'][$id] ?? 0);

switch ($do) {
    case 'add':
        if ((int)$p['stock'] <= 0) {
            flash('err', 'Produk sedang habis.');
            back();
        }

        // Ukuran wajib dipilih.
        $size = strtoupper(trim($_POST['size'] ?? ''));

        if (!in_array($size, ['S', 'M', 'L', 'XL', 'XXL'], true)) {
            flash('err', 'Silakan pilih ukuran yang valid.');
            back();
        }

        // Periksa stok ukuran langsung dari database.
        $sizeRow = q(
            "SELECT stock
             FROM product_sizes
             WHERE product_id = ? AND UPPER(size) = ?
             LIMIT 1",
            [$id, $size]
        )->fetch();

        if (!$sizeRow || (int)$sizeRow['stock'] <= 0) {
            flash('err', 'Ukuran ' . $size . ' sedang habis.');
            back();
        }

        $sizeStock = (int)$sizeRow['stock'];

        // Tolak ukuran berbeda jika produk ini sudah ada di keranjang.
        $oldSize = $_SESSION['cart_sizes'][$id] ?? null;

        if ($cur > 0 && $oldSize !== $size) {
            flash(
                'err',
                'Produk ini sudah ada di keranjang dengan ukuran ' .
                $oldSize . '. Hapus dahulu jika ingin mengganti ukuran.'
            );
            back('cart.php');
        }

        $requestedQty = max(1, (int)($_POST['qty'] ?? 1));
        $newQty = min($sizeStock, $cur + $requestedQty);

        $_SESSION['cart'][$id] = $newQty;
        $_SESSION['cart_sizes'][$id] = $size;

        flash(
            'ok',
            $p['name'] . ' ukuran ' . $size .
            ' ditambahkan ke keranjang.'
        );
        back('cart.php');

    case 'inc':
        $size = strtoupper(
            trim($_SESSION['cart_sizes'][$id] ?? '')
        );

        if ($size === '') {
            flash('err', 'Ukuran produk belum tercatat. Hapus produk ini lalu tambahkan kembali.');
            redirect('cart.php');
        }

        $sizeRow = q(
            "SELECT stock
             FROM product_sizes
             WHERE product_id = ? AND UPPER(size) = ?
             LIMIT 1",
            [$id, $size]
        )->fetch();

        $sizeStock = (int)($sizeRow['stock'] ?? 0);

        if ($sizeStock <= 0 || $cur >= $sizeStock) {
            flash('err', 'Stok ukuran ' . $size . ' tidak mencukupi.');
            redirect('cart.php');
        }

        $_SESSION['cart'][$id] = $cur + 1;
        break;

    case 'dec':
        if ($cur <= 1) {
            unset(
                $_SESSION['cart'][$id],
                $_SESSION['cart_sizes'][$id]
            );
        } else {
            $_SESSION['cart'][$id] = $cur - 1;
        }
        break;

    case 'remove':
        unset(
            $_SESSION['cart'][$id],
            $_SESSION['cart_sizes'][$id]
        );
        break;

    default:
        flash('err', 'Aksi keranjang tidak valid.');
        break;
}

redirect('cart.php');