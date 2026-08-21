<?php
// admin/logout.php - Independent Admin Logout Handler

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

if (function_exists('clear_admin_remember_cookie')) {
    clear_admin_remember_cookie();
}

if (isset($_SESSION['admin_id'])) {
    if (function_exists('log_activity')) {
        log_activity('logout', "Admin logged out: " . ($_SESSION['username'] ?? ''));
    }
    unset($_SESSION['admin_id']);
    unset($_SESSION['username']);
    unset($_SESSION['user_id']);
}

// Destroy session file ONLY if no other portal sessions (Teacher/Parent) are active
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['parent_id'])) {
    session_destroy();
}

header("Location: login.php");
exit();
?>
