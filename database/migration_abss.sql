-- ============================================================
-- ABSS IDEMPOTENT DATABASE MIGRATION SCRIPT
-- Safe to execute against existing databases without data loss.
-- ============================================================

-- 1. Add 'remarks' column to 'results' table
ALTER TABLE `results` ADD COLUMN IF NOT EXISTS `remarks` TEXT DEFAULT NULL;

-- 2. Create 'fee_rebates' table for fee concessions / waivers
CREATE TABLE IF NOT EXISTS `fee_rebates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `rebate_date` DATE NOT NULL,
  `month_for` VARCHAR(50) NOT NULL,
  `remarks` VARCHAR(255) DEFAULT 'Parent concession / fee waiver',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Performance Indexes (Non-destructive B-Tree Indexes)
CREATE INDEX IF NOT EXISTS `idx_pay_date` ON `fee_payments` (`payment_date`);
CREATE INDEX IF NOT EXISTS `idx_pay_created` ON `fee_payments` (`created_at`);
CREATE INDEX IF NOT EXISTS `idx_student_status` ON `students` (`status`);
CREATE INDEX IF NOT EXISTS `idx_results_exam_date` ON `results` (`exam_date`);
CREATE INDEX IF NOT EXISTS `idx_results_created` ON `results` (`created_at`);
CREATE INDEX IF NOT EXISTS `idx_expenses_sid_status` ON `student_expenses` (`student_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_expenses_date` ON `student_expenses` (`expense_date`);
