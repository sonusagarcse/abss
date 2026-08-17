<?php
// includes/notification_helper.php - In-Built Web Portal Notifications System

require_once __DIR__ . '/../config/db.php';

/**
 * Dispatch an in-built portal notification
 * 
 * @param string $type ('bill', 'result', 'photo', 'video', 'notice')
 * @param string $title
 * @param string $message
 * @param string $target_url
 * @param int|null $parent_id (null for all parents)
 * @param int|null $student_id
 * @param string|null $icon
 * @param string|null $badge_color
 * @return int|bool Inserted notification ID or false
 */
function create_portal_notification($type, $title, $message, $target_url, $parent_id = null, $student_id = null, $icon = null, $badge_color = null) {
    $conn = getDB();
    if (!$conn) return false;

    // Smart default icons & theme badge colors
    $type_defaults = [
        'bill' => [
            'icon' => 'fa-file-invoice-dollar',
            'color' => '#dc2626'
        ],
        'result' => [
            'icon' => 'fa-award',
            'color' => '#7c3aed'
        ],
        'photo' => [
            'icon' => 'fa-camera-retro',
            'color' => '#2563eb'
        ],
        'video' => [
            'icon' => 'fa-play-circle',
            'color' => '#ea580c'
        ],
        'notice' => [
            'icon' => 'fa-bullhorn',
            'color' => '#16a34a'
        ]
    ];

    $icon = $icon ?: ($type_defaults[$type]['icon'] ?? 'fa-bell');
    $badge_color = $badge_color ?: ($type_defaults[$type]['color'] ?? '#4f46e5');

    $stmt = $conn->prepare("
        INSERT INTO portal_notifications 
        (parent_id, student_id, type, title, message, icon, target_url, badge_color) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) return false;

    $pid_val = $parent_id !== null ? (int)$parent_id : null;
    $sid_val = $student_id !== null ? (int)$student_id : null;

    $stmt->bind_param("iissssss", $pid_val, $sid_val, $type, $title, $message, $icon, $target_url, $badge_color);
    $res = $stmt->execute();
    $insert_id = $res ? $stmt->insert_id : false;
    $stmt->close();

    return $insert_id;
}

/**
 * Fetch latest notifications for a parent
 */
function get_parent_notifications($parent_id, $limit = 20) {
    $conn = getDB();
    if (!$conn) return [];

    $parent_id = (int)$parent_id;
    $limit = (int)$limit;

    $sql = "
        SELECT 
            n.*,
            IF(nr.id IS NOT NULL, 1, 0) AS is_read,
            s.name AS student_name
        FROM portal_notifications n
        LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.parent_id = $parent_id
        LEFT JOIN students s ON s.id = n.student_id
        WHERE (n.parent_id IS NULL OR n.parent_id = 0 OR n.parent_id = $parent_id)
        ORDER BY n.created_at DESC, n.id DESC
        LIMIT $limit
    ";

    $res = $conn->query($sql);
    $notifications = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['time_ago'] = format_time_ago($row['created_at']);
            $row['is_read'] = (int)$row['is_read'];
            $notifications[] = $row;
        }
    }

    return $notifications;
}

/**
 * Get unread notification count for a parent
 */
function get_unread_notifications_count($parent_id) {
    $conn = getDB();
    if (!$conn) return 0;

    $parent_id = (int)$parent_id;

    $sql = "
        SELECT COUNT(*) AS unread_count
        FROM portal_notifications n
        LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.parent_id = $parent_id
        WHERE (n.parent_id IS NULL OR n.parent_id = 0 OR n.parent_id = $parent_id)
          AND nr.id IS NULL
    ";

    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['unread_count'];
    }

    return 0;
}

/**
 * Mark a specific notification as read
 */
function mark_notification_as_read($notification_id, $parent_id) {
    $conn = getDB();
    if (!$conn) return false;

    $nid = (int)$notification_id;
    $pid = (int)$parent_id;

    $stmt = $conn->prepare("INSERT IGNORE INTO notification_reads (notification_id, parent_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $nid, $pid);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
    return false;
}

/**
 * Mark all active notifications as read for a parent
 */
function mark_all_notifications_read($parent_id) {
    $conn = getDB();
    if (!$conn) return false;

    $pid = (int)$parent_id;

    $sql = "
        INSERT IGNORE INTO notification_reads (notification_id, parent_id)
        SELECT n.id, $pid
        FROM portal_notifications n
        WHERE (n.parent_id IS NULL OR n.parent_id = 0 OR n.parent_id = $pid)
    ";

    return (bool)$conn->query($sql);
}

/**
 * Format timestamp into human-readable relative time
 */
function format_time_ago($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . 'm ago';
    if ($diff < 86400) return round($diff / 3600) . 'h ago';
    if ($diff < 604800) return round($diff / 86400) . 'd ago';
    
    return date('d M, Y', $time);
}
?>
