<?php
// config/firebase.php - Pure PHP Firebase HTTP v1 API & OAuth 2.0 Auth Engine for ABSS / LKVM
require_once __DIR__ . '/db.php';

/**
 * Returns Firebase Service Account Configuration File Path
 */
function getFirebaseServiceAccountPath() {
    $path = __DIR__ . '/service-account.json';
    if (file_exists($path)) {
        return $path;
    }
    $fallback = __DIR__ . '/../service-account.json';
    if (file_exists($fallback)) {
        return $fallback;
    }
    return false;
}

/**
 * Generate OAuth 2.0 Access Token for Firebase HTTP v1 API using Pure PHP JWT Signing
 */
function getFirebaseAccessToken() {
    if (isset($_SESSION['fcm_access_token']) && isset($_SESSION['fcm_access_token_expires']) && $_SESSION['fcm_access_token_expires'] > time()) {
        return $_SESSION['fcm_access_token'];
    }

    $saPath = getFirebaseServiceAccountPath();
    if (!$saPath) {
        throw new Exception("Firebase Service Account JSON file (config/service-account.json) is missing. Upload service-account.json to config/ directory.");
    }

    $saData = json_decode(file_get_contents($saPath), true);
    if (!$saData || empty($saData['private_key']) || empty($saData['client_email']) || empty($saData['project_id'])) {
        throw new Exception("Invalid Firebase Service Account JSON format in config/service-account.json.");
    }

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $saData['client_email'],
        'sub' => $saData['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
    $signature = '';
    
    $success = openssl_sign($signatureInput, $signature, $saData['private_key'], 'SHA256');
    if (!$success) {
        throw new Exception("OpenSSL JWT signature generation failed. Check PHP OpenSSL extension.");
    }

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signatureInput . "." . $base64UrlSignature;

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("OAuth 2.0 Token Exchange failed (HTTP $httpCode): " . $response);
    }

    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        throw new Exception("Invalid OAuth token response from Google APIs: " . $response);
    }

    $_SESSION['fcm_access_token'] = $tokenData['access_token'];
    $_SESSION['fcm_access_token_expires'] = time() + 3300;

    return $tokenData['access_token'];
}

function getFirebaseProjectId() {
    $saPath = getFirebaseServiceAccountPath();
    if ($saPath) {
        $saData = json_decode(file_get_contents($saPath), true);
        if (!empty($saData['project_id'])) return $saData['project_id'];
    }
    if (function_exists('getAllSettings')) {
        $st = getAllSettings();
        if (!empty($st['firebase_project_id'])) return $st['firebase_project_id'];
    }
    return 'abss-notification';
}

/**
 * Build Full Platform FCM HTTP v1 JSON Payload
 */
function buildFcmMessagePayload($target, $title, $body, $image = null, $url = null, $category = 'General', $isTopic = false) {
    $targetKey = $isTopic ? "topic" : "token";
    $cleanTarget = $target;
    if ($isTopic && strpos($cleanTarget, '/topics/') === 0) {
        $cleanTarget = str_replace('/topics/', '', $cleanTarget);
    }

    $strTitle = (string)$title;
    $strBody  = (string)$body;
    $strImage = !empty($image) ? (string)$image : '';
    $strUrl   = !empty($url) ? (string)$url : '';
    $strCat   = !empty($category) ? (string)$category : 'General';
    $baseUrl  = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
    $iconUrl  = $baseUrl . '/assets/logo.png';

    $message = [
        $targetKey => $cleanTarget,
        "notification" => [
            "title" => $strTitle,
            "body" => $strBody
        ],
        "data" => [
            "title" => $strTitle,
            "body" => $strBody,
            "message" => $strBody,
            "notification_title" => $strTitle,
            "notification_body" => $strBody,
            "category" => $strCat,
            "click_url" => $strUrl,
            "url" => $strUrl,
            "link" => $strUrl,
            "open_url" => $strUrl,
            "image" => $strImage,
            "image_url" => $strImage,
            "picture" => $strImage,
            "sound" => "default",
            "timestamp" => (string)time()
        ],
        "android" => [
            "priority" => "HIGH",
            "direct_boot_ok" => true,
            "notification" => [
                "title" => $strTitle,
                "body" => $strBody,
                "icon" => "ic_notification",
                "color" => "#2563eb",
                "sound" => "default",
                "default_sound" => true,
                "default_vibrate_timings" => true,
                "default_light_settings" => true,
                "notification_priority" => "PRIORITY_MAX",
                "visibility" => "PUBLIC",
                "channel_id" => "default",
                "click_action" => "OPEN_ACTIVITY"
            ],
            "data" => [
                "title" => $strTitle,
                "body" => $strBody,
                "message" => $strBody,
                "url" => $strUrl,
                "click_url" => $strUrl,
                "image" => $strImage
            ]
        ],
        "webpush" => [
            "headers" => [
                "Urgency" => "high"
            ],
            "notification" => [
                "title" => $strTitle,
                "body" => $strBody,
                "icon" => $iconUrl,
                "badge" => $iconUrl,
                "requireInteraction" => true
            ],
            "fcm_options" => [
                "link" => $strUrl ?: $baseUrl . '/'
            ]
        ],
        "apns" => [
            "headers" => [
                "apns-priority" => "10"
            ],
            "payload" => [
                "aps" => [
                    "alert" => [
                        "title" => $strTitle,
                        "body" => $strBody
                    ],
                    "sound" => "default",
                    "badge" => 1,
                    "content-available" => 1
                ]
            ]
        ]
    ];

    if (!empty($strImage)) {
        $message['notification']['image'] = $strImage;
        $message['android']['notification']['image'] = $strImage;
        $message['webpush']['notification']['image'] = $strImage;
    }

    return json_encode(["message" => $message], JSON_UNESCAPED_SLASHES);
}

/**
 * Dispatch single FCM HTTP v1 Message to target token or FCM Topic (identical to Firebase Console Compose)
 */
function sendFcmNotificationCore($target, $title, $body, $image = null, $url = null, $category = 'General', $isTopic = false) {
    try {
        $accessToken = getFirebaseAccessToken();
    } catch (Exception $e) {
        return [
            'success' => false,
            'http_code' => 500,
            'unregistered' => false,
            'error' => $e->getMessage()
        ];
    }

    $projectId = getFirebaseProjectId();
    $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    $jsonPayload = buildFcmMessagePayload($target, $title, $body, $image, $url, $category, $isTopic);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json; UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    if ($httpCode === 200 && isset($resData['name'])) {
        return ['success' => true, 'name' => $resData['name'], 'response' => $resData];
    }

    $isUnregistered = false;
    if (isset($resData['error']['details'])) {
        foreach ($resData['error']['details'] as $detail) {
            if (isset($detail['errorCode']) && in_array($detail['errorCode'], ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'])) {
                $isUnregistered = true;
                break;
            }
        }
    }
    if (isset($resData['error']['status']) && in_array($resData['error']['status'], ['NOT_FOUND', 'UNAUTHENTICATED'])) {
        if (strpos(strtolower($response), 'unregistered') !== false || strpos(strtolower($response), 'not_found') !== false) {
            $isUnregistered = true;
        }
    }

    return [
        'success' => false,
        'http_code' => $httpCode,
        'unregistered' => $isUnregistered,
        'error' => $resData['error']['message'] ?? ('FCM HTTP ' . $httpCode . ' Dispatch Failure: ' . $response),
        'raw' => $response
    ];
}

/**
 * Dispatch FCM messages to multiple targets concurrently via curl_multi (Ultra-Fast)
 */
function sendFcmMultiTargets(array $targets, $title, $body, $image = null, $url = null, $category = 'General', $isTopic = false) {
    if (empty($targets)) return ['success_count' => 0, 'failed_count' => 0, 'results' => []];

    try {
        $accessToken = getFirebaseAccessToken();
    } catch (Exception $e) {
        return ['success_count' => 0, 'failed_count' => count($targets), 'error' => $e->getMessage()];
    }

    $projectId = getFirebaseProjectId();
    $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    $mh = curl_multi_init();
    $curlHandles = [];

    foreach ($targets as $idx => $t) {
        $cleanTarget = trim($t);
        if (empty($cleanTarget)) continue;

        $jsonPayload = buildFcmMessagePayload($cleanTarget, $title, $body, $image, $url, $category, $isTopic);
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; UTF-8'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_multi_add_handle($mh, $ch);
        $curlHandles[$idx] = ['ch' => $ch, 'target' => $cleanTarget];
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }

    $successCount = 0;
    $failedCount = 0;
    $results = [];

    foreach ($curlHandles as $idx => $item) {
        $ch = $item['ch'];
        $resp = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        if ($code === 200 && isset($data['name'])) {
            $successCount++;
            $results[$item['target']] = ['success' => true, 'name' => $data['name']];
        } else {
            $failedCount++;
            $results[$item['target']] = ['success' => false, 'error' => $data['error']['message'] ?? $resp];
        }
    }

    curl_multi_close($mh);
    return ['success_count' => $successCount, 'failed_count' => $failedCount, 'results' => $results];
}

/**
 * Dispatch FCM Notification to Single Device Token
 */
function sendSingleFcmNotification($targetToken, $title, $body, $image = null, $url = null, $category = 'General') {
    return sendFcmNotificationCore($targetToken, $title, $body, $image, $url, $category, false);
}

/**
 * Dispatch FCM Notification to Topic
 */
function sendTopicFcmNotification($topicName, $title, $body, $image = null, $url = null, $category = 'General') {
    return sendFcmNotificationCore($topicName, $title, $body, $image, $url, $category, true);
}

/**
 * Broadcast FCM Campaign across all standard Shiaho WebToApp Android APK topics concurrently
 */
function broadcastFcmCampaignToAllTopics($title, $body, $image = null, $url = null, $category = 'General') {
    $topics = [
        'all',
        'global',
        'news',
        'notice',
        'android',
        'all_users',
        'general',
        'broadcast',
        'fcm_broadcast',
        'abss',
        'abss_notification',
        'abss_all',
        'app',
        'users'
    ];
    $result = sendFcmMultiTargets($topics, $title, $body, $image, $url, $category, true);
    return ($result['success_count'] ?? 0) > 0;
}
