
<?php
require_once __DIR__ . '/includes/bootstrap.php';

$p = find_product((int)($_GET['id'] ?? 0));

if (!$p) {
    flash('err', 'Produk tidak tersedia.');
    redirect('index.php');
}

$related = q(
    PRODUCT_SELECT . " WHERE p.status='active'
    AND p.category_id = ? AND p.id <> ?
    ORDER BY p.id DESC LIMIT 4",
    [$p['category_id'] ?? 0, $p['id']]
)->fetchAll();

$inCart = (int)($_SESSION['cart'][$p['id']] ?? 0);

$pageTitle = $p['name'] . ' — ' . APP_NAME;
require __DIR__ . '/includes/partials/header.php';
?>

<div class="jb-product-page">

    <a class="jb-back" href="<?= url('index.php#koleksi') ?>">
        ← KEMBALI KE KOLEKSI
    </a>

    <div class="jb-product-layout">

        <!-- FOTO PRODUK -->
        <div class="jb-gallery">
            <div class="jb-main-image">
                <?php if (!empty($p['photo'])): ?>
                    <img
                        src="<?= url('uploads/' . h($p['photo'])) ?>"
                        alt="<?= h($p['name']) ?>"
                    >
                <?php else: ?>
                    <div class="jb-no-image">
                        <?= h($p['name']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DETAIL PRODUK -->
        <div class="jb-details">

            <div class="jb-breadcrumb">
                SHOP <span>/</span>
                <?= h($p['category'] ?? 'COLLECTION') ?>
                <span>/</span>
                <?= h($p['name']) ?>
            </div>

            <div class="jb-detail-content">

                <h1><?= h($p['name']) ?></h1>

                <div class="jb-price">
                    <?= rupiah($p['price']) ?>
                </div>

                <?php if (!empty($p['description'])): ?>
                    <p class="jb-description">
                        <?= nl2br(h($p['description'])) ?>
                    </p>
                <?php endif; ?>

                <div class="jb-size-heading">
                    <span>SIZE</span>
                    <span class="jb-size-note">SELECT YOUR SIZE</span>
                </div>

                <?php if ((int)$p['stock'] > 0): ?>

                    <form
                        method="post"
                        action="<?= url('actions/cart.php') ?>"
                        class="jb-buy-form"
                        id="jbBuyForm"
                    >
                        <?= csrf_field() ?>

                        <input type="hidden" name="do" value="add">
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$p['id'] ?>"
                        >

                        <!-- SIZE WAJIB DIPILIH -->
                        <div class="jb-sizes" role="group"
                             aria-label="Pilih ukuran produk">

                            <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                <button
                                    type="button"
                                    class="jb-size"
                                    data-size="<?= $size ?>"
                                    aria-pressed="false"
                                >
                                    <?= $size ?>
                                </button>
                            <?php endforeach; ?>

                        </div>

                        <input
                            type="hidden"
                            name="size"
                            id="jbSelectedSize"
                            value=""
                        >

                        <div
                            class="jb-size-error"
                            id="jbSizeError"
                            role="alert"
                            aria-live="polite"
                        >
                            SILAKAN PILIH UKURAN TERLEBIH DAHULU
                        </div>

                        <div class="jb-quantity">
                            <label for="jbQty">QUANTITY</label>
                            <input
                                type="number"
                                name="qty"
                                id="jbQty"
                                value="1"
                                min="1"
                                max="<?= (int)$p['stock'] ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="jb-add-button">
                            ADD TO CART
                        </button>

                    </form>

                <?php else: ?>

                    <button class="jb-add-button jb-out-of-stock"
                            type="button" disabled>
                        SOLD OUT
                    </button>

                <?php endif; ?>

                <?php if ($inCart > 0): ?>
                    <div class="jb-cart-info">
                        <?= $inCart ?> item sudah ada di keranjang.
                    </div>
                <?php endif; ?>

            </div>

            <!-- DESCRIPTION ACCORDION -->
            <details class="jb-description-panel" open>
                <summary>
                    DESCRIPTION
                    <span class="jb-minus">−</span>
                </summary>

                <div class="jb-description-text">
                    <?= !empty($p['description'])
                        ? nl2br(h($p['description']))
                        : 'Tidak ada deskripsi produk.' ?>
                </div>
            </details>

            <details class="jb-description-panel">
                <summary>
                    DELIVERY & RETURNS
                    <span class="jb-minus">+</span>
                </summary>

                <div class="jb-description-text">
                    Informasi pengiriman dan pengembalian mengikuti
                    kebijakan toko.
                </div>
            </details>

        </div>

    </div>

    <!-- PRODUK SERUPA -->
    <?php if ($related): ?>
        <section class="jb-related">
            <h2>YOU MAY ALSO LIKE</h2>

            <div class="jb-related-grid">
                <?php foreach ($related as $r): ?>
                    <a
                        class="jb-related-card"
                        href="<?= url('product.php?id=' . (int)$r['id']) ?>"
                    >
                        <div class="jb-related-image">
                            <?php if (!empty($r['photo'])): ?>
                                <img
                                    src="<?= url('uploads/' . h($r['photo'])) ?>"
                                    alt="<?= h($r['name']) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div class="jb-no-image">
                                    <?= h($r['name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3><?= h($r['name']) ?></h3>
                        <div class="jb-price">
                            <?= rupiah($r['price']) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<style>
/* JAMES BOOGIE INSPIRED PRODUCT PAGE */

.jb-product-page {
    max-width: 1240px;
    margin: 0 auto;
    padding: 40px 24px 80px;
    color: #111;
    background: #fff;
    font-family: Arial, Helvetica, sans-serif;
    box-sizing: border-box;
}

.jb-product-page * {
    box-sizing: border-box;
}

.jb-back {
    display: inline-block;
    margin-bottom: 24px;
    color: #666;
    text-decoration: none;
    font-size: 10px;
    letter-spacing: 1.5px;
}

.jb-back:hover {
    color: #000;
}

.jb-product-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 362px;
    gap: 48px;
    align-items: start;
}

/* GALLERY */

.jb-gallery {
    min-width: 0;
}

.jb-main-image {
    width: 100%;
    background: #e9e9e9;
    overflow: hidden;
}

.jb-main-image img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
}

.jb-no-image {
    min-height: 380px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #777;
    text-align: center;
}

/* RIGHT PANEL */

.jb-details {
    width: 100%;
    border: 1px solid #ccc;
    background: #fff;
}

.jb-breadcrumb {
    min-height: 35px;
    padding: 11px 15px;
    border-bottom: 1px solid #ccc;
    color: #777;
    font-size: 9px;
    line-height: 1.5;
    letter-spacing: 1px;
    text-transform: uppercase;
    overflow-wrap: anywhere;
}

.jb-breadcrumb span {
    padding: 0 5px;
    color: #aaa;
}

.jb-detail-content {
    padding: 24px;
}

.jb-detail-content h1 {
    margin: 0 0 10px;
    color: #111;
    font-size: 23px;
    line-height: 1.12;
    font-weight: 500;
    text-transform: uppercase;
    overflow-wrap: anywhere;
}

.jb-price {
    color: #111;
    font-size: 15px;
    font-weight: 400;
}

.jb-description {
    margin: 22px 0;
    color: #777;
    font-size: 13px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

.jb-size-heading {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-top: 35px;
    margin-bottom: 13px;
    color: #999;
    font-size: 10px;
}

.jb-size-note {
    color: #555;
    text-decoration: underline;
    text-underline-offset: 3px;
}

/* SIZE BUTTONS */

.jb-sizes {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
}

.jb-size {
    min-width: 0;
    height: 44px;
    padding: 0 3px;
    border: 1px solid #eee;
    border-radius: 0;
    background: #fff;
    color: #999;
    font-size: 12px;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}

.jb-size:hover {
    border-color: #777;
    color: #111;
}

.jb-size.active {
    border: 1px solid #000;
    color: #000;
    font-weight: 700;
}

.jb-size-error {
    display: none;
    margin-top: 12px;
    color: #b42318;
    font-size: 10px;
    letter-spacing: .5px;
    line-height: 1.6;
}

.jb-size-error.show {
    display: block;
}

.jb-quantity {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 20px;
    color: #777;
    font-size: 10px;
    letter-spacing: 1px;
}

.jb-quantity input {
    width: 75px;
    height: 35px;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 0;
    background: #fff;
    color: #111;
    text-align: center;
}

/* ADD TO CART */

.jb-add-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 56px;
    margin-top: 30px;
    padding: 15px;
    border: 1px solid #000;
    border-radius: 0;
    background: #000;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s, color .2s;
}

.jb-add-button:hover {
    background: #fff;
    color: #000;
}

.jb-add-button:disabled {
    background: #ddd;
    border-color: #ddd;
    color: #777;
    cursor: not-allowed;
}

.jb-cart-info {
    margin-top: 15px;
    color: #777;
    font-size: 12px;
}

/* ACCORDION */

.jb-description-panel {
    border-top: 1px solid #ccc;
}

.jb-description-panel summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 19px 24px;
    color: #222;
    font-size: 12px;
    list-style: none;
    cursor: pointer;
}

.jb-description-panel summary::-webkit-details-marker {
    display: none;
}

.jb-minus {
    font-size: 19px;
    line-height: 1;
}

.jb-description-text {
    padding: 0 24px 23px;
    color: #777;
    font-size: 12px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

/* RELATED PRODUCTS */

.jb-related {
    margin-top: 75px;
}

.jb-related > h2 {
    margin-bottom: 25px;
    font-size: 15px;
    font-weight: 500;
    letter-spacing: 2px;
}

.jb-related-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.jb-related-card {
    min-width: 0;
    color: #111;
    text-decoration: none;
}

.jb-related-image {
    overflow: hidden;
    background: #eee;
}

.jb-related-image img {
    display: block;
    width: 100%;
    height: auto;
    transition: transform .3s;
}

.jb-related-card:hover .jb-related-image img {
    transform: scale(1.03);
}

.jb-related-card h3 {
    margin: 12px 0 7px;
    font-size: 12px;
    font-weight: 400;
    text-transform: uppercase;
}

/* MOBILE */

@media (max-width: 900px) {
    .jb-product-page {
        padding: 25px 16px 50px;
    }

    .jb-product-layout {
        grid-template-columns: minmax(0, 1fr);
        gap: 0;
    }

    .jb-details {
        margin-top: 20px;
    }

    .jb-main-image img {
        width: 100%;
        height: auto;
    }

    .jb-related-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
}

@media (max-width: 380px) {
    .jb-detail-content {
        padding: 18px 14px;
    }

    .jb-size {
        height: 40px;
        font-size: 11px;
    }

    .jb-sizes {
        gap: 5px;
    }

    .jb-description-panel summary {
        padding-right: 14px;
        padding-left: 14px;
    }

    .jb-description-text {
        padding-right: 14px;
        padding-left: 14px;
    }
}
</style>

<script>
(function () {
    const form = document.getElementById('jbBuyForm');

    if (!form) return;

    const sizeButtons = form.querySelectorAll('.jb-size');
    const selectedSize = document.getElementById('jbSelectedSize');
    const sizeError = document.getElementById('jbSizeError');

    sizeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            sizeButtons.forEach(function (item) {
                item.classList.remove('active');
                item.setAttribute('aria-pressed', 'false');
            });

            button.classList.add('active');
            button.setAttribute('aria-pressed', 'true');

            selectedSize.value = button.dataset.size;
            sizeError.classList.remove('show');
        });
    });

    form.addEventListener('submit', function (event) {
        if (!selectedSize.value) {
            event.preventDefault();

            sizeError.classList.add('show');

            const firstSize = sizeButtons[0];
            firstSize.focus();

            sizeError.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    });

    document.querySelectorAll('.jb-description-panel').forEach(
        function (panel) {
            const icon = panel.querySelector('.jb-minus');

            function updateIcon() {
                icon.textContent = panel.open ? '−' : '+';
            }

            updateIcon();
            panel.addEventListener('toggle', updateIcon);
        }
    );
})();
</script>

<?php require __DIR__ . '/includes/partials/footer.php'; ?>

