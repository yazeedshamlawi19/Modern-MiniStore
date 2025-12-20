<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';

$pdo = db();

$err = '';

$next = $_GET['next'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = trim($_POST['email'] ?? '');

  $pass = trim($_POST['password'] ?? '');

 $stmt = $pdo->prepare(
  'SELECT id, name, email, password_hash, role FROM users WHERE email = :e LIMIT 1'
);

  $stmt->execute([':e' => $email]);

  $u = $stmt->fetch();

  if (!$u || !password_verify($pass, $u['password_hash'])) {

    $err = 'بيانات الدخول غير صحيحة.';

  } else {

   $_SESSION['user'] = [
  'id'    => (int)$u['id'],
  'name'  => $u['name'],
  'email' => $u['email'],
  'role'  => $u['role']
];

if ($u['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}


    header('Location: ' . ($next ?: 'index.php'));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تسجيل الدخول</title>
  <link rel="stylesheet" href="assets/style.css" />
  <script src="assets/app.js" defer></script>
</head>
<body>

  <main class="container" style="max-width:520px;margin-top:48px;">

    <div class="card" data-reveal>

      <h2>تسجيل الدخول</h2>



      <?php if ($err): ?>
        <p class="muted" style="color:#ef4444;">
          <?php echo htmlspecialchars($err); ?>
        </p>
      <?php endif; ?>

      <form method="post" class="form-grid">

        <label>البريد الإلكتروني
          <input name="email" type="email" required />
        </label>

        <label>كلمة المرور
          <input name="password" type="password" required />
        </label>

        <button class="btn primary" type="submit">دخول</button>
        
        <p class="muted">
          ليس لديك حساب؟ <a href="user_register.php">إنشاء حساب</a>
        </p>

      </form>
    </div>
  </main>
</body>
</html>
