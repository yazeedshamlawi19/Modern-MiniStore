<?php
/* تحميل ملف الإعدادات العام (BASE_URL وغيره) */
require_once __DIR__ . '/../config.php';

/* تحميل ملف الاتصال بقاعدة البيانات */
require_once __DIR__ . '/../db.php';

/* تحديد نوع الاستجابة على أنها JSON */
header('Content-Type: application/json; charset=utf-8');

try {

  /* إنشاء اتصال بقاعدة البيانات */
  $pdo = db();

  /* تفعيل عرض الأخطاء من PDO (مهم للتصحيح) */
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  /* جلب نوع الطلب HTTP (GET / POST / DELETE) */
  $method = $_SERVER['REQUEST_METHOD'];

  /* التعامل مع الطلب حسب نوعه */
  switch ($method) {

    /* ================== GET ================== */
    case 'GET':

      /* جلب جميع المنتجات من جدول products */
      $stmt = $pdo->query("
        SELECT id, name, price, stock, image_url
        FROM products
        ORDER BY id DESC
      ");

      /* تحويل النتائج إلى مصفوفة */
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

      /* إخراج البيانات بصيغة JSON */
      echo json_encode($products, JSON_UNESCAPED_UNICODE);
      break;

    /* ================== POST ================== */
    case 'POST':

      /* تحديد نوع العملية (إنشاء أو تعديل) */
      $action = $_POST['action'] ?? 'save';

      /* ID المنتج في حالة التعديل */
      $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

      /* اسم المنتج */
      $name = trim($_POST['name'] ?? '');

      /* سعر المنتج */
      $price = floatval($_POST['price'] ?? 0);

      /* كمية المنتج */
      $stock = intval($_POST['stock'] ?? 0);

      /* رابط الصورة (إن وجد) */
      $image_url = trim($_POST['image_url'] ?? '');

      /* التحقق من صحة البيانات */
      if ($name === '' || $price <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '⚠️ بيانات المنتج غير صالحة.']);
        exit;
      }

      /* ================== رفع صورة ================== */
      if (!empty($_FILES['image']['tmp_name'])) {

        /* مسار مجلد الصور */
        $uploadDir = __DIR__ . '/../uploads/';

        /* إنشاء المجلد إذا لم يكن موجود */
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        /* إنشاء اسم فريد للصورة */
        $filename = uniqid() . '-' . basename($_FILES['image']['name']);

        /* المسار النهائي للصورة */
        $targetPath = $uploadDir . $filename;

        /* نقل الصورة من المسار المؤقت */
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
          /* حفظ رابط الصورة في قاعدة البيانات */
          $image_url = BASE_URL . 'uploads/' . $filename;
        }
      }

      /* ================== إضافة منتج ================== */
      if ($action === 'create' || !$id) {

        $stmt = $pdo->prepare("
          INSERT INTO products (name, price, stock, image_url)
          VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$name, $price, $stock, $image_url]);

        echo json_encode([
          'success' => true,
          'message' => '✅ تم إضافة المنتج بنجاح'
        ]);
        exit;
      }

      /* ================== تحديث منتج ================== */
      if ($action === 'update' && $id) {

        $stmt = $pdo->prepare("
          UPDATE products
          SET name=?, price=?, stock=?, image_url=?
          WHERE id=?
        ");

        $stmt->execute([$name, $price, $stock, $image_url, $id]);

        echo json_encode([
          'success' => true,
          'message' => '✅ تم تحديث المنتج'
        ]);
        exit;
      }

      /* في حال لم يتم تنفيذ أي إجراء */
      echo json_encode([
        'success' => false,
        'message' => '❌ لم يتم تنفيذ أي إجراء'
      ]);
      break;

    /* ================== DELETE ================== */
    case 'DELETE':

      /* قراءة بيانات DELETE */
      parse_str(file_get_contents("php://input"), $_DELETE);

      /* جلب ID المنتج */
      $id = intval($_GET['id'] ?? ($_DELETE['id'] ?? 0));

      /* التحقق من صحة ID */
      if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => '⚠️ معرف المنتج غير صالح.']);
        exit;
      }

      /* حذف المنتج من قاعدة البيانات */
      $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
      $stmt->execute([$id]);

      echo json_encode([
        'success' => true,
        'message' => '🗑️ تم حذف المنتج بنجاح'
      ]);
      break;

    /* ================== طريقة غير مدعومة ================== */
    default:
      http_response_code(405);
      echo json_encode(['error' => 'طريقة الطلب غير مسموحة.']);
  }

} catch (Throwable $e) {

  /* معالجة أي خطأ في الخادم */
  http_response_code(500);
  echo json_encode([
    'error' => 'حدث خطأ في الخادم: ' . $e->getMessage()
  ]);
}
