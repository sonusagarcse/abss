<?php
// Set 404 response code
http_response_code(404);

require_once 'config/db.php';
$settings = getAllSettings();
include 'includes/header.php';
?>

<section class="container text-center" style="padding: 150px 20px 100px; min-height: 70vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <div style="background: rgba(13, 71, 161, 0.05); width: 150px; height: 150px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
        <i class="fas fa-search-minus" style="font-size: 5rem; color: var(--primary);"></i>
    </div>
    <h1 style="font-size: 8rem; font-weight: 900; color: var(--primary); line-height: 1; margin-bottom: 20px; text-shadow: 4px 4px 0px rgba(13, 71, 161, 0.1);">404</h1>
    <h2 style="font-size: 2.5rem; margin-bottom: 20px; font-weight: 700; color: var(--primary-dark);">Oops! Page Not Found</h2>
    <p style="font-size: 1.2rem; color: #666; max-width: 600px; margin: 0 auto 40px;">
        The page you are looking for might have been removed, had its name changed, or is temporarily unavailable. Let's get you back on track.
    </p>
    <a href="index.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 35px; border-radius: 50px; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(13, 71, 161, 0.3);">
        <i class="fas fa-home"></i> Back to Homepage
    </a>
</section>

<?php include 'includes/footer.php'; ?>
