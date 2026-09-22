<?php
// api/register-token.php - FCM Token Registration API Endpoint
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Auto-restore parent session from remember cookie if not already set
verify_and_restore_parent_session();

// Accept JSON payload, form POST, or GET parameters (for Shiaho WebToApp v2.4.3 compatibility)
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?? [];

$token       = trim($inputData['token'] ?? $_POST['token'] ?? $_GET['token'] ?? '');
$device_type = trim($inputData['device_type'] ?? $_POST['device_type'] ?? $_GET['device_type'] ?? 'android');
$app_version = trim($inputData['app_version'] ?? $_POST['app_version'] ?? $_GET['app_version'] ?? '1.2.0');

$parent_id   = isset($inputData['parent_id']) ? (int)$inputData['parent_id'] : (isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : (isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : (isset($_SESSION['parent_id']) ? (int)$_SESSION['parent_id'] : 0)));
$student_id  = isset($inputData['student_id']) ? (int)$inputData['student_id'] : (isset($_POST['student_id']) ? (int)$_POST['student_id'] : (isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0));

if (empty($token) || strlen($token) < 20 || strpos($token, 'web_device_') === 0) {
    http_response_code(400);
    logFcmEvent('token_registration_invalid', ['token' => substr($token, 0, 15) . '...'], 'WARN', 400, 'Invalid or placeholder token.');
    echo json_encode([
        'status' => false,
        'error' => 'Invalid or placeholder FCM registration token.'
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

// Sanitize attributes
$device_type = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $device_type), 0, 50);
$app_version = substr(preg_replace('/[^a-zA-Z0-9_.\-]/', '', $app_version), 0, 20);

try {
    $conn = getDB();
    
    // Auto-resolve student if parent is present and student is not
    if ($parent_id > 0 && $student_id <= 0) {
        $sQuery = $conn->query("SELECT id FROM students WHERE parent_id = $parent_id ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, id ASC LIMIT 1");
        if ($sQuery && $sRow = $sQuery->fetch_assoc()) {
            $student_id = (int)$sRow['id'];
        }
    }
    // Auto-resolve parent if student is present and parent is not
    if ($student_id > 0 && $parent_id <= 0) {
        $pQuery = $conn->query("SELECT parent_id FROM students WHERE id = $student_id LIMIT 1");
        if ($pQuery && $pRow = $pQuery->fetch_assoc()) {
            $parent_id = (int)$pRow['parent_id'];
        }
    }

    $db_parent_id = $parent_id > 0 ? $parent_id : null;
    $db_student_id = $student_id > 0 ? $student_id : null;
    
    // Insert or update on duplicate token
    $stmt = $conn->prepare("
        INSERT INTO fcm_tokens (token, device_type, app_version, parent_id, student_id) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            device_type = VALUES(device_type), 
            app_version = VALUES(app_version), 
            parent_id = COALESCE(VALUES(parent_id), parent_id),
            student_id = COALESCE(VALUES(student_id), student_id),
            updated_at = NOW()
    ");
    
    $stmt->bind_param("sssii", $token, $device_type, $app_version, $db_parent_id, $db_student_id);
    
    if ($stmt->execute()) {
        $tokenId = $stmt->insert_id ?: 0;
        
        if ($tokenId === 0) {
            $getIdStmt = $conn->prepare("SELECT id FROM fcm_tokens WHERE token = ?");
            $getIdStmt->bind_param("s", $token);
            $getIdStmt->execute();
            $tokenId = $getIdStmt->get_result()->fetch_assoc()['id'] ?? 0;
            $getIdStmt->close();
        }

        // Automatically link and subscribe this token to Firebase global topic 'all'
        $subSuccess = subscribeFcmTokensToTopic($token, 'all');

        logFcmEvent('token_registered', [
            'token_id' => $tokenId,
            'device_type' => $device_type,
            'app_version' => $app_version,
            'topic_subscribed' => $subSuccess
        ], 'SUCCESS', 200);

        echo json_encode([
            'status' => true,
            'message' => 'FCM Token registered and subscribed to topic successfully',
            'token_id' => (int)$tokenId,
            'device_type' => $device_type,
            'app_version' => $app_version,
            'parent_id' => $parent_id > 0 ? (int)$parent_id : null,
            'student_id' => $student_id > 0 ? (int)$student_id : null,
            'topic_subscribed' => $subSuccess
        ], JSON_UNESCAPED_SLASHES);
    } else {
        logFcmEvent('token_registration_error', [], 'ERROR', 500, $stmt->error);
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'error' => 'Failed to save FCM token to database: ' . $stmt->error
        ]);
    }
    $stmt->close();

} catch (Exception $e) {
    logFcmEvent('token_registration_exception', [], 'ERROR', 500, $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'error' => 'Database Exception: ' . $e->getMessage()
    ]);
}
exit;
