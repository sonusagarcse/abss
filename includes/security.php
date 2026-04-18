<?php
// includes/security.php - Global Security Configuration

// Prevent framing to mitigate Clickjacking
header("X-Frame-Options: SAMEORIGIN");
// Enable Cross-Site Scripting (XSS) filter
header("X-XSS-Protection: 1; mode=block");
// Prevent MIME-sniffing
header("X-Content-Type-Options: nosniff");

// Start secure sessions
if (session_status() === PHP_SESSION_NONE) {
    // Determine if HTTPS is used
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict'
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
