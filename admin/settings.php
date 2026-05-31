<?php
// admin/settings.php - Manage Global Web Settings
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $conn->begin_transaction();
    try {
        // Save simple key-value settings
        foreach (['razorpay_key_id', 'razorpay_key_secret', 'res_fee', 'day_fee'] as $k) {
            if (isset($_POST['settings'][$k])) {
                $v = $conn->real_escape_string(trim($_POST['settings'][$k]));
                $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value = '$v'");
            }
        }
        
        // Save tuition modes
        if (isset($_POST['mode_names']) && isset($_POST['mode_amounts'])) {
            $modes = [];
            foreach ($_POST['mode_names'] as $i => $name) {
                $n = trim($name);
                $a = (float)($_POST['mode_amounts'][$i] ?? 0);
                if (!empty($n) && $a >= 0) {
                    $modes[$n] = $a;
                }
            }
            if (!empty($modes)) {
                $modes_json = $conn->real_escape_string(json_encode($modes));
                $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('tuition_modes', '$modes_json') ON DUPLICATE KEY UPDATE setting_value = '$modes_json'");
            }
        }

        // Save fee extras
        if (isset($_POST['extra_fee_names']) && isset($_POST['extra_fee_amounts'])) {
            $extra_fees = [];
            foreach ($_POST['extra_fee_names'] as $i => $name) {
                $n = trim($name);
                $a = (float)($_POST['extra_fee_amounts'][$i] ?? 0);
                if (!empty($n) && $a >= 0) {
                    $extra_fees[$n] = $a;
                }
            }
            $extra_fees_json = $conn->real_escape_string(json_encode($extra_fees));
            $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('extra_fees', '$extra_fees_json') ON DUPLICATE KEY UPDATE setting_value = '$extra_fees_json'");
        }

        // Save plan features
        if (isset($_POST['feature_names'])) {
            $plan_features = [];
            foreach ($_POST['feature_names'] as $i => $name) {
                $n = trim($name);
                if (!empty($n)) {
                    $plan_features[] = [
                        'feature' => $n,
                        'res' => !empty($_POST['feature_res'][$i]),
                        'day' => !empty($_POST['feature_day'][$i])
                    ];
                }
            }
            $plan_features_json = $conn->real_escape_string(json_encode($plan_features));
            $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('plan_features', '$plan_features_json') ON DUPLICATE KEY UPDATE setting_value = '$plan_features_json'");
        }
        
        // Handle Director Image Upload
        if (isset($_FILES['director_image']) && $_FILES['director_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/settings/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['director_image']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_filename = 'director_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['director_image']['tmp_name'], $target_path)) {
                    $db_path = 'uploads/settings/' . $new_filename;
                    $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('director_image_path', '$db_path') ON DUPLICATE KEY UPDATE setting_value = '$db_path'");
                }
            }
        }

        $conn->commit();
        $msg = "Settings updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $err = "Error updating settings.";
    }
}

// Fetch Settings
$settings = getAllSettings();

// Ensure default tuition modes exist
$tuition_modes = [];
if (!empty($settings['tuition_modes'])) {
    $tuition_modes = json_decode($settings['tuition_modes'], true);
} else {
    // Migrate from old static settings or set defaults
    $fee_day_scholar = $settings['fee_day_scholar'] ?? '3000';
    $fee_hostler = $settings['fee_hostler'] ?? '5000';
    $tuition_modes = [
        'Day Scholar' => $fee_day_scholar,
        'Hostler' => $fee_hostler
    ];
}

$razorpay_key_id = $settings['razorpay_key_id'] ?? '';
$razorpay_key_secret = $settings['razorpay_key_secret'] ?? '';

// Ensure default extra fees exist
$extra_fees = [];
if (isset($settings['extra_fees'])) {
    $extra_fees = json_decode($settings['extra_fees'], true) ?: [];
} else {
    // Migrate from old static settings or set defaults
    $extra_fees = [
        'Registration Fee' => $settings['registration_fee'] ?? '100',
        'Admission Fee' => $settings['admission_fee'] ?? '2000',
        'Annual Development' => $settings['development_fee'] ?? '1000'
    ];
}
// Ensure default plan features exist
$plan_features = [];
if (isset($settings['plan_features'])) {
    $plan_features = json_decode($settings['plan_features'], true) ?: [];
} else {
    // Defaults based on previous hardcoded features
    $plan_features = [
        ['feature' => 'Hostel & Quality Meals included', 'res' => true, 'day' => false],
        ['feature' => 'Intensive Classroom Training', 'res' => true, 'day' => true]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Settings | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .settings-card { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); max-width: 700px; margin-bottom: 30px; }
        .section-title { color: var(--portal-blue); font-weight: 800; font-size: 1.1rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eef2ff; display:flex; align-items:center; justify-content:space-between; }
        .mode-row { display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; margin-bottom: 15px; align-items: center; }
        .feature-row { display: grid; grid-template-columns: 2fr auto auto auto; gap: 15px; margin-bottom: 15px; align-items: center; background: #f8faff; padding: 15px; border-radius: 12px; border: 1px solid #eef2ff; }
        .feature-checkbox { display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; color: var(--portal-blue); }
        .feature-checkbox input { margin-top: 5px; width: 18px; height: 18px; accent-color: var(--portal-blue); cursor: pointer; }
        .btn-remove-mode { background: #feeef2; color: #d32f2f; border: none; padding: 12px; border-radius: 12px; cursor: pointer; transition: 0.3s; }
        .btn-remove-mode:hover { background: #d32f2f; color: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <h1>Global Web Settings</h1>
            <p style="margin:0;">Manage default variables and options for the portal.</p>
        </div>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#feeef2; color:#d32f2f; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <!-- Razorpay Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <span><i class="fas fa-credit-card"></i> Razorpay Payment Gateway</span>
                </h3>
                <div class="portal-form-row">
                    <div class="portal-input-group">
                        <label>Razorpay Key ID</label>
                        <input type="text" name="settings[razorpay_key_id]" value="<?php echo htmlspecialchars($razorpay_key_id); ?>" placeholder="rzp_test_...">
                    </div>
                    <div class="portal-input-group">
                        <label>Razorpay Key Secret</label>
                        <input type="password" name="settings[razorpay_key_secret]" value="<?php echo htmlspecialchars($razorpay_key_secret); ?>" placeholder="Secret Key">
                    </div>
                </div>
            </div>

            <!-- Frontend Content Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <span><i class="fas fa-image"></i> Frontend Content</span>
                </h3>
                <div class="portal-input-group">
                    <label>Director Image (Home Page)</label>
                    <?php if(!empty($settings['director_image_path'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../<?php echo htmlspecialchars($settings['director_image_path']); ?>" alt="Director Image" style="height: 100px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="director_image" accept="image/*" class="portal-input">
                    <small style="color: #666; display: block; margin-top: 5px;">Upload a new image to replace the existing one on the frontend (JPG, PNG, WEBP).</small>
                </div>
            </div>

            <!-- Home Page Fee Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <span><i class="fas fa-money-check-alt"></i> Home Page Fee Structure</span>
                    <button type="button" class="btn-portal" style="padding: 5px 15px; font-size: 0.8rem; width: auto;" onclick="addExtraFeeRow()"><i class="fas fa-plus"></i> Add Extra Fee</button>
                </h3>
                <div class="portal-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="portal-input-group">
                        <label>Residential Scholar Fee (/month)</label>
                        <input type="number" name="settings[res_fee]" value="<?php echo htmlspecialchars($settings['res_fee'] ?? '5000'); ?>" placeholder="5000" step="0.01">
                    </div>
                    <div class="portal-input-group">
                        <label>Day Scholar Fee (/month)</label>
                        <input type="number" name="settings[day_fee]" value="<?php echo htmlspecialchars($settings['day_fee'] ?? '3000'); ?>" placeholder="3000" step="0.01">
                    </div>
                </div>
                
                <h4 style="color: var(--portal-blue); font-size: 0.95rem; margin-bottom: 10px;">One-time / Annual Extra Fees (Added to Initial Payment)</h4>
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

            <!-- Dynamic Tuition Modes -->
            <div class="settings-card">
                <h3 class="section-title">
                    <span><i class="fas fa-money-bill-wave"></i> Tuition Fee Modes</span>
                    <button type="button" class="btn-portal" style="padding: 5px 15px; font-size: 0.8rem; width: auto;" onclick="addModeRow()"><i class="fas fa-plus"></i> Add Mode</button>
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

            <!-- Plan Features Matrix -->
            <div class="settings-card" style="max-width: 800px;">
                <h3 class="section-title">
                    <span><i class="fas fa-list-ul"></i> Plan Features Matrix</span>
                    <button type="button" class="btn-portal" style="padding: 5px 15px; font-size: 0.8rem; width: auto;" onclick="addFeatureRow()"><i class="fas fa-plus"></i> Add Feature</button>
                </h3>
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 20px;">Add features and check the boxes to indicate which plans offer them. These will appear dynamically on the frontend pricing cards.</p>
                
                <div id="features-container">
                    <?php foreach ($plan_features as $index => $feat): ?>
                        <div class="feature-row">
                            <div class="portal-input-group" style="margin-bottom:0;">
                                <input type="text" name="feature_names[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($feat['feature']); ?>" placeholder="Feature (e.g. 24/7 Library Access)" required>
                            </div>
                            <div class="feature-checkbox">
                                <span>Residential</span>
                                <input type="hidden" name="feature_res[<?php echo $index; ?>]" value="0">
                                <input type="checkbox" name="feature_res[<?php echo $index; ?>]" value="1" <?php echo !empty($feat['res']) ? 'checked' : ''; ?>>
                            </div>
                            <div class="feature-checkbox">
                                <span>Day Scholar</span>
                                <input type="hidden" name="feature_day[<?php echo $index; ?>]" value="0">
                                <input type="checkbox" name="feature_day[<?php echo $index; ?>]" value="1" <?php echo !empty($feat['day']) ? 'checked' : ''; ?>>
                            </div>
                            <button type="button" class="btn-remove-mode" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="max-width: 700px;">
                <button type="submit" name="save_settings" class="btn-portal w-100" style="padding: 15px; font-size: 1.1rem;"><i class="fas fa-save"></i> Save All Settings</button>
            </div>
        </form>
    </main>

    <script>
        function addExtraFeeRow() {
            const container = document.getElementById('extra-fees-container');
            const row = document.createElement('div');
            row.className = 'mode-row';
            row.innerHTML = `
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="text" name="extra_fee_names[]" placeholder="Fee Name (e.g. Library Fee)" required>
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
            row.innerHTML = `
                <div class="portal-input-group" style="margin-bottom:0;">
                    <input type="text" name="feature_names[${featureIndex}]" placeholder="Feature (e.g. 24/7 Library Access)" required>
                </div>
                <div class="feature-checkbox">
                    <span>Residential</span>
                    <input type="hidden" name="feature_res[${featureIndex}]" value="0">
                    <input type="checkbox" name="feature_res[${featureIndex}]" value="1">
                </div>
                <div class="feature-checkbox">
                    <span>Day Scholar</span>
                    <input type="hidden" name="feature_day[${featureIndex}]" value="0">
                    <input type="checkbox" name="feature_day[${featureIndex}]" value="1">
                </div>
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
