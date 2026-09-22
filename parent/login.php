<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Check if persistent cookie can auto-authenticate parent
verify_and_restore_parent_session();

// Redirect if already logged in
if (isset($_SESSION['parent_id']) && (int)$_SESSION['parent_id'] > 0) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'inactive') {
    $error = 'Your student account is currently inactive. Please contact the school administration.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = authenticate_parent($username, $password);
    if ($result['success']) {
        $pId = (int)$_SESSION['parent_id'];
        $fcmToken = trim($_POST['fcm_token'] ?? $_GET['fcm_token'] ?? $_COOKIE['abss_fcm_token'] ?? '');
        if ($pId > 0 && !empty($fcmToken) && strlen($fcmToken) >= 20) {
            $conn = getDB();
            $sQ = $conn->query("SELECT id FROM students WHERE parent_id = $pId ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, id ASC LIMIT 1");
            $sId = ($sQ && $sRow = $sQ->fetch_assoc()) ? (int)$sRow['id'] : null;
            $upStmt = $conn->prepare("
                INSERT INTO fcm_tokens (token, device_type, app_version, parent_id, student_id)
                VALUES (?, 'android', '2.4.3', ?, ?)
                ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), student_id = VALUES(student_id), updated_at = NOW()
            ");
            $upStmt->bind_param("sii", $fcmToken, $pId, $sId);
            $upStmt->execute();
            $upStmt->close();
        }
        header("Location: " . $result['redirect']);
        exit();
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Login | ABSS Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --portal-green: #059669; --portal-dark: #065f46; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: #f0fdf4; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: #ffffff; border-radius: 24px; padding: 40px; width: 100%; max-width: 440px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); border: 1px solid #dcfce7; }
        .logo-header { text-align: center; margin-bottom: 30px; }
        .logo-header img { height: 60px; margin-bottom: 12px; }
        .logo-header h2 { color: var(--portal-dark); font-size: 1.6rem; font-weight: 800; }
        .logo-header p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        
        .role-badge { display: inline-flex; align-items: center; gap: 6px; background: #dcfce7; color: #166534; padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-top: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .form-control { width: 100%; padding: 14px 16px 14px 44px; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 0.95rem; outline: none; transition: 0.3s; }
        .form-control:focus { border-color: var(--portal-green); box-shadow: 0 0 0 4px rgba(5,150,105,0.1); }

        .btn-login { width: 100%; background: var(--portal-green); color: white; padding: 14px; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-login:hover { background: var(--portal-dark); transform: translateY(-2px); }

        .alert-danger { background: #fee2e2; color: #b91c1c; padding: 12px 16px; border-radius: 10px; font-size: 0.88rem; font-weight: 600; margin-bottom: 20px; border: 1px solid #fecaca; }

        .other-portals { margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: center; font-size: 0.85rem; color: #64748b; }
        .portal-links { display: flex; justify-content: center; gap: 15px; margin-top: 10px; }
        .portal-links a { color: var(--portal-green); text-decoration: none; font-weight: 700; }
        .portal-links a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            body { padding: 15px; }
            .login-card { padding: 30px 20px; }
            .logo-header h2 { font-size: 1.4rem; }
            .portal-links { flex-direction: column; gap: 12px; }
            .portal-links span { display: none; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-header">
            <img src="../assets/logo.png" alt="ABSS Logo">
            <h2>ABSS Parent Portal</h2>
            <div class="role-badge"><i class="fas fa-user-friends"></i> Parent Module</div>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="fcm_token" id="login_fcm_token" value="">
            
            <div class="form-group">
                <label for="username">Registered Email / Mobile Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Email or 10-digit mobile" autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
            </div>

            <button type="submit" class="btn-login">Login to Parent Portal &rarr;</button>
        </form>

        <div class="other-portals">
            <p>Accessing another portal?</p>
            <div class="portal-links">
                <a href="../admin/login.php"><i class="fas fa-shield-alt"></i> Admin Login</a>
                <span>|</span>
                <a href="../teacher/login.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
            </div>
        </div>
    </div>

    <?php
    $base_path = '/';
    if (defined('APP_URL') && !empty(APP_URL)) {
        $parsed_path = parse_url(APP_URL, PHP_URL_PATH);
        if (!empty($parsed_path)) {
            $base_path = rtrim($parsed_path, '/') . '/';
        }
    } elseif (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/abss/') !== false) {
        $base_path = '/abss/';
    }
    ?>
    <!-- ABSS FCM Device Token Hook for Native Android WebView App -->
    <script>
        window.ABSS_BASE_PATH = <?php echo json_encode($base_path); ?>;
    </script>
    <script src="../js/fcm-client.js"></script>
    <script>
        function syncLoginFcmToken() {
            var el = document.getElementById('login_fcm_token');
            if (!el) return;
            var token = el.value || '';
            if (!token) {
                try {
                    token = localStorage.getItem('abss_fcm_token') || '';
                } catch(e) {}
            }
            if (!token && window.Android && typeof window.Android.getFcmToken === 'function') {
                try { token = window.Android.getFcmToken(); } catch(e) {}
            }
            if (!token && window.WebToApp && typeof window.WebToApp.getFcmToken === 'function') {
                try { token = window.WebToApp.getFcmToken(); } catch(e) {}
            }
            if (!token && window.ShiahoApp && typeof window.ShiahoApp.getFcmToken === 'function') {
                try { token = window.ShiahoApp.getFcmToken(); } catch(e) {}
            }
            if (!token) {
                var match = document.cookie.match(/abss_fcm_token=([^;]+)/);
                if (match) token = decodeURIComponent(match[1]);
            }
            if (token) {
                el.value = token;
            }
        }
        document.addEventListener("DOMContentLoaded", syncLoginFcmToken);
        syncLoginFcmToken();
        var loginForm = document.querySelector('form');
        if (loginForm) {
            loginForm.addEventListener('submit', syncLoginFcmToken);
        }
    </script>
</body>
</html>
