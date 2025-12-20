<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$pdo = db();

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = trim($_POST['password'] ?? '');
  $confirm = trim($_POST['confirm'] ?? '');

  if (!$name || !$email || !$pass) {
    $err = 'الرجاء إدخال جميع الحقول.';

  } elseif ($pass !== $confirm) {
    $err = 'كلمتا المرور غير متطابقتين.';

  } else {

    $check = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $check->execute([':e' => $email]);

    if ($check->fetch()) {
      $err = 'هذا البريد مستخدم بالفعل.';
    } else {

      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash) VALUES (:n, :e, :h)'
      );

      $stmt->execute([
        ':n' => $name,
        ':e' => $email,
        ':h' => $hash
      ]);

      $msg = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>إنشاء حساب</title>
  <link rel="stylesheet" href="assets/style.css" />
  <script src="assets/app.js" defer></script>
</head>
<body>

  <main class="container" style="max-width:520px;margin-top:48px;">

    <div class="card" data-reveal>

      <h2>إنشاء حساب جديد</h2>

      <?php if ($err): ?>
        <p class="muted" style="color:#ef4444;">
          <?php echo htmlspecialchars($err); ?>
        </p>

      <?php elseif ($msg): ?>
        <p class="muted" style="color:#22d3ee;">
          <?php echo htmlspecialchars($msg); ?>
        </p>
      <?php endif; ?>

      <form method="post" class="form-grid">

        <label>الاسم الكامل
          <input name="name" type="text" required />
        </label>

        <label>البريد الإلكتروني
          <input name="email" type="email" required />
        </label>

        <label>كلمة المرور
          <input name="password" type="password" required />
        </label>

        <label>تأكيد كلمة المرور
          <input name="confirm" type="password" required />
        </label>

        <button class="btn primary" type="submit">إنشاء حساب</button>

        <p class="muted">
          لديك حساب بالفعل؟
          <a href="user_login.php">تسجيل الدخول</a>
        </p>
      </form>
    </div>
  </main>
</body>
</html>
