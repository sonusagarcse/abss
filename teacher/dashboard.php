<?php
require_once 'includes/auth.php';

$teacher_id = (int)$_SESSION['teacher_id'];

// Fetch teacher details
$t_stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$t_stmt->bind_param("i", $teacher_id);
$t_stmt->execute();
$teacher = $t_stmt->get_result()->fetch_assoc();

$teacher_name = $teacher['name'] ?? $_SESSION['teacher_name'] ?? 'Faculty Member';
$department = $teacher['department'] ?? 'General Academics';
$designation = $teacher['designation'] ?? 'Senior Educator';
$salary = $teacher['salary'] ?? 0.00;

// Fetch statistics
$total_students_res = $conn->query("SELECT COUNT(id) as cnt FROM students WHERE status = 'active'");
$total_students = $total_students_res ? $total_students_res->fetch_assoc()['cnt'] : 0;

$today_date = date('Y-m-d');
$att_res = $conn->query("SELECT COUNT(id) as cnt FROM attendance WHERE date = '$today_date'");
$today_att_count = $att_res ? $att_res->fetch_assoc()['cnt'] : 0;

$claims_res = $conn->query("SELECT COUNT(id) as cnt FROM teacher_expenses WHERE teacher_id = $teacher_id AND status = 'pending'");
$pending_claims = $claims_res ? $claims_res->fetch_assoc()['cnt'] : 0;

// Time of day greeting
$hour = date('H');
$greeting = "Good Day";
if ($hour < 12) {
    $greeting = "Good Morning";
} else if ($hour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

// Fetch notices
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | ABSS Teacher Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 60%, #6366f1 100%);
            color: #ffffff;
            padding: 35px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 15px 35px rgba(124, 58, 237, 0.25);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.12);
            filter: blur(50px);
            border-radius: 50%;
        }
        .welcome-banner h1 { font-size: 1.85rem; font-weight: 900; margin-bottom: 6px; color: #ffffff; }
        .welcome-banner p { opacity: 0.92; font-size: 0.95rem; color: #e0e7ff; margin: 0; font-weight: 500; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }
        .action-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            text-decoration: none;
            color: #1e1b4b;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95rem;
        }
        .action-card:hover {
            background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark));
            color: #ffffff;
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(124, 58, 237, 0.3);
        }
        .action-card i { font-size: 1.3rem; }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 25px;
        }
        @media (max-width: 992px) { .dashboard-layout { grid-template-columns: 1fr; } }

        .notice-item {
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #ede9fe;
            margin-bottom: 12px;
            transition: background 0.2s;
        }
        .notice-item:hover { background: #f1f5f9; }
        .notice-title { font-weight: 800; font-size: 0.92rem; color: #1e1b4b; }
        .notice-date { font-size: 0.75rem; color: #64748b; font-weight: 700; margin-top: 4px; display: flex; align-items: center; gap: 5px; }

        .profile-avatar-box {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--teacher-purple);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.2);
            flex-shrink: 0;
        }
        .profile-avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 900;
            border: 3px solid #ffffff;
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.2);
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Welcome Header Banner -->
        <div class="welcome-banner">
            <div style="position: relative; z-index: 2;">
                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); padding: 4px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: #fbbf24;">
                    <i class="far fa-calendar-alt"></i> <?= date('l, d F Y') ?>
                </div>
                <h1><?= $greeting ?>, <?= htmlspecialchars($teacher_name) ?>! 👋</h1>
                <p><i class="fas fa-graduation-cap" style="color: #a78bfa;"></i> <?= htmlspecialchars($designation) ?> &bull; Department of <?= htmlspecialchars($department) ?></p>
            </div>
            <div style="position: relative; z-index: 2;">
                <a href="attendance.php" style="background: #ffffff; color: var(--teacher-dark); padding: 12px 24px; border-radius: 50px; font-weight: 900; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); transition: 0.25s;">
                    <i class="fas fa-calendar-check" style="color: var(--teacher-purple);"></i> Take Today's Attendance
                </a>
            </div>
        </div>

        <!-- KPI Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="stat-lbl">Active Students</div>
                    <div class="stat-val"><?= number_format($total_students) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="stat-lbl">Today's Attendance</div>
                    <div class="stat-val"><?= number_format($today_att_count) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-amber"><i class="fas fa-receipt"></i></div>
                <div>
                    <div class="stat-lbl">Pending Claims</div>
                    <div class="stat-val"><?= number_format($pending_claims) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="stat-lbl">Monthly Base Salary</div>
                    <div class="stat-val">₹<?= number_format($salary, 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Action Shortcuts -->
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--teacher-dark); margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-bolt" style="color: #f59e0b;"></i> Quick Shortcuts
        </h2>
        <div class="quick-actions">
            <a href="attendance.php" class="action-card">
                <i class="fas fa-calendar-alt" style="color: #7c3aed;"></i> Mark Attendance
            </a>
            <a href="results.php" class="action-card">
                <i class="fas fa-award" style="color: #059669;"></i> Upload Test Results
            </a>
            <a href="expenses.php" class="action-card">
                <i class="fas fa-plus-circle" style="color: #d97706;"></i> File Expense Claim
            </a>
            <a href="invoices.php" class="action-card">
                <i class="fas fa-file-invoice-dollar" style="color: #2563eb;"></i> Salary Invoices
            </a>
        </div>

        <!-- Main Dashboard Content Grid -->
        <div class="dashboard-layout">
            <!-- Faculty Information Card -->
            <div class="card-panel">
                <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--teacher-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-id-card" style="color: var(--teacher-purple);"></i> Faculty Profile Summary
                </h2>
                
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 22px; flex-wrap: wrap;">
                    <?php 
                    $t_photo = !empty($teacher['photo']) ? '../' . htmlspecialchars($teacher['photo']) : '';
                    $t_initials = strtoupper(substr($teacher_name, 0, 2));
                    if ($t_photo && file_exists(__DIR__ . '/../' . $teacher['photo'])): ?>
                        <img src="<?= $t_photo ?>" alt="Photo" class="profile-avatar-box">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder"><?= $t_initials ?></div>
                    <?php endif; ?>

                    <div>
                        <h3 style="font-size: 1.25rem; color: #1e1b4b; font-weight: 900; margin: 0 0 4px 0;"><?= htmlspecialchars($teacher_name) ?></h3>
                        <div style="color: #64748b; font-size: 0.88rem; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-envelope" style="color: var(--teacher-purple);"></i> <?= htmlspecialchars($teacher['email'] ?? 'Not specified') ?>
                        </div>
                        <div style="color: #64748b; font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-phone" style="color: var(--teacher-purple);"></i> <?= htmlspecialchars($teacher['phone'] ?? 'Not specified') ?>
                        </div>
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 16px; padding: 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 0.9rem; border: 1px solid #ede9fe;">
                    <div><span style="color: #64748b; font-weight: 700;">Department:</span> <strong style="color: #1e1b4b; display: block; margin-top: 2px;"><?= htmlspecialchars($department) ?></strong></div>
                    <div><span style="color: #64748b; font-weight: 700;">Designation:</span> <strong style="color: #1e1b4b; display: block; margin-top: 2px;"><?= htmlspecialchars($designation) ?></strong></div>
                    <div><span style="color: #64748b; font-weight: 700;">Joining Date:</span> <strong style="color: #1e1b4b; display: block; margin-top: 2px;"><?= $teacher['join_date'] ? date('M d, Y', strtotime($teacher['join_date'])) : 'N/A' ?></strong></div>
                    <div><span style="color: #64748b; font-weight: 700;">Faculty Status:</span> <span class="badge badge-success" style="display: block; width: fit-content; margin-top: 4px;">Active Faculty</span></div>
                </div>
            </div>

            <!-- School Announcements Widget -->
            <div class="card-panel">
                <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--teacher-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-bullhorn" style="color: #059669;"></i> Notice Board
                </h2>
                <?php if ($notices && $notices->num_rows > 0): ?>
                    <?php while ($n = $notices->fetch_assoc()): ?>
                        <div class="notice-item">
                            <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="notice-date"><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 25px; color: #94a3b8; font-weight: 600;">No announcements posted yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
