<?php
require_once __DIR__ . '/security.php';
$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
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
    <base href="<?php echo $basePath; ?>">
    <title>Awasiya Bal Shikshan Sansthan | Competitive Education Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="manifest" href="/abss/app/manifest.json">
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/abss/app/sw.js', {scope: '/abss/'});
        });
    }

    // Global PWA Installation Logic removed as we redirect to app/index.php
    document.addEventListener('DOMContentLoaded', () => {
        // Any remaining mobile drawer logic if needed
    });
    </script>
</head>

<body>
    <header class="main-header">
        <nav class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/logo.png" alt="Awasiya Bal Shikshan Sansthan Logo" style="height: 60px;">
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#contact">Contact</a></li>
            <li><a href="inauguration.php" style="color: #d4af37;"><i class="fas fa-star"></i> Inauguration</a></li>
            <li><a href="app/index.php" style="color: #0d47a1;"><i class="fas fa-download"></i> App</a></li>
        </ul>
        <a href="admission.php" class="btn btn-primary">Admissions</a>
        <div class="menu-toggle" id="mobile-menu-open">
            <i class="fas fa-bars"></i>
        </div>
    </nav>
</header>

<!-- Mobile Drawer -->
<div class="mobile-drawer" id="mobile-drawer">
    <div class="drawer-header">
        <img src="assets/logo.png" alt="Logo" style="height: 40px;">
        <div class="close-drawer" id="mobile-menu-close"><i class="fas fa-times"></i></div>
    </div>
    <ul class="drawer-links">
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#classes">Programs</a></li>
        <li><a href="#admission">Admission</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="inauguration.php" style="color: #d4af37;"><i class="fas fa-star"></i> Inauguration</a></li>
        <li><a href="app/index.php" style="color: #0d47a1;"><i class="fas fa-download"></i> Download App</a></li>
    </ul>
    <div class="drawer-footer">
        <a href="admission.php" class="btn btn-primary w-100">Apply Now</a>
    </div>
    </div>

<!-- Floating App Install Button -->
<a href="app/index.php" id="floatingInstallBtn" class="floating-install" style="display:flex; text-decoration: none;">
    <img src="assets/logo.png" alt="App Icon">
    <div class="floating-text">
        <span>Download Our App</span>
        <small>Faster & secure</small>
    </div>
    <i class="fas fa-download download-icon"></i>
</a>

    <style>
        .main-header {
            background: var(--glass-heavy);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 12px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 2000;
            border-bottom: 1px solid var(--glass-border);
            transition: var(--ease-in-out);
        }

        .main-header.scrolled {
            padding: 8px 0;
            box-shadow: var(--shadow-md);
        }

        nav.container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 35px;
        }

        .nav-links a {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 0.95rem;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary-dark);
            transition: var(--ease-in-out);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a:hover {
            color: var(--secondary-dark);
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        @media (max-width: 992px) {

            .nav-links,
            .main-header .btn {
                display: none;
            }

            .menu-toggle {
                display: block;
            }
        }

        /* Floating Install Button Styles */
        .floating-install {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #fff;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,21,113,0.2);
            align-items: center;
            padding: 8px 20px 8px 8px;
            cursor: pointer;
            z-index: 9999;
            transition: all 0.3s ease;
            border: 2px solid var(--portal-blue);
        }
        .floating-install:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,21,113,0.3);
        }
        .floating-install img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 1px solid #eef2ff;
        }
        .floating-text {
            display: flex;
            flex-direction: column;
            margin-right: 15px;
        }
        .floating-text span {
            color: var(--portal-blue);
            font-weight: 800;
            font-size: 0.95rem;
            line-height: 1.2;
        }
        .floating-text small {
            color: #666;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .download-icon {
            color: var(--portal-blue);
            font-size: 1.2rem;
            background: #eef2ff;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        @media (max-width: 768px) {
            .floating-install {
                bottom: 20px;
                right: 20px;
            }
            .floating-text small { display: none; }
        }

        /* Mobile Drawer Styles */
        .mobile-drawer {
            position: fixed;
            top: 0;
            right: -100%;
            width: 300px;
            height: 100vh;
            background: var(--white);
            z-index: 3000;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
            transition: var(--ease-in-out);
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        .mobile-drawer.open {
            right: 0;
        }

        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .close-drawer {
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        .drawer-links {
            list-style: none;
            flex: 1;
        }

        .drawer-links li {
            margin-bottom: 25px;
        }

        .drawer-links a {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .drawer-footer {
            margin-top: auto;
        }
    </style>