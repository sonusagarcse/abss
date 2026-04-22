<?php
// admin/setup.php

require_once __DIR__ . '/../config/env.php';

function installDB() {
    echo "<h1>ABSS System Setup</h1>";
    
    // Connect without database first
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) {
        die("Connection failed: " . htmlspecialchars($conn->connect_error));
    }

    echo "<p>Connected to MySQL...</p>";

    // Create Database
    if ($conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME) === TRUE) {
        echo "<p>Database checked/created successfully.</p>";
    } else {
        die("Error creating database: " . htmlspecialchars($conn->error));
    }

    $conn->select_db(DB_NAME);

    $queries = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'staff') DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "students" => "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            parent_name VARCHAR(100),
            phone VARCHAR(15),
            target_school VARCHAR(100),
            class_admitted VARCHAR(50),
            admission_date DATE,
            status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "settings" => "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            category VARCHAR(20) DEFAULT 'general',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "gallery" => "CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_path VARCHAR(255) NOT NULL,
            caption VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "notices" => "CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            type ENUM('info', 'important', 'event') DEFAULT 'info',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "attendance" => "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            date DATE NOT NULL,
            status ENUM('present', 'absent', 'late') DEFAULT 'present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(student_id),
            INDEX(date)
        )",
        "fee_payments" => "CREATE TABLE IF NOT EXISTS fee_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            month_for VARCHAR(20) NOT NULL,
            payment_method VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(student_id)
        )",
        "results" => "CREATE TABLE IF NOT EXISTS results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            exam_name VARCHAR(100) NOT NULL,
            score DECIMAL(5,2),
            total_marks INT,
            rank INT,
            exam_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(student_id)
        )",
        "inquiries" => "CREATE TABLE IF NOT EXISTS inquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            candidate_name VARCHAR(150),
            parent_phone VARCHAR(20),
            target_exam VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "achievers" => "CREATE TABLE IF NOT EXISTS achievers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            target_school VARCHAR(100) NOT NULL,
            batch_year VARCHAR(50) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "admissions" => "CREATE TABLE IF NOT EXISTS admissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(150) NOT NULL,
            dob DATE,
            gender ENUM('Male', 'Female', 'Other'),
            parent_name VARCHAR(150),
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(150),
            scholar_mode ENUM('Day Scholar', 'Hostler') NOT NULL,
            target_program VARCHAR(100),
            prev_school VARCHAR(255),
            address TEXT,
            status ENUM('Pending', 'Reviewed', 'Admitted', 'Rejected') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "schools" => "CREATE TABLE IF NOT EXISTS schools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_name VARCHAR(150) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $table => $sql) {
        if ($conn->query($sql) === TRUE) {
            echo "<p>Table '$table' validated/created.</p>";
        } else {
            echo "<p style='color:red'>Error with table '$table': " . htmlspecialchars($conn->error) . "</p>";
        }
    }

    // Default Settings
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
        $check = $conn->query("SELECT id FROM settings WHERE setting_key = '" . $conn->real_escape_string($key) . "'");
        if ($check && $check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $key, $val);
                $stmt->execute();
            }
        }
    }
    echo "<p>Settings populated.</p>";

    // Admin array block
    $check = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    if ($check && $check->num_rows == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        if ($stmt) {
            $stmt->bind_param("s", $pass);
            $stmt->execute();
        }
        echo "<p>Default admin user created (admin / admin123).</p>";
    }

    echo "<h3>Setup Complete! <a href='login.php'>Go to Login</a></h3>";
}

installDB();
?>
