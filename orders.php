<?php
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/db.php';

session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit;
}

$pdo = db();

$orders = $pdo
    ->query("SELECT * FROM orders ORDER BY id DESC LIMIT 200")
    ->fetchAll();

function h($s){
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>لوحة الطلبات (مسؤول)</title>
  <link rel="stylesheet" href="assets/style.css" />
</head>

<body>

<header class="container">
  <h1>📦 الطلبات</h1>

  <nav>
    <a href="index.php">المنتجات</a>
    <a href="cart.php">العربة</a>
    <a href="orders.php">الطلبات</a>
    <a href="admin_logout.php">خروج (مسؤول)</a>
  </nav>
</header>

<main class="container">
  <?php if (!$orders): ?>
    <p>لا توجد طلبات بعد.</p>
  <?php else: ?>

    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>التاريخ</th>
          <th>الحالة</th>
          <th>المبلغ</th>
          <th>العميل</th>
          <th>طريقة</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?php echo (int)$o['id']; ?></td>
            <td><?php echo h($o['created_at']); ?></td>
            <td><?php echo h($o['status']); ?></td>
            <td>
              <?php
                echo number_format((float)$o['amount'], 2)
                . ' '
                . h($o['currency']);
              ?>
            </td>
            <td>
              <?php
                echo h($o['customer_name'])
                . ' / '
                . h($o['customer_phone']);
              ?>
            </td>
            <td><?php echo h($o['delivery_method']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>
</main>

</body>
</html>
