<?php
require_once 'includes/auth.php';

$msg = '';
$err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_result'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $exam_name = trim($_POST['exam_name'] ?? '');
    $score = (float)($_POST['score'] ?? ($_POST['marks_obtained'] ?? 0));
    $total_marks = (float)($_POST['total_marks'] ?? 100);
    $remarks = trim($_POST['remarks'] ?? '');
    $exam_date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : date('Y-m-d');

    if ($student_id > 0 && !empty($exam_name) && $total_marks > 0) {
        $stmt = $conn->prepare("INSERT INTO results (student_id, exam_name, score, total_marks, exam_date, remarks) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddss", $student_id, $exam_name, $score, $total_marks, $exam_date, $remarks);

        if ($stmt && $stmt->execute()) {
            $msg = "Exam result published successfully! Parent has been notified.";

            // Fetch parent email and student info
            $student_stmt = $conn->prepare("
                SELECT s.name AS student_name, s.parent_id, p.parent_name, p.email AS parent_email 
                FROM students s 
                LEFT JOIN parents p ON s.parent_id = p.id 
                WHERE s.id = ?
            ");
            $student_stmt->bind_param("i", $student_id);
            $student_stmt->execute();
            $student_res = $student_stmt->get_result()->fetch_assoc();
            
            if ($student_res) {
                // Create In-Built Portal Notification
                if (function_exists('create_portal_notification')) {
                    $pct = ($total_marks > 0) ? round(($score / $total_marks) * 100, 1) : 0;
                    create_portal_notification(
                        'result',
                        "New Result Published: $exam_name",
                        "Scorecard for " . $student_res['student_name'] . ": $score/$total_marks ($pct%).",
                        "results.php",
                        !empty($student_res['parent_id']) ? (int)$student_res['parent_id'] : null,
                        $student_id,
                        'fa-award',
                        '#7c3aed'
                    );
                }
                
                if (!empty($student_res['parent_email'])) {
                    require_once __DIR__ . '/../includes/mail_helper.php';
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
                    $base_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
                    $dashboard_url = "$base_url/parent/login.php";
                    $email_html = get_result_published_template(
                        $student_res['student_name'], 
                        $exam_name, 
                        $score, 
                        $total_marks, 
                        null, 
                        $dashboard_url
                    );
                    
                    send_smtp_email(
                        $student_res['parent_email'], 
                        "Result Published: " . $exam_name . " - " . $student_res['student_name'] . " - ABSS", 
                        $email_html
                    );
                    send_smtp_email('abssimamganj@gmail.com', "Result Published: " . $exam_name . " - " . $student_res['student_name'], $email_html);
                }
            }
        } else {
            $err = "Error recording marks: " . $conn->error;
        }
    } else {
        $err = "Please select a student, enter exam name and valid total marks.";
    }
}

// Fetch results
$results_query = $conn->query("
    SELECT r.*, s.name as student_name, s.academic_group
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    ORDER BY COALESCE(r.exam_date, DATE(r.created_at)) DESC, r.id DESC
");

// Fetch active students for dropdown
$students_list = $conn->query("SELECT id, name, reg_no, academic_group FROM students WHERE status = 'active' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .grid-layout { display: grid; grid-template-columns: 380px 1fr; gap: 24px; margin-top: 20px; }
        @media (max-width: 992px) { .grid-layout { grid-template-columns: 1fr; } }
        .page-card { background: #ffffff; border-radius: 20px; padding: 28px; border: 1px solid #ede9fe; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 800; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
        .form-group label i { margin-right: 5px; color: var(--teacher-purple); }
        .form-control { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.92rem; outline: none; box-sizing: border-box; color: #1e293b; font-weight: 600; transition: border-color 0.2s; }
        .form-control:focus { border-color: var(--teacher-purple); box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .live-calc { background: #f5f3ff; border: 1px dashed #c4b5fd; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; font-size: 0.84rem; font-weight: 700; color: #6d28d9; margin-bottom: 12px; }
        .btn-purple { width: 100%; background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark)); color: white; padding: 13px; border-radius: 12px; font-weight: 800; border: none; cursor: pointer; transition: 0.3s; margin-top: 6px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 6px 16px rgba(124,58,237,0.28); }
        .btn-purple:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(124,58,237,0.38); }
        /* Results Table */
        .result-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .result-table th { text-align: left; padding: 6px 14px; color: #64748b; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .result-row td { padding: 12px 14px; background: #faf5ff; border-top: 1px solid #ede9fe; border-bottom: 1px solid #ede9fe; font-weight: 600; color: #334155; vertical-align: middle; }
        .result-row td:first-child { border-left: 1px solid #ede9fe; border-radius: 10px 0 0 10px; }
        .result-row td:last-child { border-right: 1px solid #ede9fe; border-radius: 0 10px 10px 0; }
        .score-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; }
        .score-high { background: #dcfce7; color: #16a34a; }
        .score-mid  { background: #fef3c7; color: #b45309; }
        .score-low  { background: #fee2e2; color: #dc2626; }
        .group-tag { font-size: 0.72rem; background: #ede9fe; color: #7c3aed; padding: 2px 8px; border-radius: 6px; font-weight: 800; }
        .sticky-card { position: sticky; top: 20px; }
        @media (max-width: 700px) { .sticky-card { position: static; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 20px;">
            <h1 style="font-size: 1.8rem; color: var(--teacher-dark); font-weight: 900; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chart-bar" style="color: var(--teacher-purple);"></i> Student Exam Results
            </h1>
            <p style="color: #64748b; margin-top: 4px; font-weight: 600;">Publish and track student exam scores. Parents are notified instantly.</p>
        </header>

        <?php if ($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:14px 22px; border-radius:12px; margin-bottom:20px; font-weight:700; border: 1px solid #bbf7d0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div style="background:#fef2f2; color:#991b1b; padding:14px 22px; border-radius:12px; margin-bottom:20px; font-weight:700; border: 1px solid #fecaca; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <div class="grid-layout">
            <!-- Add Marks Form -->
            <div class="page-card sticky-card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(124,58,237,0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fas fa-pen-fancy"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e1b4b;">Enter Exam Result</h3>
                        <small style="color: #64748b; font-weight: 600;">Notify student & parent instantly</small>
                    </div>
                </div>
                <form action="results.php" method="POST">
                    <div class="form-group">
                        <label for="student_id"><i class="fas fa-user"></i> Student *</label>
                        <select id="student_id" name="student_id" class="form-control" required>
                            <option value="">-- Select Student --</option>
                            <?php if ($students_list): ?>
                                <?php while ($st = $students_list->fetch_assoc()): 
                                    $grp = $st['academic_group'] ?? '';
                                ?>
                                    <option value="<?= $st['id'] ?>">
                                        <?= htmlspecialchars($st['name']) ?>
                                        <?= !empty($st['reg_no']) ? '('.$st['reg_no'].')' : '' ?>
                                        <?= $grp ? '- '.$grp : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exam_name"><i class="fas fa-book"></i> Exam / Test Title *</label>
                        <input type="text" id="exam_name" name="exam_name" class="form-control" placeholder="e.g. Unit Test 1, Mock Test #2" required>
                    </div>

                    <div class="two-col">
                        <div class="form-group">
                            <label for="score"><i class="fas fa-check"></i> Score Obtained *</label>
                            <input type="number" step="0.01" id="score" name="score" class="form-control" placeholder="e.g. 85" required oninput="updateCalc()">
                        </div>
                        <div class="form-group">
                            <label for="total_marks"><i class="fas fa-dot-circle"></i> Max Marks *</label>
                            <input type="number" step="0.01" id="total_marks" name="total_marks" class="form-control" placeholder="100" required oninput="updateCalc()">
                        </div>
                    </div>

                    <!-- Live Percentage Preview -->
                    <div class="live-calc" id="liveCalc">
                        <span><i class="fas fa-calculator"></i> Calculated Percentage:</span>
                        <strong id="calcPct" style="font-size: 1.05rem;">-- %</strong>
                    </div>

                    <div class="two-col">
                        <div class="form-group">
                            <label for="exam_date"><i class="fas fa-calendar-alt"></i> Exam Date *</label>
                            <input type="date" id="exam_date" name="exam_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="remarks"><i class="fas fa-comment"></i> Remarks</label>
                            <input type="text" id="remarks" name="remarks" class="form-control" placeholder="Excellent performance">
                        </div>
                    </div>

                    <button type="submit" name="save_result" class="btn-purple">
                        <i class="fas fa-paper-plane"></i> Publish &amp; Notify Result
                    </button>
                </form>
            </div>

            <!-- Published Marks Table -->
            <div class="page-card" style="overflow-x: auto;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap;">
                    <h3 style="color: var(--teacher-dark); font-size: 1.1rem; font-weight: 800; margin: 0;"><i class="fas fa-list-alt"></i> Published Exam Results</h3>
                    <span style="background: #ede9fe; color: #7c3aed; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 800;">
                        <?= $results_query ? $results_query->num_rows : 0 ?> Records
                    </span>
                </div>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Exam</th>
                            <th>Score</th>
                            <th>%</th>
                            <th>Remarks</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results_query && $results_query->num_rows > 0): ?>
                            <?php while ($r = $results_query->fetch_assoc()):
                                $score_val = (float)($r['score'] ?? 0);
                                $total_val = (float)($r['total_marks'] ?? 100);
                                $pct = ($total_val > 0) ? round(($score_val / $total_val) * 100, 1) : 0;
                                $res_date = !empty($r['exam_date']) ? $r['exam_date'] : (!empty($r['created_at']) ? $r['created_at'] : null);
                                $sc_class = $pct >= 60 ? 'score-high' : ($pct >= 40 ? 'score-mid' : 'score-low');
                                $grp = $r['academic_group'] ?? '';
                            ?>
                                <tr class="result-row">
                                    <td>
                                        <div style="font-weight: 800; color: #1e1b4b; font-size: 0.92rem;"><?= htmlspecialchars($r['student_name']) ?></div>
                                        <?php if ($grp): ?><span class="group-tag"><?= htmlspecialchars($grp) ?></span><?php endif; ?>
                                    </td>
                                    <td style="font-weight: 700; color: #334155;"><?= htmlspecialchars($r['exam_name']) ?></td>
                                    <td><strong><?= $score_val ?></strong> <span style="color:#94a3b8;">/<?= (int)$total_val ?></span></td>
                                    <td><span class="score-badge <?= $sc_class ?>"><?= $pct ?>%</span></td>
                                    <td style="color: #64748b; font-size: 0.85rem; max-width: 150px;"><?= htmlspecialchars($r['remarks'] ?? '-') ?></td>
                                    <td style="color: #94a3b8; font-size: 0.8rem; white-space:nowrap;"><?= $res_date ? date('M d, Y', strtotime($res_date)) : '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No exam results published yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function updateCalc() {
        const s = parseFloat(document.getElementById('score').value);
        const t = parseFloat(document.getElementById('total_marks').value);
        const el = document.getElementById('calcPct');
        if (s >= 0 && t > 0) {
            const p = ((s / t) * 100).toFixed(1);
            el.textContent = p + '%';
            el.style.color = p >= 60 ? '#16a34a' : (p >= 40 ? '#b45309' : '#dc2626');
        } else {
            el.textContent = '-- %';
            el.style.color = '#6d28d9';
        }
    }
    </script>
</body>
</html>
