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

        // 0. Ensure FCM Notification tables exist unconditionally
        $conn->query("CREATE TABLE IF NOT EXISTS fcm_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(255) NOT NULL UNIQUE,
            device_type VARCHAR(50) DEFAULT 'android',
            app_version VARCHAR(20) DEFAULT '1.0.0',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $conn->query("CREATE TABLE IF NOT EXISTS notification_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            image VARCHAR(500) DEFAULT NULL,
            url VARCHAR(500) DEFAULT NULL,
            category VARCHAR(50) DEFAULT 'General',
            target_audience VARCHAR(50) DEFAULT 'All Users',
            sent_count INT DEFAULT 0,
            failed_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
            
            // Create notifications table if not exists
            $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                url VARCHAR(500) DEFAULT NULL,
                status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status_id (status, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Create fcm_tokens table
            $conn->query("CREATE TABLE IF NOT EXISTS fcm_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(255) NOT NULL UNIQUE,
                device_type VARCHAR(50) DEFAULT 'android',
                app_version VARCHAR(20) DEFAULT '1.0.0',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Create notification_history table
            $conn->query("CREATE TABLE IF NOT EXISTS notification_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                image VARCHAR(500) DEFAULT NULL,
                url VARCHAR(500) DEFAULT NULL,
                category VARCHAR(50) DEFAULT 'General',
                target_audience VARCHAR(50) DEFAULT 'All Users',
                sent_count INT DEFAULT 0,
                failed_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
        
        $checkAcademicGroup = $conn->query("SHOW COLUMNS FROM students LIKE 'academic_group'");
        if ($checkAcademicGroup && $checkAcademicGroup->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN academic_group VARCHAR(50) DEFAULT 'Group A' AFTER scholar_mode");
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
        $checkTestPaper = $conn->query("SHOW COLUMNS FROM students LIKE 'admission_test_paper'");
        if ($checkTestPaper && $checkTestPaper->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN admission_test_paper VARCHAR(255) NULL AFTER photo");
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
        }
            
        $checkTeacherExpenses = $conn->query("SHOW TABLES LIKE 'teacher_expenses'");
        if ($checkTeacherExpenses && $checkTeacherExpenses->num_rows == 0) {
            $conn->query("
                CREATE TABLE teacher_expenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    teacher_id INT NOT NULL,
                    invoice_id INT NULL,
                    expense_type VARCHAR(150) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    expense_date DATE NOT NULL,
                    description TEXT,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // Check if invoice_id column exists
            $checkCol = $conn->query("SHOW COLUMNS FROM teacher_expenses LIKE 'invoice_id'");
            if ($checkCol && $checkCol->num_rows == 0) {
                $conn->query("ALTER TABLE teacher_expenses ADD COLUMN invoice_id INT NULL AFTER teacher_id");
            }
        }
        
        $checkTeacherInvoices = $conn->query("SHOW TABLES LIKE 'teacher_invoices'");
        if ($checkTeacherInvoices && $checkTeacherInvoices->num_rows == 0) {
            $conn->query("CREATE TABLE teacher_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                invoice_number VARCHAR(50) UNIQUE NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                month_for VARCHAR(20) NULL,
                issue_date DATE NOT NULL,
                due_date DATE NULL,
                status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
        
        // Check and add paid_amount column to teacher_invoices
        $checkPaidAmt = $conn->query("SHOW COLUMNS FROM teacher_invoices LIKE 'paid_amount'");
        if ($checkPaidAmt && $checkPaidAmt->num_rows == 0) {
            $conn->query("ALTER TABLE teacher_invoices ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0.00 AFTER amount");
        }

        // 10. YouTube Videos & Gallery Tables
        $conn->query("CREATE TABLE IF NOT EXISTS youtube_videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            video_url VARCHAR(500) NOT NULL,
            youtube_id VARCHAR(100) NOT NULL,
            thumbnail_url VARCHAR(500) DEFAULT NULL,
            category VARCHAR(100) DEFAULT 'Campus Life',
            description TEXT DEFAULT NULL,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $conn->query("CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_path VARCHAR(255) NOT NULL,
            caption VARCHAR(255) NOT NULL,
            category VARCHAR(100) DEFAULT 'General',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $checkGalCat = $conn->query("SHOW COLUMNS FROM gallery LIKE 'category'");
        if ($checkGalCat && $checkGalCat->num_rows == 0) {
            $conn->query("ALTER TABLE gallery ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER caption");
        }

        // 11. Security Amount, Registration Fee, Admission Fee columns for students
        $checkSecurity = $conn->query("SHOW COLUMNS FROM students LIKE 'security_amount'");
        if ($checkSecurity && $checkSecurity->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN security_amount DECIMAL(10,2) DEFAULT 0.00 AFTER base_fee");
        }
        $checkRegFee = $conn->query("SHOW COLUMNS FROM students LIKE 'registration_fee'");
        if ($checkRegFee && $checkRegFee->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN registration_fee DECIMAL(10,2) DEFAULT 0.00 AFTER security_amount");
        }
        $checkAdmFee = $conn->query("SHOW COLUMNS FROM students LIKE 'admission_fee'");
        if ($checkAdmFee && $checkAdmFee->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN admission_fee DECIMAL(10,2) DEFAULT 0.00 AFTER registration_fee");
        }
        $checkAdvFee = $conn->query("SHOW COLUMNS FROM students LIKE 'advance_amount'");
        if ($checkAdvFee && $checkAdvFee->num_rows == 0) {
            $conn->query("ALTER TABLE students ADD COLUMN advance_amount DECIMAL(10,2) DEFAULT 0.00 AFTER admission_fee");
        }

        // Create in-built portal notifications table
        $conn->query("
            CREATE TABLE IF NOT EXISTS portal_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT NULL,
                student_id INT NULL,
                type VARCHAR(50) NOT NULL DEFAULT 'notice',
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                icon VARCHAR(50) DEFAULT 'fa-bell',
                target_url VARCHAR(255) NOT NULL,
                badge_color VARCHAR(30) DEFAULT '#4f46e5',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parent (parent_id),
                INDEX idx_type (type),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // Create notification read tracking table
        $conn->query("
            CREATE TABLE IF NOT EXISTS notification_reads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_id INT NOT NULL,
                parent_id INT NOT NULL,
                read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_read (notification_id, parent_id),
                INDEX idx_parent_read (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // Auto-seed Firebase Service Account into settings table if file exists
        $saFile = __DIR__ . '/service-account.json';
        if (file_exists($saFile)) {
            $checkSaSetting = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'firebase_service_account_json'");
            if (!$checkSaSetting || $checkSaSetting->num_rows == 0 || empty($checkSaSetting->fetch_assoc()['setting_value'])) {
                $rawSa = file_get_contents($saFile);
                if (!empty($rawSa)) {
                    $escSa = $conn->real_escape_string($rawSa);
                    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('firebase_service_account_json', '$escSa') ON DUPLICATE KEY UPDATE setting_value = '$escSa'");
                }
            }
        }

        // 13. Ensure schools (Entrance Coaching Programs) table exists with full management fields
        $conn->query("CREATE TABLE IF NOT EXISTS schools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_name VARCHAR(150) NOT NULL,
            description VARCHAR(255) NULL,
            icon VARCHAR(50) DEFAULT 'fas fa-graduation-cap',
            badge_text VARCHAR(50) NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $checkSchoolDesc = $conn->query("SHOW COLUMNS FROM schools LIKE 'description'");
        if ($checkSchoolDesc && $checkSchoolDesc->num_rows == 0) {
            $conn->query("ALTER TABLE schools ADD COLUMN description VARCHAR(255) NULL AFTER school_name");
        }
        $checkSchoolIcon = $conn->query("SHOW COLUMNS FROM schools LIKE 'icon'");
        if ($checkSchoolIcon && $checkSchoolIcon->num_rows == 0) {
            $conn->query("ALTER TABLE schools ADD COLUMN icon VARCHAR(50) DEFAULT 'fas fa-graduation-cap' AFTER description");
        }
        $checkSchoolBadge = $conn->query("SHOW COLUMNS FROM schools LIKE 'badge_text'");
        if ($checkSchoolBadge && $checkSchoolBadge->num_rows == 0) {
            $conn->query("ALTER TABLE schools ADD COLUMN badge_text VARCHAR(50) NULL AFTER icon");
        }
        $checkSchoolSort = $conn->query("SHOW COLUMNS FROM schools LIKE 'sort_order'");
        $checkSchoolActive = $conn->query("SHOW COLUMNS FROM schools LIKE 'is_active'");
        if ($checkSchoolActive && $checkSchoolActive->num_rows == 0) {
            $conn->query("ALTER TABLE schools ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER sort_order");
        }
        // Update any empty or NULL descriptions to default batch tag
        $conn->query("UPDATE schools SET description = 'Class 6 / Entrance Batch' WHERE description IS NULL OR TRIM(description) = ''");

        // Seed default coaching programs if table is empty
        $countSchools = $conn->query("SELECT COUNT(*) as c FROM schools");
        if ($countSchools && (int)$countSchools->fetch_assoc()['c'] == 0) {
            $default_programs = [
                ["Netarhat Residential", "Class 6 Entrance Batch", "fas fa-graduation-cap", "State Premier", 1],
                ["Sainik School (AISSEE)", "All India Sainik School", "fas fa-shield-alt", "National Merit", 2],
                ["Navodaya Vidyalaya", "JNVST Entrance Batch", "fas fa-award", "Top Selection", 3],
                ["Simultala Residential", "State Merit Batch", "fas fa-book-reader", "Merit Program", 4],
                ["BHU CHS Entrance", "Banaras Hindu University", "fas fa-university", "Central School", 5],
                ["Rashtriya Military School", "RMS Entrance Batch", "fas fa-medal", "Defense Wings", 6]
            ];
            $p_stmt = $conn->prepare("INSERT INTO schools (school_name, description, icon, badge_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            foreach ($default_programs as $dp) {
                $p_stmt->bind_param("ssssi", $dp[0], $dp[1], $dp[2], $dp[3], $dp[4]);
                $p_stmt->execute();
            }
        }

        // Keep settings and site_settings synchronized
        $syncRes = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($syncRes) {
            while ($sRow = $syncRes->fetch_assoc()) {
                $sKey = $conn->real_escape_string($sRow['setting_key']);
                $sVal = $conn->real_escape_string($sRow['setting_value'] ?? '');
                $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('$sKey', '$sVal') ON DUPLICATE KEY UPDATE setting_value = '$sVal'");
            }
        }
        
        // Ensure inquiries table has full contact query and attachment fields
        $checkInqEmail = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'email'");
        if ($checkInqEmail && $checkInqEmail->num_rows == 0) {
            $conn->query("ALTER TABLE inquiries ADD COLUMN email VARCHAR(150) NULL AFTER parent_phone");
        }
        $checkInqSub = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'subject'");
        if ($checkInqSub && $checkInqSub->num_rows == 0) {
            $conn->query("ALTER TABLE inquiries ADD COLUMN subject VARCHAR(255) NULL AFTER target_exam");
        }
        $checkInqMsg = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'message'");
        if ($checkInqMsg && $checkInqMsg->num_rows == 0) {
            $conn->query("ALTER TABLE inquiries ADD COLUMN message TEXT NULL AFTER subject");
        }
        $checkInqAttach = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'attachment'");
        if ($checkInqAttach && $checkInqAttach->num_rows == 0) {
            $conn->query("ALTER TABLE inquiries ADD COLUMN attachment VARCHAR(255) NULL AFTER message");
        }
        $checkInqType = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'inquiry_type'");
        if ($checkInqType && $checkInqType->num_rows == 0) {
            $conn->query("ALTER TABLE inquiries ADD COLUMN inquiry_type VARCHAR(100) DEFAULT 'General' AFTER attachment");
        }

        // 14. Academic Syllabus Cards Table & Auto-Seeding
        $conn->query("CREATE TABLE IF NOT EXISTS syllabus_cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_key VARCHAR(20) NOT NULL UNIQUE,
            title VARCHAR(150) NOT NULL,
            subtitle VARCHAR(255) NULL,
            badge_text VARCHAR(50) DEFAULT 'Syllabus',
            icon VARCHAR(50) DEFAULT 'fas fa-book',
            accent_color VARCHAR(30) DEFAULT '#2563eb',
            overview TEXT NULL,
            subjects_json LONGTEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $countSyllabus = $conn->query("SELECT COUNT(*) as c FROM syllabus_cards");
        if ($countSyllabus && (int)$countSyllabus->fetch_assoc()['c'] == 0) {
            $grpA_subjects = [
                ["subject_name" => "Primary Arithmetic & Mathematics", "icon" => "fas fa-calculator", "topics" => ["Number System, Place Value & Expansion", "Basic Operations: Addition, Subtraction, Multiplication & Division", "Fractional Numbers & Decimal Notation", "LCM & HCF Essentials", "Basic Geometry: Lines, Angles, Shapes & Perimeter"]],
                ["subject_name" => "Mental Ability & Visual Reasoning", "icon" => "fas fa-brain", "topics" => ["Figure Matching & Odd-One-Out", "Pattern Completion & Series Continuation", "Analogy & Spatial Visualization", "Embedded Figures & Mirror Images"]],
                ["subject_name" => "Hindi & English Language Skills", "icon" => "fas fa-font", "topics" => ["Unseen Passages & Comprehension (अपठित गद्यांश)", "Vyakaran: Sangya, Sarvanam, Kriya, Visheshan", "Synonyms & Antonyms (पर्यायवाची व विलोम)", "Basic English Vocabulary & Spellings"]],
                ["subject_name" => "General Awareness & EVS", "icon" => "fas fa-globe-asia", "topics" => ["Our Environment, Plants & Animals", "Human Body, Health & Hygiene", "Basic Geography, Seasons & Directions", "Notable Personalities & National Symbols"]]
            ];

            $grpB_subjects = [
                ["subject_name" => "Advanced Arithmetic & Quantitative Aptitude", "icon" => "fas fa-square-root-variable", "topics" => ["Factors, Multiples & Prime Factorization", "Fractions, Decimals & Simplification (BODMAS)", "Unitary Method, Ratio & Proportion", "Percentage, Profit & Loss", "Simple Interest & Average Calculation", "Area, Perimeter & Volume of Basic Solids"]],
                ["subject_name" => "Logical & Analytical Reasoning", "icon" => "fas fa-puzzle-piece", "topics" => ["Coding-Decoding & Alphabet Series", "Direction Sense & Blood Relations", "Ranking & Seating Arrangement", "Figure Matrix, Folding & Paper Cutting"]],
                ["subject_name" => "Comprehensive Hindi & English Grammar", "icon" => "fas fa-book-open-reader", "topics" => ["Complex Passage Comprehension & Inference", "Hindi Vyakaran: Sandhi, Samas, Muhavare, Lokoktiyan", "English Grammar: Tenses, Prepositions, Articles, Parts of Speech", "Idioms, One-word Substitutions & Correct Usage"]],
                ["subject_name" => "General Science & Social Studies", "icon" => "fas fa-atom", "topics" => ["Matter, Force, Energy & Work", "Solar System, Earth & Natural Phenomena", "Indian History Essentials & Freedom Movement", "Indian Constitution & Basic Civics"]]
            ];

            $grpC_subjects = [
                ["subject_name" => "AISSEE & RMS Standard Mathematics", "icon" => "fas fa-calculator", "topics" => ["Square Roots & Cube Roots", "Exponents, Powers & Algebraic Expressions", "Ratio, Proportion, Speed, Time & Distance", "Work & Time Problems", "Compound Interest & Profit-Loss Applications", "Coordinate Geometry & Mensuration (2D/3D Surface Area & Volume)"]],
                ["subject_name" => "Intelligence & Logical Aptitude", "icon" => "fas fa-lightbulb", "topics" => ["Mathematical Reasoning & Syllogism", "Classification, Venn Diagrams & Logical Sequences", "Data Sufficiency & Analytical Puzzles", "Non-Verbal Cube & Dice Reasoning"]],
                ["subject_name" => "English Language & Grammar Mastery", "icon" => "fas fa-language", "topics" => ["Active/Passive Voice & Direct/Indirect Speech", "Subject-Verb Agreement & Error Spotting", "Reading Comprehension & Critical Passages", "Vocabulary: Synonyms, Antonyms, Analogies & Phrasal Verbs"]],
                ["subject_name" => "General Knowledge, Science & Defense Studies", "icon" => "fas fa-shield-halved", "topics" => ["Indian Armed Forces, Ranks & Defense History", "Physics & Chemistry Fundamentals", "Biological Sciences & Ecosystems", "Static GK, International Organizations & Current Affairs"]]
            ];

            $grpD_subjects = [
                ["subject_name" => "Netarhat Dual-Pattern Mathematics (Obj + Subj)", "icon" => "fas fa-pen-ruler", "topics" => ["Subjective Step-by-Step Problem Solving", "Number Theory, Divisibility Rules & Remainder Theorem", "Commercial Mathematics & Financial Arithmetic", "Advanced Mensuration & Geometric Proofs", "Algebraic Equations & Problem Modeling"]],
                ["subject_name" => "Advanced Mental Ability & Mental Math", "icon" => "fas fa-head-side-brain", "topics" => ["Speed Math & Mental Calculation Drills", "Complex Pattern Recognition & Number Puzzles", "Logical Deduction & Assertion-Reasoning", "Spatial Reasoning & Diagrammatic Logic"]],
                ["subject_name" => "Hindi Sahitya & Vyakaran (नेतरहाट विशेष)", "icon" => "fas fa-feather-pointed", "topics" => ["Hindi Vyakaran: Chhand, Rasa, Alankar Basics", "Subjective Nibandh Lekhan (निबंध लेखन)", "Patra Lekhan (पत्र लेखन) & Precise Writing", "Hindi Literature, Authors & Classic Works"]],
                ["subject_name" => "General Science & Bihar Special Studies", "icon" => "fas fa-flask-vial", "topics" => ["Physics, Chemistry & Biology Concept Deep Dive", "Bihar History, Geography, Culture & Heritage", "Environmental Science & Sustainability", "Current Scientific Innovations & National Affairs"]]
            ];

            $default_syllabus = [
                [
                    "group_key" => "Group A",
                    "title" => "Group A - Primary Foundation",
                    "subtitle" => "Class 3rd & 4th Entrance Prep",
                    "badge_text" => "Foundation Batch",
                    "icon" => "fas fa-cubes",
                    "accent_color" => "#2563eb",
                    "overview" => "Comprehensive foundational coaching designed for young scholars preparing for Netarhat & Sainik School junior entrance exams.",
                    "subjects_json" => json_encode($grpA_subjects, JSON_UNESCAPED_UNICODE)
                ],
                [
                    "group_key" => "Group B",
                    "title" => "Group B - Middle Competitive",
                    "subtitle" => "Class 5th & 6th Entrance Special",
                    "badge_text" => "Standard Merit",
                    "icon" => "fas fa-microscope",
                    "accent_color" => "#059669",
                    "overview" => "Core academic syllabus focused on high-scoring entrance tests for JNVST Navodaya Vidyalaya, Sainik School AISSEE, and Simultala.",
                    "subjects_json" => json_encode($grpB_subjects, JSON_UNESCAPED_UNICODE)
                ],
                [
                    "group_key" => "Group C",
                    "title" => "Group C - Sainik & RMS Entrance",
                    "subtitle" => "All India Sainik & Military School",
                    "badge_text" => "Defense Wing",
                    "icon" => "fas fa-shield-alt",
                    "accent_color" => "#7c3aed",
                    "overview" => "Specialized test syllabus for All India Sainik School Entrance Examination (AISSEE) and Rashtriya Military School (RMS).",
                    "subjects_json" => json_encode($grpC_subjects, JSON_UNESCAPED_UNICODE)
                ],
                [
                    "group_key" => "Group D",
                    "title" => "Group D - Netarhat & Simultala Special",
                    "subtitle" => "State Residential Premier Merit",
                    "badge_text" => "Super Merit Batch",
                    "icon" => "fas fa-crown",
                    "accent_color" => "#d97706",
                    "overview" => "Advanced subjective and objective dual-pattern syllabus tailored for Netarhat Residential School & Simultala Awasiya Vidyalaya entrance.",
                    "subjects_json" => json_encode($grpD_subjects, JSON_UNESCAPED_UNICODE)
                ]
            ];

            $s_stmt = $conn->prepare("INSERT INTO syllabus_cards (group_key, title, subtitle, badge_text, icon, accent_color, overview, subjects_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($default_syllabus as $ds) {
                $s_stmt->bind_param("ssssssss", $ds['group_key'], $ds['title'], $ds['subtitle'], $ds['badge_text'], $ds['icon'], $ds['accent_color'], $ds['overview'], $ds['subjects_json']);
                $s_stmt->execute();
            }
        }

        // Restore MySQLi reporting mode
        $driver->report_mode = $prev_report;
        
    } catch (Exception $e) {
        error_log("AutoMigrator Error: " . $e->getMessage());
    }
}

/**
 * Fetch all settings into an associative array (settings table takes precedence)
 */
function getAllSettings() {
    $conn = getDB();
    $settings = [];
    
    // 1. Fetch from site_settings table (legacy fallback)
    $result2 = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    // 2. Fetch from settings table (primary authority)
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

/**
 * Save or update a setting across both settings and site_settings tables
 */
function saveSetting($key, $val) {
    $conn = getDB();
    $esc_key = $conn->real_escape_string($key);
    $esc_val = $conn->real_escape_string(is_string($val) ? trim($val) : (is_array($val) ? json_encode($val) : (string)$val));
    
    // Update/Insert in settings table
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$esc_key', '$esc_val') ON DUPLICATE KEY UPDATE setting_value = '$esc_val'");
    
    // Also sync in site_settings table
    $conn->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('$esc_key', '$esc_val') ON DUPLICATE KEY UPDATE setting_value = '$esc_val'");
}

// Auto-load Visitor Tracking and Activity Logging System
require_once __DIR__ . '/../includes/tracker_helper.php';

// Auto-load In-Built Web Notifications System
require_once __DIR__ . '/../includes/notification_helper.php';

// Auto-load Late Fine Management System
require_once __DIR__ . '/../includes/fine_helper.php';
?>
