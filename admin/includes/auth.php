<?php
// admin/includes/auth.php
require_once __DIR__ . '/../../includes/security.php';
// session_start() is already handled securely in security.php

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../../config/db.php';
$conn = getDB();
?>
