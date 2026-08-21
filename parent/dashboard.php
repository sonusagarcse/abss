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
$total_advance = 0;
$total_late_fine = 0;

$settings = function_exists('getAllSettings') ? getAllSettings() : [];

foreach ($children as $c) {
    $total_advance += (float)($c['advance_amount'] ?? 0);
    if (function_exists('get_student_total_fine')) {
        $fine_info = get_student_total_fine($c['id'], $conn, $settings);
        $total_late_fine += $fine_info['total_fine'];
    }
}

if (!empty($children_ids)) {
    $ids_str = implode(',', $children_ids);
    
    // Fee payments total
    $fee_query = $conn->query("SELECT SUM(amount) AS total_paid FROM fee_payments WHERE student_id IN ($ids_str)");
    if ($fee_query && $row = $fee_query->fetch_assoc()) {
        $total_paid = (float)$row['total_paid'];
    }
    
    // Outstanding dues total & latest unpaid bill ID
    $latest_unpaid_bill_id = 0;
    $base_dues = 0;
    $dues_query = $conn->query("SELECT SUM(amount) AS total_dues FROM fees_generated WHERE student_id IN ($ids_str) AND status = 'unpaid'");
    if ($dues_query && $row = $dues_query->fetch_assoc()) {
        $base_dues = (float)$row['total_dues'];
        $outstanding_dues = $base_dues + $total_late_fine;
    }
    if ($base_dues > 0) {
        $latest_bill_q = $conn->query("SELECT id FROM fees_generated WHERE student_id IN ($ids_str) AND status = 'unpaid' ORDER BY billing_date DESC, id DESC LIMIT 1");
        if ($latest_bill_q && $bRow = $latest_bill_q->fetch_assoc()) {
            $latest_unpaid_bill_id = (int)$bRow['id'];
        }
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

        /* Avatar Click-to-Zoom Hover Cue */
        .child-avatar {
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .child-avatar:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
        }
        .child-avatar:hover::after {
            content: "\f00e";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            inset: 0;
            background: rgba(124, 58, 237, 0.55);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            border-radius: 50%;
        }

        /* Large Scale Photo Lightbox Modal */
        .photo-lightbox-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeInLightbox 0.2s ease-out;
        }
        @keyframes fadeInLightbox {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .photo-lightbox-card {
            background: #ffffff;
            border-radius: 24px;
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.35);
            position: relative;
            text-align: center;
            animation: zoomInLightbox 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes zoomInLightbox {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .photo-lightbox-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(0,0,0,0.55);
            color: #ffffff;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: background 0.2s, transform 0.2s;
        }
        .photo-lightbox-close:hover {
            background: #dc2626;
            transform: rotate(90deg);
        }
        .photo-lightbox-img-wrap {
            width: 100%;
            height: 380px;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .photo-lightbox-img-wrap img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .lightbox-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5.5rem;
            font-weight: 900;
        }
        .photo-lightbox-details {
            padding: 22px 24px;
            background: #ffffff;
        }

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
                <?php if ($outstanding_dues > 0 && $latest_unpaid_bill_id > 0): ?>
                    <a href="view_bill.php?id=<?= $latest_unpaid_bill_id ?>" style="background: #dc2626; color: white; padding: 12px 22px; border-radius: 14px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.4);">
                        <i class="fas fa-credit-card"></i> Pay Due Bill (₹ <?= number_format($outstanding_dues, 2) ?>)
                    </a>
                <?php else: ?>
                    <a href="fees.php" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 22px; border-radius: 14px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
                        <i class="fas fa-wallet"></i> Pay Dues & Ledger
                    </a>
                <?php endif; ?>
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

                <div class="stat-card" style="border-left: 4px solid #ea580c;">
                    <div class="stat-icon" style="background:#ffedd5; color:#ea580c;"><i class="fas fa-coins"></i></div>
                    <div class="stat-info">
                        <h3 style="color:#ea580c;">₹ <?= number_format($total_late_fine, 2) ?></h3>
                        <span style="font-weight:700;">Total Late Fine</span>
                    </div>
                </div>

                <?php if ($total_advance > 0): ?>
                <div class="stat-card" style="border-left: 4px solid #0284c7;">
                    <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="stat-info">
                        <h3 style="color:#0284c7;">₹ <?= number_format($total_advance, 2) ?></h3>
                        <span style="font-weight:700;">Advance Balance</span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="stat-card" style="border-left: 4px solid #dc2626;">
                    <div class="stat-icon" style="background:#fee2e2; color:#b91c1c;"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stat-info" style="width: 100%;">
                        <h3 style="color:#b91c1c;">₹ <?= number_format($outstanding_dues, 2) ?></h3>
                        <span style="font-weight:700;">Outstanding Dues</span>
                        <?php if ($total_late_fine > 0): ?>
                            <small style="color:#ea580c; font-weight:700; font-size:0.75rem; display:block; margin-top:2px;">(Includes ₹ <?= number_format($total_late_fine, 2) ?> Late Fine)</small>
                        <?php endif; ?>
                        <?php if ($outstanding_dues > 0 && $latest_unpaid_bill_id > 0): ?>
                            <div style="margin-top: 10px;">
                                <a href="view_bill.php?id=<?= $latest_unpaid_bill_id ?>" 
                                   style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%); color: #ffffff; padding: 7px 16px; border-radius: 50px; font-size: 0.82rem; font-weight: 800; text-decoration: none; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35); transition: transform 0.2s;">
                                    <i class="fas fa-credit-card"></i> Pay Now (₹ <?= number_format($outstanding_dues, 2) ?>) →
                                </a>
                            </div>
                        <?php elseif ($outstanding_dues > 0): ?>
                            <div style="margin-top: 10px;">
                                <a href="fees.php" 
                                   style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%); color: #ffffff; padding: 7px 16px; border-radius: 50px; font-size: 0.82rem; font-weight: 800; text-decoration: none; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);">
                                    <i class="fas fa-credit-card"></i> Pay Now (₹ <?= number_format($outstanding_dues, 2) ?>) →
                                </a>
                            </div>
                        <?php else: ?>
                            <small style="color: #166534; font-weight: 700; display: block; margin-top: 4px;"><i class="fas fa-check-circle"></i> All Cleared</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Hindi Late Fee Advisory Callout Banner -->
            <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1.5px solid #fed7aa; border-radius: 16px; padding: 14px 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; box-shadow: 0 4px 15px rgba(234, 88, 12, 0.06);">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 260px;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: #ea580c; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 2px; font-size: 0.95rem; font-weight: 800; color: #9a3412;">
                            विलंब शुल्क सूचना (Late Fee Advisory)
                        </h4>
                        <p style="margin: 0; font-size: 0.85rem; color: #c2410c; font-weight: 700; line-height: 1.4;">
                            कृपया प्रत्येक माह की 5 तारीख तक शुल्क जमा करें, अन्यथा ₹5 प्रतिदिन की दर से विलंब शुल्क (Fine) देय होगा।
                        </p>
                    </div>
                </div>
                <span style="background: #ea580c; color: #ffffff; font-size: 0.75rem; font-weight: 800; padding: 5px 14px; border-radius: 50px; white-space: nowrap;">
                    ₹5 / दिन Fine
                </span>
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
                <?php foreach ($children as $c): 
                    // Use student_photo (portrait picture) first; fallback to image-based photo if student_photo is not set
                    $raw_photo = '';
                    if (!empty($c['student_photo'])) {
                        $raw_photo = $c['student_photo'];
                    } elseif (!empty($c['photo']) && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $c['photo'])) {
                        $raw_photo = $c['photo'];
                    }

                    $photo_url = '';
                    if (!empty($raw_photo)) {
                        $photo_url = (strpos($raw_photo, 'http') === 0 || strpos($raw_photo, '../') === 0) ? $raw_photo : '../' . ltrim($raw_photo, '/');
                    }
                    $c_name = htmlspecialchars($c['name']);
                    $c_reg = htmlspecialchars($c['reg_no'] ?: 'ABSS-' . str_pad($c['id'], 4, '0', STR_PAD_LEFT));
                    $c_class = htmlspecialchars($c['class_admitted'] ?: 'Class 5');
                    $c_mode = htmlspecialchars($c['scholar_mode'] ?? 'Day Scholar');
                    $c_target = htmlspecialchars($c['target_school'] ?: 'Netarhat Preparation');
                ?>
                    <div class="child-card">
                        <div class="child-avatar" 
                             title="Click to view large photo"
                             onclick="openPhotoModal('<?= addslashes($c_name) ?>', '<?= addslashes($photo_url) ?>', '<?= addslashes($c_reg) ?>', '<?= addslashes($c_class) ?>', '<?= addslashes($c_mode) ?>', '<?= addslashes($c_target) ?>')">
                            <?php if (!empty($photo_url)): ?>
                                <img src="<?= htmlspecialchars($photo_url) ?>" 
                                     alt="<?= $c_name ?>"
                                     style="width:52px; height:52px; object-fit:cover; border-radius:50%; display:block;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <span style="display:none; width:100%; height:100%; align-items:center; justify-content:center;"><?= htmlspecialchars(strtoupper(substr($c['name'] ?? 'S', 0, 1))) ?></span>
                            <?php else: ?>
                                <span style="display:flex; width:100%; height:100%; align-items:center; justify-content:center;"><?= htmlspecialchars(strtoupper(substr($c['name'] ?? 'S', 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="child-details">
                            <h4><?= $c_name ?></h4>
                            <span>Class: <strong><?= $c_class ?></strong></span><br>
                            <span>Target: <?= $c_target ?></span><br>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; align-items: center;">
                                <span class="badge badge-purple"><i class="fas fa-hotel"></i> <?= $c_mode ?></span>
                                <?php if (!empty($c['admission_test_paper'])): 
                                    $tp_url = (strpos($c['admission_test_paper'], 'http') === 0 || strpos($c['admission_test_paper'], '../') === 0) ? $c['admission_test_paper'] : '../' . ltrim($c['admission_test_paper'], '/');
                                ?>
                                    <a href="<?= htmlspecialchars($tp_url) ?>" target="_blank" class="badge" style="background: #faf5ff; color: #7e22ce; border: 1px solid #d8b4fe; text-decoration: none;" title="View Admission Test Paper Scan">
                                        <i class="fas fa-file-signature"></i> Test Paper
                                    </a>
                                <?php endif; ?>
                            </div>
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

    <!-- Student Large Photo Lightbox Modal -->
    <div id="studentPhotoModal" class="photo-lightbox-modal" style="display:none;" onclick="closePhotoModal(event)">
        <div class="photo-lightbox-card" onclick="event.stopPropagation();">
            <button type="button" class="photo-lightbox-close" onclick="closePhotoModal()" title="Close">&times;</button>
            <div class="photo-lightbox-img-wrap">
                <img id="lightboxImg" src="" alt="Student High-Res Photo" style="display:none;">
                <div id="lightboxPlaceholder" class="lightbox-placeholder" style="display:none;"></div>
            </div>
            <div class="photo-lightbox-details">
                <h3 id="lightboxName" style="margin: 0 0 6px; font-size: 1.35rem; color: #0f172a; font-weight: 800;"></h3>
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 10px;">
                    <span id="lightboxReg" class="badge badge-purple" style="font-family: monospace; font-size: 0.8rem; font-weight: 800;"></span>
                    <span id="lightboxClass" class="badge badge-success" style="font-size: 0.8rem; font-weight: 700;"></span>
                    <span id="lightboxMode" class="badge badge-purple" style="font-size: 0.8rem; font-weight: 700;"></span>
                </div>
                <p id="lightboxTarget" style="margin: 0; color: #64748b; font-size: 0.88rem; font-weight: 600;"></p>
            </div>
        </div>
    </div>

    <script>
        function openPhotoModal(name, photoUrl, regNo, className, scholarMode, targetSchool) {
            document.getElementById('lightboxName').textContent = name;
            document.getElementById('lightboxReg').textContent = regNo || 'ABSS Student';
            document.getElementById('lightboxClass').textContent = className || 'Class 5';
            document.getElementById('lightboxMode').textContent = scholarMode || 'Day Scholar';
            document.getElementById('lightboxTarget').textContent = targetSchool ? '🎯 Target: ' + targetSchool : '';
            
            const img = document.getElementById('lightboxImg');
            const placeholder = document.getElementById('lightboxPlaceholder');
            
            if (photoUrl && photoUrl.trim() !== '') {
                img.src = photoUrl;
                img.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                img.style.display = 'none';
                placeholder.textContent = name ? name.charAt(0).toUpperCase() : 'S';
                placeholder.style.display = 'flex';
            }
            
            document.getElementById('studentPhotoModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closePhotoModal(e) {
            if (!e || e.target === document.getElementById('studentPhotoModal') || e.target.classList.contains('photo-lightbox-close')) {
                document.getElementById('studentPhotoModal').style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('studentPhotoModal').style.display === 'flex') {
                closePhotoModal();
            }
        });
    </script>
</body>
</html>
