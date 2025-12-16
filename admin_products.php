<?php
// استدعاء ملف الإعدادات العامة (قاعدة البيانات، BASE_URL، إلخ)
require_once __DIR__ . '/config.php';

// بدء الجلسة إذا لم تكن مبدوءة مسبقًا
if (session_status() === PHP_SESSION_NONE) session_start();

// استدعاء ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/db.php';

// ===============================
// التحقق من تسجيل دخول المسؤول
// ===============================

// إذا لم يكن المسؤول مسجّل دخول
if (empty($_SESSION['admin']['logged_in'])) {

  // تحويله إلى صفحة تسجيل دخول المسؤول
  header('Location: admin_login.php');

  // إيقاف تنفيذ أي كود بعد التحويل
  exit;
}

// إنشاء اتصال PDO بقاعدة البيانات
$pdo = db();

// متغير لرسائل النجاح
$msg = '';

// متغير لرسائل الخطأ (غير مستخدم حاليًا لكنه جاهز)
$err = '';


// =======================================
// إضافة منتج جديد
// =======================================

// التحقق إذا تم إرسال الفورم الخاص بإضافة منتج
if (isset($_POST['add_product'])) {

  // قراءة اسم المنتج مع إزالة الفراغات
  $name = trim($_POST['name']);

  // قراءة السعر وتحويله إلى رقم عشري
  $price = (float)$_POST['price'];

  // قراءة الكمية وتحويلها إلى رقم صحيح
  $stock = (int)$_POST['stock'];

  // متغير لحفظ مسار الصورة
  $imagePath = '';

  // ===============================
  // رفع صورة المنتج (إن وُجدت)
  // ===============================

  // التحقق إذا تم اختيار ملف صورة
  if (!empty($_FILES['image']['name'])) {

    // تحديد مجلد الرفع
    $uploadDir = __DIR__ . '/uploads/';

    // إذا لم يكن المجلد موجودًا يتم إنشاؤه
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // إنشاء اسم فريد للصورة باستخدام الوقت
    $filename = time() . '_' . basename($_FILES['image']['name']);

    // المسار النهائي للصورة
    $target = $uploadDir . $filename;

    // نقل الصورة من المجلد المؤقت إلى المجلد النهائي
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {

      // حفظ المسار في قاعدة البيانات
      $imagePath = 'uploads/' . $filename;
    }
  }

  // ===============================
  // إدخال المنتج إلى قاعدة البيانات
  // ===============================

  // تجهيز استعلام الإدخال باستخدام Prepared Statement
  $stmt = $pdo->prepare(
    "INSERT INTO products (name, price, stock, image_url)
     VALUES (:n, :p, :s, :i)"
  );

  // تنفيذ الاستعلام مع القيم
  $stmt->execute([
    ':n' => $name,
    ':p' => $price,
    ':s' => $stock,
    ':i' => $imagePath
  ]);

  // رسالة نجاح
  $msg = '✅ تمت إضافة المنتج بنجاح';
}


// =======================================
// تعديل منتج موجود
// =======================================

// التحقق إذا تم إرسال فورم التعديل
if (isset($_POST['edit_product'])) {

  // قراءة رقم المنتج
  $id = (int)$_POST['id'];

  // قراءة البيانات الجديدة
  $name = trim($_POST['name']);
  $price = (float)$_POST['price'];
  $stock = (int)$_POST['stock'];

  // الاحتفاظ بالصورة القديمة افتراضيًا
  $imagePath = $_POST['old_image'] ?? '';

  // ===============================
  // رفع صورة جديدة (إن وُجدت)
  // ===============================

  if (!empty($_FILES['image']['name'])) {

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = time() . '_' . basename($_FILES['image']['name']);
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $imagePath = 'uploads/' . $filename;
    }
  }

  // تنفيذ تحديث المنتج
  $stmt = $pdo->prepare(
    "UPDATE products
     SET name=:n, price=:p, stock=:s, image_url=:i
     WHERE id=:id"
  );

  $stmt->execute([
    ':n'  => $name,
    ':p'  => $price,
    ':s'  => $stock,
    ':i'  => $imagePath,
    ':id' => $id
  ]);

  // رسالة نجاح
  $msg = '✏️ تم تعديل المنتج بنجاح';
}


// =======================================
// حذف منتج
// =======================================

// التحقق إذا تم طلب حذف منتج عبر GET
if (isset($_GET['delete'])) {

  // قراءة رقم المنتج
  $id = (int)$_GET['delete'];

  // تنفيذ استعلام الحذف
  $stmt = $pdo->prepare("DELETE FROM products WHERE id=:id");
  $stmt->execute([':id' => $id]);

  // رسالة نجاح
  $msg = '🗑️ تم حذف المنتج بنجاح';
}


// =======================================
// جلب جميع المنتجات لعرضها
// =======================================

// استعلام لجلب كل المنتجات
$products = $pdo
  ->query("SELECT * FROM products ORDER BY id DESC")
  ->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<!-- تعريف نوع المستند: HTML5 -->
<html lang="ar" dir="rtl">
<!-- تحديد لغة الصفحة عربية واتجاه النص من اليمين لليسار -->

<head>
  <!-- تحديد ترميز الأحرف لدعم العربية -->
  <meta charset="UTF-8">

  <!-- جعل الصفحة متجاوبة مع الشاشات المختلفة (موبايل / لابتوب) -->
  <meta name="viewport" content="width=device-width,initial-scale=1.0">

  <!-- عنوان الصفحة الذي يظهر في التبويب -->
  <title>إدارة المنتجات</title>

  <!-- ربط ملف CSS العام للمشروع -->
  <link rel="stylesheet" href="assets/style.css">

  <!-- CSS خاص بهذه الصفحة فقط -->
  <style>

    /* تنسيق جسم الصفحة بالكامل */
    body {
      /* خلفية متدرجة بنفسجية */
      background: linear-gradient(145deg, #5b2abf, #7d4dff);

      /* لون النص أبيض */
      color: #fff;

      /* استخدام خط Cairo العربي */
      font-family: "Cairo", sans-serif;

      /* مسافة داخلية حول الصفحة */
      padding: 20px;
    }

    /* تنسيق عنوان الصفحة الرئيسي */
    h1 {
      text-align: center;      /* توسيط النص */
      margin-bottom: 20px;     /* مسافة أسفل العنوان */
    }

    /* تنسيق جدول المنتجات */
    table {
      width: 100%;             /* عرض الجدول كامل الصفحة */
      border-collapse: collapse; /* دمج حدود الخلايا */
      background: rgba(255,255,255,0.1); /* خلفية شفافة */
    }

    /* تنسيق خلايا الجدول */
    th, td {
      border: 1px solid rgba(255,255,255,0.2); /* حدود خفيفة */
      padding: 10px;        /* مسافة داخل الخلية */
      text-align: center;   /* توسيط المحتوى */
    }

    /* تنسيق صور المنتجات */
    img {
      width: 80px;          /* عرض ثابت للصورة */
      border-radius: 8px;   /* حواف دائرية */
    }

    /* تنسيق الحقول والأزرار */
    input, button {
      padding: 8px 10px;    /* مسافة داخلية */
      border-radius: 5px;   /* حواف دائرية */
      border: none;         /* بدون إطار */
    }

    /* تحديد عرض حقول الإدخال */
    input[type="text"],
    input[type="number"],
    input[type="file"] {
      width: 160px;
    }

    /* تنسيق الأزرار الافتراضية */
    button {
      background: #a78bfa;  /* لون بنفسجي */
      color: #fff;
      cursor: pointer;     /* تغيير شكل المؤشر */
    }

    /* تأثير عند المرور على الزر */
    button:hover {
      background: #c4b5fd;
    }

    /* رسالة النجاح */
    .msg {
      margin: 10px 0;
      text-align: center;
      color: #bbf7d0;      /* لون أخضر */
      font-weight: bold;
    }

    /* روابط الإجراءات */
    .actions a {
      color: #fff;
      text-decoration: none;
      margin: 0 5px;
      padding: 6px 10px;
      border-radius: 5px;
    }

    /* زر التعديل */
    .edit {
      background: #818cf8;
    }

    /* زر الحذف */
    .delete {
      background: #ef4444;
    }

    /* زر الرجوع للوحة التحكم */
    .back {
      display: inline-block;
      margin-bottom: 20px;
      background: #4ade80;
      padding: 8px 14px;
      border-radius: 8px;
      color: #000;
      font-weight: bold;
    }

    /* صف إضافة منتج جديد */
    .add-row {
      display: flex;                 /* ترتيب العناصر أفقياً */
      justify-content: center;       /* توسيط العناصر */
      gap: 10px;                     /* مسافة بين العناصر */
      align-items: center;
      margin-bottom: 25px;
    }

    /* لون نص اختيار الملف */
    .add-row input[type="file"] {
      color: #fff;
    }

    /* زر إضافة منتج */
    .add-row button {
      background: #22c55e !important;
      color: white !important;
      font-weight: bold;
      border: 2px solid #fff;
      padding: 9px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s ease;
      font-size: 15px;
    }

    /* تأثير عند المرور على زر الإضافة */
    .add-row button:hover {
      background: #16a34a !important;
      transform: scale(1.08);
    }
  </style>
</head>

<body>

  <!-- زر الرجوع إلى لوحة تحكم المسؤول -->
  <a class="back" href="admin_dashboard.php">⬅️ العودة للوحة التحكم</a>

  <!-- عنوان الصفحة -->
  <h1>🛍️ إدارة المنتجات</h1>

  <!-- عرض رسالة نجاح إن وجدت -->
  <?php if($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- نموذج إضافة منتج جديد -->
  <form method="post" enctype="multipart/form-data" class="add-row">

    <!-- اسم المنتج -->
    <input type="text" name="name" placeholder="اسم المنتج" required>

    <!-- سعر المنتج -->
    <input type="number" step="0.01" name="price" placeholder="السعر" required>

    <!-- كمية المنتج -->
    <input type="number" name="stock" placeholder="الكمية" required>

    <!-- صورة المنتج -->
    <input type="file" name="image" accept="image/*" required>

    <!-- زر إرسال النموذج -->
    <button type="submit" name="add_product">➕ إضافة منتج</button>
  </form>

  <!-- جدول عرض المنتجات -->
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>الاسم</th>
        <th>السعر</th>
        <th>الكمية</th>
        <th>الصورة</th>
        <th>إجراءات</th>
      </tr>
    </thead>

    <tbody>
      <!-- تكرار كل منتج -->
      <?php foreach($products as $p): ?>
      <tr>

        <!-- رقم المنتج -->
        <td><?= $p['id'] ?></td>

        <!-- اسم المنتج مع حماية XSS -->
        <td><?= htmlspecialchars($p['name']) ?></td>

        <!-- السعر -->
        <td><?= $p['price'] ?></td>

        <!-- الكمية -->
        <td><?= $p['stock'] ?></td>

        <!-- صورة المنتج إن وجدت -->
        <td>
          <?php if($p['image_url']): ?>
            <img src="<?= htmlspecialchars($p['image_url']) ?>">
          <?php endif; ?>
        </td>

        <!-- إجراءات التعديل والحذف -->
        <td class="actions">

          <!-- نموذج تعديل المنتج -->
          <form method="post" enctype="multipart/form-data" style="display:inline-block;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="old_image" value="<?= htmlspecialchars($p['image_url']) ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required>
            <input type="number" step="0.01" name="price" value="<?= $p['price'] ?>" required>
            <input type="number" name="stock" value="<?= $p['stock'] ?>" required>
            <input type="file" name="image" accept="image/*">
            <button type="submit" name="edit_product" class="edit">✏️ تعديل</button>
          </form>

          <!-- رابط حذف المنتج -->
          <a href="?delete=<?= $p['id'] ?>" class="delete"
             onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟');">
            🗑️ حذف
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</body>
</html>
