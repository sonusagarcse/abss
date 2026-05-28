<?php
// parent/settings.php - Parent Portal Security Settings

require_once 'includes/auth.php';

$msg = '';
$err = '';

$pid = (int)$_SESSION['parent_id'];

// Handle Password Modification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $err = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $err = "New password and confirmation password do not match.";
    } elseif (strlen($new_pass) < 6) {
        $err = "New password must be at least 6 characters long.";
    } else {
        // Fetch current hashed password
        $check_stmt = $conn->prepare("SELECT password, parent_name, email FROM parents WHERE id = ?");
        $check_stmt->bind_param("i", $pid);
        $check_stmt->execute();
        $parent = $check_stmt->get_result()->fetch_assoc();

        if ($parent && password_verify($current_pass, $parent['password'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE parents SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hash, $pid);
            
            if ($update_stmt->execute()) {
                $msg = "Password changed successfully.";
                
                // Log Parent Security Activity
                log_activity('password_changed', "Parent updated account security password");

                // Send email notification regarding security change
                require_once __DIR__ . '/../includes/mail_helper.php';
                $email_html = get_base_template(
                    "Security Update: Password Changed",
                    '<div class="greeting">Security Alert: Password Updated</div>
                     <p>Dear ' . htmlspecialchars($parent['parent_name']) . ',</p>
                     <p>This email is to confirm that the security password for your ABSS Parent Portal account (<b>' . htmlspecialchars($parent['email']) . '</b>) was successfully updated on <b>' . date('d F, Y \a\t h:i A') . '</b>.</p>
                     <p><b>If you initiated this change:</b> No further action is required.</p>
                     <p style="color:#d32f2f; font-weight:700;"><b>If you did not initiate this change:</b> Please reach out to the school administrative desk immediately to secure your account.</p>'
                );
                
                send_smtp_email(
                    $parent['email'],
                    "Security Alert: Password Changed - ABSS Portal",
                    $email_html
                );
            } else {
                $err = "Error updating password. Please try again.";
            }
        } else {
            $err = "Incorrect current password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Account Settings</h1>
            <p>Manage your parent portal password credentials and security alerts.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#ffebee; color:#d32f2f; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700; border: 1px solid rgba(211,47,47,0.1);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <div style="max-width: 600px;">
            <div class="portal-card">
                <h3 style="margin-bottom: 25px; color:var(--portal-indigo);"><i class="fas fa-key" style="margin-right:8px; color:var(--portal-purple);"></i> Update Password</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Current Security Password</label>
                        <input type="password" name="current_password" placeholder="••••••••" required>
                    </div>
                    <div class="portal-input-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="••••••••" minlength="6" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" minlength="6" required>
                    </div>
                    <button type="submit" name="update_password" class="btn-portal w-100" style="padding:18px;">Save Password Changes</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
