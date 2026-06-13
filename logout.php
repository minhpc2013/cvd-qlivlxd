<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    logActivity('LOGOUT', 'Đăng xuất');
}
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit;
