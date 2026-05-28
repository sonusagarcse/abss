<?php
// admin/visitors.php - Visitor Analytics & Action Auditing Console

require_once 'includes/auth.php';

// Fetch overall visitor stats
$total_visits_res = $conn->query("SELECT COUNT(*) AS total FROM site_visitors");
$total_visits = $total_visits_res->fetch_assoc()['total'] ?? 0;

$unique_ips_res = $conn->query("SELECT COUNT(DISTINCT ip_address) AS total FROM site_visitors");
$unique_ips = $unique_ips_res->fetch_assoc()['total'] ?? 0;

// Device Breakdown (Mobile vs Desktop)
$mobile_hits_res = $conn->query("SELECT COUNT(*) AS total FROM site_visitors WHERE user_agent LIKE '%Mobi%' OR user_agent LIKE '%iPhone%' OR user_agent LIKE '%Android%'");
$mobile_hits = $mobile_hits_res->fetch_assoc()['total'] ?? 0;
$desktop_hits = max(0, $total_visits - $mobile_hits);

// Popular pages breakdown
$popular_pages = $conn->query("
    SELECT page_visited, COUNT(*) AS hits 
    FROM site_visitors 
    GROUP BY page_visited 
    ORDER BY hits DESC LIMIT 6
");
$top_page = 'N/A';
$top_page_hits = 0;
if ($popular_pages && $popular_pages->num_rows > 0) {
    // Reset internal pointer to fetch first row
    $first = $popular_pages->fetch_assoc();
    $top_page = $first['page_visited'];
    $top_page_hits = $first['hits'];
    $popular_pages->data_seek(0); // reset pointer
}

// ----------------------------------------------------
// Filters and Query logic for Activity Audit Logs
// ----------------------------------------------------
$filter_role = isset($_GET['role']) ? trim($_GET['role']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$audit_sql = "SELECT * FROM activity_logs WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_role)) {
    $audit_sql .= " AND user_role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

if (!empty($search_query)) {
    $audit_sql .= " AND (username LIKE ? OR action_details LIKE ? OR action_type LIKE ? OR ip_address LIKE ?)";
    $like_search = "%" . $search_query . "%";
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $types .= "ssss";
}

$audit_sql .= " ORDER BY created_at DESC LIMIT 100";

$audit_stmt = $conn->prepare($audit_sql);
if (!empty($params)) {
    $audit_stmt->bind_param($types, ...$params);
}
$audit_stmt->execute();
$audit_logs = $audit_stmt->get_result();

// Fetch latest visitor logs (last 50)
$latest_visitors = $conn->query("
    SELECT * FROM site_visitors 
    ORDER BY visited_at DESC LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Audit Desk | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        /* Modern Glassmorphic Stats Layout */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: #fff; padding: 25px 30px; border-radius: 30px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); border: 1px solid #f0f4f8; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(13,71,161,0.05); }
        .stat-icon { width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: bold; }
        .stat-icon-blue { background: #e8f0fe; color: #0d47a1; }
        .stat-icon-purple { background: #f3e5f5; color: #7b1fa2; }
        .stat-icon-green { background: #e8f5e9; color: #2e7d32; }
        .stat-icon-orange { background: #fff3e0; color: #e65100; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-info h3 { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--portal-blue); }
        .stat-info span { font-size: 0.8rem; color: #9aa5ce; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 3px; }

        /* Unified Tab System */
        .tab-bar { display: flex; gap: 15px; margin-bottom: 35px; border-bottom: 2px solid #eef2ff; padding-bottom: 15px; }
        .tab-btn { background: none; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; color: #5c6bc0; font-family: inherit; font-size: 0.95rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { background: #f8faff; color: var(--portal-blue); }
        .tab-btn.active { background: #e8f0fe; color: var(--portal-blue); }

        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: fadeIn 0.4s ease; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Filter Controls Grid */
        .filter-bar { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: end; background: #fff; padding: 25px 30px; border-radius: 25px; border: 1px solid #f0f4f8; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.01); }
        .filter-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }

        /* Popular Pages Metric Widget */
        .analytics-split { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .popular-list { list-style: none; padding: 0; margin: 0; }
        .popular-item { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #f8faff; border-radius: 16px; margin-bottom: 12px; border: 1px solid #eef2ff; font-weight: 700; transition: 0.3s; }
        .popular-item:hover { background: #f1f5ff; transform: translateX(3px); }
        .popular-url { color: var(--portal-blue); font-family: monospace; font-size: 0.9rem; word-break: break-all; margin-right: 15px; }
        .popular-count { background: #e8f0fe; color: var(--portal-blue); padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }

        /* Custom Table Layout and Badges */
        .portal-table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        th { text-align: left; padding: 15px 20px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; border: none; }
        td { padding: 20px; background: #f8faff; border-top: 1px solid #eef2ff; border-bottom: 1px solid #eef2ff; color: #5c6bc0; font-weight: 600; vertical-align: top; transition: all 0.3s ease; }
        td:first-child { border-left: 1px solid #eef2ff; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #eef2ff; border-radius: 0 20px 20px 0; }
        tr:hover td { background: #f1f5ff; border-color: #dbe4ff; }

        .role-badge { padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .role-admin { background: #feeef2; color: #d32f2f; }
        .role-parent { background: #e8f5e9; color: #2e7d32; }
        .role-guest { background: #e8f0fe; color: #0d47a1; }

        .action-tag { background: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border: 1px solid #eef2ff; color: #5c6bc0; display: inline-block; margin-bottom: 6px; }

        .device-badge { display: inline-flex; align-items: center; gap: 8px; color: #5c6bc0; font-size: 0.85rem; font-weight: 700; background: #fff; padding: 4px 10px; border-radius: 6px; border: 1px solid #eef2ff; }
        .device-badge i { font-size: 1rem; }

        .referrer-txt { font-size: 0.8rem; font-weight: 600; color: #9aa5ce; word-break: break-all; display: block; margin-top: 5px; }

        @media (max-width: 1100px) {
            .analytics-split { grid-template-columns: 1fr; }
        }
        @media (max-width: 800px) {
            .filter-bar { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Visitor & System Auditing</h1>
            <p>Monitor live website traffic analytics and track detailed administrative & parent panel action logs.</p>
        </header>

        <!-- Stats Overview Row -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i class="fas fa-eye"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_visits); ?></h3>
                    <span>Total Page Views</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($unique_ips); ?></h3>
                    <span>Unique Visitors</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i class="fas fa-mobile-alt"></i></div>
                <div class="stat-info">
                    <h3>
                        <?php 
                        $pct = $total_visits > 0 ? round(($mobile_hits / $total_visits) * 100) : 0;
                        echo $pct . "%";
                        ?>
                    </h3>
                    <span>Mobile Traffic</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-orange"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3 style="font-size:1.15rem; word-break:break-all; font-weight:800; color:var(--portal-blue);"><?php echo htmlspecialchars(basename($top_page) ?: '/'); ?></h3>
                    <span>Top Page Hits (<?php echo number_format($top_page_hits); ?>)</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tab-bar">
            <button class="tab-btn active" id="btn-analytics" onclick="switchTab('analytics')"><i class="fas fa-chart-line"></i> Traffic Analytics</button>
            <button class="tab-btn" id="btn-audits" onclick="switchTab('audits')"><i class="fas fa-history"></i> System Action Logs</button>
        </div>

        <!-- Tab 1: Traffic Analytics -->
        <div class="tab-pane active" id="pane-analytics">
            <div class="analytics-split">
                <!-- Page Metrics List -->
                <div class="portal-card" style="height: fit-content;">
                    <h3 style="margin-bottom: 25px; color:var(--portal-blue); font-size:1.2rem;"><i class="fas fa-fire" style="margin-right:8px; color:#e65100;"></i> Page Popularity</h3>
                    <ul class="popular-list">
                        <?php if ($popular_pages->num_rows == 0): ?>
                            <li style="text-align: center; color: #9aa5ce; padding: 20px;">No traffic tracked yet.</li>
                        <?php else: ?>
                            <?php while($p = $popular_pages->fetch_assoc()): ?>
                                <li class="popular-item">
                                    <span class="popular-url"><?php echo htmlspecialchars($p['page_visited']); ?></span>
                                    <span class="popular-count"><i class="fas fa-eye"></i> <?php echo number_format($p['hits']); ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Latest Hits Grid -->
                <div class="portal-card">
                    <h3 style="margin-bottom: 25px; color:var(--portal-blue); font-size:1.2rem;"><i class="fas fa-route" style="margin-right:8px; color:var(--portal-blue);"></i> Real-Time Visitor Stream <span style="font-size:0.75rem; font-weight:normal; opacity:0.6; margin-left:8px;">(Latest 50 Hits)</span></h3>
                    <div class="portal-table-container">
                        <table style="width: 100%; table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Visitor IP / Date</th>
                                    <th style="width: 20%;">Device / Browser</th>
                                    <th style="width: 35%;">Page / Referrer</th>
                                    <th style="width: 20%;">Portal Session</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($latest_visitors->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #9aa5ce; padding: 40px;">No web traffic detected yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($v = $latest_visitors->fetch_assoc()): 
                                        $d_info = get_device_info($v['user_agent']);
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:800; color:#333; font-family:monospace; font-size:0.95rem;"><?php echo htmlspecialchars($v['ip_address']); ?></div>
                                                <small style="color:#9aa5ce; font-weight:600;"><?php echo date('d M, Y - h:i A', strtotime($v['visited_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="device-badge" style="margin-bottom: 5px;">
                                                    <i class="fas <?php echo $d_info['icon']; ?>"></i> <?php echo $d_info['device']; ?>
                                                </div><br>
                                                <small style="font-weight:700; color:#5c6bc0;"><i class="fas fa-window-maximize"></i> <?php echo $d_info['browser']; ?></small>
                                            </td>
                                            <td>
                                                <div style="font-weight:700; color:var(--portal-indigo); font-family:monospace; word-break:break-all; font-size:0.85rem;"><?php echo htmlspecialchars($v['page_visited']); ?></div>
                                                <?php if($v['referrer']): ?>
                                                    <span class="referrer-txt" title="<?php echo htmlspecialchars($v['referrer']); ?>"><i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(parse_url($v['referrer'], PHP_URL_HOST) ?: $v['referrer']); ?></span>
                                                <?php else: ?>
                                                    <span class="referrer-txt"><i class="fas fa-link"></i> Direct Traffic</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="role-badge role-<?php echo $v['user_role']; ?>"><?php echo $v['user_role']; ?></span>
                                                <?php if($v['user_role'] === 'admin'): ?>
                                                    <small style="display:block; margin-top:4px; font-weight:700; color:#9aa5ce;">Admin #<?php echo $v['user_id']; ?></small>
                                                <?php elseif($v['user_role'] === 'parent'): ?>
                                                    <small style="display:block; margin-top:4px; font-weight:700; color:#9aa5ce;">Parent #<?php echo $v['parent_id']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: System Action Logs -->
        <div class="tab-pane" id="pane-audits">
            <!-- Filter Controls -->
            <form action="" method="GET" id="search-form">
                <input type="hidden" name="tab" value="audits">
                <div class="filter-bar">
                    <div class="filter-fields">
                        <div class="portal-input-group" style="margin-bottom:0;">
                            <label>Filter By User Role</label>
                            <select name="role" onchange="document.getElementById('search-form').submit()">
                                <option value="">-- All Roles --</option>
                                <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="parent" <?php echo $filter_role == 'parent' ? 'selected' : ''; ?>>Parent</option>
                                <option value="guest" <?php echo $filter_role == 'guest' ? 'selected' : ''; ?>>Guest / Public</option>
                            </select>
                        </div>
                        <div class="portal-input-group" style="margin-bottom:0;">
                            <label>Search Username / Details</label>
                            <input type="text" name="search" placeholder="e.g. parent name, payment amount, IP..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn-portal" style="padding:16px 25px;"><i class="fas fa-search"></i> Search Logs</button>
                        <?php if(!empty($filter_role) || !empty($search_query)): ?>
                            <a href="visitors.php?tab=audits" class="btn-portal" style="background:#fff; color:var(--portal-blue); border:2px solid #eef2ff; padding:14px 22px; text-decoration:none; display:inline-flex; align-items:center; box-shadow:none;"><i class="fas fa-sync-alt"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Action Grid Card -->
            <div class="portal-card">
                <h3 style="margin-bottom: 25px; color:var(--portal-blue); font-size:1.2rem;"><i class="fas fa-history" style="margin-right:8px; color:var(--portal-purple);"></i> System Activity Audit Trail</h3>
                <div class="portal-table-container">
                    <table style="width: 100%; table-layout: fixed;">
                        <thead>
                            <tr>
                                <th style="width: 25%;">User Identity</th>
                                <th style="width: 15%;">Role</th>
                                <th style="width: 48%;">Action Audited / Details</th>
                                <th style="width: 12%;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($audit_logs->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #9aa5ce; padding: 40px;">No audit records found matching the query filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($log = $audit_logs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:800; color:#333; word-break:break-all;"><?php echo htmlspecialchars($log['username']); ?></div>
                                            <small style="color:#9aa5ce; font-weight:600;"><?php echo date('d M, Y - h:i A', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?php echo $log['user_role']; ?>"><?php echo $log['user_role']; ?></span>
                                        </td>
                                        <td>
                                            <div class="action-tag"><?php echo str_replace('_', ' ', htmlspecialchars($log['action_type'])); ?></div>
                                            <div style="font-weight:700; color:var(--portal-indigo); font-size:0.92rem; line-height:1.4; margin-top:3px; word-break:break-word;"><?php echo htmlspecialchars($log['action_details']); ?></div>
                                        </td>
                                        <td style="font-family:monospace; font-weight:800; font-size:0.85rem; color:#5c6bc0; vertical-align:middle;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tabs Management JavaScript Engine
        function switchTab(tabId) {
            // Remove active states from buttons
            document.getElementById('btn-analytics').classList.remove('active');
            document.getElementById('btn-audits').classList.remove('active');
            
            // Remove active states from panes
            document.getElementById('pane-analytics').classList.remove('active');
            document.getElementById('pane-audits').classList.remove('active');

            // Apply active states
            if (tabId === 'audits') {
                document.getElementById('btn-audits').classList.add('active');
                document.getElementById('pane-audits').classList.add('active');
                // Store active tab parameter in URL hash state
                window.location.hash = 'audits';
            } else {
                document.getElementById('btn-analytics').classList.add('active');
                document.getElementById('pane-analytics').classList.add('active');
                window.location.hash = 'analytics';
            }
        }

        // On page load, restore tab selection from URL hash or query params
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            const hash = window.location.hash;

            if (activeTab === 'audits' || hash === '#audits') {
                switchTab('audits');
            } else {
                switchTab('analytics');
            }
        });
    </script>
</body>
</html>
