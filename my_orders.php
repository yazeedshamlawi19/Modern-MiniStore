<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_login();

$pdo = db();

$orders = [];
$itemsByOrder = [];

$uid = current_user_id();

$stmt = $pdo->prepare(
  "SELECT * FROM orders 
   WHERE user_id = :uid 
   ORDER BY id ASC"
);

$stmt->execute([':uid' => $uid]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($orders) {

  $ids = array_column($orders, 'id');
  $in = implode(',', array_fill(0, count($ids), '?'));

  $stmt2 = $pdo->prepare(
    "SELECT * FROM order_items 
     WHERE order_id IN ($in)"
  );

  $stmt2->execute($ids);

  foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $it) {
    $itemsByOrder[$it['order_id']][] = $it;
  }
}


function order_status_meta(string $status): array {

  $status = trim($status);

  return match ($status) {

    // عربي
    'قيد التأكيد' => ['badge-pending', 'قيد التأكيد'],
    'قيد التنفيذ' => ['badge-processing', 'قيد التنفيذ'],
    'مكتمل'       => ['badge-completed', 'مكتمل'],
    'ملغى'        => ['badge-cancelled', 'ملغى'],

    default => ['badge-neutral', $status],
  };
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>طلباتي</title>
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    main {
      display: grid;
      gap: 22px;
      margin: 40px auto;
      max-width: 900px;
    }

    .order-card {
      background: linear-gradient(
        145deg,
        rgba(50, 20, 85, 0.9),
        rgba(70, 25, 110, 0.8)
      );
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 18px;
      padding: 22px;
      color: #fff;
      box-shadow: 0 10px 25px rgba(0,0,0,0.25);
      transition: transform 0.3s ease,
                  box-shadow 0.3s ease;
    }

    .order-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(150, 90, 255, 0.25);
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .order-header h3 {
      margin: 0;
      font-size: 18px;
      color: #d6c9e6;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .2px;
      border: 1px solid transparent;
    }

    .badge-pending {
      background: rgba(250, 204, 21, .15);
      color: #fde68a;
      border-color: rgba(250, 204, 21, .35);
    }

    .badge-completed {
      background: rgba(16, 185, 129, .15);
      color: #a7f3d0;
      border-color: rgba(16,185,129,.35);
    }

    .badge-cancelled {
      background: rgba(239, 68, 68, .15);
      color: #fecaca;
      border-color: rgba(239,68,68,.35);
    }

    .badge-neutral {
      background: rgba(250, 204, 21, .15);
      color: #fde68a;
      border-color: rgba(250, 204, 21, .35);
    }

    .badge::before {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: currentColor;
      opacity: .85;
      box-shadow: 0 0 10px currentColor;
    }

    ul.order-items {
      list-style: none;
      padding: 0;
      margin: 8px 0 12px;
      border-top: 1px solid rgba(255,255,255,0.1);
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    ul.order-items li {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      color: #eee;
    }

    .total-line {
      display: flex;
      justify-content: space-between;
      font-weight: bold;
      font-size: 15px;
      margin-top: 8px;
    }

    .delivery-info {
      margin-top: 6px;
      font-size: 14px;
      color: #cbbbee;
    }

    .muted {
      color: #bdaedb;
    }
  </style>
</head>
<body>

<header class="container">
  <h1>🧾 طلباتي</h1>

  <nav>
    <a href="index.php">المنتجات</a>
    <a href="cart.php">العربة</a>
    <a href="my_orders.php">طلباتي</a>

    <?php if (is_admin()): ?>
      <a href="orders.php">الطلبات (مسؤول)</a>
      <a href="admin_logout.php">خروج (مسؤول)</a>
    <?php endif; ?>

    <?php if (is_logged_in()): ?>
      <span class="muted">
        مرحباً، <?= htmlspecialchars($_SESSION['user']['name']) ?>
      </span>
      <a href="user_logout.php">خروج</a>
    <?php else: ?>
      <a href="user_login.php">دخول</a>
      <a href="user_register.php">تسجيل</a>
    <?php endif; ?>
  </nav>
</header>

<main>

  <?php if (!$orders): ?>
    <p style="text-align:center; color:var(--muted); margin-top:40px;">
      لا توجد طلبات للعرض حالياً.
    </p>
  <?php else: ?>

    <?php $i = 1; foreach ($orders as $o): ?>

      <?php
        [$stClass, $stLabel] = order_status_meta($o['status'] ?? '');
      ?>

      <section class="order-card reveal-in">

        <div class="order-header">
          <h3>طلب رقم <?= $i++; ?></h3>
          <span class="muted">
            <?= htmlspecialchars($o['created_at']); ?>
          </span>
        </div>

        <div class="badge <?= $stClass; ?>">
          <?= htmlspecialchars($stLabel); ?>
        </div>

        <ul class="order-items">
          <?php foreach ($itemsByOrder[$o['id']] ?? [] as $it): ?>
            <li>
              <span>
                <?= htmlspecialchars($it['name']); ?> × <?= (int)$it['qty']; ?>
              </span>
              <strong>
                <?= number_format((float)$it['unit_price'] * (int)$it['qty'], 2); ?> USD
              </strong>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="total-line">
          <span>الإجمالي:</span>
          <strong>
            <?= number_format((float)$o['amount'], 2); ?> USD
          </strong>
        </div>

        <div class="delivery-info">
          طريقة الاستلام:
          <strong>
            <?= $o['delivery_method'] === 'pickup' ? 'استلام من نقطة' : 'توصيل'; ?>
          </strong>

          <?php if ($o['delivery_method'] === 'pickup' && $o['pickup_location']): ?>
            — النقطة: <?= htmlspecialchars($o['pickup_location']); ?>
          <?php elseif ($o['delivery_method'] === 'delivery' && $o['address']): ?>
            — العنوان: <?= htmlspecialchars($o['address']); ?>
          <?php endif; ?>
        </div>
      </section>

    <?php endforeach; ?>
  <?php endif; ?>
</main>

<script>
window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.order-card').forEach((c, i) => {
    setTimeout(() => c.classList.add('reveal-in'), 120 * (i + 1));
  });
});
</script>

</body>
</html>
