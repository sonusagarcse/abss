<?php
/**
 * security/ajax_firewall.php
 * AJAX endpoint for admin IP firewall actions.
 * Requires administrator authentication & CSRF validation.
 */
require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/includes/ip_helper.php';
require_once __DIR__ . '/includes/block_engine.php';

header('Content-Type: application/json; charset=UTF-8');

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed. Please refresh the page.']);
    exit();
}

$action = trim($_POST['action'] ?? '');
$admin_id = (int)($_SESSION['admin_id'] ?? 1);

// Test Simulation Action
if ($action === 'simulate_test_scan') {
    $test_ip = trim($_POST['test_ip'] ?? '198.51.100.88');
    if (!filter_var($test_ip, FILTER_VALIDATE_IP)) {
        $test_ip = '198.51.100.88';
    }

    // Clean previous logs for this test IP
    $del = $conn->prepare("DELETE FROM ip_threat_log WHERE ip_address = ?");
    if ($del) { $del->bind_param("s", $test_ip); $del->execute(); $del->close(); }
    $del2 = $conn->prepare("DELETE FROM ip_blocked WHERE ip_address = ?");
    if ($del2) { $del2->bind_param("s", $test_ip); $del2->execute(); $del2->close(); }

    // Simulate 12 rapid URL scans to unknown / serverhost endpoints
    $simulated_uris = [
        '/abss/xyz_probe_1',
        '/abss/admin/xyz_test',
        '/abss/.env',
        '/abss/wp-login.php',
        '/abss/serverhost/scan_01',
        '/abss/serverhost/scan_02',
        '/abss/config/backup.sql',
        '/abss/shell.php',
        '/abss/serverhost/test_endpoint',
        '/abss/vendor/phpunit',
        '/abss/serverhost/xyz_final',
        '/abss/admin/unauthorized_scan'
    ];

    $logged = 0;
    foreach ($simulated_uris as $uri) {
        log_threat_request($test_ip, $uri, 'Mozilla/5.0 SecurityTester/1.0', 'GET', 1, $conn);
        $logged++;
    }

    // Trigger analysis
    $blocked = analyze_and_auto_block($test_ip, $conn, 10, 20, 60, 5);

    echo json_encode([
        'success' => $blocked,
        'test_ip' => $test_ip,
        'scans_logged' => $logged,
        'message' => $blocked 
            ? "Test Successful! Simulated {$logged} unknown scans from IP {$test_ip}. IP was AUTO-BLOCKED for 5 minutes." 
            : "Simulated {$logged} requests, but auto-block condition was not met."
    ]);
    exit();
}

// IP-specific actions
$ip_raw = trim($_POST['ip'] ?? '');
if (!filter_var($ip_raw, FILTER_VALIDATE_IP)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid IPv4 or IPv6 address.']);
    exit();
}
$ip = $ip_raw;

switch ($action) {
    case 'block_ip':
        $minutes = max(1, min(10080, (int)($_POST['minutes'] ?? 60))); // 1 min to 7 days
        $reason  = substr(strip_tags($_POST['reason'] ?? 'Manual admin block'), 0, 255);
        $ok = admin_block_ip($ip, $minutes, $reason, $admin_id, $conn);
        echo json_encode(['success' => $ok, 'message' => $ok ? "IP {$ip} has been manually blocked for {$minutes} minutes." : 'Failed to block IP.']);
        break;

    case 'unblock_ip':
        $ok = admin_unblock_ip($ip, $conn);
        echo json_encode(['success' => $ok, 'message' => $ok ? "IP {$ip} has been unblocked immediately." : 'Failed to unblock IP.']);
        break;

    case 'whitelist_ip':
        $label = substr(strip_tags($_POST['label'] ?? 'Trusted IP'), 0, 150);
        $ok = admin_whitelist_ip($ip, $label, $admin_id, $conn);
        // Also ensure it is unblocked if currently blocked
        admin_unblock_ip($ip, $conn);
        echo json_encode(['success' => $ok, 'message' => $ok ? "IP {$ip} added to whitelist (immune from blocking)." : 'Failed (already whitelisted?).']);
        break;

    case 'remove_whitelist':
        $ok = admin_remove_whitelist($ip, $conn);
        echo json_encode(['success' => $ok, 'message' => $ok ? "IP {$ip} removed from whitelist." : 'Failed to remove from whitelist.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
}
