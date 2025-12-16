<?php
// تحميل ملف الإعدادات العامة (قاعدة البيانات، BASE_URL، المنطقة الزمنية...)
require_once __DIR__ . '/config.php';

// التأكد من أن الجلسة (Session) بدأت حتى نتمكن من استخدام $_SESSION
if (session_status() === PHP_SESSION_NONE) session_start();

// تحميل ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/db.php';


// إنشاء اتصال بقاعدة البيانات باستخدام الدالة db()
$pdo = db();

// متغير لتخزين رسالة الخطأ في حال فشل تسجيل الدخول
$err = '';

// جلب الصفحة التي كان المستخدم يريد الوصول إليها قبل تسجيل الدخول (إن وجدت)
$next = $_GET['next'] ?? '';


// التحقق: هل الطلب من النوع POST؟ (أي تم إرسال الفورم)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // جلب البريد الإلكتروني من الفورم مع إزالة المسافات الزائدة
  $email = trim($_POST['email'] ?? '');

  // جلب كلمة المرور من الفورم مع إزالة المسافات الزائدة
  $pass = trim($_POST['password'] ?? '');

  // تجهيز استعلام SQL لجلب بيانات المستخدم بناءً على البريد الإلكتروني
  $stmt = $pdo->prepare(
    'SELECT id, name, email, password_hash FROM users WHERE email = :e LIMIT 1'
  );

  // تنفيذ الاستعلام وتمرير البريد الإلكتروني بشكل آمن
  $stmt->execute([':e' => $email]);

  // جلب بيانات المستخدم (إن وُجد) كمصفوفة
  $u = $stmt->fetch();

  // التحقق: إذا لم يتم العثور على مستخدم أو كانت كلمة المرور غير صحيحة
  if (!$u || !password_verify($pass, $u['password_hash'])) {

    // تخزين رسالة خطأ لعرضها للمستخدم
    $err = 'بيانات الدخول غير صحيحة.';

  } else {

    // إذا كانت البيانات صحيحة، يتم تخزين بيانات المستخدم في الجلسة
    $_SESSION['user'] = [
      // تخزين رقم المستخدم
      'id' => (int)$u['id'],

      // تخزين اسم المستخدم
      'name' => $u['name'],

      // تخزين البريد الإلكتروني
      'email' => $u['email']
    ];

    // تحويل المستخدم للصفحة المطلوبة سابقًا أو الصفحة الرئيسية
    header('Location: ' . ($next ?: 'index.php'));

    // إيقاف تنفيذ السكربت بعد التحويل
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <!-- تحديد ترميز الصفحة لدعم اللغة العربية -->
  <meta charset="UTF-8" />

  <!-- جعل الصفحة متجاوبة مع جميع أحجام الشاشات -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- عنوان الصفحة -->
  <title>تسجيل الدخول</title>

  <!-- ربط ملف التنسيقات الرئيسي -->
  <link rel="stylesheet" href="assets/style.css" />

  <!-- ربط ملف JavaScript المسؤول عن الواجهات والتأثيرات -->
  <script src="assets/app.js" defer></script>
</head>
<body>

  <!-- الحاوية الرئيسية مع تحديد عرض مناسب للواجهة -->
  <main class="container" style="max-width:520px;margin-top:48px;">

    <!-- كرت (Card) لواجهة تسجيل الدخول مع تأثير الظهور -->
    <div class="card" data-reveal>

      <!-- عنوان الفورم -->
      <h2>تسجيل الدخول</h2>

      <!-- التحقق: إذا كان هناك رسالة خطأ، يتم عرضها -->
      <?php if ($err): ?>
        <p class="muted" style="color:#ef4444;">
          <?php echo htmlspecialchars($err); ?>
        </p>
      <?php endif; ?>

      <!-- نموذج تسجيل الدخول -->
      <form method="post" class="form-grid">

        <!-- حقل البريد الإلكتروني -->
        <label>البريد الإلكتروني
          <input name="email" type="email" required />
        </label>

        <!-- حقل كلمة المرور -->
        <label>كلمة المرور
          <input name="password" type="password" required />
        </label>

        <!-- زر إرسال الفورم -->
        <button class="btn primary" type="submit">دخول</button>

        <!-- رابط لإنشاء حساب جديد -->
        <p class="muted">
          ليس لديك حساب؟ <a href="user_register.php">إنشاء حساب</a>
        </p>

      </form>
    </div>
  </main>
</body>
</html>
