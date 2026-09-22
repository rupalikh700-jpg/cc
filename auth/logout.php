<?php
// ============================================
// auth/logout.php — Logout Handler
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header("Location: /index.php");
exit;
?>
