<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>🛍️ Modern MiniStore</title>
  <link rel="stylesheet" href="assets/style.css" />

  <script>
    window.IS_ADMIN = <?php echo is_admin() ? 'true' : 'false'; ?>;
    window.IS_LOGGED = <?php echo !empty($_SESSION['user']['id']) ? 'true' : 'false'; ?>;
  </script>

  <script src="assets/app.js" defer></script>

  <style>
    body {
      background: linear-gradient(135deg, #4b0082, #7a2ff7);
      color: #fff;
      font-family: "Cairo", sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }

    header.container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 30px;
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(6px);
      border-bottom: 1px solid rgba(255,255,255,0.15);
    }

    header h1 {
      font-size: 1.6rem;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin: 0 8px;
      padding: 6px 12px;
      border-radius: 8px;
      background: rgba(255,255,255,0.1);
      transition: background 0.3s;
    }

    nav a:hover {
      background: rgba(255,255,255,0.25);
    }

    main.container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      padding: 40px;
    }

    .card {
      background: rgba(255,255,255,0.1);
      border-radius: 16px;
      text-align: center;
      padding: 16px;
      transition: transform 0.3s ease, background 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      background: rgba(255,255,255,0.2);
    }

    .card img {
      width: 100%;
      border-radius: 12px;
      margin-bottom: 10px;
      object-fit: cover;
      height: 180px;
    }

    .btn {
      background: #fff;
      color: #4b0082;
      border: none;
      border-radius: 10px;
      padding: 8px 14px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }

    .btn:hover {
      background: #e2d4ff;
      transform: scale(1.05);
    }

    footer {
      text-align: center;
      padding: 20px;
      color: #ccc;
      background: rgba(255,255,255,0.08);
      border-top: 1px solid rgba(255,255,255,0.15);
    }
  </style>
</head>

<body>
  <header class="container">
    <h1>Modern MiniStore 🛍️</h1>

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

  <main class="container" id="products-list">
    <p style="grid-column:1/-1;text-align:center;color:#eee;">
      جارٍ تحميل المنتجات...
    </p>
  </main>

  <footer>
    <p>© 2025 Modern MiniStore. جميع الحقوق محفوظة.</p>
  </footer>

  <script>
    const loggedIn = <?php echo is_logged_in() ? 'true' : 'false'; ?>;

    async function loadProducts() {
      const listEl = document.getElementById('products-list');

      try {
        const res = await fetch('api/products.php');
        if (!res.ok) throw new Error('فشل تحميل المنتجات');

        const products = await res.json();

        if (!products.length) {
          listEl.innerHTML = `
            <p style="grid-column:1/-1;text-align:center;">
              🚫 لا توجد منتجات حالياً.
            </p>
          `;
          return;
        }

        listEl.innerHTML = products.map(p => `
          <div class="card">
            <img src="${p.image_url || 'assets/no_image.png'}" alt="${p.name}">
            <h3>${p.name}</h3>
            <p>USD ${p.price.toFixed(2)}</p>
            ${loggedIn
              ? `<a class="btn" href="product_details.php?id=<?= (int)$p['id'] ?>">عرض التفاصيل</a>`
              : `<a class="btn" href="user_login.php">سجّل الدخول للشراء</a>`
            }
          </div>
        `).join('');

      } catch (e) {
        listEl.innerHTML = `
          <p style="grid-column:1/-1;text-align:center;">
            ⚠️ تعذر تحميل المنتجات.
          </p>
        `;
      }
    }

    loadProducts();
  </script>
</body>
</html>
