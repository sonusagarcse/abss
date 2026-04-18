<?php
// admin/includes/auth.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../../config/db.php';
$conn = getDB();
?>
