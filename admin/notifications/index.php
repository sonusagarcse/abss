<?php
// admin/notifications/index.php - FCM Push Notifications Management Panel
require_once '../includes/auth.php';
require_once '../../config/firebase.php';

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

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

        .dashboard-2col { display: grid; grid-template-columns: 360px 1fr; gap: 30px; align-items: start; }
        @media (max-width: 1024px) {
            .dashboard-2col { grid-template-columns: 1fr; }
        }

        .panel-card { background: #fff; border-radius: 24px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .badge-cat { padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .cat-Admission { background: #eff6ff; color: #2563eb; }
        .cat-FeeReminder { background: #fef3c7; color: #d97706; }
        .cat-ExamNotice { background: #f3e8ff; color: #7c3aed; }
        .cat-Result { background: #dcfce7; color: #16a34a; }
        .cat-Holiday { background: #ffe4e6; color: #e11d48; }
        .cat-General { background: #f1f5f9; color: #475569; }

        .btn-send-cta { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: 900; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3); transition: all 0.25s ease; }
        .btn-send-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.45); color: #fff; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0 0 4px 0;">FCM Push Notifications</h1>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem; font-weight: 600;">Dispatch Firebase HTTP v1 push notifications to Android app users & web visitors.</p>
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
        <div style="background: #ffffff; border-radius: 20px; padding: 22px 25px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 46px; height: 46px; border-radius: 14px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: bold;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #0f172a; font-size: 1rem; font-weight: 800;">Firebase Project: <span style="color: #2563eb;">abss-notification</span></h4>
                        <small style="color: #64748b; font-weight: 600; display: block; margin-top: 2px;">Sender ID: 343001874555 • VAPID Key: BLBC9JquNYYa... • Web Push & Android Ready</small>
                    </div>
                </div>
                <a href="create.php" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 8px 18px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-paper-plane" style="color: #2563eb;"></i> Test Push Campaign
                </a>
            </div>
        </div>

        <div class="dashboard-2col">
            
            <!-- LEFT COL: REGISTERED APP DEVICES -->
            <div class="panel-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><i class="fas fa-mobile" style="color: #2563eb;"></i> App Device Tokens</h3>
                    <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;"><?php echo $total_tokens; ?> Devices</span>
                </div>

                <div style="max-height: 520px; overflow-y: auto;">
                    <?php if ($tokens_query && $tokens_query->num_rows > 0): ?>
                        <?php while ($tk = $tokens_query->fetch_assoc()): ?>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 14px; margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <strong style="color: #0f172a; font-size: 0.85rem;"><i class="fab fa-android" style="color: #22c55e;"></i> <?php echo htmlspecialchars(ucfirst($tk['device_type'])); ?> App</strong>
                                    <small style="color: #64748b; font-weight: 700;">v<?php echo htmlspecialchars($tk['app_version']); ?></small>
                                </div>
                                <div style="font-family: monospace; font-size: 0.75rem; color: #64748b; word-break: break-all;">
                                    <?php echo htmlspecialchars(substr($tk['token'], 0, 35)) . '...'; ?>
                                </div>
                                <small style="display: block; color: #94a3b8; font-size: 0.7rem; margin-top: 4px; font-weight: 600;">Active: <?php echo date('d M Y, h:i A', strtotime($tk['updated_at'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 10px; color: #94a3b8; font-weight: 600; font-size: 0.9rem;">
                            <i class="fas fa-mobile-alt" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No FCM app tokens registered yet.<br>Install Mobile App or visit website to connect devices.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COL: NOTIFICATION HISTORY TABLE -->
            <div class="panel-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><i class="fas fa-history" style="color: #2563eb;"></i> Broadcast History</h3>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
                                <th style="padding: 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Campaign Details</th>
                                <th style="padding: 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Category</th>
                                <th style="padding: 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Audience</th>
                                <th style="padding: 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Sent</th>
                                <th style="padding: 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);">Date</th>
                                <th style="padding: 10px; font-size: 0.8rem; font-weight: 800; color: var(--portal-blue); text-align: right;">Action</th>
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
