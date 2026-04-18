<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Result Entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_result'])) {
    $sid = $_POST['student_id'];
    $exam = $_POST['exam_name'];
    $score = $_POST['score'];
    $total = $_POST['total_marks'];
    $rank = $_POST['rank'];
    $date = $_POST['exam_date'];

    $stmt = $conn->prepare("INSERT INTO results (student_id, exam_name, score, total_marks, rank, exam_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdiis", $sid, $exam, $score, $total, $rank, $date);
    $stmt->execute();
    $msg = "Examination result recorded.";
}

$students = $conn->query("SELECT id, name FROM students WHERE status = 'active' ORDER BY name ASC");
$results = $conn->query("
    SELECT r.*, s.name 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    ORDER BY r.exam_date DESC LIMIT 15
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
        .result-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .result-table th { text-align: left; padding: 0 25px 10px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; }
        .result-row td { padding: 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; font-weight: 600; color: #5c6bc0; }
        .result-row td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        .result-row td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .rank-badge { width: 32px; height: 32px; background: var(--portal-blue); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Academic Performance</h1>
            <p>Record and publish mock test results.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-medal"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <div class="portal-card">
                <h3 style="margin-bottom: 30px;">Post Result</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Student</label>
                        <select name="student_id" required>
                            <option value="">Select Student...</option>
                            <?php while($s = $students->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Exam Name</label>
                        <input type="text" name="exam_name" placeholder="e.g. Sainik Mock-01" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="portal-input-group">
                            <label>Marks</label>
                            <input type="number" step="0.01" name="score" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Out Of</label>
                            <input type="number" name="total_marks" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="portal-input-group">
                            <label>Rank</label>
                            <input type="number" name="rank">
                        </div>
                        <div class="portal-input-group">
                            <label>Exam Date</label>
                            <input type="date" name="exam_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="save_result" class="btn-portal w-100">Publish Result</button>
                </form>
            </div>

            <div class="list-section">
                <h3 style="margin-bottom: 25px; padding-left: 20px;">Recent Performances</h3>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Module</th>
                            <th>Score / Rank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $results->fetch_assoc()): ?>
                            <tr class="result-row">
                                <td style="color:var(--portal-blue); font-weight:800;"><?php echo htmlspecialchars($r['name']); ?></td>
                                <td>
                                    <div style="font-weight:700; color:var(--portal-blue);"><?php echo $r['exam_name']; ?></div>
                                    <div style="font-size:0.75rem; opacity:0.6;"><?php echo date('d M, Y', strtotime($r['exam_date'])); ?></div>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:15px;">
                                        <span style="font-weight:800;"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></span>
                                        <?php if($r['rank']): ?>
                                            <span class="rank-badge"><?php echo $r['rank']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
