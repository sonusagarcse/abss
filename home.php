<?php
require_once 'config/db.php';
$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ABSS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
            font-family: 'Outfit', sans-serif;
            margin: 0;
        }

        .home-card {
            width: 100%;
            max-width: 500px;
            background: #f4f7fa; 
            border-radius: 50px; 
            padding: 60px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            text-align: center;
        }

        .home-logo { height: 120px; margin-bottom: 25px; }

        .home-header h1 {
            color: #0d47a1;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 40px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 100%;
            margin: 20px auto;
            background: linear-gradient(to bottom, #1565c0, #0a2f7a);
            color: #fff;
            padding: 20px;
            border-radius: 100px;
            border: 3px solid #000;
            font-size: 1.4rem;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: 0.3s;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            color: #fff;
        }
        
        .action-btn.outline {
            background: #fff;
            color: #0d47a1;
            border-color: #0d47a1;
        }
        .action-btn.outline:hover {
            background: #f0f4f8;
            color: #0d47a1;
        }

        .home-footer {
            margin-top: 40px;
            color: #5c6bc0;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="home-card">
        <div class="home-header">
            <img src="assets/logo.png" alt="ABSS Logo" class="home-logo">
            <h1>Welcome to ABSS</h1>
            <p style="color: #5c6bc0; font-size: 1.1rem; margin-bottom: 30px; font-weight: 600;">Please select an option to continue</p>
        </div>

        <a href="index.php" class="action-btn outline">
            <i class="fas fa-globe"></i> Explore Website
        </a>

        <a href="parent/login.php" class="action-btn" style="background: linear-gradient(135deg, #059669, #047857);">
            <i class="fas fa-user-friends"></i> Parent Login
        </a>

        <a href="teacher/login.php" class="action-btn" style="background: linear-gradient(135deg, #7c3aed, #6d28d9);">
            <i class="fas fa-chalkboard-teacher"></i> Teacher Login
        </a>

        <a href="admin/login.php" class="action-btn" style="background: linear-gradient(135deg, #0d47a1, #002171);">
            <i class="fas fa-shield-alt"></i> Admin Login
        </a>

        <div class="home-footer">
            Protected by ABSS Secure System &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>
