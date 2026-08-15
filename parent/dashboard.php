<?php
// parent/dashboard.php - Ultra-Modern Parent Portal Homepage Dashboard

require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];

// 1. Fetch children
$children_query = $conn->prepare("SELECT * FROM students WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$children_query->bind_param("i", $pid);
$children_query->execute();
$children_res = $children_query->get_result();
$children = [];
$children_ids = [];
while ($c = $children_res->fetch_assoc()) {
    $children[] = $c;
    $children_ids[] = (int)$c['id'];
}

// 2. Fetch stats (Payments & Dues & Results)
$total_paid = 0;
$recent_results = [];
$outstanding_dues = 0;

if (!empty($children_ids)) {
    $ids_str = implode(',', $children_ids);
    
    // Fee payments total
    $fee_query = $conn->query("SELECT SUM(amount) AS total_paid FROM fee_payments WHERE student_id IN ($ids_str)");
    if ($fee_query && $row = $fee_query->fetch_assoc()) {
        $total_paid = (float)$row['total_paid'];
    }
    
    // Outstanding dues total
    $dues_query = $conn->query("SELECT SUM(amount) AS total_dues FROM fees_generated WHERE student_id IN ($ids_str) AND status = 'unpaid'");
    if ($dues_query && $row = $dues_query->fetch_assoc()) {
        $outstanding_dues = (float)$row['total_dues'];
    }
    
    // Recent results
    $res_query = $conn->query("
        SELECT r.*, s.name AS student_name 
        FROM results r 
        JOIN students s ON r.student_id = s.id 
        WHERE s.id IN ($ids_str)
        ORDER BY r.id DESC LIMIT 5
    ");
    if ($res_query) {
        while ($r = $res_query->fetch_assoc()) {
            $recent_results[] = $r;
        }
    }
}

// Fetch notices
$notices_res = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 3");
$recent_notices = [];
if ($notices_res) {
    while ($n = $notices_res->fetch_assoc()) {
        $recent_notices[] = $n;
    }
}

$parent_name = $_SESSION['parent_name'] ?? 'Parent Profile';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .children-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 30px; }
        .child-card { 
            background: #ffffff; 
            border-radius: var(--radius-lg); 
            padding: 20px; 
            border: 1px solid var(--card-border); 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
            transition: transform 0.25s ease;
        }
        .child-card:hover { transform: translateY(-3px); }
        .child-avatar { 
            width: 52px; 
            height: 52px; 
            border-radius: 50%; 
            background: var(--portal-accent); 
            color: var(--portal-purple); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.2rem; 
            font-weight: 800; 
            flex-shrink: 0;
            border: 2px solid #ede9fe;
        }
        .child-details h4 { margin: 0 0 4px; color: var(--portal-indigo); font-weight: 800; font-size: 1.05rem; }
        .child-details span { font-size: 0.82rem; color: #64748b; font-weight: 600; }

        .notice-item { 
            background: #f8fafc; 
            border-radius: var(--radius-md); 
            padding: 16px; 
            border-left: 4px solid var(--portal-purple); 
            margin-bottom: 14px; 
            transition: transform 0.2s;
        }
        .notice-item:hover { transform: translateX(4px); }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Hero Greeting Banner -->
        <div class="hero-welcome-card">
            <div>
                <h1>Welcome Back, <?= htmlspecialchars($parent_name) ?>! 👋</h1>
                <p><i class="fas fa-child"></i> Parent space monitoring <?= count($children) ?> registered ward(s) academic progress and billing ledger.</p>
            </div>
            <div>
                <a href="fees.php" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 22px; border-radius: 14px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
                    <i class="fas fa-wallet"></i> Pay Dues & Ledger
                </a>
            </div>
        </div>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-users-slash" style="font-size: 3.5rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                <h2>No Children Associated</h2>
                <p style="max-width: 480px; margin: 0 auto 24px;">Your parent account has not been mapped to any student profile yet. Please contact administration to link your child's profile.</p>
                <a href="mailto:abssimamganj@gmail.com" class="shortcut-card" style="display:inline-flex; width:auto;"><i class="fas fa-envelope"></i> Contact Administration</a>
            </div>
        <?php else: ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#ede9fe; color:var(--portal-purple);"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h3><?= count($children) ?></h3>
                        <span>Registered Wards</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:#dcfce7; color:#15803d;"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3>₹ <?= number_format($total_paid, 2) ?></h3>
                        <span>Tuition Paid</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2; color:#b91c1c;"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stat-info">
                        <h3 style="color:#b91c1c;">₹ <?= number_format($outstanding_dues, 2) ?></h3>
                        <span>Outstanding Dues</span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Shortcuts -->
            <div class="quick-shortcut-grid">
                <a href="fees.php" class="shortcut-card">
                    <i class="fas fa-wallet"></i> Pay Dues & Ledger
                </a>
                <a href="results.php" class="shortcut-card">
                    <i class="fas fa-chart-line"></i> View Exam Marks
                </a>
                <a href="gallery.php" class="shortcut-card">
                    <i class="fas fa-photo-video"></i> Gallery & Videos
                </a>
                <a href="documents.php" class="shortcut-card">
                    <i class="fas fa-file-upload"></i> Upload Documents
                </a>
                <a href="tickets.php" class="shortcut-card">
                    <i class="fas fa-headset"></i> Support Helpdesk
                </a>
            </div>

            <!-- Child Registry Cards -->
            <h3 style="font-size: 1.2rem; margin-bottom: 16px;"><i class="fas fa-children" style="color:var(--portal-purple); margin-right:8px;"></i> Registered Ward Profiles</h3>
            <div class="children-grid">
                <?php foreach ($children as $c): ?>
                    <div class="child-card">
                        <div class="child-avatar">
                            <?php if (!empty($c['student_photo'])): ?>
                                <img src="../<?= htmlspecialchars($c['student_photo']) ?>" 
                                     alt="<?= htmlspecialchars($c['name']) ?>"
                                     style="width:52px; height:52px; object-fit:cover; border-radius:50%;">
                            <?php else: ?>
                                <?= htmlspecialchars(substr($c['name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="child-details">
                            <h4><?= htmlspecialchars($c['name']) ?></h4>
                            <span>Class: <strong><?= htmlspecialchars($c['class_admitted']) ?></strong></span><br>
                            <span>Target: <?= htmlspecialchars($c['target_school'] ?: 'Netarhat Preparation') ?></span><br>
                            <span class="badge badge-purple" style="margin-top:6px;"><i class="fas fa-hotel"></i> <?= htmlspecialchars($c['scholar_mode'] ?? 'Day Scholar') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2 Column Layout (Assessments & Notices) -->
            <div class="dashboard-grid" style="min-width: 0; width: 100%;">
                <!-- Left: Recent Assessments -->
                <div class="portal-card" style="min-width: 0; overflow: hidden;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="font-size: 1.15rem; margin:0;"><i class="fas fa-award" style="color:var(--portal-purple); margin-right:8px;"></i> Recent Assessment Results</h3>
                        <a href="results.php" style="color:var(--portal-purple); text-decoration:none; font-weight:700; font-size:0.88rem;">View All <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="table-responsive" style="width:100%; min-width:0; overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Child</th>
                                    <th>Assessment</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_results)): ?>
                                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 25px;">No exam scorecards recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_results as $r): 
                                        $score_val = $r['score'] ?? $r['marks_obtained'] ?? 0;
                                        $total_val = $r['total_marks'] ?? 100;
                                        $pct = ($total_val > 0) ? round(($score_val / $total_val) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td style="font-weight: 700; color: var(--portal-indigo);"><?= htmlspecialchars($r['student_name']) ?></td>
                                            <td>
                                                <div style="font-weight:700;"><?= htmlspecialchars($r['exam_name']) ?></div>
                                                <div style="font-size:0.75rem; color:#94a3b8;"><?= date('M d, Y', strtotime($r['exam_date'] ?? $r['created_at'] ?? 'now')) ?></div>
                                            </td>
                                            <td><strong><?= $score_val ?></strong> / <?= $total_val ?></td>
                                            <td>
                                                <span class="badge <?= $pct >= 40 ? 'badge-success' : 'badge-danger' ?>"><?= $pct ?>%</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($r['rank'])): ?>
                                                    <span class="badge badge-purple">Rank #<?= $r['rank'] ?></span>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8; font-size:0.8rem;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Notices Feed -->
                <div class="portal-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                        <h3 style="font-size: 1.15rem; margin:0;"><i class="fas fa-bullhorn" style="color:var(--portal-purple); margin-right:8px;"></i> School Notices</h3>
                        <a href="notices.php" style="color:var(--portal-purple); text-decoration:none; font-weight:700; font-size:0.88rem;">See All <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <?php if (empty($recent_notices)): ?>
                        <p style="color: #94a3b8; font-size: 0.9rem; text-align: center; padding: 20px;">No new announcements posted.</p>
                    <?php else: ?>
                        <?php foreach ($recent_notices as $n): 
                            $badge_class = 'badge-purple';
                            if (($n['type'] ?? '') === 'important') $badge_class = 'badge-danger';
                            if (($n['type'] ?? '') === 'event') $badge_class = 'badge-success';
                        ?>
                            <div class="notice-item">
                                <span class="badge <?= $badge_class ?>" style="margin-bottom: 8px;"><?= htmlspecialchars($n['type'] ?? 'General') ?></span>
                                <h4 style="margin: 0 0 6px; font-size: 0.95rem;"><?= htmlspecialchars($n['title']) ?></h4>
                                <p style="font-size: 0.82rem; margin: 0 0 8px; line-height: 1.4; color: #475569;"><?= substr(htmlspecialchars($n['content']), 0, 90) . (strlen($n['content']) > 90 ? '...' : '') ?></p>
                                <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 700;">
                                    <i class="far fa-clock"></i> <?= date('M d, Y', strtotime($n['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </main>
</body>
</html>
