<?php
require_once 'includes/auth.php';

// Fetch stats
$today = date('Y-m-d');
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$inquiry_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'")->fetch_assoc()['count'];
$attendance_today = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'present'")->fetch_assoc()['count'];
$fees_total = $conn->query("SELECT SUM(amount) as total FROM fee_payments WHERE month_for = '" . date('F') . "'")->fetch_assoc()['total'];
$results_latest = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ABSS Management Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .dash-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 50px; }
        .academic-badge { background: #fff; padding: 12px 25px; border-radius: 100px; border: 1px solid rgba(13, 71, 161, 0.1); color: var(--portal-blue); font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 50px; }
        .stat-card { background: #fff; padding: 35px; border-radius: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 25px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.04); }
        .stat-icon { width: 70px; height: 70px; border-radius: 22px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
        .icon-blue { background: #eef2ff; color: var(--portal-blue); }
        .icon-orange { background: #fff7ed; color: #f97316; }
        .icon-green { background: #f0fdf4; color: #22c55e; }
        .stat-info h3 { font-size: 2.2rem; margin: 0; color: var(--portal-blue); font-weight: 800; }
        .stat-info p { margin: 0; color: #5c6bc0; font-size: 0.95rem; font-weight: 600; }
        .dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .card { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .quick-action-btn { display: flex; align-items: center; justify-content: space-between; padding: 20px 25px; background: #f8faff; border-radius: 20px; color: var(--portal-blue); text-decoration: none; font-weight: 700; margin-bottom: 15px; transition: 0.3s; }
        .quick-action-btn:hover { background: var(--portal-blue); color: #fff; transform: scale(1.02); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="dash-header">
            <div class="welcome-text">
                <h1>Hi, Administrator</h1>
                <p>Welcome to your school's command center.</p>
            </div>
            <div class="academic-badge">
                <i class="fas fa-calendar-alt"></i> Session 2026-27
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($attendance_today); ?></h3>
                    <p>Present Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <h3>₹ <?php echo number_format($fees_total ?: 0); ?></h3>
                    <p><?php echo date('F'); ?> Collections</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fas fa-paper-plane"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($inquiry_count); ?></h3>
                    <p>New Inquiries</p>
                </div>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="card">
                <div class="card-header">
                    <h2>Academic Status Overview</h2>
                    <span style="background:#eef2ff; color:var(--portal-blue); padding:8px 15px; border-radius:10px; font-weight:800; font-size:0.75rem;">UP TO DATE</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="padding: 25px; background: #f8faff; border-radius: 25px; border: 2px solid #eef2ff;">
                        <h4 style="margin-bottom: 10px; font-size: 0.8rem; text-transform: uppercase;">Student Strength</h4>
                        <div style="color: var(--portal-dark); font-weight: 800; font-size: 1.6rem;">
                            <?php echo $student_count; ?> <small style="font-size: 0.8rem; opacity: 0.6;">Enrolled</small>
                        </div>
                    </div>
                    <div style="padding: 25px; background: #f8faff; border-radius: 25px; border: 2px solid #eef2ff;">
                        <h4 style="margin-bottom: 10px; font-size: 0.8rem; text-transform: uppercase;">Exam Modules</h4>
                        <div style="color: var(--portal-dark); font-weight: 800; font-size: 1.6rem;">
                            <?php echo $results_latest; ?> <small style="font-size: 0.8rem; opacity: 0.6;">Active</small>
                        </div>
                    </div>
                </div>
                <p>
                    All systems are operational. Your public website is currently serving dynamic content from the centralized database. 
                    Manage notices, student registrations, and gallery media through the sidebar modules on the left.
                </p>
            </div>

            <div class="card" style="padding: 40px 30px;">
                <div class="card-header">
                    <h2>Quick Actions</h2>
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
            </div>
        </div>
    </main>
</body>
</html>
