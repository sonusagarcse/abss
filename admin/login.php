<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Auto-restore session from persistent cookie if available
verify_and_restore_admin_session();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed. Please refresh and try again.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']);

    $result = authenticate_admin($username, $password, $remember);
    if ($result['success']) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Login | ABSS Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --portal-blue: #0d47a1; --portal-dark: #002171; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .login-card { background: #ffffff; border-radius: 24px; padding: 36px 30px; width: 100%; max-width: 440px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
        .logo-header { text-align: center; margin-bottom: 25px; }
        .logo-header img { height: 58px; margin-bottom: 12px; }
        .logo-header h2 { color: var(--portal-dark); font-size: 1.5rem; font-weight: 900; }
        .logo-header p { color: #64748b; font-size: 0.88rem; margin-top: 4px; font-weight: 600; }
        
        .role-badge { display: inline-flex; align-items: center; gap: 6px; background: #e0e7ff; color: #3730a3; padding: 5px 14px; border-radius: 50px; font-size: 0.76rem; font-weight: 800; text-transform: uppercase; margin-top: 8px; letter-spacing: 0.05em; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
        .input-wrapper { position: relative; }
        .input-wrapper i.input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1rem; pointer-events: none; }
        .form-control { width: 100%; padding: 14px 44px 14px 44px; border: 2px solid #e2e8f0; border-radius: 14px; font-size: 1rem; outline: none; transition: 0.25s; color: #0f172a; font-weight: 600; }
        .form-control:focus { border-color: var(--portal-blue); box-shadow: 0 0 0 4px rgba(13,71,161,0.12); }
        
        .toggle-password-btn { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; font-size: 1.1rem; cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; }
        .toggle-password-btn:hover { color: var(--portal-blue); }

        .btn-login { width: 100%; background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%); color: white; padding: 15px; border: none; border-radius: 14px; font-weight: 800; font-size: 1rem; cursor: pointer; transition: 0.25s; margin-top: 10px; box-shadow: 0 6px 18px rgba(13, 71, 161, 0.25); display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-login:hover { background: linear-gradient(135deg, #0a3880 0%, #0d47a1 100%); transform: translateY(-2px); box-shadow: 0 10px 25px rgba(13, 71, 161, 0.35); }

        .alert-danger { background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 12px; font-size: 0.88rem; font-weight: 700; margin-bottom: 20px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px; }

        .other-portals { margin-top: 25px; border-top: 1px solid #f1f5f9; padding-top: 18px; text-align: center; font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .portal-links { display: flex; justify-content: center; gap: 14px; margin-top: 8px; }
        .portal-links a { color: var(--portal-blue); text-decoration: none; font-weight: 800; }
        .portal-links a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .login-card { padding: 28px 20px; border-radius: 20px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-header">
            <img src="../assets/logo.png" alt="ABSS Logo">
            <h2>ABSS Administration</h2>
            <div class="role-badge"><i class="fas fa-shield-alt"></i> Control Panel Login</div>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="adminLoginForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="form-group">
                <label for="username">Admin Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Enter admin username" autofocus autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password" autocomplete="current-password" autocapitalize="none" autocorrect="off">
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="pwdEyeIcon"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; font-size: 0.85rem;">
                <label style="display: inline-flex; align-items: center; gap: 8px; color: #475569; font-weight: 700; cursor: pointer; text-transform: none;">
                    <input type="checkbox" name="remember_me" value="1" checked style="width: 17px; height: 17px; accent-color: var(--portal-blue); cursor: pointer;">
                    Remember login on this device
                </label>
            </div>

            <button type="submit" id="btnLogin" class="btn-login">
                <span>Login to Admin Portal</span> &rarr;
            </button>
        </form>

        <div class="other-portals">
            <p>Accessing another portal?</p>
            <div class="portal-links">
                <a href="../parent/login.php"><i class="fas fa-user-friends"></i> Parent Login</a>
                <span>|</span>
                <a href="../teacher/login.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            var pwdField = document.getElementById('password');
            var eyeIcon = document.getElementById('pwdEyeIcon');
            if (pwdField.type === 'password') {
                pwdField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                pwdField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        document.getElementById('adminLoginForm')?.addEventListener('submit', function() {
            var btn = document.getElementById('btnLogin');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
                btn.style.opacity = '0.85';
                btn.style.pointerEvents = 'none';
            }
        });
    </script>
</body>
</html>
