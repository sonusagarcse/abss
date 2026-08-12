<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $url = isset($_POST['url']) ? trim($_POST['url']) : null;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if (!empty($title) && !empty($message)) {
        try {
            $db = getDB();
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE notifications SET title = ?, message = ?, url = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssii", $title, $message, $url, $status, $id);
            } else {
                $stmt = $db->prepare("INSERT INTO notifications (title, message, url, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $title, $message, $url, $status);
            }
            $stmt->execute();
            $redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
            header("Location: " . $redirect . "?msg=success");
            exit;
        } catch (Exception $e) {
            $redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
            header("Location: " . $redirect . "?error=" . urlencode($e->getMessage()));
            exit;
        }
    } else {
        $redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
        header("Location: " . $redirect . "?error=" . urlencode("Title and Message are required."));
        exit;
    }
}

$redirect = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'notifications/admin.php') !== false) ? 'admin.php' : '../admin/notifications.php';
header("Location: " . $redirect);
exit;
