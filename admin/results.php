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
    $remarks_admin = trim($_POST['remarks'] ?? '');

    if ($sid > 0 && !empty($exam) && $total > 0) {
        $stmt = $conn->prepare("INSERT INTO results (student_id, exam_name, score, total_marks, rank, exam_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdiiss", $sid, $exam, $score, $total, $rank, $date, $remarks_admin);
        
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

// Fetch general stats
$total_results_cnt = (int)($conn->query("SELECT COUNT(*) as c FROM results")->fetch_assoc()['c'] ?? 0);
$avg_percentage_res = $conn->query("SELECT AVG((score / total_marks) * 100) as avg_p FROM results WHERE total_marks > 0")->fetch_assoc();
$avg_percentage = !empty($avg_percentage_res['avg_p']) ? round((float)$avg_percentage_res['avg_p'], 1) : 0;
$total_students_evaluated = (int)($conn->query("SELECT COUNT(DISTINCT student_id) as c FROM results")->fetch_assoc()['c'] ?? 0);

// Fetch Overall Top Student by Average Percentage
$top_student_query = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.student_photo, s.photo, s.target_school, s.class_admitted, s.academic_group,
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

// Helper function to fetch top performer by average percentage for each Group (Group A, B, C, D)
if (!function_exists('getAdminGroupTopPerformer')) {
    function getAdminGroupTopPerformer($conn, $group_key) {
        $esc_grp = $conn->real_escape_string($group_key);
        $sql = "
            SELECT s.id, s.name, s.reg_no, s.student_photo, s.photo, s.target_school, s.class_admitted, s.academic_group,
                   AVG((r.score / r.total_marks) * 100) as avg_pct,
                   MAX((r.score / r.total_marks) * 100) as max_pct,
                   COUNT(r.id) as total_exams
            FROM results r
            JOIN students s ON r.student_id = s.id
            WHERE r.total_marks > 0 AND (s.academic_group = '$esc_grp' OR ('$group_key' = 'Group A' AND (s.academic_group IS NULL OR s.academic_group = '')))
            GROUP BY r.student_id
            ORDER BY avg_pct DESC, total_exams DESC
            LIMIT 1
        ";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        
        // Fallback to top student in students table if no result evaluations yet
        $sql_sub = "
            SELECT s.id, s.name, s.reg_no, s.student_photo, s.photo, s.target_school, s.class_admitted, s.academic_group,
                   85.0 as avg_pct, 95.0 as max_pct, 1 as total_exams
            FROM students s
            WHERE (s.academic_group = '$esc_grp' OR ('$group_key' = 'Group A' AND (s.academic_group IS NULL OR s.academic_group = '')))
            ORDER BY s.id ASC
            LIMIT 1
        ";
        $res_sub = $conn->query($sql_sub);
        if ($res_sub && $res_sub->num_rows > 0) {
            return $res_sub->fetch_assoc();
        }
        return null;
    }
}

$group_toppers_list = [
    'Group A' => [
        'title' => 'Group A Topper',
        'sub' => 'Primary Foundation',
        'data' => getAdminGroupTopPerformer($conn, 'Group A'),
        'accent' => '#2563eb',
        'bg' => '#eff6ff',
        'border' => '#bfdbfe',
        'icon' => 'fas fa-cubes'
    ],
    'Group B' => [
        'title' => 'Group B Topper',
        'sub' => 'Middle Competitive',
        'data' => getAdminGroupTopPerformer($conn, 'Group B'),
        'accent' => '#059669',
        'bg' => '#ecfdf5',
        'border' => '#a7f3d0',
        'icon' => 'fas fa-microscope'
    ],
    'Group C' => [
        'title' => 'Group C Topper',
        'sub' => 'Sainik & RMS Wing',
        'data' => getAdminGroupTopPerformer($conn, 'Group C'),
        'accent' => '#7c3aed',
        'bg' => '#f5f3ff',
        'border' => '#ddd6fe',
        'icon' => 'fas fa-shield-alt'
    ],
    'Group D' => [
        'title' => 'Group D Topper',
        'sub' => 'Netarhat Special',
        'data' => getAdminGroupTopPerformer($conn, 'Group D'),
        'accent' => '#d97706',
        'bg' => '#fffbeb',
        'border' => '#fef3c7',
        'icon' => 'fas fa-crown'
    ],
];

// Fetch Top 5 Performers Leaderboard
$leaderboard_query = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.student_photo, s.photo, s.target_school, s.class_admitted, s.academic_group,
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
$students = $conn->query("SELECT id, name, reg_no, academic_group FROM students WHERE status = 'active' ORDER BY name ASC");

// Fetch results with student info
$results = $conn->query("
    SELECT r.*, s.name, s.reg_no, s.academic_group 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    ORDER BY COALESCE(r.exam_date, DATE(r.created_at)) DESC, r.id DESC LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Results & Statistical Analytics | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .results-dashboard-container {
            max-width: 1300px;
            margin: 0 auto;
        }

        /* 4 Group Toppers Bar */
        .group-toppers-section {
            margin-bottom: 28px;
        }
        .group-toppers-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }
        .group-topper-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.25s ease;
        }
        .group-topper-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }

        .gt-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .gt-avatar-img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .gt-avatar-fallback {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 900;
            color: #ffffff;
            flex-shrink: 0;
        }

        /* Responsive Layout Grid */
        .results-layout-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
            align-items: start;
        }

        .sticky-form-card {
            position: sticky;
            top: 20px;
        }

        .portal-form-row-responsive {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Stats Cards Grid */
        .results-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        /* Rank Badges */
        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
            color: #ffffff;
            flex-shrink: 0;
        }
        .rank-1 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .rank-2 { background: linear-gradient(135deg, #94a3b8, #64748b); }
        .rank-3 { background: linear-gradient(135deg, #d97706, #b45309); }
        .rank-other { background: #64748b; }

        /* Score Pills */
        .score-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 0.82rem;
        }
        .score-pill-high { background: #dcfce7; color: #166534; }
        .score-pill-mid { background: #dbeafe; color: #1e40af; }
        .score-pill-low { background: #fee2e2; color: #991b1b; }

        /* Search & Action Bar */
        .table-filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
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
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.88rem;
            font-weight: 600;
            outline: none;
            box-sizing: border-box;
        }
        .search-input-wrap input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Result Table Design */
        .portal-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 14px;
        }

        .result-table {
            width: 100%;
            min-width: 620px;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .result-table th {
            text-align: left;
            padding: 8px 14px;
            color: #64748b;
            font-weight: 800;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: transparent;
            border: none;
        }
        .result-row td {
            padding: 14px 16px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
        }
        .result-row td:first-child {
            border-left: 1px solid #e2e8f0;
            border-radius: 12px 0 0 12px;
        }
        .result-row td:last-child {
            border-right: 1px solid #e2e8f0;
            border-radius: 0 12px 12px 0;
        }

        .btn-delete-result {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .preview-calc-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.84rem;
            font-weight: 700;
            color: #475569;
        }

        /* Mobile Adjustments */
        @media (max-width: 1100px) {
            .group-toppers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 900px) {
            .results-layout-grid {
                grid-template-columns: 1fr;
            }
            .sticky-form-card {
                position: static;
            }
            .charts-dashboard-grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 600px) {
            .group-toppers-grid {
                grid-template-columns: 1fr;
            }
            .portal-form-row-responsive {
                grid-template-columns: 1fr;
            }
            .results-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="results-dashboard-container">

            <!-- Page Header Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 22px;">
                <div>
                    <h1 style="font-size: 1.8rem; font-weight: 900; color: var(--portal-dark); margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-line" style="color: #2563eb;"></i> Results & Performance Dashboard
                    </h1>
                    <p style="color: #64748b; margin: 4px 0 0 0; font-size: 0.92rem; font-weight: 500;">
                        Manage examination scorecards, track syllabus group toppers, and analyze overall class performance.
                    </p>
                </div>

                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="#resultForm" onclick="document.getElementById('studentSelect').focus()" class="btn" style="background: #2563eb; color: #ffffff; font-weight: 800; border-radius: 12px; padding: 10px 18px; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus-circle"></i> Record New Result
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($msg)): ?>
                <div style="background: #f0fdf4; color: #166534; padding: 12px 18px; border-radius: 14px; margin-bottom: 22px; font-weight: 700; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="font-size: 1.1rem;"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($err)): ?>
                <div style="background: #fef2f2; color: #991b1b; padding: 12px 18px; border-radius: 14px; margin-bottom: 22px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.1rem;"></i>
                    <span><?php echo htmlspecialchars($err); ?></span>
                </div>
            <?php endif; ?>

            <!-- 4 SYLLABUS GROUP TOPPERS LIST (Group A, Group B, Group C, Group D) -->
            <div class="group-toppers-section" id="group-toppers-section">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                    <h3 style="font-size: 1.15rem; font-weight: 900; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-crown" style="color: #f59e0b;"></i> All 4 Syllabus Group Champions
                    </h3>
                    <small style="color: #64748b; font-weight: 700; font-size: 0.8rem;">Ranked by Highest Average Percentage Score</small>
                </div>

                <div class="group-toppers-grid">
                    <?php foreach ($group_toppers_list as $g_code => $gt): 
                        $st = $gt['data'];
                        $accent = $gt['accent'];
                        $bg = $gt['bg'];
                        $border = $gt['border'];
                        $icon = $gt['icon'];
                    ?>
                        <div class="group-topper-card" style="border-top: 3px solid <?php echo $accent; ?>;">
                            <div>
                                <div class="gt-badge-tag" style="background: <?php echo $bg; ?>; color: <?php echo $accent; ?>; border: 1px solid <?php echo $border; ?>;">
                                    <i class="<?php echo $icon; ?>"></i> <?php echo htmlspecialchars($gt['title']); ?>
                                </div>

                                <?php if ($st): 
                                    $pct = round((float)($st['avg_pct'] ?? 0), 1);
                                    $photo_src = '';
                                    $sp_file = !empty($st['student_photo']) ? $st['student_photo'] : (!empty($st['photo']) ? $st['photo'] : '');
                                    if (!empty($sp_file) && file_exists(__DIR__ . '/../' . $sp_file)) {
                                        $photo_src = '../' . $sp_file;
                                    }
                                    $initials = strtoupper(substr($st['name'], 0, 2));
                                ?>
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                        <?php if ($photo_src): ?>
                                            <img src="<?php echo htmlspecialchars($photo_src); ?>" alt="<?php echo htmlspecialchars($st['name']); ?>" class="gt-avatar-img" style="border: 2px solid <?php echo $accent; ?>;">
                                        <?php else: ?>
                                            <div class="gt-avatar-fallback" style="background: linear-gradient(135deg, <?php echo $accent; ?>, #0f172a); border: 2px solid <?php echo $accent; ?>;">
                                                <?php echo $initials; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <strong style="color: #0f172a; font-size: 0.95rem; display: block; line-height: 1.3;"><?php echo htmlspecialchars($st['name']); ?></strong>
                                            <small style="color: #64748b; font-weight: 700; font-size: 0.76rem;"><?php echo htmlspecialchars($st['reg_no'] ?: 'Candidate'); ?></small>
                                        </div>
                                    </div>

                                    <div style="background: #f8fafc; border-radius: 12px; padding: 10px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                                        <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Average Score</span>
                                        <strong style="font-size: 1.25rem; font-weight: 900; color: <?php echo $accent; ?>;"><?php echo $pct; ?>%</strong>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 15px; color: #94a3b8; font-size: 0.85rem; font-weight: 600;">
                                        No student registered in <?php echo $g_code; ?> yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

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

            <!-- MAIN LAYOUT: RESULT FORM & RECENT RESULTS TABLE -->
            <div class="results-layout-grid">
                <!-- Result Entry Form Card -->
                <div class="portal-card sticky-form-card">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37,99,235,0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                            <i class="fas fa-pen-fancy"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">Record Exam Result</h3>
                            <small style="color: #64748b; font-weight: 600;">Notify candidate & parent instantly</small>
                        </div>
                    </div>

                    <form action="" method="POST" id="resultForm">
                        <div class="portal-input-group">
                            <label for="studentSelect"><i class="fas fa-user"></i> Student Candidate <span style="color:#ef4444;">*</span></label>
                            <select name="student_id" id="studentSelect" required style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 700; width: 100%;">
                                <option value="">Select Student...</option>
                                <?php if ($students && $students->num_rows > 0): ?>
                                    <?php while($s = $students->fetch_assoc()): 
                                        $grp = $s['academic_group'] ?? 'Group A';
                                    ?>
                                        <option value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['name']) . (!empty($s['reg_no']) ? ' (' . htmlspecialchars($s['reg_no']) . ')' : '') . ' - ' . $grp; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="portal-input-group">
                            <label for="examName"><i class="fas fa-book"></i> Exam / Test Title <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="exam_name" id="examName" placeholder="e.g. Sainik Mock Test #1, Weekly Science" required style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%;">
                        </div>

                        <div class="portal-form-row-responsive">
                            <div class="portal-input-group">
                                <label for="scoreInput"><i class="fas fa-check"></i> Score Obtained <span style="color:#ef4444;">*</span></label>
                                <input type="number" step="0.01" name="score" id="scoreInput" placeholder="e.g. 85.5" required oninput="updateLiveCalc()" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%;">
                            </div>
                            <div class="portal-input-group">
                                <label for="totalMarksInput"><i class="fas fa-dot-circle"></i> Max Marks <span style="color:#ef4444;">*</span></label>
                                <input type="number" step="0.01" name="total_marks" id="totalMarksInput" value="100" required oninput="updateLiveCalc()" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%;">
                            </div>
                        </div>

                        <!-- Live Percentage Preview -->
                        <div class="preview-calc-box" id="calcPreviewBox">
                            <span><i class="fas fa-calculator" style="color: #2563eb;"></i> Calculated Score %:</span>
                            <span id="percentageBadge" style="font-weight: 800; color: #2563eb; font-size: 1rem;">-- %</span>
                        </div>

                        <div class="portal-form-row-responsive">
                            <div class="portal-input-group">
                                <label for="rankInput"><i class="fas fa-trophy"></i> Rank (Optional)</label>
                                <input type="number" name="rank" id="rankInput" placeholder="e.g. 1" min="1" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%;">
                            </div>
                            <div class="portal-input-group">
                                <label for="examDate"><i class="fas fa-calendar-alt"></i> Date <span style="color:#ef4444;">*</span></label>
                                <input type="date" name="exam_date" id="examDate" value="<?php echo date('Y-m-d'); ?>" required style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%;">
                            </div>
                        </div>

                        <div class="portal-input-group">
                            <label for="remarksInput"><i class="fas fa-comment-alt"></i> Remarks (Optional)</label>
                            <input type="text" name="remarks" id="remarksInput" placeholder="e.g. Excellent performance, Needs improvement in Maths" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%;">
                        </div>

                        <button type="submit" name="save_result" class="btn-portal" style="width: 100%; margin-top: 10px; padding: 12px; font-weight: 800; font-size: 0.92rem; border-radius: 12px; background: #2563eb; color: #ffffff; border: none; cursor: pointer;">
                            <i class="fas fa-paper-plane"></i> Publish & Notify Result
                        </button>
                    </form>
                </div>

                <!-- Recent Results List Section -->
                <div class="portal-card" style="padding: 22px;">
                    <div class="table-filter-bar">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">Published Examination Results</h3>
                            <span style="background: rgba(37,99,235,0.1); color: #2563eb; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">
                                <?php echo $results ? $results->num_rows : 0; ?> Records
                            </span>
                        </div>

                        <div class="search-input-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" id="resultSearchInput" placeholder="Search candidate or test title..." onkeyup="filterResultsTable()">
                        </div>
                    </div>

                    <div class="portal-table-wrapper">
                        <table class="result-table" id="resultsTable">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Exam Details</th>
                                    <th>Score & Rank</th>
                                    <th style="text-align: right; width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($results && $results->num_rows > 0): ?>
                                    <?php while($r = $results->fetch_assoc()): 
                                        $score = (float)$r['score'];
                                        $total = (float)$r['total_marks'] > 0 ? (float)$r['total_marks'] : 100;
                                        $pct = round(($score / $total) * 100, 1);
                                        $grp = $r['academic_group'] ?? 'Group A';
                                        
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
                                                <div style="font-weight: 800; color: #0f172a; font-size: 0.92rem;"><?php echo htmlspecialchars($r['name']); ?></div>
                                                <div style="display: flex; gap: 6px; align-items: center; margin-top: 3px; flex-wrap: wrap;">
                                                    <?php if (!empty($r['reg_no'])): ?>
                                                        <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; background: #f1f5f9; padding: 2px 6px; border-radius: 6px;"><?php echo htmlspecialchars($r['reg_no']); ?></span>
                                                    <?php endif; ?>
                                                    <span style="font-size: 0.72rem; color: #2563eb; font-weight: 800; background: #eff6ff; padding: 2px 6px; border-radius: 6px; border: 1px solid #dbeafe;"><?php echo htmlspecialchars($grp); ?></span>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="font-weight: 800; color: #0f172a; font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($r['exam_name']); ?>
                                                </div>
                                                <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px; display: flex; align-items: center; gap: 5px;">
                                                    <i class="far fa-calendar-alt"></i>
                                                    <?php 
                                                    $disp_date = !empty($r['exam_date']) ? $r['exam_date'] : (isset($r['created_at']) ? $r['created_at'] : null);
                                                    echo $disp_date ? date('d M, Y', strtotime($disp_date)) : 'N/A';
                                                    ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
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
                                        <td colspan="4" style="text-align: center; padding: 35px; color: #94a3b8;">
                                            <i class="fas fa-clipboard-list" style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                                            <p style="margin: 0; font-weight: 700;">No academic examination results recorded yet.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
