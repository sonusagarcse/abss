<?php
// parent/results.php - Mobile-Friendly Parent Portal Academic Results
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
    <title>Academic Performance | ABSS Parent Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .child-section { 
            margin-bottom: 40px; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            border: 1px solid #e2e8f0; 
        }

        .student-header-banner {
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 20px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            flex-wrap: wrap; 
            gap: 15px;
        }

        .performance-overview { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 18px; 
            margin-bottom: 30px; 
        }

        .perf-card { 
            background: #f8fafc; 
            border-radius: 16px; 
            padding: 20px; 
            text-align: center; 
            border: 1px solid #e2e8f0; 
        }
        .perf-card h4 { 
            margin: 0 0 8px 0; 
            color: #64748b; 
            font-size: 0.78rem; 
            text-transform: uppercase; 
            font-weight: 800; 
            letter-spacing: 0.05em; 
        }
        .perf-card .value { 
            font-size: 1.8rem; 
            font-weight: 900; 
            color: var(--portal-dark); 
        }
        .perf-card .sub-val { 
            font-size: 0.8rem; 
            color: #64748b; 
            font-weight: 700; 
            margin-top: 4px; 
        }
        
        .badge-rank { 
            background: #fef3c7; 
            color: #b45309; 
            font-weight: 800; 
            padding: 5px 12px; 
            border-radius: 8px; 
            font-size: 0.78rem; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #fde68a;
        }

        .score-pill {
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.85rem;
            display: inline-block;
        }

        /* Mobile Responsive Rules (max-width: 640px) */
        @media (max-width: 640px) {
            .child-section { padding: 20px 15px; border-radius: 18px; }
            .student-header-banner { flex-direction: column; align-items: flex-start; gap: 8px; }
            .performance-overview { grid-template-columns: 1fr; gap: 12px; }
            .perf-card { padding: 15px; border-radius: 12px; }
            
            .desktop-table-view { display: none !important; }
            .mobile-card-list { display: block !important; }
        }

        @media (min-width: 641px) {
            .mobile-card-list { display: none !important; }
            .desktop-table-view { display: block !important; }
        }

        .mobile-result-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            border-left: 4px solid var(--portal-blue);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 30px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Academic Performance</h1>
            <p style="margin: 0;">Track test evaluations, rank standings, and score cards for your child.</p>
        </header>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-users-slash" style="font-size: 3.5rem; color: #94a3b8; margin-bottom: 20px;"></i>
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
                    <!-- Student Header -->
                    <div class="student-header-banner">
                        <h2 style="margin:0; font-size:1.4rem; color:var(--portal-dark); display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <i class="fas fa-user-graduate" style="color:var(--portal-blue);"></i>
                            <?php echo htmlspecialchars($child['name']); ?>
                            <span style="font-size:0.8rem; font-weight:800; color:#2563eb; background:#eff6ff; padding:4px 12px; border-radius:50px;">Class: <?php echo htmlspecialchars($child['class_admitted']); ?></span>
                        </h2>
                        <span style="font-weight:700; color:#64748b; font-size:0.88rem;"><i class="far fa-calendar-check"></i> Enrolled: <?php echo date('d M, Y', strtotime($child['admission_date'])); ?></span>
                    </div>
                    
                    <!-- Performance Quick Stats Cards -->
                    <div class="performance-overview">
                        <div class="perf-card">
                            <h4>Assessments Taken</h4>
                            <div class="value" style="color:var(--portal-blue);"><?php echo $total_tests; ?></div>
                            <div class="sub-val">Exams Completed</div>
                        </div>
                        
                        <div class="perf-card">
                            <h4>Academic Average</h4>
                            <div class="value" style="color:<?php echo $avg_percent >= 60 ? '#15803d' : ($avg_percent >= 40 ? '#d97706' : '#dc2626'); ?>;"><?php echo $avg_percent; ?>%</div>
                            <div class="sub-val">Overall Score Grade</div>
                        </div>
                        
                        <div class="perf-card">
                            <h4>Top Performance</h4>
                            <div class="value" style="font-size:1.2rem; color:#7c3aed; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($best_exam); ?></div>
                            <div class="sub-val" style="color:#7c3aed; font-weight:800;">Peak Score: <?php echo round($best_score, 1); ?>%</div>
                        </div>
                    </div>
                    
                    <!-- Section Title -->
                    <h3 style="font-size:1.1rem; margin-bottom:15px; color:var(--portal-dark); display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-award" style="color:var(--portal-blue);"></i> Assessment Performance History
                    </h3>

                    <!-- Desktop View Table -->
                    <div class="desktop-table-view portal-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Assessment Title</th>
                                    <th>Exam Date</th>
                                    <th>Marks Obtained</th>
                                    <th>Percentage</th>
                                    <th>Class Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#94a3b8; padding: 25px;">No assessment records recorded for this child.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results as $res): ?>
                                        <?php 
                                        $percent = round(($res['score'] / $res['total_marks']) * 100, 1);
                                        $bg_color = $percent >= 60 ? '#dcfce7' : ($percent >= 40 ? '#fef3c7' : '#fee2e2');
                                        $text_color = $percent >= 60 ? '#15803d' : ($percent >= 40 ? '#b45309' : '#dc2626');
                                        ?>
                                        <tr>
                                            <td style="color:var(--portal-dark); font-weight:800;"><?php echo htmlspecialchars($res['exam_name']); ?></td>
                                            <td><?php echo date('d M, Y', strtotime($res['exam_date'])); ?></td>
                                            <td style="font-weight:800; color:var(--portal-dark);"><?php echo $res['score']; ?> <span style="font-weight:600; color:#64748b;">/ <?php echo $res['total_marks']; ?></span></td>
                                            <td>
                                                <span class="score-pill" style="background:<?php echo $bg_color; ?>; color:<?php echo $text_color; ?>;">
                                                    <?php echo $percent; ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($res['rank']): ?>
                                                    <span class="badge-rank"><i class="fas fa-medal" style="color:#d97706;"></i> Rank #<?php echo $res['rank']; ?></span>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8; font-size:0.85rem;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View Touch Cards -->
                    <div class="mobile-card-list">
                        <?php if (empty($results)): ?>
                            <div style="background:#f8fafc; color:#94a3b8; padding:20px; border-radius:12px; text-align:center;">
                                No assessment records recorded.
                            </div>
                        <?php else: ?>
                            <?php foreach ($results as $res): ?>
                                <?php 
                                $percent = round(($res['score'] / $res['total_marks']) * 100, 1);
                                $bg_color = $percent >= 60 ? '#dcfce7' : ($percent >= 40 ? '#fef3c7' : '#fee2e2');
                                $text_color = $percent >= 60 ? '#15803d' : ($percent >= 40 ? '#b45309' : '#dc2626');
                                ?>
                                <div class="mobile-result-card" style="border-left-color:<?php echo $text_color; ?>;">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; gap:10px;">
                                        <strong style="font-size:0.98rem; color:var(--portal-dark); line-height:1.3;"><?php echo htmlspecialchars($res['exam_name']); ?></strong>
                                        <span class="score-pill" style="background:<?php echo $bg_color; ?>; color:<?php echo $text_color; ?>; flex-shrink:0;">
                                            <?php echo $percent; ?>%
                                        </span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.84rem; color:#64748b; margin-top:10px; flex-wrap:wrap; gap:6px;">
                                        <span>Marks: <strong style="color:var(--portal-dark);"><?php echo $res['score']; ?> / <?php echo $res['total_marks']; ?></strong></span>
                                        <span><?php echo date('d M, Y', strtotime($res['exam_date'])); ?></span>
                                    </div>
                                    <?php if ($res['rank']): ?>
                                        <div style="margin-top:10px;">
                                            <span class="badge-rank"><i class="fas fa-medal" style="color:#d97706;"></i> Rank #<?php echo $res['rank']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
