<?php
// admin/includes/auth.php - Strict Admin Session Authorization Gatekeeper

// When invoked from Counselor Portal module bridge, counselor session & module permissions are already validated
if (defined('IS_COUNSELOR_VIEW')) {
    return;
}

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/db.php';

$conn = getDB();

// 1. Auto-restore admin session from persistent cookie if available
verify_and_restore_admin_session();

// 2. STRICT GATEKEEPER: Admin panel is exclusively for Administrator accounts
if (!isset($_SESSION['admin_id']) || (int)$_SESSION['admin_id'] <= 0) {
    // If a counselor is logged in, redirect them strictly to their own Counselor Panel
    if (isset($_SESSION['counselor_id']) && (int)$_SESSION['counselor_id'] > 0) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $counselor_path = (strpos($host, 'localhost') !== false) ? '/abss/counselor/dashboard.php' : '/counselor/dashboard.php';
        header("Location: $counselor_path");
        exit();
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $login_path = (strpos($host, 'localhost') !== false) ? '/abss/admin/login' : '/admin/login';
    header("Location: $login_path");
    exit();
}
?>
