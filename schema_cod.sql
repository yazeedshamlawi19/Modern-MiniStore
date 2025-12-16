-- إنشاء جدول المنتجات إذا لم يكن موجودًا مسبقًا
CREATE TABLE IF NOT EXISTS products (

  -- عمود id: رقم تعريفي فريد لكل منتج
  -- AUTO_INCREMENT يعني يزيد تلقائيًا
  -- PRIMARY KEY يعني مفتاح أساسي للجدول
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- اسم المنتج كنص بطول أقصى 255 حرف
  -- NOT NULL يعني لا يسمح أن يكون فارغًا
  name VARCHAR(255) NOT NULL,

  -- سعر المنتج
  -- DECIMAL(10,2) يعني رقم عشري بدقة رقمين بعد الفاصلة
  -- DEFAULT 0 يعني القيمة الافتراضية 0
  price DECIMAL(10,2) NOT NULL DEFAULT 0,

  -- كمية المخزون
  -- عدد صحيح
  stock INT NOT NULL DEFAULT 0,

  -- رابط صورة المنتج
  -- NULL يعني يمكن أن يكون فارغًا
  image_url VARCHAR(500) NULL,

  -- تاريخ إنشاء المنتج
  -- يتم تعيينه تلقائيًا عند الإدخال
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

-- استخدام محرك InnoDB لدعم العلاقات (Foreign Keys)
-- الترميز utf8mb4 لدعم اللغة العربية والإيموجي
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- إنشاء جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (

  -- رقم المستخدم (مفتاح أساسي)
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- اسم المستخدم
  name VARCHAR(255) NOT NULL,

  -- البريد الإلكتروني
  -- UNIQUE يعني لا يسمح بتكرار نفس البريد
  email VARCHAR(255) NOT NULL UNIQUE,

  -- تخزين كلمة المرور بشكل مشفر (hash)
  password_hash VARCHAR(255) NOT NULL,

  -- تاريخ إنشاء الحساب
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- إنشاء جدول الطلبات
CREATE TABLE IF NOT EXISTS orders (

  -- رقم الطلب (مفتاح أساسي)
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- رقم المستخدم الذي قام بالطلب
  -- NULL مسموح (في حال حذف المستخدم)
  user_id INT NULL,

  -- رقم الطلب من بوابة الدفع
  -- في مشروعك COD-xxxx
  gateway_order_id VARCHAR(200) NOT NULL,

  -- حالة الطلب (قيد التأكيد، مكتمل، ملغى...)
  status VARCHAR(50) NOT NULL,

  -- العملة (USD)
  currency VARCHAR(10) NOT NULL,

  -- المبلغ الإجمالي للطلب
  amount DECIMAL(10,2) NOT NULL,

  -- اسم العميل وقت الطلب
  customer_name VARCHAR(255) NOT NULL,

  -- رقم هاتف العميل
  customer_phone VARCHAR(50) NOT NULL,

  -- طريقة الاستلام
  -- delivery = توصيل
  -- pickup = استلام من نقطة
  delivery_method VARCHAR(20) NOT NULL, 

  -- عنوان التوصيل (اختياري)
  address VARCHAR(500) NULL,

  -- نقطة الاستلام (اختياري)
  pickup_location VARCHAR(255) NULL,

  -- ملاحظات إضافية من العميل
  notes TEXT NULL,

  -- تاريخ إنشاء الطلب
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  -- فهرس لتحسين البحث حسب المستخدم
  INDEX idx_orders_user_id (user_id),

  -- ربط user_id مع جدول users
  -- ON DELETE SET NULL يعني:
  -- إذا تم حذف المستخدم، يبقى الطلب لكن user_id يصبح NULL
  CONSTRAINT fk_orders_user 
    FOREIGN KEY (user_id) 
    REFERENCES users(id) 
    ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- إنشاء جدول عناصر الطلب
CREATE TABLE IF NOT EXISTS order_items (

  -- رقم العنصر داخل الطلب
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- رقم الطلب المرتبط بهذا العنصر
  order_id INT NOT NULL,

  -- رقم المنتج (اختياري)
  -- NULL مسموح في حال حذف المنتج
  product_id INT NULL,

  -- اسم المنتج وقت الطلب
  -- يتم حفظه حتى لو تغير اسم المنتج لاحقًا
  name VARCHAR(255),

  -- الكمية المطلوبة من المنتج
  qty INT NOT NULL,

  -- سعر الوحدة وقت الطلب
  unit_price DECIMAL(10,2) NOT NULL,

  -- ربط العنصر بالطلب
  -- ON DELETE CASCADE يعني:
  -- إذا تم حذف الطلب، تُحذف عناصره تلقائيًا
  FOREIGN KEY (order_id) 
    REFERENCES orders(id) 
    ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- إدخال بيانات افتراضية في جدول المنتجات
INSERT INTO products (name, price, stock, image_url) VALUES

-- المنتج الأول
('قلم حبر', 2.50, 100, 'https://picsum.photos/seed/pen/400/300'),

-- المنتج الثاني
('دفتر ملاحظات', 5.90, 80, 'https://picsum.photos/seed/notebook/400/300'),

-- المنتج الثالث
('زجاجة ماء', 3.20, 60, 'https://picsum.photos/seed/bottle/400/300')

-- في حال كان المنتج موجود مسبقًا بنفس المفتاح
-- يتم تحديث الاسم بدل إدخال سجل جديد
ON DUPLICATE KEY UPDATE name=VALUES(name);
