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
