<?php
/**
 * config/env.php - Environment Loader
 * This file parses the .env file and defines constants for the application.
 */

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    die("Error: .env file not found. Please create one based on .env.example.");
}

// Simple absolute path for parsing or custom logic
$env = parse_ini_file($envPath);

// Define Constants from .env
foreach ($env as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

// Error Reporting based on ENVIRONMENT
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>