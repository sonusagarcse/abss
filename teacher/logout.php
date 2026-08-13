<?php
// teacher/logout.php - Independent Teacher Logout Handler

require_once __DIR__ . '/../includes/security.php';

if (isset($_SESSION['teacher_id'])) {
    if (function_exists('log_activity')) {
        log_activity('logout', "Teacher logged out: " . ($_SESSION['teacher_email'] ?? ''));
    }
    unset($_SESSION['teacher_id']);
    unset($_SESSION['teacher_name']);
    unset($_SESSION['teacher_email']);
}

// Destroy session file ONLY if no other portal sessions (Admin/Parent) are active
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['parent_id'])) {
    session_destroy();
}

header("Location: login.php");
exit();
