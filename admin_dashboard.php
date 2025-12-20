<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/auth.php';
require_admin();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>لوحة تحكم المسؤول - Modern MiniStore</title>
  <link rel="stylesheet" href="assets/style.css" />
  <script src="assets/app.js" defer></script>

  <style>
    body {
      background: linear-gradient(145deg, #5b2abf, #7d4dff);
      color: #fff;
      font-family: "Cairo", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    header {
      width: 100%;
      background: rgba(255,255,255,0.08);
      backdrop-filter: blur(6px);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    header h1 {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.5rem;
    }

    header img {
      width: 36px;
      height: 36px;
      filter: brightness(1.3);
    }

    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 2rem;
    }

    .btns {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
    }

    .btn {
      background: rgba(255,255,255,0.15);
      padding: 1rem 2rem;
      border-radius: 10px;
      font-size: 1.2rem;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .btn:hover {
      background: rgba(255,255,255,0.3);
      transform: scale(1.05);
    }

    .logout-btn {
      background: rgba(255,255,255,0.15);
      border: none;
      color: #fff;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .logout-btn:hover {
      background: rgba(255,255,255,0.3);
    }

  </style>
</head>
<body>

  <header>
    <h1>
      <img src="assets/logo.png" alt="Logo" />
      <span>🛍️ Modern Store — مرحبًا بك يا أدمن!</span>
    </h1>

    <a href="admin_logout.php" class="logout-btn">تسجيل الخروج 🔓</a>
  </header>

  <main>
    <div class="btns">
      <a href="admin_products.php" class="btn">
        إدارة المنتجات 🛒
      </a>

      <a href="admin_orders.php" class="btn">
        إدارة الطلبات 📦
      </a>
    </div>
  </main>

</body>
</html>
