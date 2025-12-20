<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$cart = $_SESSION['cart'] ?? [];
$pdo  = db();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>🧺 العربة</title>
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body>

<header class="container">
  <h1>🧺 العربة</h1>

  <nav>
    <a href="index.php">المنتجات</a>
    <a href="cart.php">العربة</a>
    <a href="my_orders.php">طلباتي</a>

    <?php if (is_admin()): ?>
      <a href="admin_dashboard.php">لوحة التحكم (مسؤول)</a>
      <a href="orders.php">الطلبات</a>
      <a href="admin_logout.php">خروج المسؤول</a>
    <?php endif; ?>

    <?php if (is_logged_in()): ?>
      <span>مرحباً، <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
      <a href="user_logout.php">خروج</a>
    <?php else: ?>
      <a href="user_login.php">دخول</a>
      <a href="user_register.php">تسجيل</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
<?php
if (!$cart) {

  echo '<p>🛒 عربتك فارغة.</p>';

} else {

  // مفاتيح العربة هي variant_id
  $variantIds = array_map('intval', array_keys($cart));
  $in = implode(',', array_fill(0, count($variantIds), '?'));

  $stmt = $pdo->prepare(
    "SELECT
        pv.id   AS variant_id,
        pv.color,
        p.name,
        p.price
     FROM product_variants pv
     JOIN products p ON p.id = pv.product_id
     WHERE pv.id IN ($in)"
  );

  $stmt->execute($variantIds);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!$items) {
    echo '<p>🛒 عربتك فارغة.</p>';
  } else {

    $total = 0;

    echo '
      <table class="table">
        <thead>
          <tr>
            <th>المنتج</th>
            <th>اللون</th>
            <th>الكمية</th>
            <th>السعر</th>
            <th>الإجمالي</th>
          </tr>
        </thead>
        <tbody>
    ';

    foreach ($items as $it) {

      $variant_id = (int)$it['variant_id'];
      $qty = $cart[$variant_id]['qty'] ?? 0;

      if ($qty <= 0) continue;

      $price = (float)$it['price'];
      $line  = $qty * $price;
      $total += $line;

      echo '
        <tr>
          <td>'.htmlspecialchars($it['name']).'</td>
          <td>'.htmlspecialchars($it['color']).'</td>
          <td>'.$qty.'</td>
          <td>'.number_format($price, 2).'</td>
          <td>'.number_format($line, 2).'</td>
        </tr>
      ';
    }

    echo '
        </tbody>
      </table>
    ';

    echo '
      <p class="total">
        الإجمالي: <strong>'.number_format($total, 2).'</strong>
      </p>
    ';

    echo '
      <p>
        <a class="btn primary" href="checkout.php">
          تأكيد الطلب (الدفع عند الاستلام)
        </a>
      </p>
    ';

    echo '
      <p class="muted">
        <em>سيتم الدفع عند الاستلام أو من نقطة الاستلام.</em>
      </p>
    ';
  }
}
?>
</main>

</body>
</html>
