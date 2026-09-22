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

            // Fetch target tokens or execute single dispatch mode
            $tokens = [];
            $custom_token = trim($_POST['custom_token'] ?? '');
            $direct_result_msg = '';

            if ($target === 'custom' && !empty($custom_token)) {
                $tokens[] = ['id' => 0, 'token' => $custom_token];
                $targetAudience = 'Direct Custom Token';
            } elseif ($target === 'student') {
                $target_student_id = (int)($_POST['target_student_id'] ?? 0);
                if ($target_student_id > 0) {
                    $stQuery = $conn->query("SELECT s.name, s.reg_no, s.class_admitted, s.parent_id FROM students s WHERE s.id = $target_student_id LIMIT 1");
                    $stInfo = $stQuery ? $stQuery->fetch_assoc() : null;
                    $pId = $stInfo && !empty($stInfo['parent_id']) ? (int)$stInfo['parent_id'] : 0;
                    
                    $res = $conn->query("SELECT DISTINCT id, token FROM fcm_tokens WHERE student_id = $target_student_id OR (parent_id > 0 AND parent_id = $pId)");
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $tokens[] = $row;
                        }
                    }
                    $targetAudience = 'Student: ' . ($stInfo['name'] ?? "ID $target_student_id") . ' (' . count($tokens) . ' devices)';
                    if (empty($tokens)) {
                        $err = "No registered app devices found for " . ($stInfo['name'] ?? 'this student') . ". Please ask the parent to open the mobile app or assign their device under Notifications index.";
                    }
                } else {
                    $err = "Please select a target student.";
                }
            } elseif ($target === 'class') {
                $target_class = trim($_POST['target_class'] ?? '');
                if (!empty($target_class)) {
                    $clsEsc = $conn->real_escape_string($target_class);
                    $res = $conn->query("
                        SELECT DISTINCT f.id, f.token 
                        FROM fcm_tokens f
                        INNER JOIN students s ON (f.student_id = s.id OR (f.parent_id > 0 AND f.parent_id = s.parent_id))
                        WHERE s.class_admitted = '$clsEsc'
                    ");
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $tokens[] = $row;
                        }
                    }
                    $targetAudience = 'Class: ' . $target_class . ' (' . count($tokens) . ' devices)';
                    if (empty($tokens)) {
                        $err = "No registered app devices found for class '$target_class'.";
                    }
                } else {
                    $err = "Please select a target class.";
                }
            } elseif ($target === 'selected' && !empty($selected_tokens)) {
                if (is_string($selected_tokens)) {
                    $selected_tokens = explode(',', $selected_tokens);
                }
                $cleanTokens = array_map(function($t) use ($conn) {
                    return "'" . $conn->real_escape_string(trim($t)) . "'";
                }, $selected_tokens);
                
                $tokenListStr = implode(',', $cleanTokens);
                $res = $conn->query("SELECT id, token FROM fcm_tokens WHERE token IN ($tokenListStr)");
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $tokens[] = $row;
                    }
                }
                $targetAudience = 'Selected Tokens (' . count($tokens) . ')';
            } elseif ($target === 'topic') {
                // Topic-only broadcast
                $topicRes = sendTopicFcmNotification('all', $title, $message, $image, $url, $category);
                if ($topicRes['success']) {
                    $sent_count++;
                    $direct_result_msg = "Topic broadcast delivered successfully";
                } else {
                    $failed_count++;
                    $direct_result_msg = "Topic broadcast failed: " . ($topicRes['error'] ?? 'Unknown');
                }
                $targetAudience = 'FCM Topic (all)';
            } else {
                // Broadcast to all registered app devices (Unique Token Dispatch)
                $res = $conn->query("SELECT id, token FROM fcm_tokens ORDER BY id DESC");
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $tokens[] = $row;
                    }
                }
                // If no tokens in database yet, broadcast to 'all' topic
                if (empty($tokens)) {
                    $topicRes = sendTopicFcmNotification('all', $title, $message, $image, $url, $category);
                    if ($topicRes['success']) {
                        $sent_count++;
                        $direct_result_msg = "Topic broadcast delivered";
                    } else {
                        $failed_count++;
                        $direct_result_msg = "Topic broadcast failed";
                    }
                }
                $targetAudience = 'All Registered Devices (' . count($tokens) . ')';
            }

            if (!empty($err)) {
                // Return error without sending
            } else {
                foreach ($tokens as $item) {
                    $fcmToken = $item['token'];
                    $tokenId  = (int)$item['id'];

                    $result = sendSingleFcmNotification($fcmToken, $title, $message, $image, $url, $category);

                    if ($result['success']) {
                        $sent_count++;
                        $direct_result_msg = "Delivery successful (ID: " . ($result['name'] ?? 'OK') . ")";
                    } else {
                        $failed_count++;
                        $direct_result_msg = "Delivery failed: " . ($result['error'] ?? 'Unknown');
                        if (!empty($result['unregistered']) && $tokenId > 0) {
                            $expired_ids[] = $tokenId;
                        }
                    }
                }

                // If tokens were unregistered/expired, clean them up from database
                if (!empty($expired_ids)) {
                    $idList = implode(',', array_map('intval', $expired_ids));
                    $conn->query("DELETE FROM fcm_tokens WHERE id IN ($idList)");
                    $cleaned_tokens = count($expired_ids);
                }

                // Record Campaign in notification_history
                $adminId = $_SESSION['admin_id'] ?? 1;
                $stmt = $conn->prepare("
                    INSERT INTO notification_history 
                    (title, message, image_url, action_url, category, target_audience, sent_count, failed_count, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssssiii", $title, $message, $image, $url, $category, $targetAudience, $sent_count, $failed_count, $adminId);
                $stmt->execute();
                $campaignId = $stmt->insert_id;
                $stmt->close();

                // Also publish into in-app portal_notifications
                $portalStmt = $conn->prepare("
                    INSERT INTO portal_notifications (title, message, category, type, target_audience, action_url, created_by) 
                    VALUES (?, ?, ?, 'push', ?, ?, ?)
                ");
                $portalStmt->bind_param("sssssi", $title, $message, $category, $targetAudience, $url, $adminId);
                $portalStmt->execute();
                $portalStmt->close();

                logFcmEvent('push_campaign_dispatched', [
                    'campaign_id' => $campaignId,
                    'title' => $title,
                    'sent' => $sent_count,
                    'failed' => $failed_count,
                    'cleaned' => $cleaned_tokens,
                    'audience' => $targetAudience
                ], 'SUCCESS', 200);

                $successMsg = "Push Notification dispatched successfully! Delivered to $sent_count recipient(s).";
                if ($failed_count > 0) {
                    $successMsg .= " ($failed_count delivery failures handled).";
                }
                if ($cleaned_tokens > 0) {
                    $successMsg .= " ($cleaned_tokens obsolete device tokens removed).";
                }

                header("Location: index.php?msg=" . urlencode($successMsg));
                exit();
            }

        } catch (Exception $e) {
            $err = "Dispatch Error: " . $e->getMessage();
        }
    }
}

// Fetch active tokens for specific target dropdown with student/parent join
$tokens_res = $conn->query("
    SELECT f.id, f.token, f.device_type, f.app_version, f.updated_at,
           s.name AS student_name, s.reg_no, s.class_admitted,
           p.parent_name
    FROM fcm_tokens f
    LEFT JOIN students s ON f.student_id = s.id
    LEFT JOIN parents p ON f.parent_id = p.id
    ORDER BY (f.student_id IS NOT NULL OR f.parent_id IS NOT NULL) DESC, f.updated_at DESC
");

// Fetch active students with device count
$all_students_res = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.class_admitted, p.parent_name, p.phone AS parent_phone,
           (SELECT COUNT(*) FROM fcm_tokens f WHERE f.student_id = s.id OR (f.parent_id > 0 AND f.parent_id = s.parent_id)) AS device_count
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

// Fetch distinct classes with device count
$classes_res = $conn->query("
    SELECT s.class_admitted,
           COUNT(DISTINCT f.id) AS device_count,
           COUNT(DISTINCT s.id) AS student_count
    FROM students s
    LEFT JOIN fcm_tokens f ON (f.student_id = s.id OR (f.parent_id > 0 AND f.parent_id = s.parent_id))
    WHERE s.class_admitted IS NOT NULL AND s.class_admitted != '' AND s.status = 'active'
    GROUP BY s.class_admitted
    ORDER BY s.class_admitted ASC
");
$all_classes = [];
if ($classes_res) {
    while ($cl = $classes_res->fetch_assoc()) {
        $all_classes[] = $cl;
    }
}

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
                            <option value="all">📢 Broadcast: All Registered Devices &amp; Topics</option>
                            <option value="student">👤 Target Specific Student / Parent</option>
                            <option value="class">🏫 Target by Class</option>
                            <option value="selected">📱 Select Registered App Devices</option>
                            <option value="custom">🎯 Direct Custom FCM Token (Instant Test)</option>
                        </select>
                    </div>
                </div>

                <!-- TARGET SPECIFIC STUDENT / PARENT -->
                <div id="studentSelectBox" class="form-group" style="display: none; margin-bottom: 20px; background: #eff6ff; padding: 18px; border-radius: 16px; border: 1px solid #bfdbfe;">
                    <label class="form-label" style="color: #1e40af;"><i class="fas fa-user-graduate"></i> Select Target Student / Parent</label>
                    <input type="text" id="targetStudentSearch" oninput="filterTargetStudents()" placeholder="Quick filter by student name, reg no, mobile..." style="width: 100%; padding: 10px 14px; margin-bottom: 10px; border-radius: 10px; border: 2px solid #93c5fd; font-size: 0.88rem; outline: none; box-sizing: border-box;">
                    <select name="target_student_id" id="targetStudentSelect" class="form-input" style="font-size: 0.9rem;">
                        <option value="">-- Choose Student &amp; Parent Profile --</option>
                        <?php foreach ($all_students as $st): 
                            $hasDev = $st['device_count'] > 0;
                        ?>
                            <option value="<?php echo (int)$st['id']; ?>" data-has-dev="<?php echo $hasDev ? '1' : '0'; ?>">
                                <?php echo $hasDev ? '🟢' : '⚪'; ?> [<?php echo htmlspecialchars($st['class_admitted'] ?: 'Class N/A'); ?>] <?php echo htmlspecialchars($st['name']); ?> (Reg: <?php echo htmlspecialchars($st['reg_no'] ?: 'N/A'); ?>) — Parent: <?php echo htmlspecialchars($st['parent_name'] ?: 'N/A'); ?> (<?php echo htmlspecialchars($st['parent_phone'] ?: 'N/A'); ?>) <?php echo $hasDev ? '(' . $st['device_count'] . ' app device)' : '(No app device yet)'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #1d4ed8; font-weight: 600; margin-top: 6px; display: block;">
                        🟢 indicates student/parent already has an active app device linked.
                    </small>
                </div>

                <!-- TARGET BY CLASS -->
                <div id="classSelectBox" class="form-group" style="display: none; margin-bottom: 20px; background: #f0fdf4; padding: 18px; border-radius: 16px; border: 1px solid #bbf7d0;">
                    <label class="form-label" style="color: #166534;"><i class="fas fa-school"></i> Select Target Class</label>
                    <select name="target_class" class="form-input" style="font-size: 0.9rem;">
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($all_classes as $cl): ?>
                            <option value="<?php echo htmlspecialchars($cl['class_admitted']); ?>">
                                <?php echo htmlspecialchars($cl['class_admitted']); ?> (<?php echo (int)$cl['device_count']; ?> active app devices / <?php echo (int)$cl['student_count']; ?> students)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #15803d; font-weight: 600; margin-top: 6px; display: block;">
                        Notifications will be sent to all app devices linked to parents and students in this class.
                    </small>
                </div>

                <div id="tokenSelectBox" class="form-group" style="display: none; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 14px; border: 1px solid #e2e8f0;">
                    <label class="form-label">Select Target Devices</label>
                    <select name="selected_tokens[]" multiple class="form-input" style="height: 140px;">
                        <?php if ($tokens_res && $tokens_res->num_rows > 0): ?>
                            <?php while ($tk = $tokens_res->fetch_assoc()): 
                                $label = !empty($tk['student_name']) 
                                    ? '[' . ($tk['class_admitted'] ?: 'Class') . '] ' . $tk['student_name'] . ' (' . ($tk['parent_name'] ?: 'Parent') . ')'
                                    : ucfirst($tk['device_type']) . ' App (v' . $tk['app_version'] . ') - ' . substr($tk['token'], 0, 25) . '...';
                            ?>
                                <option value="<?php echo htmlspecialchars($tk['token']); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No device tokens available</option>
                        <?php endif; ?>
                    </select>
                    <small style="color: #64748b; font-weight: 600; margin-top: 4px;">Hold Ctrl (Cmd on Mac) to select multiple device tokens.</small>
                </div>

                <div id="customTokenBox" class="form-group" style="display: none; margin-bottom: 20px; background: #fdf2f8; padding: 15px; border-radius: 14px; border: 1px solid #fbcfe8;">
                    <label class="form-label" style="color: #be185d;"><i class="fas fa-crosshairs"></i> Enter Specific FCM Device Token</label>
                    <textarea name="custom_token" class="form-input" rows="2" placeholder="Paste phone's FCM registration token..."></textarea>
                    <small style="color: #9d174d; font-weight: 600; margin-top: 4px; display:block;">Directly target this specific device token via Firebase HTTP v1 API.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Banner Image URL <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                    <input type="url" name="image" class="form-input" placeholder="https://abss.lkvmbihar.in/assets/banner.jpg">
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label class="form-label">Action Click URL <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                    <input type="url" name="url" class="form-input" placeholder="https://abss.lkvmbihar.in/parent/dashboard">
                </div>

                <button type="submit" name="dispatch_notification" class="btn-dispatch">
                    <i class="fas fa-paper-plane"></i> Broadcast Push Notification Now
                </button>
            </form>
        </div>

    </main>

    <script>
        function toggleTokenSelect(val) {
            var studentBox = document.getElementById('studentSelectBox');
            var classBox = document.getElementById('classSelectBox');
            var box = document.getElementById('tokenSelectBox');
            var customBox = document.getElementById('customTokenBox');
            
            if (studentBox) studentBox.style.display = (val === 'student') ? 'block' : 'none';
            if (classBox) classBox.style.display = (val === 'class') ? 'block' : 'none';
            if (box) box.style.display = (val === 'selected') ? 'block' : 'none';
            if (customBox) customBox.style.display = (val === 'custom') ? 'block' : 'none';
        }

        function filterTargetStudents() {
            var q = (document.getElementById('targetStudentSearch').value || '').toLowerCase().trim();
            var sel = document.getElementById('targetStudentSelect');
            for (var i = 0; i < sel.options.length; i++) {
                var opt = sel.options[i];
                if (!opt.value) continue;
                var txt = opt.textContent.toLowerCase();
                opt.style.display = txt.includes(q) ? '' : 'none';
            }
        }
    </script>
</body>
</html>
