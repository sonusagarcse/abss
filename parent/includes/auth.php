<?php
// parent/includes/auth.php - Parent Session Authorization Gatekeeper

require_once __DIR__ . '/../../includes/security.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: ../admin/login.php?role=parent");
    exit();
}

require_once __DIR__ . '/../../config/db.php';
$conn = getDB();
?>
