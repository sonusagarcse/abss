<?php
// parent/dashboard.php - Parent Portal Homepage Dashboard

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

// 2. Fetch stats (Payments & Results)
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
        ORDER BY r.exam_date DESC LIMIT 5
    ");
    if ($res_query) {
        while ($r = $res_query->fetch_assoc()) {
            $recent_results[] = $r;
        }
    }
}

// Fetch notices
$notices_res = $conn->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
$recent_notices = [];
if ($notices_res) {
    while ($n = $notices_res->fetch_assoc()) {
        $recent_notices[] = $n;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; }
        .children-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .child-card { background: #fff; border-radius: 25px; padding: 25px; border: 1px solid #f0f4f8; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.01); }
        .child-avatar { width: 50px; height: 50px; border-radius: 50%; background: #eef2ff; color: var(--portal-purple); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 800; }
        .child-details h4 { margin: 0 0 5px; color: var(--portal-indigo); font-weight: 800; }
        .child-details span { font-size: 0.8rem; color: #9aa5ce; font-weight: 700; }
        
        .recent-notice-card { background: #f8faff; border-radius: 20px; padding: 20px; border-left: 5px solid var(--portal-purple); margin-bottom: 20px; transition: 0.3s; }
        .recent-notice-card:hover { transform: translateX(5px); }
        .notice-badge { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 5px; color: #fff; margin-bottom: 10px; display: inline-block; }
        
        .rank-tag { width: 28px; height: 28px; background: var(--portal-indigo); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Dashboard Overview</h1>
            <p>Welcome back! Monitor academic progress and billing ledgers for your ward(s).</p>
        </header>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 80px 40px;">
                <i class="fas fa-users-slash" style="font-size: 4rem; color: #9aa5ce; margin-bottom: 30px;"></i>
                <h2>No Children Associated</h2>
                <p style="max-width: 500px; margin: 0 auto 30px;">Your parent account has not been mapped to any student profile in the system. Please reach out to the school administration to bind your children accounts.</p>
                <a href="mailto:abssimamganj@gmail.com" class="btn-portal" style="text-decoration:none;"><i class="fas fa-envelope"></i> Contact Support</a>
            </div>
        <?php else: ?>
            <!-- 1. Stats Counter Widgets -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#eef2ff; color:var(--portal-purple);"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h3><?php echo count($children); ?></h3>
                        <span>Registered Ward(s)</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f5e9; color:#2e7d32;"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3>₹ <?php echo number_format($total_paid); ?></h3>
                        <span>Total Tuition Paid</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#feeef2; color:#d32f2f;"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stat-info">
                        <h3 style="color:#d32f2f;">₹ <?php echo number_format($outstanding_dues); ?></h3>
                        <span>Outstanding Dues</span>
                    </div>
                </div>
            </div>

            <!-- 2. Children Cards list -->
            <h3 style="margin-bottom: 20px;">Your Child / Ward Registry</h3>
            <div class="children-grid">
                <?php foreach ($children as $c): ?>
                    <div class="child-card">
                        <div class="child-avatar">
                            <?php 
                            $initials = explode(' ', $c['name']);
                            echo htmlspecialchars(substr($initials[0], 0, 1) . (isset($initials[1]) ? substr($initials[1], 0, 1) : ''));
                            ?>
                        </div>
                        <div class="child-details">
                            <h4><?php echo htmlspecialchars($c['name']); ?></h4>
                            <span>Class: <?php echo htmlspecialchars($c['class_admitted']); ?></span><br>
                            <span>Target: <?php echo htmlspecialchars($c['target_school'] ? $c['target_school'] : 'Netarhat Preparation'); ?></span><br>
                            <span class="children-tag" style="background:#eef2ff; color:var(--portal-indigo); padding:3px 8px; border-radius:6px; font-size:0.75rem; font-weight:800; display:inline-block; margin-top:5px;"><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($c['scholar_mode'] ?? 'Day Scholar'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 3. Two Column Dashboard details -->
            <div class="dashboard-grid">
                <!-- Column Left: Recent results -->
                <div class="portal-card" style="padding: 35px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="margin:0;">Recent Mock Assessments</h3>
                        <a href="results.php" style="color:var(--portal-purple); text-decoration:none; font-weight:700; font-size:0.9rem;">View All Results <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="portal-table-container" style="margin-top: 0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Child</th>
                                    <th>Assessment</th>
                                    <th>Score / Percentage</th>
                                    <th>Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_results)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; color:#9aa5ce;">No grading results recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_results as $r): ?>
                                        <tr>
                                            <td style="color:var(--portal-indigo); font-weight:800;"><?php echo htmlspecialchars($r['student_name']); ?></td>
                                            <td>
                                                <div style="font-weight:700; color:var(--portal-indigo);"><?php echo htmlspecialchars($r['exam_name']); ?></div>
                                                <div style="font-size:0.75rem; opacity:0.6;"><?php echo date('d M, Y', strtotime($r['exam_date'])); ?></div>
                                            </td>
                                            <td>
                                                <span style="font-weight:800;"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></span>
                                                <small style="color:#2e7d32; font-weight:700; margin-left: 8px;">(<?php echo round(($r['score']/$r['total_marks'])*100, 1); ?>%)</small>
                                            </td>
                                            <td>
                                                <?php if ($r['rank']): ?>
                                                    <span class="rank-tag"><?php echo $r['rank']; ?></span>
                                                <?php else: ?>
                                                    <span style="color:#9aa5ce; font-size:0.8rem;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Column Right: Recent Notices -->
                <div class="portal-card" style="padding: 35px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="margin:0;">Notice Board</h3>
                        <a href="notices.php" style="color:var(--portal-purple); text-decoration:none; font-weight:700; font-size:0.9rem;">See All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if (empty($recent_notices)): ?>
                        <div style="text-align:center; padding: 40px 0; color:#9aa5ce;">
                            <i class="fas fa-bullhorn" style="font-size: 2rem; opacity:0.3; margin-bottom: 10px;"></i>
                            <p style="font-size:0.9rem; margin:0;">No new notices published.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_notices as $n): ?>
                            <?php 
                            $badge_bg = '#3f51b5';
                            if ($n['type'] === 'important') $badge_bg = '#d32f2f';
                            if ($n['type'] === 'event') $badge_bg = '#2e7d32';
                            ?>
                            <div class="recent-notice-card">
                                <span class="notice-badge" style="background:<?php echo $badge_bg; ?>;"><?php echo $n['type']; ?></span>
                                <h4 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 800; color: var(--portal-indigo);"><?php echo htmlspecialchars($n['title']); ?></h4>
                                <p style="font-size: 0.8rem; line-height: 1.4; margin: 0 0 10px 0; color: #5c6bc0; font-weight: 500;"><?php echo substr(htmlspecialchars($n['content']), 0, 100) . (strlen($n['content']) > 100 ? '...' : ''); ?></p>
                                <span style="font-size: 0.7rem; font-weight: 700; color:#9aa5ce;"><i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($n['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
