<?php
/**
 * security/admin_ip_blocks.php
 * Strictly restricted endpoint — redirects normal users to Admin Login,
 * and authenticated administrators to the integrated Admin Control Desk.
 */
require_once __DIR__ . '/../admin/includes/auth.php';

// If authenticated admin, route seamlessly to the integrated Admin Firewall Desk
$base = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
header("Location: {$base}/admin/ip_firewall.php");
exit();
