<?php
/* 
  تضمين ملف الإعدادات العامة للمشروع
  يحتوي على:
  - BASE_URL
  - بيانات المسؤول
  - إعدادات عامة أخرى
*/
require_once __DIR__ . '/config.php';

/* 
  التأكد من أن الجلسة مفعّلة
  لأننا نحتاج التحقق من تسجيل دخول المسؤول
*/
if (session_status() === PHP_SESSION_NONE) session_start();


/* 
  التحقق من أن المسؤول مسجّل دخول
  إذا لم يكن هناك جلسة admin أو لم يكن logged_in = true
  يتم منعه من الوصول إلى لوحة التحكم
*/
if (empty($_SESSION['admin']['logged_in'])) {

  /* 
    إعادة توجيه المستخدم إلى صفحة تسجيل دخول المسؤول
    لمنع الوصول غير المصرح به
  */
  header('Location: admin_login.php');

  /* 
    إيقاف تنفيذ السكربت بعد التوجيه
  */
  exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <!-- 
    تحديد ترميز الصفحة لدعم اللغة العربية
  -->
  <meta charset="UTF-8" />

  <!-- 
    جعل الصفحة متجاوبة مع جميع الشاشات
  -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- 
    عنوان الصفحة الذي يظهر في المتصفح
  -->
  <title>لوحة تحكم المسؤول - Modern MiniStore</title>

  <!-- 
    ربط ملف التنسيقات العامة للمشروع
  -->
  <link rel="stylesheet" href="assets/style.css" />

  <!-- 
    ربط ملف JavaScript الرئيسي
    defer يعني تحميله بعد اكتمال تحميل HTML
  -->
  <script src="assets/app.js" defer></script>

  <!-- 
    تنسيقات CSS خاصة بهذه الصفحة فقط
  -->
  <style>
    /* 
      تنسيق الصفحة العامة
      خلفية متدرجة بنفسجية
      توسيط المحتوى عموديًا
    */
    body {
      background: linear-gradient(145deg, #5b2abf, #7d4dff);
      color: #fff;
      font-family: "Cairo", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* 
      تنسيق الهيدر العلوي
      يحتوي على عنوان الصفحة وزر تسجيل الخروج
    */
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

    /* 
      تنسيق عنوان لوحة التحكم
    */
    header h1 {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.5rem;
    }

    /* 
      تنسيق صورة الشعار داخل العنوان
    */
    header img {
      width: 36px;
      height: 36px;
      filter: brightness(1.3);
    }

    /* 
      المحتوى الرئيسي للصفحة
      يحتوي على أزرار الإدارة
    */
    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 2rem;
    }

    /* 
      حاوية الأزرار
    */
    .btns {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
    }

    /* 
      تنسيق الأزرار الرئيسية
      مثل إدارة المنتجات وإدارة الطلبات
    */
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

    /* 
      تأثير عند تمرير الماوس على الأزرار
    */
    .btn:hover {
      background: rgba(255,255,255,0.3);
      transform: scale(1.05);
    }

    /* 
      زر تسجيل الخروج
    */
    .logout-btn {
      background: rgba(255,255,255,0.15);
      border: none;
      color: #fff;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    /* 
      تأثير تمرير الماوس على زر الخروج
    */
    .logout-btn:hover {
      background: rgba(255,255,255,0.3);
    }
  </style>
</head>
<body>

  <!-- 
    رأس الصفحة (الهيدر)
    يحتوي على اسم المتجر وزر تسجيل الخروج
  -->
  <header>
    <h1>
      <!-- شعار المتجر -->
      <img src="assets/logo.png" alt="Logo" />

      <!-- عنوان لوحة التحكم -->
      <span>🛍️ Modern Store — مرحبًا بك يا أدمن!</span>
    </h1>

    <!-- 
      رابط تسجيل الخروج من لوحة التحكم
      يقوم بإنهاء جلسة المسؤول
    -->
    <a href="admin_logout.php" class="logout-btn">تسجيل الخروج 🔓</a>
  </header>

  <!-- 
    المحتوى الرئيسي للوحة التحكم
    يحتوي على أزرار التنقل
  -->
  <main>
    <div class="btns">

      <!-- زر الانتقال إلى صفحة إدارة المنتجات -->
      <a href="admin_products.php" class="btn">
        إدارة المنتجات 🛒
      </a>

      <!-- زر الانتقال إلى صفحة إدارة الطلبات -->
      <a href="admin_orders.php" class="btn">
        إدارة الطلبات 📦
      </a>

    </div>
  </main>

</body>
</html>
