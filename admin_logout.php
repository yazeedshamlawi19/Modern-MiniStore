<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

unset($_SESSION['user']);
session_destroy();

$base = defined('BASE_URL') ? BASE_URL : '/';
header('Location: ' . $base . 'index.php');
exit;

$base = defined('BASE_URL') ? BASE_URL : '/';

header('Location: ' . $base . 'index.php');
exit;
