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

    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));

    // 🔧 تعديل الاستعلام فقط
    $stmt = $pdo->prepare(
        "SELECT
            pv.id AS variant_id,
            p.id  AS product_id,
            p.name,
            p.price
         FROM product_variants pv
         JOIN products p ON p.id = pv.product_id
         WHERE pv.id IN ($in)"
    );
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;

    foreach ($products as $p) {
        $qty = (int)($cart[$p['variant_id']]['qty'] ?? 0);
        $total += $p['price'] * $qty;
    }

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

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items
        (order_id, product_id, name, qty, unit_price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($products as $p) {
        $qty = (int)($cart[$p['variant_id']]['qty'] ?? 0);
        $stmtItem->execute([
            $order_id,
            $p['product_id'],
            $p['name'],
            $qty,
            $p['price']
        ]);
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
