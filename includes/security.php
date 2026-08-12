<?php
// includes/security.php - Global Security Configuration

// Prevent framing to mitigate Clickjacking
header("X-Frame-Options: SAMEORIGIN");
// Enable Cross-Site Scripting (XSS) filter
header("X-XSS-Protection: 1; mode=block");
// Prevent MIME-sniffing
header("X-Content-Type-Options: nosniff");

// Start secure sessions with 1-year persistence for Web & App
if (session_status() === PHP_SESSION_NONE) {
    // 1 Year Session Lifetime (31536000 seconds)
    $oneYear = 31536000;
    @ini_set('session.gc_maxlifetime', $oneYear);
    @ini_set('session.cookie_lifetime', $oneYear);

    // Determine if HTTPS is used
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_set_cookie_params([
        'lifetime' => $oneYear,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Generate a CSRF token and store it in the session
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the provided CSRF token against the session
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Generate the token immediately so it's ready for forms
generate_csrf_token();
?>
