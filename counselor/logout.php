<?php
// counselor/logout.php - Counselor Session Termination
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

if (isset($_SESSION['counselor_id'])) {
    if (function_exists('log_activity')) {
        log_activity('logout', "Counselor logged out: " . ($_SESSION['counselor_email'] ?? ''));
    }
    unset($_SESSION['counselor_id']);
    unset($_SESSION['counselor_name']);
    unset($_SESSION['counselor_email']);
    unset($_SESSION['counselor_designation']);
    unset($_SESSION['counselor_permissions']);
}

clear_counselor_remember_cookie();

// If no other sessions are active, destroy session
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['parent_id']) && !isset($_SESSION['teacher_id'])) {
    session_destroy();
}

header("Location: login.php");
exit();

