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

  $items = [];
  $total = 0;

  foreach ($cart as $key => $c) {

    // منتج مع لون
    if (strpos($key, 'v_') === 0) {

      $stmt = $pdo->prepare("
        SELECT 
          p.name,
          p.price,
          pv.color
        FROM product_variants pv
        JOIN products p ON p.id = pv.product_id
        WHERE pv.id = ?
      ");
      $stmt->execute([(int)$c['variant_id']]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

    } else {

      // منتج بدون لون
      $stmt = $pdo->prepare("
        SELECT 
          name,
          price,
          NULL AS color
        FROM products
        WHERE id = ?
      ");
      $stmt->execute([(int)$c['product_id']]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$row) continue;

    $qty = (int)$c['qty'];
    $line = $qty * (float)$row['price'];
    $total += $line;

    $items[] = [
      'name'  => $row['name'],
      'color' => $row['color'],
      'qty'   => $qty,
      'price'=> $row['price'],
      'line' => $line
    ];
  }

  if (!$items) {
    echo '<p>🛒 عربتك فارغة.</p>';
  } else {

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
      echo '
        <tr>
          <td>'.htmlspecialchars($it['name']).'</td>
          <td>'.($it['color'] ? htmlspecialchars($it['color']) : '—').'</td>
          <td>'.$it['qty'].'</td>
          <td>'.number_format($it['price'], 2).'</td>
          <td>'.number_format($it['line'], 2).'</td>
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
