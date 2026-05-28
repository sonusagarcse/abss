<?php
// parent/results.php - Parent Portal Academic Results

require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];

// 1. Fetch children
$children_query = $conn->prepare("SELECT * FROM students WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$children_query->bind_param("i", $pid);
$children_query->execute();
$children_res = $children_query->get_result();
$children = [];
while ($c = $children_res->fetch_assoc()) {
    $children[] = $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Performance | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .child-section { margin-bottom: 50px; background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); border: 1px solid #f0f4f8; }
        .performance-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .perf-card { background: #f8faff; border-radius: 20px; padding: 20px; text-align: center; border: 1px solid #eef2ff; }
        .perf-card h4 { margin: 0 0 10px 0; color: #5c6bc0; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .perf-card .value { font-size: 1.8rem; font-weight: 800; color: var(--portal-indigo); }
        .perf-card .sub-val { font-size: 0.8rem; color: #9aa5ce; font-weight: 600; margin-top: 5px; }
        .progress-ring-container { display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
        
        .badge-rank { background: #feeef2; color: #b71c1c; font-weight: 800; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Academic Performance</h1>
            <p>Track test evaluations, rank standings, and score cards of your ward(s).</p>
        </header>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 80px 40px;">
                <i class="fas fa-users-slash" style="font-size: 4rem; color: #9aa5ce; margin-bottom: 30px;"></i>
                <h2>No Students Linked</h2>
                <p>Please contact the school office to link your student accounts.</p>
            </div>
        <?php else: ?>
            <?php foreach ($children as $child): ?>
                <?php 
                $sid = (int)$child['id'];
                
                // Fetch results for this child
                $res_query = $conn->query("
                    SELECT * FROM results 
                    WHERE student_id = $sid 
                    ORDER BY exam_date DESC
                ");
                $results = [];
                $total_tests = 0;
                $sum_percent = 0;
                $best_score = 0;
                $best_exam = 'N/A';
                
                if ($res_query) {
                    while ($r = $res_query->fetch_assoc()) {
                        $results[] = $r;
                        $total_tests++;
                        
                        $pct = ($r['score'] / $r['total_marks']) * 100;
                        $sum_percent += $pct;
                        
                        if ($pct > $best_score) {
                            $best_score = $pct;
                            $best_exam = $r['exam_name'];
                        }
                    }
                }
                
                $avg_percent = $total_tests > 0 ? round($sum_percent / $total_tests, 1) : 0;
                ?>
                
                <div class="child-section">
                    <div style="border-bottom: 2px solid #f0f4f8; padding-bottom: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
                        <h2 style="margin:0; font-size:1.6rem; color:var(--portal-indigo); display:flex; align-items:center; gap:12px;">
                            <i class="fas fa-user-graduate" style="color:var(--portal-purple);"></i>
                            <?php echo htmlspecialchars($child['name']); ?>
                            <span style="font-size:0.85rem; font-weight:700; color:#5c6bc0; background:#f0f4f8; padding:5px 15px; border-radius:100px;">Class: <?php echo htmlspecialchars($child['class_admitted']); ?></span>
                        </h2>
                        <span style="font-weight:700; color:#9aa5ce; font-size:0.9rem;"><i class="far fa-calendar-check"></i> Enrollment Date: <?php echo date('d M, Y', strtotime($child['admission_date'])); ?></span>
                    </div>
                    
                    <!-- Performance Quick Stats -->
                    <div class="performance-overview">
                        <div class="perf-card">
                            <h4>Mock Assessments</h4>
                            <div class="value"><?php echo $total_tests; ?></div>
                            <div class="sub-val">Total Taken</div>
                        </div>
                        <div class="perf-card">
                            <h4>Average Grade</h4>
                            <div class="value" style="color:<?php echo $avg_percent >= 60 ? '#2e7d32' : ($avg_percent >= 40 ? '#ef6c00' : '#d32f2f'); ?>;"><?php echo $avg_percent; ?>%</div>
                            <div class="sub-val">Academic Average</div>
                        </div>
                        <div class="perf-card">
                            <h4>Top Performance</h4>
                            <div class="value" style="font-size:1.3rem; line-height:1.4; color:var(--portal-purple); height:45px; display:flex; align-items:center; justify-content:center;"><?php echo htmlspecialchars($best_exam); ?></div>
                            <div class="sub-val">Peak Score: <?php echo round($best_score, 1); ?>%</div>
                        </div>
                    </div>
                    
                    <!-- Results Table -->
                    <h3 style="font-size:1.1rem; margin-bottom:15px; color:var(--portal-indigo);"><i class="fas fa-history" style="margin-right:8px; opacity:0.7;"></i> Test Performance Log</h3>
                    <div class="portal-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Assessment Title</th>
                                    <th>Exam Date</th>
                                    <th>Marks Obtained</th>
                                    <th>Percentile</th>
                                    <th>Rank in Class</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#9aa5ce; padding: 30px;">No assessment records recorded for this child.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results as $res): ?>
                                        <?php 
                                        $percent = round(($res['score'] / $res['total_marks']) * 100, 1);
                                        $score_color = $percent >= 60 ? '#2e7d32' : ($percent >= 40 ? '#ef6c00' : '#d32f2f');
                                        ?>
                                        <tr>
                                            <td style="color:var(--portal-indigo); font-weight:800;"><?php echo htmlspecialchars($res['exam_name']); ?></td>
                                            <td><?php echo date('d F, Y', strtotime($res['exam_date'])); ?></td>
                                            <td style="font-weight:800; color:var(--portal-indigo);"><?php echo $res['score']; ?> <span style="font-weight:600; color:#9aa5ce;">/ <?php echo $res['total_marks']; ?></span></td>
                                            <td style="font-weight:800; color:<?php echo $score_color; ?>;"><?php echo $percent; ?>%</td>
                                            <td>
                                                <?php if ($res['rank']): ?>
                                                    <span class="badge-rank"><i class="fas fa-medal" style="margin-right:5px; color:#d4af37;"></i> Rank <?php echo $res['rank']; ?></span>
                                                <?php else: ?>
                                                    <span style="color:#9aa5ce; font-size:0.85rem; font-style:italic;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
