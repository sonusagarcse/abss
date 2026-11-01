<?php
// counselor/visitors.php - Seamless gateway to admin panel module with granular permission checks
require_once __DIR__ . '/../admin/includes/auth.php';
$target = '../admin/visitors.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header("Location: $target");
exit();
