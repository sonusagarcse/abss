<?php
// counselor/login.php - Counselor Authentication Gateway
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Auto-restore session from persistent cookie if available
verify_and_restore_counselor_session();

// Redirect to dashboard if already authenticated
if (isset($_SESSION['counselor_id'])) {
    header("Location: ../admin/dashboard.php");
    exit();
}

$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'inactive') {
        $error = 'Your counselor account is currently inactive. Please contact the administration office.';
    } elseif ($_GET['error'] === 'unauthorized') {
        $error = 'You do not have permission to access that section.';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed. Please refresh and try again.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    $result = authenticate_counselor($username, $password, $remember);
    if ($result['success']) {
        $redirect = $result['redirect'] ?? 'dashboard.php';
        header("Location: ../admin/dashboard.php");
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
    <title>Counselor Login | ABSS Counseling Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --counselor-teal: #0d9488; 
            --counselor-dark: #0f172a; 
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        
        body { 
            background: linear-gradient(135deg, #042f2e 0%, #0f172a 50%, #134e4a 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
            position: relative;
            overflow: hidden;
        }

        .ambient-blob {
            position: absolute;
            filter: blur(80px);
            opacity: 0.25;
            pointer-events: none;
            border-radius: 50%;
        }
        .blob-1 {
            width: 350px;
            height: 350px;
            background: #14b8a6;
            top: -50px;
            left: -50px;
        }
        .blob-2 {
            width: 320px;
            height: 320px;
            background: #2563eb;
            bottom: -50px;
            right: -50px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 28px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            position: relative;
            z-index: 10;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-header img {
            height: 60px;
            margin-bottom: 14px;
        }
        .brand-header h1 {
            font-size: 1.55rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .brand-header p {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }

        .badge-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ccfbf1;
            color: #0f766e;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .input-group {
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .form-control {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 600;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background: #ffffff;
            border-color: #0d9488;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
        }
        .form-control:focus + i {
            color: #0d9488;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-size: 0.98rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.35);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 24px;
        }
        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(13, 148, 136, 0.45);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .portal-links {
            margin-top: 26px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 18px;
            font-size: 0.82rem;
            color: #64748b;
        }
        .portal-links a {
            color: #0d9488;
            text-decoration: none;
            font-weight: 700;
        }
        .portal-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="ambient-blob blob-1"></div>
    <div class="ambient-blob blob-2"></div>

    <div class="login-card">
        <div class="brand-header">
            <img src="../assets/logo.png" alt="ABSS Logo">
            <h1>Amar Bharat Siksha Sansthan</h1>
            <p>Admission Counselor &amp; Lead Desk</p>
            <div class="badge-role">
                <i class="fas fa-user-shield"></i> Counselor Access
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

            <div class="form-group">
                <label class="form-label">Email Address or Mobile</label>
                <div class="input-group">
                    <input type="text" name="username" class="form-control" required placeholder="counselor@abss.com" autofocus>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; color: #475569;">
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="checkbox" name="remember" checked style="accent-color: #0d9488; width: 16px; height: 16px;">
                    Keep me logged in
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign In to Console</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="portal-links">
            <p>Looking for another portal?</p>
            <div style="display: flex; justify-content: center; gap: 14px; margin-top: 6px;">
                <a href="../admin/login.php">Admin</a> •
                <a href="../teacher/login.php">Teacher</a> •
                <a href="../parent/login.php">Parent</a>
            </div>
        </div>
    </div>
</body>
</html>

