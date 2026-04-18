<?php
session_start();
require_once '../config/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'User not found.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Portal | ABSS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --portal-blue: #0d47a1;
            --portal-dark: #002171;
        }

        body {
            background: linear-gradient(135deg, var(--portal-blue) 0%, var(--portal-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 500px;
            background: #f4f7fa; /* Light grey/blue background from image */
            border-radius: 50px; /* Highly rounded as in image */
            padding: 60px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            text-align: center;
        }

        .login-logo { height: 85px; margin-bottom: 25px; }

        .login-header h1 {
            color: #0d47a1;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 40px;
            font-family: 'Outfit', sans-serif;
        }

        .role-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 50px;
        }

        .role-tab {
            background: transparent;
            border: none;
            color: #5c6bc0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: 0.3s;
            padding: 12px 20px;
            border-radius: 15px;
        }

        .role-tab.active {
            background: #fff;
            color: var(--portal-blue);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .portal-input-group {
            text-align: left;
            margin-bottom: 30px;
        }

        .portal-input-group label {
            display: block;
            color: #0d47a1;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .portal-input-group input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #757575;
            border-radius: 4px; /* Sharper inputs as per image */
            font-size: 1rem;
            background: #fff;
        }

        .portal-access-btn {
            width: 80%;
            margin: 30px auto 0;
            display: block;
            background: linear-gradient(to bottom, #1565c0, #0a2f7a);
            color: #fff;
            padding: 20px;
            border-radius: 100px;
            border: 3px solid #000;
            font-size: 1.4rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: 0.3s;
        }

        .portal-access-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .portal-footer {
            margin-top: 50px;
            color: #5c6bc0;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .error-msg {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(211, 47, 47, 0.1);
        }
    </style>
</head>
<body>
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/logo.png" alt="Logo" class="login-logo">
                <h1 style="color: #0d47a1; font-size: 3.5rem; font-weight: 800; margin: 20px 0 40px; font-family: 'Outfit', sans-serif;">Digital Portal</h1>
            </div>

            <div class="role-tabs">
                <button class="role-tab active" data-role="Admin"><i class="fas fa-user-shield"></i> Admin</button>
                <button class="role-tab" data-role="Teacher"><i class="fas fa-chalkboard-teacher"></i> Teacher</button>
                <button class="role-tab" data-role="Parent"><i class="fas fa-user-friends"></i> Parent</button>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="role" id="selectedRole" value="admin">
                
                <div class="portal-input-group">
                    <label>Identification / Username</label>
                    <input type="text" name="username" placeholder="Username or Registration ID" required>
                </div>

                <div class="portal-input-group">
                    <label>Security Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="portal-access-btn">
                    Access Portal Account
                </button>
            </form>

            <div class="portal-footer">
                Protected by ABSS Secure System © 2026
            </div>
        </div>

    <script>
        const tabs = document.querySelectorAll('.role-tab');
        const roleInput = document.getElementById('selectedRole');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                roleInput.value = tab.dataset.role.toLowerCase();
            });
        });

        // Pre-select role based on URL ?role=
        const urlParams = new URLSearchParams(window.location.search);
        const urlRole = urlParams.get('role');
        if (urlRole) {
            const targetTab = Array.from(tabs).find(t => t.dataset.role.toLowerCase() === urlRole.toLowerCase());
            if (targetTab) targetTab.click();
        }
    </script>
</body>
</html>
