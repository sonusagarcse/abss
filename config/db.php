<?php
// Central Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'abss');

/**
 * Get Database Connection
 */
function getDB() {
    static $conn;
    if ($conn === NULL) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    return $conn;
}

// Ensure database and tables exist
function initDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);

    // Users Table (Admin)
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff') DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Students Table
    $conn->query("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        parent_name VARCHAR(100),
        phone VARCHAR(15),
        target_school VARCHAR(100),
        class_admitted VARCHAR(50),
        admission_date DATE,
        status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Settings Table (Dynamic Content)
    $conn->query("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        category VARCHAR(20) DEFAULT 'general',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Gallery Table
    $conn->query("CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Notices Table
    $conn->query("CREATE TABLE IF NOT EXISTS notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        type ENUM('info', 'important', 'event') DEFAULT 'info',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Attendance Table
    $conn->query("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('present', 'absent', 'late') DEFAULT 'present',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(student_id),
        INDEX(date)
    )");

    // Fee Payments Table
    $conn->query("CREATE TABLE IF NOT EXISTS fee_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        month_for VARCHAR(20) NOT NULL,
        payment_method VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(student_id)
    )");

    // Results Table
    $conn->query("CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        exam_name VARCHAR(100) NOT NULL,
        score DECIMAL(5,2),
        total_marks INT,
        rank INT,
        exam_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(student_id)
    )");

    // Pre-populate default settings
    $defaults = [
        'school_name' => 'Awasiya Bal Shikshan Sansthan',
        'phone' => '+91 9523012888',
        'email' => 'abssimamganj@gmail.com',
        'address' => 'Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206',
        'facebook' => '#',
        'twitter' => '#',
        'instagram' => '#',
        'linkedin' => '#',
        'res_fee' => '5000',
        'day_fee' => '3000',
        'admission_fee' => '2000',
        'registration_fee' => '100',
        'development_fee' => '1000'
    ];

    foreach ($defaults as $key => $val) {
        $check = $conn->query("SELECT id FROM settings WHERE setting_key = '$key'");
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $key, $val);
            $stmt->execute();
        }
    }

    // Create default admin if not exists (admin/admin123)
    $check = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    if ($check->num_rows == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, password) VALUES ('admin', '$pass')");
    }
}

// Auto-init on include
initDB();

/**
 * Fetch all settings into an associative array
 */
function getAllSettings() {
    $conn = getDB();
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}
?>
