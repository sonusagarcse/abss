<?php
require_once 'config/db.php';
$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ABSS Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --portal-blue: #0d47a1;
            --portal-dark: #002171;
            --accent-green: #059669;
        }

        * { box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0d47a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            color: #ffffff;
        }

        .home-card {
            width: 100%;
            max-width: 480px;
            background: rgba(255, 255, 255, 0.96); 
            backdrop-filter: blur(20px);
            border-radius: 36px; 
            padding: 45px 35px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
            color: #1e293b;
        }

        .home-logo { height: 95px; margin-bottom: 20px; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.1)); }

        .home-header h1 {
            color: #0f172a;
            font-size: 2.1rem;
            font-weight: 900;
            margin: 0 0 6px 0;
            letter-spacing: -0.02em;
        }
        
        .home-header p {
            color: #64748b; 
            font-size: 0.98rem; 
            margin-bottom: 30px; 
            font-weight: 600;
        }

        /* Main Hero Button (Parent Login) */
        .btn-parent-hero {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            padding: 22px 25px;
            border-radius: 20px;
            font-size: 1.35rem;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(5, 150, 105, 0.35);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
        }

        .btn-parent-hero:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 18px 40px rgba(5, 150, 105, 0.45);
            color: #ffffff;
        }

        .btn-parent-hero i {
            font-size: 1.6rem;
        }

        /* Secondary Row (Teacher & Admin in 1 row) */
        .secondary-btn-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 25px;
        }

        .btn-secondary-portal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 16px;
            border-radius: 14px;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .btn-teacher {
            background: #f3e8ff;
            color: #6b21a8;
            border-color: #e9d5ff;
        }
        .btn-teacher:hover {
            background: #7c3aed;
            color: #ffffff;
            border-color: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(124, 58, 237, 0.25);
        }

        .btn-admin {
            background: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }
        .btn-admin:hover {
            background: #1d4ed8;
            color: #ffffff;
            border-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(29, 78, 216, 0.25);
        }

        /* Explore Website Link */
        .btn-website {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #475569;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 30px;
            background: #f1f5f9;
            transition: all 0.2s ease;
        }
        .btn-website:hover {
            color: #0f172a;
            background: #e2e8f0;
        }

        .home-footer {
            margin-top: 30px;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="home-card">
        <div class="home-header">
            <img src="assets/logo.png" alt="ABSS Logo" class="home-logo">
            <h1>Welcome to ABSS</h1>
            <p>Select portal or login option to continue</p>
        </div>

        <!-- Main Focus: Parent Login Hero Button -->
        <a href="parent/login.php" class="btn-parent-hero">
            <i class="fas fa-user-friends"></i> Parent Login
        </a>

        <!-- Secondary Row: Teacher & Admin Login in 1 Row -->
        <div class="secondary-btn-row">
            <a href="teacher/login.php" class="btn-secondary-portal btn-teacher">
                <i class="fas fa-chalkboard-teacher"></i> Teacher Login
            </a>

            <a href="admin/login.php" class="btn-secondary-portal btn-admin">
                <i class="fas fa-user-shield"></i> Admin Login
            </a>
        </div>

        <!-- Explore Website Link -->
        <div>
            <a href="index.php" class="btn-website">
                <i class="fas fa-globe"></i> Explore Public Website
            </a>
        </div>

        <div class="home-footer">
            Protected by ABSS Secure System &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>
