<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

$order_id = (int)($_GET['order_id'] ?? 0);

$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute([
    ':id' => $order_id
]);

$o = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تم استلام طلبك</title>
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    body {
      background: linear-gradient(135deg, #4b0082, #7a2ff7);
      color: #fff;
      font-family: "Cairo", sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .thankyou-box {
      background: rgba(255,255,255,0.08);
      padding: 40px 50px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      max-width: 480px;
      animation: fadeIn 1s ease forwards;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.92);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .checkmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #22c55e, #4ade80);
      margin: 0 auto 20px;
      position: relative;
      animation: pop 0.6s ease forwards;
    }

    .checkmark::before {
      content: "✓";
      font-size: 40px;
      font-weight: bold;
      color: #fff;
      animation: appear 0.6s ease forwards;
    }

    @keyframes pop {
      0% { transform: scale(0); opacity: 0; }
      60% { transform: scale(1.2); opacity: 1; }
      100% { transform: scale(1); }
    }

    @keyframes appear {
      from { opacity: 0; transform: scale(0.6); }
      to { opacity: 1; transform: scale(1); }
    }

    h3 {
      font-size: 1.6rem;
      margin-bottom: 10px;
      color: #f0e8ff;
    }

    .muted {
      color: #d1c4f9;
      font-size: 0.95rem;
      margin-top: 8px;
    }

    p strong {
      color: #fff;
    }

    a.btn {
      display: inline-block;
      background: linear-gradient(135deg, #9b5cff, #b26fff);
      color: #120926;
      font-weight: 700;
      text-decoration: none;
      padding: 10px 18px;
      border-radius: 10px;
      transition: all 0.25s ease;
      margin-top: 20px;
    }

    a.btn:hover {
      transform: scale(1.05);
      filter: brightness(1.2);
    }
  </style>
</head>

<body>
  <div class="thankyou-box">

    <div class="checkmark"></div>

    <?php if(!$o): ?>
      <h3>❌ لم يتم العثور على الطلب.</h3>
      <p>
        <a class="btn" href="index.php">العودة للمتجر</a>
      </p>

    <?php else: ?>

      <h3>✅ تم استلام طلبك بنجاح</h3>

      <p>
        رقم الطلب:
        <strong>#<?= (int)$o['id'] ?></strong>
      </p>

      <p>
        الحالة الحالية:
        <strong><?= h($o['status']) ?></strong>
      </p>

      <p>
        المبلغ:
        <strong>
          <?= number_format((float)$o['amount'],2) ?>
          <?= h($o['currency']) ?>
        </strong>
      </p>

      <p>
        طريقة الاستلام:
        <strong>
          <?= h($o['delivery_method'] === 'pickup'
              ? 'استلام من نقطة'
              : 'توصيل') ?>
        </strong>
      </p>

      <?php if($o['delivery_method']==='pickup'): ?>
        <p>
          نقطة الاستلام:
          <?= h($o['pickup_location']) ?>
        </p>

      <?php else: ?>
        <p>
          العنوان:
          <?= h($o['address']) ?>
        </p>
      <?php endif; ?>

      <p class="muted">
        سيتم التواصل معك لتأكيد موعد التسليم/الاستلام.<br>
        الدفع عند الاستلام 💵
      </p>

      <a class="btn" href="index.php">
        🛍️ العودة للمتجر
      </a>

    <?php endif; ?>
  </div>
</body>
</html>
