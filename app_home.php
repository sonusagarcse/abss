<?php
require_once 'config/db.php';
$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ABSS Mobile App</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="manifest" href="/abss/app/manifest.json">
    <style>
        :root {
            --portal-blue: #0d47a1;
            --portal-dark: #002171;
            --portal-accent: #ffb300;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--portal-blue) 0%, var(--portal-dark) 100%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            overflow: hidden;
        }

        .app-container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            box-sizing: border-box;
            text-align: center;
        }

        .app-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #fff;
            padding: 10px;
            margin: 0 auto 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }

        .app-logo img {
            width: 80%;
            height: auto;
        }

        .app-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0 0 10px;
            letter-spacing: 1px;
        }

        .app-subtitle {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.8;
            margin-bottom: 50px;
        }

        .app-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 18px 20px;
            margin-bottom: 20px;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .btn-website {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .btn-website:active {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-login {
            background: #fff;
            color: var(--portal-blue);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .btn-login:active {
            background: #f0f4f8;
            transform: scale(0.98);
        }

        .app-btn i {
            margin-right: 12px;
            font-size: 1.4rem;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .app-footer {
            position: absolute;
            bottom: 30px;
            font-size: 0.8rem;
            opacity: 0.6;
        }
    </style>
</head>
<body>

    <div class="app-container">
        <div class="app-logo">
            <img src="assets/logo.png" alt="ABSS Logo">
        </div>
        
        <h1 class="app-title">ABSS Portal</h1>
        <p class="app-subtitle">Select an option to continue</p>

        <a href="index.php" class="app-btn btn-website">
            <i class="fas fa-globe"></i> Main Website
        </a>

        <a href="admin/login.php?role=parent" class="app-btn btn-login">
            <i class="fas fa-user-circle"></i> Parent Login Panel
        </a>
    </div>

    <div class="app-footer">
        ABSS Secure System © 2026
    </div>

    <script>
        // Register SW just in case they launch from somewhere else
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/abss/app/sw.js.php', {scope: '/abss/'});
            });
        }
    </script>
</body>
</html>
