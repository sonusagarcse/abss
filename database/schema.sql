-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2026 at 11:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `abss`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievers`
--

CREATE TABLE `achievers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `target_school` varchar(100) NOT NULL,
  `batch_year` varchar(50) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievers`
--

INSERT INTO `achievers` (`id`, `name`, `target_school`, `batch_year`, `image_path`, `created_at`) VALUES
(1, 'Ujjwal Kumar', 'Navodya Vidyalay', '2018', 'assets/achievers/1776881528_9857.jpg', '2026-04-22 18:12:08'),
(2, 'RENU KUMARI', 'Navodya Vidyalay', '2018', 'assets/achievers/1776882403_7564.jpg', '2026-04-22 18:26:43');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_role`, `user_id`, `username`, `action_type`, `action_details`, `ip_address`, `created_at`) VALUES
(1, 'admin', 1, 'admin', 'fee_payment_recorded', 'Recorded payment of ₹1,500.00 for student SONU SAGAR via Quick Collect (month: May)', '::1', '2026-05-28 18:27:24'),
(2, 'parent', 1, 'sonusagarpoly@gmail.com', 'login', 'Parent successfully logged in', '::1', '2026-05-30 03:48:11'),
(3, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-05-30 03:48:21'),
(4, 'admin', 1, 'admin', 'fee_bill_generated', 'Generated invoice of ₹3,000.00 for student SONU SAGAR (month: June)', '::1', '2026-05-30 04:06:25'),
(5, 'admin', 1, 'admin', 'fee_payment_recorded', 'Recorded payment of ₹3,000.00 for student SONU SAGAR via Quick Collect (month: June)', '::1', '2026-05-30 04:09:48'),
(6, 'admin', 1, 'admin', 'fee_bill_generated', 'Generated invoice of ₹122.00 for student SONU SAGAR (month: May)', '::1', '2026-05-30 06:26:43'),
(7, 'admin', 1, 'admin', 'fee_payment_recorded', 'Recorded payment of ₹122.00 for student SONU SAGAR via Quick Collect (month: May)', '::1', '2026-05-30 06:39:46'),
(8, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹37.00 generated for student SONU SAGAR', '::1', '2026-05-30 06:40:01'),
(9, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹245.00 generated for student SONU SAGAR', '::1', '2026-05-30 07:08:03'),
(10, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹45.00 generated for student SONU SAGAR', '::1', '2026-05-30 07:12:05'),
(11, 'guest', NULL, 'Guest', 'login_failed', 'Failed parent login: incorrect password for 8581040110', '::1', '2026-05-30 12:50:22'),
(12, 'parent', 1, 'sonusagarpoly@gmail.com', 'login', 'Parent successfully logged in', '::1', '2026-05-30 12:50:33'),
(13, 'parent', 1, 'sonusagarpoly@gmail.com', 'logout', 'Parent logged out', '::1', '2026-05-30 12:50:40'),
(14, 'parent', 1, 'sonusagarpoly@gmail.com', 'login', 'Parent successfully logged in', '::1', '2026-05-31 07:41:53'),
(15, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-05-31 08:00:48'),
(16, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-02 08:37:55'),
(17, 'admin', 1, 'admin', 'login_failed', 'Failed parent login: incorrect password for 8581040110', '::1', '2026-06-02 08:38:38'),
(18, 'admin', 1, 'admin', 'login_failed', 'Failed admin login: username not found 8581040110', '::1', '2026-06-02 08:38:53'),
(19, 'admin', 1, 'admin', 'login_failed', 'Failed parent login: incorrect password for 8581040110', '::1', '2026-06-02 08:38:57'),
(20, 'admin', 1, 'admin', 'login_failed', 'Failed parent login: incorrect password for 8581040110', '::1', '2026-06-02 08:39:14'),
(21, 'admin', 1, 'admin', 'parent_updated', 'Updated parent credentials & linkages for Suman Kumar (sonusagarpoly@gmail.com)', '::1', '2026-06-02 08:39:22'),
(22, 'admin', 1, 'admin', 'login', 'Parent successfully logged in', '::1', '2026-06-02 08:39:29'),
(23, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹4,000.00 generated for student SONU', '::1', '2026-06-02 08:39:57'),
(24, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹3,500.00 generated for student SONU', '::1', '2026-06-02 08:53:27'),
(25, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹4,000.00 generated for student SONU', '::1', '2026-06-02 08:54:17'),
(26, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹4,025.00 generated for student SONU', '::1', '2026-06-02 08:57:14'),
(27, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹4,225.00 generated for student SONU', '::1', '2026-06-02 08:58:08'),
(28, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹4,725.00 generated for student SONU', '::1', '2026-06-02 08:58:57'),
(29, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹4,759.00 generated for student SONU', '::1', '2026-06-02 09:03:13'),
(30, 'admin', 1, 'admin', 'fee_payment_recorded', 'Recorded payment of ₹327.00 for student SONU SAGAR (month: June)', '::1', '2026-06-02 09:05:29'),
(31, 'admin', 1, 'admin', 'fee_payment_recorded', 'Recorded payment of ₹759.00 for student SONU (month: June)', '::1', '2026-06-02 09:09:05'),
(32, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-06 07:42:54'),
(33, 'parent', 1, 'sonusagarpoly@gmail.com', 'login', 'Parent successfully logged in', '::1', '2026-06-13 14:34:43'),
(34, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-13 14:35:10'),
(35, 'admin', 1, 'admin', 'logout', 'Admin logged out', '::1', '2026-06-13 14:44:44'),
(36, 'parent', 1, 'sonusagarpoly@gmail.com', 'login', 'Parent successfully logged in', '::1', '2026-06-13 14:44:59'),
(37, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-13 14:45:54'),
(38, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹2,999.99 generated for student SONU SAGAR', '::1', '2026-06-13 14:55:57'),
(39, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹2,999.99 generated for student Ram', '::1', '2026-06-13 14:57:03'),
(40, 'admin', 1, 'admin', 'logout', 'Admin logged out', '::1', '2026-06-13 14:57:43'),
(41, 'parent', 2, 'sonusagarpolysd@gmail.com', 'login', 'Parent successfully logged in', '::1', '2026-06-13 14:57:48'),
(42, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-13 14:58:11'),
(43, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹5,999.98 generated for student Ram (September 2026)', '::1', '2026-06-13 15:10:17'),
(44, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹8,999.97 generated for student Ram (October 2026)', '::1', '2026-06-13 15:10:45'),
(45, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹5,999.98 generated for student Ram (June, September 2026)', '::1', '2026-06-13 15:13:09'),
(46, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹5,999.98 generated for student Ram (June, September 2026)', '::1', '2026-06-13 15:13:29'),
(47, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹5,999.98 generated for student Ram (June, September 2026)', '::1', '2026-06-13 15:18:56'),
(48, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹5,999.98 generated for student Ram (June, September 2026)', '::1', '2026-06-13 15:19:17'),
(49, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹3,000.00 generated for student Gunjan Kumar (June 2026)', '::1', '2026-06-13 15:20:18'),
(50, 'admin', 1, 'admin', 'fee_payment_recorded', 'Recorded payment of ₹5,999.98 for student Ram via Quick Collect (month: June, September 2026)', '::1', '2026-06-13 15:27:51'),
(51, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-15 16:29:11'),
(52, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹1,600.00 generated for student Gola (June 2026)', '::1', '2026-06-15 16:32:34'),
(53, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹1,546.67 generated for student Gola (June 2026)', '::1', '2026-06-15 16:39:41'),
(54, 'admin', 1, 'admin', 'auto_bill_generated', 'Automated bill of ₹1,493.33 generated for student Gola (June 2026)', '::1', '2026-06-15 16:42:16'),
(55, 'admin', 1, 'admin', 'login', 'Admin successfully logged in', '::1', '2026-06-27 14:53:56'),
(56, 'admin', 1, 'admin', 'logout', 'Admin logged out', '::1', '2026-06-28 00:40:32');

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `student_photo` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `parent_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `scholar_mode` enum('Day Scholar','Hostler') DEFAULT NULL,
  `target_program` varchar(100) DEFAULT NULL,
  `prev_school` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Pending','Reviewed','Admitted','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_relationship` varchar(50) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `has_allergies` tinyint(1) DEFAULT 0,
  `allergies_detail` text DEFAULT NULL,
  `has_medical_condition` tinyint(1) DEFAULT 0,
  `medical_condition_detail` text DEFAULT NULL,
  `physician_name` varchar(150) DEFAULT NULL,
  `physician_phone` varchar(20) DEFAULT NULL,
  `insurance_provider` varchar(150) DEFAULT NULL,
  `insurance_policy` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`id`, `student_name`, `student_photo`, `dob`, `gender`, `home_address`, `parent_name`, `phone`, `email`, `scholar_mode`, `target_program`, `prev_school`, `address`, `status`, `created_at`, `city`, `state`, `zip_code`, `guardian_relationship`, `guardian_address`, `emergency_contact_name`, `emergency_relationship`, `emergency_phone`, `has_allergies`, `allergies_detail`, `has_medical_condition`, `medical_condition_detail`, `physician_name`, `physician_phone`, `insurance_provider`, `insurance_policy`) VALUES
(2, 'SONU SAGAR', NULL, '2026-04-21', 'Male', NULL, 'sdfsdf', '+918581040110', 'sonusagarpoly@gmail.com', 'Hostler', 'Navodaya Vidyalaya', 'sdf', 'Lok Kala Bhawan\r\nGewalganj', 'Admitted', '2026-04-22 19:30:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `date`, `status`, `created_at`) VALUES
(1, 1, '2026-05-28', 'absent', '2026-05-28 17:35:50');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `name`, `is_required`, `created_at`) VALUES
(1, 'Aadhar Card', 1, '2026-05-30 04:54:00'),
(2, 'Transfer Certificate (TC)', 1, '2026-05-30 04:54:00'),
(3, 'Birth Certificate', 1, '2026-05-30 04:54:00'),
(4, 'Previous Year Marksheet', 0, '2026-05-30 04:54:00');

-- --------------------------------------------------------

--
-- Table structure for table `fees_generated`
--

CREATE TABLE `fees_generated` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `month_for` varchar(20) NOT NULL,
  `billing_date` date NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees_generated`
--

INSERT INTO `fees_generated` (`id`, `student_id`, `amount`, `month_for`, `billing_date`, `remark`, `status`, `created_at`) VALUES
(1, 1, 1500.00, 'May', '2026-05-28', 'Dudh', 'paid', '2026-05-28 18:05:32'),
(2, 1, 3000.00, 'June', '2026-05-30', 'Dudh', 'paid', '2026-05-30 04:06:25'),
(3, 1, 122.00, 'May', '2026-05-30', 'sdfsdf', 'paid', '2026-05-30 06:26:43'),
(4, 1, 327.00, 'May', '2026-05-30', 'Auto-generated Bill. Base Fee: ₹0.00 | sdfsdf: ₹12.00 | 1 Packet Pem (Expense): ₹25.00 | Base Fee: ₹0.00 | sdfsdf: ₹12.00 | zxczxc (Expense): ₹233.00 | pen (Expense): ₹45.00', 'unpaid', '2026-05-30 06:40:01'),
(5, 2, 4000.00, 'June', '2026-06-02', 'Auto-generated Bill. Base Fee: ₹5,000.00 | Discount applied (-₹1,500.00) | Milk: ₹500.00 | Computer Class: ₹200.00 | 1 Packet Pen (Expense): ₹25.00 | Book (Expense): ₹500.00 | 123 (Expense): ₹34.00 | Payment received on 2026-06-02 (-₹759.00)', 'unpaid', '2026-06-02 08:39:57'),
(6, 3, 5999.98, 'June, September 2026', '2026-06-13', 'Auto-generated Bill. Base Fee: ₹3,000.00 | Discount applied (-₹0.01) | Base Fee: ₹3,000.00 | Discount applied (-₹0.01)', 'paid', '2026-06-13 14:55:57'),
(7, 4, 3000.00, 'June 2026', '2026-06-13', 'Auto-generated Bill. Base Fee: ₹3,000.00 (Prorated: 18/30 days)', 'unpaid', '2026-06-13 15:20:18'),
(8, 5, 1493.33, 'June 2026', '2026-06-15', 'Auto-generated Bill. Base Fee: ₹1,600.00 (Prorated: 16/30 days) | Discount applied (-₹106.67)', 'unpaid', '2026-06-15 16:32:34');

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `month_for` varchar(20) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `student_id`, `amount`, `payment_date`, `month_for`, `payment_method`, `created_at`) VALUES
(1, 1, 7000.00, '2026-05-28', 'January', 'Cash', '2026-05-28 17:35:25'),
(2, 1, 2000.00, '2026-05-30', 'February', 'Cash', '2026-05-28 17:56:16'),
(3, 1, 2000.00, '2026-05-30', 'February', 'Cash', '2026-05-28 18:04:05'),
(4, 1, 1500.00, '2026-05-28', 'May', 'Cash', '2026-05-28 18:27:24'),
(5, 1, 3000.00, '2026-05-30', 'June', 'Cash', '2026-05-30 04:09:48'),
(6, 1, 122.00, '2026-05-30', 'May', 'Cash', '2026-05-30 06:39:46'),
(7, 1, 327.00, '2026-06-02', 'June', 'Cash', '2026-06-02 09:05:29'),
(8, 2, 759.00, '2026-06-02', 'June', 'Cash', '2026-06-02 09:09:05'),
(9, 3, 5999.98, '2026-06-13', 'June, September 2026', 'Cash', '2026-06-13 15:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `image_path`, `caption`, `created_at`) VALUES
(1, 'assets/gallery/1776881454_1600.jpg', 'dfdfdf', '2026-04-22 18:10:54'),
(2, 'assets/gallery/1780112974_9409.png', 'POster Celebration', '2026-05-30 03:49:34');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `candidate_name` varchar(100) NOT NULL,
  `parent_phone` varchar(15) NOT NULL,
  `target_exam` varchar(100) DEFAULT NULL,
  `status` enum('new','contacted','admitted','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `type` enum('info','important','event') DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `type`, `is_active`, `created_at`) VALUES
(1, 'Dudh Facility Available', 'Jo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call Karen\r\nJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call KarenJo koi lene chahte hai obo Call Karen', 'info', 1, '2026-05-28 18:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `parent_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `parent_name`, `email`, `password`, `phone`, `created_at`) VALUES
(1, 'Suman Kumar', 'sonusagarpoly@gmail.com', '$2y$10$HF5JBe/Wkc/Jf4LlxNagV.JWxSmpsInrWoSDAiUVc1Mb3aZavq2/.', '8581040110', '2026-05-28 17:49:02'),
(2, 'Shyam', 'sonusagarpolysd@gmail.com', '$2y$10$hgnUO3gtjnZ2JiNTrAN68ONF3WpphxhZb46x0OhOCgfu9Fhn8Pcoe', '7676763454', '2026-06-13 14:57:03'),
(3, 'Laljeet Yadav', 'dfghdfg@gmail.com', '$2y$10$MKgJ.D./UAIEi.GbLQYPsuHC.tDKxoPhZUOnuyEPZq3TagGI1Nrya', '8581040111', '2026-06-13 15:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `total_marks` int(11) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_id`, `exam_name`, `score`, `total_marks`, `rank`, `exam_date`, `created_at`) VALUES
(1, 1, 'KYP', 12.00, 15, 1, '2026-05-28', '2026-05-28 18:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `school_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_name`, `created_at`) VALUES
(1, 'Netarhat Residential School', '2026-04-22 19:04:57'),
(2, 'Sainik School', '2026-04-22 19:04:57'),
(3, 'Navodaya Vidyalaya', '2026-04-22 19:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `category` varchar(20) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `category`, `updated_at`) VALUES
(1, 'school_name', 'Awasiya Bal Shikshan Sansthan', 'general', '2026-02-13 17:45:48'),
(2, 'phone', '+91 9523012888', 'general', '2026-02-13 17:45:48'),
(3, 'email', 'abssimamganj@gmail.com', 'general', '2026-02-13 17:45:48'),
(4, 'address', 'Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206', 'general', '2026-02-13 17:45:48'),
(5, 'facebook', '#', 'general', '2026-02-13 17:45:48'),
(6, 'twitter', '#', 'general', '2026-02-13 17:45:48'),
(7, 'instagram', '#', 'general', '2026-02-13 17:45:48'),
(8, 'linkedin', '#', 'general', '2026-02-13 17:45:48'),
(9, 'res_fee', '5000', 'general', '2026-02-13 17:45:48'),
(10, 'day_fee', '3000', 'general', '2026-02-13 17:45:48'),
(11, 'admission_fee', '2000', 'general', '2026-02-13 17:45:48'),
(12, 'registration_fee', '100', 'general', '2026-02-13 17:45:48'),
(13, 'development_fee', '1000', 'general', '2026-02-13 17:45:48'),
(14, 'smtp_host', 'smtp.gmail.com', 'smtp', '2026-05-28 17:45:59'),
(15, 'smtp_port', '587', 'smtp', '2026-05-28 17:45:59'),
(16, 'smtp_username', '', 'smtp', '2026-05-28 17:45:59'),
(17, 'smtp_password', '', 'smtp', '2026-05-28 17:45:59'),
(18, 'smtp_encryption', 'tls', 'smtp', '2026-05-28 17:45:59');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('day_fee', '3000', '2026-05-31 08:19:02'),
('director_image_path', 'uploads/settings/director_1780126879.png', '2026-05-30 07:41:19'),
('extra_fees', '{\"Registration Fee\":100,\"Admission Fee\":2000,\"Annual Development\":1000,\"Library charge\":200}', '2026-05-31 08:19:02'),
('fee_day_scholar', '3000', '2026-05-30 06:27:11'),
('fee_hostler', '5000', '2026-05-30 06:27:11'),
('razorpay_key_id', 'admin', '2026-05-30 07:41:19'),
('razorpay_key_secret', 'admin123', '2026-05-30 07:41:19'),
('res_fee', '5000', '2026-05-31 08:19:02'),
('tuition_modes', '{\"Day Scholar\":3000,\"Hostler\":5000}', '2026-05-30 07:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `site_visitors`
--

CREATE TABLE `site_visitors` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(512) DEFAULT NULL,
  `page_visited` varchar(255) NOT NULL,
  `user_role` varchar(50) DEFAULT 'guest',
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_visitors`
--

INSERT INTO `site_visitors` (`id`, `ip_address`, `user_agent`, `referrer`, `page_visited`, `user_role`, `user_id`, `parent_id`, `visited_at`) VALUES
(1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/tickets', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:22:53'),
(2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-28 18:23:05'),
(3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:23:21'),
(4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:23:43'),
(5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-28 18:23:46'),
(6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:23:54'),
(7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:23:56'),
(8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:25:04'),
(9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:25:39'),
(10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-28 18:25:42'),
(11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-28 18:26:03'),
(12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors?tab=audits&role=admin&search=', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-28 18:26:05'),
(13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors?tab=audits&role=parent&search=', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:26:19'),
(14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-28 18:26:41'),
(15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors?tab=audits&role=parent&search=', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:26:44'),
(16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:26:45'),
(17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:27:24'),
(18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-28 18:27:34'),
(19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:27:40'),
(20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-28 18:27:44'),
(21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-28 18:31:38'),
(22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:31:41'),
(23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:31:43'),
(24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-28 18:31:47'),
(25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:31:49'),
(26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-28 18:31:51'),
(27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=3', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:31:53'),
(28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-28 18:31:54'),
(29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:31:56'),
(30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-28 18:31:58'),
(31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?collect_offline=1', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:32:14'),
(32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?collect_offline=1', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:33:51'),
(33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/attendance', 'admin', 1, NULL, '2026-05-28 18:34:03'),
(34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:34:10'),
(35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:34:16'),
(36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:35:44'),
(37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/attendance', 'admin', 1, NULL, '2026-05-28 18:35:49'),
(38, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:35:53'),
(39, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:35:55'),
(40, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:36:07'),
(41, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-28 18:36:10'),
(42, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-28 18:36:35'),
(43, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:36:40'),
(44, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/results', 'admin', 1, NULL, '2026-05-28 18:36:46'),
(45, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/results', 'admin', 1, NULL, '2026-05-28 18:37:00'),
(46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/results', 'admin', 1, NULL, '2026-05-28 18:37:01'),
(47, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/results', '/abss/admin/inquiries', 'admin', 1, NULL, '2026-05-28 18:37:10'),
(48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/inquiries', '/abss/admin/tickets', 'admin', 1, NULL, '2026-05-28 18:37:13'),
(49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/tickets', '/abss/admin/admissions', 'admin', 1, NULL, '2026-05-28 18:37:17'),
(50, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/admissions', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-28 18:37:22'),
(51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-28 18:37:25'),
(52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors', '/abss/admin/attendance', 'admin', 1, NULL, '2026-05-28 18:37:28'),
(53, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:37:35'),
(54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-28 18:37:44'),
(55, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-28 18:37:51'),
(56, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:38:50'),
(57, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:39:14'),
(58, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-28 18:39:19'),
(59, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/results', 'admin', 1, NULL, '2026-05-28 18:39:23'),
(60, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/results', '/abss/admin/inquiries', 'admin', 1, NULL, '2026-05-28 18:39:28'),
(61, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/inquiries', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:39:39'),
(62, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:39:40'),
(63, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:40:08'),
(64, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:40:14'),
(65, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:40:23'),
(66, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/attendance', 'admin', 1, NULL, '2026-05-28 18:40:35'),
(67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:40:42'),
(68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-28 18:40:43'),
(69, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'admin', 1, NULL, '2026-05-28 18:40:45'),
(70, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-28 18:40:45'),
(71, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-28 18:40:46'),
(72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/tickets', 'admin', 1, NULL, '2026-05-28 18:40:46'),
(73, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/settings', 'admin', 1, NULL, '2026-05-28 18:40:47'),
(74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/settings', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-28 18:40:48'),
(75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/admissions', 'admin', 1, NULL, '2026-05-28 18:58:27'),
(76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/admissions', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:58:31'),
(77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/admissions', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:58:37'),
(78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/admissions', '/abss/admin/students', 'admin', 1, NULL, '2026-05-28 18:59:25'),
(79, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/gallery', 'admin', 1, NULL, '2026-05-28 18:59:36'),
(80, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/gallery', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-28 18:59:38'),
(81, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/index', 'admin', 1, NULL, '2026-05-28 18:59:45'),
(82, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admin/login', 'admin', 1, NULL, '2026-05-28 19:00:51'),
(83, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admin/login', 'admin', 1, NULL, '2026-05-28 19:00:55'),
(84, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/inauguration', 'admin', 1, NULL, '2026-05-28 19:00:58'),
(85, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/inauguration', '/abss/index', 'admin', 1, NULL, '2026-05-28 19:01:11'),
(86, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/inauguration', '/abss/index', 'admin', 1, NULL, '2026-05-28 19:03:08'),
(87, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/inauguration', '/abss/index', 'admin', 1, NULL, '2026-05-28 19:03:36'),
(88, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admission', 'admin', 1, NULL, '2026-05-28 19:03:37'),
(89, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-28 19:04:01'),
(90, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 03:46:59'),
(91, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 03:48:00'),
(92, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 03:48:11'),
(93, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-05-30 03:48:12'),
(94, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-05-30 03:48:14'),
(95, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/login', 'parent', NULL, 1, '2026-05-30 03:48:19'),
(96, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'parent', NULL, 1, '2026-05-30 03:48:21'),
(97, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 03:48:21'),
(98, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-30 03:48:54'),
(99, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors', '/abss/admin/inquiries', 'admin', 1, NULL, '2026-05-30 03:49:15'),
(100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/inquiries', '/abss/admin/achievers', 'admin', 1, NULL, '2026-05-30 03:49:17'),
(101, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/achievers', '/abss/admin/gallery', 'admin', 1, NULL, '2026-05-30 03:49:19'),
(102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/gallery', '/abss/admin/gallery', 'admin', 1, NULL, '2026-05-30 03:49:34'),
(103, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'admin', 1, NULL, '2026-05-30 03:49:44'),
(104, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/gallery', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 03:50:23'),
(105, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 03:50:25'),
(106, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 03:50:33'),
(107, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 03:51:40'),
(108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admission', 'admin', 1, NULL, '2026-05-30 03:58:10'),
(109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 03:59:02'),
(110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 03:59:06'),
(111, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 03:59:23'),
(112, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 03:59:30'),
(113, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 03:59:42'),
(114, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 03:59:52'),
(115, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-30 03:59:54'),
(116, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 03:59:56'),
(117, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 03:59:58'),
(118, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:00:15'),
(119, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 04:00:17'),
(120, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:00:22'),
(121, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-30 04:00:28'),
(122, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/tickets', 'admin', 1, NULL, '2026-05-30 04:00:36'),
(123, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/settings', 'admin', 1, NULL, '2026-05-30 04:00:46'),
(124, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/settings', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:00:51'),
(125, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 04:01:07'),
(126, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:01:11'),
(127, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/attendance', 'admin', 1, NULL, '2026-05-30 04:01:15'),
(128, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/attendance', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:01:17'),
(129, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 04:01:28'),
(130, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admission', 'admin', 1, NULL, '2026-05-30 04:01:29'),
(131, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 04:01:34'),
(132, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 04:04:21'),
(133, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:04:21'),
(134, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:04:43'),
(135, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 04:05:07'),
(136, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 04:06:25'),
(137, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:06:37'),
(138, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 04:08:17'),
(139, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:08:34'),
(140, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 04:09:48'),
(141, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:09:53'),
(142, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 04:10:00'),
(143, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:10:10'),
(144, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?collect_offline=2', '/abss/admin/results', 'admin', 1, NULL, '2026-05-30 04:10:34'),
(145, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/results', '/abss/admin/inquiries', 'admin', 1, NULL, '2026-05-30 04:10:43'),
(146, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/inquiries', '/abss/admin/tickets', 'admin', 1, NULL, '2026-05-30 04:10:44'),
(147, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/tickets', '/abss/admin/admissions', 'admin', 1, NULL, '2026-05-30 04:10:46'),
(148, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/admissions', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-30 04:10:51'),
(149, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/notices', 'admin', 1, NULL, '2026-05-30 04:10:53'),
(150, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/notices', '/abss/admin/gallery', 'admin', 1, NULL, '2026-05-30 04:10:55'),
(151, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 04:11:05'),
(152, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/gallery', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:12:02'),
(153, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-30 04:13:00'),
(154, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:15:20'),
(155, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 04:15:34'),
(156, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:15:46'),
(157, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 04:17:50'),
(158, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 04:17:51'),
(159, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-30 04:18:03'),
(160, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-30 04:18:09'),
(161, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-30 04:18:13'),
(162, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-30 04:18:14'),
(163, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:18:35'),
(164, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:18:45'),
(165, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admission', 'admin', 1, NULL, '2026-05-30 04:22:08'),
(166, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:25:53'),
(167, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:26:01'),
(168, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:26:19'),
(169, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:27:07'),
(170, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:27:50'),
(171, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:28:33'),
(172, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:28:38'),
(173, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:35:44'),
(174, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:35:52'),
(175, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/admission', 'admin', 1, NULL, '2026-05-30 04:36:09'),
(176, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:36:38'),
(177, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:38:39'),
(178, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:38:39'),
(179, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:38:42'),
(180, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:53:43'),
(181, 'UNKNOWN', '', '', '', 'guest', NULL, NULL, '2026-05-30 04:54:00'),
(182, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:54:28'),
(183, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:54:32'),
(184, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 04:54:50'),
(185, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 04:54:58'),
(186, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:55:12'),
(187, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 04:58:14'),
(188, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 04:58:18'),
(189, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:59:14'),
(190, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:14'),
(191, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:16'),
(192, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:16'),
(193, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:17'),
(194, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:17'),
(195, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:17'),
(196, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:17'),
(197, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 04:59:19'),
(198, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 04:59:20'),
(199, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:20'),
(200, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:20'),
(201, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:39'),
(202, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:46'),
(203, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 04:59:49'),
(204, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:53'),
(205, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:54'),
(206, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 04:59:55'),
(207, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 05:00:00'),
(208, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 05:00:03'),
(209, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-30 05:00:06'),
(210, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/tickets', 'admin', 1, NULL, '2026-05-30 05:00:07'),
(211, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/settings', 'admin', 1, NULL, '2026-05-30 05:00:07'),
(212, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/settings', '/abss/parent/tickets', 'admin', 1, NULL, '2026-05-30 05:00:08'),
(213, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-30 05:00:09'),
(214, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 05:00:09'),
(215, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 05:00:09'),
(216, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 05:00:10'),
(217, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 05:00:18');
INSERT INTO `site_visitors` (`id`, `ip_address`, `user_agent`, `referrer`, `page_visited`, `user_role`, `user_id`, `parent_id`, `visited_at`) VALUES
(218, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 05:00:41'),
(219, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 05:00:45'),
(220, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-30 05:00:47'),
(221, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 05:00:55'),
(222, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 05:04:22'),
(223, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 05:04:23'),
(224, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 05:04:29'),
(225, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 05:04:30'),
(226, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 05:04:37'),
(227, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 05:05:10'),
(228, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 05:05:13'),
(229, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 05:05:26'),
(230, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 05:05:38'),
(231, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 05:05:39'),
(232, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 05:05:39'),
(233, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 05:05:40'),
(234, 'UNKNOWN', '', '', '', 'guest', NULL, NULL, '2026-05-30 05:11:50'),
(235, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 06:03:18'),
(236, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 06:03:24'),
(237, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:03:28'),
(238, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 06:03:33'),
(239, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:03:42'),
(240, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:03:52'),
(241, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 06:04:22'),
(242, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:04:25'),
(243, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:04:44'),
(244, 'UNKNOWN', '', '', '', 'guest', NULL, NULL, '2026-05-30 06:11:23'),
(245, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:12:25'),
(246, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:12:29'),
(247, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:12:35'),
(248, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:12:54'),
(249, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:14:09'),
(250, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:14:47'),
(251, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:19:18'),
(252, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:19:28'),
(253, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 06:21:32'),
(254, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses.php', 'admin', 1, NULL, '2026-05-30 06:21:43'),
(255, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:22:02'),
(256, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:22:03'),
(257, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:22:05'),
(258, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:22:06'),
(259, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 06:22:12'),
(260, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 06:22:13'),
(261, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:22:15'),
(262, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 06:22:19'),
(263, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:22:22'),
(264, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-30 06:23:18'),
(265, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-30 06:23:20'),
(266, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-30 06:23:21'),
(267, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:23:21'),
(268, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:23:59'),
(269, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:24:10'),
(270, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:24:11'),
(271, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/student_addons.php', 'admin', 1, NULL, '2026-05-30 06:24:16'),
(272, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons.php?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:24:18'),
(273, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:24:19'),
(274, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:24:23'),
(275, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:24:24'),
(276, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:24:25'),
(277, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:24:31'),
(278, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:26:18'),
(279, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:26:43'),
(280, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:26:52'),
(281, 'UNKNOWN', '', '', '', 'guest', NULL, NULL, '2026-05-30 06:27:11'),
(282, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 06:28:41'),
(283, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 06:28:44'),
(284, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:29:58'),
(285, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:30:00'),
(286, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:30:09'),
(287, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:30:14'),
(288, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:30:25'),
(289, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:31:04'),
(290, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:36:10'),
(291, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:36:22'),
(292, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-05-30 06:36:24'),
(293, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:36:26'),
(294, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:36:41'),
(295, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:36:50'),
(296, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=1', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:38:48'),
(297, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 06:38:52'),
(298, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-30 06:39:03'),
(299, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 06:39:05'),
(300, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/admissions', 'admin', 1, NULL, '2026-05-30 06:39:06'),
(301, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/admissions', '/abss/admin/visitors', 'admin', 1, NULL, '2026-05-30 06:39:08'),
(302, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/visitors', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:39:32'),
(303, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:39:46'),
(304, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:39:51'),
(305, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:39:56'),
(306, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:40:01'),
(307, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:40:19'),
(308, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:42:48'),
(309, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:42:50'),
(310, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 06:42:54'),
(311, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 06:42:54'),
(312, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:42:56'),
(313, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:18'),
(314, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 06:44:31'),
(315, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses.php', 'admin', 1, NULL, '2026-05-30 06:44:41'),
(316, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 06:44:43'),
(317, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:44'),
(318, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:49'),
(319, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:56'),
(320, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:56'),
(321, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/results', 'admin', 1, NULL, '2026-05-30 06:44:57'),
(322, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/results', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 06:44:58'),
(323, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:58'),
(324, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:44:59'),
(325, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:45:18'),
(326, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:45:21'),
(327, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-05-30 06:45:26'),
(328, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:45:48'),
(329, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:45:49'),
(330, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 06:45:51'),
(331, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:46:26'),
(332, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 06:46:32'),
(333, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:46:34'),
(334, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:48:34'),
(335, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 06:48:38'),
(336, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 06:50:43'),
(337, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:51:01'),
(338, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:51:10'),
(339, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:51:11'),
(340, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:51:15'),
(341, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:53:16'),
(342, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 06:58:41'),
(343, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 06:58:48'),
(344, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 06:58:50'),
(345, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:58:56'),
(346, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 06:58:59'),
(347, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 06:59:06'),
(348, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-30 06:59:11'),
(349, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 06:59:29'),
(350, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:07:18'),
(351, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:07:24'),
(352, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 07:07:28'),
(353, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 07:07:40'),
(354, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:07:51'),
(355, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:07:55'),
(356, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 07:07:59'),
(357, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:08:02'),
(358, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:08:03'),
(359, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:08:08'),
(360, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 07:08:10'),
(361, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 07:11:20'),
(362, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:11:48'),
(363, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/parents', 'admin', 1, NULL, '2026-05-30 07:11:49'),
(364, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-30 07:11:51'),
(365, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses.php', 'admin', 1, NULL, '2026-05-30 07:12:01'),
(366, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:12:03'),
(367, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:12:05'),
(368, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 07:12:10'),
(369, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:12:19'),
(370, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:17:08'),
(371, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:18:22'),
(372, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:18:35'),
(373, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-05-30 07:18:37'),
(374, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:18:41'),
(375, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=4', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:31:21'),
(376, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-30 07:40:52'),
(377, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 07:40:54'),
(378, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/settings.php', 'admin', 1, NULL, '2026-05-30 07:41:19'),
(379, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 07:41:23'),
(380, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 07:41:27'),
(381, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 07:41:41'),
(382, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 07:41:45'),
(383, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 07:41:45'),
(384, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 07:41:47'),
(385, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 07:41:48'),
(386, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 07:54:16'),
(387, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 07:54:23'),
(388, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 07:54:27'),
(389, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 11:50:03'),
(390, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-30 11:50:05'),
(391, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:50:21'),
(392, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:51:37'),
(393, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:54:19'),
(394, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:54:20'),
(395, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:54:37'),
(396, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:54:37'),
(397, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 11:54:40'),
(398, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:54:40'),
(399, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 11:54:50'),
(400, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:54:50'),
(401, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:54:52'),
(402, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:55:05'),
(403, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:55:08'),
(404, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:55:13'),
(405, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 11:55:15'),
(406, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-05-30 11:55:19'),
(407, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-05-30 11:55:20'),
(408, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 11:55:24'),
(409, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 11:55:27'),
(410, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 11:55:29'),
(411, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-05-30 11:57:19'),
(412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-30 11:58:15'),
(413, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 11:58:51'),
(414, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=2', '/abss/parent/notices', 'admin', 1, NULL, '2026-05-30 11:59:39'),
(415, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/tickets', 'admin', 1, NULL, '2026-05-30 11:59:43'),
(416, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/tickets', '/abss/parent/settings', 'admin', 1, NULL, '2026-05-30 11:59:46'),
(417, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/settings', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-05-30 11:59:47'),
(418, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/students', 'admin', 1, NULL, '2026-05-30 12:00:23'),
(419, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-30 12:00:34'),
(420, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 12:00:51'),
(421, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 12:48:31'),
(422, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/schools', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-30 12:49:03'),
(423, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/index', 'guest', NULL, NULL, '2026-05-30 12:49:33'),
(424, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 12:49:40'),
(425, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 12:49:51'),
(426, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 12:49:54'),
(427, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 12:50:22'),
(428, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 12:50:33'),
(429, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-05-30 12:50:33'),
(430, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/admin/logout', 'parent', NULL, 1, '2026-05-30 12:50:40'),
(431, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-30 12:50:40'),
(432, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 12:50:45'),
(433, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 12:50:58'),
(434, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 12:51:28'),
(435, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 12:52:32');
INSERT INTO `site_visitors` (`id`, `ip_address`, `user_agent`, `referrer`, `page_visited`, `user_role`, `user_id`, `parent_id`, `visited_at`) VALUES
(436, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-30 12:52:45'),
(437, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'admin', 1, NULL, '2026-05-30 12:55:32'),
(438, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 12:55:44'),
(439, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-30 12:55:55'),
(440, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'admin', 1, NULL, '2026-05-30 12:56:27'),
(441, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 06:46:15'),
(442, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 06:48:46'),
(443, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 06:49:40'),
(444, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admission', 'guest', NULL, NULL, '2026-05-31 06:50:08'),
(445, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'guest', NULL, NULL, '2026-05-31 06:50:23'),
(446, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 06:51:13'),
(447, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 06:52:20'),
(448, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:06:16'),
(449, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:10:06'),
(450, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:10:30'),
(451, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'http://192.168.0.102/abss/', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-31 07:11:36'),
(452, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:11:42'),
(453, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:12:35'),
(454, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:13:02'),
(455, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:13:49'),
(456, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:13:51'),
(457, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:16:00'),
(458, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:16:01'),
(459, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:23:13'),
(460, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:23:51'),
(461, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:23:51'),
(462, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:23:55'),
(463, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app_home', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-31 07:24:01'),
(464, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app_home', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:24:04'),
(465, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:24:26'),
(466, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:25:06'),
(467, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:25:08'),
(468, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:25:08'),
(469, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:25:36'),
(470, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:27:26'),
(471, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:31:34'),
(472, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:31:59'),
(473, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:41:31'),
(474, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:41:33'),
(475, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:41:33'),
(476, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app_home', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-31 07:41:41'),
(477, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/admin/login', 'guest', NULL, NULL, '2026-05-31 07:41:53'),
(478, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-05-31 07:41:53'),
(479, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'parent', NULL, 1, '2026-05-31 07:42:12'),
(480, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'parent', NULL, 1, '2026-05-31 07:42:26'),
(481, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'parent', NULL, 1, '2026-05-31 07:42:33'),
(482, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/view_bill', 'parent', NULL, 1, '2026-05-31 07:42:38'),
(483, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/app_home', 'parent', NULL, 1, '2026-05-31 07:42:50'),
(484, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admission', 'parent', NULL, 1, '2026-05-31 07:44:07'),
(485, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'android-app://com.google.android.googlequicksearchbox/', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:44:48'),
(486, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'parent', NULL, 1, '2026-05-31 07:45:32'),
(487, '192.168.0.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-05-31 07:46:07'),
(488, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:47:02'),
(489, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:47:04'),
(490, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:47:04'),
(491, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/index', 'guest', NULL, NULL, '2026-05-31 07:54:58'),
(492, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-05-31 07:55:13'),
(493, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admission', 'parent', NULL, 1, '2026-05-31 07:55:42'),
(494, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'parent', NULL, 1, '2026-05-31 07:56:03'),
(495, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'parent', NULL, 1, '2026-05-31 07:58:25'),
(496, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'parent', NULL, 1, '2026-05-31 08:00:41'),
(497, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/admin/login', 'parent', NULL, 1, '2026-05-31 08:00:46'),
(498, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'parent', NULL, 1, '2026-05-31 08:00:48'),
(499, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-31 08:00:48'),
(500, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-31 08:00:51'),
(501, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/settings', 'admin', 1, NULL, '2026-05-31 08:08:45'),
(502, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-31 08:17:04'),
(503, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/settings.php', 'admin', 1, NULL, '2026-05-31 08:19:02'),
(504, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-31 08:19:09'),
(505, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings.php', '/abss/admin/results', 'admin', 1, NULL, '2026-05-31 08:22:26'),
(506, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/results', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-31 08:22:28'),
(507, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses', 'admin', 1, NULL, '2026-05-31 08:22:41'),
(508, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/fees', 'admin', 1, NULL, '2026-05-31 08:22:41'),
(509, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-31 08:23:13'),
(510, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-05-31 11:07:23'),
(511, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admission', '/abss/index', 'admin', 1, NULL, '2026-05-31 11:07:24'),
(512, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/documents', 'admin', 1, NULL, '2026-05-31 11:18:58'),
(513, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-05-31 11:19:01'),
(514, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/schools', 'admin', 1, NULL, '2026-05-31 11:19:08'),
(515, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-02 07:10:36'),
(516, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-02 07:10:38'),
(517, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-02 07:10:38'),
(518, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-02 08:37:44'),
(519, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-02 08:37:53'),
(520, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-02 08:37:55'),
(521, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-02 08:37:55'),
(522, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:38:12'),
(523, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:38:38'),
(524, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:38:41'),
(525, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-02 08:38:48'),
(526, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:38:53'),
(527, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:38:56'),
(528, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:39:01'),
(529, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:39:14'),
(530, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-02 08:39:22'),
(531, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'admin', 1, NULL, '2026-06-02 08:39:29'),
(532, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-02 08:39:29'),
(533, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 08:39:40'),
(534, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 08:39:48'),
(535, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 08:39:51'),
(536, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:39:54'),
(537, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:39:57'),
(538, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 08:40:01'),
(539, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=2', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:40:12'),
(540, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 08:40:21'),
(541, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:40:35'),
(542, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:44:43'),
(543, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:44:54'),
(544, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:44:55'),
(545, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:46:29'),
(546, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:46:31'),
(547, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:46:33'),
(548, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:46:42'),
(549, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:46:46'),
(550, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:46:47'),
(551, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:46:50'),
(552, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:46:52'),
(553, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:53:18'),
(554, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:53:27'),
(555, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:53:30'),
(556, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:53:42'),
(557, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-06-02 08:53:47'),
(558, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=2', '/abss/admin/student_addons.php', 'admin', 1, NULL, '2026-06-02 08:54:10'),
(559, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons.php?id=2', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:54:16'),
(560, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:54:17'),
(561, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:54:24'),
(562, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-06-02 08:56:32'),
(563, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses.php', 'admin', 1, NULL, '2026-06-02 08:56:45'),
(564, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:56:49'),
(565, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:56:53'),
(566, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:57:14'),
(567, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:57:22'),
(568, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:57:40'),
(569, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 08:57:49'),
(570, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/student_addons', 'admin', 1, NULL, '2026-06-02 08:57:52'),
(571, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons?id=2', '/abss/admin/student_addons.php', 'admin', 1, NULL, '2026-06-02 08:58:03'),
(572, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/student_addons.php?id=2', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:58:06'),
(573, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:58:08'),
(574, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 08:58:11'),
(575, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-06-02 08:58:30'),
(576, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses.php', 'admin', 1, NULL, '2026-06-02 08:58:38'),
(577, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:58:41'),
(578, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-06-02 08:58:47'),
(579, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:58:55'),
(580, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 08:58:57'),
(581, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:01:21'),
(582, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:02:59'),
(583, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-06-02 09:03:04'),
(584, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses', '/abss/admin/expenses.php', 'admin', 1, NULL, '2026-06-02 09:03:13'),
(585, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:03:17'),
(586, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:03:42'),
(587, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:04:20'),
(588, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/expenses.php', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:05:20'),
(589, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:05:29'),
(590, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:05:38'),
(591, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:06:56'),
(592, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-02 09:07:11'),
(593, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:08:24'),
(594, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:08:53'),
(595, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:09:05'),
(596, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:09:17'),
(597, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 09:11:04'),
(598, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:11:57'),
(599, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:12:21'),
(600, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:12:25'),
(601, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:12:30'),
(602, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/notices', 'admin', 1, NULL, '2026-06-02 09:12:38'),
(603, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/notices', '/abss/parent/settings', 'admin', 1, NULL, '2026-06-02 09:12:43'),
(604, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/settings', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-02 09:12:44'),
(605, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:12:50'),
(606, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 09:13:45'),
(607, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:15:14'),
(608, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-02 09:15:15'),
(609, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'admin', 1, NULL, '2026-06-02 09:15:21'),
(610, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-06-02 09:15:30'),
(611, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:15:33'),
(612, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:15:35'),
(613, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:15:37'),
(614, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:15:39'),
(615, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:15:40'),
(616, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=2', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:15:42'),
(617, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 09:19:57'),
(618, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:20:58'),
(619, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:21:09'),
(620, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-02 09:23:17'),
(621, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:23:32'),
(622, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/settings.php', 'admin', 1, NULL, '2026-06-02 09:23:41'),
(623, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-02 09:24:06'),
(624, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'admin', 1, NULL, '2026-06-02 09:24:57'),
(625, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-02 09:32:03'),
(626, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-02 09:32:43'),
(627, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:32:55'),
(628, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:37:19'),
(629, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/receipt', 'admin', 1, NULL, '2026-06-02 09:37:27'),
(630, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/receipt?id=8', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:37:48'),
(631, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:37:56'),
(632, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:38:15'),
(633, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:38:20'),
(634, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:38:22'),
(635, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=2', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:38:27'),
(636, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=1', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:38:29'),
(637, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees?child_id=2', '/abss/parent/view_bill', 'admin', 1, NULL, '2026-06-02 09:38:30'),
(638, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/view_bill?id=5', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-02 09:38:33'),
(639, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:38:40'),
(640, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:39:58'),
(641, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/inquiries', 'admin', 1, NULL, '2026-06-02 09:40:11'),
(642, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/inquiries', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:40:18'),
(643, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:40:36'),
(644, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-02 09:40:44'),
(645, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=3', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:40:46'),
(646, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-02 09:40:49'),
(647, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=5', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:40:55'),
(648, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/parent/settings', 'admin', 1, NULL, '2026-06-02 09:41:47'),
(649, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/gallery', 'admin', 1, NULL, '2026-06-02 09:42:56'),
(650, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/gallery', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-02 09:42:58'),
(651, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:43:35'),
(652, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:43:38'),
(653, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?filter=paid', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:43:41'),
(654, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?filter=unpaid', '/abss/admin/bulk_print', 'admin', 1, NULL, '2026-06-02 09:43:48'),
(655, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?filter=unpaid', '/abss/admin/settings', 'admin', 1, NULL, '2026-06-02 09:44:09');
INSERT INTO `site_visitors` (`id`, `ip_address`, `user_agent`, `referrer`, `page_visited`, `user_role`, `user_id`, `parent_id`, `visited_at`) VALUES
(656, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:44:17'),
(657, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/settings', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:49:41'),
(658, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:50:20'),
(659, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?filter=unpaid', '/abss/admin/bulk_print', 'admin', 1, NULL, '2026-06-02 09:50:26'),
(660, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-02 09:52:49'),
(661, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?filter=unpaid', '/abss/admin/bulk_print', 'admin', 1, NULL, '2026-06-02 09:52:57'),
(662, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?filter=unpaid', '/abss/admin/bulk_print', 'admin', 1, NULL, '2026-06-02 09:53:21'),
(663, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-06 07:42:42'),
(664, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-06 07:42:45'),
(665, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-06 07:42:52'),
(666, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-06 07:42:54'),
(667, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-06 07:42:54'),
(668, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-06 07:44:54'),
(669, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 07:45:39'),
(670, '::1', 'Go-http-client/1.1', 'http://localhost/abss/admin/login.php', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-06 07:47:48'),
(671, '::1', 'Go-http-client/1.1', 'http://localhost/abss/admin/login.php', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-06 07:47:49'),
(672, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 07:51:18'),
(673, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/documents', 'admin', 1, NULL, '2026-06-06 07:51:30'),
(674, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/documents', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 07:51:32'),
(675, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 07:51:33'),
(676, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 07:51:38'),
(677, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:02:18'),
(678, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:02:22'),
(679, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:02:22'),
(680, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:02:24'),
(681, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:02:25'),
(682, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:02:49'),
(683, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:02:59'),
(684, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:02:59'),
(685, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:03:01'),
(686, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:03:02'),
(687, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:03:09'),
(688, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:03:09'),
(689, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:03:10'),
(690, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:03:11'),
(691, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:03:43'),
(692, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:03:52'),
(693, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:04:04'),
(694, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:04:04'),
(695, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:04:09'),
(696, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:04:09'),
(697, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:04:11'),
(698, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:04:16'),
(699, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:04:27'),
(700, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:04:29'),
(701, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:04:34'),
(702, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:04:34'),
(703, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:04:35'),
(704, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:04:36'),
(705, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:04:45'),
(706, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:04:48'),
(707, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:11'),
(708, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:05:14'),
(709, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=1', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:24'),
(710, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:31'),
(711, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:31'),
(712, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:05:32'),
(713, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=1', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:36'),
(714, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:05:37'),
(715, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:05:42'),
(716, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:05:42'),
(717, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:43'),
(718, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:05:45'),
(719, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:05:47'),
(720, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:07:52'),
(721, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:07:56'),
(722, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:02'),
(723, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:02'),
(724, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:08:03'),
(725, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:08:05'),
(726, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=1', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:08:12'),
(727, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:24'),
(728, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:27'),
(729, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:27'),
(730, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:36'),
(731, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:08:36'),
(732, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:08:39'),
(733, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:08:41'),
(734, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:10:27'),
(735, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=1', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:10:31'),
(736, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:10:32'),
(737, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:10:35'),
(738, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:10:35'),
(739, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:10:36'),
(740, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:10:39'),
(741, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=1', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:10:41'),
(742, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:10:44'),
(743, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:10:44'),
(744, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:11:37'),
(745, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:11:38'),
(746, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:11:42'),
(747, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=2', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:11:47'),
(748, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:11:52'),
(749, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teachers', 'admin', 1, NULL, '2026-06-06 08:12:39'),
(750, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teachers', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:12:43'),
(751, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:12:46'),
(752, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=2', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:12:53'),
(753, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:25:26'),
(754, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:25:33'),
(755, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:25:33'),
(756, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:25:37'),
(757, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:25:37'),
(758, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:25:39'),
(759, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:25:40'),
(760, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:27:01'),
(761, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:27:12'),
(762, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/print_teacher_invoice?id=2', '/abss/admin/teacher_invoices', 'admin', 1, NULL, '2026-06-06 08:27:19'),
(763, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:27:34'),
(764, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:27:40'),
(765, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:27:40'),
(766, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:27:42'),
(767, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:27:45'),
(768, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:27:50'),
(769, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_expenses', '/abss/admin/teacher_expenses', 'admin', 1, NULL, '2026-06-06 08:27:51'),
(770, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/teacher_invoices', '/abss/admin/print_teacher_invoice', 'admin', 1, NULL, '2026-06-06 08:27:51'),
(771, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-13 12:21:02'),
(772, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-13 12:29:36'),
(773, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-13 13:05:06'),
(774, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-13 13:07:19'),
(775, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-13 14:33:41'),
(776, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-13 14:34:26'),
(777, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-13 14:34:43'),
(778, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-06-13 14:34:43'),
(779, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'parent', NULL, 1, '2026-06-13 14:34:51'),
(780, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'parent', NULL, 1, '2026-06-13 14:35:00'),
(781, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/', 'parent', NULL, 1, '2026-06-13 14:35:03'),
(782, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admin/login', 'parent', NULL, 1, '2026-06-13 14:35:08'),
(783, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'parent', NULL, 1, '2026-06-13 14:35:10'),
(784, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-13 14:35:10'),
(785, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-06-13 14:39:35'),
(786, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/document_approvals', 'admin', 1, NULL, '2026-06-13 14:39:44'),
(787, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/results', 'admin', 1, NULL, '2026-06-13 14:39:47'),
(788, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-06-13 14:39:47'),
(789, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-13 14:39:48'),
(790, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-06-13 14:39:48'),
(791, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-13 14:39:49'),
(792, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-06-13 14:39:49'),
(793, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/documents', 'admin', 1, NULL, '2026-06-13 14:44:17'),
(794, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-13 14:44:37'),
(795, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'admin', 1, NULL, '2026-06-13 14:44:41'),
(796, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/admin/logout', 'admin', 1, NULL, '2026-06-13 14:44:44'),
(797, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-13 14:44:44'),
(798, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-13 14:44:58'),
(799, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-06-13 14:44:59'),
(800, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'parent', NULL, 1, '2026-06-13 14:45:11'),
(801, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-06-13 14:45:13'),
(802, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-06-13 14:45:14'),
(803, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/documents', 'parent', NULL, 1, '2026-06-13 14:45:15'),
(804, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/documents', '/abss/parent/dashboard', 'parent', NULL, 1, '2026-06-13 14:45:34'),
(805, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/dashboard', '/abss/parent/results', 'parent', NULL, 1, '2026-06-13 14:45:42'),
(806, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'parent', NULL, 1, '2026-06-13 14:45:43'),
(807, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/document_approvals', '/abss/admin/login', 'parent', NULL, 1, '2026-06-13 14:45:51'),
(808, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'parent', NULL, 1, '2026-06-13 14:45:54'),
(809, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-13 14:45:54'),
(810, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 14:46:52'),
(811, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 14:49:47'),
(812, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=3', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 14:50:01'),
(813, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:50:28'),
(814, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:50:36'),
(815, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:51:16'),
(816, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:51:18'),
(817, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-13 14:52:06'),
(818, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:55:10'),
(819, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:55:13'),
(820, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:55:57'),
(821, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:55:59'),
(822, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:56:01'),
(823, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:56:04'),
(824, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/results', '/abss/parent/fees', 'admin', 1, NULL, '2026-06-13 14:56:16'),
(825, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:56:25'),
(826, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:57:03'),
(827, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:57:05'),
(828, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:57:11'),
(829, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 14:57:18'),
(830, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 14:57:26'),
(831, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/admin/logout', 'admin', 1, NULL, '2026-06-13 14:57:43'),
(832, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/parent/fees', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-13 14:57:43'),
(833, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-13 14:57:48'),
(834, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/parent/dashboard', 'parent', NULL, 2, '2026-06-13 14:57:48'),
(835, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/login', 'parent', NULL, 2, '2026-06-13 14:58:08'),
(836, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/login', 'parent', NULL, 2, '2026-06-13 14:58:11'),
(837, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-13 14:58:12'),
(838, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 14:58:30'),
(839, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:08:08'),
(840, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:09:57'),
(841, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:10:17'),
(842, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:10:20'),
(843, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:10:22'),
(844, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:10:29'),
(845, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:10:38'),
(846, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:10:40'),
(847, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:10:45'),
(848, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:10:47'),
(849, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:10:48'),
(850, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:11:34'),
(851, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:12:50'),
(852, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:13:00'),
(853, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:13:04'),
(854, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:13:09'),
(855, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:13:11'),
(856, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:13:14'),
(857, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:13:17'),
(858, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:13:23'),
(859, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:13:26'),
(860, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:13:29'),
(861, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:13:31'),
(862, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:13:32'),
(863, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:13:41'),
(864, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:18:48'),
(865, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:18:51');
INSERT INTO `site_visitors` (`id`, `ip_address`, `user_agent`, `referrer`, `page_visited`, `user_role`, `user_id`, `parent_id`, `visited_at`) VALUES
(866, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:18:55'),
(867, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:18:58'),
(868, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:18:59'),
(869, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:19:02'),
(870, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:19:05'),
(871, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:19:07'),
(872, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:19:17'),
(873, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:19:19'),
(874, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:19:21'),
(875, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:19:25'),
(876, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:19:28'),
(877, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:19:29'),
(878, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:20:18'),
(879, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-13 15:20:20'),
(880, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-13 15:20:30'),
(881, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:20:44'),
(882, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:20:48'),
(883, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=7', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:21:28'),
(884, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=7', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:25:58'),
(885, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:26:01'),
(886, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=7', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:27:27'),
(887, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:27:51'),
(888, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:27:59'),
(889, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:28:03'),
(890, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees?collect_offline=6', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-13 15:28:11'),
(891, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-13 15:28:16'),
(892, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/expenses', 'admin', 1, NULL, '2026-06-13 15:28:24'),
(893, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-15 16:28:53'),
(894, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-15 16:29:10'),
(895, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-15 16:29:11'),
(896, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-15 16:29:11'),
(897, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:29:22'),
(898, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:29:40'),
(899, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=7', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:29:56'),
(900, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:30:01'),
(901, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=7', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:31:19'),
(902, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:31:28'),
(903, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=6', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:31:41'),
(904, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:31:43'),
(905, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=7', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:31:45'),
(906, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:32:01'),
(907, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:32:34'),
(908, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:32:36'),
(909, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:32:38'),
(910, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:32:42'),
(911, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=8', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:34:08'),
(912, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:34:23'),
(913, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:39:41'),
(914, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:39:43'),
(915, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:39:50'),
(916, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:39:52'),
(917, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=8', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:40:37'),
(918, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:40:43'),
(919, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:40:58'),
(920, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/parents', 'admin', 1, NULL, '2026-06-15 16:41:00'),
(921, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/parents', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:41:04'),
(922, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:41:06'),
(923, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=8', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:41:21'),
(924, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/view_bill', 'admin', 1, NULL, '2026-06-15 16:41:43'),
(925, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/view_bill?id=8', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:41:47'),
(926, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:41:50'),
(927, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:42:16'),
(928, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/students', 'admin', 1, NULL, '2026-06-15 16:42:18'),
(929, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/students', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-15 16:42:19'),
(930, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-21 10:30:56'),
(931, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-22 02:48:46'),
(932, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-27 14:52:09'),
(933, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-27 14:52:57'),
(934, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-27 14:53:51'),
(935, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-27 14:53:56'),
(936, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=admin', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-27 14:53:56'),
(937, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-27 15:11:59'),
(938, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'admin', 1, NULL, '2026-06-28 00:40:07'),
(939, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'admin', 1, NULL, '2026-06-28 00:40:07'),
(940, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/fees', 'admin', 1, NULL, '2026-06-28 00:40:26'),
(941, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/fees', '/abss/admin/dashboard', 'admin', 1, NULL, '2026-06-28 00:40:29'),
(942, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/logout', 'admin', 1, NULL, '2026-06-28 00:40:32'),
(943, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/dashboard', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-28 00:40:32'),
(944, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:40:41'),
(945, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:41:04'),
(946, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:44:13'),
(947, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:44:15'),
(948, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:44:28'),
(949, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app_home', '/abss/index', 'guest', NULL, NULL, '2026-06-28 00:44:33'),
(950, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:45:05'),
(951, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 00:45:05'),
(952, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:45:05'),
(953, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:50:32'),
(954, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:50:34'),
(955, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:50:48'),
(956, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:52:19'),
(957, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:52:21'),
(958, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 00:52:21'),
(959, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:52:44'),
(960, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:53:52'),
(961, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:54:46'),
(962, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:54:58'),
(963, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 00:54:59'),
(964, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:54:59'),
(965, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:55:05'),
(966, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 00:55:06'),
(967, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:55:07'),
(968, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:58:33'),
(969, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 00:58:55'),
(970, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 00:58:56'),
(971, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 00:58:56'),
(972, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:01:47'),
(973, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:01:47'),
(974, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:01:47'),
(975, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:01:58'),
(976, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app_home', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:02:03'),
(977, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:02:08'),
(978, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app_home', '/abss/admin/login', 'guest', NULL, NULL, '2026-06-28 01:02:09'),
(979, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:02:15'),
(980, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:03:46'),
(981, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:04:29'),
(982, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:04:30'),
(983, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:09:37'),
(984, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:12:36'),
(985, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:14:17'),
(986, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:14:18'),
(987, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:14:18'),
(988, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:16:43'),
(989, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:18:24'),
(990, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:18:24'),
(991, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:20:55'),
(992, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:20:55'),
(993, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:20:55'),
(994, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:20:56'),
(995, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:20:56'),
(996, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:20:56'),
(997, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:21:25'),
(998, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:21:25'),
(999, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:21:25'),
(1000, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:21:25'),
(1001, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:21:25'),
(1002, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:22:48'),
(1003, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:22:49'),
(1004, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:22:49'),
(1005, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:22:49'),
(1006, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:22:49'),
(1007, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:24:48'),
(1008, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:24:48'),
(1009, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:24:48'),
(1010, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:24:48'),
(1011, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:24:49'),
(1012, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:24:58'),
(1013, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:24:59'),
(1014, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:24:59'),
(1015, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:24:59'),
(1016, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:24:59'),
(1017, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app/', 'guest', NULL, NULL, '2026-06-28 01:25:04'),
(1018, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:25:04'),
(1019, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:25:04'),
(1020, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:25:04'),
(1021, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:25:04'),
(1022, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/app/dfgdfg', 'guest', NULL, NULL, '2026-06-28 01:26:04'),
(1023, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:04'),
(1024, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:04'),
(1025, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/dfgdfg', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:07'),
(1026, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:09'),
(1027, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/indexsdf', 'guest', NULL, NULL, '2026-06-28 01:26:13'),
(1028, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/indexsdf', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:14'),
(1029, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:26:15'),
(1030, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:16'),
(1031, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:16'),
(1032, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:17'),
(1033, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:18'),
(1034, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:18'),
(1035, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:26:18'),
(1036, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:18'),
(1037, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:19'),
(1038, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:21'),
(1039, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:21'),
(1040, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:21'),
(1041, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:26:25'),
(1042, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:25'),
(1043, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:25'),
(1044, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:27'),
(1045, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:26:27'),
(1046, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:26:27'),
(1047, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:27:24'),
(1048, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:27:32'),
(1049, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:27:33'),
(1050, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:27:33'),
(1051, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:27:36'),
(1052, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:27:37'),
(1053, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js.php', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:27:37'),
(1054, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:27:39'),
(1055, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app_home', 'guest', NULL, NULL, '2026-06-28 01:27:39'),
(1056, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:27:39'),
(1057, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/index', '/abss/index', 'guest', NULL, NULL, '2026-06-28 01:32:00'),
(1058, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/index', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:32:01'),
(1059, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/app/sw.js', '/abss/app/index', 'guest', NULL, NULL, '2026-06-28 01:32:03'),
(1060, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:34:36'),
(1061, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-28 01:34:40'),
(1062, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-06-29 09:12:34'),
(1063, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-07-02 08:59:19'),
(1064, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/home', 'guest', NULL, NULL, '2026-07-02 08:59:27'),
(1065, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/home', '/abss/index', 'guest', NULL, NULL, '2026-07-02 08:59:35'),
(1066, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/home', '/abss/admin/login', 'guest', NULL, NULL, '2026-07-02 08:59:37'),
(1067, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/abss/admin/login?role=parent', '/abss/app/sw.js.php', 'guest', NULL, NULL, '2026-07-02 08:59:37'),
(1068, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', '/abss/', 'guest', NULL, NULL, '2026-07-02 08:59:46');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(20) DEFAULT NULL,
  `student_photo` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `target_school` varchar(100) DEFAULT NULL,
  `class_admitted` varchar(50) DEFAULT NULL,
  `scholar_mode` varchar(50) DEFAULT 'Day Scholar',
  `monthly_discount` decimal(10,2) DEFAULT 0.00,
  `base_fee` decimal(10,2) DEFAULT 0.00,
  `last_billed_date` date DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('active','inactive','graduated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `prev_school` varchar(255) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_email` varchar(150) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_relationship` varchar(50) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `has_allergies` tinyint(1) DEFAULT 0,
  `allergies_detail` text DEFAULT NULL,
  `has_medical_condition` tinyint(1) DEFAULT 0,
  `medical_condition_detail` text DEFAULT NULL,
  `physician_name` varchar(150) DEFAULT NULL,
  `physician_phone` varchar(20) DEFAULT NULL,
  `insurance_provider` varchar(150) DEFAULT NULL,
  `insurance_policy` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `reg_no`, `student_photo`, `parent_id`, `name`, `parent_name`, `phone`, `target_school`, `class_admitted`, `scholar_mode`, `monthly_discount`, `base_fee`, `last_billed_date`, `admission_date`, `status`, `created_at`, `photo`, `dob`, `gender`, `home_address`, `city`, `state`, `zip_code`, `prev_school`, `guardian_relationship`, `guardian_email`, `guardian_address`, `emergency_contact_name`, `emergency_relationship`, `emergency_phone`, `has_allergies`, `allergies_detail`, `has_medical_condition`, `medical_condition_detail`, `physician_name`, `physician_phone`, `insurance_provider`, `insurance_policy`) VALUES
(1, NULL, 'uploads/students/pic_1780115919_9303.jpeg', 1, 'SONU SAGAR', 'sdfsdf', '+918581040110', 'Navodaya Vidyalaya', 'Class 6', 'Day Scholar', 0.00, 0.00, '2026-07-22', '2026-04-22', 'active', '2026-04-22 19:33:20', '', '0000-00-00', '', '', '', '', '', '', '', '', '', '', '', '', 0, '', 0, '', '', '', '', ''),
(2, 'ABSS-2026-0002', '', 1, 'SONU', 'sdfsdf', '08581040110', '', 'Class 5 (Preparation)', 'Hostler', 1500.00, 5000.00, '2026-12-30', '2026-05-30', 'active', '2026-05-30 11:54:19', '', '0000-00-00', '', 'Lok Kala Bhawan', 'Imamganj', 'Please Select', '824206', '', '', 'sonusagarpoly@gmail.com', '', '', '', '', 0, '', 0, '', '', '', '', ''),
(3, 'ABSS-2026-0003', 'uploads/students/3_pic.png', 2, 'Ram', 'Shyam', '7676763454', 'Netarhat Residential School', 'Class 6', 'Day Scholar', 0.01, 3000.00, '2026-10-31', '2026-06-11', 'active', '2026-06-13 14:55:57', 'uploads/students/3_admission.png', '0000-00-00', 'Male', 'Lok Kala Bhawan', 'Imamganj', 'Bihar', '824206', 'dfdf', 'Father', 'sonusagarpolysd@gmail.com', '', '', '', '', 0, '', 0, '', '', '', '', ''),
(4, 'ABSS-2026-0004', 'uploads/students/4_pic.jpg', 3, 'Gunjan Kumar', 'Laljeet Yadav', '8581040111', '', 'Class 5 (Preparation)', 'Hostler', 0.00, 5000.00, '2026-06-30', '2026-06-13', 'active', '2026-06-13 15:20:18', '', '0000-00-00', '', 'S/O : Antoni Prasad, Raniganj, Gaya, Bihar, 824210', 'Gaya', 'Bihar', '824210', '', '', 'dfghdfg@gmail.com', '', '', '', '', 0, '', 0, '', '', '', '', ''),
(5, 'ABSS-2026-0005', 'uploads/students/5_pic.png', 1, 'Gola', 'Shyamaaaaa', '08581040110', '', 'Class 5 (Preparation)', 'Day Scholar', 200.00, 3000.00, '2026-06-30', '2026-06-15', 'active', '2026-06-15 16:32:34', '', '0000-00-00', '', 'Lok Kala Bhawan', 'Imamganj', 'Bihar', '824206', '', '', 'sonusagarpoly@gmail.com', '', '', '', '', 0, '', 0, '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `student_addons`
--

CREATE TABLE `student_addons` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `addon_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_addons`
--

INSERT INTO `student_addons` (`id`, `student_id`, `addon_name`, `amount`, `created_at`) VALUES
(1, 1, 'sdfsdf', 12.00, '2026-05-30 06:24:16'),
(2, 2, 'Milk', 500.00, '2026-06-02 08:54:10'),
(3, 2, 'Computer Class', 200.00, '2026-06-02 08:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `student_id`, `document_type_id`, `file_path`, `status`, `uploaded_at`) VALUES
(1, 1, 1, 'uploads/documents/doc_1_1_1780117179.png', 'pending', '2026-05-30 04:59:39'),
(2, 1, 2, 'uploads/documents/doc_1_2_1780117186.jpeg', 'pending', '2026-05-30 04:59:46'),
(3, 1, 3, 'uploads/documents/doc_1_3_1780117189.png', 'approved', '2026-05-30 04:59:49'),
(4, 2, 1, 'uploads/documents/doc_2_1_1780142105.jpg', 'pending', '2026-05-30 11:55:05'),
(5, 2, 2, 'uploads/documents/doc_2_2_1780142108.jpeg', 'pending', '2026-05-30 11:55:08'),
(6, 2, 3, 'uploads/documents/doc_2_3_1780142113.jpg', 'rejected', '2026-05-30 11:55:13');

-- --------------------------------------------------------

--
-- Table structure for table `student_expenses`
--

CREATE TABLE `student_expenses` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('unbilled','billed') DEFAULT 'unbilled',
  `billed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_expenses`
--

INSERT INTO `student_expenses` (`id`, `student_id`, `item_name`, `amount`, `expense_date`, `status`, `billed_at`, `created_at`) VALUES
(1, 1, '1 Packet Pem', 25.00, '2026-05-30', 'billed', '2026-05-30 06:40:01', '2026-05-30 06:21:43'),
(2, 1, 'zxczxc', 233.00, '2026-05-30', 'billed', '2026-05-30 07:08:03', '2026-05-30 06:44:41'),
(3, 1, 'pen', 45.00, '2026-05-30', 'billed', '2026-05-30 07:12:05', '2026-05-30 07:12:01'),
(4, 2, '1 Packet Pen', 25.00, '2026-06-02', 'billed', '2026-06-02 08:57:14', '2026-06-02 08:56:45'),
(5, 2, 'Book', 500.00, '2026-06-02', 'billed', '2026-06-02 08:58:57', '2026-06-02 08:58:38'),
(6, 2, '123', 34.00, '2026-06-02', 'billed', '2026-06-02 09:03:13', '2026-06-02 09:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `parent_id`, `student_id`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 1, 1, 'Kya Obno Kahana kha raha hai sahi se', 'Explain me', 'open', '2026-05-28 18:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `email`, `phone`, `department`, `designation`, `join_date`, `salary`, `photo`, `status`, `created_at`) VALUES
(1, 'SONU SAGAR', 'sonusagarpoly@gmail.com', '08581040110', '', '', '0000-00-00', 30000.00, 'uploads/teachers/teacher_1780733074_2815.png', 'active', '2026-06-06 08:02:22');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_expenses`
--

CREATE TABLE `teacher_expenses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `expense_type` varchar(150) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_expenses`
--

INSERT INTO `teacher_expenses` (`id`, `teacher_id`, `invoice_id`, `expense_type`, `amount`, `expense_date`, `description`, `status`, `created_at`) VALUES
(2, 1, 2, 'Dawa', 5000.00, '2026-06-06', 'rfdgdf', 'approved', '2026-06-06 08:08:36'),
(3, 1, 2, 'dfgdfg', 3434.00, '2026-06-06', 'dfdsfgdf', 'approved', '2026-06-06 08:25:33'),
(4, 1, 2, 'dfgdfg', 120.00, '2026-06-06', 'fdgdfg', 'approved', '2026-06-06 08:27:40');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_invoices`
--

CREATE TABLE `teacher_invoices` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `month_for` varchar(20) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_invoices`
--

INSERT INTO `teacher_invoices` (`id`, `teacher_id`, `invoice_number`, `amount`, `month_for`, `issue_date`, `due_date`, `status`, `created_at`) VALUES
(2, 1, 'TINV-20260606-2175', 21446.00, NULL, '2026-06-06', '2026-06-06', 'unpaid', '2026-06-06 08:11:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$jt2xosZyts9FYQmqVpWMGePIUsad0qHV070E4S9BwVqrM10Q5VmaC', 'admin', '2026-02-13 17:16:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievers`
--
ALTER TABLE `achievers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fees_generated`
--
ALTER TABLE `fees_generated`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `site_visitors`
--
ALTER TABLE `site_visitors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_parent` (`parent_id`);

--
-- Indexes for table `student_addons`
--
ALTER TABLE `student_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `document_type_id` (`document_type_id`);

--
-- Indexes for table `student_expenses`
--
ALTER TABLE `student_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `teacher_expenses`
--
ALTER TABLE `teacher_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_invoices`
--
ALTER TABLE `teacher_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievers`
--
ALTER TABLE `achievers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fees_generated`
--
ALTER TABLE `fees_generated`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `site_visitors`
--
ALTER TABLE `site_visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1069;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_addons`
--
ALTER TABLE `student_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_expenses`
--
ALTER TABLE `student_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_expenses`
--
ALTER TABLE `teacher_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_invoices`
--
ALTER TABLE `teacher_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_addons`
--
ALTER TABLE `student_addons`
  ADD CONSTRAINT `student_addons_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_expenses`
--
ALTER TABLE `student_expenses`
  ADD CONSTRAINT `student_expenses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_expenses`
--
ALTER TABLE `teacher_expenses`
  ADD CONSTRAINT `teacher_expenses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_invoices`
--
ALTER TABLE `teacher_invoices`
  ADD CONSTRAINT `teacher_invoices_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `youtube_videos`
--
CREATE TABLE IF NOT EXISTS `youtube_videos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `video_url` VARCHAR(500) NOT NULL,
  `youtube_id` VARCHAR(100) NOT NULL,
  `thumbnail_url` VARCHAR(500) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT 'Campus Life',
  `description` TEXT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_status_category` (`status`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `fcm_tokens`
--
CREATE TABLE IF NOT EXISTS `fcm_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `device_type` VARCHAR(50) DEFAULT 'android',
  `app_version` VARCHAR(20) DEFAULT '1.2.0',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `notification_history`
--
CREATE TABLE IF NOT EXISTS `notification_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `image` VARCHAR(500) DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `category` VARCHAR(50) DEFAULT 'General',
  `target_audience` VARCHAR(100) DEFAULT 'All Users',
  `sent_count` INT DEFAULT 0,
  `failed_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

