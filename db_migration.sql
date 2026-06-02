-- ABSS Portal Database Migration Script
-- Contains the recent structural changes for Automated Billing, Addons, Expenses, and Site Settings.

-- 1. Site Settings (Used for Director Image and other global configs)
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default site settings if not present
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES 
('director_image', 'assets/director_default.png'),
('fee_day_scholar', '3000'),
('fee_hostler', '5000'),
('razorpay_key_id', 'admin'),
('razorpay_key_secret', 'admin123'),
('tuition_modes', '{"Day Scholar":3000,"Hostler":5000}');

-- 2. Student Addons (For recurring monthly items like Milk, Medicine, etc.)
CREATE TABLE IF NOT EXISTS `student_addons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `addon_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `student_addons_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Student Expenses (For one-time items like Pen, Book, etc.)
CREATE TABLE IF NOT EXISTS `student_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('unbilled','billed') DEFAULT 'unbilled',
  `billed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `student_expenses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Note: The `fees_generated` and `students` tables were already existing,
-- but ensure that `fees_generated` has the `status` enum('unpaid', 'paid').
-- If it did not have it before, uncomment the line below:
-- ALTER TABLE `fees_generated` MODIFY `status` enum('unpaid','paid') DEFAULT 'unpaid';

-- 4. Update students table to allow custom scholar modes dynamically
ALTER TABLE `students` MODIFY `scholar_mode` varchar(50) DEFAULT 'Day Scholar';


-- 1. Add new fee and billing columns to students
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `scholar_mode` ENUM('Day Scholar', 'Hostler') DEFAULT 'Day Scholar' AFTER `class_admitted`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `reg_no` VARCHAR(20) NULL AFTER `id`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `photo` VARCHAR(255) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `monthly_discount` DECIMAL(10,2) DEFAULT 0.00 AFTER `scholar_mode`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `base_fee` DECIMAL(10,2) DEFAULT 0.00 AFTER `monthly_discount`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `last_billed_date` DATE NULL AFTER `base_fee`;

-- 2. Create student addons and expenses tables
CREATE TABLE IF NOT EXISTS `student_addons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `addon_name` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `status` ENUM('unbilled', 'billed') DEFAULT 'unbilled',
    `billed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Add personal/demographic and medical fields to students table
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `dob` DATE NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `gender` ENUM('Male','Female','Other') NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `home_address` TEXT NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `state` VARCHAR(100) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `zip_code` VARCHAR(10) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `guardian_relationship` VARCHAR(50) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `guardian_email` VARCHAR(150) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `guardian_address` TEXT NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `emergency_contact_name` VARCHAR(150) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `emergency_relationship` VARCHAR(50) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `emergency_phone` VARCHAR(20) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `has_allergies` TINYINT(1) DEFAULT 0;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `allergies_detail` TEXT NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `has_medical_condition` TINYINT(1) DEFAULT 0;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `medical_condition_detail` TEXT NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `physician_name` VARCHAR(150) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `physician_phone` VARCHAR(20) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `insurance_provider` VARCHAR(100) NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `insurance_policy` VARCHAR(100) NULL;

-- 4. Create site_settings table and insert default settings
CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES 
    ('fee_day_scholar', '3000'),
    ('fee_hostler', '5000');

-- 5. Expand admissions table with full offline form fields
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `state` VARCHAR(100) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `zip_code` VARCHAR(10) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `guardian_relationship` VARCHAR(50) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `guardian_address` TEXT NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `emergency_contact_name` VARCHAR(150) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `emergency_relationship` VARCHAR(50) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `emergency_phone` VARCHAR(20) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `has_allergies` TINYINT(1) DEFAULT 0;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `allergies_detail` TEXT NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `has_medical_condition` TINYINT(1) DEFAULT 0;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `medical_condition_detail` TEXT NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `physician_name` VARCHAR(150) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `physician_phone` VARCHAR(20) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `insurance_provider` VARCHAR(150) NULL;
ALTER TABLE `admissions` ADD COLUMN IF NOT EXISTS `insurance_policy` VARCHAR(100) NULL;

-- 6. Create document tracking tables (document_types and student_documents)
CREATE TABLE IF NOT EXISTS `document_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `is_required` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `document_type_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(`student_id`),
    INDEX(`document_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Seed default document types
INSERT INTO `document_types` (`name`, `is_required`) VALUES 
    ('Aadhar Card', 1), 
    ('Transfer Certificate (TC)', 1), 
    ('Birth Certificate', 1), 
    ('Previous Year Marksheet', 0);
