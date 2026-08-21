<?php
require_once __DIR__ . '/security.php';
$settings = getAllSettings();

// Calculate base path from APP_URL for dynamic server environments
$basePath = '/';
if (defined('APP_URL')) {
    $parsedUrl = parse_url(APP_URL, PHP_URL_PATH);
    $basePath = rtrim((string)$parsedUrl, '/') . '/';
    if ($basePath === '' || $basePath === '/') {
        $basePath = '/';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $basePath; ?>">
    <title>Awasiya Bal Shikshan Sansthan | Competitive Residential Education Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="manifest" href="app/manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ABSS App">
    <link rel="apple-touch-icon" href="assets/logo.png">
    <style>
        .top-utility-bar {
            background: #0f172a;
            color: #94a3b8;
            font-size: 0.82rem;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 600;
        }
        .top-utility-bar a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .top-utility-bar a:hover {
            color: #38bdf8;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 2000;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        nav.container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 28px;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: #0f172a;
            font-weight: 700;
            font-size: 0.92rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .nav-links a:hover {
            color: #2563eb;
        }

        /* Highlighted Glowing Admission Navbar CTA Button */
        .btn-apply-header {
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%) !important;
            color: #ffffff !important;
            border: none;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 900;
            font-size: 0.88rem;
            text-decoration: none;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(234, 88, 12, 0.35);
            animation: pulse-admission 2.2s infinite;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-apply-header:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 25px rgba(234, 88, 12, 0.55);
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
            color: #ffffff !important;
        }

        @keyframes pulse-admission {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }

        /* Mobile Drawer Styles */
        .mobile-drawer {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100vh;
            background: #ffffff;
            z-index: 99999;
            box-shadow: -10px 0 35px rgba(0,0,0,0.2);
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .mobile-drawer.active {
            right: 0 !important;
        }

        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 99998;
            display: none;
        }
        .drawer-overlay.active {
            display: block !important;
        }

        .menu-toggle {
            display: none;
            background: #f1f5f9;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #0f172a;
            font-size: 1.25rem;
            transition: all 0.2s ease;
            border: none;
        }
        .menu-toggle:hover {
            background: #e2e8f0;
        }

        /* Responsive Navbar Rules */
        @media (max-width: 900px) {
            .top-utility-bar { display: none !important; } /* Hidden on mobile/Android */
            .nav-links { display: none; }
            .menu-toggle { display: flex !important; }
            .logo-text { display: none !important; } /* Only Logo Icon visible in mobile view */
        }

        @media (max-width: 480px) {
            .btn-apply-header { 
                padding: 8px 14px !important; 
                font-size: 0.76rem !important; 
            }
            .main-header { 
                padding: 8px 0 !important; 
            }
            .logo img {
                height: 42px !important;
            }
        }
    </style>
</head>

<body>
    <!-- Top Utility Ribbon (Desktop Only) -->
    <div class="top-utility-bar">
        <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div style="display:flex; gap:22px; align-items:center; flex-wrap:wrap;">
                <span><i class="fas fa-phone-alt" style="color:#38bdf8;"></i> +91 9523012888</span>
                <span><i class="fas fa-map-marker-alt" style="color:#38bdf8;"></i> Lok Kala Bhavan, Imamganj, Gaya</span>
                <span><i class="fas fa-envelope" style="color:#38bdf8;"></i> abssimamganj@gmail.com</span>
            </div>

            <!-- Desktop Login Quick Buttons -->
            <div style="display:flex; gap:16px; align-items:center;">
                <a href="parent/login.php" style="color:#38bdf8; font-weight:800; text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:5px;">
                    <i class="fas fa-user-friends"></i> Parent Login
                </a>
                <span style="color:rgba(255,255,255,0.25);">|</span>
                <a href="teacher/login.php" style="color:#c084fc; font-weight:800; text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:5px;">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher Login
                </a>
                <span style="color:rgba(255,255,255,0.25);">|</span>
                <a href="admin/login.php" style="color:#f43f5e; font-weight:800; text-decoration:none; font-size:0.8rem; display:inline-flex; align-items:center; gap:5px;">
                    <i class="fas fa-user-shield"></i> Admin Login
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="main-header">
        <nav class="container">
            <div class="logo">
                <a href="index.php" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
                    <img src="assets/logo.png" alt="ABSS School Logo" style="height: 48px;">
                    <div class="logo-text">
                        <strong style="display:block; color:#0f172a; font-size:1.1rem; font-weight:900; line-height:1.1;">आवासीय बाल शिक्षण संस्थान</strong>
                        <small style="color:#64748b; font-weight:700; font-size:0.72rem; letter-spacing:0.05em; text-transform:uppercase;">ABSS Imamganj • Est. 2011</small>
                    </div>
                </a>
            </div>

            <ul class="nav-links">
                <li><a href="index.php#home">Home</a></li>
                <li><a href="index.php#about">About</a></li>
                <li><a href="index.php#exams">Competitive Exams</a></li>
                <li><a href="index.php#achievers">Achievers</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="index.php#admission">Fees</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div style="display:flex; align-items:center; gap:10px;">
                <a href="admission.php" class="btn btn-primary btn-apply-header">
                    <i class="fas fa-sparkles"></i> Admission 2026-27
                </a>
                <button type="button" class="menu-toggle" id="mobile-menu-open" aria-label="Open Navigation">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>

    <!-- Dimming Drawer Backdrop Overlay -->
    <div class="drawer-overlay" id="drawer-overlay"></div>

    <!-- Mobile Drawer -->
    <div class="mobile-drawer" id="mobile-drawer">
        <div class="drawer-header" style="padding:18px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <img src="assets/logo.png" alt="Logo" style="height: 36px;">
                <strong style="color:#0f172a; font-size:1rem;">ABSS Menu</strong>
            </div>
            <button type="button" class="close-drawer" id="mobile-menu-close" style="background:none; border:none; font-size:1.3rem; cursor:pointer; color:#64748b;" aria-label="Close Menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="drawer-links" style="list-style:none; padding:20px; margin:0;">
            <li style="margin-bottom:14px;"><a href="index.php#home" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">Home</a></li>
            <li style="margin-bottom:14px;"><a href="index.php#about" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">About Us</a></li>
            <li style="margin-bottom:14px;"><a href="index.php#exams" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">Competitive Exams</a></li>
            <!-- <li style="margin-bottom:14px;"><a href="index.php#facilities" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">Campus Facilities</a></li> -->
            <li style="margin-bottom:14px;"><a href="index.php#achievers" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">Hall of Excellence</a></li>
            <li style="margin-bottom:14px;"><a href="gallery.php" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">Gallery</a></li>
            <li style="margin-bottom:14px;"><a href="index.php#admission" style="text-decoration:none; font-weight:700; color:#0f172a; display:block; padding:6px 0;">Fees</a></li>
            <li style="margin-bottom:14px;"><a href="contact.php" style="text-decoration:none; font-weight:800; color:#2563eb; display:block; padding:6px 0;"><i class="fas fa-headset" style="margin-right:4px;"></i> Contact Us</a></li>
            <li style="margin-bottom:14px; border-top:1px solid #e2e8f0; padding-top:14px;"><a href="parent/login.php" style="text-decoration:none; font-weight:800; color:#059669; display:block; padding:6px 0;"><i class="fas fa-user-friends"></i> Parent Portal Login</a></li>
            <li style="margin-bottom:14px;"><a href="teacher/login.php" style="text-decoration:none; font-weight:800; color:#7c3aed; display:block; padding:6px 0;"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</a></li>
            <li style="margin-bottom:14px;"><a href="admin/login.php" style="text-decoration:none; font-weight:800; color:#1d4ed8; display:block; padding:6px 0;"><i class="fas fa-user-shield"></i> Admin Portal</a></li>
        </ul>
        <div class="drawer-footer" style="padding:20px; margin-top:auto;">
            <a href="admission.php" style="text-align:center; display:block; padding:12px; border-radius:50px; background:linear-gradient(135deg, #f59e0b, #ea580c); color:#fff; font-weight:800; text-decoration:none;">Apply Admission Now</a>
        </div>
    </div>

    <!-- Mobile Drawer Event Listeners -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var openBtn = document.getElementById('mobile-menu-open');
            var closeBtn = document.getElementById('mobile-menu-close');
            var drawer = document.getElementById('mobile-drawer');
            var overlay = document.getElementById('drawer-overlay');

            function openNav() {
                if (drawer) drawer.classList.add('active');
                if (overlay) overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeNav() {
                if (drawer) drawer.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (openBtn) openBtn.addEventListener('click', openNav);
            if (closeBtn) closeBtn.addEventListener('click', closeNav);
            if (overlay) overlay.addEventListener('click', closeNav);

            var drawerLinks = document.querySelectorAll('.drawer-links a');
            drawerLinks.forEach(function(link) {
                link.addEventListener('click', closeNav);
            });
        });
    </script>