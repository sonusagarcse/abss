<?php
// includes/tracker_helper.php - Global Visitor and Action Auditing Engine

// Guard against multiple inclusions
if (defined('ABSS_TRACKER_LOADED')) {
    return;
}
define('ABSS_TRACKER_LOADED', true);

// Ensure session is started safely
if (session_status() === PHP_SESSION_NONE) {
    if (file_exists(__DIR__ . '/security.php')) {
        require_once __DIR__ . '/security.php';
    } else {
        session_start();
    }
}

/**
 * Resolve client IP address supporting proxies
 */
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    
    // In case of multiple proxy IPs, extract the first one
    if (strpos($ipaddress, ',') !== false) {
        $ips = explode(',', $ipaddress);
        $ipaddress = trim($ips[0]);
    }
    return $ipaddress;
}

/**
 * Parse user agent to extract premium device metrics and browser labels
 */
function get_device_info($ua) {
    $ua = trim($ua);
    $browser = "Unknown Browser";
    $device = "Desktop";
    $icon = "fa-desktop";

    if (empty($ua)) {
        return ['browser' => $browser, 'device' => $device, 'icon' => $icon];
    }

    // Determine Browser
    if (preg_match('/Chrome/i', $ua)) {
        $browser = "Chrome";
    } elseif (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) {
        $browser = "Safari";
    } elseif (preg_match('/Firefox/i', $ua)) {
        $browser = "Firefox";
    } elseif (preg_match('/MSIE/i', $ua) || preg_match('/Trident/i', $ua)) {
        $browser = "Internet Explorer";
    } elseif (preg_match('/Edge/i', $ua)) {
        $browser = "Edge";
    } elseif (preg_match('/Opera/i', $ua) || preg_match('/OPR/i', $ua)) {
        $browser = "Opera";
    }

    // Determine Device
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
        $device = "Tablet";
        $icon = "fa-tablet-alt";
    } elseif (preg_match('/(mobi|ipod|iphone|blackberry|opera mini|fennec|minimo|symbian|psp|nintendo ds)/i', $ua)) {
        $device = "Mobile";
        $icon = "fa-mobile-alt";
    }

    return ['browser' => $browser, 'device' => $device, 'icon' => $icon];
}

/**
 * Record a visitor hit on the current page
 */
function track_visitor_hit() {
    // Avoid double tracking in same thread context
    if (defined('TRACKER_HIT_RECORDED')) {
        return;
    }
    define('TRACKER_HIT_RECORDED', true);

    try {
        $conn = getDB();
        $ip = get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Truncate user agent if excessive
        if (strlen($ua) > 255) {
            $ua = substr($ua, 0, 252) . '...';
        }
        
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strlen($referrer) > 512) {
            $referrer = substr($referrer, 0, 509) . '...';
        }
        
        // Clean and format current page URL
        $page = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($page, '?') !== false) {
            $parts = explode('?', $page);
            $page = $parts[0];
        }

        // Determine user session details
        $user_role = 'guest';
        $user_id = null;
        $parent_id = null;

        if (isset($_SESSION['admin_id'])) {
            $user_role = 'admin';
            $user_id = (int)$_SESSION['admin_id'];
        } elseif (isset($_SESSION['parent_id'])) {
            $user_role = 'parent';
            $parent_id = (int)$_SESSION['parent_id'];
        }

        $stmt = $conn->prepare("
            INSERT INTO site_visitors (ip_address, user_agent, referrer, page_visited, user_role, user_id, parent_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssii", $ip, $ua, $referrer, $page, $user_role, $user_id, $parent_id);
        $stmt->execute();
    } catch (Exception $e) {
        // Fail silently to prevent user disruptions, logs to local server log
        error_log("Tracker Hit Recording Failed: " . $e->getMessage());
    }
}

/**
 * Log a high-fidelity system audit action
 */
function log_activity($action_type, $action_details) {
    try {
        $conn = getDB();
        $ip = get_client_ip();
        
        // Determine role and account details
        $user_role = 'guest';
        $user_id = null;
        $username = 'Guest';

        if (isset($_SESSION['admin_id'])) {
            $user_role = 'admin';
            $user_id = (int)$_SESSION['admin_id'];
            $username = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? 'Administrator';
        } elseif (isset($_SESSION['parent_id'])) {
            $user_role = 'parent';
            $user_id = (int)$_SESSION['parent_id'];
            
            // Query parent email/name for better logs representation
            $parent_stmt = $conn->prepare("SELECT email FROM parents WHERE id = ?");
            $parent_stmt->bind_param("i", $user_id);
            $parent_stmt->execute();
            $p_res = $parent_stmt->get_result()->fetch_assoc();
            $username = $p_res['email'] ?? 'Parent ID ' . $user_id;
        }

        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_role, user_id, username, action_type, action_details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sissss", $user_role, $user_id, $username, $action_type, $action_details, $ip);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity Logging Failed: " . $e->getMessage());
    }
}

// Automatically track the hit immediately upon load
track_visitor_hit();
