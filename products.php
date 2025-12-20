<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $method = $_SERVER['REQUEST_METHOD'];

  switch ($method) {

    case 'GET':

      $stmt = $pdo->query("SELECT id, name, price, stock, image_url FROM products ORDER BY id DESC");
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode($products, JSON_UNESCAPED_UNICODE);
      break;

    case 'POST':

      $action = $_POST['action'] ?? 'save';
      $id     = isset($_POST['id']) ? (int)$_POST['id'] : null;
      $name   = trim($_POST['name'] ?? '');
      $price  = floatval($_POST['price'] ?? 0);
      $stock  = intval($_POST['stock'] ?? 0);
      $image_url = trim($_POST['image_url'] ?? '');

      if ($name === '' || $price <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '⚠️ بيانات المنتج غير صالحة.']);
        exit;
      }

      if (!empty($_FILES['image']['tmp_name'])) {

        $uploadDir = __DIR__ . '/../uploads/';

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = uniqid() . '-' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
          $image_url = BASE_URL . 'uploads/' . $filename;
        }
      }

      if ($action === 'create' || !$id) {

        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $stock, $image_url]);
        echo json_encode(['success' => true, 'message' => '✅ تم إضافة المنتج بنجاح']);
        exit;
      }

      if ($action === 'update' && $id) {

        $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, stock=?, image_url=? WHERE id=?");
        $stmt->execute([$name, $price, $stock, $image_url, $id]);
        echo json_encode(['success' => true, 'message' => '✅ تم تحديث المنتج']);
        exit;
      }

      echo json_encode(['success' => false, 'message' => '❌ لم يتم تنفيذ أي إجراء']);
      break;

    case 'DELETE':

      parse_str(file_get_contents("php://input"), $_DELETE);
      $id = intval($_GET['id'] ?? ($_DELETE['id'] ?? 0));

      if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => '⚠️ معرف المنتج غير صالح.']);
        exit;
      }

      $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
      $stmt->execute([$id]);
      echo json_encode(['success' => true, 'message' => '🗑️ تم حذف المنتج بنجاح']);
      break;

    default:

      http_response_code(405);
      echo json_encode(['error' => 'طريقة الطلب غير مسموحة.']);
  }

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'حدث خطأ في الخادم: ' . $e->getMessage()]);
}
