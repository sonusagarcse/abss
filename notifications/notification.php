<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, title, message, url FROM notifications WHERE status = 1 AND id > ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $last_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response = [
            "status" => true,
            "id" => (int)$row['id'],
            "title" => $row['title'],
            "message" => $row['message'],
            "url" => $row['url'] ? $row['url'] : null
        ];
    } else {
        $response = [
            "status" => false
        ];
    }
} catch (Exception $e) {
    $response = [
        "status" => false,
        "error" => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
