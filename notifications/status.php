<?php
require_once __DIR__ . '/../config/db.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$status = isset($_REQUEST['status']) ? (int)$_REQUEST['status'] : null;

if ($id > 0 && $status !== null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET status = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        $redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
        header("Location: " . $redirect . "?msg=status_updated");
        exit;
    } catch (Exception $e) {
        $redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
        header("Location: " . $redirect . "?error=" . urlencode($e->getMessage()));
        exit;
    }
}

$redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
header("Location: " . $redirect);
exit;
