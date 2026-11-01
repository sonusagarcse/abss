<?php
/**
 * security/includes/block_engine.php
 * Core IP blocking, whitelisting, and threat analysis engine.
 * Prepared statements only — strictly zero SQL string interpolation.
 */

/**
 * Check if an IP is currently blocked. Also auto-expires stale blocks.
 */
function is_ip_blocked(string $ip, $conn): bool {
    // 1. Auto-expire any block whose timer has expired
    $exp_stmt = $conn->prepare("UPDATE ip_blocked SET is_active = 0 WHERE is_active = 1 AND expires_at <= NOW()");
    if ($exp_stmt) {
        $exp_stmt->execute();
        $exp_stmt->close();
    }

    // 2. Query for active valid block
    $stmt = $conn->prepare("SELECT id FROM ip_blocked WHERE ip_address = ? AND is_active = 1 AND expires_at > NOW() LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->store_result();
    $blocked = ($stmt->num_rows > 0);
    $stmt->close();
    return $blocked;
}

/**
 * Check if an IP is in the whitelist (never block these IPs).
 */
function is_ip_whitelisted(string $ip, $conn): bool {
    $stmt = $conn->prepare("SELECT id FROM ip_whitelist WHERE ip_address = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->store_result();
    $found = ($stmt->num_rows > 0);
    $stmt->close();
    return $found;
}

/**
 * Log a request to ip_threat_log.
 */
function log_threat_request(string $ip, string $uri, string $ua, string $method, int $is_suspicious, $conn): void {
    $stmt = $conn->prepare("INSERT INTO ip_threat_log (ip_address, request_uri, user_agent, request_method, is_suspicious) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) return;
    $stmt->bind_param("ssssi", $ip, $uri, $ua, $method, $is_suspicious);
    $stmt->execute();
    $stmt->close();
}

/**
 * Analyze recent request patterns and auto-block if threshold exceeded.
 *
 * Thresholds (within sliding 60-second window):
 *   - 10+ unknown / 404 / probe endpoints scanned -> Auto-block for 5 min
 *   - OR 20+ distinct URLs scanned rapidly -> Auto-block for 5 min
 *
 * Returns true if the IP was newly blocked.
 */
function analyze_and_auto_block(
    string $ip,
    $conn,
    int $unknown_threshold = 10,
    int $total_threshold = 30,
    int $window_sec = 60,
    int $block_min = 5
): bool {
    // Count distinct URIs and suspicious hits from this IP in the sliding window
    $stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT request_uri) AS url_count,
                COALESCE(SUM(is_suspicious), 0) AS suspicious_count
         FROM ip_threat_log
         WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)"
    );
    if (!$stmt) return false;
    $stmt->bind_param("si", $ip, $window_sec);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $unique_urls      = (int)($row['url_count'] ?? 0);
    $suspicious_count = (int)($row['suspicious_count'] ?? 0);

    // If neither threshold has been crossed, allow request through
    if ($suspicious_count < $unknown_threshold && $unique_urls < $total_threshold) {
        return false;
    }

    // Determine descriptive reason
    if ($suspicious_count >= $unknown_threshold) {
        $reason = "Automated: Rapid scanning of {$suspicious_count} unknown/suspicious endpoints in {$window_sec}s";
    } else {
        $reason = "Automated: Rapid URL enumeration ({$unique_urls} unique URLs) in {$window_sec}s";
    }

    // Insert or update active block entry with exact 5-minute expiry
    $ins = $conn->prepare(
        "INSERT INTO ip_blocked (ip_address, reason, blocked_by, blocked_at, expires_at, is_active, unique_urls_count)
         VALUES (?, ?, 'auto', NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), 1, ?)
         ON DUPLICATE KEY UPDATE
           reason = VALUES(reason),
           blocked_by = 'auto',
           blocked_at = NOW(),
           expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
           is_active = 1,
           unblocked_at = NULL,
           unique_urls_count = VALUES(unique_urls_count)"
    );
    if (!$ins) return false;
    $ins->bind_param("ssiii", $ip, $reason, $block_min, $unique_urls, $block_min);
    $ins->execute();
    $ins->close();

    // Log security event to dedicated log file
    $log_dir = __DIR__ . '/../../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $log_entry = date('Y-m-d H:i:s') . " [IP-BLOCK-AUTO] IP: {$ip} | Suspicious: {$suspicious_count} | UniqueURLs: {$unique_urls} | Window: {$window_sec}s | Duration: {$block_min}m\n";
    @file_put_contents($log_dir . '/ip_firewall.log', $log_entry, FILE_APPEND | LOCK_EX);

    return true;
}

/**
 * Probabilistic cleanup: delete ip_threat_log entries older than 24h.
 * Runs on ~2% of requests to prevent database bloat.
 */
function maybe_cleanup_threat_log($conn): void {
    if (mt_rand(1, 50) !== 1) return;
    $conn->query("DELETE FROM ip_threat_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

/**
 * Manual admin block: block an IP for $minutes minutes.
 */
function admin_block_ip(string $ip, int $minutes, string $reason, int $admin_id, $conn): bool {
    $stmt = $conn->prepare(
        "INSERT INTO ip_blocked (ip_address, reason, blocked_by, admin_id, blocked_at, expires_at, is_active)
         VALUES (?, ?, 'admin', ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), 1)
         ON DUPLICATE KEY UPDATE
           reason = VALUES(reason),
           blocked_by = 'admin',
           admin_id = VALUES(admin_id),
           blocked_at = NOW(),
           expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
           is_active = 1,
           unblocked_at = NULL"
    );
    if (!$stmt) return false;
    $stmt->bind_param("ssiii", $ip, $reason, $admin_id, $minutes, $minutes);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

/**
 * Admin unblock: deactivate an active IP block immediately.
 */
function admin_unblock_ip(string $ip, $conn): bool {
    $stmt = $conn->prepare("UPDATE ip_blocked SET is_active = 0, unblocked_at = NOW() WHERE ip_address = ? AND is_active = 1");
    if (!$stmt) return false;
    $stmt->bind_param("s", $ip);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

/**
 * Add IP to whitelist.
 */
function admin_whitelist_ip(string $ip, string $label, int $admin_id, $conn): bool {
    $stmt = $conn->prepare("INSERT IGNORE INTO ip_whitelist (ip_address, label, added_by) VALUES (?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("ssi", $ip, $label, $admin_id);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

/**
 * Remove IP from whitelist.
 */
function admin_remove_whitelist(string $ip, $conn): bool {
    $stmt = $conn->prepare("DELETE FROM ip_whitelist WHERE ip_address = ?");
    if (!$stmt) return false;
    $stmt->bind_param("s", $ip);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}
