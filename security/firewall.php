<?php
/**
 * security/firewall.php — IP Scanning Detection & Auto-Block Middleware
 *
 * ABSS Institute Management Portal
 * Self-contained Security Module
 */

// Prevent double-execution in a single request lifecycle
if (defined('ABSS_FIREWALL_LOADED')) {
    return;
}
define('ABSS_FIREWALL_LOADED', true);

require_once __DIR__ . '/includes/ip_helper.php';
require_once __DIR__ . '/includes/block_engine.php';
require_once __DIR__ . '/../config/db.php';

// ── 0. Failsafe: Never block admin login or firewall control panel itself ─────
$current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (
    strpos($current_script, 'admin/login') !== false ||
    strpos($current_script, 'admin/ip_firewall') !== false ||
    strpos($current_script, 'security/admin_ip_blocks') !== false ||
    strpos($current_script, 'security/ajax_firewall') !== false
) {
    return;
}

// ── 1. Check if authenticated admin (Admins are always protected) ─────────────
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!empty($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] > 0) {
    // Authenticated administrator desk is unconditionally immune
    return;
}

// ── 2. Extract Client IP & Request Details ────────────────────────────────────
$fw_ip     = get_real_client_ip();
$fw_uri    = $_SERVER['REQUEST_URI'] ?? '/';
$fw_ua     = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$fw_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ── 3. Database connection (safe fallback) ────────────────────────────────────
try {
    $fw_conn = getDB();
} catch (Exception $e) {
    // Fail-open: if DB is temporarily unreachable, do not halt customer traffic
    error_log("[ABSS Firewall] DB Error: " . $e->getMessage());
    return;
}

// ── 4. Whitelist Check ────────────────────────────────────────────────────────
if (is_ip_whitelisted($fw_ip, $fw_conn)) {
    return;
}

// ── 5. Check if IP is currently blocked ───────────────────────────────────────
if (is_ip_blocked($fw_ip, $fw_conn)) {
    render_blocked_page($fw_ip, $fw_conn);
    exit();
}

// ── 5.5. Completely Ignore Static Assets & Sub-resources ──────────────────────
// Critical False-Positive Prevention:
// When a page loads, the browser automatically fetches 15-30 images, CSS, JS, and fonts.
// These must NEVER be counted as URL scanning or trigger any rate limiting!
$fw_path = parse_url($fw_uri, PHP_URL_PATH) ?? '';
$fw_ext  = strtolower(pathinfo($fw_path, PATHINFO_EXTENSION));

$static_extensions = [
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'bmp', 'tiff',
    'css', 'js', 'map', 'json',
    'woff', 'woff2', 'ttf', 'eot', 'otf',
    'mp4', 'webm', 'ogg', 'mp3', 'wav',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'apk'
];

// If file extension is a static asset, skip immediately without logging or blocking
if (in_array($fw_ext, $static_extensions, true)) {
    return;
}

// Browser Sec-Fetch-Dest header check (elements loaded by the page like <img>, <link>, <script>)
if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && in_array(strtolower($_SERVER['HTTP_SEC_FETCH_DEST']), ['image', 'style', 'script', 'font', 'video', 'audio', 'manifest'], true)) {
    return;
}

// Assets & Uploads folder sub-resources (unless targeting an executable script)
if ($fw_ext !== 'php' && (
    strpos($fw_path, '/assets/') !== false ||
    strpos($fw_path, '/uploads/') !== false ||
    strpos($fw_path, '/images/') !== false ||
    strpos($fw_path, '/css/') !== false ||
    strpos($fw_path, '/js/') !== false
)) {
    return;
}

// ── 6. Filter Benign Bots ─────────────────────────────────────────────────────
// Search engine crawlers (Googlebot, Bingbot) are logged but not blocked
if (is_good_bot($fw_ua)) {
    log_threat_request($fw_ip, $fw_uri, $fw_ua, $fw_method, 0, $fw_conn);
    return;
}

// ── 7. Detect Suspicious Request Signature ────────────────────────────────────
$is_suspicious = 0;
if (defined('IS_404_PAGE') || http_response_code() === 404) {
    $is_suspicious = 1;
}

// Probing signatures for vulnerability / credential scanning
$probe_signatures = [
    '.env', 'wp-admin', 'wp-login', 'phpmyadmin', 'eval-stdin', 'shell', 
    'phpunit', 'vendor/', 'backup', '.git', 'config.php', 'passwd', 'etc/shadow',
    'setup.php', 'actuator', 'swagger', 'console', 'solr', 'boaform', 'autodiscover'
];
foreach ($probe_signatures as $sig) {
    if (stripos($fw_uri, $sig) !== false) {
        $is_suspicious = 1;
        break;
    }
}

// ── 8. Log the request ────────────────────────────────────────────────────────
log_threat_request($fw_ip, $fw_uri, $fw_ua, $fw_method, $is_suspicious, $fw_conn);

// ── 9. Analyze & Trigger 5-Minute Auto-Block if Threshold Exceeded ────────────
$newly_blocked = analyze_and_auto_block($fw_ip, $fw_conn);
if ($newly_blocked) {
    render_blocked_page($fw_ip, $fw_conn);
    exit();
}

// ── 10. Periodic Background Maintenance (1 in 50 requests) ────────────────────
maybe_cleanup_threat_log($fw_conn);


/**
 * Helper to render polished, simple, and friendly HTTP 403 block page
 * Designed to be easily understood by parents, students, and regular users.
 */
function render_blocked_page(string $ip, $conn): void {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    
    // Fetch remaining block time
    $mins_left = 5;
    $secs_left = 300;
    $reason = 'Too many rapid requests';
    
    $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as mins_left, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as secs_left, reason FROM ip_blocked WHERE ip_address = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res) {
            $mins_left = max(1, (int)$res['mins_left']);
            $secs_left = max(10, (int)$res['secs_left']);
            $reason = htmlspecialchars($res['reason'] ?? 'Too many rapid requests');
        }
    }
    
    $clean_ip = htmlspecialchars($ip);
    $base_url = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Please Wait — Access Temporarily Paused | ABSS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', -apple-system, sans-serif; }
        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .friendly-card {
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08), 0 4px 12px rgba(15, 23, 42, 0.04);
            border: 1px solid #e2e8f0;
            max-width: 520px;
            width: 100%;
            padding: 42px 34px;
            text-align: center;
            animation: popIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 24px;
        }
        .brand-badge img {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }
        .brand-badge span {
            font-size: 0.8rem;
            font-weight: 800;
            color: #475569;
            letter-spacing: 0.02em;
        }
        .status-avatar {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            background: #fef3c7;
            color: #d97706;
            border: 3px solid #fde68a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.3rem;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.15);
        }
        h1 {
            font-size: 1.65rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }
        .sub-tag {
            font-size: 0.96rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 22px;
            line-height: 1.5;
        }
        .timer-box {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 18px 24px;
            margin-bottom: 22px;
        }
        .timer-box small {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }
        .countdown-digits {
            font-size: 2.1rem;
            font-weight: 900;
            color: #d97706;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            letter-spacing: 0.04em;
        }
        .hint-text {
            color: #475569;
            font-size: 0.88rem;
            line-height: 1.55;
            margin-bottom: 24px;
        }
        .hindi-note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 24px;
            line-height: 1.45;
        }
        .info-pill-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .info-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-refresh {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: #2563eb;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.94rem;
            padding: 13px 20px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-refresh:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .footer-note {
            margin-top: 18px;
            font-size: 0.78rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="friendly-card">
        <!-- School Brand Badge -->
        <div class="brand-badge">
            <img src="<?= $base_url ?>/assets/logo.png" alt="ABSS" onerror="this.style.display='none'">
            <span>ABSS Institute Security Protection</span>
        </div>

        <!-- Friendly Status Icon -->
        <div class="status-avatar">
            <i class="fas fa-hourglass-half"></i>
        </div>

        <!-- Friendly Heading -->
        <h1>Access Temporarily Paused</h1>
        <p class="sub-tag">
            Too many rapid requests or page clicks were detected from your connection.
        </p>

        <!-- Live Real-Time Countdown Box -->
        <div class="timer-box">
            <small><i class="fas fa-clock"></i> Resuming Automatically In</small>
            <div class="countdown-digits" id="timerClock">05:00</div>
        </div>

        <!-- Simple Hindi Helper Note -->
        <div class="hindi-note">
            <i class="fas fa-info-circle"></i> <strong>चिंता न करें:</strong> सुरक्षा कारणों से साइट 5 मिनट के लिए रुकी है। यह समय पूरा होते ही अपने आप खुल जाएगी।
        </div>

        <!-- Transparent Info Pills -->
        <div class="info-pill-row">
            <span class="info-pill"><i class="fas fa-wifi"></i> IP: <?= $clean_ip ?></span>
            <span class="info-pill"><i class="fas fa-shield"></i> Code: 403 Paused</span>
        </div>

        <!-- Refresh Button -->
        <button class="btn-refresh" onclick="window.location.reload();">
            <i class="fas fa-rotate-right"></i> Check &amp; Reload Page
        </button>

        <p class="footer-note">
            If you are an administrator, log into your <a href="<?= $base_url ?>/admin/login" style="color:#2563eb;text-decoration:none;font-weight:700;">Admin Desk</a> to manage settings.
        </p>
    </div>

    <script>
    // Real-time ticking countdown in seconds
    let secondsLeft = <?= (int)$secs_left ?>;
    const timerElem = document.getElementById('timerClock');

    function updateClock() {
        if (secondsLeft <= 0) {
            timerElem.innerText = '00:00';
            timerElem.style.color = '#10b981';
            // Auto-reload to restore access once 5 minutes complete
            setTimeout(() => { window.location.reload(); }, 1200);
            return;
        }
        const m = Math.floor(secondsLeft / 60);
        const s = secondsLeft % 60;
        timerElem.innerText = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        secondsLeft--;
    }

    updateClock();
    setInterval(updateClock, 1000);
    </script>
</body>
</html>
    <?php
}
