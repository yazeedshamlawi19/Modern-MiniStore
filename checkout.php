<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_login();

$cart = $_SESSION['cart'] ?? [];

$pdo  = db();

$actionUrl = rtrim(BASE_URL, '/') . '/api/cod_place_order.php';

$items = [];

if (!empty($cart)) {

  $ids = array_map('intval', array_keys($cart));
  $in  = implode(',', array_fill(0, count($ids), '?'));

  $stmt = $pdo->prepare(
    "SELECT 
    pv.id AS variant_id,
    p.id  AS id,
    p.name,
    p.price
 FROM product_variants pv
 JOIN products p ON p.id = pv.product_id
 WHERE pv.id IN ($in)"
  );

  $stmt->execute($ids);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تأكيد الطلب — الدفع عند الاستلام</title>
  <link rel="stylesheet" href="assets/style.css" />

  <style>
  main.container {
    display: grid;
    grid-template-columns: 60% 40%;
    gap: 24px;
    margin-top: 30px;
  }

  @media (max-width: 900px) {
    main.container {
      grid-template-columns: 1fr;
    }
  }

  .card {
    background: var(--card);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow);
  }

  h3 {
    margin-top: 0;
    color: var(--text);
  }

  .form-grid {
    display: grid;
    gap: 12px;
  }

  input, select, textarea {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.08);
    color: #fff;
  }

  textarea {
    resize: vertical;
    min-height: 70px;
  }

  .btn.primary {
    width: 100%;
    background: linear-gradient(135deg,#9b5cff,#b26fff);
    color:#120926;
    font-weight:700;
    padding:12px 16px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    transition:.2s;
  }

  .btn.primary:hover {
    transform: scale(1.03);
    filter: brightness(1.15);
  }

  aside.card ul {
    list-style:none;
    padding:0;
    margin:0;
  }

  aside.card li {
    display:flex;
    justify-content:space-between;
    margin:6px 0;
  }

  aside.card hr {
    border: 1px solid rgba(255,255,255,.1);
    margin:10px 0;
  }
  </style>
</head>

<body>

<header class="container">
  <h1>🧾 تأكيد الطلب</h1>

  <nav>
    <a href="index.php">المنتجات</a>
    <a href="cart.php">العربة</a>
    <a href="my_orders.php">طلباتي</a>

    <?php if(is_logged_in()): ?>
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

<main class="container">

  <section class="card">

    <h3>معلومات الاستلام/التوصيل</h3>

    <?php if (empty($cart)): ?>

      <p class="muted">
        عربتك فارغة.
        <a class="btn" href="index.php">العودة للمتجر</a>
      </p>

    <?php else: ?>

      <form action="<?= htmlspecialchars($actionUrl) ?>"
            method="post"
            class="form-grid">

        <label>
          الاسم الكامل
          <input type="text" name="customer_name" required>
        </label>

        <label>
          رقم الهاتف
          <input type="text" name="customer_phone" required>
        </label>

        <label>
          طريقة الاستلام
          <select name="delivery_method" required>
            <option value="delivery">توصيل</option>
            <option value="pickup">استلام من نقطة</option>
          </select>
        </label>

        <label>
          العنوان (للتوصيل)
          <input type="text" name="address">
        </label>

        <label>
          نقطة الاستلام (للاستلام من نقطة)
          <input type="text" name="pickup_location">
        </label>

        <label>
          ملاحظات
          <textarea name="notes"></textarea>
        </label>

        <button class="btn primary" type="submit">
          تأكيد الطلب
        </button>
      </form>
    <?php endif; ?>
  </section>

  <aside class="card">

    <h3>ملخص الطلب</h3>

    <?php if (empty($items)): ?>

      <p class="muted">لا توجد عناصر لعرضها.</p>

    <?php else: ?>

      <ul>
        <?php
        $total = 0;

        foreach ($items as $it):
          $qty = (int)($cart[$it['variant_id']]['qty'] ?? 0);
          $line = $qty * (float)$it['price'];
          $total += $line;
        ?>
          <li>
            <span>
              <?= htmlspecialchars($it['name']) ?> × <?= $qty ?>
            </span>
            <strong>
              <?= number_format($line,2) ?> USD
            </strong>
          </li>
        <?php endforeach; ?>
      </ul>

      <hr/>

      <p class="row">
        <span>الإجمالي</span>
        <strong><?= number_format($total,2) ?> USD</strong>
      </p>

      <p class="muted">
        الدفع يتم عند الاستلام (COD).
      </p>

    <?php endif; ?>
  </aside>
</main>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.card').forEach((c, i) => {
      setTimeout(() => {
        c.classList.add('reveal-in');
      }, 150 * (i + 1));
    });
  });
</script>
</body>
</html>
