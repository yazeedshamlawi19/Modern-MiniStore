<?php
// تحميل ملف الإعدادات العامة للمشروع
// يحتوي على BASE_URL وإعدادات أخرى مشتركة
require_once __DIR__ . '/config.php';

// التأكد من أن الجلسة مفعّلة
// إذا لم تكن الجلسة قد بدأت، نقوم بتشغيلها
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تحميل ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/db.php';

// إنشاء اتصال بقاعدة البيانات باستخدام الدالة db()
$pdo = db();

// متغير لتخزين رسالة الخطأ في حال وجودها
$err = '';

// متغير لتخزين رسالة النجاح
$msg = '';

// التحقق إذا كان الطلب من نوع POST
// أي أن المستخدم قام بإرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // قراءة اسم المستخدم من النموذج مع إزالة الفراغات
  $name = trim($_POST['name'] ?? '');

  // قراءة البريد الإلكتروني من النموذج
  $email = trim($_POST['email'] ?? '');

  // قراءة كلمة المرور
  $pass = trim($_POST['password'] ?? '');

  // قراءة تأكيد كلمة المرور
  $confirm = trim($_POST['confirm'] ?? '');

  // التحقق من أن جميع الحقول المطلوبة مُعبّأة
  if (!$name || !$email || !$pass) {
    // في حال وجود حقل فارغ
    $err = 'الرجاء إدخال جميع الحقول.';

  // التحقق من تطابق كلمة المرور مع التأكيد
  } elseif ($pass !== $confirm) {
    // في حال عدم التطابق
    $err = 'كلمتا المرور غير متطابقتين.';

  } else {

    // التحقق إذا كان البريد الإلكتروني مستخدمًا مسبقًا
    $check = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');

    // تنفيذ الاستعلام مع تمرير البريد الإلكتروني
    $check->execute([':e' => $email]);

    // إذا تم العثور على مستخدم بنفس البريد
    if ($check->fetch()) {
      $err = 'هذا البريد مستخدم بالفعل.';
    } else {

      // تشفير كلمة المرور باستخدام password_hash
      // PASSWORD_DEFAULT يستخدم خوارزمية آمنة افتراضيًا
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      // تجهيز استعلام إدخال مستخدم جديد
      $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash) VALUES (:n, :e, :h)'
      );

      // تنفيذ الاستعلام مع تمرير القيم
      $stmt->execute([
        ':n' => $name,
        ':e' => $email,
        ':h' => $hash
      ]);

      // رسالة نجاح عند إنشاء الحساب
      $msg = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <!-- جعل الصفحة متوافقة مع الجوال -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>إنشاء حساب</title>

  <!-- تحميل ملف التنسيقات العامة -->
  <link rel="stylesheet" href="assets/style.css" />

  <!-- تحميل ملف JavaScript للتأثيرات -->
  <script src="assets/app.js" defer></script>
</head>
<body>

  <!-- الحاوية الرئيسية مع تحديد عرض مناسب -->
  <main class="container" style="max-width:520px;margin-top:48px;">

    <!-- كرت يحتوي على نموذج التسجيل -->
    <div class="card" data-reveal>

      <h2>إنشاء حساب جديد</h2>

      <!-- عرض رسالة الخطأ إن وُجدت -->
      <?php if ($err): ?>
        <p class="muted" style="color:#ef4444;">
          <?php echo htmlspecialchars($err); ?>
        </p>

      <!-- عرض رسالة النجاح إن وُجدت -->
      <?php elseif ($msg): ?>
        <p class="muted" style="color:#22d3ee;">
          <?php echo htmlspecialchars($msg); ?>
        </p>
      <?php endif; ?>

      <!-- نموذج التسجيل -->
      <form method="post" class="form-grid">

        <!-- حقل الاسم الكامل -->
        <label>الاسم الكامل
          <input name="name" type="text" required />
        </label>

        <!-- حقل البريد الإلكتروني -->
        <label>البريد الإلكتروني
          <input name="email" type="email" required />
        </label>

        <!-- حقل كلمة المرور -->
        <label>كلمة المرور
          <input name="password" type="password" required />
        </label>

        <!-- حقل تأكيد كلمة المرور -->
        <label>تأكيد كلمة المرور
          <input name="confirm" type="password" required />
        </label>

        <!-- زر إرسال النموذج -->
        <button class="btn primary" type="submit">إنشاء حساب</button>

        <!-- رابط الانتقال لتسجيل الدخول -->
        <p class="muted">
          لديك حساب بالفعل؟
          <a href="user_login.php">تسجيل الدخول</a>
        </p>
      </form>
    </div>
  </main>
</body>
</html>
