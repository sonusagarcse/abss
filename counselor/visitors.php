<?php
// counselor/visitors.php - Campus Visitors Log & Traffic Analytics
require_once 'includes/auth.php';
require_counselor_module('visitors');

// Statistics
$today_visitors_res = $conn->query("SELECT COUNT(*) as views, COUNT(DISTINCT ip_address) as uniques FROM site_visitors WHERE DATE(visited_at) = CURDATE()");
$today_stats = $today_visitors_res ? $today_visitors_res->fetch_assoc() : ['views' => 0, 'uniques' => 0];

$total_visitors_res = $conn->query("SELECT COUNT(*) as total, COUNT(DISTINCT ip_address) as uniques FROM site_visitors");
$total_stats = $total_visitors_res ? $total_visitors_res->fetch_assoc() : ['total' => 0, 'uniques' => 0];

// Recent visitors
$recent_res = $conn->query("SELECT * FROM site_visitors ORDER BY visited_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitors Log | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .custom-table tr:hover { background: #f8fafc; }
        .stats-ribbon { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-info h3 { margin: 0; font-size: 1.5rem; font-weight: 800; color: #0f172a; }
        .stat-info p { margin: 2px 0 0 0; font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-chart-line" style="color: #ea580c;"></i> Campus Visitors &amp; Web Inflow Log
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Track real-time digital engagement and campus inquiry interest.
                    </p>
                </div>
            </div>

            <div class="stats-ribbon">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$today_stats['uniques']; ?></h3>
                        <p>Today Unique Visitors</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$today_stats['views']; ?></h3>
                        <p>Today Page Views</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f0fdf4; color: #059669;">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo (int)$total_stats['uniques']; ?></h3>
                        <p>Total Unique Reach</p>
                    </div>
                </div>
            </div>

            <div class="portal-card">
                <h2 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 16px 0;">
                    Recent Visitor Activity Logs
                </h2>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Page / Section Viewed</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$recent_res || $recent_res->num_rows === 0): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        No recent visitor logs recorded.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($v = $recent_res->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span style="font-family: monospace; font-weight: 700; background: #f1f5f9; padding: 3px 8px; border-radius: 6px;">
                                                <?php echo htmlspecialchars($v['ip_address']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #0d9488; font-weight: 600;">
                                                <?php echo htmlspecialchars($v['page_url'] ?: 'Homepage / Portal'); ?>
                                            </span>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo date('d M Y, h:i:s A', strtotime($v['visited_at'])); ?>
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

