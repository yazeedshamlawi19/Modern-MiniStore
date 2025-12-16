<?php
/* تضمين ملف الإعدادات العامة (قاعدة البيانات، المسارات، الثوابت) */
require_once __DIR__ . '/config.php';

/* بدء الجلسة إذا لم تكن قد بدأت بعد */
if (session_status() === PHP_SESSION_NONE) session_start();

/* تضمين ملف الاتصال بقاعدة البيانات */
require_once __DIR__ . '/db.php';

/* تضمين ملف التحقق من الصلاحيات (تسجيل الدخول / مسؤول) */
require_once __DIR__ . '/auth.php';

/* التأكد أن المستخدم الحالي مسؤول، وإلا يتم تحويله لصفحة تسجيل دخول المسؤول */
require_admin(); 

/* إنشاء اتصال بقاعدة البيانات باستخدام PDO */
$pdo = db();


/* 
   التحقق إذا كان الطلب من نوع POST 
   وأن البيانات المطلوبة (رقم الطلب + الحالة) موجودة
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {

    /* تحويل رقم الطلب إلى عدد صحيح للحماية */
    $order_id = (int)$_POST['order_id'];

    /* تنظيف قيمة الحالة من الفراغات */
    $status = trim($_POST['status']);

    /* الحالات المسموح بها فقط داخل النظام */
    $allowed = ['قيد التأكيد', 'قيد التنفيذ', 'مكتمل', 'ملغى'];

    /* التحقق أن الحالة المختارة موجودة ضمن الحالات المسموحة */
    if (in_array($status, $allowed, true)) {

        /* تجهيز استعلام تحديث حالة الطلب */
        $stmt = $pdo->prepare("UPDATE orders SET status = :s WHERE id = :id");

        /* تنفيذ الاستعلام مع تمرير القيم */
        $stmt->execute([
            ':s' => $status,
            ':id' => $order_id
        ]);

        /* تخزين رسالة نجاح في الجلسة */
        $_SESSION['flash'] = "✅ تم تحديث حالة الطلب رقم #$order_id";

        /* إعادة تحميل الصفحة لتحديث البيانات */
        header('Location: admin_orders.php');
        exit;
    }
}


/* 
   جلب جميع الطلبات من قاعدة البيانات
   مع ربط المستخدم في حال كان الطلب مرتبط بحساب
*/
$stmt = $pdo->query("
    SELECT 
        o.id, 
        o.customer_name, 
        o.customer_phone, 
        o.amount, 
        o.status, 
        o.created_at, 
        o.delivery_method, 
        o.address, 
        o.pickup_location, 
        u.name AS user_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC
");

/* جلب النتائج كمصفوفة associative */
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>

    <!-- تحديد الترميز لدعم اللغة العربية -->
    <meta charset="UTF-8">

    <!-- عنوان الصفحة -->
    <title>إدارة الطلبات</title>

    <!-- ربط ملف CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- تنسيقات خاصة بهذه الصفحة -->
    <style>

        /* تنسيق جسم الصفحة */
        body {
            background: linear-gradient(145deg, #4b0082, #7a2ff7);
            color: #fff;
            font-family: "Cairo", sans-serif;
        }

        /* تنسيق عنوان الصفحة */
        h1 {
            text-align:center;
            margin-top: 20px;
        }

        /* تنسيق جدول الطلبات */
        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        /* تنسيق خلايا الجدول */
        th, td {
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 10px;
            text-align: center;
        }

        /* خلفية رؤوس الأعمدة */
        th {
            background: rgba(255,255,255,0.15);
        }

        /* تأثير عند المرور على الصف */
        tr:hover { background: rgba(255,255,255,0.08); }

        /* تنسيق القوائم المنسدلة والأزرار */
        select, button {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
        }

        /* تنسيق القائمة المنسدلة */
        select { background: #fff; color: #000; }

        /* تنسيق زر التحديث */
        button {
            background: #7c3aed;
            color: #fff;
            cursor: pointer;
        }

        /* تأثير عند المرور على الزر */
        button:hover { background: #9f67ff; }

        /* تنسيق رسالة الفلاش */
        .flash {
            text-align:center;
            margin: 10px;
            background: rgba(0,0,0,0.4);
            display: inline-block;
            padding: 10px 18px;
            border-radius: 10px;
        }

        /* زر الرجوع */
        a.back {
            color: #fff;
            text-decoration: none;
            background: #22c55e;
            padding: 8px 14px;
            border-radius: 8px;
            display:inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<!-- عنوان الصفحة -->
<h1>📦 إدارة الطلبات</h1>

<!-- زر العودة للوحة التحكم -->
<div style="text-align:center;">
    <a href="admin_dashboard.php" class="back">⬅ العودة للوحة التحكم</a>
</div>

<!-- عرض رسالة الفلاش إذا وجدت -->
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- في حال عدم وجود طلبات -->
<?php if (empty($orders)): ?>
    <p style="text-align:center;">لا توجد طلبات حالياً.</p>

<!-- في حال وجود طلبات -->
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>الهاتف</th>
                <th>المستخدم</th>
                <th>الإجمالي</th>
                <th>طريقة الاستلام</th>
                <th>الحالة</th>
                <th>تغيير الحالة</th>
                <th>تاريخ الإنشاء</th>
            </tr>
        </thead>
        <tbody>

        <!-- تكرار عرض كل طلب -->
        <?php foreach ($orders as $o): ?>
            <tr>

                <!-- رقم الطلب -->
                <td><?= $o['id'] ?></td>

                <!-- اسم العميل -->
                <td><?= htmlspecialchars($o['customer_name']) ?></td>

                <!-- رقم الهاتف -->
                <td><?= htmlspecialchars($o['customer_phone']) ?></td>

                <!-- اسم المستخدم إن وجد -->
                <td><?= htmlspecialchars($o['user_name'] ?? '-') ?></td>

                <!-- المبلغ الإجمالي -->
                <td><?= number_format($o['amount'],2) ?> USD</td>

                <!-- طريقة الاستلام -->
                <td>
                    <?= $o['delivery_method'] === 'pickup'
                        ? 'استلام من نقطة (' . htmlspecialchars($o['pickup_location'] ?? '-') . ')'
                        : 'توصيل (' . htmlspecialchars($o['address'] ?? '-') . ')' ?>
                </td>

                <!-- الحالة الحالية -->
                <td><strong><?= htmlspecialchars($o['status']) ?></strong></td>

                <!-- نموذج تغيير الحالة -->
                <td>
                    <form method="post" style="display:flex; gap:6px; justify-content:center;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">

                        <select name="status" required>
                            <option value="قيد التأكيد" <?= $o['status']==='قيد التأكيد'?'selected':'' ?>>قيد التأكيد</option>
                            <option value="قيد التنفيذ" <?= $o['status']==='قيد التنفيذ'?'selected':'' ?>>قيد التنفيذ</option>
                            <option value="مكتمل" <?= $o['status']==='مكتمل'?'selected':'' ?>>مكتمل</option>
                            <option value="ملغى" <?= $o['status']==='ملغى'?'selected':'' ?>>ملغى</option>
                        </select>

                        <button type="submit">تحديث</button>
                    </form>
                </td>

                <!-- تاريخ إنشاء الطلب -->
                <td><?= htmlspecialchars($o['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
