<?php
// admin/dashboard.php - Frosted Glass Admin Dashboard
require_once 'includes/auth.php';

// Trigger automated billing engine for any due monthly invoices
require_once 'includes/billing_engine.php';

// Fetch core stats
$today = date('Y-m-d');
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$inquiry_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'")->fetch_assoc()['count'];
$attendance_today = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'present'")->fetch_assoc()['count'];

// Current month collection
$fees_total = $conn->query("SELECT SUM(amount) as total FROM fee_payments WHERE MONTH(payment_date) = MONTH(NOW()) AND YEAR(payment_date) = YEAR(NOW())")->fetch_assoc()['total'];

// Total lifetime fee collection
$fees_lifetime = $conn->query("SELECT SUM(amount) as total FROM fee_payments")->fetch_assoc()['total'];
$results_latest = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'];

// 1. Unpaid Bills Metrics (Total amount & Count)
$unpaid_query = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM fees_generated WHERE status = 'unpaid'")->fetch_assoc();
$unpaid_total = (float)$unpaid_query['total'];
$unpaid_count = (int)$unpaid_query['count'];

// 2. Previous Month Metrics (Collection & Billed)
$prev_month_name = date('F Y', strtotime('-1 month'));
$prev_month_num = (int)date('m', strtotime('-1 month'));
$prev_year_num = (int)date('Y', strtotime('-1 month'));

$prev_month_collection_res = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM fee_payments 
    WHERE MONTH(payment_date) = $prev_month_num AND YEAR(payment_date) = $prev_year_num
");
$prev_month_collection = (float)($prev_month_collection_res ? $prev_month_collection_res->fetch_assoc()['total'] : 0);

$prev_month_billed_res = $conn->query("
    SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
    FROM fees_generated 
    WHERE month_for LIKE '%$prev_month_name%'
");
$prev_month_billed_data = $prev_month_billed_res ? $prev_month_billed_res->fetch_assoc() : ['count' => 0, 'total' => 0];
$prev_month_billed_total = (float)$prev_month_billed_data['total'];
$prev_month_billed_count = (int)$prev_month_billed_data['count'];

// 3. Security Deposit, Registration & Admission Fee Collections
$security_res = $conn->query("SELECT COALESCE(SUM(security_amount), 0) as total FROM students WHERE status = 'active'");
$security_total = (float)($security_res ? $security_res->fetch_assoc()['total'] : 0);

$reg_fee_res = $conn->query("SELECT COALESCE(SUM(registration_fee), 0) as total FROM students WHERE status = 'active'");
$registration_fee_total = (float)($reg_fee_res ? $reg_fee_res->fetch_assoc()['total'] : 0);

$adm_fee_res = $conn->query("SELECT COALESCE(SUM(admission_fee), 0) as total FROM students WHERE status = 'active'");
$admission_fee_total = (float)($adm_fee_res ? $adm_fee_res->fetch_assoc()['total'] : 0);

// 4. Calculate total dynamic late fine across all unpaid bills
$settings = function_exists('getAllSettings') ? getAllSettings() : [];
$total_fine_amount = function_exists('get_all_unpaid_total_fine') ? get_all_unpaid_total_fine($conn, $settings) : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ABSS Management Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .dash-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            flex-wrap: wrap; 
            gap: 15px; 
        }
        .academic-badge { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(12px);
            padding: 10px 22px; 
            border-radius: 50px; 
            border: 1px solid var(--glass-border); 
            color: var(--portal-blue); 
            font-weight: 800; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: var(--glass-shadow); 
            font-size: 0.9rem;
        }

        .dashboard-row { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 25px; 
        }
        @media (max-width: 1024px) {
            .dashboard-row { grid-template-columns: 1fr; }
        }

        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            flex-wrap: wrap;
            gap: 10px;
        }

        .quick-action-btn { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 15px 20px; 
            background: rgba(248, 250, 252, 0.8); 
            border-radius: var(--radius-md); 
            color: var(--portal-dark); 
            text-decoration: none; 
            font-weight: 700; 
            margin-bottom: 12px; 
            transition: all 0.25s ease; 
            border: 1px solid #e2e8f0;
            font-size: 0.92rem;
        }
        .quick-action-btn:hover { 
            background: var(--portal-blue); 
            color: #ffffff; 
            transform: translateX(4px); 
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
            border-color: var(--portal-blue);
        }
        .quick-action-btn i { font-size: 0.9rem; opacity: 0.7; }

        .stats-grid-3x2 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        @media (max-width: 1024px) {
            .stats-grid-3x2 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .stats-grid-3x2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="dash-header">
            <div class="welcome-text">
                <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Hi, Administrator 👋</h1>
                <p style="margin:0;">Welcome to your school's command center.</p>
            </div>
            <div class="academic-badge">
                <i class="fas fa-calendar-alt"></i> Academic Session 2026-27
            </div>
        </header>

        <!-- Glass Stat Cards Grid: 1 Row 3 Columns -->
        <div class="stats-grid-3x2">
            <!-- Card 1: Unpaid Bills Box (Click to open Student Dues Statement) -->
            <a href="student_dues.php" class="stat-card" style="text-decoration:none; color:inherit; border-left: 4px solid #dc2626;" title="Click to view all student dues & arrears statement">
                <div class="stat-icon icon-red"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-info">
                    <h3 style="color:#dc2626;">₹ <?php echo number_format($unpaid_total); ?></h3>
                    <p style="font-weight:700;">Total Unpaid Dues</p>
                    <small style="color:#dc2626; font-weight:800; font-size:0.75rem;"><i class="fas fa-file-invoice-dollar"></i> <?php echo number_format($unpaid_count); ?> Invoices Due (Open Dues →)</small>
                </div>
            </a>

            <!-- Card 2: Security Deposit (Caution Money - Non Receipt) -->
            <div class="stat-card" style="border-left: 4px solid #7c3aed;">
                <div class="stat-icon icon-purple"><i class="fas fa-shield-alt"></i></div>
                <div class="stat-info">
                    <h3 style="color:#7c3aed;">₹ <?php echo number_format($security_total); ?></h3>
                    <p style="font-weight:700;">Total Security Deposit</p>
                    <small style="color:#64748b; font-weight:700; font-size:0.75rem;">Caution Money (Non-Receipt)</small>
                </div>
            </div>

            <!-- Card 3: Total Registration Fee -->
            <div class="stat-card" style="border-left: 4px solid #2563eb;">
                <div class="stat-icon icon-blue"><i class="fas fa-id-card"></i></div>
                <div class="stat-info">
                    <h3 style="color:#2563eb;">₹ <?php echo number_format($registration_fee_total); ?></h3>
                    <p style="font-weight:700;">Total Registration Fee</p>
                    <small style="color:#64748b; font-weight:700; font-size:0.75rem;">Candidate Reg Collections</small>
                </div>
            </div>

            <!-- Card 4: Total Admission Fee -->
            <div class="stat-card" style="border-left: 4px solid #16a34a;">
                <div class="stat-icon icon-green"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-info">
                    <h3 style="color:#16a34a;">₹ <?php echo number_format($admission_fee_total); ?></h3>
                    <p style="font-weight:700;">Total Admission Fee</p>
                    <small style="color:#64748b; font-weight:700; font-size:0.75rem;">One-time Admission Charges</small>
                </div>
            </div>

            <!-- Card 5: Current Month Collections -->
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <h3>₹ <?php echo number_format($fees_total ?: 0); ?></h3>
                    <p><?php echo date('F'); ?> Collections</p>
                    <small style="color:#166534; font-weight:700; font-size:0.75rem;">Current Month Total</small>
                </div>
            </div>

            <!-- Card 6: Total Late Fine Amount (विलंब शुल्क) -->
            <div class="stat-card" style="border-left: 4px solid #ea580c;">
                <div class="stat-icon" style="background:#ffedd5; color:#ea580c;"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <h3 style="color:#ea580c;">₹ <?php echo number_format($total_fine_amount, 2); ?></h3>
                    <p style="font-weight:700;">Total Late Fine</p>
                    <small style="color:<?php echo is_fine_system_enabled($settings) ? '#ea580c' : '#64748b'; ?>; font-weight:700; font-size:0.75rem;">
                        <?php echo is_fine_system_enabled($settings) ? '₹5/day after 5th of month' : 'Fine System Disabled'; ?>
                    </small>
                </div>
            </div>

            <!-- Card 7: Previous Month Data Box -->
            <div class="stat-card">
                <div class="stat-icon icon-teal"><i class="fas fa-history"></i></div>
                <div class="stat-info">
                    <h3>₹ <?php echo number_format($prev_month_collection); ?></h3>
                    <p style="font-weight:700;"><?php echo $prev_month_name; ?> Collected</p>
                    <small style="color:#0284c7; font-weight:700; font-size:0.75rem;">Billed: ₹ <?php echo number_format($prev_month_billed_total); ?></small>
                </div>
            </div>

            <!-- Card 7: Lifetime Revenue -->
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <h3>₹ <?php echo number_format($fees_lifetime ?: 0); ?></h3>
                    <p>Lifetime Tuition Revenue</p>
                    <small style="color:#7c3aed; font-weight:700; font-size:0.75rem;">All Time Fee Payments</small>
                </div>
            </div>

            <!-- Card 8: Present Today -->
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($attendance_today); ?></h3>
                    <p>Present Today</p>
                    <small style="color:#2563eb; font-weight:700; font-size:0.75rem;">Attendance Marked</small>
                </div>
            </div>

            <!-- Card 9: New Inquiries -->
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fas fa-paper-plane"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($inquiry_count); ?></h3>
                    <p>New Inquiries</p>
                    <small style="color:#d97706; font-weight:700; font-size:0.75rem;">Pending Follow-up</small>
                </div>
            </div>
        </div>

        <!-- 2 Column Dashboard Grid -->
        <div class="dashboard-row">
            <div class="portal-card">
                <div class="card-header">
                    <h2 style="font-size: 1.25rem; margin:0;"><i class="fas fa-chart-pie" style="color:var(--portal-blue); margin-right:8px;"></i> Academic Status Overview</h2>
                    <span class="badge badge-purple" style="padding: 6px 14px;"><i class="fas fa-check-circle"></i> UP TO DATE</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 25px;">
                    <div style="padding: 20px; background: rgba(248, 250, 252, 0.8); border-radius: var(--radius-md); border: 1px solid #e2e8f0;">
                        <h4 style="margin-bottom: 8px; font-size: 0.78rem; text-transform: uppercase; color:#64748b;">Student Strength</h4>
                        <div style="color: var(--portal-dark); font-weight: 800; font-size: 1.6rem;">
                            <?php echo number_format($student_count); ?> <small style="font-size: 0.8rem; opacity: 0.6;">Enrolled</small>
                        </div>
                    </div>

                    <div style="padding: 20px; background: rgba(248, 250, 252, 0.8); border-radius: var(--radius-md); border: 1px solid #e2e8f0;">
                        <h4 style="margin-bottom: 8px; font-size: 0.78rem; text-transform: uppercase; color:#64748b;">Exam Scorecards</h4>
                        <div style="color: var(--portal-dark); font-weight: 800; font-size: 1.6rem;">
                            <?php echo number_format($results_latest); ?> <small style="font-size: 0.8rem; opacity: 0.6;">Active</small>
                        </div>
                    </div>
                </div>

                <p style="line-height: 1.6; font-size: 0.92rem;">
                    All school management systems are operational. Your public portal serves dynamic information directly from the database. 
                    Manage announcements, student admissions, fee ledgers, and gallery items through the navigation menu on the left.
                </p>
            </div>

            <div class="portal-card">
                <div class="card-header">
                    <h2 style="font-size: 1.25rem; margin:0;"><i class="fas fa-bolt" style="color:var(--portal-blue); margin-right:8px;"></i> Quick Actions</h2>
                </div>

                <a href="attendance.php" class="quick-action-btn">
                    Mark Attendance <i class="fas fa-chevron-right"></i>
                </a>
                <a href="fees.php" class="quick-action-btn">
                    Collect Fees <i class="fas fa-chevron-right"></i>
                </a>
                <a href="results.php" class="quick-action-btn">
                    Post Exam Result <i class="fas fa-chevron-right"></i>
                </a>
                <a href="notices.php" class="quick-action-btn">
                    Publish Notice <i class="fas fa-chevron-right"></i>
                </a>
                <a href="admissions.php" class="quick-action-btn">
                    View Admissions <i class="fas fa-chevron-right"></i>
                </a>
                <a href="schools.php" class="quick-action-btn">
                    Manage Schools <i class="fas fa-chevron-right"></i>
                </a>
                <a href="achievers.php" class="quick-action-btn">
                    Manage Achievers <i class="fas fa-chevron-right"></i>
                </a>
                <a href="gallery.php" class="quick-action-btn">
                    Update Gallery <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </main>
</body>
</html>
