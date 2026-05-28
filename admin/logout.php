<?php
// admin/logout.php - Session Termination & Audit Log

require_once '../config/db.php';
// session_start() is already handled in db.php via tracker_helper.php -> security.php

if (isset($_SESSION['admin_id'])) {
    log_activity('logout', "Admin logged out");
} elseif (isset($_SESSION['parent_id'])) {
    log_activity('logout', "Parent logged out");
}

session_destroy();
header("Location: login.php");
exit();
?>
