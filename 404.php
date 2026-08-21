<?php
// Set 404 response code
http_response_code(404);

require_once 'config/db.php';
$settings = getAllSettings();
$page_title = "404 - Page Not Found | " . ($settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan');
include 'includes/header.php';
?>

<main style="background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 120px 20px 80px;">
    <div class="container text-center" style="max-width: 780px;">
        <!-- Animated 404 Badge -->
        <div style="position: relative; display: inline-block; margin-bottom: 20px;">
            <div style="background: rgba(37, 99, 235, 0.08); width: 140px; height: 140px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1);">
                <i class="fas fa-compass" style="font-size: 4.5rem; color: var(--primary, #1e3a8a); animation: spinCompass 6s linear infinite;"></i>
            </div>
            <span style="position: absolute; bottom: 5px; right: 5px; background: #ef4444; color: #fff; font-weight: 800; font-size: 0.85rem; padding: 4px 10px; border-radius: 50px; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);">404</span>
        </div>

        <h1 style="font-size: clamp(3rem, 8vw, 5.5rem); font-weight: 900; color: var(--primary, #1e3a8a); line-height: 1; margin: 0 0 14px; letter-spacing: -0.03em;">
            Page Not Found
        </h1>
        
        <p style="font-size: clamp(1rem, 2.5vw, 1.2rem); color: #475569; max-width: 580px; margin: 0 auto 35px; line-height: 1.6; font-weight: 500;">
            The page or resource you are looking for may have been moved, deleted, or the web address might have been typed incorrectly.
        </p>

        <!-- Quick Navigation Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; max-width: 650px; margin: 0 auto 35px; text-align: left;">
            <a href="index.php" style="background: #ffffff; padding: 18px 20px; border-radius: 16px; border: 1px solid #e2e8f0; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-home"></i>
                </div>
                <div>
                    <strong style="display: block; color: #0f172a; font-size: 0.95rem;">Home Page</strong>
                    <small style="color: #64748b; font-size: 0.78rem;">Main school portal</small>
                </div>
            </a>

            <a href="parent/login.php" style="background: #ffffff; padding: 18px 20px; border-radius: 16px; border: 1px solid #e2e8f0; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fdf4ff; color: #c026d3; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <strong style="display: block; color: #0f172a; font-size: 0.95rem;">Parent Login</strong>
                    <small style="color: #64748b; font-size: 0.78rem;">Fees, results & records</small>
                </div>
            </a>

            <a href="contact.php" style="background: #ffffff; padding: 18px 20px; border-radius: 16px; border: 1px solid #e2e8f0; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div>
                    <strong style="display: block; color: #0f172a; font-size: 0.95rem;">Contact Us</strong>
                    <small style="color: #64748b; font-size: 0.78rem;">Helpline & directions</small>
                </div>
            </a>
        </div>

        <a href="javascript:history.back()" style="display: inline-flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.9rem; font-weight: 700; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
            <i class="fas fa-arrow-left"></i> Go Back to Previous Page
        </a>
    </div>
</main>

<style>
@keyframes spinCompass {
    0% { transform: rotate(0deg); }
    25% { transform: rotate(45deg); }
    50% { transform: rotate(-30deg); }
    75% { transform: rotate(15deg); }
    100% { transform: rotate(0deg); }
}
</style>

<?php include 'includes/footer.php'; ?>
