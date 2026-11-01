<?php
/**
 * admin/ip_firewall.php — IP Firewall & Rapid URL Scanning Blocker Control
 * ABSS Management Portal — Strictly Admin Only
 */
require_once 'includes/auth.php';
require_once __DIR__ . '/../security/includes/ip_helper.php';
require_once __DIR__ . '/../security/includes/block_engine.php';

$page_title = 'IP Firewall & Threat Control';
$csrf_token = generate_csrf_token();

$active_blocks   = [];
$expired_blocks  = [];
$whitelist       = [];
$threat_stats    = [];
$total_log_count = 0;

try {
    // 1. Auto-expire any expired blocks
    $conn->query("UPDATE ip_blocked SET is_active = 0 WHERE is_active = 1 AND expires_at <= NOW()");

    // 2. Active Blocks (Sorted latest first)
    $r = $conn->query("SELECT *, TIMESTAMPDIFF(MINUTE, NOW(), expires_at) AS mins_left FROM ip_blocked WHERE is_active = 1 ORDER BY blocked_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $active_blocks[] = $row;

    // 3. Expired/Historical Blocks
    $r2 = $conn->query("SELECT * FROM ip_blocked WHERE is_active = 0 ORDER BY blocked_at DESC LIMIT 50");
    if ($r2) while ($row = $r2->fetch_assoc()) $expired_blocks[] = $row;

    // 4. Whitelist
    $r3 = $conn->query("SELECT * FROM ip_whitelist ORDER BY created_at DESC");
    if ($r3) while ($row = $r3->fetch_assoc()) $whitelist[] = $row;

    // 5. Threat Radar (IPs active in last 1 hour)
    $r4 = $conn->query("SELECT ip_address, COUNT(*) AS total_requests, COUNT(DISTINCT request_uri) AS unique_urls, SUM(is_suspicious) AS suspicious_count, MAX(created_at) AS last_seen FROM ip_threat_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) GROUP BY ip_address ORDER BY unique_urls DESC LIMIT 25");
    if ($r4) while ($row = $r4->fetch_assoc()) $threat_stats[] = $row;

    // 6. Total request log count (last 1 hour)
    $lc = $conn->query("SELECT COUNT(*) as c FROM ip_threat_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    if ($lc) $total_log_count = (int)($lc->fetch_assoc()['c'] ?? 0);

} catch (Exception $e) {
    error_log("[IP Firewall Admin Error] " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .action-bar-title h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--portal-dark);
            margin: 0 0 4px 0;
            letter-spacing: -0.02em;
        }
        .action-bar-title p {
            margin: 0;
            color: #64748b;
            font-size: 0.92rem;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #dcfce7;
            color: #15803d;
            font-weight: 800;
            font-size: 0.8rem;
            padding: 8px 16px;
            border-radius: 50px;
            border: 1px solid #bbf7d0;
        }

        /* Stats Cards */
        .fw-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .fw-stat-card {
            background: #ffffff;
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .fw-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
        }
        .fw-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .fw-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .fw-stat-value {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1;
            color: var(--portal-dark);
            margin-bottom: 6px;
        }
        .fw-stat-label {
            font-size: 0.78rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Simulator Card */
        .sim-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 24px 28px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .sim-text h3 {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sim-text p {
            font-size: 0.88rem;
            color: #94a3b8;
            max-width: 680px;
            margin: 0;
            line-height: 1.5;
        }
        .sim-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .sim-input {
            padding: 10px 14px;
            background: #0f172a;
            border: 1px solid #334155;
            color: #ffffff;
            border-radius: 10px;
            font-size: 0.88rem;
            font-family: monospace;
            width: 170px;
            outline: none;
        }
        .sim-input:focus {
            border-color: #38bdf8;
        }

        /* Panel Cards */
        .fw-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .fw-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #fafbfc;
        }
        .fw-card-header h2 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--portal-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .fw-card-body {
            padding: 0;
        }

        /* Tables */
        .fw-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .fw-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .fw-table th {
            background: #f8fafc;
            padding: 13px 20px;
            text-align: left;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #e2e8f0;
        }
        .fw-table td {
            padding: 13px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .fw-table tr:last-child td { border-bottom: none; }
        .fw-table tr:hover td { background: #fafbfc; }

        .ip-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 800;
            color: var(--portal-dark);
            background: #f1f5f9;
            padding: 4px 9px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.86rem;
        }
        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .tag-red { background: #fee2e2; color: #dc2626; }
        .tag-blue { background: #e0f2fe; color: #0284c7; }
        .tag-amber { background: #fef3c7; color: #d97706; }
        .tag-green { background: #dcfce7; color: #16a34a; }

        /* Form Row */
        .fw-form-bar {
            padding: 20px 24px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .fw-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .fw-field label {
            font-size: 0.74rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
        }
        .fw-input {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.88rem;
            background: #ffffff;
            outline: none;
            transition: all 0.2s;
        }
        .fw-input:focus {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-act-danger { background: #dc2626; color: #ffffff; }
        .btn-act-danger:hover { background: #b91c1c; }
        .btn-act-success { background: #10b981; color: #ffffff; }
        .btn-act-success:hover { background: #059669; }
        .btn-act-primary { background: #2563eb; color: #ffffff; }
        .btn-act-primary:hover { background: #1d4ed8; }
        .btn-act-sm { padding: 6px 12px; font-size: 0.76rem; border-radius: 8px; }
        .btn-act-outline { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; }
        .btn-act-outline:hover { background: #f1f5f9; color: var(--portal-dark); }

        .empty-fw {
            padding: 44px 20px;
            text-align: center;
            color: #64748b;
        }
        .empty-fw i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 12px;
            opacity: 0.35;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Top Action Bar -->
        <div class="action-bar">
            <div class="action-bar-title">
                <h1>IP Firewall &amp; Rapid Scanning Defense</h1>
                <p>Automated 5-minute temporary IP blocking engine for brute-force and URL enumeration protection</p>
            </div>
            <div>
                <span class="status-pill"><i class="fas fa-shield-halved"></i> Firewall Defense Active</span>
            </div>
        </div>

        <!-- Simulator Banner -->
        <div class="sim-banner">
            <div class="sim-text">
                <h3><i class="fas fa-bolt" style="color:#fbbf24;"></i> Live Scanning Detection Test Simulator</h3>
                <p>
                    Verify the IP blocker live: clicks 12 rapid scans to <code>/abss/serverhost/xyz</code>, triggers auto-blocking within the 60s window, applies the 5-minute restriction, and logs the security event.
                </p>
            </div>
            <div class="sim-actions">
                <input type="text" id="simIp" value="198.51.100.88" class="sim-input" placeholder="Test IP">
                <button class="btn-act btn-act-danger" onclick="runSimulatedScan()"><i class="fas fa-play"></i> Run Test Scan Now</button>
            </div>
        </div>

        <!-- Metric Stats Grid -->
        <div class="fw-stats-grid">
            <div class="fw-stat-card">
                <div class="fw-stat-top">
                    <span class="fw-stat-label">Currently Blocked</span>
                    <div class="fw-stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-ban"></i></div>
                </div>
                <div class="fw-stat-value" style="color:#dc2626;"><?= count($active_blocks) ?></div>
                <span class="tag-pill tag-red"><?= count($active_blocks) > 0 ? 'Threats Contained' : 'Network Clean' ?></span>
            </div>

            <div class="fw-stat-card">
                <div class="fw-stat-top">
                    <span class="fw-stat-label">Suspicious IPs (1h)</span>
                    <div class="fw-stat-icon" style="background:#fef3c7;color:#ea580c;"><i class="fas fa-user-secret"></i></div>
                </div>
                <div class="fw-stat-value" style="color:#ea580c;"><?= count($threat_stats) ?></div>
                <span class="tag-pill tag-amber"><?= count($threat_stats) ?> Scanning Sources</span>
            </div>

            <div class="fw-stat-card">
                <div class="fw-stat-top">
                    <span class="fw-stat-label">Requests Logged (1h)</span>
                    <div class="fw-stat-icon" style="background:#e0f2fe;color:#0284c7;"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="fw-stat-value" style="color:#0284c7;"><?= number_format($total_log_count) ?></div>
                <span class="tag-pill tag-blue">Traffic Telemetry</span>
            </div>

            <div class="fw-stat-card">
                <div class="fw-stat-top">
                    <span class="fw-stat-label">Whitelisted IPs</span>
                    <div class="fw-stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-shield"></i></div>
                </div>
                <div class="fw-stat-value" style="color:#16a34a;"><?= count($whitelist) ?></div>
                <span class="tag-pill tag-green">Immune IPs</span>
            </div>
        </div>

        <!-- Manual Block & Whitelist Rule Panel -->
        <div class="fw-card">
            <div class="fw-card-header">
                <h2><i class="fas fa-sliders-h" style="color:var(--portal-blue);"></i> Enforce Manual IP Rule</h2>
                <small style="color:#64748b;">Immediately block an offending IP or whitelist a trusted administrator network</small>
            </div>
            <div class="fw-form-bar">
                <div class="fw-field">
                    <label>Target IP Address</label>
                    <input type="text" id="manualIp" class="fw-input" placeholder="e.g. 203.0.113.45" style="width:190px;">
                </div>
                <div class="fw-field">
                    <label>Duration (Minutes)</label>
                    <input type="number" id="manualMinutes" class="fw-input" value="5" min="1" max="10080" style="width:120px;">
                </div>
                <div class="fw-field" style="flex:1;min-width:220px;">
                    <label>Reason / Audit Note</label>
                    <input type="text" id="manualReason" class="fw-input" value="Manual administrator restriction">
                </div>
                <button class="btn-act btn-act-danger" onclick="firewallAction('block_ip', 'manual')"><i class="fas fa-ban"></i> Block IP</button>
                <button class="btn-act btn-act-success" onclick="firewallAction('whitelist_ip', 'manual')"><i class="fas fa-shield-check"></i> Whitelist IP</button>
            </div>
        </div>

        <!-- Active Blocks Table -->
        <div class="fw-card">
            <div class="fw-card-header">
                <h2><i class="fas fa-ban" style="color:#dc2626;"></i> Active Blocked IPs <span class="tag-pill tag-red"><?= count($active_blocks) ?></span></h2>
                <small style="color:#64748b;">These connections are rejected with HTTP 403. Auto-restored when 5 minutes expire.</small>
            </div>
            <div class="fw-card-body">
                <?php if (empty($active_blocks)): ?>
                    <div class="empty-fw">
                        <i class="fas fa-shield-check" style="color:#10b981;"></i>
                        <strong>No IP addresses are currently blocked.</strong>
                        <p style="margin-top:6px;font-size:0.86rem;">All traffic is healthy. Any rapid scanning bot will automatically be quarantined here.</p>
                    </div>
                <?php else: ?>
                    <div class="fw-table-wrap">
                        <table class="fw-table">
                            <thead>
                                <tr>
                                    <th>Client IP</th>
                                    <th>Source</th>
                                    <th>Detection Reason</th>
                                    <th>Blocked At</th>
                                    <th>Time Remaining</th>
                                    <th>URLs Scanned</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_blocks as $b): ?>
                                    <tr>
                                        <td><span class="ip-badge"><?= htmlspecialchars($b['ip_address']) ?></span></td>
                                        <td>
                                            <span class="tag-pill <?= $b['blocked_by'] === 'auto' ? 'tag-red' : 'tag-blue' ?>">
                                                <?= ucfirst(htmlspecialchars($b['blocked_by'])) ?>
                                            </span>
                                        </td>
                                        <td style="max-width:320px;word-break:break-word;font-weight:600;color:#334155;">
                                            <?= htmlspecialchars($b['reason']) ?>
                                        </td>
                                        <td><?= htmlspecialchars(date('d M, H:i:s', strtotime($b['blocked_at']))) ?></td>
                                        <td>
                                            <?php $m = (int)$b['mins_left']; ?>
                                            <strong style="color:<?= $m <= 2 ? '#dc2626' : '#ea580c' ?>;">
                                                <?= $m > 0 ? "{$m} mins remaining" : 'Expiring soon...' ?>
                                            </strong>
                                        </td>
                                        <td><strong><?= (int)($b['unique_urls_count'] ?? 0) ?> URLs</strong></td>
                                        <td style="white-space:nowrap;">
                                            <button class="btn-act btn-act-sm btn-act-success" onclick="firewallAction('unblock_ip', '<?= htmlspecialchars($b['ip_address']) ?>')"><i class="fas fa-unlock"></i> Unblock</button>
                                            <button class="btn-act btn-act-sm btn-act-outline" onclick="firewallAction('whitelist_ip', '<?= htmlspecialchars($b['ip_address']) ?>')"><i class="fas fa-shield-check"></i> Whitelist</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Threat Radar Panel -->
        <?php if (!empty($threat_stats)): ?>
        <div class="fw-card">
            <div class="fw-card-header">
                <h2><i class="fas fa-radar" style="color:#ea580c;"></i> Live Threat Radar (Last 1 Hour)</h2>
                <small style="color:#64748b;">Telemetry of unique URL scans and 404 access attempts</small>
            </div>
            <div class="fw-card-body">
                <div class="fw-table-wrap">
                    <table class="fw-table">
                        <thead>
                            <tr>
                                <th>Client IP</th>
                                <th>Total Hits</th>
                                <th>Unique URLs</th>
                                <th>Suspicious / 404</th>
                                <th>Last Activity</th>
                                <th>Firewall Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($threat_stats as $t): 
                                $is_currently_blocked = in_array($t['ip_address'], array_column($active_blocks, 'ip_address'));
                            ?>
                                <tr>
                                    <td><span class="ip-badge"><?= htmlspecialchars($t['ip_address']) ?></span></td>
                                    <td><?= number_format($t['total_requests']) ?></td>
                                    <td><strong><?= (int)$t['unique_urls'] ?></strong></td>
                                    <td>
                                        <span class="tag-pill <?= (int)$t['suspicious_count'] > 0 ? 'tag-amber' : 'tag-blue' ?>">
                                            <?= (int)$t['suspicious_count'] ?> hits
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('H:i:s', strtotime($t['last_seen']))) ?></td>
                                    <td>
                                        <?php if ($is_currently_blocked): ?>
                                            <span class="tag-pill tag-red"><i class="fas fa-ban"></i> Blocked (403)</span>
                                        <?php else: ?>
                                            <span class="tag-pill tag-green"><i class="fas fa-check"></i> Allowed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <?php if (!$is_currently_blocked): ?>
                                            <button class="btn-act btn-act-sm btn-act-danger" onclick="firewallAction('block_ip', '<?= htmlspecialchars($t['ip_address']) ?>')"><i class="fas fa-ban"></i> Block</button>
                                        <?php else: ?>
                                            <button class="btn-act btn-act-sm btn-act-success" onclick="firewallAction('unblock_ip', '<?= htmlspecialchars($t['ip_address']) ?>')"><i class="fas fa-unlock"></i> Unblock</button>
                                        <?php endif; ?>
                                        <button class="btn-act btn-act-sm btn-act-outline" onclick="firewallAction('whitelist_ip', '<?= htmlspecialchars($t['ip_address']) ?>')"><i class="fas fa-shield"></i> Whitelist</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Whitelist Panel -->
        <div class="fw-card">
            <div class="fw-card-header">
                <h2><i class="fas fa-shield-check" style="color:#10b981;"></i> Trusted Whitelist <span class="tag-pill tag-green"><?= count($whitelist) ?></span></h2>
                <small style="color:#64748b;">Whitelisted IPs are immune to automatic rate limits and scans</small>
            </div>
            <div class="fw-card-body">
                <?php if (empty($whitelist)): ?>
                    <div class="empty-fw">
                        <i class="fas fa-shield"></i>
                        <strong>No custom whitelist IPs configured.</strong>
                        <p style="margin-top:6px;font-size:0.86rem;">Add static IPs above for trusted administrative connections.</p>
                    </div>
                <?php else: ?>
                    <div class="fw-table-wrap">
                        <table class="fw-table">
                            <thead>
                                <tr>
                                    <th>Whitelisted IP</th>
                                    <th>Description / Label</th>
                                    <th>Added Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($whitelist as $w): ?>
                                    <tr>
                                        <td><span class="ip-badge"><?= htmlspecialchars($w['ip_address']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($w['label']) ?></strong></td>
                                        <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($w['created_at']))) ?></td>
                                        <td>
                                            <button class="btn-act btn-act-sm btn-act-danger" onclick="firewallAction('remove_whitelist', '<?= htmlspecialchars($w['ip_address']) ?>')"><i class="fas fa-trash"></i> Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historical Expired Blocks -->
        <?php if (!empty($expired_blocks)): ?>
        <div class="fw-card">
            <div class="fw-card-header">
                <h2><i class="fas fa-history" style="color:#64748b;"></i> Historical Block Log (Last 50 Entries)</h2>
                <small style="color:#64748b;">Neutralized scanning threats that have safely regained access</small>
            </div>
            <div class="fw-card-body">
                <div class="fw-table-wrap">
                    <table class="fw-table">
                        <thead>
                            <tr>
                                <th>Target IP</th>
                                <th>Block Reason</th>
                                <th>Trigger Source</th>
                                <th>Blocked At</th>
                                <th>Restoration Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expired_blocks as $e): ?>
                                <tr>
                                    <td><span class="ip-badge"><?= htmlspecialchars($e['ip_address']) ?></span></td>
                                    <td style="max-width:320px;word-break:break-word;color:#64748b;"><?= htmlspecialchars($e['reason']) ?></td>
                                    <td><span class="tag-pill tag-blue"><?= ucfirst(htmlspecialchars($e['blocked_by'])) ?></span></td>
                                    <td><?= htmlspecialchars(date('d M, H:i', strtotime($e['blocked_at']))) ?></td>
                                    <td>
                                        <?php if (!empty($e['unblocked_at'])): ?>
                                            <span style="color:#10b981;font-weight:700;"><?= htmlspecialchars(date('d M, H:i', strtotime($e['unblocked_at']))) ?> (Manual)</span>
                                        <?php else: ?>
                                            <span style="color:#64748b;"><?= htmlspecialchars(date('d M, H:i', strtotime($e['expires_at']))) ?> (Auto-Restored)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <script>
    const CSRF_TOKEN = '<?= htmlspecialchars($csrf_token) ?>';
    const AJAX_URL = '<?= (defined("APP_URL") ? rtrim(APP_URL, "/") : "/abss") ?>/security/ajax_firewall.php';

    function firewallAction(action, ipSource) {
        let ip = '', minutes = 5, reason = 'Manual admin block', label = 'Trusted IP';

        if (ipSource === 'manual') {
            ip = document.getElementById('manualIp').value.trim();
            minutes = parseInt(document.getElementById('manualMinutes').value) || 5;
            reason = document.getElementById('manualReason').value.trim() || 'Manual admin block';
            label = reason;
            if (!ip) {
                alert('Please enter a valid IP address.');
                return;
            }
        } else {
            ip = ipSource;
        }

        const msgs = {
            'block_ip': `Confirm: Block IP ${ip} for ${minutes} minutes?`,
            'unblock_ip': `Confirm: Immediately unblock IP ${ip}?`,
            'whitelist_ip': `Confirm: Whitelist IP ${ip}? (This IP will never be blocked)`,
            'remove_whitelist': `Confirm: Remove IP ${ip} from whitelist?`
        };

        if (!confirm(msgs[action] || 'Proceed with action?')) return;

        const fd = new FormData();
        fd.append('action', action);
        fd.append('ip', ip);
        fd.append('minutes', minutes);
        fd.append('reason', reason);
        fd.append('label', label);
        fd.append('csrf_token', CSRF_TOKEN);

        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(err => {
                alert('Network error while processing firewall command.');
            });
    }

    function runSimulatedScan() {
        const testIp = document.getElementById('simIp').value.trim() || '198.51.100.88';
        if (!confirm(`Run rapid URL scanning simulation for test IP: ${testIp}?`)) return;

        const fd = new FormData();
        fd.append('action', 'simulate_test_scan');
        fd.append('test_ip', testIp);
        fd.append('csrf_token', CSRF_TOKEN);

        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(err => {
                alert('Error running simulation.');
            });
    }
    </script>
</body>
</html>

