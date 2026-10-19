
<?php
/* ===================== KATEGORI & PRODUK ===================== */

function all_categories(): array {
    return q("SELECT * FROM categories ORDER BY name")->fetchAll();
}

const PRODUCT_SELECT = "SELECT p.*, c.name AS category
                        FROM products p
                        LEFT JOIN categories c ON c.id = p.category_id";

function find_product(int $id, bool $onlyActive = true): ?array {
    $r = q(
        PRODUCT_SELECT . " WHERE p.id = ?" .
        ($onlyActive ? " AND p.status = 'active'" : ''),
        [$id]
    )->fetch();

    return $r ?: null;
}

/* ============================ KERANJANG ============================ */

function cart_items(): array {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];

    $ids = array_map('intval', array_keys($cart));
    $in = implode(',', array_fill(0, count($ids), '?'));

    $rows = q(
        PRODUCT_SELECT . " WHERE p.id IN ($in) AND p.status = 'active'",
        $ids
    )->fetchAll();

    $items = [];

    foreach ($rows as $p) {
        $id = (int)$p['id'];
        $qty = min((int)$cart[$id], (int)$p['stock']);

        if ($qty < 1) continue;

        $size = $_SESSION['cart_sizes'][$id] ?? '';

        $items[] = [
            'product'  => $p,
            'qty'      => $qty,
            'size'     => $size,
            'subtotal' => (int)$p['price'] * $qty
        ];
    }

    return $items;
}

function cart_count(): int {
    return array_sum($_SESSION['cart'] ?? []);
}

function cart_total(array $items): int {
    return array_sum(array_column($items, 'subtotal'));
}

/* ============================ PESANAN ============================ */

function order_items(int $orderId): array {
    return q(
        "SELECT * FROM order_items WHERE order_id = ?",
        [$orderId]
    )->fetchAll();
}

function create_order(int $userId, array $data, array $items): array {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $total = cart_total($items);
        $code = 'NV' . date('ymdHis') . random_int(100, 999);

        q(
            "INSERT INTO orders
            (order_code, user_id, customer_name, address, phone,
             payment_method, total, status, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu Konfirmasi', ?)",
            [
                $code,
                $userId,
                $data['name'],
                $data['address'],
                $data['phone'],
                $data['payment'],
                $total,
                $data['note']
            ]
        );

        $oid = (int)$pdo->lastInsertId();

        foreach ($items as $it) {
            $p = $it['product'];
            $pid = (int)$p['id'];
            $qty = (int)$it['qty'];
            $size = strtoupper(trim((string)($it['size'] ?? '')));

            if (!in_array($size, ['S', 'M', 'L', 'XL', 'XXL'], true)) {
                throw new RuntimeException(
                    'Ukuran produk "' . $p['name'] .
                    '" belum dipilih. Silakan kembali ke keranjang.'
                );
            }

            // Kurangi stok ukuran yang dipilih.
            $st = q(
                "UPDATE product_sizes
                 SET stock = stock - ?
                 WHERE product_id = ? AND size = ? AND stock >= ?",
                [$qty, $pid, $size, $qty]
            );

            if ($st->rowCount() !== 1) {
                throw new RuntimeException(
                    'Stok ukuran ' . $size . ' untuk "' .
                    $p['name'] . '" tidak mencukupi.'
                );
            }

            // Kurangi stok total produk.
            $st = q(
                "UPDATE products
                 SET stock = stock - ?
                 WHERE id = ? AND status = 'active' AND stock >= ?",
                [$qty, $pid, $qty]
            );

            if ($st->rowCount() !== 1) {
                throw new RuntimeException(
                    'Stok "' . $p['name'] .
                    '" tidak mencukupi. Periksa keranjang.'
                );
            }

            // Simpan ukuran pada item pesanan.
            q(
                "INSERT INTO order_items
                 (order_id, product_id, product_name, price, qty, size)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$oid, $pid, $p['name'], $p['price'], $qty, $size]
            );
        }

        $pdo->commit();
        return [$oid, $code];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/* ======================== UBAH STATUS PESANAN ======================== */

function change_order_status(int $orderId, string $new): bool {
    if (!in_array($new, ORDER_STATUSES, true)) {
        return false;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $o = q(
            "SELECT status FROM orders WHERE id = ?",
            [$orderId]
        )->fetch();

        if (!$o) {
            $pdo->rollBack();
            return false;
        }

        $old = $o['status'];
        $wasOut = in_array($old, RESTOCK_STATUSES, true);
        $isOut = in_array($new, RESTOCK_STATUSES, true);

        if (!$wasOut && $isOut) {
            // Pesanan dibatalkan/ditolak: kembalikan stok.
            foreach (order_items($orderId) as $i) {
                if (!empty($i['product_id'])) {
                    q(
                        "UPDATE products SET stock = stock + ? WHERE id = ?",
                        [$i['qty'], $i['product_id']]
                    );

                    if (!empty($i['size'])) {
                        q(
                            "UPDATE product_sizes
                             SET stock = stock + ?
                             WHERE product_id = ? AND size = ?",
                            [$i['qty'], $i['product_id'], $i['size']]
                        );
                    }
                }
            }

        } elseif ($wasOut && !$isOut) {
            // Pesanan diaktifkan kembali: kurangi stok lagi.
            foreach (order_items($orderId) as $i) {
                if (empty($i['product_id'])) continue;

                if (!empty($i['size'])) {
                    $ss = q(
                        "UPDATE product_sizes
                         SET stock = stock - ?
                         WHERE product_id = ? AND size = ? AND stock >= ?",
                        [$i['qty'], $i['product_id'], $i['size'], $i['qty']]
                    );

                    if ($ss->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Stok ukuran ' . $i['size'] .
                            ' untuk "' . $i['product_name'] .
                            '" tidak cukup.'
                        );
                    }
                }

                $st = q(
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?",
                    [$i['qty'], $i['product_id'], $i['qty']]
                );

                if ($st->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Stok "' . $i['product_name'] .
                        '" tidak cukup untuk mengaktifkan kembali pesanan.'
                    );
                }
            }
        }

        q(
            "UPDATE orders
             SET status = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$new, $orderId]
        );

        $pdo->commit();
        return true;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}