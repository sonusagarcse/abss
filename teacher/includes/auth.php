<?php
// teacher/includes/auth.php - Teacher Session Authorization Gatekeeper

require_once __DIR__ . '/../../includes/security.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../../config/db.php';
$conn = getDB();
