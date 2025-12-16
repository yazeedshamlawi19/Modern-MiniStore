<?php

/**
 * التحقق من حالة الجلسة
 * 
 * PHP لا يسمح ببدء جلسة أكثر من مرة.
 * هذا الشرط يتأكد أن الجلسة غير مفعّلة
 * ثم يقوم ببدئها.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * is_logged_in
 * 
 * دالة تتحقق هل المستخدم مسجّل دخول أم لا.
 * 
 * الشروط:
 * - وجود $_SESSION['user']
 * - وجود user id داخلها
 * 
 * ترجع:
 * true  → المستخدم مسجّل دخول
 * false → المستخدم غير مسجّل
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * current_user_id
 * 
 * ترجع رقم (ID) المستخدم الحالي.
 * 
 * إذا كان المستخدم مسجّل دخول:
 * - ترجع user id كرقم صحيح
 * 
 * إذا لم يكن مسجّل:
 * - ترجع 0
 */
function current_user_id(): int
{
    return is_logged_in() ? (int)$_SESSION['user']['id'] : 0;
}

/**
 * require_login
 * 
 * دالة حماية للصفحات الخاصة بالمستخدمين.
 * 
 * إذا لم يكن المستخدم مسجّل دخول:
 * - يتم تحويله إلى صفحة تسجيل الدخول
 * - مع حفظ الصفحة الحالية (next)
 * - للعودة إليها بعد تسجيل الدخول
 */
function require_login(): void
{
    if (!is_logged_in()) {

        /**
         * حفظ الرابط الحالي
         * حتى نعيد المستخدم إليه بعد تسجيل الدخول
         */
        $next = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '';

        /**
         * BASE_URL
         * نستخدمه إن كان معرّفًا
         * وإلا نرجع إلى الجذر /
         */
        $base = defined('BASE_URL') ? BASE_URL : '/';

        /**
         * تحويل المستخدم إلى صفحة تسجيل الدخول
         * مع تمرير next إذا كان موجود
         */
        header('Location: ' . $base . 'user_login.php' . ($next ? ('?next=' . $next) : ''));
        exit;
    }
}

/**
 * is_admin
 * 
 * دالة تتحقق هل المستخدم الحالي هو مسؤول (Admin).
 * 
 * الشرط:
 * - وجود $_SESSION['admin']
 * - وجود logged_in = true داخلها
 */
function is_admin(): bool
{
    return isset($_SESSION['admin'])
        && !empty($_SESSION['admin']['logged_in']);
}

/**
 * require_admin
 * 
 * دالة حماية لصفحات المسؤول.
 * 
 * إذا لم يكن المستخدم أدمن:
 * - يتم تحويله إلى صفحة تسجيل دخول الأدمن
 * - يمنع الوصول للصفحة مباشرة
 */
function require_admin(): void
{
    if (!is_admin()) {

        /**
         * تحديد المسار الأساسي للمشروع
         */
        $base = defined('BASE_URL') ? BASE_URL : '/';

        /**
         * تحويل المستخدم إلى صفحة تسجيل دخول المسؤول
         */
        header('Location: ' . $base . 'admin_login.php');
        exit;
    }
}
