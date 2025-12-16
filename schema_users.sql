-- إنشاء جدول users (المستخدمين) إذا لم يكن موجودًا
CREATE TABLE IF NOT EXISTS users (

  -- id: رقم تعريفي فريد لكل مستخدم
  -- AUTO_INCREMENT يعني يزيد تلقائيًا
  -- PRIMARY KEY يعني المفتاح الأساسي للجدول
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- name: اسم المستخدم الكامل
  -- لا يسمح بأن يكون فارغًا
  name VARCHAR(255) NOT NULL,

  -- email: البريد الإلكتروني
  -- UNIQUE يعني لا يمكن تكرار نفس البريد لمستخدمين مختلفين
  email VARCHAR(255) NOT NULL UNIQUE,

  -- password_hash: كلمة المرور بعد تشفيرها (hash)
  -- لا يتم تخزين كلمة المرور كنص عادي لأسباب أمنية
  password_hash VARCHAR(255) NOT NULL,

  -- created_at: تاريخ إنشاء الحساب
  -- يتم تعيينه تلقائيًا عند إدخال السجل
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

-- استخدام محرك InnoDB لدعم المفاتيح الخارجية
-- الترميز utf8mb4 لدعم اللغة العربية
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- =========================================
-- فحص هل العمود user_id موجود في جدول orders
-- =========================================

-- إنشاء متغير اسمه col_exists
-- يتم وضع فيه عدد الأعمدة التي اسمها user_id
SET @col_exists := (

  -- الاستعلام على معلومات بنية قاعدة البيانات
  SELECT COUNT(*)
  FROM information_schema.columns

  -- التحقق من نفس قاعدة البيانات الحالية
  WHERE table_schema = DATABASE()

    -- التأكد أن الجدول هو orders
    AND table_name = 'orders'

    -- التأكد أن العمود هو user_id
    AND column_name = 'user_id'
);

-- إنشاء أمر SQL ديناميكي
-- IF:
-- إذا كان العمود غير موجود (count = 0)
-- قم بإضافة العمود user_id + إنشاء index
-- غير ذلك نفّذ SELECT 1 (ولا تفعل شيئًا)
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE orders ADD COLUMN user_id INT NULL, ADD INDEX idx_orders_user_id (user_id)',
  'SELECT 1'
);

-- تحضير الأمر المخزن في المتغير @ddl
PREPARE stmt FROM @ddl;

-- تنفيذ الأمر المحضّر
EXECUTE stmt;

-- حذف الأمر من الذاكرة
DEALLOCATE PREPARE stmt;



-- =========================================
-- فحص هل المفتاح الخارجي موجود مسبقًا
-- =========================================

-- اسم المفتاح الخارجي الذي نريده
SET @fk_name := 'fk_orders_user';

-- التحقق من وجود Foreign Key بهذا الاسم
SET @fk_exists := (

  -- عدّ عدد القيود (constraints)
  SELECT COUNT(*)
  FROM information_schema.table_constraints

  -- في نفس قاعدة البيانات
  WHERE constraint_schema = DATABASE()

    -- على جدول orders
    AND table_name = 'orders'

    -- نوع القيد Foreign Key
    AND constraint_type = 'FOREIGN KEY'

    -- بنفس الاسم fk_orders_user
    AND constraint_name = @fk_name
);

-- إنشاء أمر SQL ديناميكي
-- IF:
-- إذا لم يكن المفتاح الخارجي موجودًا
-- قم بإنشائه
-- غير ذلك نفّذ SELECT 1 فقط
SET @ddl2 := IF(@fk_exists = 0,
  CONCAT(
    'ALTER TABLE orders ADD CONSTRAINT ', @fk_name,
    ' FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL'
  ),
  'SELECT 1'
);

-- تحضير الأمر الثاني
PREPARE stmt2 FROM @ddl2;

-- تنفيذ الأمر
EXECUTE stmt2;

-- حذف الأمر من الذاكرة
DEALLOCATE PREPARE stmt2;
