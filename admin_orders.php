<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_admin(); 

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {

    $order_id = (int)$_POST['order_id'];
    $status = trim($_POST['status']);

    $allowed = ['قيد التأكيد', 'قيد التنفيذ', 'مكتمل', 'ملغى'];

    if (in_array($status, $allowed, true)) {

        $stmt = $pdo->prepare("UPDATE orders SET status = :s WHERE id = :id");
        $stmt->execute([
            ':s' => $status,
            ':id' => $order_id
        ]);

        $_SESSION['flash'] = "✅ تم تحديث حالة الطلب رقم #$order_id";

        header('Location: admin_orders.php');
        exit;
    }
}

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

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>

    <meta charset="UTF-8">
    <title>إدارة الطلبات</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: linear-gradient(145deg, #4b0082, #7a2ff7);
            color: #fff;
            font-family: "Cairo", sans-serif;
        }

        h1 {
            text-align:center;
            margin-top: 20px;
        }

        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 10px;
            text-align: center;
        }

        th {
            background: rgba(255,255,255,0.15);
        }

        tr:hover { background: rgba(255,255,255,0.08); }

        select, button {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
        }

        select { background: #fff; color: #000; }

        button {
            background: #7c3aed;
            color: #fff;
            cursor: pointer;
        }

        button:hover { background: #9f67ff; }

        .flash {
            text-align:center;
            margin: 10px;
            background: rgba(0,0,0,0.4);
            display: inline-block;
            padding: 10px 18px;
            border-radius: 10px;
        }

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

<h1>📦 إدارة الطلبات</h1>

<div style="text-align:center;">
    <a href="admin_dashboard.php" class="back">⬅ العودة للوحة التحكم</a>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <p style="text-align:center;">لا توجد طلبات حالياً.</p>
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

        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                <td><?= htmlspecialchars($o['customer_phone']) ?></td>
                <td><?= htmlspecialchars($o['user_name'] ?? '-') ?></td>
                <td><?= number_format($o['amount'],2) ?> USD</td>
                <td>
                    <?= $o['delivery_method'] === 'pickup'
                        ? 'استلام من نقطة (' . htmlspecialchars($o['pickup_location'] ?? '-') . ')'
                        : 'توصيل (' . htmlspecialchars($o['address'] ?? '-') . ')' ?>
                </td>
                <td><strong><?= htmlspecialchars($o['status']) ?></strong></td>
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
                <td><?= htmlspecialchars($o['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
