<?php
// api/send-notification.php - Bulk FCM Push Notification Dispatcher Engine
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/firebase.php';

// Security check: Admin session or API security key check
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$apiKey  = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? '';
$validKey = defined('FCM_API_SECRET') ? FCM_API_SECRET : 'abss_fcm_secret_key_2026';

if (!$isAdmin && $apiKey !== $validKey) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'error' => 'Unauthorized access. Admin authentication or valid API Secret required.'
    ]);
    exit;
}

// Parse Input
$inputRaw  = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?? [];

$title    = trim($inputData['title'] ?? $_POST['title'] ?? '');
$message  = trim($inputData['message'] ?? $_POST['message'] ?? '');
$image    = trim($inputData['image'] ?? $_POST['image'] ?? '');
$url      = trim($inputData['url'] ?? $_POST['url'] ?? '');
$category = trim($inputData['category'] ?? $_POST['category'] ?? 'General');
$selected_tokens = $inputData['selected_tokens'] ?? $_POST['selected_tokens'] ?? [];

if (empty($title) || empty($message)) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'error' => 'Missing required notification fields: title and message are required.'
    ]);
    exit;
}

try {
    $conn = getDB();

    // 1. Fetch Target Tokens
    $tokens = [];
    if (!empty($selected_tokens)) {
        if (is_string($selected_tokens)) {
            $selected_tokens = explode(',', $selected_tokens);
        }
        $cleanTokens = array_map(function($t) use ($conn) {
            return "'" . $conn->real_escape_string(trim($t)) . "'";
        }, $selected_tokens);
        
        $tokenListStr = implode(',', $cleanTokens);
        $res = $conn->query("SELECT id, token FROM fcm_tokens WHERE token IN ($tokenListStr)");
    } else {
        $res = $conn->query("SELECT id, token FROM fcm_tokens ORDER BY id DESC");
    }

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tokens[] = $row;
        }
    }

    // If no FCM tokens found, still broadcast to native website notification feed and history
    if (empty($tokens)) {
        // Save to native website notifications table
        $webNotifStmt = $conn->prepare("INSERT INTO notifications (title, message, url, status) VALUES (?, ?, ?, 1)");
        $webNotifStmt->bind_param("sss", $title, $message, $url);
        $webNotifStmt->execute();
        $webNotifStmt->close();

        // Record Log in notification_history
        $histStmt = $conn->prepare("
            INSERT INTO notification_history (title, message, image, url, category, target_audience, sent_count, failed_count) 
            VALUES (?, ?, ?, ?, ?, 'All Users', 0, 0)
        ");
        $histStmt->bind_param("sssss", $title, $message, $image, $url, $category);
        $histStmt->execute();
        $historyId = $histStmt->insert_id;
        $histStmt->close();

        echo json_encode([
            'status' => true,
            'message' => 'Notification broadcasted successfully to live website notification feed & in-app alerts.',
            'history_id' => $historyId,
            'sent_count' => 0,
            'failed_count' => 0,
            'cleaned_tokens' => 0,
            'total_targets' => 0
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $sent_count = 0;
    $failed_count = 0;
    $cleaned_tokens = 0;
    $expired_ids = [];
    $last_error = '';

    // 2. Dispatch Notifications via FCM HTTP v1 API
    foreach ($tokens as $item) {
        $fcmToken = $item['token'];
        $tokenId  = (int)$item['id'];

        $result = sendSingleFcmNotification($fcmToken, $title, $message, $image, $url, $category);

        if ($result['success']) {
            $sent_count++;
        } else {
            $failed_count++;
            if (!empty($result['error'])) {
                $last_error = $result['error'];
            }
            if (!empty($result['unregistered'])) {
                $expired_ids[] = $tokenId;
            }
        }
    }

    // 3. Auto-Clean Expired or Unregistered Tokens
    if (!empty($expired_ids)) {
        $cleaned_tokens = count($expired_ids);
        $idsStr = implode(',', array_map('intval', $expired_ids));
        $conn->query("DELETE FROM fcm_tokens WHERE id IN ($idsStr)");
    }

    // 4. Also insert into native website notifications table so web users see it instantly
    $webNotifStmt = $conn->prepare("INSERT INTO notifications (title, message, url, status) VALUES (?, ?, ?, 1)");
    $webNotifStmt->bind_param("sss", $title, $message, $url);
    $webNotifStmt->execute();
    $webNotifStmt->close();

    // 5. Record Dispatch Log in notification_history
    $targetAudience = !empty($selected_tokens) ? 'Selected Tokens (' . count($tokens) . ')' : 'All App Users (' . count($tokens) . ')';
    $histStmt = $conn->prepare("
        INSERT INTO notification_history (title, message, image, url, category, target_audience, sent_count, failed_count) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $histStmt->bind_param("ssssssii", $title, $message, $image, $url, $category, $targetAudience, $sent_count, $failed_count);
    $histStmt->execute();
    $historyId = $histStmt->insert_id;
    $histStmt->close();

    echo json_encode([
        'status' => true,
        'message' => ($sent_count > 0) ? "FCM Notification dispatched successfully to $sent_count devices." : "Notification processed ($failed_count failed/expired).",
        'history_id' => $historyId,
        'sent_count' => $sent_count,
        'failed_count' => $failed_count,
        'cleaned_tokens' => $cleaned_tokens,
        'total_targets' => count($tokens),
        'last_error' => $last_error
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'error' => 'FCM Dispatch Exception: ' . $e->getMessage()
    ]);
}
exit;
