<?php
require_once 'includes/auth.php';

// Handle Settings Update
$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $msg = "Settings updated successfully.";
}

$settings = getAllSettings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Settings | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .settings-card { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .section-header { margin-bottom: 30px; border-bottom: 2px solid #f0f4f8; padding-bottom: 15px; color: var(--portal-blue); font-weight: 800; font-size: 1.4rem; }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .msg-alert { background: #f0fdf4; color: #166534; padding: 15px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 700; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Website Configuration</h1>
            <p>Maintain your school's digital presence.</p>
        </header>

        <?php if($msg): ?>
            <div class="msg-alert"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="settings-card">
            <div class="section-header">General Information</div>
            <div class="settings-grid">
                <div class="portal-input-group">
                    <label>School Name</label>
                    <input type="text" name="settings[school_name]" value="<?php echo htmlspecialchars($settings['school_name']); ?>">
                </div>
                <div class="portal-input-group">
                    <label>Official Phone</label>
                    <input type="text" name="settings[phone]" value="<?php echo htmlspecialchars($settings['phone']); ?>">
                </div>
                <div class="portal-input-group">
                    <label>Official Email</label>
                    <input type="email" name="settings[email]" value="<?php echo htmlspecialchars($settings['email']); ?>">
                </div>
                <div class="portal-input-group">
                    <label>Physical Address</label>
                    <input type="text" name="settings[address]" value="<?php echo htmlspecialchars($settings['address']); ?>">
                </div>
            </div>

            <div class="section-header" style="margin-top: 40px;">Fee Structure (Homepage)</div>
            <div class="settings-grid">
                <div class="portal-input-group">
                    <label>Residential Fee (₹)</label>
                    <input type="text" name="settings[res_fee]" value="<?php echo htmlspecialchars($settings['res_fee']); ?>">
                </div>
                <div class="portal-input-group">
                    <label>Day Boarding Fee (₹)</label>
                    <input type="text" name="settings[day_fee]" value="<?php echo htmlspecialchars($settings['day_fee']); ?>">
                </div>
            </div>

            <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                <button type="submit" name="save_settings" class="btn-portal" style="padding: 18px 60px;">
                    <i class="fas fa-save"></i> Save All Changes
                </button>
            </div>
        </form>
    </main>
</body>
</html>
