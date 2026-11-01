<?php
// Set 404 response code
http_response_code(404);
define('IS_404_PAGE', true);

require_once 'config/db.php';
$settings = getAllSettings();
$page_title = "404 - Page Not Found | " . ($settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan');
include 'includes/header.php';
?>

<main class="page-404-wrapper">
    <div class="page-404-container">
        <!-- Animated 404 Visual Indicator -->
        <div class="badge-404-container">
            <div class="compass-circle">
                <i class="fas fa-compass compass-icon"></i>
            </div>
            <span class="pill-404">Error 404</span>
        </div>

        <h1 class="page-404-title">Page Not Found</h1>
        
        <p class="page-404-desc">
            The page you are looking for might have been moved, removed, or the link may be mistyped.
        </p>

        <!-- Quick Navigation Cards Grid (Mobile Responsive) -->
        <div class="nav-404-grid">
            <a href="index.php" class="nav-404-card">
                <div class="card-icon-box box-blue">
                    <i class="fas fa-home"></i>
                </div>
                <div class="card-text-box">
                    <strong>Home Page</strong>
                    <small>Main school portal</small>
                </div>
                <i class="fas fa-chevron-right arrow-mobile"></i>
            </a>

            <a href="parent/login.php" class="nav-404-card">
                <div class="card-icon-box box-purple">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="card-text-box">
                    <strong>Parent Login</strong>
                    <small>Fees &amp; student results</small>
                </div>
                <i class="fas fa-chevron-right arrow-mobile"></i>
            </a>

            <a href="contact.php" class="nav-404-card">
                <div class="card-icon-box box-green">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div class="card-text-box">
                    <strong>Contact Us</strong>
                    <small>Helpline &amp; admissions</small>
                </div>
                <i class="fas fa-chevron-right arrow-mobile"></i>
            </a>
        </div>

        <!-- Back Button -->
        <div class="back-btn-wrapper">
            <a href="javascript:history.back()" class="btn-go-back">
                <i class="fas fa-arrow-left"></i> Go Back to Previous Page
            </a>
        </div>
    </div>
</main>

<style>
/* 404 Full-bleed Responsive Styling */
.page-404-wrapper {
    background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(40px, 8vw, 100px) clamp(16px, 4vw, 24px);
    width: 100%;
    box-sizing: border-box;
}

.page-404-container {
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
    text-align: center;
    box-sizing: border-box;
}

.badge-404-container {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.compass-circle {
    background: rgba(37, 99, 235, 0.08);
    width: clamp(96px, 20vw, 130px);
    height: clamp(96px, 20vw, 130px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1);
}

.compass-icon {
    font-size: clamp(3rem, 7vw, 4.2rem);
    color: var(--primary, #1e3a8a);
    animation: spinCompass 7s linear infinite;
}

.pill-404 {
    position: absolute;
    bottom: 2px;
    right: -4px;
    background: #ef4444;
    color: #ffffff;
    font-weight: 800;
    font-size: clamp(0.72rem, 1.8vw, 0.82rem);
    padding: 4px 11px;
    border-radius: 50px;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
    letter-spacing: 0.02em;
}

.page-404-title {
    font-size: clamp(2rem, 6vw, 3.4rem);
    font-weight: 900;
    color: var(--primary, #1e3a8a);
    line-height: 1.15;
    margin: 0 0 12px;
    letter-spacing: -0.02em;
}

.page-404-desc {
    font-size: clamp(0.92rem, 2.5vw, 1.08rem);
    color: #475569;
    max-width: 520px;
    margin: 0 auto 30px;
    line-height: 1.6;
    font-weight: 500;
    padding: 0 8px;
}

/* Responsive Navigation Grid */
.nav-404-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    max-width: 620px;
    margin: 0 auto 30px;
    text-align: left;
    box-sizing: border-box;
}

@media (min-width: 600px) {
    .nav-404-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }
}

.nav-404-card {
    background: #ffffff;
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    min-height: 48px;
}

.nav-404-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}

.card-icon-box {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}
.box-blue { background: #eff6ff; color: #2563eb; }
.box-purple { background: #fdf4ff; color: #c026d3; }
.box-green { background: #f0fdf4; color: #16a34a; }

.card-text-box {
    flex: 1;
    overflow: hidden;
}
.card-text-box strong {
    display: block;
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 800;
}
.card-text-box small {
    display: block;
    color: #64748b;
    font-size: 0.74rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.arrow-mobile {
    color: #94a3b8;
    font-size: 0.8rem;
    display: block;
}

@media (min-width: 600px) {
    .arrow-mobile {
        display: none;
    }
    .nav-404-card {
        flex-direction: column;
        align-items: flex-start;
        padding: 18px;
        gap: 12px;
    }
    .card-icon-box {
        width: 44px;
        height: 44px;
    }
}

.back-btn-wrapper {
    margin-top: 10px;
}
.btn-go-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 50px;
    transition: all 0.2s;
    min-height: 44px;
}
.btn-go-back:hover {
    color: #0f172a;
    background: rgba(0,0,0,0.04);
}

@keyframes spinCompass {
    0% { transform: rotate(0deg); }
    25% { transform: rotate(35deg); }
    50% { transform: rotate(-25deg); }
    75% { transform: rotate(15deg); }
    100% { transform: rotate(0deg); }
}
</style>

<?php include 'includes/footer.php'; ?>
