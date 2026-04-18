<?php
require_once __DIR__ . '/security.php';
$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awasiya Bal Shikshan Sansthan | Competitive Education Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
            </ul>
            <a href="#admission" class="btn btn-primary">Admissions</a>
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
        </ul>
        <div class="drawer-footer">
            <a href="#admission" class="btn btn-primary w-100">Apply Now</a>
        </div>
    </div>

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