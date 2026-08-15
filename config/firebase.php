<?php
// config/firebase.php - Pure PHP Firebase HTTP v1 API & OAuth 2.0 Auth Engine for ABSS / LKVM
require_once __DIR__ . '/db.php';

/**
 * FCM Centralized Activity & Error Logger
 */
function logFcmEvent($action, $details = [], $status = 'INFO', $httpCode = null, $error = null) {
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/fcm.log';
        $timestamp = date('Y-m-d H:i:s');
        $codeStr = $httpCode !== null ? " [HTTP $httpCode]" : "";
        $errStr  = $error ? " | Error: " . (is_string($error) ? $error : json_encode($error)) : "";
        $detailStr = !empty($details) ? " | Data: " . (is_string($details) ? $details : json_encode($details, JSON_UNESCAPED_SLASHES)) : "";

        $line = "[$timestamp] [$status] [$action]$codeStr$detailStr$errStr" . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("FCM Logging Error: " . $e->getMessage());
    }
}

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
        $errMsg = "Firebase Service Account JSON file (config/service-account.json) is missing.";
        logFcmEvent('oauth_token_generation', ['path' => $saPath], 'ERROR', 500, $errMsg);
        throw new Exception($errMsg);
    }

    $saData = json_decode(file_get_contents($saPath), true);
    if (!$saData || empty($saData['private_key']) || empty($saData['client_email']) || empty($saData['project_id'])) {
        $errMsg = "Invalid Firebase Service Account JSON format in config/service-account.json.";
        logFcmEvent('oauth_token_generation', ['client_email' => $saData['client_email'] ?? ''], 'ERROR', 500, $errMsg);
        throw new Exception($errMsg);
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
        $errMsg = "OpenSSL JWT signature generation failed. Check PHP OpenSSL extension.";
        logFcmEvent('oauth_token_generation', [], 'ERROR', 500, $errMsg);
        throw new Exception($errMsg);
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
        $errMsg = "OAuth 2.0 Token Exchange failed (HTTP $httpCode): " . $response;
        logFcmEvent('oauth_token_generation', ['response' => $response], 'ERROR', $httpCode, $errMsg);
        throw new Exception($errMsg);
    }

    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        $errMsg = "Invalid OAuth token response from Google APIs: " . $response;
        logFcmEvent('oauth_token_generation', ['response' => $response], 'ERROR', $httpCode, $errMsg);
        throw new Exception($errMsg);
    }

    $_SESSION['fcm_access_token'] = $tokenData['access_token'];
    $_SESSION['fcm_access_token_expires'] = time() + 3300;

    logFcmEvent('oauth_token_generation', ['expires_in' => $tokenData['expires_in'] ?? 3600], 'SUCCESS', 200);

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
            "imageUrl" => $strImage,
            "clickUrl" => $strUrl,
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
                "channel_id" => "fcm_notification_channel"
            ],
            "data" => [
                "title" => $strTitle,
                "body" => $strBody,
                "message" => $strBody,
                "url" => $strUrl,
                "click_url" => $strUrl,
                "clickUrl" => $strUrl,
                "image" => $strImage,
                "imageUrl" => $strImage
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
 * Dispatch single FCM HTTP v1 Message to target token or FCM Topic
 */
function sendFcmNotificationCore($target, $title, $body, $image = null, $url = null, $category = 'General', $isTopic = false) {
    try {
        $accessToken = getFirebaseAccessToken();
    } catch (Exception $e) {
        logFcmEvent('fcm_dispatch_error', ['target' => $target, 'is_topic' => $isTopic], 'ERROR', 500, $e->getMessage());
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
        logFcmEvent('fcm_dispatch_success', [
            'target' => $isTopic ? "topic/$target" : substr($target, 0, 20) . "...",
            'message_id' => $resData['name']
        ], 'SUCCESS', 200);

        return ['success' => true, 'name' => $resData['name'], 'http_code' => 200, 'response' => $resData];
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

    $errMessage = $resData['error']['message'] ?? ('FCM HTTP ' . $httpCode . ' Dispatch Failure: ' . $response);
    logFcmEvent('fcm_dispatch_failure', [
        'target' => $isTopic ? "topic/$target" : substr($target, 0, 20) . "...",
        'response' => $resData ?? $response
    ], 'ERROR', $httpCode, $errMessage);

    return [
        'success' => false,
        'http_code' => $httpCode,
        'unregistered' => $isUnregistered,
        'error' => $errMessage,
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
        logFcmEvent('fcm_multi_dispatch_error', ['total_targets' => count($targets)], 'ERROR', 500, $e->getMessage());
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
            $results[$item['target']] = ['success' => true, 'name' => $data['name'], 'http_code' => 200];
        } else {
            $failedCount++;
            $results[$item['target']] = ['success' => false, 'error' => $data['error']['message'] ?? $resp, 'http_code' => $code];
        }
    }

    curl_multi_close($mh);

    logFcmEvent('fcm_multi_dispatch_complete', [
        'total' => count($targets),
        'success' => $successCount,
        'failed' => $failedCount
    ], $failedCount === 0 ? 'SUCCESS' : 'WARN', 200);

    return ['success_count' => $successCount, 'failed_count' => $failedCount, 'results' => $results];
}

/**
 * Subscribe one or multiple FCM Tokens to a Topic using Google IID / Firebase Topic Management API
 */
function subscribeFcmTokensToTopic($tokens, $topic = 'all') {
    if (is_string($tokens)) {
        $tokens = [$tokens];
    }
    $tokens = array_filter(array_map('trim', $tokens));
    if (empty($tokens)) return false;

    try {
        $accessToken = getFirebaseAccessToken();
    } catch (Exception $e) {
        logFcmEvent('topic_subscription_error', ['tokens_count' => count($tokens), 'topic' => $topic], 'ERROR', 500, $e->getMessage());
        return false;
    }

    $cleanTopic = strpos($topic, '/topics/') === 0 ? $topic : '/topics/' . $topic;
    $endpoint = 'https://iid.googleapis.com/iid/v1:batchAdd';

    $payload = json_encode([
        'to' => $cleanTopic,
        'registration_tokens' => array_values($tokens)
    ]);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'access_token_auth: true',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respData = json_decode($resp, true);
    $isSuccess = ($code === 200);

    logFcmEvent('topic_subscription_result', [
        'topic' => $cleanTopic,
        'tokens_count' => count($tokens),
        'results' => $respData['results'] ?? $resp
    ], $isSuccess ? 'SUCCESS' : 'ERROR', $code, $isSuccess ? null : $resp);

    return $isSuccess;
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
 * Broadcast FCM Campaign across all standard Android APK topics concurrently
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
    return $result;
}
