<?php
// cron_billing.php - Automated Billing Cron Job Trigger

// Only allow execution from CLI or via a secure token query parameter
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/config/db.php';
    $settings = getAllSettings();
    $cron_token = $settings['cron_security_token'] ?? 'abss_secure_cron_token_2026';
    
    if (!isset($_GET['token']) || $_GET['token'] !== $cron_token) {
        header('HTTP/1.0 403 Forbidden');
        echo "Access Denied: Invalid Security Token.";
        exit();
    }
}

// Set execution limits to prevent script timeout during bulk processing
set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/config/db.php';
$conn = getDB();

echo "ABSS Automated Billing System Cron Job\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "----------------------------------------\n";

// Set variables for billing engine
// Since this runs in the background, we can process all due students at once (e.g. batch_size = 1000)
$billing_batch_size = 1000;
$skip_email = false; // Send emails since it runs in the background!

// Include billing engine
require_once __DIR__ . '/admin/includes/billing_engine.php';

echo "Billing cycle completed successfully.\n";
?>
