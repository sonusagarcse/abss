<?php
require_once '../config/db.php';
$conn = getDB();
$settings = getAllSettings();
include '../includes/header.php';
?>

<style>
    /* Specific Styles for the App Landing Page */
    .app-hero {
        padding: 120px 0 80px;
        background: linear-gradient(135deg, var(--bg-light) 0%, #fff 100%);
        text-align: center;
    }
    
    .app-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    
    .feature-card {
        background: #fff;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        text-align: center;
        border-bottom: 3px solid var(--secondary);
        transition: transform 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
    }
    .feature-icon {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 20px;
    }
    
    .process-section {
        background: var(--primary-dark);
        color: #fff;
        padding: 80px 0;
        margin-top: 50px;
    }
    .step-container {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        background: rgba(255,255,255,0.05);
        padding: 20px;
        border-radius: 10px;
    }
    .step-num {
        background: var(--secondary);
        color: var(--primary-dark);
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.5rem;
        font-weight: bold;
        flex-shrink: 0;
    }
</style>

<!-- App Hero Section -->
<section class="app-hero">
    <div class="container">
        <span class="section-badge">ABSS Parent Portal</span>
        <h1 class="section-title" style="font-size: 3rem; margin-bottom: 20px;">Experience ABSS on the Go</h1>
        <p class="lead-text" style="max-width: 700px; margin: 0 auto 40px;">
            The ultimate companion app for parents. Get instant access to academic progress, attendance records, school notices, and secure portal features all in one place.
        </p>
        
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <button id="install-pwa-btn" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 30px; border-radius: 50px;">
                <i class="fas fa-download"></i> Install Web App
            </button>
            <a href="app/ABSS_v1.0.0.APK" class="btn btn-secondary" style="font-size: 1.2rem; padding: 15px 30px; border-radius: 50px;" download>
                <i class="fab fa-android"></i> Download APK
            </a>
        </div>
        <p id="install-status-text" style="margin-top: 15px; color: #666; font-weight: 600;"></p>
    </div>
</section>

<!-- Features Section -->
<section class="container" style="padding-bottom: 60px;">
    <div class="section-header" style="text-align: center;">
        <h2 class="section-title">App Benefits & Features</h2>
    </div>
    <div class="app-features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bell"></i></div>
            <h3>Instant Alerts</h3>
            <p>Get real-time push notifications for important school announcements, holidays, and urgent notices straight to your phone.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <h3>Track Progress</h3>
            <p>View detailed exam results, performance analytics, and daily attendance records instantly without waiting for physical reports.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Secure & Private</h3>
            <p>Your child's data is fully encrypted. Access information securely with our robust login and authentication system.</p>
        </div>
    </div>
</section>

<!-- Process / How it Works Section -->
<section class="process-section">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <span class="section-badge" style="background: rgba(255,255,255,0.1); color: #fff;">Simple Process</span>
            <h2 class="section-title" style="color: #fff;">How It Works</h2>
        </div>
        
        <div style="max-width: 800px; margin: 0 auto;">
            <div class="step-container">
                <div class="step-num">1</div>
                <div>
                    <h3 style="margin-bottom: 5px;">Install the App</h3>
                    <p style="margin: 0; color: rgba(255,255,255,0.8);">Click the "Install Web App" button above. The app will be added directly to your phone's home screen without using any app store.</p>
                </div>
            </div>
            
            <div class="step-container">
                <div class="step-num">2</div>
                <div>
                    <h3 style="margin-bottom: 5px;">Login Securely</h3>
                    <p style="margin: 0; color: rgba(255,255,255,0.8);">Open the app from your home screen and login using your registered Parent Mobile Number and Password provided by the school.</p>
                </div>
            </div>
            
            <div class="step-container">
                <div class="step-num">3</div>
                <div>
                    <h3 style="margin-bottom: 5px;">Stay Connected</h3>
                    <p style="margin: 0; color: rgba(255,255,255,0.8);">That's it! You can now monitor your child's progress, pay fees, and receive instant alerts directly on your device.</p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 50px;">
            <button onclick="document.getElementById('install-pwa-btn').click()" class="btn btn-secondary" style="font-size: 1.2rem; padding: 15px 40px; border-radius: 50px;">
                <i class="fas fa-rocket"></i> Get Started Now
            </button>
        </div>
    </div>
</section>

<!-- Installation Logic -->
<script>
    let deferredPrompt;
    const installBtn = document.getElementById('install-pwa-btn');
    const statusText = document.getElementById('install-status-text');

    // Check if already installed
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        if(installBtn) installBtn.style.display = 'none';
        if(statusText) statusText.textContent = 'App is already installed and running.';
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if(statusText) statusText.textContent = 'App is ready to install.';
    });

    if(installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installBtn.style.display = 'none';
                    statusText.textContent = 'Installing...';
                } else {
                    statusText.textContent = 'Installation cancelled.';
                }
                deferredPrompt = null;
            } else {
                statusText.textContent = 'App is already installed or browser blocked it.';
                installBtn.style.transform = 'scale(0.95)';
                setTimeout(() => installBtn.style.transform = 'scale(1)', 150);
            }
        });
    }

    window.addEventListener('appinstalled', () => {
        if(installBtn) installBtn.style.display = 'none';
        if(statusText) statusText.textContent = 'App installed successfully!';
    });
    
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/abss/app/sw.js', {scope: '/abss/'});
        });
    }
</script>

<?php include '../includes/footer.php'; ?>
