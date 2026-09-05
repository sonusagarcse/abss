<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Redirect if already logged in
if (isset($_SESSION['teacher_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'inactive') {
    $error = 'Your teacher account is currently inactive. Please contact the administration office.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = authenticate_teacher($username, $password);
    if ($result['success']) {
        $redirect = $result['redirect'] ?? 'dashboard.php';
        header("Location: " . $redirect);
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
    <title>Teacher Login | ABSS Faculty Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --teacher-purple: #7c3aed; 
            --teacher-dark: #4c1d95; 
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #4c1d95 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
            position: relative;
            overflow: hidden;
        }

        .bg-glow-1 {
            position: absolute;
            top: -120px;
            left: -120px;
            width: 400px;
            height: 400px;
            background: rgba(124, 58, 237, 0.35);
            filter: blur(100px);
            border-radius: 50%;
        }

        .bg-glow-2 {
            position: absolute;
            bottom: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            background: rgba(99, 102, 241, 0.35);
            filter: blur(100px);
            border-radius: 50%;
        }

        .login-card { 
            background: rgba(255, 255, 255, 0.94); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px; 
            padding: 42px 36px; 
            width: 100%; 
            max-width: 440px; 
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3); 
            border: 1px solid rgba(255, 255, 255, 0.4); 
            position: relative;
            z-index: 10;
        }

        .logo-header { text-align: center; margin-bottom: 28px; }
        .logo-header img { height: 68px; margin-bottom: 12px; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.1)); }
        .logo-header h2 { color: #1e1b4b; font-size: 1.65rem; font-weight: 900; margin: 0; }
        .logo-header p { color: #64748b; font-size: 0.9rem; margin-top: 4px; font-weight: 500; }
        
        .role-badge { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            background: rgba(124, 58, 237, 0.1); 
            color: var(--teacher-purple); 
            border: 1px solid rgba(124, 58, 237, 0.25);
            padding: 5px 16px; 
            border-radius: 50px; 
            font-size: 0.78rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            margin-top: 10px; 
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 800; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; }
        
        .form-control { 
            width: 100%; 
            padding: 14px 16px 14px 46px; 
            border: 2px solid #cbd5e1; 
            border-radius: 14px; 
            font-size: 1rem; 
            font-weight: 600;
            color: #1e293b;
            outline: none; 
            transition: all 0.25s ease; 
            background: #ffffff;
        }
        .form-control:focus { 
            border-color: var(--teacher-purple); 
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12); 
        }

        .btn-login { 
            width: 100%; 
            background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark)); 
            color: white; 
            padding: 14px; 
            border: none; 
            border-radius: 14px; 
            font-weight: 900; 
            font-size: 1rem; 
            cursor: pointer; 
            transition: all 0.25s ease; 
            margin-top: 10px; 
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(124, 58, 237, 0.4); }

        .alert-danger { 
            background: #fee2e2; 
            color: #b91c1c; 
            padding: 12px 16px; 
            border-radius: 12px; 
            font-size: 0.88rem; 
            font-weight: 700; 
            margin-bottom: 20px; 
            border: 1px solid #fecaca; 
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .other-portals { margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 18px; text-align: center; font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .portal-links { display: flex; justify-content: center; gap: 15px; margin-top: 10px; }
        .portal-links a { color: var(--teacher-purple); text-decoration: none; font-weight: 800; }
        .portal-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="login-card">
        <div class="logo-header">
            <img src="../assets/logo.png" alt="ABSS Logo">
            <h2>ABSS Faculty Portal</h2>
            <div class="role-badge"><i class="fas fa-chalkboard-teacher"></i> Teacher Module</div>
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
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Email or mobile number" autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
            </div>

            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login to Teacher Portal &rarr;</button>
        </form>

        <div class="other-portals">
            <p>Accessing another portal?</p>
            <div class="portal-links">
                <a href="../admin/login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
                <span>|</span>
                <a href="../parent/login.php"><i class="fas fa-user-friends"></i> Parent Login</a>
            </div>
        </div>
    </div>
</body>
</html>
