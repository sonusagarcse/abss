<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Check if persistent cookie can auto-authenticate parent
if (!isset($_SESSION['parent_id']) && isset($_COOKIE['abss_parent_remember'])) {
    $cookie_data = explode(':', $_COOKIE['abss_parent_remember'], 2);
    if (count($cookie_data) === 2) {
        $pid = (int)$cookie_data[0];
        $token_hash = $cookie_data[1];
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, parent_name, email FROM parents WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($parent = $res->fetch_assoc()) {
            $secret_key = defined('DB_PASS') ? DB_PASS . '_ABSS_AUTH_SECRET' : 'ABSS_AUTH_SECRET';
            $expected_hash = hash_hmac('sha256', $parent['id'] . '|' . $parent['email'], $secret_key);
            if (hash_equals($expected_hash, $token_hash)) {
                $_SESSION['parent_id'] = $parent['id'];
                $_SESSION['parent_name'] = $parent['parent_name'];
                $_SESSION['parent_email'] = $parent['email'];
            }
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION['parent_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = authenticate_parent($username, $password);
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
</body>
</html>
