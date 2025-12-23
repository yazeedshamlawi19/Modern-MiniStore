<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';

$pdo = db();

/* ================== جلب ID المنتج ================== */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: index.php');
  exit;
}

/* ================== جلب المنتج ================== */
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
  header('Location: index.php');
  exit;
}

/* ================== جلب الألوان (إن وجدت) ================== */
$st2 = $pdo->prepare("
  SELECT id, color, stock
  FROM product_variants
  WHERE product_id = :pid
  ORDER BY color ASC
");
$st2->execute([':pid' => $id]);
$variants = $st2->fetchAll(PDO::FETCH_ASSOC);

/* هل المستخدم مسجل؟ */
$is_logged = !empty($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تفاصيل المنتج</title>
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    body{
      background: linear-gradient(145deg, #5b2abf, #7d4dff);
      color:#fff;
      font-family:"Cairo", sans-serif;
      min-height:100vh;
      padding:20px;
    }
    .wrap{max-width:1000px;margin:0 auto;}
    .cardx{
      background:rgba(255,255,255,0.12);
      border:1px solid rgba(255,255,255,0.2);
      border-radius:14px;
      padding:18px;
    }
    .grid{
      display:grid;
      grid-template-columns: 360px 1fr;
      gap:18px;
      align-items:start;
    }
    .img{
      width:100%;
      border-radius:12px;
      overflow:hidden;
      background:rgba(0,0,0,0.2);
      border:1px solid rgba(255,255,255,0.2);
    }
    .img img{width:100%;display:block;}
    .btnx{
      display:inline-block;
      padding:10px 16px;
      border-radius:10px;
      text-decoration:none;
      border:none;
      cursor:pointer;
      font-weight:700;
    }
    .primary{background:#a78bfa;color:#fff;}
    .green{background:#22c55e;color:#fff;}
    select{
      width:100%;
      padding:10px;
      border-radius:10px;
      border:none;
      margin-top:8px;
    }
    .muted{opacity:.9;}
    @media (max-width: 900px){
      .grid{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>

<div class="wrap">

  <a class="btnx green" href="index.php">⬅️ رجوع للمنتجات</a>

  <div class="cardx" style="margin-top:14px;">
    <div class="grid">

      <!-- صورة المنتج -->
      <div class="img">
        <?php if (!empty($product['image_url'])): ?>
          <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="product">
        <?php else: ?>
          <div style="padding:40px;text-align:center;">لا توجد صورة</div>
        <?php endif; ?>
      </div>

      <!-- تفاصيل المنتج -->
      <div>

        <h2><?= htmlspecialchars($product['name']) ?></h2>

        <p class="muted" style="font-size:18px;">
          السعر: <b><?= number_format((float)$product['price'], 2) ?> USD</b>
        </p>

        <p class="muted">
          الكمية العامة: <b><?= (int)$product['stock'] ?></b>
        </p>

        <?php if (!empty($product['description'])): ?>
          <p class="muted" style="margin-top:12px;line-height:1.7;">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
          </p>
        <?php endif; ?>

        <hr style="border-color:rgba(255,255,255,0.2);margin:14px 0;">

        <form method="post" action="cart_add.php">

          <!-- ID المنتج -->
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

          <?php if (!empty($variants)): ?>
            <!-- المنتج يحتوي على ألوان -->
            <label>اختر اللون (وكمية اللون المتاحة):</label>

            <select name="variant_id" required>
              <option value="" disabled selected>اختر لون</option>
              <?php foreach ($variants as $v): ?>
                <option value="<?= (int)$v['id'] ?>" <?= ((int)$v['stock'] <= 0 ? 'disabled' : '') ?>>
                  <?= htmlspecialchars($v['color']) ?> — المتاح: <?= (int)$v['stock'] ?>
                  <?= ((int)$v['stock'] <= 0 ? ' (غير متوفر)' : '') ?>
                </option>
              <?php endforeach; ?>
            </select>

          <?php else: ?>
            <!-- المنتج بدون ألوان -->
            <input type="hidden" name="variant_id" value="0">
            <p class="muted">هذا المنتج لا يحتوي على ألوان.</p>
          <?php endif; ?>

          <!-- الكمية -->
          <label style="display:block;margin-top:12px;">الكمية المطلوبة:</label>
          <input type="number" name="qty" min="1" value="1" required
                 style="padding:10px;border-radius:10px;border:none;width:140px;">

          <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <?php if ($is_logged): ?>
              <button class="btnx primary" type="submit">🛒 إضافة للعربة</button>
            <?php else: ?>
              <a class="btnx primary"
                 href="user_login.php?next=<?= urlencode('product_details.php?id=' . $product['id']) ?>">
                🔒 تسجيل الدخول للشراء
              </a>
            <?php endif; ?>
          </div>

        </form>

      </div>

    </div>
  </div>

</div>

</body>
</html>
