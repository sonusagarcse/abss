<?php
// config/env.php - Centralized Environment Configuration

// Define Environment
define('ENVIRONMENT', 'development'); // Change to 'production' on live server

// Database Configuration
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'abss');

define('DB_HOST', 'localhost');
define('DB_USER', 'ouvcxwtd_abss');
define('DB_PASS', 'ouvcxwtd_abss');
define('DB_NAME', 'ouvcxwtd_abss');

// Application Settings
define('APP_URL', 'http://localhost/abss');
define('APP_NAME', 'ABSS Portal');

// Security Configurations
define('SESSION_LIFETIME', 86400); // 1 day

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>