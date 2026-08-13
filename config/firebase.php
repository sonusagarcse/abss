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
    return 'abss-notification';
}

/**
 * Dispatch single FCM HTTP v1 Message matching exact Firebase Console Compose Notification format
 */
function sendSingleFcmNotification($targetToken, $title, $body, $image = null, $url = null, $category = 'General') {
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

    // Exact FCM HTTP v1 payload identical to Firebase Console Compose Notification
    $messagePayload = [
        "message" => [
            "token" => $targetToken,
            "notification" => [
                "title" => $title,
                "body" => $body
            ],
            "data" => [
                "title" => $title,
                "message" => $body,
                "body" => $body,
                "category" => $category,
                "click_url" => $url ?: '',
                "url" => $url ?: '',
                "image" => $image ?: ''
            ]
        ]
    ];

    if (!empty($image)) {
        $messagePayload['message']['notification']['image'] = $image;
    }

    $jsonPayload = json_encode($messagePayload, JSON_UNESCAPED_SLASHES);

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
