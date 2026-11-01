<?php
/**
 * security/includes/ip_helper.php
 * Cloudflare-aware, spoofing-resistant IP extractor.
 * Never blindly trusts X-Forwarded-For from non-trusted proxies.
 */

// Cloudflare published IPv4 ranges
define('CF_IP_RANGES', [
    '173.245.48.0/20','103.21.244.0/22','103.22.200.0/22','103.31.4.0/22',
    '141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20',
    '197.234.240.0/22','198.41.128.0/17','162.158.0.0/15','104.16.0.0/13',
    '104.24.0.0/14','172.64.0.0/13','131.0.72.0/22',
]);

/**
 * Check if $ip is within a CIDR range.
 */
function ip_in_cidr(string $ip, string $cidr): bool {
    [$subnet, $bits] = explode('/', $cidr);
    $ip_long     = ip2long($ip);
    $subnet_long = ip2long($subnet);
    if ($ip_long === false || $subnet_long === false) return false;
    $mask = $bits == 0 ? 0 : (~0 << (32 - (int)$bits));
    return ($ip_long & $mask) === ($subnet_long & $mask);
}

/**
 * Returns true if $ip is a Cloudflare edge node.
 */
function is_cloudflare_ip(string $ip): bool {
    foreach (CF_IP_RANGES as $cidr) {
        if (ip_in_cidr($ip, $cidr)) return true;
    }
    return false;
}

/**
 * Extract the real client IP, resistant to spoofing.
 * Priority: CF-Connecting-IP (only if REMOTE_ADDR is CF) → X-Forwarded-For (only if behind trusted proxy) → REMOTE_ADDR
 */
function get_real_client_ip(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // 1. If direct connection is from Cloudflare, trust CF-Connecting-IP
    if (is_cloudflare_ip($remote)) {
        $cf_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if ($cf_ip && filter_var($cf_ip, FILTER_VALIDATE_IP)) {
            return trim($cf_ip);
        }
    }

    // 2. If behind trusted local / reverse proxy, allow reading first valid X-Forwarded-For hop
    $is_private = ($remote === '::1' || $remote === '127.0.0.1') || array_reduce(['127.0.0.0/8','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16'], function($carry, $cidr) use ($remote) {
        return $carry || ip_in_cidr($remote, $cidr);
    }, false);

    if ($is_private && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        foreach ($forwarded_ips as $fip) {
            if (filter_var($fip, FILTER_VALIDATE_IP)) {
                return $fip;
            }
        }
    }

    // Default to direct socket address
    return $remote;
}

/**
 * Returns true if user agent string is a known benign search engine crawler.
 */
function is_good_bot(string $ua): bool {
    if (empty($ua)) return false;
    $good_bots = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'facebot', 'ia_archiver'];
    $ua_lower = strtolower($ua);
    foreach ($good_bots as $bot) {
        if (stripos($ua_lower, strtolower($bot)) !== false) return true;
    }
    return false;
}
