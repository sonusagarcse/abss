<?php
// parent/api/notifications.php - Parent Portal In-Built Notifications API

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notification_helper.php';

// Verify parent session
if (!isset($_SESSION['parent_id']) || empty($_SESSION['parent_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$parent_id = (int)$_SESSION['parent_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'fetch');

if ($action === 'fetch') {
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 15;
    $notifications = get_parent_notifications($parent_id, $limit);
    $unread_count = get_unread_notifications_count($parent_id);

    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);
    exit;
}

if ($action === 'mark_read') {
    $notification_id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    if ($notification_id > 0) {
        $res = mark_notification_as_read($notification_id, $parent_id);
        $unread_count = get_unread_notifications_count($parent_id);
        echo json_encode(['success' => (bool)$res, 'unread_count' => $unread_count]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

if ($action === 'mark_all_read') {
    $res = mark_all_notifications_read($parent_id);
    echo json_encode(['success' => (bool)$res, 'unread_count' => 0]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
