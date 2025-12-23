<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

require_login();

$pdo = db();

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    $_SESSION['flash'] = '❌ لا يمكن تنفيذ الطلب — العربة فارغة.';
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$customer_name   = trim($_POST['customer_name'] ?? '');
$customer_phone  = trim($_POST['customer_phone'] ?? '');
$delivery_method = $_POST['delivery_method'] ?? 'delivery';
$address         = trim($_POST['address'] ?? '');
$pickup_location = trim($_POST['pickup_location'] ?? '');
$notes           = trim($_POST['notes'] ?? '');

if ($customer_name === '' || $customer_phone === '') {
    $_SESSION['flash'] = '❌ يرجى تعبئة جميع الحقول المطلوبة.';
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}

try {
    $pdo->beginTransaction();

    /* =========================
       فصل المنتجات (مع لون / بدون لون)
    ========================= */
    $variantIds = [];
    $productIds = [];

    foreach ($cart as $key => $row) {
        if (str_starts_with($key, 'v_')) {
            $variantIds[] = (int)$row['variant_id'];
        } else {
            $productIds[] = (int)$row['product_id'];
        }
    }

    $products = [];

    /* ===== منتجات مع ألوان ===== */
    if ($variantIds) {
        $in = implode(',', array_fill(0, count($variantIds), '?'));

        $stmt = $pdo->prepare(
            "SELECT
                pv.id AS variant_id,
                pv.stock AS variant_stock,
                p.id  AS product_id,
                p.stock AS product_stock,
                p.name,
                p.price
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.id IN ($in)"
        );
        $stmt->execute($variantIds);
        $products = array_merge($products, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /* ===== منتجات بدون ألوان ===== */
    if ($productIds) {
        $in = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $pdo->prepare(
            "SELECT
                id AS product_id,
                stock AS product_stock,
                name,
                price,
                NULL AS variant_id,
                NULL AS variant_stock
             FROM products
             WHERE id IN ($in)"
        );
        $stmt->execute($productIds);
        $products = array_merge($products, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /* =========================
       حساب الإجمالي + التحقق من المخزون
    ========================= */
    $total = 0;

    foreach ($products as $p) {
        $key = $p['variant_id']
            ? 'v_' . $p['variant_id']
            : 'p_' . $p['product_id'];

        $qty = (int)($cart[$key]['qty'] ?? 0);

        // تحقق من المخزون
        if ($p['variant_id']) {
            if ($p['variant_stock'] < $qty) {
                throw new Exception('الكمية غير متوفرة للمنتج: ' . $p['name']);
            }
        } else {
            if ($p['product_stock'] < $qty) {
                throw new Exception('الكمية غير متوفرة للمنتج: ' . $p['name']);
            }
        }

        $total += $p['price'] * $qty;
    }

    /* =========================
       إنشاء الطلب
    ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (user_id, gateway_order_id, status, currency, amount,
         customer_name, customer_phone, delivery_method,
         address, pickup_location, notes, created_at)
        VALUES
        (:uid, :goid, :status, 'USD', :amount,
         :name, :phone, :method,
         :addr, :pickup, :notes, NOW())
    ");

    $stmt->execute([
        ':uid'    => current_user_id(),
        ':goid'   => 'COD-' . time(),
        ':status' => 'قيد التأكيد',
        ':amount' => $total,
        ':name'   => $customer_name,
        ':phone'  => $customer_phone,
        ':method' => $delivery_method,
        ':addr'   => $address,
        ':pickup' => $pickup_location,
        ':notes'  => $notes
    ]);

    $order_id = $pdo->lastInsertId();

    /* =========================
       إدخال عناصر الطلب + إنقاص المخزون
    ========================= */
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items
        (order_id, product_id, name, qty, unit_price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($products as $p) {
        $key = $p['variant_id']
            ? 'v_' . $p['variant_id']
            : 'p_' . $p['product_id'];

        $qty = (int)($cart[$key]['qty'] ?? 0);

        // حفظ عنصر الطلب
        $stmtItem->execute([
            $order_id,
            $p['product_id'],
            $p['name'],
            $qty,
            $p['price']
        ]);

        // ⬇️ إنقاص المخزون
        if ($p['variant_id']) {

            // إنقاص مخزون اللون
            $stmtStockVariant = $pdo->prepare(
                "UPDATE product_variants
                 SET stock = stock - ?
                 WHERE id = ?"
            );
            $stmtStockVariant->execute([$qty, $p['variant_id']]);
        }

        // إنقاص المخزون العام للمنتج
        $stmtStockProduct = $pdo->prepare(
            "UPDATE products
             SET stock = stock - ?
             WHERE id = ?"
        );
        $stmtStockProduct->execute([$qty, $p['product_id']]);
    }

    $pdo->commit();
    unset($_SESSION['cart']);

    header('Location: ' . BASE_URL . 'thankyou.php?order_id=' . $order_id);
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    $_SESSION['flash'] = '⚠️ حدث خطأ أثناء تنفيذ الطلب.';
    header('Location: ' . BASE_URL . 'checkout.php');
    exit;
}
