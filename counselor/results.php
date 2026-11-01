<?php
// counselor/results.php - Academic Examination Results & Rank Cards
require_once 'includes/auth.php';
require_counselor_module('results');

$search = trim($_GET['search'] ?? '');
$sql = "SELECT r.*, s.name as student_name, s.reg_no, s.target_school 
        FROM results r 
        LEFT JOIN students s ON r.student_id = s.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR s.reg_no LIKE ? OR r.exam_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY r.exam_date DESC, r.id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .form-input { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.88rem; font-family: inherit; background: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-award" style="color: #9333ea;"></i> Examination Results &amp; Academic Scores
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Review student performance records for student-parent performance counseling.
                    </p>
                </div>
            </div>

            <div class="portal-card">
                <form method="GET" action="results.php" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student name, reg no or exam name..." class="form-input" style="flex: 1; min-width: 250px;">
                    <button type="submit" class="btn-portal btn-teal">
                        <i class="fas fa-search"></i> Search Results
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="results.php" class="btn-portal btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Examination</th>
                                <th>Exam Date</th>
                                <th>Score / Total Marks</th>
                                <th>Percentage</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$results || $results->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        No exam result records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($res = $results->fetch_assoc()): ?>
                                    <?php 
                                    $score = (float)$res['score'];
                                    $total = (float)$res['total_marks'];
                                    $pct = $total > 0 ? round(($score / $total) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a;">
                                                <?php echo htmlspecialchars($res['student_name'] ?: 'Student #' . $res['student_id']); ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                <?php echo htmlspecialchars($res['reg_no'] ?: ''); ?> • <?php echo htmlspecialchars($res['target_school'] ?: ''); ?>
                                            </div>
                                        </td>
                                        <td style="font-weight: 700; color: #1e293b;">
                                            <?php echo htmlspecialchars($res['exam_name']); ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo !empty($res['exam_date']) ? date('d M Y', strtotime($res['exam_date'])) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 800; color: #0f172a;">
                                                <?php echo $score; ?> / <?php echo $total; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="background: <?php echo $pct >= 60 ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;'; ?> padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.8rem;">
                                                <?php echo $pct; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: #0284c7;">
                                                <?php echo !empty($res['rank']) ? '#' . $res['rank'] : '-'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
