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
        
        // 3. Check and upgrade Scholar Mode column
        $checkScholar = $conn->query("SHOW COLUMNS FROM students LIKE 'scholar_mode'");
        if ($checkScholar && $checkScholar->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN scholar_mode ENUM('Day Scholar', 'Hostler') DEFAULT 'Day Scholar' AFTER class_admitted");
        }
        
        // 4. Add student registration number and photo upload
        $checkRegNo = $conn->query("SHOW COLUMNS FROM students LIKE 'reg_no'");
        if ($checkRegNo && $checkRegNo->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN reg_no VARCHAR(20) NULL AFTER id");
        }
        $checkPhoto = $conn->query("SHOW COLUMNS FROM students LIKE 'photo'");
        if ($checkPhoto && $checkPhoto->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN photo VARCHAR(255) NULL");
        }
        
        $checkDiscount = $conn->query("SHOW COLUMNS FROM students LIKE 'monthly_discount'");
        if ($checkDiscount && $checkDiscount->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN monthly_discount DECIMAL(10,2) DEFAULT 0.00 AFTER scholar_mode");
        }
        
        $checkBaseFee = $conn->query("SHOW COLUMNS FROM students LIKE 'base_fee'");
        if ($checkBaseFee && $checkBaseFee->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN base_fee DECIMAL(10,2) DEFAULT 0.00 AFTER monthly_discount");
            $conn->query("ALTER TABLE students ADD COLUMN last_billed_date DATE NULL AFTER base_fee");
        }

        // 4b. Add recurring addons and daily expenses tables
        $conn->query("CREATE TABLE IF NOT EXISTS student_addons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            addon_name VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $conn->query("CREATE TABLE IF NOT EXISTS student_expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            expense_date DATE NOT NULL,
            status ENUM('unbilled', 'billed') DEFAULT 'unbilled',
            billed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // 5. Add student personal/demographic fields
        $checkDob = $conn->query("SHOW COLUMNS FROM students LIKE 'dob'");
        if ($checkDob && $checkDob->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN dob DATE NULL");
            $conn->query("ALTER TABLE students ADD COLUMN gender ENUM('Male','Female','Other') NULL");
            $conn->query("ALTER TABLE students ADD COLUMN home_address TEXT NULL");
            $conn->query("ALTER TABLE students ADD COLUMN city VARCHAR(100) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN state VARCHAR(100) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN zip_code VARCHAR(10) NULL");
        }
        
        // 6. Add guardian/emergency/medical fields
        $checkGuardianRel = $conn->query("SHOW COLUMNS FROM students LIKE 'guardian_relationship'");
        if ($checkGuardianRel && $checkGuardianRel->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN guardian_relationship VARCHAR(50) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN guardian_email VARCHAR(150) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN guardian_address TEXT NULL");
            
            $conn->query("ALTER TABLE students ADD COLUMN emergency_contact_name VARCHAR(150) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN emergency_relationship VARCHAR(50) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN emergency_phone VARCHAR(20) NULL");
            
            $conn->query("ALTER TABLE students ADD COLUMN has_allergies TINYINT(1) DEFAULT 0");
            $conn->query("ALTER TABLE students ADD COLUMN allergies_detail TEXT NULL");
            $conn->query("ALTER TABLE students ADD COLUMN has_medical_condition TINYINT(1) DEFAULT 0");
            $conn->query("ALTER TABLE students ADD COLUMN medical_condition_detail TEXT NULL");
            $conn->query("ALTER TABLE students ADD COLUMN physician_name VARCHAR(150) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN physician_phone VARCHAR(20) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN insurance_provider VARCHAR(100) NULL");
            $conn->query("ALTER TABLE students ADD COLUMN insurance_policy VARCHAR(100) NULL");
        }

        // 7. Site Settings Table
        $checkSettings = $conn->query("SHOW TABLES LIKE 'site_settings'");
        if ($checkSettings && $checkSettings->num_rows == 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Seed default fee settings
            $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES 
                ('fee_day_scholar', '3000'),
                ('fee_hostler', '5000')
            ");
        }
        
        // 7. Expand admissions table with full offline form fields
        $checkAdmDob = $conn->query("SHOW COLUMNS FROM admissions LIKE 'city'");
        if ($checkAdmDob && $checkAdmDob->num_rows == 0) {
            $conn->query("ALTER TABLE admissions ADD COLUMN city VARCHAR(100) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN state VARCHAR(100) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN zip_code VARCHAR(10) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN guardian_relationship VARCHAR(50) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN guardian_address TEXT NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN emergency_contact_name VARCHAR(150) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN emergency_relationship VARCHAR(50) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN emergency_phone VARCHAR(20) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN has_allergies TINYINT(1) DEFAULT 0");
            $conn->query("ALTER TABLE admissions ADD COLUMN allergies_detail TEXT NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN has_medical_condition TINYINT(1) DEFAULT 0");
            $conn->query("ALTER TABLE admissions ADD COLUMN medical_condition_detail TEXT NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN physician_name VARCHAR(150) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN physician_phone VARCHAR(20) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN insurance_provider VARCHAR(150) NULL");
            $conn->query("ALTER TABLE admissions ADD COLUMN insurance_policy VARCHAR(100) NULL");
        }
        
        // 8. Required Documents tracking schemas
        $checkDocs = $conn->query("SHOW TABLES LIKE 'document_types'");
        if ($checkDocs && $checkDocs->num_rows == 0) {
            // Create document_types table
            $conn->query("CREATE TABLE IF NOT EXISTS document_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                is_required TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Create student_documents table
            $conn->query("CREATE TABLE IF NOT EXISTS student_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                document_type_id INT NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(student_id),
                INDEX(document_type_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Seed some default document types
            $conn->query("INSERT INTO document_types (name, is_required) VALUES ('Aadhar Card', 1), ('Transfer Certificate (TC)', 1), ('Birth Certificate', 1), ('Previous Year Marksheet', 0)");
        }
        
        // 9. Teacher Management System
        $checkTeachers = $conn->query("SHOW TABLES LIKE 'teachers'");
        if ($checkTeachers && $checkTeachers->num_rows == 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS teachers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                department VARCHAR(100) DEFAULT NULL,
                designation VARCHAR(100) DEFAULT NULL,
                join_date DATE NULL,
                salary DECIMAL(10,2) DEFAULT 0.00,
                photo VARCHAR(255) NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            $conn->query("CREATE TABLE IF NOT EXISTS teacher_expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                expense_type VARCHAR(150) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                expense_date DATE NOT NULL,
                description TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            $conn->query("CREATE TABLE IF NOT EXISTS teacher_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                invoice_number VARCHAR(50) UNIQUE NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                issue_date DATE NOT NULL,
                due_date DATE NULL,
                status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
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
    // Fetch from settings table
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Fetch from site_settings table
    $result2 = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

// Auto-load Visitor Tracking and Activity Logging System
require_once __DIR__ . '/../includes/tracker_helper.php';
?>
