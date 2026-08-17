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
    <title>Academic Results | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
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

        /* Stats Cards */
        .results-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
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

        /* Performance Pill */
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

        /* Mobile & Tablet Optimizations */
        @media (max-width: 1024px) {
            .results-layout-grid {
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
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header" style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-award" style="color: var(--portal-blue);"></i> Academic Results
                    </h1>
                    <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Record, manage, and instantly publish examination & mock test results.</p>
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

        <!-- Key Stats Widgets -->
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
        </div>

        <!-- Main Layout: Form & Result Table -->
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
