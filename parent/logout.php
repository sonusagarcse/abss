<?php
// parent/logout.php - Independent Parent Portal Logout Handler

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

if (isset($_SESSION['parent_id'])) {
    if (function_exists('log_activity')) {
        log_activity('logout', "Parent logged out: " . ($_SESSION['parent_email'] ?? ''));
    }
    unset($_SESSION['parent_id']);
    unset($_SESSION['parent_name']);
    unset($_SESSION['parent_email']);
    unset($_SESSION['show_missing_docs_popup']);
    unset($_SESSION['missing_docs_notified']);
}

// Clear persistent auto-login cookie safely across all scopes
clear_parent_remember_cookie();

// Destroy session file ONLY if no other portal sessions (Admin/Teacher) are active
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])) {
    session_destroy();
}

header("Location: login.php");
exit();
