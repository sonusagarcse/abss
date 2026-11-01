<?php
// counselor/tickets.php - Seamless gateway to admin panel module with granular permission checks
require_once __DIR__ . '/../admin/includes/auth.php';
$target = '../admin/tickets.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header("Location: $target");
exit();
