<?php
// counselor/syllabus.php - Seamless gateway to admin panel module with granular permission checks
require_once __DIR__ . '/../admin/includes/auth.php';
$target = '../admin/syllabus.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header("Location: $target");
exit();
