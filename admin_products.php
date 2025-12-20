<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_admin();

$pdo = db();
$msg = '';
$err = '';

/* ================= ADD PRODUCT ================= */
if (isset($_POST['add_product'])) {

  $name        = trim($_POST['name']);
  $description = trim($_POST['description'] ?? '');
  $price       = (float)$_POST['price'];
  $stock       = (int)$_POST['stock'];

  $color_name  = trim($_POST['color_name']);
  $color_qty   = (int)$_POST['color_qty'];

  $imagePath = '';

  if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = time() . '_' . basename($_FILES['image']['name']);
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $imagePath = 'uploads/' . $filename;
    }
  }

  $stmt = $pdo->prepare("
    INSERT INTO products (name, description, price, stock, image_url)
    VALUES (:n, :d, :p, :s, :i)
  ");

  $stmt->execute([
    ':n' => $name,
    ':d' => $description,
    ':p' => $price,
    ':s' => $stock,
    ':i' => $imagePath
  ]);

  $product_id = (int)$pdo->lastInsertId();

  if ($color_name && $color_qty > 0) {
    $pdo->prepare(
      "INSERT INTO product_variants (product_id, color, stock)
       VALUES (?, ?, ?)"
    )->execute([$product_id, $color_name, $color_qty]);
  }

  $msg = '✅ تمت إضافة المنتج مع اللون بنجاح';
}

/* ================= EDIT PRODUCT ================= */
if (isset($_POST['edit_product'])) {

  $id          = (int)$_POST['id'];
  $name        = trim($_POST['name']);
  $description = trim($_POST['description'] ?? '');
  $price       = (float)$_POST['price'];
  $stock       = (int)$_POST['stock'];
  $imagePath   = $_POST['old_image'] ?? '';

  if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = time() . '_' . basename($_FILES['image']['name']);
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $imagePath = 'uploads/' . $filename;
    }
  }

  $pdo->prepare("
    UPDATE products
    SET name=?, description=?, price=?, stock=?, image_url=?
    WHERE id=?
  ")->execute([$name, $description, $price, $stock, $imagePath, $id]);

  $msg = '✏️ تم تعديل المنتج';
}

/* ================= DELETE PRODUCT ================= */
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
  $msg = '🗑️ تم حذف المنتج';
}

/* ================= VARIANTS ================= */
if (isset($_POST['add_variant'])) {
  $pdo->prepare(
    "INSERT INTO product_variants (product_id, color, stock)
     VALUES (?, ?, ?)"
  )->execute([
    (int)$_POST['product_id'],
    trim($_POST['color']),
    (int)$_POST['stock']
  ]);
  $msg = '🎨 تم إضافة لون';
}

if (isset($_POST['edit_variant'])) {
  $pdo->prepare(
    "UPDATE product_variants SET color=?, stock=? WHERE id=?"
  )->execute([
    trim($_POST['color']),
    (int)$_POST['stock'],
    (int)$_POST['variant_id']
  ]);
  $msg = '🎨 تم تعديل اللون';
}

if (isset($_POST['delete_variant'])) {
  $pdo->prepare("DELETE FROM product_variants WHERE id=?")
      ->execute([(int)$_POST['variant_id']]);
  $msg = '🗑️ تم حذف اللون';
}

/* ================= DATA ================= */
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")
                ->fetchAll(PDO::FETCH_ASSOC);

function getVariants($pdo, $productId) {
  $s = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
  $s->execute([$productId]);
  return $s->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة المنتجات</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* ===== ADMIN PRODUCTS ===== */

.admin-wrap {
  max-width: 1200px;
  margin: auto;
}

.product-card {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 16px;
  padding: 18px;
  margin-bottom: 25px;
}

.product-top {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 18px;
  align-items: start;
}

.product-img {
  width: 140px;
  height: 140px;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.2);
}

.product-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-form {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.product-form input,
.product-form textarea {
  width: 100%;
  padding: 8px;
  border-radius: 8px;
  border: none;
}

.product-form textarea {
  grid-column: span 4;
  resize: vertical;
}

.product-actions {
  grid-column: span 4;
  display: flex;
  gap: 10px;
}

.product-actions button,
.product-actions a {
  padding: 8px 14px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: bold;
  text-decoration: none;
}

.btn-edit { background:#a78bfa; color:#000; }
.btn-delete { background:#ef4444; color:#fff; }

/* ===== VARIANTS ===== */

.variants-box {
  margin-top: 18px;
  padding-top: 12px;
  border-top: 1px dashed rgba(255,255,255,0.3);
}

.variants-title {
  margin-bottom: 10px;
  font-weight: bold;
  opacity: .9;
}

.variant-row {
  display: grid;
  grid-template-columns: 2fr 1fr auto auto;
  gap: 8px;
  margin-bottom: 8px;
}

.variant-row input {
  padding: 6px;
  border-radius: 6px;
  border: none;
}

.variant-row button {
  padding: 6px 10px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.variant-edit { background:#22c55e; }
.variant-delete { background:#ef4444; color:#fff; }

.add-variant {
  margin-top: 10px;
  display: grid;
  grid-template-columns: 2fr 1fr auto;
  gap: 8px;
}

.add-variant button {
  background:#38bdf8;
  font-weight:bold;
}
/* ===== FORM ELEMENTS DARK THEME ===== */

input,
textarea,
select {
  background: rgba(255,255,255,0.08);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 10px;
  padding: 10px;
  outline: none;
  transition: 0.25s ease;
}

input::placeholder,
textarea::placeholder {
  color: rgba(255,255,255,0.6);
}

input:focus,
textarea:focus,
select:focus {
  background: rgba(255,255,255,0.12);
  border-color: #a78bfa;
  box-shadow: 0 0 0 2px rgba(167,139,250,0.35);
}

/* file input */
input[type="file"] {
  background: transparent;
  border: none;
  color: #fff;
}

.variant-row {
  display: grid;
  grid-template-columns: 2fr 1fr auto auto;
  gap: 10px;
  align-items: center;
}

.add-variant {
  display: grid;
  grid-template-columns: 2fr 1fr auto;
  gap: 10px;
  margin-top: 10px;
}

</style>
</head>
<body>

<a href="admin_dashboard.php">⬅️ رجوع</a>
<h1>🛍️ إدارة المنتجات</h1>

<?php if($msg): ?><p><?= htmlspecialchars($msg) ?></p><?php endif; ?>

<h3>➕ إضافة منتج</h3>
<form method="post" enctype="multipart/form-data">
  <input name="name" placeholder="اسم المنتج" required>
  <textarea name="description" placeholder="وصف المنتج" required></textarea>
  <input type="number" step="0.01" name="price" placeholder="السعر" required>
  <input type="number" name="stock" placeholder="مخزون عام">
  <input name="color_name" placeholder="لون (أحمر)">
  <input type="number" name="color_qty" placeholder="كمية اللون">
  <input type="file" name="image" required>
  <button name="add_product">➕ إضافة</button>
</form>

<hr>

<?php foreach($products as $p): ?>
<div class="product-card">

  <div class="product-top">

    <!-- الصورة -->
    <div class="product-img">
      <?php if ($p['image_url']): ?>
        <img src="<?= htmlspecialchars($p['image_url']) ?>">
      <?php else: ?>
        <div style="padding:20px;text-align:center;">لا صورة</div>
      <?php endif; ?>
    </div>

    <!-- فورم المنتج -->
    <form method="post" enctype="multipart/form-data" class="product-form">
      <input type="hidden" name="id" value="<?= $p['id'] ?>">
      <input type="hidden" name="old_image" value="<?= $p['image_url'] ?>">

      <input name="name" value="<?= htmlspecialchars($p['name']) ?>">
      <input type="number" step="0.01" name="price" value="<?= $p['price'] ?>">
      <input type="number" name="stock" value="<?= $p['stock'] ?>">
      <input type="file" name="image">

      <textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea>

      <div class="product-actions">
        <button name="edit_product" class="btn-edit">✏️ تعديل</button>
        <a href="?delete=<?= $p['id'] ?>" class="btn-delete"
           onclick="return confirm('حذف المنتج؟')">🗑️ حذف</a>
      </div>
    </form>

  </div>

  <!-- الألوان -->
  <div class="variants-box">
    <div class="variants-title">🎨 الألوان</div>

    <?php foreach (getVariants($pdo, $p['id']) as $v): ?>
      <form method="post" class="variant-row">
        <input type="hidden" name="variant_id" value="<?= $v['id'] ?>">
        <input name="color" value="<?= htmlspecialchars($v['color']) ?>">
        <input type="number" name="stock" value="<?= $v['stock'] ?>">
        <button name="edit_variant" class="variant-edit">✔</button>
        <button name="delete_variant" class="variant-delete">✖</button>
      </form>
    <?php endforeach; ?>

    <!-- إضافة لون -->
    <form method="post" class="add-variant">
      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
      <input name="color" placeholder="لون جديد">
      <input type="number" name="stock" placeholder="كمية">
      <button name="add_variant">➕</button>
    </form>

  </div>

</div>

<?php foreach(getVariants($pdo,$p['id']) as $v): ?>
<form method="post" class="variant">
  <input type="hidden" name="variant_id" value="<?= $v['id'] ?>">
  <input name="color" value="<?= htmlspecialchars($v['color']) ?>">
  <input type="number" name="stock" value="<?= $v['stock'] ?>">
  <button name="edit_variant">تعديل</button>
  <button name="delete_variant" onclick="return confirm('حذف اللون؟')">حذف</button>
</form>
<?php endforeach; ?>

<form method="post" class="variant">
  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
  <input name="color" placeholder="لون جديد">
  <input type="number" name="stock" placeholder="كمية">
  <button name="add_variant">➕ إضافة لون</button>
</form>

<hr>
<?php endforeach; ?>


</body>
</html>
