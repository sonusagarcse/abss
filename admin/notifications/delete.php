<?php
// admin/notifications/delete.php - Delete Notification Record
require_once '../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM notification_history WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: index.php?msg=" . urlencode("Notification record #$id deleted successfully."));
        exit();
    } else {
        header("Location: index.php?err=" . urlencode("Error deleting record: " . $stmt->error));
        exit();
    }
}

header("Location: index.php");
exit();
