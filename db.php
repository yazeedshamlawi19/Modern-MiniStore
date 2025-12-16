<?php

/**
 * التأكد إذا كانت الدالة h غير معرّفة مسبقًا
 * الهدف:
 * - منع إعادة تعريف الدالة إذا تم تضمين الملف أكثر من مرة
 * - تجنب أخطاء Fatal Error
 */
if (!function_exists('h')) {

    /**
     * دالة h
     * تستخدم لتعقيم النصوص قبل عرضها في HTML
     * 
     * الهدف:
     * - منع هجمات XSS
     * - تحويل الرموز الخاصة مثل < > " '
     * 
     * $s ?? '' :
     * إذا كانت القيمة null يتم تحويلها إلى نص فارغ
     */
    function h($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * دالة db
 * مسؤولة عن إنشاء وإرجاع اتصال قاعدة البيانات (PDO)
 * 
 * يتم استخدام static حتى:
 * - يتم إنشاء الاتصال مرة واحدة فقط
 * - إعادة استخدام نفس الاتصال في كل استدعاء
 */
function db() {

    /**
     * static $pdo
     * متغير ثابت داخل الدالة
     * يحتفظ بقيمة الاتصال حتى بعد انتهاء تنفيذ الدالة
     */
    static $pdo;

    /**
     * إذا كان الاتصال موجود مسبقًا (كائن PDO)
     * نعيده مباشرة بدون إنشاء اتصال جديد
     */
    if ($pdo instanceof PDO) return $pdo;

    /**
     * تضمين ملف الإعدادات
     * للحصول على:
     * - DB_HOST
     * - DB_NAME
     * - DB_USER
     * - DB_PASS
     * - DB_CHARSET
     */
    require_once __DIR__ . '/config.php';

    /**
     * إنشاء DSN (Data Source Name)
     * يحتوي معلومات الاتصال بقاعدة البيانات:
     * - نوع قاعدة البيانات (mysql)
     * - السيرفر
     * - اسم قاعدة البيانات
     * - الترميز
     */
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    try {

        /**
         * إنشاء كائن PDO
         * المعاملات:
         * 1- DSN
         * 2- اسم المستخدم
         * 3- كلمة المرور
         * 4- إعدادات إضافية
         */
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [

            /**
             * PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
             * يجعل PDO يرمي Exceptions عند حدوث أي خطأ
             * بدلاً من الفشل الصامت
             */
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            /**
             * PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
             * يجعل نتائج الاستعلامات ترجع كمصفوفة Associative
             * بدل مصفوفة رقمية
             */
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

    } catch (PDOException $e) {

        /**
         * في حال فشل الاتصال بقاعدة البيانات
         * يتم إيقاف التنفيذ وعرض رسالة خطأ
         * (حل تعليمي – في المشاريع الكبيرة يتم تسجيل الخطأ فقط)
         */
        die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }

    /**
     * إرجاع كائن PDO
     * لاستخدامه في باقي ملفات المشروع
     */
    return $pdo;
}
