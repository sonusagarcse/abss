<?php
// counselor/includes/auth.php - Counselor Session Authorization & Granular Module Gatekeeper

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

$conn = getDB();

// 1. Auto-restore counselor session from persistent cookie if available
verify_and_restore_counselor_session();

// 2. Check counselor login
if (!isset($_SESSION['counselor_id']) || (int)$_SESSION['counselor_id'] <= 0) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $login_path = (strpos($host, 'localhost') !== false) ? '/abss/counselor/login' : '/counselor/login';
    header("Location: $login_path");
    exit();
}

$counselor_id = (int)$_SESSION['counselor_id'];

// 3. Real-time Status & Permissions Verification from DB
// Ensures any permission or status changes made by Admin take effect immediately!
$stmt = $conn->prepare("SELECT id, name, email, designation, permissions, status FROM counselors WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$counselor_record = $stmt->get_result()->fetch_assoc();

if (!$counselor_record || $counselor_record['status'] !== 'active') {
    // Account inactive or deleted
    unset($_SESSION['counselor_id']);
    unset($_SESSION['counselor_name']);
    unset($_SESSION['counselor_email']);
    unset($_SESSION['counselor_designation']);
    unset($_SESSION['counselor_permissions']);
    clear_counselor_remember_cookie();
    
    header("Location: login.php?error=inactive");
    exit();
}

// Refresh session data
$_SESSION['counselor_name'] = $counselor_record['name'];
$_SESSION['counselor_email'] = $counselor_record['email'];
$_SESSION['counselor_designation'] = $counselor_record['designation'] ?: 'Admission Counselor';

$live_perms = !empty($counselor_record['permissions']) ? json_decode($counselor_record['permissions'], true) : [];
if (!is_array($live_perms)) $live_perms = [];
$_SESSION['counselor_permissions'] = $live_perms;
$counselor_permissions = $live_perms;

/**
 * Gatekeeper helper to enforce module permissions on individual pages
 */
function require_counselor_module($module_key) {
    global $counselor_permissions, $counselor_record;
    
    // Check if permission is present
    if (!in_array($module_key, $counselor_permissions, true)) {
        http_response_code(403);
        $catalog = get_counselor_modules_catalog();
        $module_name = $catalog[$module_key]['label'] ?? ucfirst($module_key);
        $module_icon = $catalog[$module_key]['icon'] ?? 'fas fa-lock';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Restricted | ABSS Counselor Portal</title>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
                body { background: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
                .denied-card { background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 40px; max-width: 520px; width: 100%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.04); }
                .denied-icon { width: 72px; height: 72px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; }
                h1 { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-bottom: 10px; }
                p { color: #64748b; font-size: 0.92rem; line-height: 1.5; margin-bottom: 24px; }
                .module-badge { display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 8px 16px; border-radius: 50px; font-weight: 700; color: #334155; font-size: 0.88rem; margin-bottom: 20px; }
                .btn { display: inline-flex; align-items: center; gap: 8px; background: #0d9488; color: #fff; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: background 0.2s; }
                .btn:hover { background: #0f766e; }
            </style>
        </head>
        <body>
            <div class="denied-card">
                <div class="denied-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1>Module Access Restricted</h1>
                <div class="module-badge">
                    <i class="<?php echo $module_icon; ?>"></i> <?php echo htmlspecialchars($module_name); ?>
                </div>
                <p>
                    The administration has not enabled access to this module for your counselor account.
                    Please contact the Institute Administrator if you require access to this section.
                </p>
                <a href="dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Return to Counselor Dashboard
                </a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}
?>

