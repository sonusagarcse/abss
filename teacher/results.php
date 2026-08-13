<?php
require_once 'includes/auth.php';

$msg = '';
$err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_result'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $exam_name = trim($_POST['exam_name'] ?? '');
    $score = isset($_POST['marks_obtained']) ? (float)$_POST['marks_obtained'] : (isset($_POST['score']) ? (float)$_POST['score'] : 0);
    $total_marks = (float)($_POST['total_marks'] ?? 100);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($student_id > 0 && !empty($exam_name)) {
        // Auto-detect columns for maximum DB compatibility
        $colCheck = $conn->query("SHOW COLUMNS FROM results LIKE 'score'");
        $scoreCol = ($colCheck && $colCheck->num_rows > 0) ? 'score' : 'marks_obtained';
        
        $remCheck = $conn->query("SHOW COLUMNS FROM results LIKE 'remarks'");
        $hasRemarks = ($remCheck && $remCheck->num_rows > 0);

        if ($hasRemarks) {
            $stmt = $conn->prepare("INSERT INTO results (student_id, exam_name, {$scoreCol}, total_marks, remarks) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdds", $student_id, $exam_name, $score, $total_marks, $remarks);
        } else {
            $stmt = $conn->prepare("INSERT INTO results (student_id, exam_name, {$scoreCol}, total_marks) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isdd", $student_id, $exam_name, $score, $total_marks);
        }

        if ($stmt && $stmt->execute()) {
            $msg = "Exam marks recorded successfully for student.";
        } else {
            $err = "Error recording marks: " . $conn->error;
        }
    } else {
        $err = "Please select a student and enter exam name.";
    }
}

// Fetch results
$results_query = $conn->query("
    SELECT r.*, s.name as student_name 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    ORDER BY r.id DESC
");

// Fetch active students for dropdown
$students_list = $conn->query("SELECT id, name FROM students WHERE status = 'active' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .grid-layout { display: grid; grid-template-columns: 350px 1fr; gap: 24px; margin-top: 20px; }
        @media (max-width: 992px) { .grid-layout { grid-template-columns: 1fr; } }
        .page-card { background: #ffffff; border-radius: 20px; padding: 28px; border: 1px solid #ede9fe; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 10px 14px; border: 2px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; outline: none; box-sizing: border-box; }
        .form-control:focus { border-color: var(--teacher-purple); }
        .btn-purple { width: 100%; background: var(--teacher-purple); color: white; padding: 12px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-purple:hover { background: var(--teacher-dark); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #ede9fe; font-size: 0.9rem; }
        th { background: #f5f3ff; color: var(--teacher-dark); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <h1 style="font-size: 1.8rem; color: var(--teacher-dark); font-weight: 800;">Student Test Results</h1>
            <p style="color: #64748b; margin-top: 4px;">Record and publish student exam scores.</p>
        </header>

        <?php if ($msg): ?>
            <div style="background:#dcfce7; color:#166534; padding:15px 25px; border-radius:14px; margin-top:20px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:15px 25px; border-radius:14px; margin-top:20px; font-weight:700; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <div class="grid-layout">
            <!-- Add Marks Form -->
            <div class="page-card">
                <h3 style="color: var(--teacher-dark); margin-bottom: 18px; font-size: 1.1rem;"><i class="fas fa-plus-circle"></i> Enter Marks</h3>
                <form action="results.php" method="POST">
                    <div class="form-group">
                        <label for="student_id">Select Student *</label>
                        <select id="student_id" name="student_id" class="form-control" required>
                            <option value="">-- Choose Student --</option>
                            <?php if ($students_list): ?>
                                <?php while ($st = $students_list->fetch_assoc()): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?> (#<?= $st['id'] ?>)</option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exam_name">Exam Name *</label>
                        <input type="text" id="exam_name" name="exam_name" class="form-control" placeholder="e.g. Unit Test 1" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label for="marks_obtained">Marks *</label>
                            <input type="number" step="0.1" id="marks_obtained" name="marks_obtained" class="form-control" placeholder="85" required>
                        </div>
                        <div class="form-group">
                            <label for="total_marks">Total *</label>
                            <input type="number" step="0.1" id="total_marks" name="total_marks" class="form-control" placeholder="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Teacher Remarks</label>
                        <input type="text" id="remarks" name="remarks" class="form-control" placeholder="Excellent performance">
                    </div>

                    <button type="submit" name="save_result" class="btn-purple"><i class="fas fa-paper-plane"></i> Submit Marks</button>
                </form>
            </div>

            <!-- Published Marks Table -->
            <div class="page-card" style="overflow-x: auto;">
                <h3 style="color: var(--teacher-dark); margin-bottom: 18px; font-size: 1.1rem;"><i class="fas fa-list"></i> Recorded Test Results</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Exam</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Remarks</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results_query && $results_query->num_rows > 0): ?>
                            <?php while ($r = $results_query->fetch_assoc()): 
                                $score_val = $r['score'] ?? $r['marks_obtained'] ?? 0;
                                $total_val = $r['total_marks'] ?? 100;
                                $pct = ($total_val > 0) ? round(($score_val / $total_val) * 100, 1) : 0;
                                $res_date = $r['exam_date'] ?? $r['created_at'] ?? null;
                            ?>
                                <tr>
                                    <td style="font-weight: 700; color: #1e1b4b;"><?= htmlspecialchars($r['student_name']) ?></td>
                                    <td><?= htmlspecialchars($r['exam_name']) ?></td>
                                    <td><strong><?= $score_val ?></strong> / <?= $total_val ?></td>
                                    <td>
                                        <span style="font-weight:700; color: <?= $pct >= 40 ? '#166534' : '#b91c1c' ?>;"><?= $pct ?>%</span>
                                    </td>
                                    <td style="color: #64748b; font-size: 0.85rem;"><?= htmlspecialchars($r['remarks'] ?? '-') ?></td>
                                    <td style="color: #94a3b8; font-size: 0.8rem;"><?= $res_date ? date('M d, Y', strtotime($res_date)) : '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No exam marks recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
