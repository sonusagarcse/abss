<?php
// admin/notifications/create.php - Create & Dispatch FCM Push Notification
require_once '../includes/auth.php';
require_once '../../config/firebase.php';

$msg = '';
$err = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['dispatch_notification'])) {
    $title    = trim($_POST['title'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $image    = trim($_POST['image'] ?? '');
    $url      = trim($_POST['url'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $target   = trim($_POST['target'] ?? 'all');
    $selected_tokens = $_POST['selected_tokens'] ?? [];

    if (empty($title) || empty($message)) {
        $err = "Title and Message fields are required.";
    } else {
        try {
            $sent_count = 0;
            $failed_count = 0;
            $cleaned_tokens = 0;

            // 1. BROADCAST TO ALL FIREBASE TOPICS (all, global, news, notice, android)
            // Matches Firebase Console Broadcast to ALL installed Android APKs 100%!
            if ($target === 'all') {
                broadcastFcmCampaignToAllTopics($title, $message, $image, $url, $category);
                $sent_count++;
            }

            // 2. DISPATCH TO SPECIFIC OR STORED DEVICE TOKENS IN DATABASE
            $tokens = [];
            if ($target === 'selected' && !empty($selected_tokens)) {
                if (is_string($selected_tokens)) {
                    $selected_tokens = explode(',', $selected_tokens);
                }
                $cleanTokens = array_map(function($t) use ($conn) {
                    return "'" . $conn->real_escape_string(trim($t)) . "'";
                }, $selected_tokens);
                
                $tokenListStr = implode(',', $cleanTokens);
                $res = $conn->query("SELECT id, token FROM fcm_tokens WHERE token IN ($tokenListStr)");
            } else {
                $res = $conn->query("SELECT id, token FROM fcm_tokens ORDER BY id DESC");
            }

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $tokens[] = $row;
                }
            }

            $expired_ids = [];
            foreach ($tokens as $item) {
                $fcmToken = $item['token'];
                $tokenId  = (int)$item['id'];

                $result = sendSingleFcmNotification($fcmToken, $title, $message, $image, $url, $category);

                if ($result['success']) {
                    $sent_count++;
                } else {
                    $failed_count++;
                    if (!empty($result['unregistered'])) {
                        $expired_ids[] = $tokenId;
                    }
                }
            }

            // Auto-Clean Expired/Unregistered Tokens
            if (!empty($expired_ids)) {
                $cleaned_tokens = count($expired_ids);
                $idsStr = implode(',', array_map('intval', $expired_ids));
                $conn->query("DELETE FROM fcm_tokens WHERE id IN ($idsStr)");
            }

            // 3. BROADCAST TO LIVE WEBSITE NOTIFICATION FEED
            $webNotifStmt = $conn->prepare("INSERT INTO notifications (title, message, url, status) VALUES (?, ?, ?, 1)");
            $webNotifStmt->bind_param("sss", $title, $message, $url);
            $webNotifStmt->execute();
            $webNotifStmt->close();

            // 4. RECORD CAMPAIGN LOG IN NOTIFICATION_HISTORY
            $targetAudience = ($target === 'selected' && !empty($selected_tokens)) ? 'Selected Tokens (' . count($tokens) . ')' : 'All App Users (FCM Topic all)';
            $totalSentLog = max(1, $sent_count);
            $histStmt = $conn->prepare("
                INSERT INTO notification_history (title, message, image, url, category, target_audience, sent_count, failed_count) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $histStmt->bind_param("ssssssii", $title, $message, $image, $url, $category, $targetAudience, $totalSentLog, $failed_count);
            $histStmt->execute();
            $histStmt->close();

            $successMsg = "Push Notification dispatched successfully to all Android App users (FCM Topic 'all') & live website feed.";
            if ($cleaned_tokens > 0) {
                $successMsg .= " ($cleaned_tokens invalid token(s) cleaned)";
            }

            header("Location: index.php?msg=" . urlencode($successMsg));
            exit();

        } catch (Exception $e) {
            $err = "Dispatch Error: " . $e->getMessage();
        }
    }
}

// Fetch active tokens for specific target dropdown
$tokens_res = $conn->query("SELECT id, token, device_type, app_version, updated_at FROM fcm_tokens ORDER BY updated_at DESC");
$categories = [
    'Admission',
    'Fee Reminder',
    'Exam Notice',
    'Result',
    'Holiday',
    'Hostel',
    'Group A',
    'Group B',
    'Group C',
    'Group D',
    'General'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send FCM Notification | ABSS Admin</title>
    <?php include '../includes/head_css.php'; ?>
    <style>
        .form-card { background: #fff; border-radius: 28px; padding: 35px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 0.82rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; }
        .form-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #e2e8f0; background: #fff; font-family: inherit; font-size: 0.95rem; font-weight: 600; color: #0f172a; outline: none; transition: border-color 0.2s ease; }
        .form-input:focus { border-color: #2563eb; }

        .btn-dispatch { width: 100%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; padding: 15px; border-radius: 14px; font-weight: 900; font-size: 1.05rem; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-dispatch:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(37, 99, 235, 0.45); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <a href="index.php" style="color: #64748b; font-weight: 800; text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back to Notifications
                </a>
            </div>
            <h1 style="font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0;">Send New Push Notification</h1>
            <p style="color: #64748b; font-size: 0.9rem; font-weight: 600; margin: 4px 0 0 0;">Compose and broadcast real-time Firebase FCM push alerts to app users.</p>
        </header>

        <?php if($err): ?>
            <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 14px 20px; border-radius: 14px; font-weight: 700; margin-bottom: 25px; max-width: 800px; margin-left: auto; margin-right: auto;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="" method="POST" id="fcmCreateForm">
                
                <div class="form-group">
                    <label class="form-label">Notification Title <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. Netarhat Exam Result Published!" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Notification Message <span style="color:#ef4444;">*</span></label>
                    <textarea name="message" class="form-input" rows="4" placeholder="Enter message text to display on user smartphone..." required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Category <span style="color:#ef4444;">*</span></label>
                        <select name="category" class="form-input" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Send Target <span style="color:#ef4444;">*</span></label>
                        <select name="target" id="targetSelect" class="form-input" onchange="toggleTokenSelect(this.value)">
                            <option value="all">All Installed App Devices (FCM Broadcast Topic)</option>
                            <option value="selected">Select Specific Tokens</option>
                        </select>
                    </div>
                </div>

                <div id="tokenSelectBox" class="form-group" style="display: none; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 14px; border: 1px solid #e2e8f0;">
                    <label class="form-label">Select Target Devices</label>
                    <select name="selected_tokens[]" multiple class="form-input" style="height: 120px;">
                        <?php if ($tokens_res && $tokens_res->num_rows > 0): ?>
                            <?php while ($tk = $tokens_res->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($tk['token']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($tk['device_type'])); ?> App (v<?php echo htmlspecialchars($tk['app_version']); ?>) - <?php echo substr($tk['token'], 0, 30); ?>...
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No device tokens available</option>
                        <?php endif; ?>
                    </select>
                    <small style="color: #64748b; font-weight: 600; margin-top: 4px;">Hold Ctrl (Cmd on Mac) to select multiple device tokens.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Banner Image URL <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                    <input type="url" name="image" class="form-input" placeholder="https://abss.lkvmbihar.in/assets/banner.jpg">
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label class="form-label">Action Click URL <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                    <input type="url" name="url" class="form-input" placeholder="https://abss.lkvmbihar.in/parent/login.php">
                </div>

                <button type="submit" name="dispatch_notification" class="btn-dispatch">
                    <i class="fas fa-paper-plane"></i> Broadcast Push Notification Now
                </button>
            </form>
        </div>

    </main>

    <script>
        function toggleTokenSelect(val) {
            var box = document.getElementById('tokenSelectBox');
            if (val === 'selected') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }
    </script>
</body>
</html>
