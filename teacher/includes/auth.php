<?php
// teacher/includes/auth.php - Teacher Session Authorization Gatekeeper

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
$conn = getDB();

if (!isset($_SESSION['teacher_id']) || (int)$_SESSION['teacher_id'] <= 0) {
    header("Location: login.php");
    exit();
}

// Active Status Gatekeeper: Real-time verification of teacher account status
$tid = (int)$_SESSION['teacher_id'];
$stmt = $conn->prepare("SELECT id, status, name, email FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $tid);
$stmt->execute();
$teacher_check = $stmt->get_result()->fetch_assoc();

if (!$teacher_check || $teacher_check['status'] !== 'active') {
    // Unset teacher session
    unset($_SESSION['teacher_id']);
    unset($_SESSION['teacher_name']);
    unset($_SESSION['teacher_email']);

    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['parent_id'])) {
        session_destroy();
    }

    header("Location: login.php?error=inactive");
    exit();
}
