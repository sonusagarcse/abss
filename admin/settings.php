<?php
// admin/settings.php - Global Web Settings (2-Column Grid Redesign)
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $val) {
            saveSetting($key, $val);
        }
    }
    
    // Save Dynamic Tuition Modes
    if (isset($_POST['mode_names']) && isset($_POST['mode_amounts'])) {
        $modes = [];
        for ($i = 0; $i < count($_POST['mode_names']); $i++) {
            $name = trim($_POST['mode_names'][$i]);
            $amt = (float)$_POST['mode_amounts'][$i];
            if (!empty($name) && $amt >= 0) {
                $modes[$name] = $amt;
                if ($name === 'Day Scholar') saveSetting('fee_day_scholar', $amt);
                if ($name === 'Hostler') saveSetting('fee_hostler', $amt);
                if ($name === 'Tuition') saveSetting('fee_tuition', $amt);
            }
        }
        $modes_json = json_encode($modes);
        saveSetting('tuition_modes', $modes_json);
    }

    // Save Extra Fees
    if (isset($_POST['extra_fee_names']) && isset($_POST['extra_fee_amounts'])) {
        $extra_fees = [];
        for ($i = 0; $i < count($_POST['extra_fee_names']); $i++) {
            $name = trim($_POST['extra_fee_names'][$i]);
            $amt = (float)$_POST['extra_fee_amounts'][$i];
            if (!empty($name) && $amt >= 0) {
                $extra_fees[$name] = $amt;
            }
        }
        $extra_json = json_encode($extra_fees);
        saveSetting('extra_fees', $extra_json);
    }

    // Save Plan Features
    if (isset($_POST['feature_names'])) {
        $features = [];
        foreach ($_POST['feature_names'] as $index => $feat_name) {
            $feat_name = trim($feat_name);
            if (!empty($feat_name)) {
                $modes = isset($_POST['feature_modes'][$index]) ? $_POST['feature_modes'][$index] : [];
                $features[] = [
                    'feature' => $feat_name,
                    'modes' => $modes
                ];
            }
        }
        $features_json = json_encode($features);
        saveSetting('plan_features', $features_json);
    }

    // Handle Director Image Upload
    if (!empty($_FILES['director_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['director_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed) && $_FILES['director_image']['size'] < 5 * 1024 * 1024) {
            $upload_dir = __DIR__ . '/../uploads/site/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $filename = 'director_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['director_image']['tmp_name'], $upload_dir . $filename)) {
                $path = 'uploads/site/' . $filename;
                saveSetting('director_image_path', $path);
            } else {
                $err = "Failed to upload director image.";
            }
        } else {
            $err = "Invalid image type or file too large (Max 5MB).";
        }
    }

    // Handle Firebase Service Account JSON Upload
    if (!empty($_FILES['service_account_json']['name'])) {
        $ext = strtolower(pathinfo($_FILES['service_account_json']['name'], PATHINFO_EXTENSION));
        if ($ext === 'json' && $_FILES['service_account_json']['size'] < 2 * 1024 * 1024) {
            $jsonContent = file_get_contents($_FILES['service_account_json']['tmp_name']);
            $parsed = json_decode($jsonContent, true);
            if ($parsed && !empty($parsed['private_key']) && !empty($parsed['client_email'])) {
                $targetSaPath = __DIR__ . '/../config/service-account.json';
                if (file_put_contents($targetSaPath, $jsonContent)) {
                    if (!empty($parsed['project_id'])) {
                        saveSetting('firebase_project_id', $parsed['project_id']);
                    }
                    $msg .= " Firebase Service Account JSON uploaded & verified successfully.";
                } else {
                    $err = "Failed to save service-account.json to config directory.";
                }
            } else {
                $err = "Invalid Firebase Service Account JSON. Missing private_key or client_email.";
            }
        } else {
            $err = "Service Account file must be a valid JSON file under 2MB.";
        }
    }

    if (!$err) {
        $msg = "Settings saved successfully." . (isset($msg) ? ' ' . $msg : '');
        if (function_exists('log_activity')) {
            log_activity('settings_update', "Updated global web settings.");
        }
    }
}

// Handle Admin Password Change Verification Code Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_verification_code'])) {
    $code = rand(100000, 999999);
    $_SESSION['admin_verification_code'] = (string)$code;
    $_SESSION['admin_verification_expiry'] = time() + 900; // 15 mins
    
    $admin_email = "sonusagarpoly@gmail.com";
    $subject = "Admin Password Reset Verification Code - ABSS";
    $body = "Hello Admin,\n\nYour verification code to reset your admin password is: $code\n\nThis code is valid for 15 minutes.\n\nIf you did not request this, please ignore.";
    
    $headers = "From: no-reply@abss.com\r\n";
    @mail($admin_email, $subject, $body, $headers);
    
    $msg = "A 6-digit verification code has been sent to $admin_email.";
}

// Handle Admin Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin_password'])) {
    $code = trim($_POST['verification_code'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');
    
    if (isset($_SESSION['admin_verification_code']) && isset($_SESSION['admin_verification_expiry'])) {
        if (time() > $_SESSION['admin_verification_expiry']) {
            $err = "Verification code has expired. Please request a new one.";
        } elseif ($code !== $_SESSION['admin_verification_code']) {
            $err = "Invalid verification code.";
        } elseif (strlen($new_pass) < 6) {
            $err = "Password must be at least 6 characters long.";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed, $_SESSION['admin_id']);
            if ($update_stmt->execute()) {
                $msg = "Admin password updated successfully.";
                unset($_SESSION['admin_verification_code']);
                unset($_SESSION['admin_verification_expiry']);
            } else {
                $err = "Failed to update password.";
            }
        }
    } else {
        $err = "No verification code was requested.";
    }
}

// Fetch Settings
$settings = getAllSettings();
require_once __DIR__ . '/../config/firebase.php';

$saPath = getFirebaseServiceAccountPath();
$saData = ($saPath && file_exists($saPath)) ? json_decode(file_get_contents($saPath), true) : null;
$hasValidSa = ($saData && !empty($saData['private_key']) && !empty($saData['client_email']));

$firebase_project_id = $settings['firebase_project_id'] ?? ($saData['project_id'] ?? 'abss-notification');
$firebase_sender_id  = $settings['firebase_sender_id'] ?? '343001874555';
$firebase_api_key    = $settings['firebase_api_key'] ?? 'AIzaSyCWHzgexBb-ogRJ6ypTTMjbGUT0768wmE8';
$firebase_app_id     = $settings['firebase_app_id'] ?? '1:343001874555:web:7d97e7f76603009b0962de';
$firebase_vapid_key  = $settings['firebase_vapid_key'] ?? 'BLBC9JquNYYaHFTiJuzrH50jyTBweuMdgSDkNZpHlyf_JhPgiPUa1l1bokgWdho1xo4YPpnk33-adM7qX1KcM3M';
$fcm_api_secret      = $settings['fcm_api_secret'] ?? 'abss_fcm_secret_key_2026';

// Ensure default tuition modes exist
$tuition_modes = [];
if (!empty($settings['tuition_modes'])) {
    $tuition_modes = json_decode($settings['tuition_modes'], true);
} else {
    $tuition_modes = ['Hostler' => 5000, 'Day Scholar' => 3000, 'Tuition' => 1500];
}

$razorpay_key_id = $settings['razorpay_key_id'] ?? '';
$razorpay_key_secret = $settings['razorpay_key_secret'] ?? '';

// Ensure default extra fees exist
$extra_fees = [];
if (isset($settings['extra_fees'])) {
    $extra_fees = json_decode($settings['extra_fees'], true) ?: [];
} else {
    $extra_fees = [
        'Registration Fee' => $settings['registration_fee'] ?? '100',
        'Admission Fee' => $settings['admission_fee'] ?? '2000',
        'Annual Development' => $settings['development_fee'] ?? '1000'
    ];
}

// Ensure default plan features exist
$plan_features = [];
if (isset($settings['plan_features'])) {
    $loaded_features = json_decode($settings['plan_features'], true) ?: [];
    foreach ($loaded_features as $feat) {
        if (isset($feat['modes'])) {
            $plan_features[] = $feat;
        } else {
            $modes = [];
            if (!empty($feat['res'])) { $modes[] = 'Hostler'; $modes[] = 'Residential Scholar'; }
            if (!empty($feat['day'])) { $modes[] = 'Day Scholar'; }
            $plan_features[] = [
                'feature' => $feat['feature'],
                'modes' => $modes
            ];
        }
    }
} else {
    $plan_features = [
        ['feature' => '24/7 Secure Residential Hostel Stay', 'modes' => ['Hostler', 'Residential Scholar']],
        ['feature' => 'Hygienic Organic Meals & RO Drinking Water', 'modes' => ['Hostler', 'Residential Scholar']],
        ['feature' => 'Full-Day Intensive Classroom Coaching', 'modes' => ['Hostler', 'Day Scholar', 'Residential Scholar']],
        ['feature' => 'Specialized Mental Ability & Reasoning Drills', 'modes' => ['Hostler', 'Day Scholar', 'Residential Scholar']],
        ['feature' => 'Weekly OMR Test Series & National Ranks', 'modes' => ['Hostler', 'Day Scholar', 'Residential Scholar']],
        ['feature' => '24/7 Warden & Medical Supervision', 'modes' => ['Hostler', 'Residential Scholar']],
        ['feature' => 'Core Subject Foundation & Tuition Classes', 'modes' => ['Hostler', 'Day Scholar', 'Tuition', 'Residential Scholar']]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Settings | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        
        .settings-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .settings-card { 
            background: #ffffff; 
            padding: 28px; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
            border: 1px solid #e2e8f0; 
            margin-bottom: 25px; 
            width: 100%;
        }

        .section-title { 
            color: var(--portal-dark); 
            font-weight: 800; 
            font-size: 1.05rem; 
            margin-bottom: 20px; 
            padding-bottom: 12px; 
            border-bottom: 2px solid #f1f5f9; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            flex-wrap: wrap;
            gap: 10px;
        }

        .mode-row { display: grid; grid-template-columns: 2fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: center; }
        .feature-row { display: grid; grid-template-columns: 2fr auto auto; gap: 12px; margin-bottom: 12px; align-items: center; background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .feature-checkbox { display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #475569; }
        .feature-checkbox input { margin-top: 4px; width: 17px; height: 17px; accent-color: var(--portal-blue); cursor: pointer; }
        
        .btn-remove-mode { background: #fee2e2; color: #dc2626; border: none; padding: 10px 12px; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; }
        .btn-remove-mode:hover { background: #dc2626; color: #ffffff; }

        @media (max-width: 1024px) {
            .settings-grid-2col { grid-template-columns: 1fr; gap: 15px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1 style="margin:0 0 4px 0; font-size:1.8rem;">Global Web Settings</h1>
                <p style="margin:0; color:#64748b;">Configure portal options, pricing, payment gateways, and features matrix.</p>
            </div>
        </div>

        <?php if($msg): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#fee2e2; color:#dc2626; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <form action="settings.php" method="POST" enctype="multipart/form-data">
            
            <!-- 2-COLUMN GRID LAYOUT -->
            <div class="settings-grid-2col">
                
                <!-- COLUMN 1: PAYMENT GATEWAY, EMAIL, FRONTEND & SECURITY -->
                <div>
                    <!-- Razorpay Settings -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-credit-card" style="color:var(--portal-blue);"></i> Razorpay Payment Gateway</span>
                        </h3>
                        <div class="portal-form-row">
                            <div class="portal-input-group">
                                <label><i class="fas fa-key" style="color: var(--portal-blue);"></i> Razorpay Key ID</label>
                                <input type="text" name="settings[razorpay_key_id]" id="razorpay_key_id" value="<?php echo htmlspecialchars($razorpay_key_id); ?>" placeholder="rzp_test_... or rzp_live_...">
                            </div>
                            <div class="portal-input-group">
                                <label><i class="fas fa-lock" style="color: var(--portal-blue);"></i> Razorpay Key Secret</label>
                                <div style="position: relative;">
                                    <input type="text" name="settings[razorpay_key_secret]" id="razorpay_key_secret" value="<?php echo htmlspecialchars($razorpay_key_secret); ?>" placeholder="Enter Razorpay Secret Key">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Firebase FCM Push Notification Settings -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-fire" style="color:#ea580c;"></i> Firebase Push Notifications (FCM HTTP v1)</span>
                            <div style="display: flex; gap: 8px;">
                                <a href="notifications/create.php" class="btn-portal" style="padding: 5px 12px; font-size: 0.75rem; width: auto; background: #ea580c; text-decoration: none;">
                                    <i class="fas fa-paper-plane"></i> Send Push
                                </a>
                                <a href="notifications/index.php" class="btn-portal" style="padding: 5px 12px; font-size: 0.75rem; width: auto; background: #475569; text-decoration: none;">
                                    <i class="fas fa-history"></i> History
                                </a>
                            </div>
                        </h3>
                        
                        <!-- Service Account Health Indicator -->
                        <div style="background: <?php echo $hasValidSa ? '#f0fdf4' : '#fff7ed'; ?>; border: 1px solid <?php echo $hasValidSa ? '#bbf7d0' : '#fed7aa'; ?>; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: <?php echo $hasValidSa ? '#166534' : '#c2410c'; ?>; display: block;">
                                        <i class="fas <?php echo $hasValidSa ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i> 
                                        Service Account (OAuth 2.0 HTTP v1): <?php echo $hasValidSa ? 'Connected & Verified' : 'Missing or Incomplete'; ?>
                                    </span>
                                    <small style="color: #475569; font-weight: 600; font-family: monospace; font-size: 0.75rem; display: block; margin-top: 3px;">
                                        <?php echo $hasValidSa ? htmlspecialchars($saData['client_email'] ?? 'service-account.json') : 'Upload your Firebase Service Account JSON file below'; ?>
                                    </small>
                                </div>
                                <span style="background: <?php echo $hasValidSa ? '#22c55e' : '#f97316'; ?>; color: #fff; padding: 3px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 800;">
                                    <?php echo $hasValidSa ? 'HTTP v1 Active' : 'Action Required'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="portal-form-row">
                            <div class="portal-input-group">
                                <label>Firebase Project ID</label>
                                <input type="text" name="settings[firebase_project_id]" value="<?php echo htmlspecialchars($firebase_project_id); ?>" placeholder="abss-notification">
                            </div>
                            <div class="portal-input-group">
                                <label>Messaging Sender ID (Project Number)</label>
                                <input type="text" name="settings[firebase_sender_id]" value="<?php echo htmlspecialchars($firebase_sender_id); ?>" placeholder="343001874555">
                            </div>
                        </div>

                        <div class="portal-form-row">
                            <div class="portal-input-group">
                                <label>Web API Key</label>
                                <input type="text" name="settings[firebase_api_key]" value="<?php echo htmlspecialchars($firebase_api_key); ?>" placeholder="AIzaSy...">
                            </div>
                            <div class="portal-input-group">
                                <label>Firebase App ID</label>
                                <input type="text" name="settings[firebase_app_id]" value="<?php echo htmlspecialchars($firebase_app_id); ?>" placeholder="1:343001874555:web:...">
                            </div>
                        </div>

                        <div class="portal-input-group">
                            <label>Web Push VAPID Public Key</label>
                            <input type="text" name="settings[firebase_vapid_key]" value="<?php echo htmlspecialchars($firebase_vapid_key); ?>" placeholder="BLBC9JquNYYa...">
                            <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px; display: block;">Generated from Firebase Console &gt; Project Settings &gt; Cloud Messaging &gt; Web configuration.</small>
                        </div>

                        <div class="portal-input-group">
                            <label>Internal Backend API Secret Key (X-API-KEY)</label>
                            <input type="text" name="settings[fcm_api_secret]" value="<?php echo htmlspecialchars($fcm_api_secret); ?>" placeholder="abss_fcm_secret_key_2026">
                            <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px; display: block;">Used for authenticating external POST requests to <code>/api/send-notification.php</code>.</small>
                        </div>

                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label>Update Service Account JSON File</label>
                            <input type="file" name="service_account_json" accept=".json,application/json" class="portal-input">
                            <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px; display: block;">Upload the <code>service-account.json</code> file generated from Google Cloud Console / Firebase Project Settings &gt; Service Accounts.</small>
                        </div>
                    </div>

                    <!-- SMTP Email Settings -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-envelope" style="color:#7c3aed;"></i> SMTP Email Configuration</span>
                        </h3>
                        <div class="portal-form-row">
                            <div class="portal-input-group">
                                <label>SMTP Host</label>
                                <input type="text" name="settings[smtp_host]" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="portal-input-group">
                                <label>SMTP Port</label>
                                <input type="number" name="settings[smtp_port]" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" placeholder="587">
                            </div>
                        </div>
                        <div class="portal-form-row">
                            <div class="portal-input-group">
                                <label>SMTP Username / Email</label>
                                <input type="text" name="settings[smtp_username]" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" placeholder="youremail@gmail.com">
                            </div>
                            <div class="portal-input-group">
                                <label>SMTP Password</label>
                                <input type="password" name="settings[smtp_password]" value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="portal-input-group">
                            <label>Encryption Type</label>
                            <select name="settings[smtp_encryption]">
                                <option value="tls" <?php echo (($settings['smtp_encryption'] ?? 'tls') == 'tls') ? 'selected' : ''; ?>>TLS (Recommended, Port 587)</option>
                                <option value="ssl" <?php echo (($settings['smtp_encryption'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="none" <?php echo (($settings['smtp_encryption'] ?? '') == 'none') ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>

                    <!-- Frontend Content Settings -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-image" style="color:#059669;"></i> Director Photo Upload</span>
                        </h3>
                        <div class="portal-input-group">
                            <label>Secretary / Director Image</label>
                            <?php if(!empty($settings['director_image_path'])): ?>
                                <div style="margin-bottom: 12px;">
                                    <img src="../<?php echo htmlspecialchars($settings['director_image_path']); ?>" alt="Director Image" style="height: 90px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="director_image" accept="image/*" class="portal-input">
                            <small style="color: #64748b; display: block; margin-top: 6px;">Upload a new photo for Secretary Suman Kumar on the homepage.</small>
                        </div>
                    </div>

                    <!-- Admin Account Security Card -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-shield-alt" style="color:#dc2626;"></i> Admin Security Password</span>
                        </h3>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Send a 6-digit verification code to <strong>sonusagarpoly@gmail.com</strong> to update password.</p>
                        
                        <?php if (!isset($_SESSION['admin_verification_code'])): ?>
                            <button type="submit" form="sendCodeForm" name="send_verification_code" class="btn-portal" style="width: auto; padding:10px 18px; font-size:0.88rem;">
                                <i class="fas fa-paper-plane"></i> Send Verification Code
                            </button>
                        <?php else: ?>
                            <div class="portal-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="portal-input-group" style="margin-bottom:0;">
                                    <label>Verification Code</label>
                                    <input type="text" name="verification_code" placeholder="6-digit code">
                                </div>
                                <div class="portal-input-group" style="margin-bottom:0;">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" placeholder="New password" minlength="6">
                                </div>
                            </div>
                            <div style="display:flex; gap: 10px; flex-wrap:wrap;">
                                <button type="submit" name="update_admin_password" class="btn-portal" style="width: auto; padding:10px 18px; font-size:0.88rem;">
                                    <i class="fas fa-save"></i> Update Password
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- COLUMN 2: TUITION MODES, EXTRA FEES & PLAN FEATURES -->
                <div>
                    <!-- Dynamic Tuition Modes -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-money-bill-wave" style="color:#2563eb;"></i> Scholar Modes & Rates</span>
                            <button type="button" class="btn-portal" style="padding: 6px 14px; font-size: 0.78rem; width: auto;" onclick="addModeRow()"><i class="fas fa-plus"></i> Add Mode</button>
                        </h3>
                        
                        <div id="modes-container">
                            <?php foreach ($tuition_modes as $name => $amount): ?>
                                <div class="mode-row">
                                    <div class="portal-input-group" style="margin-bottom:0;">
                                        <input type="text" name="mode_names[]" value="<?php echo htmlspecialchars($name); ?>" placeholder="Mode Name (e.g. Day Scholar)" required>
                                    </div>
                                    <div class="portal-input-group" style="margin-bottom:0;">
                                        <input type="number" name="mode_amounts[]" value="<?php echo htmlspecialchars($amount); ?>" placeholder="Fee (₹)" step="0.01" required>
                                    </div>
                                    <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Home Page Extra Fees -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-calculator" style="color:#059669;"></i> Initial Extra Charges</span>
                            <button type="button" class="btn-portal" style="padding: 6px 14px; font-size: 0.78rem; width: auto;" onclick="addExtraFeeRow()"><i class="fas fa-plus"></i> Add Extra Fee</button>
                        </h3>
                        <div id="extra-fees-container">
                            <?php foreach ($extra_fees as $name => $amount): ?>
                                <div class="mode-row">
                                    <div class="portal-input-group" style="margin-bottom:0;">
                                        <input type="text" name="extra_fee_names[]" value="<?php echo htmlspecialchars($name); ?>" placeholder="Fee Name (e.g. Library Fee)" required>
                                    </div>
                                    <div class="portal-input-group" style="margin-bottom:0;">
                                        <input type="number" name="extra_fee_amounts[]" value="<?php echo htmlspecialchars($amount); ?>" placeholder="Fee (₹)" step="0.01" required>
                                    </div>
                                    <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Plan Features Matrix -->
                    <div class="settings-card">
                        <h3 class="section-title">
                            <span><i class="fas fa-list-ul" style="color:#7c3aed;"></i> Plan Features & Facilities Matrix</span>
                            <button type="button" class="btn-portal" style="padding: 6px 14px; font-size: 0.78rem; width: auto;" onclick="addFeatureRow()"><i class="fas fa-plus"></i> Add Feature</button>
                        </h3>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 18px;">Check the boxes for modes that offer each facility. These render as tick/cross badges on homepage fee cards.</p>
                        
                        <div id="features-container">
                            <?php foreach ($plan_features as $index => $feat): ?>
                                <div class="feature-row">
                                    <div class="portal-input-group" style="margin-bottom:0;">
                                        <input type="text" name="feature_names[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($feat['feature']); ?>" placeholder="Feature title" required>
                                    </div>
                                    <div class="feature-modes-grid" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                        <?php foreach ($tuition_modes as $mode_name => $mode_amount): 
                                            $is_checked = in_array($mode_name, $feat['modes'] ?? []);
                                        ?>
                                        <div class="feature-checkbox">
                                            <span><?php echo htmlspecialchars($mode_name); ?></span>
                                            <input type="checkbox" name="feature_modes[<?php echo $index; ?>][]" value="<?php echo htmlspecialchars($mode_name); ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Save All Settings CTA Button -->
                    <div style="margin-bottom: 30px;">
                        <button type="submit" name="save_settings" class="btn-portal w-100" style="padding: 16px; font-size: 1.1rem; border-radius: 16px;">
                            <i class="fas fa-save"></i> Save All Global Settings
                        </button>
                    </div>
                </div>

            </div>
        </form>

        <!-- Hidden Form for Password Code Trigger -->
        <form id="sendCodeForm" action="settings.php" method="POST" style="display:none;">
            <input type="hidden" name="send_verification_code" value="1">
        </form>

    </main>

    <script>
        function addExtraFeeRow() {
            const container = document.getElementById('extra-fees-container');
            const row = document.createElement('div');
            row.className = 'mode-row';
            row.innerHTML = `
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="text" name="extra_fee_names[]" placeholder="Fee Name (e.g. Exam Fee)" required>
                </div>
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="number" name="extra_fee_amounts[]" placeholder="Fee (₹)" step="0.01" required>
                </div>
                <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }

        let featureIndex = 999;
        function addFeatureRow() {
            featureIndex++;
            const container = document.getElementById('features-container');
            const row = document.createElement('div');
            row.className = 'feature-row';
            
            const modeNames = Array.from(document.querySelectorAll('input[name="mode_names[]"]')).map(input => input.value).filter(val => val.trim() !== '');
            
            let checkboxesHTML = '<div class="feature-modes-grid" style="display: flex; gap: 12px; flex-wrap: wrap;">';
            modeNames.forEach(mode => {
                checkboxesHTML += `
                    <div class="feature-checkbox">
                        <span>${mode}</span>
                        <input type="checkbox" name="feature_modes[${featureIndex}][]" value="${mode}">
                    </div>
                `;
            });
            checkboxesHTML += '</div>';

            row.innerHTML = `
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="text" name="feature_names[${featureIndex}]" placeholder="Feature title" required>
                </div>
                ${checkboxesHTML}
                <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }

        function addModeRow() {
            const container = document.getElementById('modes-container');
            const row = document.createElement('div');
            row.className = 'mode-row';
            row.innerHTML = `
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="text" name="mode_names[]" placeholder="Mode Name (e.g. Hostler)" required>
                </div>
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="number" name="mode_amounts[]" placeholder="Fee (₹)" step="0.01" required>
                </div>
                <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>
