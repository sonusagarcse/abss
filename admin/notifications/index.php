<?php
// admin/notifications/index.php - FCM Push Notifications Management Panel
require_once '../includes/auth.php';
require_once '../../config/firebase.php';

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Handle Manual Token Addition
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['manual_register_token'])) {
    $manToken = trim($_POST['token'] ?? '');
    $manType  = trim($_POST['device_type'] ?? 'android');
    $manVer   = trim($_POST['app_version'] ?? '1.2.0');

    if (empty($manToken) || strlen($manToken) < 20) {
        $err = "Please provide a valid FCM Device Token.";
    } else {
        $stmt = $conn->prepare("INSERT INTO fcm_tokens (token, device_type, app_version) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE device_type = VALUES(device_type), app_version = VALUES(app_version), updated_at = NOW()");
        $stmt->bind_param("sss", $manToken, $manType, $manVer);
        if ($stmt->execute()) {
            // Automatically subscribe to topic all
            @subscribeFcmTokensToTopic($manToken, 'all');
            $msg = "Device token registered & auto-subscribed to Firebase topic 'all' successfully!";
        } else {
            $err = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Sync from Live Server (https://abss.lkvmbihar.in)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['sync_live_tokens'])) {
    $liveUrl = "https://abss.lkvmbihar.in/api/get-tokens.php?api_key=abss_fcm_secret_key_2026";
    $ch = curl_init($liveUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $resp) {
        $data = json_decode($resp, true);
        if ($data && !empty($data['tokens'])) {
            $syncedCount = 0;
            $tokensToSub = [];
            foreach ($data['tokens'] as $tRow) {
                $tVal = trim($tRow['token'] ?? '');
                if (strlen($tVal) >= 20) {
                    $tType = $tRow['device_type'] ?? 'android';
                    $tVer  = $tRow['app_version'] ?? '1.2.0';
                    $stmt = $conn->prepare("INSERT INTO fcm_tokens (token, device_type, app_version) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE device_type = VALUES(device_type), app_version = VALUES(app_version), updated_at = NOW()");
                    $stmt->bind_param("sss", $tVal, $tType, $tVer);
                    if ($stmt->execute()) {
                        $syncedCount++;
                        $tokensToSub[] = $tVal;
                    }
                    $stmt->close();
                }
            }
            if (!empty($tokensToSub)) {
                @subscribeFcmTokensToTopic($tokensToSub, 'all');
            }
            $msg = "Synced $syncedCount device token(s) from live server and subscribed to topic 'all'!";
        } else {
            $err = "No device tokens found on live server.";
        }
    } else {
        $err = "Failed to connect to live server (HTTP $httpCode). Ensure https://abss.lkvmbihar.in is accessible.";
    }
}

// Fetch Stats
$total_tokens_res = $conn->query("SELECT COUNT(*) AS total FROM fcm_tokens");
$total_tokens = $total_tokens_res ? (int)$total_tokens_res->fetch_assoc()['total'] : 0;

$sent_stats_res = $conn->query("SELECT SUM(sent_count) AS total_sent, COUNT(*) AS total_campaigns FROM notification_history");
$sent_stats = $sent_stats_res ? $sent_stats_res->fetch_assoc() : ['total_sent' => 0, 'total_campaigns' => 0];

// Fetch Notification History
$history_query = $conn->query("SELECT * FROM notification_history ORDER BY id DESC");

// Fetch Recent Device Tokens
$tokens_query = $conn->query("SELECT * FROM fcm_tokens ORDER BY updated_at DESC LIMIT 50");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCM App Notifications | ABSS Admin Portal</title>
    <?php include '../includes/head_css.php'; ?>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 22px 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .stat-card .val { font-size: 1.8rem; font-weight: 900; color: var(--portal-blue); margin-top: 5px; }
        .stat-card .lbl { font-size: 0.8rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

        .dashboard-2col {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 25px;
            align-items: start;
            min-width: 0;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }
        @media (max-width: 1024px) {
            .dashboard-2col {
                grid-template-columns: 100% !important;
                gap: 20px !important;
            }
        }

        .panel-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            min-width: 0;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .badge-cat { padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .cat-Admission { background: #eff6ff; color: #2563eb; }
        .cat-FeeReminder { background: #fef3c7; color: #d97706; }
        .cat-ExamNotice { background: #f3e8ff; color: #7c3aed; }
        .cat-Result { background: #dcfce7; color: #16a34a; }
        .cat-Holiday { background: #ffe4e6; color: #e11d48; }
        .cat-General { background: #f1f5f9; color: #475569; }

        .btn-send-cta { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: 900; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3); transition: all 0.25s ease; }
        .btn-send-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.45); color: #fff; }

        /* Grid-based Token Action Buttons: Guarantee 50/50 split without clipping */
        .token-action-row {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
            margin-bottom: 16px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .token-action-row form {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            min-width: 0 !important;
            display: block !important;
        }
        .btn-token-action {
            width: 100% !important;
            height: 100% !important;
            min-height: 42px !important;
            padding: 10px 8px !important;
            font-size: 0.82rem !important;
            font-weight: 700 !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            border: none !important;
            cursor: pointer !important;
            box-sizing: border-box !important;
            text-align: center !important;
            white-space: nowrap !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
        }
        .btn-token-blue {
            background: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important;
        }
        .btn-token-blue:hover {
            background: #1d4ed8 !important;
        }
        .btn-token-slate {
            background: #475569 !important;
            color: #ffffff !important;
        }
        .btn-token-slate:hover {
            background: #334155 !important;
        }
        .btn-token-green {
            background: #16a34a !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2) !important;
        }
        .btn-token-green:hover {
            background: #15803d !important;
        }

        .token-card-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 10px;
            word-break: break-all;
            overflow-wrap: anywhere;
        }

        .mobile-table-hint {
            display: none;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--portal-blue);
            background: #eff6ff;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #dbeafe;
        }

        .portal-table-container {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            border-radius: 12px !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            display: block !important;
            border: 1px solid #e2e8f0;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
        .portal-table-container::-webkit-scrollbar {
            height: 7px;
        }
        .portal-table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .portal-table-container::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }
        .portal-table-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        .table-broadcast {
            width: 100%;
            border-collapse: collapse;
            min-width: 660px !important;
            table-layout: auto;
        }

        @media (max-width: 768px) {
            .notifications-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 14px !important;
                margin-bottom: 20px !important;
            }
            .notifications-header h1 {
                font-size: 1.45rem !important;
            }
            .btn-send-cta {
                width: 100% !important;
                justify-content: center !important;
                padding: 13px 20px !important;
                font-size: 0.92rem !important;
                box-sizing: border-box;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
                margin-bottom: 20px !important;
            }
            .stat-card {
                padding: 14px 16px !important;
                border-radius: 16px !important;
            }
            .stat-card .val {
                font-size: 1.35rem !important;
                margin-top: 4px !important;
            }
            .stat-card .lbl {
                font-size: 0.7rem !important;
            }

            .fcm-config-card {
                padding: 16px 14px !important;
                border-radius: 16px !important;
                margin-bottom: 20px !important;
            }
            .fcm-config-inner {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }
            .btn-test-campaign {
                width: 100% !important;
                justify-content: center !important;
                padding: 10px 16px !important;
                box-sizing: border-box;
            }

            .panel-card {
                padding: 18px 14px !important;
                border-radius: 18px !important;
            }

            .mobile-table-hint {
                display: flex;
            }
        }

        @media (max-width: 380px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
            .token-action-row {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="notifications-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0 0 4px 0;">FCM Push Notifications</h1>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem; font-weight: 600;">Dispatch Firebase HTTP v1 push notifications to Android app users &amp; web visitors.</p>
            </div>
            <a href="create.php" class="btn-send-cta">
                <i class="fas fa-paper-plane"></i> Send New Push Notification
            </a>
        </header>

        <?php if($msg): ?>
            <div style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 20px; border-radius: 14px; font-weight: 700; margin-bottom: 25px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if($err): ?>
            <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 14px 20px; border-radius: 14px; font-weight: 700; margin-bottom: 25px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?>
            </div>
        <?php endif; ?>

        <!-- STATS OVERVIEW -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="lbl"><i class="fas fa-mobile-alt" style="color: #2563eb;"></i> Active Devices</div>
                <div class="val"><?php echo number_format($total_tokens); ?></div>
            </div>
            <div class="stat-card">
                <div class="lbl"><i class="fas fa-paper-plane" style="color: #16a34a;"></i> Total Sent Messages</div>
                <div class="val"><?php echo number_format($sent_stats['total_sent'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="lbl"><i class="fas fa-bullhorn" style="color: #7c3aed;"></i> Notification Campaigns</div>
                <div class="val"><?php echo number_format($sent_stats['total_campaigns'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="lbl"><i class="fas fa-shield-alt" style="color: #f59e0b;"></i> FCM Engine Status</div>
                <div class="val" style="font-size: 1.1rem; color: #16a34a; margin-top: 10px;">
                    <?php echo getFirebaseServiceAccountPath() ? 'HTTP v1 Active' : 'Config Ready'; ?>
                </div>
            </div>
        </div>

        <!-- FIREBASE PROJECT CONFIGURATION STATUS CARD -->
        <div class="fcm-config-card" style="background: #ffffff; border-radius: 20px; padding: 22px 25px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div class="fcm-config-inner" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div class="fcm-config-left" style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 46px; height: 46px; border-radius: 14px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: bold; flex-shrink: 0;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #0f172a; font-size: 1rem; font-weight: 800;">Firebase Project: <span style="color: #2563eb;">abss-notification</span></h4>
                        <small style="color: #64748b; font-weight: 600; display: block; margin-top: 2px;">Sender ID: 343001874555 • VAPID Key: BLBC9JquNYYa... • Web Push &amp; Android Ready</small>
                    </div>
                </div>
                <a href="create.php" class="btn-test-campaign" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 8px 18px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-paper-plane" style="color: #2563eb;"></i> Test Push Campaign
                </a>
            </div>
        </div>

        <div class="dashboard-2col">
            
            <!-- LEFT COL: REGISTERED APP DEVICES -->
            <div class="panel-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><i class="fas fa-mobile-alt" style="color: #2563eb;"></i> App Device Tokens</h3>
                    <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;"><?php echo $total_tokens; ?> Devices</span>
                </div>

                <!-- Quick Actions: Add Token & Sync Live -->
                <div class="token-action-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; width: 100%; box-sizing: border-box;">
                    <button type="button" onclick="var f=document.getElementById('manualTokenForm'); f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';" class="btn-token-action btn-token-blue" style="width: 100%; min-height: 42px; padding: 10px 8px; font-size: 0.82rem; font-weight: 700; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; border: none; cursor: pointer; background: #2563eb; color: #fff; box-sizing: border-box;">
                        <i class="fas fa-plus"></i> Add Token
                    </button>
                    <form method="POST" style="margin: 0; padding: 0; width: 100%; min-width: 0;">
                        <button type="submit" name="sync_live_tokens" class="btn-token-action btn-token-slate" style="width: 100%; min-height: 42px; padding: 10px 8px; font-size: 0.82rem; font-weight: 700; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; border: none; cursor: pointer; background: #475569; color: #fff; box-sizing: border-box;" title="Fetch device tokens from live abss.lkvmbihar.in domain">
                            <i class="fas fa-sync"></i> Sync Live
                        </button>
                    </form>
                </div>

                <!-- Inline Manual Token Form -->
                <div id="manualTokenForm" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; margin-bottom: 15px;">
                    <form method="POST">
                        <label style="font-size: 0.75rem; font-weight: 800; color: #334155; display: block; margin-bottom: 4px;">Paste Android Device FCM Token</label>
                        <textarea name="token" class="portal-input" rows="2" placeholder="e.g. fStbptCDA4icD0JulIQGx3:APA91b..." required style="font-size: 16px; padding: 8px; margin-bottom: 8px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box;"></textarea>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <select name="device_type" style="padding: 9px 10px; font-size: 0.82rem; border-radius: 8px; border: 1px solid #cbd5e1; flex: 1; min-width: 120px;">
                                <option value="android">Android Phone</option>
                                <option value="tablet">Android Tablet</option>
                                <option value="web_browser">Web Browser</option>
                            </select>
                            <button type="submit" name="manual_register_token" class="btn-token-action btn-token-green" style="flex: 1; min-width: 130px; height: 42px;">
                                <i class="fas fa-save"></i> Save &amp; Subscribe
                            </button>
                        </div>
                    </form>
                </div>

                <div style="max-height: 520px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <?php if ($tokens_query && $tokens_query->num_rows > 0): ?>
                        <?php while ($tk = $tokens_query->fetch_assoc()): ?>
                            <div class="token-card-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; gap: 8px; flex-wrap: wrap;">
                                    <strong style="color: #0f172a; font-size: 0.85rem;"><i class="fab fa-android" style="color: #22c55e;"></i> <?php echo htmlspecialchars(ucfirst($tk['device_type'])); ?> App</strong>
                                    <small style="color: #64748b; font-weight: 700;">v<?php echo htmlspecialchars($tk['app_version']); ?></small>
                                </div>
                                <div style="font-family: monospace; font-size: 0.75rem; color: #64748b; word-break: break-all; overflow-wrap: anywhere;">
                                    <?php echo htmlspecialchars(substr($tk['token'], 0, 35)) . '...'; ?>
                                </div>
                                <small style="display: block; color: #94a3b8; font-size: 0.7rem; margin-top: 4px; font-weight: 600;">Active: <?php echo date('d M Y, h:i A', strtotime($tk['updated_at'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 10px; color: #94a3b8; font-weight: 600; font-size: 0.9rem; word-break: break-word; overflow-wrap: break-word;">
                            <i class="fas fa-mobile-alt" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No FCM app tokens registered yet.<br>Install Mobile App or use "+ Add Token" above to register devices.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COL: NOTIFICATION HISTORY TABLE -->
            <div class="panel-card" style="overflow: hidden; width: 100%; box-sizing: border-box;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><i class="fas fa-history" style="color: #2563eb;"></i> Broadcast History</h3>
                </div>

                <div class="mobile-table-hint" style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; color: var(--portal-blue); background: #eff6ff; padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; border: 1px solid #dbeafe;">
                    <i class="fas fa-arrows-left-right"></i> Scroll table sideways to view details &amp; actions
                </div>
                <div class="portal-table-container" style="overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; border: 1px solid #e2e8f0; border-radius: 12px; display: block !important;">
                    <table class="table-broadcast" style="width: 100%; border-collapse: collapse; min-width: 660px !important;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; text-align: left; background: #f8fafc;">
                                <th style="padding: 12px 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Campaign Details</th>
                                <th style="padding: 12px 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Category</th>
                                <th style="padding: 12px 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Audience</th>
                                <th style="padding: 12px 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Sent</th>
                                <th style="padding: 12px 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Date</th>
                                <th style="padding: 12px 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue); text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history_query && $history_query->num_rows > 0): ?>
                                <?php while ($h = $history_query->fetch_assoc()): 
                                    $catClass = 'cat-' . preg_replace('/[^a-zA-Z]/', '', $h['category'] ?? 'General');
                                ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 12px 10px;">
                                            <strong style="color: #0f172a; font-size: 0.9rem; display: block;"><?php echo htmlspecialchars($h['title']); ?></strong>
                                            <span style="color: #64748b; font-size: 0.82rem; font-weight: 500; display: block; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($h['message']); ?>
                                            </span>
                                            <?php if(!empty($h['url'])): ?>
                                                <small><a href="<?php echo htmlspecialchars($h['url']); ?>" target="_blank" style="color:#2563eb; font-weight:700;">Link <i class="fas fa-external-link-alt"></i></a></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 10px;">
                                            <span class="badge-cat <?php echo $catClass; ?>"><?php echo htmlspecialchars($h['category'] ?? 'General'); ?></span>
                                        </td>
                                        <td style="padding: 12px 10px; font-size: 0.82rem; font-weight: 700; color: #475569;">
                                            <?php echo htmlspecialchars($h['target_audience'] ?? 'All Users'); ?>
                                        </td>
                                        <td style="padding: 12px 10px;">
                                            <span style="color: #16a34a; font-weight: 900; font-size: 0.9rem;"><?php echo (int)$h['sent_count']; ?></span>
                                        </td>
                                        <td style="padding: 12px 10px; font-size: 0.8rem; color: #64748b; font-weight: 600;">
                                            <?php echo date('d M Y, h:i A', strtotime($h['created_at'])); ?>
                                        </td>
                                        <td style="padding: 12px 10px; text-align: right; white-space: nowrap;">
                                            <a href="edit.php?id=<?php echo $h['id']; ?>" style="color: #2563eb; font-weight: 800; font-size: 0.8rem; text-decoration: none; margin-right: 10px;">Edit</a>
                                            <a href="delete.php?id=<?php echo $h['id']; ?>" onclick="return confirm('Delete this notification history record?')" style="color: #ef4444; font-weight: 800; font-size: 0.8rem; text-decoration: none;">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 600;">
                                        No FCM notification history recorded yet. Click "Send New Push Notification" above to dispatch your first push campaign.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>
</body>
</html>
