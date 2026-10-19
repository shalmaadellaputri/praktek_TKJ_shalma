
<?php
require_once __DIR__ . '/includes/bootstrap.php';

$q       = trim($_GET['q'] ?? '');
$cat     = (int)($_GET['category_id'] ?? 0);
$catName = trim($_GET['category'] ?? '');
$sort    = $_GET['sort'] ?? 'newest';

$orderBy = [
    'newest'     => 'p.id DESC',
    'price_low'  => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name'       => 'p.name ASC'
][$sort] ?? 'p.id DESC';

$where  = ["p.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR c.name LIKE ? OR p.description LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%");
}

if ($cat) {
    $where[] = "p.category_id = ?";
    $params[] = $cat;
}

if ($catName !== '') {
    $where[] = "c.name = ?";
    $params[] = $catName;
}

$cond = implode(' AND ', $where);

$total = (int)q(
    "SELECT COUNT(*) FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $cond",
    $params
)->fetchColumn();

[$offset, $pages, $page] = paginate($total, 12);

$products = q(
    PRODUCT_SELECT . " WHERE $cond ORDER BY $orderBy LIMIT 12 OFFSET $offset",
    $params
)->fetchAll();

$cats = q(
    "SELECT c.id, c.name, COUNT(p.id) n
     FROM categories c
     LEFT JOIN products p
       ON p.category_id = c.id AND p.status = 'active'
     GROUP BY c.id, c.name
     ORDER BY c.name"
)->fetchAll();

$filtering = $q !== '' || $cat || $catName !== ''
    || isset($_GET['all']) || isset($_GET['sort'])
    || isset($_GET['page']);

$featured = $filtering ? [] : q(
    PRODUCT_SELECT . " WHERE p.status = 'active'
     ORDER BY p.id DESC LIMIT 12"
)->fetchAll();

$isHome = !$filtering;

$pageTitle = APP_NAME . ' — Fashion Store';
require __DIR__ . '/includes/partials/header.php';
?>

<style>
/* JAMES BOOGIE INSPIRED — NOVA FEATURED PRODUCTS */

.feat {
    position: relative;
    overflow: hidden;
}

.feat > .section-label {
    min-height: 48px;
    padding: 0 110px 0 18px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
    font-size: 11px;
    letter-spacing: 3px;
}

/* Tombol geser di bagian atas */
.feat .feat-arrow {
    position: absolute;
    top: 8px;
    right: 14px;
    z-index: 5;
    width: 38px;
    height: 34px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #fff;
    color: #000;
    border: 1px solid #ccc;
    font-size: 22px;
    cursor: pointer;
}

.feat .feat-arrow.prev {
    right: 60px;
}

/* Carousel horizontal */
.feat .feat-track {
    display: flex;
    gap: 0;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.feat .feat-track::-webkit-scrollbar {
    display: none;
}

.feat .feat-card {
    flex: 0 0 50%;
    min-width: 0;
    box-sizing: border-box;
    scroll-snap-align: start;
    text-decoration: none;
}

.feat .feat-card .thumb {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    background: #e5e5e5;
}

.feat .feat-card .thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
}

.feat .feat-info {
    padding: 12px;
}

.feat .feat-info h3 {
    margin: 0 0 6px;
    font-size: 12px;
    line-height: 1.5;
}

.feat .feat-info .price {
    font-size: 12px;
}

/* Tampilan HP */
@media (max-width: 600px) {
    .feat > .section-label {
        min-height: 46px;
        padding: 0 100px 0 14px;
        font-size: 10px;
        letter-spacing: 2.5px;
    }

    .feat .feat-arrow {
        top: 7px;
        right: 10px;
        width: 34px;
        height: 32px;
        font-size: 20px;
    }

    .feat .feat-arrow.prev {
        right: 51px;
    }

    .feat .feat-card {
        flex-basis: 50%;
    }

    .feat .feat-card .thumb {
        aspect-ratio: 3 / 4;
    }

    .feat .feat-info {
        padding: 10px 8px 14px;
    }

    .feat .feat-info h3,
    .feat .feat-info .price {
        font-size: 11px;
    }

    .feat .feat-card .badge {
        top: 10px;
        left: 8px;
        font-size: 9px;
        padding: 5px 7px;
    }
}

/* HP sangat kecil */
@media (max-width: 380px) {
    .feat .feat-card {
        flex-basis: 72%;
    }
}
</style>

<?php if (!$filtering): ?>
<section class="hero">
    <div>
        <h1><?= h(APP_NAME) ?> '26</h1>
        <p>Modern fashion. Simple shopping.</p>
        <a class="hero-link" href="#featured">Discover more &gt;</a>
    </div>
</section>

<section class="feat" id="featured">
    <div class="section-label">Featured products</div>

    <?php if ($featured): ?>
        <button class="feat-arrow prev"
                data-feat="-1"
                aria-label="Sebelumnya">‹</button>

        <button class="feat-arrow next"
                data-feat="1"
                aria-label="Berikutnya">›</button>

        <div class="feat-track" id="featTrack">
            <?php foreach ($featured as $p): ?>
                <a class="feat-card"
                   href="<?= url('product.php?id=' . $p['id']) ?>">

                    <div class="thumb">
                        <?php if ($p['photo']): ?>
                            <img
                                src="<?= url('uploads/' . h($p['photo'])) ?>"
                                alt="<?= h($p['name']) ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div class="ph"><?= h($p['name']) ?></div>
                        <?php endif; ?>

                        <?php if ($p['stock'] <= 0): ?>
                            <span class="badge">Out of stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="feat-info">
                        <h3><?= h($p['name']) ?></h3>
                        <div class="price"><?= rupiah($p['price']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty" style="margin:20px 18px">
            Belum ada produk.
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<main class="container section" id="koleksi">

<?php if ($filtering): ?>
    <div class="section-label">
        Hasil — <?= $total ?> produk
    </div>

    <form class="tools" method="get" action="#koleksi">
        <div class="search">
            <input type="search"
                   name="q"
                   value="<?= h($q) ?>"
                   placeholder="Cari produk…">
        </div>

        <?php if ($cat): ?>
            <input type="hidden" name="category_id" value="<?= $cat ?>">
        <?php endif; ?>

        <select name="sort"
                onchange="this.form.submit()"
                aria-label="Urutkan">
            <?php foreach ([
                'newest' => 'Terbaru',
                'price_low' => 'Harga terendah',
                'price_high' => 'Harga tertinggi',
                'name' => 'Nama A–Z'
            ] as $k => $v): ?>
                <option value="<?= $k ?>"
                    <?= $sort === $k ? 'selected' : '' ?>>
                    <?= $v ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn">Cari</button>
    </form>

    <div class="chips">
        <a class="chip <?= !$cat && $catName === '' ? 'on' : '' ?>"
           href="<?= url('index.php#koleksi') ?>">Semua</a>

        <?php foreach ($cats as $c): ?>
            <a class="chip <?= $cat === (int)$c['id'] || $catName === $c['name'] ? 'on' : '' ?>"
               href="<?= url('index.php?category_id=' . $c['id'] . '#koleksi') ?>">
                <?= h($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$products): ?>
        <div class="empty">
            <h3>Produk tidak ditemukan</h3>
            <p class="muted">Coba kata kunci atau kategori lain.</p>
            <a class="btn" href="<?= url('index.php') ?>">Reset pencarian</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($products as $p): ?>
                <a class="card"
                   href="<?= url('product.php?id=' . $p['id']) ?>">

                    <div class="thumb">
                        <?php if ($p['photo']): ?>
                            <img
                                src="<?= url('uploads/' . h($p['photo'])) ?>"
                                alt="<?= h($p['name']) ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div class="ph"><?= h($p['name']) ?></div>
                        <?php endif; ?>

                        <?php if ($p['stock'] <= 0): ?>
                            <span class="badge">Habis</span>
                        <?php elseif ($p['stock'] <= LOW_STOCK): ?>
                            <span class="badge">Sisa <?= (int)$p['stock'] ?></span>
                        <?php endif; ?>
                    </div>

                    <h3><?= h($p['name']) ?></h3>
                    <div class="price"><?= rupiah($p['price']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>

        <?= pager($pages, $page) ?>
    <?php endif; ?>
<?php endif; ?>

<?php if (!$filtering): ?>
    <div class="section-label" style="margin-top:20px">
        Shop by categories
    </div>

    <div class="cat-grid">
        <?php foreach ($cats as $c): ?>
            <a class="cat-card"
               href="<?= url('index.php?category_id=' . $c['id'] . '#koleksi') ?>">
                <?= h($c['name']) ?> &gt;
            </a>
        <?php endforeach; ?>
    </div>

    <div class="about" id="about">
        <h2>About <?= h(APP_NAME) ?> Store</h2>
        <p>
            NOVA adalah ruang untuk berekspresi bebas lewat busana:
            konsep baru, eksperimen, dan batas yang terus didorong —
            tanpa harus terikat aturan industri fashion.
        </p>
    </div>
<?php endif; ?>

</main>

<script>
/* Tombol geser Featured Products */
document.querySelectorAll('[data-feat]').forEach(function (button) {
    button.addEventListener('click', function () {
        const track = document.getElementById('featTrack');
        if (!track) return;

        const firstCard = track.querySelector('.feat-card');
        if (!firstCard) return;

        const distance = firstCard.getBoundingClientRect().width;
        track.scrollBy({
            left: distance * Number(button.dataset.feat),
            behavior: 'smooth'
        });
    });
});
</script>

<?php require __DIR__ . '/includes/partials/footer.php'; ?>