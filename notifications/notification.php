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
    
    // Ensure index exists on database for sub-millisecond execution
    @$db->query("ALTER TABLE notifications ADD INDEX idx_status_id (status, id)");

    $stmt = $db->prepare("SELECT id, title, message, url, created_at FROM notifications WHERE status = 1 AND id > ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $last_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $defaultIcon = $protocol . $domainName . "/abss/assets/logo.png";

        $response = [
            "status" => true,
            "id" => (int)$row['id'],
            "title" => $row['title'],
            "message" => $row['message'],
            "url" => !empty($row['url']) ? $row['url'] : null,
            "icon" => $defaultIcon,
            "created_at" => $row['created_at']
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
