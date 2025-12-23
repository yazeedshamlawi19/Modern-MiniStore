<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/* تأكد إن المستخدم مسجل */
if (!is_logged_in()) {
  $_SESSION['flash'] = 'يرجى تسجيل الدخول لإضافة منتجات إلى العربة.';
  header('Location: user_login.php');
  exit;
}

/* تأكد من البيانات */
if (
  empty($_POST['product_id']) ||
  empty($_POST['qty'])
) {
  header('Location: index.php');
  exit;
}

$product_id = (int)$_POST['product_id'];
$variant_id = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
$qty        = max(1, (int)$_POST['qty']);

$pdo = db();

/* جلب المنتج + اللون + مخزون اللون */
if ($variant_id) {

  // المنتج مع لون
  $stmt = $pdo->prepare(
    "SELECT 
        pv.id   AS variant_id,
        pv.stock AS stock,
        p.id    AS product_id,
        p.name,
        p.price
     FROM product_variants pv
     JOIN products p ON p.id = pv.product_id
     WHERE pv.id = :vid AND p.id = :pid
     LIMIT 1"
  );

  $stmt->execute([
    ':vid' => $variant_id,
    ':pid' => $product_id
  ]);

} else {

  // المنتج بدون لون
  $stmt = $pdo->prepare(
    "SELECT 
        id AS product_id,
        name,
        price,
        stock
     FROM products
     WHERE id = :pid
     LIMIT 1"
  );

  $stmt->execute([
    ':pid' => $product_id
  ]);
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  $_SESSION['flash'] = 'المنتج غير موجود.';
  header('Location: index.php');
  exit;
}

if ($row['stock'] < $qty) {
  $_SESSION['flash'] = 'الكمية المطلوبة غير متوفرة.';
  header('Location: product_details.php?id=' . $product_id);
  exit;
}

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

$key = $variant_id > 0
  ? 'v_' . $variant_id   // منتج مع لون
  : 'p_' . $product_id;  // منتج بدون لون

if (!isset($_SESSION['cart'][$key])) {
  $_SESSION['cart'][$key] = [
    'product_id' => $product_id,
    'variant_id' => $variant_id,
    'qty' => 0
  ];
}

$_SESSION['cart'][$key]['qty'] += $qty;

header('Location: cart.php');
exit;
