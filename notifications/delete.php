<?php
require_once __DIR__ . '/../config/db.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
        header("Location: " . $redirect . "?msg=deleted");
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
