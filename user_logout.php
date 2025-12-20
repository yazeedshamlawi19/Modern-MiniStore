<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

session_destroy();

$base = defined('BASE_URL') ? BASE_URL : '/';

header('Location: ' . $base . 'index.php');
exit;
