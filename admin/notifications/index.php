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
                    $tParentId  = !empty($tRow['parent_id']) ? (int)$tRow['parent_id'] : null;
                    $tStudentId = !empty($tRow['student_id']) ? (int)$tRow['student_id'] : null;

                    $stmt = $conn->prepare("
                        INSERT INTO fcm_tokens (token, device_type, app_version, parent_id, student_id) 
                        VALUES (?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                            device_type = VALUES(device_type), 
                            app_version = VALUES(app_version), 
                            parent_id = COALESCE(VALUES(parent_id), parent_id),
                            student_id = COALESCE(VALUES(student_id), student_id),
                            updated_at = NOW()
                    ");
                    $stmt->bind_param("sssii", $tVal, $tType, $tVer, $tParentId, $tStudentId);
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

// Handle Retroactive Auto-Linking of Unlinked Tokens
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['auto_link_tokens'])) {
    $unlinkedTokens = $conn->query("SELECT id, token, created_at, updated_at FROM fcm_tokens WHERE parent_id IS NULL OR student_id IS NULL");
    $linkedCount = 0;
    if ($unlinkedTokens) {
        while ($tk = $unlinkedTokens->fetch_assoc()) {
            $tokenId = (int)$tk['id'];
            $cTime = $tk['created_at'];
            $uTime = $tk['updated_at'];
            $matchedParentId = 0;

            // 1. Try finding login within +/- 20 minutes of created_at
            $logStmt = $conn->prepare("
                SELECT user_id 
                FROM activity_logs 
                WHERE (user_role = 'parent' OR action_details LIKE '%Parent%')
                  AND user_id > 0
                  AND created_at BETWEEN DATE_SUB(?, INTERVAL 20 MINUTE) AND DATE_ADD(?, INTERVAL 20 MINUTE)
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC 
                LIMIT 1
            ");
            if ($logStmt) {
                $logStmt->bind_param("sss", $cTime, $cTime, $cTime);
                $logStmt->execute();
                $res = $logStmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $matchedParentId = (int)$row['user_id'];
                }
                $logStmt->close();
            }

            // 2. Try site_visitors within +/- 20 minutes of created_at
            if (!$matchedParentId) {
                $visStmt = $conn->prepare("
                    SELECT parent_id 
                    FROM site_visitors 
                    WHERE parent_id > 0 
                      AND visited_at BETWEEN DATE_SUB(?, INTERVAL 20 MINUTE) AND DATE_ADD(?, INTERVAL 20 MINUTE)
                    ORDER BY ABS(TIMESTAMPDIFF(SECOND, visited_at, ?)) ASC 
                    LIMIT 1
                ");
                if ($visStmt) {
                    $visStmt->bind_param("sss", $cTime, $cTime, $cTime);
                    $visStmt->execute();
                    $res = $visStmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $matchedParentId = (int)$row['parent_id'];
                    }
                    $visStmt->close();
                }
            }

            // 3. Fallback: check updated_at if different
            if (!$matchedParentId && $uTime !== $cTime) {
                $logStmt2 = $conn->prepare("
                    SELECT user_id 
                    FROM activity_logs 
                    WHERE (user_role = 'parent' OR action_details LIKE '%Parent%')
                      AND user_id > 0
                      AND created_at BETWEEN DATE_SUB(?, INTERVAL 20 MINUTE) AND DATE_ADD(?, INTERVAL 20 MINUTE)
                    ORDER BY ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC 
                    LIMIT 1
                ");
                if ($logStmt2) {
                    $logStmt2->bind_param("sss", $uTime, $uTime, $uTime);
                    $logStmt2->execute();
                    $res2 = $logStmt2->get_result();
                    if ($row2 = $res2->fetch_assoc()) {
                        $matchedParentId = (int)$row2['user_id'];
                    }
                    $logStmt2->close();
                }
            }

            if ($matchedParentId > 0) {
                $checkParent = $conn->query("SELECT id FROM parents WHERE id = $matchedParentId LIMIT 1");
                if ($checkParent && $checkParent->num_rows > 0) {
                    $studQ = $conn->query("SELECT id FROM students WHERE parent_id = $matchedParentId ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, id ASC LIMIT 1");
                    $studentId = ($studQ && $stud = $studQ->fetch_assoc()) ? (int)$stud['id'] : null;

                    $upStmt = $conn->prepare("UPDATE fcm_tokens SET parent_id = ?, student_id = ?, updated_at = NOW() WHERE id = ?");
                    if ($upStmt) {
                        $upStmt->bind_param("iii", $matchedParentId, $studentId, $tokenId);
                        if ($upStmt->execute()) {
                            $linkedCount++;
                        }
                        $upStmt->close();
                    }
                }
            }
        }
    }
    $msg = "Auto-linking complete: Successfully correlated and linked $linkedCount device token(s) to parent accounts!";
}

// Handle Manual Token to Student Assignment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['assign_token_student'])) {
    $targetTokenId = (int)($_POST['token_id'] ?? 0);
    $assignStudentId = (int)($_POST['student_id'] ?? 0);

    if ($targetTokenId > 0) {
        if ($assignStudentId > 0) {
            $stRow = $conn->query("SELECT id, parent_id FROM students WHERE id = $assignStudentId LIMIT 1")->fetch_assoc();
            $assignParentId = $stRow && !empty($stRow['parent_id']) ? (int)$stRow['parent_id'] : null;
            $uStmt = $conn->prepare("UPDATE fcm_tokens SET student_id = ?, parent_id = ?, updated_at = NOW() WHERE id = ?");
            $uStmt->bind_param("iii", $assignStudentId, $assignParentId, $targetTokenId);
            if ($uStmt->execute()) {
                $msg = "Device token linked to student successfully!";
            } else {
                $err = "Database error linking token: " . $uStmt->error;
            }
            $uStmt->close();
        } else {
            // Unlink
            if ($conn->query("UPDATE fcm_tokens SET student_id = NULL, parent_id = NULL, updated_at = NOW() WHERE id = $targetTokenId")) {
                $msg = "Device token unlinked from student and parent.";
            } else {
                $err = "Error unlinking token: " . $conn->error;
            }
        }
    }
}

// Fetch Stats
$total_tokens_res = $conn->query("SELECT COUNT(*) AS total FROM fcm_tokens");
$total_tokens = $total_tokens_res ? (int)$total_tokens_res->fetch_assoc()['total'] : 0;

$linked_tokens_res = $conn->query("SELECT COUNT(*) AS linked FROM fcm_tokens WHERE student_id IS NOT NULL OR parent_id IS NOT NULL");
$linked_tokens = $linked_tokens_res ? (int)$linked_tokens_res->fetch_assoc()['linked'] : 0;

$sent_stats_res = $conn->query("SELECT SUM(sent_count) AS total_sent, COUNT(*) AS total_campaigns FROM notification_history");
$sent_stats = $sent_stats_res ? $sent_stats_res->fetch_assoc() : ['total_sent' => 0, 'total_campaigns' => 0];

// Fetch Notification History
$history_query = $conn->query("SELECT * FROM notification_history ORDER BY id DESC");

// Fetch Device Tokens with Parent and Student Details
$tokens_query = $conn->query("
    SELECT 
        f.*,
        p.parent_name,
        p.phone AS parent_phone,
        p.email AS parent_email,
        s.name AS student_name,
        s.reg_no,
        s.class_admitted,
        s.scholar_mode
    FROM fcm_tokens f
    LEFT JOIN parents p ON f.parent_id = p.id
    LEFT JOIN students s ON f.student_id = s.id
    ORDER BY (f.student_id IS NOT NULL OR f.parent_id IS NOT NULL) DESC, f.updated_at DESC
");

// Fetch active students list for assignment dropdown
$all_students_res = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.class_admitted, p.parent_name, p.phone AS parent_phone
    FROM students s
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.status = 'active'
    ORDER BY s.class_admitted ASC, s.name ASC
");
$all_students = [];
if ($all_students_res) {
    while ($st = $all_students_res->fetch_assoc()) {
        $all_students[] = $st;
    }
}
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
                <div class="lbl"><i class="fas fa-user-check" style="color: #16a34a;"></i> Linked to Students</div>
                <div class="val" style="color: #16a34a;"><?php echo number_format($linked_tokens); ?> <span style="font-size: 0.9rem; color: #64748b; font-weight: 600;">/ <?php echo $total_tokens; ?></span></div>
            </div>
            <div class="stat-card">
                <div class="lbl"><i class="fas fa-paper-plane" style="color: #2563eb;"></i> Total Sent Messages</div>
                <div class="val"><?php echo number_format($sent_stats['total_sent'] ?? 0); ?></div>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><i class="fas fa-mobile-alt" style="color: #2563eb;"></i> App Device Tokens</h3>
                        <small style="color: #64748b; font-weight: 600; font-size: 0.76rem;">Linked directly to parent &amp; student profiles</small>
                    </div>
                    <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;"><?php echo $total_tokens; ?> Devices</span>
                </div>

                <!-- Filter Pills: All / Linked / Unlinked -->
                <div style="display: flex; gap: 6px; margin-bottom: 12px;">
                    <button type="button" class="btn-token-filter active" onclick="filterDeviceCards('all', this)" style="flex: 1; padding: 6px 4px; font-size: 0.75rem; font-weight: 800; border-radius: 8px; border: 1px solid #cbd5e1; background: #2563eb; color: #fff; cursor: pointer;">
                        All (<?php echo $total_tokens; ?>)
                    </button>
                    <button type="button" class="btn-token-filter" onclick="filterDeviceCards('linked', this)" style="flex: 1; padding: 6px 4px; font-size: 0.75rem; font-weight: 800; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; color: #15803d; cursor: pointer;">
                        Linked (<?php echo $linked_tokens; ?>)
                    </button>
                    <button type="button" class="btn-token-filter" onclick="filterDeviceCards('unlinked', this)" style="flex: 1; padding: 6px 4px; font-size: 0.75rem; font-weight: 800; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; color: #b45309; cursor: pointer;">
                        Unlinked (<?php echo max(0, $total_tokens - $linked_tokens); ?>)
                    </button>
                </div>

                <!-- Quick Actions: Add Token & Sync Live -->
                <div class="token-action-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                    <button type="button" onclick="var f=document.getElementById('manualTokenForm'); f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';" class="btn-token-action btn-token-blue" style="width: 100%; min-height: 42px; padding: 10px 8px; font-size: 0.82rem; font-weight: 700; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; border: none; cursor: pointer; background: #2563eb; color: #fff; box-sizing: border-box;">
                        <i class="fas fa-plus"></i> Add Token
                    </button>
                    <form method="POST" style="margin: 0; padding: 0; width: 100%; min-width: 0;">
                        <button type="submit" name="sync_live_tokens" class="btn-token-action btn-token-slate" style="width: 100%; min-height: 42px; padding: 10px 8px; font-size: 0.82rem; font-weight: 700; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; border: none; cursor: pointer; background: #475569; color: #fff; box-sizing: border-box;" title="Fetch device tokens from live abss.lkvmbihar.in domain">
                            <i class="fas fa-sync"></i> Sync Live
                        </button>
                    </form>
                </div>
                <?php if ($total_tokens > $linked_tokens): ?>
                    <form method="POST" style="margin: 0 0 16px 0; padding: 0; width: 100%;">
                        <button type="submit" name="auto_link_tokens" class="btn-token-action" style="width: 100%; min-height: 38px; padding: 9px 12px; font-size: 0.82rem; font-weight: 800; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #bbf7d0; cursor: pointer; background: #f0fdf4; color: #15803d; box-sizing: border-box; transition: 0.2s;" title="Auto-match unlinked tokens with recent parent logins and active students">
                            <i class="fas fa-wand-magic-sparkles"></i> Auto-Link <?php echo ($total_tokens - $linked_tokens); ?> Unlinked Device(s)
                        </button>
                    </form>
                <?php endif; ?>

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

                <div style="max-height: 580px; overflow-y: auto; -webkit-overflow-scrolling: touch; padding-right: 2px;">
                    <?php if ($tokens_query && $tokens_query->num_rows > 0): ?>
                        <?php while ($tk = $tokens_query->fetch_assoc()): 
                            $isLinked = !empty($tk['student_name']) || !empty($tk['parent_name']);
                            $cardStatus = $isLinked ? 'linked' : 'unlinked';
                        ?>
                            <div class="token-card-item dev-token-card" data-status="<?php echo $cardStatus; ?>" style="background: <?php echo $isLinked ? '#ffffff' : '#fffbeb'; ?>; border: 1px solid <?php echo $isLinked ? '#e2e8f0' : '#fde68a'; ?>; border-radius: 14px; padding: 14px; margin-bottom: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                
                                <!-- Card Header: Badge & App Version -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px; flex-wrap: wrap;">
                                    <div>
                                        <?php if ($isLinked): ?>
                                            <span style="background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 50px; font-size: 0.72rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-check-circle"></i> Linked Student
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #fef3c7; color: #b45309; padding: 3px 8px; border-radius: 50px; font-size: 0.72rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-exclamation-triangle"></i> Unlinked Device
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <small style="color: #64748b; font-weight: 700; font-size: 0.75rem;"><i class="fab fa-android" style="color: #22c55e;"></i> v<?php echo htmlspecialchars($tk['app_version']); ?></small>
                                </div>

                                <!-- Student & Parent Details -->
                                <?php if (!empty($tk['student_name'])): ?>
                                    <div style="margin-bottom: 8px;">
                                        <div style="font-weight: 900; color: #0f172a; font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-user-graduate" style="color: #2563eb;"></i>
                                            <?php echo htmlspecialchars($tk['student_name']); ?>
                                        </div>
                                        <div style="font-size: 0.76rem; color: #475569; margin-top: 4px; display: flex; gap: 6px; flex-wrap: wrap; font-weight: 700;">
                                            <span style="background: #eff6ff; color: #1d4ed8; padding: 2px 7px; border-radius: 6px;">Reg: <?php echo htmlspecialchars($tk['reg_no'] ?: 'N/A'); ?></span>
                                            <span style="background: #f1f5f9; color: #334155; padding: 2px 7px; border-radius: 6px;"><?php echo htmlspecialchars($tk['class_admitted'] ?: 'Class N/A'); ?></span>
                                        </div>
                                        <div style="font-size: 0.76rem; color: #64748b; margin-top: 5px;">
                                            <i class="fas fa-user-friends" style="color: #94a3b8;"></i> Parent: <strong><?php echo htmlspecialchars($tk['parent_name'] ?: 'N/A'); ?></strong>
                                            <?php if (!empty($tk['parent_phone'])): ?>
                                                • <a href="tel:<?php echo htmlspecialchars($tk['parent_phone']); ?>" style="color: #2563eb; text-decoration: none; font-weight: 700;"><?php echo htmlspecialchars($tk['parent_phone']); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php elseif (!empty($tk['parent_name'])): ?>
                                    <div style="margin-bottom: 8px;">
                                        <div style="font-weight: 800; color: #0f172a; font-size: 0.88rem;">
                                            <i class="fas fa-user-friends" style="color: #2563eb;"></i> Parent: <?php echo htmlspecialchars($tk['parent_name']); ?>
                                        </div>
                                        <?php if (!empty($tk['parent_phone'])): ?>
                                            <small style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($tk['parent_phone']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size: 0.78rem; color: #b45309; margin-bottom: 8px; font-weight: 600;">
                                        Not linked to any student yet. Assign below or wait for parent to log in.
                                    </div>
                                <?php endif; ?>

                                <!-- Token Preview -->
                                <div style="font-family: monospace; font-size: 0.7rem; color: #64748b; word-break: break-all; overflow-wrap: anywhere; background: #f8fafc; padding: 5px 8px; border-radius: 6px; border: 1px dashed #cbd5e1; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars(substr($tk['token'], 0, 32)) . '...'; ?>
                                </div>

                                <!-- Card Footer: Last Active & Assign/Change Action -->
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <small style="color: #94a3b8; font-size: 0.68rem; font-weight: 600;">
                                        Active: <?php echo date('d M, h:i A', strtotime($tk['updated_at'])); ?>
                                    </small>
                                    <button type="button" onclick="openAssignModal(<?php echo (int)$tk['id']; ?>, <?php echo (int)($tk['student_id'] ?? 0); ?>, '<?php echo htmlspecialchars(addslashes($tk['student_name'] ?? '')); ?>')" style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-edit" style="color: #2563eb;"></i> <?php echo !empty($tk['student_name']) ? 'Change' : 'Assign Student'; ?>
                                    </button>
                                </div>
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

    <!-- ASSIGN STUDENT MODAL -->
    <div id="assignStudentModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; width: 100%; max-width: 500px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0; overflow: hidden; animation: popIn 0.2s ease-out;">
            <div style="background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-link" style="color: #2563eb;"></i> Link Device to Student
                </h3>
                <button type="button" onclick="closeAssignModal()" style="background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" style="padding: 24px;">
                <input type="hidden" name="assign_token_student" value="1">
                <input type="hidden" name="token_id" id="modalTokenId" value="">
                
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px;">
                    <span style="font-size: 0.76rem; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.04em;">Selected Device</span>
                    <div style="font-weight: 800; color: #1e3a8a; font-size: 0.95rem; margin-top: 2px;">
                        Token ID: <span id="modalTokenDisplay" style="font-family: monospace;">#0</span>
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 800; color: #334155; margin-bottom: 6px;">
                        Quick Search Student / Parent
                    </label>
                    <div style="position: relative;">
                        <input type="text" id="studentSearchInput" oninput="filterStudentOptions()" placeholder="Type student name, reg no, or mobile..." style="width: 100%; padding: 10px 14px 10px 36px; border: 2px solid #cbd5e1; border-radius: 10px; font-size: 0.88rem; box-sizing: border-box; outline: none;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 800; color: #334155; margin-bottom: 6px;">
                        Select Student &amp; Parent Profile <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="student_id" id="modalStudentSelect" required style="width: 100%; padding: 12px 14px; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 0.88rem; box-sizing: border-box; outline: none;">
                        <option value="0">-- Unlink / Remove Assignment --</option>
                        <?php foreach ($all_students as $st): ?>
                            <option value="<?php echo (int)$st['id']; ?>">
                                [<?php echo htmlspecialchars($st['class_admitted'] ?: 'Class N/A'); ?>] <?php echo htmlspecialchars($st['name']); ?> (Reg: <?php echo htmlspecialchars($st['reg_no'] ?: 'N/A'); ?>) — Parent: <?php echo htmlspecialchars($st['parent_name'] ?: 'N/A'); ?> (<?php echo htmlspecialchars($st['parent_phone'] ?: 'N/A'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; color: #64748b; font-size: 0.74rem; margin-top: 5px;">
                        Linking a student will also automatically associate their registered parent.
                    </small>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeAssignModal()" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 10px 20px; border-radius: 10px; border: none; background: #2563eb; color: #ffffff; font-weight: 800; font-size: 0.88rem; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                        <i class="fas fa-save"></i> Save Link
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(tokenId, currentStudentId, studentName) {
            document.getElementById('modalTokenId').value = tokenId;
            document.getElementById('modalTokenDisplay').textContent = '#' + tokenId;
            document.getElementById('modalStudentSelect').value = currentStudentId || '0';
            document.getElementById('studentSearchInput').value = '';
            filterStudentOptions();
            
            const modal = document.getElementById('assignStudentModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAssignModal() {
            const modal = document.getElementById('assignStudentModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function filterDeviceCards(status, btn) {
            document.querySelectorAll('.btn-token-filter').forEach(function(b) {
                b.style.background = '#f8fafc';
                b.style.color = '#475569';
            });
            btn.style.background = '#2563eb';
            btn.style.color = '#ffffff';

            document.querySelectorAll('.dev-token-card').forEach(function(card) {
                if (status === 'all' || card.getAttribute('data-status') === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterStudentOptions() {
            const query = (document.getElementById('studentSearchInput').value || '').toLowerCase().trim();
            const select = document.getElementById('modalStudentSelect');
            for (let i = 0; i < select.options.length; i++) {
                const opt = select.options[i];
                if (opt.value === '0') continue;
                const text = opt.textContent.toLowerCase();
                opt.style.display = text.includes(query) ? '' : 'none';
            }
        }

        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('assignStudentModal').style.display === 'flex') {
                closeAssignModal();
            }
        });
    </script>
</body>
</html>
