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
            background: linear-gradient(135deg, var(--teacher-dark), var(--teacher-purple));
            color: white;
            padding: 35px;
            border-radius: 24px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 15px 30px rgba(124, 58, 237, 0.15);
        }
        .welcome-banner h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; }
        .welcome-banner p { opacity: 0.9; font-size: 0.95rem; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #ede9fe;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .icon-purple { background: #ede9fe; color: var(--teacher-purple); }
        .icon-green { background: #dcfce7; color: #166534; }
        .icon-blue { background: #dbeafe; color: #1e40af; }
        .icon-amber { background: #fef3c7; color: #92400e; }
        .stat-val { font-size: 1.5rem; font-weight: 800; color: #1e1b4b; margin-top: 2px; }
        .stat-lbl { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

        .section-title { font-size: 1.25rem; font-weight: 800; color: var(--teacher-dark); margin-bottom: 20px; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }
        .action-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #ede9fe;
            text-decoration: none;
            color: #1e1b4b;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s;
        }
        .action-card:hover {
            background: var(--teacher-purple);
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.2);
        }
        .action-card i { font-size: 1.3rem; }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        @media (max-width: 992px) { .dashboard-layout { grid-template-columns: 1fr; } }

        .card-panel {
            background: #ffffff;
            border-radius: 20px;
            padding: 28px;
            border: 1px solid #ede9fe;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .notice-item {
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .notice-item:last-child { border-bottom: none; }
        .notice-title { font-weight: 700; font-size: 0.95rem; color: #1e1b4b; }
        .notice-date { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Welcome Header Banner -->
        <div class="welcome-banner">
            <div>
                <h1>Welcome Back, <?= htmlspecialchars($teacher_name) ?>! 👋</h1>
                <p><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($designation) ?> &bull; Department of <?= htmlspecialchars($department) ?></p>
            </div>
            <div>
                <a href="attendance.php" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 22px; border-radius: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
                    <i class="fas fa-calendar-check"></i> Take Today's Attendance
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
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

        <!-- Quick Shortcuts -->
        <h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="quick-actions">
            <a href="attendance.php" class="action-card">
                <i class="fas fa-calendar-alt"></i> Mark Attendance
            </a>
            <a href="results.php" class="action-card">
                <i class="fas fa-pen-alt"></i> Upload Test Results
            </a>
            <a href="expenses.php" class="action-card">
                <i class="fas fa-plus-circle"></i> File Expense Claim
            </a>
            <a href="invoices.php" class="action-card">
                <i class="fas fa-file-invoice"></i> View Salary Invoices
            </a>
        </div>

        <!-- Main Dashboard Content -->
        <div class="dashboard-layout">
            <!-- Faculty Information Card -->
            <div class="card-panel">
                <h2 class="section-title"><i class="fas fa-id-card"></i> Faculty Profile Summary</h2>
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                    <img src="<?= !empty($teacher['photo']) ? '../' . htmlspecialchars($teacher['photo']) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' ?>" alt="Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ede9fe;">
                    <div>
                        <h3 style="font-size: 1.2rem; color: #1e1b4b; font-weight: 800;"><?= htmlspecialchars($teacher_name) ?></h3>
                        <p style="color: #64748b; font-size: 0.9rem; margin-top: 2px;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($teacher['email'] ?? 'Not specified') ?></p>
                        <p style="color: #64748b; font-size: 0.9rem; margin-top: 2px;"><i class="fas fa-phone"></i> <?= htmlspecialchars($teacher['phone'] ?? 'Not specified') ?></p>
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 12px; padding: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
                    <div><strong>Department:</strong> <?= htmlspecialchars($department) ?></div>
                    <div><strong>Designation:</strong> <?= htmlspecialchars($designation) ?></div>
                    <div><strong>Joining Date:</strong> <?= $teacher['join_date'] ? date('M d, Y', strtotime($teacher['join_date'])) : 'N/A' ?></div>
                    <div><strong>Status:</strong> <span style="color: #166534; font-weight: 700;">Active Faculty</span></div>
                </div>
            </div>

            <!-- School Announcements Widget -->
            <div class="card-panel">
                <h2 class="section-title"><i class="fas fa-bullhorn"></i> Notice Board</h2>
                <?php if ($notices && $notices->num_rows > 0): ?>
                    <?php while ($n = $notices->fetch_assoc()): ?>
                        <div class="notice-item">
                            <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="notice-date"><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #94a3b8; font-size: 0.9rem;">No announcements posted.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
