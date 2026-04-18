<?php
// Central Database Configuration
require_once __DIR__ . '/env.php';

/**
 * Get Database Connection
 */
function getDB() {
    static $conn;
    if ($conn === NULL) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable exceptions
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            // Log error instead of displaying to user
            error_log($e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    return $conn;
}

/**
 * Fetch all settings into an associative array
 */
function getAllSettings() {
    $conn = getDB();
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}
?>
