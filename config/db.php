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
            
            // Execute automatic self-healing DB migrator to sync schemas on live deployment
            runAutoMigrator($conn);
            
        } catch (mysqli_sql_exception $e) {
            // Log error instead of displaying to user
            error_log($e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    return $conn;
}

/**
 * Self-healing automatic database schema migrator
 */
function runAutoMigrator($conn) {
    try {
        // Disable strict reports temporarily to query columns safely
        $driver = new mysqli_driver();
        $prev_report = $driver->report_mode;
        $driver->report_mode = MYSQLI_REPORT_OFF;

        // 1. Check and upgrade Parent and Billing ledgers
        $check = $conn->query("SHOW COLUMNS FROM students LIKE 'parent_id'");
        if ($check && $check->num_rows == 0) {
            // Create parents table
            $conn->query("CREATE TABLE IF NOT EXISTS parents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_name VARCHAR(150) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Add parent_id column
            $conn->query("ALTER TABLE students ADD COLUMN parent_id INT NULL AFTER id");
            
            // Add foreign key constraint
            $conn->query("ALTER TABLE students ADD CONSTRAINT fk_student_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL");
            
            // Create fees_generated table
            $conn->query("CREATE TABLE IF NOT EXISTS fees_generated (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                month_for VARCHAR(20) NOT NULL,
                billing_date DATE NOT NULL,
                remark VARCHAR(255) DEFAULT NULL,
                status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Create support_tickets table
            $conn->query("CREATE TABLE IF NOT EXISTS support_tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT NOT NULL,
                student_id INT DEFAULT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('open', 'resolved', 'closed') DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Seed settings defaults for SMTP
            $smtp_settings = [
                'smtp_host' => '',
                'smtp_port' => '',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls'
            ];
            foreach ($smtp_settings as $k => $v) {
                $checkSetting = $conn->query("SELECT id FROM settings WHERE setting_key = '" . $conn->real_escape_string($k) . "'");
                if ($checkSetting && $checkSetting->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, 'mail')");
                    $stmt->bind_param("ss", $k, $v);
                    $stmt->execute();
                }
            }
        }
        
        // 2. Check and upgrade Visitor tracking schemas
        $checkVisitor = $conn->query("SHOW TABLES LIKE 'site_visitors'");
        if ($checkVisitor && $checkVisitor->num_rows == 0) {
            // Create site_visitors table
            $conn->query("CREATE TABLE IF NOT EXISTS site_visitors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                referrer VARCHAR(512) DEFAULT NULL,
                page_visited VARCHAR(255) NOT NULL,
                user_role VARCHAR(50) DEFAULT 'guest',
                user_id INT DEFAULT NULL,
                parent_id INT DEFAULT NULL,
                visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Create activity_logs table
            $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_role VARCHAR(50) NOT NULL,
                user_id INT DEFAULT NULL,
                username VARCHAR(100) DEFAULT NULL,
                action_type VARCHAR(100) NOT NULL,
                action_details TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
        
        // Restore MySQLi reporting mode
        $driver->report_mode = $prev_report;
        
    } catch (Exception $e) {
        error_log("AutoMigrator Error: " . $e->getMessage());
    }
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

// Auto-load Visitor Tracking and Activity Logging System
require_once __DIR__ . '/../includes/tracker_helper.php';
?>
