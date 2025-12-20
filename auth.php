<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function current_user_id(): int
{
    return is_logged_in() ? (int)$_SESSION['user']['id'] : 0;
}

function require_login(): void
{
    if (!is_logged_in()) {

        $next = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '';

        $base = defined('BASE_URL') ? BASE_URL : '/';

        header('Location: ' . $base . 'user_login.php' . ($next ? ('?next=' . $next) : ''));
        exit;
    }
}

function is_admin(): bool {
    return !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function require_admin(): void {
    if (!is_admin()) {
        $base = defined('BASE_URL') ? BASE_URL : '/';
        header('Location: ' . $base . 'user_login.php');
        exit;
    }
}
