<?php
// parent/results.php - Beautiful Parent Portal Academic Results
require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];

// Fetch children
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
        /* ===== PAGE HEADER ===== */
        .results-page-header {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 60%, #4f46e5 100%);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            margin-bottom: 28px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .results-page-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }
        .results-page-header::after {
            content: '';
            position: absolute;
            bottom: -50px; left: 30%;
            width: 220px; height: 220px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .results-page-header h1 {
            font-size: 1.7rem;
            font-weight: 900;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
        }
        .results-page-header p {
            margin: 0;
            color: #ffffff;
            opacity: 0.88;
            font-size: 0.92rem;
            font-weight: 500;
        }

        /* ===== CHILD TABS ===== */
        .child-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .child-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            background: #fff;
            font-weight: 700;
            font-size: 0.88rem;
            color: #475569;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .child-tab-btn:hover {
            border-color: #6366f1;
            color: #6366f1;
        }
        .child-tab-btn.active {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 14px rgba(99,102,241,0.35);
        }
        .child-tab-btn .avatar-sm {
            width: 26px; height: 26px;
            border-radius: 50%;
            object-fit: cover;
        }
        .child-tab-btn .avatar-sm-fallback {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 900;
        }

        /* ===== CHILD SECTION ===== */
        .child-section { display: none; }
        .child-section.active { display: block; }

        /* ===== STUDENT PROFILE BANNER ===== */
        .student-profile-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 22px 26px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .student-avatar-lg {
            width: 68px; height: 68px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #6366f1;
            flex-shrink: 0;
        }
        .student-avatar-fallback-lg {
            width: 68px; height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 900;
            flex-shrink: 0;
            border: 3px solid #6366f1;
        }

        /* ===== KPI CARDS ===== */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .kpi-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            text-align: center;
            transition: transform 0.2s ease;
        }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-card .kpi-icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        .kpi-card .kpi-value {
            font-size: 1.7rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 5px;
        }
        .kpi-card .kpi-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ===== EXAM HISTORY ===== */
        .exam-history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .exam-history-header h3 {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Result Cards (Mobile & Desktop) */
        .result-card-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        @media (min-width: 640px) {
            .result-card-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1100px) {
            .result-card-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .result-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
            overflow: hidden;
            transition: all 0.25s ease;
            position: relative;
        }
        .result-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
        }
        .result-card-accent {
            height: 5px;
            width: 100%;
        }
        .result-card-body {
            padding: 18px 20px;
        }
        .result-card-exam {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .result-card-date {
            font-size: 0.78rem;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .result-score-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .result-marks {
            font-size: 1.4rem;
            font-weight: 900;
            color: #0f172a;
        }
        .result-marks span {
            font-size: 0.9rem;
            font-weight: 600;
            color: #94a3b8;
        }
        .pct-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 900;
            font-size: 0.88rem;
        }
        .pct-high { background: #dcfce7; color: #16a34a; }
        .pct-mid  { background: #fef3c7; color: #b45309; }
        .pct-low  { background: #fee2e2; color: #dc2626; }

        .rank-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            border-radius: 50px;
            padding: 3px 10px;
            font-size: 0.76rem;
            font-weight: 800;
            margin-top: 10px;
        }

        .remarks-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 8px 12px;
            margin-top: 10px;
            font-size: 0.8rem;
            color: #475569;
            font-weight: 600;
            border-left: 3px solid #e2e8f0;
            font-style: italic;
        }

        .no-results-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 50px 20px;
            text-align: center;
            border: 2px dashed #e2e8f0;
        }
        .no-results-box i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        .no-results-box p {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.95rem;
            margin: 0;
        }

        /* ===== DONUT CHART ===== */
        .chart-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        .donut-wrap {
            position: relative;
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }
        .donut-svg { transform: rotate(-90deg); }
        .donut-center {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .donut-center .val {
            font-size: 1.35rem;
            font-weight: 900;
            display: block;
        }
        .donut-center .lbl {
            font-size: 0.6rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .progress-grade-row {
            flex: 1;
            min-width: 160px;
        }
        .grade-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .grade-bar-bg {
            height: 8px;
            background: #f1f5f9;
            border-radius: 50px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .grade-bar-fill {
            height: 100%;
            border-radius: 50px;
            transition: width 1s ease;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header Banner -->
        <div class="results-page-header">
            <h1><i class="fas fa-trophy"></i> Academic Performance</h1>
            <p>View detailed exam results, score trends, and performance insights for your child.</p>
        </div>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-users-slash" style="font-size: 3.5rem; color: #94a3b8; margin-bottom: 20px; display: block;"></i>
                <h2 style="color: #334155;">No Students Linked</h2>
                <p style="color: #64748b;">Please contact the school office to link your student accounts.</p>
            </div>
        <?php else: ?>
            <!-- Child Selector Tabs (if multiple children) -->
            <?php if (count($children) > 1): ?>
            <div class="child-tabs">
                <?php foreach ($children as $ci => $ch): 
                    $photo_file = $ch['student_photo'] ?? $ch['photo'] ?? '';
                    $has_photo = !empty($photo_file) && file_exists(__DIR__ . '/../' . $photo_file);
                    $initials = strtoupper(substr($ch['name'], 0, 2));
                ?>
                    <button class="child-tab-btn <?= $ci === 0 ? 'active' : '' ?>" onclick="switchChild(<?= $ci ?>)" id="tab-btn-<?= $ci ?>">
                        <?php if ($has_photo): ?>
                            <img src="../<?= htmlspecialchars($photo_file) ?>" class="avatar-sm" alt="">
                        <?php else: ?>
                            <div class="avatar-sm-fallback"><?= $initials ?></div>
                        <?php endif; ?>
                        <?= htmlspecialchars($ch['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Child Sections -->
            <?php foreach ($children as $ci => $child): ?>
                <?php
                $sid = (int)$child['id'];

                // Fetch results
                $res_query = $conn->query("
                    SELECT * FROM results 
                    WHERE student_id = $sid 
                    ORDER BY COALESCE(exam_date, DATE(created_at)) DESC, id DESC
                ");
                $results = [];
                $total_tests = 0;
                $sum_percent = 0;
                $best_score_pct = 0;
                $best_exam = 'N/A';
                $cnt_high = 0; // >= 75%
                $cnt_pass = 0; // >= 40%
                $cnt_low = 0;  // < 40%
                
                if ($res_query) {
                    while ($r = $res_query->fetch_assoc()) {
                        $results[] = $r;
                        $total_tests++;
                        $pct_r = ($r['total_marks'] > 0) ? (($r['score'] / $r['total_marks']) * 100) : 0;
                        $sum_percent += $pct_r;
                        if ($pct_r >= 75) $cnt_high++;
                        elseif ($pct_r >= 40) $cnt_pass++;
                        else $cnt_low++;
                        if ($pct_r > $best_score_pct) {
                            $best_score_pct = $pct_r;
                            $best_exam = $r['exam_name'];
                        }
                    }
                }
                $avg_percent = $total_tests > 0 ? round($sum_percent / $total_tests, 1) : 0;
                $grade = $avg_percent >= 75 ? 'A+' : ($avg_percent >= 60 ? 'A' : ($avg_percent >= 45 ? 'B' : ($avg_percent >= 33 ? 'C' : 'D')));
                $grade_color = $avg_percent >= 60 ? '#16a34a' : ($avg_percent >= 40 ? '#d97706' : '#dc2626');

                $photo_file = $child['student_photo'] ?? $child['photo'] ?? '';
                $has_photo_main = !empty($photo_file) && file_exists(__DIR__ . '/../' . $photo_file);
                $initials_main = strtoupper(substr($child['name'], 0, 2));
                
                // Donut chart arc calculation
                $donut_pct = min(100, max(0, $avg_percent));
                $radius = 40;
                $circ = 2 * M_PI * $radius;
                $stroke_offset = $circ * (1 - $donut_pct / 100);
                $donut_color = $avg_percent >= 60 ? '#16a34a' : ($avg_percent >= 40 ? '#d97706' : '#dc2626');
                ?>

                <div class="child-section <?= $ci === 0 ? 'active' : '' ?>" id="child-section-<?= $ci ?>">
                    <!-- Student Profile Card -->
                    <div class="student-profile-card">
                        <?php if ($has_photo_main): ?>
                            <img src="../<?= htmlspecialchars($photo_file) ?>" class="student-avatar-lg" alt="">
                        <?php else: ?>
                            <div class="student-avatar-fallback-lg"><?= $initials_main ?></div>
                        <?php endif; ?>

                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 1.2rem; font-weight: 900; color: #0f172a;"><?= htmlspecialchars($child['name']) ?></div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                                <?php if (!empty($child['reg_no'])): ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 50px; font-size: 0.76rem; font-weight: 800;"><?= htmlspecialchars($child['reg_no']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($child['class_admitted'])): ?>
                                    <span style="background: #eff6ff; color: #2563eb; padding: 3px 10px; border-radius: 50px; font-size: 0.76rem; font-weight: 800;">Class: <?= htmlspecialchars($child['class_admitted']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($child['academic_group'])): ?>
                                    <span style="background: #f5f3ff; color: #7c3aed; padding: 3px 10px; border-radius: 50px; font-size: 0.76rem; font-weight: 800;"><?= htmlspecialchars($child['academic_group']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($total_tests > 0): ?>
                        <div style="text-align: center; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border-radius: 14px; padding: 14px 20px; flex-shrink: 0;">
                            <div style="font-size: 2rem; font-weight: 900; line-height: 1;"><?= $grade ?></div>
                            <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; opacity: 0.85; margin-top: 3px;">Grade</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_tests > 0): ?>
                    <!-- Performance Overview with Donut -->
                    <div class="chart-card">
                        <!-- Donut -->
                        <div class="donut-wrap">
                            <svg class="donut-svg" width="100" height="100" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="<?= $radius ?>" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                                <circle cx="50" cy="50" r="<?= $radius ?>" fill="none" stroke="<?= $donut_color ?>" stroke-width="12"
                                    stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $stroke_offset ?>"
                                    stroke-linecap="round" style="transition: stroke-dashoffset 1.2s ease;"/>
                            </svg>
                            <div class="donut-center">
                                <span class="val" style="color: <?= $donut_color ?>;"><?= $avg_percent ?>%</span>
                                <span class="lbl">Average</span>
                            </div>
                        </div>

                        <!-- Grade Bar Breakdown -->
                        <div class="progress-grade-row">
                            <div style="font-size: 0.88rem; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Performance Breakdown</div>
                            
                            <div class="grade-label-row">
                                <span style="color: #16a34a;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Excellent (≥75%)</span>
                                <span><?= $cnt_high ?> test<?= $cnt_high != 1 ? 's' : '' ?></span>
                            </div>
                            <div class="grade-bar-bg">
                                <div class="grade-bar-fill" style="width: <?= $total_tests > 0 ? round(($cnt_high/$total_tests)*100) : 0 ?>%; background: #16a34a;"></div>
                            </div>

                            <div class="grade-label-row">
                                <span style="color: #d97706;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Pass (40–74%)</span>
                                <span><?= $cnt_pass ?> test<?= $cnt_pass != 1 ? 's' : '' ?></span>
                            </div>
                            <div class="grade-bar-bg">
                                <div class="grade-bar-fill" style="width: <?= $total_tests > 0 ? round(($cnt_pass/$total_tests)*100) : 0 ?>%; background: #d97706;"></div>
                            </div>

                            <div class="grade-label-row">
                                <span style="color: #dc2626;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Below Pass (<40%)</span>
                                <span><?= $cnt_low ?> test<?= $cnt_low != 1 ? 's' : '' ?></span>
                            </div>
                            <div class="grade-bar-bg">
                                <div class="grade-bar-fill" style="width: <?= $total_tests > 0 ? round(($cnt_low/$total_tests)*100) : 0 ?>%; background: #dc2626;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick KPI Row -->
                    <div class="kpi-row">
                        <div class="kpi-card">
                            <div class="kpi-icon">📝</div>
                            <div class="kpi-value" style="color: #4f46e5;"><?= $total_tests ?></div>
                            <div class="kpi-label">Exams Taken</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">📊</div>
                            <div class="kpi-value" style="color: <?= $grade_color ?>;"><?= $avg_percent ?>%</div>
                            <div class="kpi-label">Avg Score</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">🏆</div>
                            <div class="kpi-value" style="color: #d97706; font-size: 1.2rem;"><?= htmlspecialchars($best_exam) ?></div>
                            <div class="kpi-label">Best Exam (<?= round($best_score_pct, 1) ?>%)</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon">⭐</div>
                            <div class="kpi-value" style="color: #7c3aed; font-size: 2rem;"><?= $cnt_high ?></div>
                            <div class="kpi-label">Excellent Scores</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Exam History -->
                    <div class="exam-history-header">
                        <h3><i class="fas fa-history" style="color: #6366f1;"></i> Exam History</h3>
                        <?php if ($total_tests > 0): ?>
                            <span style="background: #ede9fe; color: #7c3aed; font-size: 0.78rem; font-weight: 800; padding: 4px 12px; border-radius: 50px;"><?= $total_tests ?> Records</span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="no-results-box">
                            <i class="fas fa-file-alt"></i>
                            <h3 style="color: #475569; margin: 0 0 8px 0;">No Results Published Yet</h3>
                            <p>Exam results will appear here once published by the school.</p>
                        </div>
                    <?php else: ?>
                        <div class="result-card-grid">
                            <?php foreach ($results as $res):
                                $sc = (float)$res['score'];
                                $tm = (float)$res['total_marks'];
                                $pct = ($tm > 0) ? round(($sc / $tm) * 100, 1) : 0;
                                $pct_class = $pct >= 60 ? 'pct-high' : ($pct >= 40 ? 'pct-mid' : 'pct-low');
                                $accent_col = $pct >= 60 ? '#16a34a' : ($pct >= 40 ? '#d97706' : '#dc2626');
                                $res_date = !empty($res['exam_date']) ? $res['exam_date'] : (!empty($res['created_at']) ? $res['created_at'] : null);
                            ?>
                                <div class="result-card">
                                    <div class="result-card-accent" style="background: <?= $accent_col ?>;"></div>
                                    <div class="result-card-body">
                                        <div class="result-card-exam"><?= htmlspecialchars($res['exam_name']) ?></div>
                                        <div class="result-card-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?= $res_date ? date('d M, Y', strtotime($res_date)) : 'Date not specified' ?>
                                        </div>
                                        <div class="result-score-row">
                                            <div class="result-marks"><?= $sc ?><span> / <?= (int)$tm ?></span></div>
                                            <span class="pct-badge <?= $pct_class ?>"><?= $pct ?>%</span>
                                        </div>
                                        <?php if (!empty($res['rank'])): ?>
                                            <div><span class="rank-chip"><i class="fas fa-medal"></i> Rank #<?= (int)$res['rank'] ?></span></div>
                                        <?php endif; ?>
                                        <?php if (!empty($res['remarks'])): ?>
                                            <div class="remarks-box"><i class="fas fa-quote-left" style="color:#94a3b8; margin-right:4px; font-size:0.7rem;"></i><?= htmlspecialchars($res['remarks']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div><!-- end child-section -->
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
    function switchChild(idx) {
        document.querySelectorAll('.child-section').forEach((el, i) => {
            el.classList.toggle('active', i === idx);
        });
        document.querySelectorAll('.child-tab-btn').forEach((btn, i) => {
            btn.classList.toggle('active', i === idx);
        });
    }
    </script>
</body>
</html>
