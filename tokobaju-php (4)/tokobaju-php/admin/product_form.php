
<?php
// Form tambah dan edit produk + stok per ukuran
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = $id ? find_product($id, false) : null;

if ($id && !$p) {
    flash('err', 'Produk tidak ditemukan.');
    redirect('admin/products.php');
}

$cats = all_categories();

$v = $p ?? [
    'name' => '',
    'category_id' => '',
    'price' => 0,
    'stock' => 0,
    'status' => 'active',
    'description' => '',
    'photo' => null
];

$error = '';
$sizes = ['S', 'M', 'L', 'XL', 'XXL'];
$sizeStocks = array_fill_keys($sizes, 0);
$existingSizes = [];

if ($p) {
    $rows = q(
        "SELECT size, stock FROM product_sizes WHERE product_id = ?",
        [$id]
    )->fetchAll();

    foreach ($rows as $row) {
        $key = strtoupper($row['size']);
        $existingSizes[$key] = (int)$row['stock'];

        if (in_array($key, $sizes, true)) {
            $sizeStocks[$key] = (int)$row['stock'];
        }
    }
}

$hasSizeData = !empty($existingSizes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = array_merge($v, [
        'name' => trim($_POST['name'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'price' => (int)($_POST['price'] ?? 0),
        'status' => ($_POST['status'] ?? '') === 'inactive'
            ? 'inactive' : 'active',
        'description' => trim($_POST['description'] ?? ''),
    ]);

    $postedSizes = $_POST['sizes'] ?? [];
    $totalStock = 0;
    $validSizes = true;

    foreach ($sizes as $size) {
        $raw = $postedSizes[$size] ?? '';

        if (
            !is_scalar($raw) ||
            (string)$raw === '' ||
            !ctype_digit((string)$raw)
        ) {
            $validSizes = false;
            break;
        }

        $sizeStocks[$size] = (int)$raw;
        $totalStock += $sizeStocks[$size];
    }

    $v['stock'] = $totalStock;

    $catOk = (bool)q(
        "SELECT 1 FROM categories WHERE id = ?",
        [$v['category_id']]
    )->fetch();

    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Sesi formulir tidak valid.';
    } elseif ($v['name'] === '' || !$catOk) {
        $error = 'Nama dan kategori wajib diisi.';
    } elseif ($v['price'] < 0) {
        $error = 'Harga tidak boleh negatif.';
    } elseif (!$validSizes) {
        $error = 'Stok setiap ukuran harus berupa bilangan bulat nol atau lebih.';
    } elseif ($totalStock > 2147483647) {
        $error = 'Total stok terlalu besar.';
    } else {
        $photo = save_upload($_FILES['photo'] ?? null);

        if ($photo === false) {
            $error = 'Foto tidak valid. Gunakan JPG/PNG/WEBP maksimal 5 MB.';
        } else {
            if ($p) {
                q(
                    "UPDATE products
                     SET category_id=?, name=?, price=?, stock=?,
                         description=?, status=?
                     WHERE id=?",
                    [
                        $v['category_id'],
                        $v['name'],
                        $v['price'],
                        $totalStock,
                        $v['description'],
                        $v['status'],
                        $id
                    ]
                );

                if ($photo) {
                    delete_upload($p['photo']);
                    q(
                        "UPDATE products SET photo=? WHERE id=?",
                        [$photo, $id]
                    );
                }

                $productId = $id;
            } else {
                q(
                    "INSERT INTO products
                     (category_id, name, price, stock, description, photo, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $v['category_id'],
                        $v['name'],
                        $v['price'],
                        $totalStock,
                        $v['description'],
                        $photo,
                        $v['status']
                    ]
                );

                $productId = (int)q(
                    "SELECT LAST_INSERT_ID() AS id"
                )->fetch()['id'];
            }

            foreach ($sizes as $size) {
                q(
                    "INSERT INTO product_sizes (product_id, size, stock)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE stock = VALUES(stock)",
                    [$productId, $size, $sizeStocks[$size]]
                );
            }

            flash(
                'ok',
                $p
                    ? 'Produk dan stok ukuran berhasil diperbarui.'
                    : 'Produk berhasil ditambahkan beserta ukuran dan stoknya.'
            );

            redirect('admin/products.php');
        }
    }
}

$pageTitle = $p ? 'Edit produk' : 'Tambah produk';
$active = 'products';

require __DIR__ . '/../includes/partials/admin_header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow">CATALOG MANAGEMENT</div>
        <h1><?= h($pageTitle) ?></h1>
    </div>
    <a class="btn light" href="products.php">← Daftar produk</a>
</div>

<section class="panel narrow">

    <?php if ($error): ?>
        <div class="alert danger"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($p && !$hasSizeData): ?>
        <div class="alert">
            Produk ini masih menggunakan stok lama.
            Masukkan stok untuk masing-masing ukuran di bawah.
            Setelah disimpan, stok total akan dihitung dari stok ukuran.
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="field">
            <label>Nama produk</label>
            <input
                type="text"
                name="name"
                value="<?= h($v['name']) ?>"
                maxlength="150"
                required
            >
        </div>

        <div class="row">
            <div class="field">
                <label>Kategori</label>
                <select name="category_id" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($cats as $c): ?>
                        <option
                            value="<?= (int)$c['id'] ?>"
                            <?= (int)$v['category_id'] === (int)$c['id']
                                ? 'selected' : '' ?>
                        >
                            <?= h($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Status produk</label>
                <select name="status">
                    <option value="active"
                        <?= $v['status'] === 'active' ? 'selected' : '' ?>>
                        Aktif
                    </option>
                    <option value="inactive"
                        <?= $v['status'] === 'inactive' ? 'selected' : '' ?>>
                        Nonaktif
                    </option>
                </select>
            </div>
        </div>

        <div class="field">
            <label>Harga (Rp)</label>
            <input
                type="number"
                name="price"
                min="0"
                value="<?= (int)$v['price'] ?>"
                required
            >
        </div>

        <div class="size-inventory">
            <div class="inventory-heading">
                <div>
                    <div class="eyebrow">INVENTORY</div>
                    <h2>Ukuran & stok</h2>
                </div>
                <span class="inventory-tag">SIZE MANAGEMENT</span>
            </div>

            <p class="inventory-description">
                Atur stok untuk setiap ukuran. Ukuran dengan stok nol
                akan dianggap habis.
            </p>

            <div class="size-stock-list">
                <?php foreach ($sizes as $size): ?>
                    <div class="size-stock-row">
                        <div class="size-label">
                            <span class="size-badge">
                                <?= h($size) ?>
                            </span>
                            <span class="size-label-text">
                                Ukuran <?= h($size) ?>
                            </span>
                        </div>

                        <div class="stock-control">
                            <label for="size_<?= h($size) ?>">Stok</label>
                            <input
                                id="size_<?= h($size) ?>"
                                type="number"
                                name="sizes[<?= h($size) ?>]"
                                min="0"
                                step="1"
                                value="<?= (int)$sizeStocks[$size] ?>"
                                required
                            >
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="inventory-total">
                <div>
                    <span class="total-caption">TOTAL STOK</span>
                    <span class="total-note">Semua ukuran</span>
                </div>
                <strong id="sizeTotal"><?= (int)array_sum($sizeStocks) ?></strong>
                <span class="total-unit">pcs</span>
            </div>

            <p class="inventory-footnote">
                Total dihitung otomatis. Kolom products.stock akan
                diperbarui saat produk disimpan.
            </p>
        </div>

        <div class="field">
            <label>Deskripsi produk</label>
            <textarea
                name="description"
                rows="5"
            ><?= h($v['description']) ?></textarea>
        </div>

        <div class="field">
            <label>
                Foto produk
                <small>
                    JPG/PNG/WEBP maksimal 5 MB
                    <?= $p ? ' — kosongkan bila tidak diganti' : '' ?>
                </small>
            </label>

            <input
                type="file"
                name="photo"
                accept=".jpg,.jpeg,.png,.webp"
                data-preview="#pv"
            >

            <img
                id="pv"
                class="preview"
                src="<?= !empty($v['photo'])
                    ? url('uploads/' . h($v['photo'])) : '' ?>"
                alt=""
                <?= !empty($v['photo']) ? '' : 'hidden' ?>
            >
        </div>

        <button class="btn full" type="submit">
            <?= $p ? 'Simpan perubahan' : 'Simpan produk' ?>
        </button>
    </form>
</section>

<style>
.size-inventory {
    margin: 28px 0;
    padding: 22px;
    border: 1px solid #dedede;
    background: #fff;
    color: #111;
}

.inventory-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.inventory-heading h2 {
    margin: 5px 0 0;
    font-size: 20px;
    font-weight: 500;
}

.inventory-tag {
    font-size: 9px;
    letter-spacing: 1px;
    color: #777;
    text-align: right;
}

.inventory-description {
    margin: 15px 0 20px;
    color: #777;
    font-size: 12px;
    line-height: 1.7;
}

.size-stock-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 13px 0;
    border-top: 1px solid #eee;
}

.size-label {
    display: flex;
    align-items: center;
    gap: 12px;
}

.size-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border: 1px solid #222;
    font-size: 12px;
    font-weight: 600;
}

.size-label-text {
    font-size: 12px;
}

.stock-control {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stock-control label {
    color: #777;
    font-size: 11px;
}

.stock-control input {
    width: 90px;
    height: 40px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 0;
    color: #111;
    background: #fff;
}

.inventory-total {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 16px;
    padding: 18px;
    background: #f5f5f5;
}

.inventory-total > div {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 5px;
}

.total-caption {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1px;
}

.total-note,
.total-unit {
    color: #777;
    font-size: 11px;
}

.inventory-total strong {
    font-size: 25px;
    font-weight: 500;
}

.inventory-footnote {
    margin: 12px 0 0;
    color: #777;
    font-size: 11px;
    line-height: 1.7;
}

@media (max-width: 480px) {
    .size-inventory {
        padding: 15px;
    }

    .inventory-tag {
        max-width: 85px;
        line-height: 1.5;
    }

    .size-label {
        gap: 8px;
    }

    .size-label-text {
        font-size: 11px;
    }

    .stock-control {
        gap: 6px;
    }

    .stock-control input {
        width: 68px;
    }
}
</style>

<script>
(function () {
    const inputs = document.querySelectorAll(
        '.size-stock-row input[type="number"]'
    );
    const totalElement = document.getElementById('sizeTotal');

    function updateTotal() {
        let total = 0;

        inputs.forEach(function (input) {
            const value = Number(input.value);
            if (Number.isFinite(value) && value >= 0) {
                total += Math.floor(value);
            }
        });

        totalElement.textContent = total.toLocaleString('id-ID');
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', updateTotal);
    });

    updateTotal();
})();
</script>

<?php require __DIR__ . '/../includes/partials/admin_footer.php'; ?>