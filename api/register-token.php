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

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/firebase.php';

// Accept JSON payload, form POST, or GET parameters (for Shiaho WebToApp v2.4.3 compatibility)
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?? [];

$token       = trim($inputData['token'] ?? $_POST['token'] ?? $_GET['token'] ?? '');
$device_type = trim($inputData['device_type'] ?? $_POST['device_type'] ?? $_GET['device_type'] ?? 'android');
$app_version = trim($inputData['app_version'] ?? $_POST['app_version'] ?? $_GET['app_version'] ?? '1.2.0');

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
    
    // Insert or update on duplicate token
    $stmt = $conn->prepare("
        INSERT INTO fcm_tokens (token, device_type, app_version) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            device_type = VALUES(device_type), 
            app_version = VALUES(app_version), 
            updated_at = NOW()
    ");
    
    $stmt->bind_param("sss", $token, $device_type, $app_version);
    
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
