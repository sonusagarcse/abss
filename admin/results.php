<?php
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Delete Result
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id > 0) {
        $del_stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            $msg = "Examination result record deleted successfully.";
        } else {
            $err = "Error deleting result record.";
        }
    }
}

// Handle Result Entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_result'])) {
    $sid = (int)($_POST['student_id'] ?? 0);
    $exam = trim($_POST['exam_name'] ?? '');
    $score = (float)($_POST['score'] ?? 0);
    $total = (float)($_POST['total_marks'] ?? 100);
    $rank = !empty($_POST['rank']) ? (int)$_POST['rank'] : null;
    $date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : date('Y-m-d');

    if ($sid > 0 && !empty($exam) && $total > 0) {
        $stmt = $conn->prepare("INSERT INTO results (student_id, exam_name, score, total_marks, rank, exam_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdiis", $sid, $exam, $score, $total, $rank, $date);
        
        if ($stmt->execute()) {
            $msg = "Examination result recorded and published successfully.";

            // Fetch parent email and student info
            $student_stmt = $conn->prepare("
                SELECT s.name AS student_name, s.parent_id, p.parent_name, p.email AS parent_email 
                FROM students s 
                LEFT JOIN parents p ON s.parent_id = p.id 
                WHERE s.id = ?
            ");
            $student_stmt->bind_param("i", $sid);
            $student_stmt->execute();
            $student_res = $student_stmt->get_result()->fetch_assoc();
            
            if ($student_res) {
                // Create In-Built Portal Notification
                if (function_exists('create_portal_notification')) {
                    $pct = ($total > 0) ? round(($score / $total) * 100, 1) : 0;
                    $rank_txt = $rank ? " (Rank #$rank)" : "";
                    create_portal_notification(
                        'result',
                        "New Result Published: $exam",
                        "Scorecard for " . $student_res['student_name'] . ": $score/$total ($pct%)$rank_txt.",
                        "results.php",
                        !empty($student_res['parent_id']) ? (int)$student_res['parent_id'] : null,
                        $sid,
                        'fa-award',
                        '#7c3aed'
                    );
                }
            }
            
            if ($student_res && !empty($student_res['parent_email'])) {
                require_once __DIR__ . '/../includes/mail_helper.php';
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
                $base_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
                $dashboard_url = "$base_url/parent/login.php";
                $email_html = get_result_published_template(
                    $student_res['student_name'], 
                    $exam, 
                    $score, 
                    $total, 
                    $rank, 
                    $dashboard_url
                );
                
                send_smtp_email(
                    $student_res['parent_email'], 
                    "Result Published: " . $exam . " - " . $student_res['student_name'] . " - ABSS", 
                    $email_html
                );
                send_smtp_email('abssimamganj@gmail.com', "Result Published: " . $exam . " - " . $student_res['student_name'], $email_html);
            }
        } else {
            $err = "Error recording result: " . $conn->error;
        }
    } else {
        $err = "Please select a student and fill all required fields.";
    }
}

// Fetch stats
$total_results_cnt = (int)($conn->query("SELECT COUNT(*) as c FROM results")->fetch_assoc()['c'] ?? 0);
$avg_percentage_res = $conn->query("SELECT AVG((score / total_marks) * 100) as avg_p FROM results WHERE total_marks > 0")->fetch_assoc();
$avg_percentage = !empty($avg_percentage_res['avg_p']) ? round((float)$avg_percentage_res['avg_p'], 1) : 0;
$total_students_evaluated = (int)($conn->query("SELECT COUNT(DISTINCT student_id) as c FROM results")->fetch_assoc()['c'] ?? 0);

// Fetch Top Student by Average Percentage
$top_student_query = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.student_photo, s.photo, s.target_school, s.class_admitted,
           COUNT(r.id) as total_exams,
           AVG((r.score / r.total_marks) * 100) as avg_pct,
           SUM(r.score) as total_score_obtained,
           SUM(r.total_marks) as total_max_marks,
           MAX((r.score / r.total_marks) * 100) as highest_single_pct
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE r.total_marks > 0
    GROUP BY r.student_id
    ORDER BY avg_pct DESC, total_exams DESC
    LIMIT 1
");
$top_student = ($top_student_query && $top_student_query->num_rows > 0) ? $top_student_query->fetch_assoc() : null;
$top_student_avg = $top_student ? round((float)$top_student['avg_pct'], 1) : 0;

// Fetch Excellence Rate (% scoring >= 75%)
$excellence_cnt = (int)($conn->query("SELECT COUNT(*) as c FROM results WHERE total_marks > 0 AND (score / total_marks) * 100 >= 75")->fetch_assoc()['c'] ?? 0);
$excellence_pct = ($total_results_cnt > 0) ? round(($excellence_cnt / $total_results_cnt) * 100, 1) : 0;

// Fetch Top 5 Performers Leaderboard
$leaderboard_query = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.student_photo, s.photo, s.target_school, s.class_admitted,
           COUNT(r.id) as total_exams,
           AVG((r.score / r.total_marks) * 100) as avg_pct,
           MAX((r.score / r.total_marks) * 100) as max_pct
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE r.total_marks > 0
    GROUP BY r.student_id
    ORDER BY avg_pct DESC, total_exams DESC
    LIMIT 5
");
$leaderboard = [];
if ($leaderboard_query) {
    while ($row = $leaderboard_query->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}

// Fetch Grade Distribution
$dist_query = $conn->query("
    SELECT 
        SUM(CASE WHEN (score / total_marks) * 100 >= 85 THEN 1 ELSE 0 END) as dist_excellence,
        SUM(CASE WHEN (score / total_marks) * 100 >= 75 AND (score / total_marks) * 100 < 85 THEN 1 ELSE 0 END) as dist_verygood,
        SUM(CASE WHEN (score / total_marks) * 100 >= 60 AND (score / total_marks) * 100 < 75 THEN 1 ELSE 0 END) as dist_good,
        SUM(CASE WHEN (score / total_marks) * 100 >= 40 AND (score / total_marks) * 100 < 60 THEN 1 ELSE 0 END) as dist_pass,
        SUM(CASE WHEN (score / total_marks) * 100 < 40 THEN 1 ELSE 0 END) as dist_needs_imp
    FROM results WHERE total_marks > 0
");
$dist = $dist_query ? $dist_query->fetch_assoc() : [
    'dist_excellence' => 0, 'dist_verygood' => 0, 'dist_good' => 0, 'dist_pass' => 0, 'dist_needs_imp' => 0
];

// Fetch Exam Summary Breakdown
$exam_summary = $conn->query("
    SELECT exam_name, 
           COUNT(*) as candidates_count, 
           AVG((score / total_marks) * 100) as exam_avg_pct,
           MAX((score / total_marks) * 100) as exam_max_pct,
           MIN((score / total_marks) * 100) as exam_min_pct,
           MAX(exam_date) as last_date
    FROM results 
    WHERE total_marks > 0 
    GROUP BY exam_name 
    ORDER BY last_date DESC 
    LIMIT 6
");

// Fetch students for dropdown
$students = $conn->query("SELECT id, name, reg_no FROM students WHERE status = 'active' ORDER BY name ASC");

// Fetch results with student info
$results = $conn->query("
    SELECT r.*, s.name, s.reg_no 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    ORDER BY r.exam_date DESC, r.id DESC LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Results & Statistical Analytics | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* Responsive Page Layout */
        .results-layout-grid {
            display: grid;
            grid-template-columns: minmax(320px, 390px) 1fr;
            gap: 30px;
            align-items: start;
        }
        
        .sticky-form-card {
            position: sticky;
            top: 25px;
        }

        .portal-form-row-responsive {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Statistical Banner Card */
        .top-champion-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e40af 100%);
            border-radius: 24px;
            padding: 28px 32px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(30, 64, 175, 0.25);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .top-champion-card::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: rgba(245, 158, 11, 0.18);
            filter: blur(50px);
            border-radius: 50%;
        }

        /* Stats Cards Grid */
        .results-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        /* Rank Badges */
        .rank-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.82rem;
            color: #ffffff;
            flex-shrink: 0;
        }
        .rank-1 { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 10px rgba(245, 158, 11, 0.35); }
        .rank-2 { background: linear-gradient(135deg, #94a3b8, #64748b); box-shadow: 0 4px 10px rgba(100, 116, 139, 0.3); }
        .rank-3 { background: linear-gradient(135deg, #d97706, #b45309); box-shadow: 0 4px 10px rgba(217, 119, 6, 0.3); }
        .rank-other { background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark)); }

        /* Score Pills */
        .score-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.84rem;
        }
        .score-pill-high { background: #dcfce7; color: #166534; }
        .score-pill-mid { background: #dbeafe; color: #1e40af; }
        .score-pill-low { background: #fee2e2; color: #991b1b; }

        /* Search & Action Bar */
        .table-filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 220px;
        }
        .search-input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .search-input-wrap input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
            transition: 0.25s;
            box-sizing: border-box;
        }
        .search-input-wrap input:focus {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Charts Layout Grid */
        .charts-dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Result Table Design */
        .result-table {
            width: 100%;
            min-width: 600px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .result-table th {
            text-align: left;
            padding: 8px 18px;
            color: var(--portal-blue);
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: transparent;
            border: none;
        }
        .result-row td {
            padding: 18px 20px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
            transition: background 0.2s;
        }
        .result-row:hover td {
            background: #f8fafc;
        }
        .result-row td:first-child {
            border-left: 1px solid #f1f5f9;
            border-radius: 16px 0 0 16px;
        }
        .result-row td:last-child {
            border-right: 1px solid #f1f5f9;
            border-radius: 0 16px 16px 0;
        }

        .student-title {
            font-weight: 800;
            color: var(--portal-dark);
            font-size: 0.98rem;
        }
        .student-reg {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 700;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 3px;
        }

        .btn-delete-result {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .btn-delete-result:hover {
            background: #ef4444;
            color: #ffffff;
            transform: scale(1.05);
        }

        .preview-calc-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 700;
            color: #475569;
        }

        /* Leaderboard styling */
        .leaderboard-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        .leaderboard-avatar-fallback {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            border: 2px solid #e2e8f0;
        }

        /* Mobile & Tablet Optimizations */
        @media (max-width: 1024px) {
            .results-layout-grid, .charts-dashboard-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            .sticky-form-card {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .portal-form-row-responsive {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .results-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 12px;
            }
            .stat-card {
                padding: 16px;
                gap: 12px;
            }
            .stat-icon {
                width: 44px;
                height: 44px;
                font-size: 1.2rem;
            }
            .stat-info h3 {
                font-size: 1.3rem;
            }
            .stat-info span {
                font-size: 0.7rem;
            }
            .result-row td {
                padding: 14px 12px;
            }
            .top-champion-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header" style="margin-bottom: 25px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-line" style="color: var(--portal-blue);"></i> Academic Results & Statistical Analytics
                    </h1>
                    <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Real-time performance analytics, top student metrics, and exam recording.</p>
                </div>
            </div>
        </header>

        <!-- Alerts -->
        <?php if (!empty($msg)): ?>
            <div style="background: #f0fdf4; color: #166534; padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: 700; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
                <span><?php echo htmlspecialchars($err); ?></span>
            </div>
        <?php endif; ?>

        <!-- TOP STUDENT CHAMPION SPOTLIGHT CARD -->
        <?php if ($top_student): 
            $photo_src = '';
            $sp_file = !empty($top_student['student_photo']) ? $top_student['student_photo'] : (!empty($top_student['photo']) ? $top_student['photo'] : '');
            if (!empty($sp_file) && file_exists(__DIR__ . '/../' . $sp_file)) {
                $photo_src = '../' . $sp_file;
            }
            $initials = strtoupper(substr($top_student['name'], 0, 2));
        ?>
            <div class="top-champion-card">
                <div style="position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
                    <div style="display: flex; align-items: center; gap: 22px;">
                        <div style="position: relative;">
                            <?php if ($photo_src): ?>
                                <img src="<?php echo htmlspecialchars($photo_src); ?>" alt="<?php echo htmlspecialchars($top_student['name']); ?>" style="width: 84px; height: 84px; border-radius: 50%; object-fit: cover; border: 3px solid #f59e0b; box-shadow: 0 8px 20px rgba(0,0,0,0.3);">
                            <?php else: ?>
                                <div style="width: 84px; height: 84px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 900; border: 3px solid #ffffff; box-shadow: 0 8px 20px rgba(0,0,0,0.3);">
                                    <?php echo $initials; ?>
                                </div>
                            <?php endif; ?>
                            <div style="position: absolute; bottom: -4px; right: -4px; background: #f59e0b; color: #ffffff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; border: 2px solid #ffffff;" title="Top Rank Champion">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>

                        <div>
                            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 4px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">
                                <i class="fas fa-trophy"></i> Top Student in Average Percentage
                            </div>
                            <h2 style="margin: 0; font-size: 1.6rem; font-weight: 900; color: #ffffff; letter-spacing: -0.01em;">
                                <?php echo htmlspecialchars($top_student['name']); ?>
                            </h2>
                            <div style="font-size: 0.88rem; color: #cbd5e1; font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <span><i class="fas fa-id-badge" style="color: #38bdf8;"></i> Reg: <?php echo htmlspecialchars($top_student['reg_no'] ?: 'N/A'); ?></span>
                                <span>•</span>
                                <span><i class="fas fa-graduation-cap" style="color: #c084fc;"></i> <?php echo htmlspecialchars($top_student['target_school'] ?: 'Entrance Batch'); ?></span>
                                <span>•</span>
                                <span><i class="fas fa-file-alt" style="color: #4ade80;"></i> <?php echo $top_student['total_exams']; ?> Exams Evaluated</span>
                            </div>
                        </div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); padding: 16px 24px; border-radius: 18px; border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; min-width: 170px;">
                        <div style="font-size: 0.75rem; color: #93c5fd; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Overall Avg Score</div>
                        <div style="font-size: 2.2rem; font-weight: 900; color: #f59e0b; line-height: 1.1; margin: 4px 0;">
                            <?php echo $top_student_avg; ?>%
                        </div>
                        <div style="font-size: 0.75rem; color: #cbd5e1; font-weight: 700;">
                            Highest Single: <?php echo round((float)$top_student['highest_single_pct'], 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- KEY STATS WIDGETS -->
        <div class="results-stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_results_cnt); ?></h3>
                    <span>Total Published</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_students_evaluated); ?></h3>
                    <span>Students Evaluated</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $avg_percentage; ?>%</h3>
                    <span>Class Average</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $top_student_avg; ?>%</h3>
                    <span>Top Student Avg</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $excellence_pct; ?>%</h3>
                    <span>Excellence Rate (≥75%)</span>
                </div>
            </div>
        </div>



        <!-- TOP PERFORMERS LEADERBOARD & EXAM BREAKDOWN -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;" class="charts-dashboard-grid">
            <!-- Leaderboard Card -->
            <div class="portal-card" style="padding: 22px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: 1.1rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-crown" style="color: #f59e0b;"></i> Top Performers Leaderboard
                    </h3>
                    <span style="font-size: 0.78rem; font-weight: 800; color: var(--portal-blue); background: rgba(37,99,235,0.1); padding: 3px 10px; border-radius: 20px;">
                        Top 5 Overall
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if (!empty($leaderboard)): 
                        $rank_idx = 1;
                        foreach ($leaderboard as $lb):
                            $l_avg = round((float)$lb['avg_pct'], 1);
                            $l_photo = '';
                            $l_file = !empty($lb['student_photo']) ? $lb['student_photo'] : (!empty($lb['photo']) ? $lb['photo'] : '');
                            if (!empty($l_file) && file_exists(__DIR__ . '/../' . $l_file)) {
                                $l_photo = '../' . $l_file;
                            }
                            $l_initials = strtoupper(substr($lb['name'], 0, 2));
                            $r_badge_class = ($rank_idx === 1) ? 'rank-1' : (($rank_idx === 2) ? 'rank-2' : (($rank_idx === 3) ? 'rank-3' : 'rank-other'));
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="rank-badge <?php echo $r_badge_class; ?>">#<?php echo $rank_idx; ?></span>
                                <?php if ($l_photo): ?>
                                    <img src="<?php echo htmlspecialchars($l_photo); ?>" alt="<?php echo htmlspecialchars($lb['name']); ?>" class="leaderboard-avatar">
                                <?php else: ?>
                                    <div class="leaderboard-avatar-fallback"><?php echo $l_initials; ?></div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 800; color: var(--portal-dark); font-size: 0.92rem;"><?php echo htmlspecialchars($lb['name']); ?></div>
                                    <small style="color: #64748b; font-weight: 700;">Reg: <?php echo htmlspecialchars($lb['reg_no'] ?: 'N/A'); ?> • <?php echo $lb['total_exams']; ?> Exams</small>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <span class="score-pill <?php echo ($l_avg >= 75) ? 'score-pill-high' : (($l_avg >= 50) ? 'score-pill-mid' : 'score-pill-low'); ?>">
                                    <?php echo $l_avg; ?>% Avg
                                </span>
                            </div>
                        </div>
                    <?php 
                        $rank_idx++;
                        endforeach; 
                    else: ?>
                        <div style="text-align: center; padding: 25px; color: #94a3b8; font-weight: 600;">No evaluation data available.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Breakdown Card -->
            <div class="portal-card" style="padding: 22px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: 1.1rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-tasks" style="color: #059669;"></i> Exam Performance Summary
                    </h3>
                    <span style="font-size: 0.78rem; font-weight: 800; color: #059669; background: #dcfce7; padding: 3px 10px; border-radius: 20px;">
                        Recent Tests
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if ($exam_summary && $exam_summary->num_rows > 0): 
                        while ($eb = $exam_summary->fetch_assoc()):
                            $e_avg = round((float)$eb['exam_avg_pct'], 1);
                            $e_max = round((float)$eb['exam_max_pct'], 1);
                    ?>
                        <div style="padding: 12px 14px; background: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <span style="font-weight: 800; color: var(--portal-dark); font-size: 0.9rem;"><?php echo htmlspecialchars($eb['exam_name']); ?></span>
                                <span style="font-size: 0.8rem; font-weight: 800; color: var(--portal-blue);"><?php echo $e_avg; ?>% Avg</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 6px;">
                                <div style="width: <?php echo min(100, max(0, $e_avg)); ?>%; height: 100%; background: linear-gradient(90deg, #2563eb, #059669); border-radius: 10px;"></div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b; font-weight: 700;">
                                <span><i class="fas fa-users"></i> <?php echo $eb['candidates_count']; ?> Candidates</span>
                                <span>Highest Score: <?php echo $e_max; ?>%</span>
                            </div>
                        </div>
                    <?php endwhile; 
                    else: ?>
                        <div style="text-align: center; padding: 25px; color: #94a3b8; font-weight: 600;">No exam breakdown data available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- MAIN LAYOUT: RESULT FORM & RECENT RESULTS TABLE -->
        <div class="results-layout-grid">
            <!-- Result Entry Form Card -->
            <div class="portal-card sticky-form-card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 22px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37,99,235,0.1); color: var(--portal-blue); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fas fa-pen-fancy"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.2rem;">Record Result</h3>
                        <small style="color: #64748b; font-weight: 600;">Notify student & parent instantly</small>
                    </div>
                </div>

                <form action="" method="POST" id="resultForm">
                    <div class="portal-input-group">
                        <label for="studentSelect"><i class="fas fa-user"></i> Student <span style="color:#ef4444;">*</span></label>
                        <select name="student_id" id="studentSelect" required>
                            <option value="">Select Student...</option>
                            <?php if ($students && $students->num_rows > 0): ?>
                                <?php while($s = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $s['id']; ?>">
                                        <?php echo htmlspecialchars($s['name']) . (!empty($s['reg_no']) ? ' (' . htmlspecialchars($s['reg_no']) . ')' : ''); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="portal-input-group">
                        <label for="examName"><i class="fas fa-book"></i> Exam / Mock Test Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="exam_name" id="examName" placeholder="e.g. Sainik School Mock-01, Mid-Term Math" required>
                    </div>

                    <div class="portal-form-row-responsive">
                        <div class="portal-input-group">
                            <label for="scoreInput"><i class="fas fa-check"></i> Marks Obtained <span style="color:#ef4444;">*</span></label>
                            <input type="number" step="0.01" name="score" id="scoreInput" placeholder="e.g. 85.5" required oninput="updateLiveCalc()">
                        </div>
                        <div class="portal-input-group">
                            <label for="totalMarksInput"><i class="fas fa-dot-circle"></i> Max Marks <span style="color:#ef4444;">*</span></label>
                            <input type="number" step="0.01" name="total_marks" id="totalMarksInput" value="100" required oninput="updateLiveCalc()">
                        </div>
                    </div>

                    <!-- Live Percentage Preview -->
                    <div class="preview-calc-box" id="calcPreviewBox">
                        <span><i class="fas fa-calculator" style="color: var(--portal-blue);"></i> Calculated Percentage:</span>
                        <span id="percentageBadge" style="font-weight: 800; color: var(--portal-blue);">-- %</span>
                    </div>

                    <div class="portal-form-row-responsive">
                        <div class="portal-input-group">
                            <label for="rankInput"><i class="fas fa-trophy"></i> Rank (Optional)</label>
                            <input type="number" name="rank" id="rankInput" placeholder="e.g. 1" min="1">
                        </div>
                        <div class="portal-input-group">
                            <label for="examDate"><i class="fas fa-calendar-alt"></i> Exam Date <span style="color:#ef4444;">*</span></label>
                            <input type="date" name="exam_date" id="examDate" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <button type="submit" name="save_result" class="btn-portal w-100" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-paper-plane"></i> Publish & Notify Result
                    </button>
                </form>
            </div>

            <!-- Recent Results List Section -->
            <div class="portal-card" style="padding: 24px;">
                <div class="table-filter-bar">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h3 style="margin: 0; font-size: 1.2rem; color: var(--portal-dark);">Recent Performances</h3>
                        <span style="background: rgba(37,99,235,0.1); color: var(--portal-blue); padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">
                            <?php echo $results ? $results->num_rows : 0; ?> Records
                        </span>
                    </div>

                    <div class="search-input-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="resultSearchInput" placeholder="Search student or exam..." onkeyup="filterResultsTable()">
                    </div>
                </div>

                <div class="portal-table-container">
                    <table class="result-table" id="resultsTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Exam Details</th>
                                <th>Score & Rank</th>
                                <th style="text-align: right; width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($results && $results->num_rows > 0): ?>
                                <?php while($r = $results->fetch_assoc()): 
                                    $score = (float)$r['score'];
                                    $total = (float)$r['total_marks'] > 0 ? (float)$r['total_marks'] : 100;
                                    $pct = round(($score / $total) * 100, 1);
                                    
                                    $score_class = 'score-pill-mid';
                                    if ($pct >= 75) $score_class = 'score-pill-high';
                                    elseif ($pct < 45) $score_class = 'score-pill-low';

                                    $rank_class = 'rank-other';
                                    if (!empty($r['rank'])) {
                                        if ((int)$r['rank'] === 1) $rank_class = 'rank-1';
                                        elseif ((int)$r['rank'] === 2) $rank_class = 'rank-2';
                                        elseif ((int)$r['rank'] === 3) $rank_class = 'rank-3';
                                    }
                                ?>
                                    <tr class="result-row">
                                        <td>
                                            <div class="student-title"><?php echo htmlspecialchars($r['name']); ?></div>
                                            <?php if (!empty($r['reg_no'])): ?>
                                                <span class="student-reg"><?php echo htmlspecialchars($r['reg_no']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 800; color: var(--portal-dark); font-size: 0.92rem;">
                                                <?php echo htmlspecialchars($r['exam_name']); ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: #64748b; margin-top: 3px; display: flex; align-items: center; gap: 5px;">
                                                <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($r['exam_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                <span class="score-pill <?php echo $score_class; ?>">
                                                    <?php echo $score . ' / ' . $total; ?> (<?php echo $pct; ?>%)
                                                </span>
                                                <?php if(!empty($r['rank'])): ?>
                                                    <span class="rank-badge <?php echo $rank_class; ?>" title="Rank <?php echo (int)$r['rank']; ?>">
                                                        #<?php echo (int)$r['rank']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="results.php?delete=<?php echo $r['id']; ?>" 
                                               class="btn-delete-result" 
                                               title="Delete result record"
                                               onclick="return confirm('Are you sure you want to delete this result for <?php echo addslashes($r['name']); ?>?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-clipboard-list" style="font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                                        <p style="margin: 0; font-weight: 700;">No academic examination results recorded yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>


        // Live Score % calculation preview
        function updateLiveCalc() {
            const score = parseFloat(document.getElementById('scoreInput').value);
            const total = parseFloat(document.getElementById('totalMarksInput').value);
            const badge = document.getElementById('percentageBadge');

            if (!isNaN(score) && !isNaN(total) && total > 0) {
                const pct = ((score / total) * 100).toFixed(1);
                badge.innerText = pct + '%';
                if (pct >= 75) {
                    badge.style.color = '#16a34a';
                } else if (pct >= 50) {
                    badge.style.color = '#2563eb';
                } else {
                    badge.style.color = '#dc2626';
                }
            } else {
                badge.innerText = '-- %';
                badge.style.color = '#2563eb';
            }
        }

        // Live table filtering
        function filterResultsTable() {
            const query = document.getElementById('resultSearchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#resultsTable tbody tr.result-row');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
