<?php
// admin/includes/auth.php
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

// Auto-restore admin session from persistent cookie if available
verify_and_restore_admin_session();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../../config/db.php';
$conn = getDB();
?>
