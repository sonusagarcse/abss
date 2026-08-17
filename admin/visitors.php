<?php
// admin/visitors.php - Next-Gen Visitor Analytics & System Audit Console

require_once 'includes/auth.php';

// ----------------------------------------------------
// 1. Overall & Realtime Traffic Aggregates
// ----------------------------------------------------
$total_visits_res = $conn->query("SELECT COUNT(*) AS total FROM site_visitors");
$total_visits = (int)($total_visits_res->fetch_assoc()['total'] ?? 0);

$unique_ips_res = $conn->query("SELECT COUNT(DISTINCT ip_address) AS total FROM site_visitors");
$unique_ips = (int)($unique_ips_res->fetch_assoc()['total'] ?? 0);

// Today's traffic
$today_stats = $conn->query("
    SELECT 
        COUNT(*) AS today_views, 
        COUNT(DISTINCT ip_address) AS today_uniques 
    FROM site_visitors 
    WHERE DATE(visited_at) = CURDATE()
")->fetch_assoc();
$today_views = (int)($today_stats['today_views'] ?? 0);
$today_uniques = (int)($today_stats['today_uniques'] ?? 0);

// Active in Last 1 Hour (Realtime velocity)
$last_hour_res = $conn->query("
    SELECT COUNT(*) AS total, COUNT(DISTINCT ip_address) as active_ips 
    FROM site_visitors 
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
")->fetch_assoc();
$last_hour_views = (int)($last_hour_res['total'] ?? 0);
$last_hour_ips = (int)($last_hour_res['active_ips'] ?? 0);

// ----------------------------------------------------
// 2. Timeline Daily Traffic Series (Last 14 Days)
// ----------------------------------------------------
$timeline_res = $conn->query("
    SELECT 
        DATE(visited_at) AS v_date, 
        COUNT(*) AS total_hits, 
        COUNT(DISTINCT ip_address) AS unique_visitors 
    FROM site_visitors 
    WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(visited_at) 
    ORDER BY v_date ASC
");

$dates_map = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates_map[$d] = [
        'label' => date('d M', strtotime($d)),
        'hits' => 0,
        'uniques' => 0
    ];
}

if ($timeline_res && $timeline_res->num_rows > 0) {
    while ($row = $timeline_res->fetch_assoc()) {
        $vd = $row['v_date'];
        if (isset($dates_map[$vd])) {
            $dates_map[$vd]['hits'] = (int)$row['total_hits'];
            $dates_map[$vd]['uniques'] = (int)$row['unique_visitors'];
        }
    }
}

$chart_labels = array_column($dates_map, 'label');
$chart_hits = array_column($dates_map, 'hits');
$chart_uniques = array_column($dates_map, 'uniques');

// ----------------------------------------------------
// 3. Hourly Activity Distribution (0 to 23 Hours)
// ----------------------------------------------------
$hourly_res = $conn->query("
    SELECT 
        HOUR(visited_at) AS v_hour, 
        COUNT(*) AS hits 
    FROM site_visitors 
    GROUP BY HOUR(visited_at) 
    ORDER BY v_hour ASC
");
$hourly_data = array_fill(0, 24, 0);
$peak_hour = 0;
$peak_hour_hits = 0;
if ($hourly_res) {
    while ($h = $hourly_res->fetch_assoc()) {
        $hour_idx = (int)$h['v_hour'];
        $hits = (int)$h['hits'];
        $hourly_data[$hour_idx] = $hits;
        if ($hits > $peak_hour_hits) {
            $peak_hour_hits = $hits;
            $peak_hour = $hour_idx;
        }
    }
}
$hourly_labels = [];
for ($i = 0; $i < 24; $i++) {
    $ampm = $i >= 12 ? 'PM' : 'AM';
    $display_h = $i % 12;
    if ($display_h == 0) $display_h = 12;
    $hourly_labels[] = $display_h . ' ' . $ampm;
}

// ----------------------------------------------------
// 4. Device & Browser Ecosystem Breakdown
// ----------------------------------------------------
$mobile_hits = 0;
$tablet_hits = 0;
$desktop_hits = 0;
$browser_stats = [
    'Chrome' => 0,
    'Safari' => 0,
    'Firefox' => 0,
    'Edge' => 0,
    'Opera' => 0,
    'Other' => 0
];

$all_ua_res = $conn->query("SELECT user_agent FROM site_visitors");
if ($all_ua_res) {
    while ($ua_row = $all_ua_res->fetch_assoc()) {
        $d_info = get_device_info($ua_row['user_agent']);
        if ($d_info['device'] === 'Mobile') $mobile_hits++;
        elseif ($d_info['device'] === 'Tablet') $tablet_hits++;
        else $desktop_hits++;

        $b = $d_info['browser'];
        if (isset($browser_stats[$b])) {
            $browser_stats[$b]++;
        } else {
            $browser_stats['Other']++;
        }
    }
}

// ----------------------------------------------------
// 5. User Roles Breakdown
// ----------------------------------------------------
$role_counts = [
    'guest' => 0,
    'parent' => 0,
    'admin' => 0,
    'teacher' => 0
];
$roles_res = $conn->query("SELECT user_role, COUNT(*) AS count FROM site_visitors GROUP BY user_role");
if ($roles_res) {
    while ($r = $roles_res->fetch_assoc()) {
        $role_key = strtolower($r['user_role']);
        if (isset($role_counts[$role_key])) {
            $role_counts[$role_key] = (int)$r['count'];
        }
    }
}

// ----------------------------------------------------
// 6. Popular Pages Breakdown
// ----------------------------------------------------
$popular_pages = $conn->query("
    SELECT page_visited, COUNT(*) AS hits, COUNT(DISTINCT ip_address) as unique_ips
    FROM site_visitors 
    GROUP BY page_visited 
    ORDER BY hits DESC LIMIT 8
");

// ----------------------------------------------------
// 7. Referrer Intelligence
// ----------------------------------------------------
$referrers = $conn->query("
    SELECT 
        CASE 
            WHEN referrer IS NULL OR referrer = '' THEN 'Direct Traffic' 
            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1)
        END AS source_domain, 
        COUNT(*) AS hits 
    FROM site_visitors 
    GROUP BY source_domain 
    ORDER BY hits DESC LIMIT 5
");

// ----------------------------------------------------
// 8. Filters and Query logic for Activity Audit Logs
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

// Latest visitor stream logs for multi-tab pagination
$latest_visitors = $conn->query("
    SELECT * FROM site_visitors 
    ORDER BY visited_at DESC LIMIT 150
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Analytics & Auditing | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <!-- Chart.js Modern High-Performance Visualization Engine -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* Modern Analytics Layout Engine */
        .analytics-grid-2col {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .analytics-grid-3col {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Glassmorphic Metric Hero Cards */
        .metrics-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-hero-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .metric-hero-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(37, 99, 235, 0.08);
        }

        .metric-hero-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .metric-hero-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .metric-hero-value {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--portal-dark);
            letter-spacing: -0.02em;
            margin: 0;
            line-height: 1.1;
        }
        .metric-hero-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        .pulse-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #166534;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulseGlow 1.8s infinite;
        }
        @keyframes pulseGlow {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        /* Modern Tabs Header */
        .tab-bar-nav {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            overflow-x: auto;
        }
        .tab-btn-pill {
            background: transparent;
            border: 2px solid transparent;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 800;
            color: #64748b;
            font-family: inherit;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        .tab-btn-pill:hover {
            background: rgba(239, 246, 255, 0.8);
            color: var(--portal-blue);
        }
        .tab-btn-pill.active {
            background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark));
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }

        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: tabFadeIn 0.35s ease; }
        @keyframes tabFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Chart Canvas Wrappers */
        .chart-card-container {
            position: relative;
            width: 100%;
            height: 280px;
        }
        .chart-donut-container {
            position: relative;
            width: 100%;
            height: 230px;
        }

        /* Progress List for Popular Pages */
        .progress-rank-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .progress-rank-item {
            margin-bottom: 16px;
        }
        .progress-rank-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.86rem;
            font-weight: 700;
        }
        .progress-rank-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }
        .progress-rank-fill {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(90deg, var(--portal-blue), #38bdf8);
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Live Stream Table */
        .live-stream-table {
            width: 100%;
            min-width: 650px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .live-stream-table th {
            text-align: left;
            padding: 8px 18px;
            color: var(--portal-blue);
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: transparent;
            border: none;
        }
        .live-stream-row td {
            padding: 16px 18px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
            transition: background 0.2s;
        }
        .live-stream-row:hover td {
            background: #f8fafc;
        }
        .live-stream-row td:first-child {
            border-left: 1px solid #f1f5f9;
            border-radius: 14px 0 0 14px;
        }
        .live-stream-row td:last-child {
            border-right: 1px solid #f1f5f9;
            border-radius: 0 14px 14px 0;
        }

        .role-pill {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-block;
        }
        .role-admin { background: #fee2e2; color: #b91c1c; }
        .role-parent { background: #dcfce7; color: #166534; }
        .role-teacher { background: #f3e8ff; color: #7c3aed; }
        .role-guest { background: #e0f2fe; color: #0369a1; }

        .device-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* Filter Form Box */
        .filter-glass-box {
            background: var(--glass-bg);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--glass-border);
            margin-bottom: 25px;
            box-shadow: var(--glass-shadow);
        }

        /* Stream Table Pagination Tabs */
        .stream-pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 12px;
        }

        .stream-page-tabs {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .stream-page-tab {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            color: #475569;
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            box-sizing: border-box;
            user-select: none;
        }
        .stream-page-tab:hover:not(:disabled) {
            background: #eff6ff;
            color: var(--portal-blue);
            border-color: #bfdbfe;
            transform: translateY(-1px);
        }
        .stream-page-tab.active {
            background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark));
            color: #ffffff;
            border-color: var(--portal-blue);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .stream-page-tab:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f8fafc;
        }

        @media (max-width: 1024px) {
            .analytics-grid-2col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header" style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-pie" style="color: var(--portal-blue);"></i> Traffic Intelligence & System Auditing
                    </h1>
                    <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Real-time web analytics, user audience tracking, and security audit logs.</p>
                </div>

                <div class="pulse-live">
                    <span class="pulse-dot"></span> Live Telemetry Active
                </div>
            </div>
        </header>

        <!-- Metric Hero Row -->
        <div class="metrics-overview-grid">
            <div class="metric-hero-card">
                <div class="metric-hero-header">
                    <span class="metric-hero-label">Total Page Views</span>
                    <div class="metric-hero-icon icon-blue"><i class="fas fa-eye"></i></div>
                </div>
                <h2 class="metric-hero-value"><?php echo number_format($total_visits); ?></h2>
                <div style="margin-top: 8px; font-size: 0.8rem; font-weight: 700; color: #166534; display: flex; align-items: center; gap: 4px;">
                    <i class="fas fa-arrow-up"></i> +<?php echo number_format($today_views); ?> today
                </div>
            </div>

            <div class="metric-hero-card">
                <div class="metric-hero-header">
                    <span class="metric-hero-label">Unique Visitors</span>
                    <div class="metric-hero-icon icon-purple"><i class="fas fa-users"></i></div>
                </div>
                <h2 class="metric-hero-value"><?php echo number_format($unique_ips); ?></h2>
                <div style="margin-top: 8px; font-size: 0.8rem; font-weight: 700; color: #7c3aed; display: flex; align-items: center; gap: 4px;">
                    <i class="fas fa-fingerprint"></i> +<?php echo number_format($today_uniques); ?> today
                </div>
            </div>

            <div class="metric-hero-card">
                <div class="metric-hero-header">
                    <span class="metric-hero-label">Active (Last 1 Hour)</span>
                    <div class="metric-hero-icon icon-green"><i class="fas fa-bolt"></i></div>
                </div>
                <h2 class="metric-hero-value"><?php echo number_format($last_hour_views); ?></h2>
                <div style="margin-top: 8px; font-size: 0.8rem; font-weight: 700; color: #166534; display: flex; align-items: center; gap: 4px;">
                    <i class="fas fa-user-clock"></i> <?php echo $last_hour_ips; ?> distinct IPs
                </div>
            </div>

            <div class="metric-hero-card">
                <div class="metric-hero-header">
                    <span class="metric-hero-label">Mobile Traffic</span>
                    <div class="metric-hero-icon icon-orange"><i class="fas fa-mobile-alt"></i></div>
                </div>
                <h2 class="metric-hero-value">
                    <?php 
                        $mob_pct = $total_visits > 0 ? round(($mobile_hits / $total_visits) * 100, 1) : 0;
                        echo $mob_pct . '%';
                    ?>
                </h2>
                <div style="margin-top: 8px; font-size: 0.8rem; font-weight: 700; color: #ea580c; display: flex; align-items: center; gap: 4px;">
                    <i class="fas fa-desktop"></i> <?php echo (100 - $mob_pct); ?>% Desktop & Tablet
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tab-bar-nav">
            <button class="tab-btn-pill active" id="btn-analytics" onclick="switchTab('analytics')">
                <i class="fas fa-chart-line"></i> Traffic Analytics & Charts
            </button>
            <button class="tab-btn-pill" id="btn-audits" onclick="switchTab('audits')">
                <i class="fas fa-shield-alt"></i> Security Action Audit Trail
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: VISUAL DATA ANALYTICS               -->
        <!-- ========================================== -->
        <div class="tab-pane active" id="pane-analytics">
            <!-- Row 1: Daily Engagement Trend (Line Area Chart) + Peak Hours (Bar Chart) -->
            <div class="analytics-grid-2col">
                <div class="portal-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.15rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-wave-square" style="color: var(--portal-blue);"></i> Traffic Trajectory (Last 14 Days)
                            </h3>
                            <small style="color: #64748b; font-weight: 600;">Daily Page Views vs Unique Visitor footprint</small>
                        </div>
                    </div>
                    <div class="chart-card-container">
                        <canvas id="trafficTrendChart"></canvas>
                    </div>
                </div>

                <div class="portal-card">
                    <div style="margin-bottom: 20px;">
                        <h3 style="margin: 0; font-size: 1.15rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-clock" style="color: #ea580c;"></i> 24-Hour Peak Activity
                        </h3>
                        <small style="color: #64748b; font-weight: 600;">Peak hour: <b><?php echo $hourly_labels[$peak_hour]; ?></b> (<?php echo number_format($peak_hour_hits); ?> hits)</small>
                    </div>
                    <div class="chart-card-container">
                        <canvas id="hourlyBarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 2: Donut Breakdowns (Device vs Role vs Browser) -->
            <div class="analytics-grid-3col">
                <div class="portal-card">
                    <h3 style="margin: 0 0 16px; font-size: 1.1rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-laptop" style="color: #0284c7;"></i> Device Distribution
                    </h3>
                    <div class="chart-donut-container">
                        <canvas id="deviceDonutChart"></canvas>
                    </div>
                </div>

                <div class="portal-card">
                    <h3 style="margin: 0 0 16px; font-size: 1.1rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-users-cog" style="color: #7c3aed;"></i> Portal Audience Roles
                    </h3>
                    <div class="chart-donut-container">
                        <canvas id="roleDonutChart"></canvas>
                    </div>
                </div>

                <div class="portal-card">
                    <h3 style="margin: 0 0 16px; font-size: 1.1rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-compass" style="color: #16a34a;"></i> Top Web Browsers
                    </h3>
                    <div class="chart-donut-container">
                        <canvas id="browserDonutChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 3: Popular Content & Traffic Sources -->
            <div class="analytics-grid-2col">
                <!-- Popular Content with Visual Progress Bars -->
                <div class="portal-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <h3 style="margin: 0; font-size: 1.15rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-fire" style="color: #e11d48;"></i> Most Visited Pages & Endpoints
                        </h3>
                    </div>
                    <ul class="progress-rank-list">
                        <?php if (!$popular_pages || $popular_pages->num_rows == 0): ?>
                            <li style="text-align: center; color: #94a3b8; padding: 25px;">No traffic metrics logged yet.</li>
                        <?php else: ?>
                            <?php 
                            $max_hits = 1;
                            $pages_data = [];
                            while ($p = $popular_pages->fetch_assoc()) {
                                $pages_data[] = $p;
                                if ((int)$p['hits'] > $max_hits) $max_hits = (int)$p['hits'];
                            }
                            foreach ($pages_data as $p): 
                                $pct = round(((int)$p['hits'] / $max_hits) * 100);
                            ?>
                                <li class="progress-rank-item">
                                    <div class="progress-rank-header">
                                        <span style="font-family: monospace; color: var(--portal-dark); word-break: break-all;">
                                            <i class="fas fa-file-alt" style="color: var(--portal-blue); margin-right: 6px;"></i>
                                            <?php echo htmlspecialchars($p['page_visited']); ?>
                                        </span>
                                        <span style="color: #64748b; font-size: 0.8rem; font-weight: 800;">
                                            <?php echo number_format($p['hits']); ?> hits (<?php echo number_format($p['unique_ips']); ?> uniques)
                                        </span>
                                    </div>
                                    <div class="progress-rank-bar">
                                        <div class="progress-rank-fill" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Traffic Referral Breakdown -->
                <div class="portal-card">
                    <h3 style="margin: 0 0 20px; font-size: 1.15rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-external-link-alt" style="color: var(--portal-blue);"></i> Referral & Direct Sources
                    </h3>
                    <ul class="progress-rank-list">
                        <?php if (!$referrers || $referrers->num_rows == 0): ?>
                            <li style="text-align: center; color: #94a3b8; padding: 25px;">No referral data recorded.</li>
                        <?php else: ?>
                            <?php while($ref = $referrers->fetch_assoc()): 
                                $ref_pct = $total_visits > 0 ? round(((int)$ref['hits'] / $total_visits) * 100, 1) : 0;
                            ?>
                                <li class="progress-rank-item">
                                    <div class="progress-rank-header">
                                        <span style="color: var(--portal-dark); font-weight: 700;">
                                            <i class="fas fa-link" style="color: #64748b; margin-right: 6px;"></i>
                                            <?php echo htmlspecialchars($ref['source_domain']); ?>
                                        </span>
                                        <span style="color: var(--portal-blue); font-weight: 800;">
                                            <?php echo number_format($ref['hits']); ?> (<?php echo $ref_pct; ?>%)
                                        </span>
                                    </div>
                                    <div class="progress-rank-bar">
                                        <div class="progress-rank-fill" style="width: <?php echo $ref_pct; ?>%; background: linear-gradient(90deg, #10b981, #059669);"></div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Row 4: Real-Time Stream (Latest 50 Hits) -->
            <div class="portal-card" style="padding: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.2rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-stream" style="color: var(--portal-blue);"></i> Real-Time Visitor Telemetry Stream
                        </h3>
                        <small style="color: #64748b; font-weight: 600;">Latest 50 active requests intercepted</small>
                    </div>
                    <div style="position: relative; min-width: 250px;">
                        <input type="text" id="streamSearchInput" placeholder="Filter by IP, page, role..." onkeyup="filterLiveStreamTable()" 
                               style="width: 100%; padding: 10px 14px 10px 36px; border-radius: 12px; border: 2px solid #e2e8f0; outline: none; font-family: inherit; font-size: 0.9rem; box-sizing: border-box;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.85rem;"></i>
                    </div>
                </div>

                <div class="portal-table-container">
                    <table class="live-stream-table" id="liveStreamTable">
                        <thead>
                            <tr>
                                <th>Visitor IP & Time</th>
                                <th>Client Platform</th>
                                <th>Page Visited / Origin</th>
                                <th>Portal Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$latest_visitors || $latest_visitors->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No web traffic detected yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($v = $latest_visitors->fetch_assoc()): 
                                    $d_info = get_device_info($v['user_agent']);
                                    $role_slug = strtolower($v['user_role'] ?? 'guest');
                                ?>
                                    <tr class="live-stream-row">
                                        <td>
                                            <div style="font-weight: 800; color: var(--portal-dark); font-family: monospace; font-size: 0.92rem;">
                                                <?php echo htmlspecialchars($v['ip_address']); ?>
                                            </div>
                                            <small style="color: #64748b; font-weight: 600;">
                                                <i class="far fa-clock"></i> <?php echo date('d M, Y - h:i A', strtotime($v['visited_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="device-pill" style="margin-bottom: 4px;">
                                                <i class="fas <?php echo $d_info['icon']; ?>"></i> <?php echo $d_info['device']; ?>
                                            </div>
                                            <div style="font-size: 0.78rem; font-weight: 700; color: #64748b;">
                                                <?php echo $d_info['browser']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--portal-blue); font-family: monospace; font-size: 0.88rem; word-break: break-all;">
                                                <?php echo htmlspecialchars($v['page_visited']); ?>
                                            </div>
                                            <?php if (!empty($v['referrer'])): ?>
                                                <small style="color: #94a3b8; font-weight: 600; display: block; margin-top: 2px;">
                                                    <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(parse_url($v['referrer'], PHP_URL_HOST) ?: $v['referrer']); ?>
                                                </small>
                                            <?php else: ?>
                                                <small style="color: #94a3b8; font-weight: 600; display: block; margin-top: 2px;">
                                                    <i class="fas fa-link"></i> Direct Traffic
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="role-pill role-<?php echo $role_slug; ?>"><?php echo htmlspecialchars($v['user_role']); ?></span>
                                            <?php if($v['user_role'] === 'admin' && !empty($v['user_id'])): ?>
                                                <small style="display: block; margin-top: 3px; font-weight: 700; color: #94a3b8;">Admin #<?php echo (int)$v['user_id']; ?></small>
                                            <?php elseif($v['user_role'] === 'parent' && !empty($v['parent_id'])): ?>
                                                <small style="display: block; margin-top: 3px; font-weight: 700; color: #94a3b8;">Parent #<?php echo (int)$v['parent_id']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Stream Tabbed Pagination Bar (10 records per tab) -->
                <div class="stream-pagination-bar" id="streamPaginationBar">
                    <div style="font-size: 0.85rem; color: #64748b; font-weight: 700;">
                        Showing <span id="streamPageStart" style="color: var(--portal-blue); font-weight: 800;">1</span> - <span id="streamPageEnd" style="color: var(--portal-blue); font-weight: 800;">10</span> of <span id="streamTotalRows" style="font-weight: 800; color: var(--portal-dark);">0</span> entries
                    </div>
                    <div class="stream-page-tabs" id="streamPageTabs">
                        <!-- Generated page tabs [Prev] [1] [2] [3] ... [Next] -->
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: SYSTEM ACTION AUDIT TRAIL           -->
        <!-- ========================================== -->
        <div class="tab-pane" id="pane-audits">
            <!-- Filter Controls -->
            <form action="" method="GET" id="search-form">
                <input type="hidden" name="tab" value="audits">
                <div class="filter-glass-box">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) auto; gap: 16px; align-items: end;">
                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label>Filter By User Role</label>
                            <select name="role" onchange="document.getElementById('search-form').submit()">
                                <option value="">-- All User Roles --</option>
                                <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="parent" <?php echo $filter_role == 'parent' ? 'selected' : ''; ?>>Parent</option>
                                <option value="teacher" <?php echo $filter_role == 'teacher' ? 'selected' : ''; ?>>Faculty / Teacher</option>
                                <option value="guest" <?php echo $filter_role == 'guest' ? 'selected' : ''; ?>>Guest / Public</option>
                            </select>
                        </div>
                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label>Search Identity / Action Details</label>
                            <input type="text" name="search" placeholder="e.g. email, student name, fee amount, IP..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn-portal" style="padding: 14px 22px;"><i class="fas fa-search"></i> Search Logs</button>
                            <?php if(!empty($filter_role) || !empty($search_query)): ?>
                                <a href="visitors.php?tab=audits" class="btn-portal" style="background: #ffffff; color: var(--portal-blue); border: 2px solid #e2e8f0; padding: 12px 18px; text-decoration: none; box-shadow: none;"><i class="fas fa-sync-alt"></i> Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Action Grid Card -->
            <div class="portal-card" style="padding: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 1.2rem; color: var(--portal-dark); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-history" style="color: var(--portal-blue);"></i> Security & Activity Audit Log
                    </h3>
                    <span style="font-size: 0.8rem; font-weight: 800; background: rgba(37,99,235,0.1); color: var(--portal-blue); padding: 4px 10px; border-radius: 20px;">
                        <?php echo $audit_logs ? $audit_logs->num_rows : 0; ?> Records
                    </span>
                </div>

                <div class="portal-table-container">
                    <table class="live-stream-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">User Identity & Timestamp</th>
                                <th style="width: 15%;">Role</th>
                                <th style="width: 45%;">Audited Action & Details</th>
                                <th style="width: 15%;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$audit_logs || $audit_logs->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 35px;">No audit entries match the current filter.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($log = $audit_logs->fetch_assoc()): 
                                    $role_slug = strtolower($log['user_role'] ?? 'guest');
                                ?>
                                    <tr class="live-stream-row">
                                        <td>
                                            <div style="font-weight: 800; color: var(--portal-dark);"><?php echo htmlspecialchars($log['username'] ?: 'Anonymous'); ?></div>
                                            <small style="color: #64748b; font-weight: 600;">
                                                <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y - h:i A', strtotime($log['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="role-pill role-<?php echo $role_slug; ?>"><?php echo htmlspecialchars($log['user_role']); ?></span>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; background: #f1f5f9; color: var(--portal-blue); font-size: 0.75rem; font-weight: 800; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; margin-bottom: 4px;">
                                                <?php echo str_replace('_', ' ', htmlspecialchars($log['action_type'])); ?>
                                            </span>
                                            <div style="font-weight: 600; color: #334155; font-size: 0.9rem; word-break: break-word;">
                                                <?php echo htmlspecialchars($log['action_details']); ?>
                                            </div>
                                        </td>
                                        <td style="font-family: monospace; font-weight: 800; font-size: 0.85rem; color: #64748b;">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
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

    <!-- Chart.js Visualization Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Chart 1: Daily Traffic Line/Area Chart
            const ctxTraffic = document.getElementById('trafficTrendChart').getContext('2d');
            
            const gradViews = ctxTraffic.createLinearGradient(0, 0, 0, 260);
            gradViews.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
            gradViews.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

            const gradUniques = ctxTraffic.createLinearGradient(0, 0, 0, 260);
            gradUniques.addColorStop(0, 'rgba(124, 58, 237, 0.25)');
            gradUniques.addColorStop(1, 'rgba(124, 58, 237, 0.0)');

            new Chart(ctxTraffic, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Total Page Views',
                            data: <?php echo json_encode($chart_hits); ?>,
                            borderColor: '#2563eb',
                            backgroundColor: gradViews,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#2563eb',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Unique Visitors',
                            data: <?php echo json_encode($chart_uniques); ?>,
                            borderColor: '#7c3aed',
                            backgroundColor: gradUniques,
                            borderWidth: 2,
                            borderDash: [4, 4],
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#7c3aed',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: 'Outfit', weight: '700' } } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Chart 2: Hourly Activity Bar Chart
            const ctxHourly = document.getElementById('hourlyBarChart').getContext('2d');
            new Chart(ctxHourly, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($hourly_labels); ?>,
                    datasets: [{
                        label: 'Hits by Hour',
                        data: <?php echo json_encode($hourly_data); ?>,
                        backgroundColor: '#ea580c',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 45 } }
                    }
                }
            });

            // Chart 3: Device Donut Chart
            const ctxDevice = document.getElementById('deviceDonutChart').getContext('2d');
            new Chart(ctxDevice, {
                type: 'doughnut',
                data: {
                    labels: ['Mobile', 'Desktop', 'Tablet'],
                    datasets: [{
                        data: [<?php echo $mobile_hits; ?>, <?php echo $desktop_hits; ?>, <?php echo $tablet_hits; ?>],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Outfit', weight: '700' } } } }
                }
            });

            // Chart 4: Role Donut Chart
            const ctxRole = document.getElementById('roleDonutChart').getContext('2d');
            new Chart(ctxRole, {
                type: 'doughnut',
                data: {
                    labels: ['Guests', 'Parents', 'Admin', 'Faculty'],
                    datasets: [{
                        data: [
                            <?php echo $role_counts['guest']; ?>, 
                            <?php echo $role_counts['parent']; ?>, 
                            <?php echo $role_counts['admin']; ?>, 
                            <?php echo $role_counts['teacher']; ?>
                        ],
                        backgroundColor: ['#0284c7', '#16a34a', '#dc2626', '#7c3aed'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Outfit', weight: '700' } } } }
                }
            });

            // Chart 5: Browser Breakdown
            const ctxBrowser = document.getElementById('browserDonutChart').getContext('2d');
            new Chart(ctxBrowser, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($browser_stats)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($browser_stats)); ?>,
                        backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Outfit', weight: '700', size: 10 } } } }
                }
            });
        });

        // Tabs Management
        function switchTab(tabId) {
            document.getElementById('btn-analytics').classList.remove('active');
            document.getElementById('btn-audits').classList.remove('active');
            document.getElementById('pane-analytics').classList.remove('active');
            document.getElementById('pane-audits').classList.remove('active');

            if (tabId === 'audits') {
                document.getElementById('btn-audits').classList.add('active');
                document.getElementById('pane-audits').classList.add('active');
                window.location.hash = 'audits';
            } else {
                document.getElementById('btn-analytics').classList.add('active');
                document.getElementById('pane-analytics').classList.add('active');
                window.location.hash = 'analytics';
            }
        }

        // ----------------------------------------------------
        // Stream Pagination Engine (10 records per tab)
        // ----------------------------------------------------
        const streamPageSize = 10;
        let streamCurrentPage = 1;

        function getFilteredStreamRows() {
            const query = (document.getElementById('streamSearchInput')?.value || '').toLowerCase().trim();
            const allRows = Array.from(document.querySelectorAll('#liveStreamTable tbody tr.live-stream-row'));
            if (!query) {
                return allRows;
            }
            return allRows.filter(row => row.innerText.toLowerCase().includes(query));
        }

        function updateStreamPagination() {
            const allRows = Array.from(document.querySelectorAll('#liveStreamTable tbody tr.live-stream-row'));
            const matchingRows = getFilteredStreamRows();
            const totalMatching = matchingRows.length;
            const totalPages = Math.ceil(totalMatching / streamPageSize) || 1;

            if (streamCurrentPage > totalPages) {
                streamCurrentPage = totalPages;
            }
            if (streamCurrentPage < 1) {
                streamCurrentPage = 1;
            }

            // Hide all rows first
            allRows.forEach(r => r.style.display = 'none');

            // Calculate current 10-item slice
            const startIndex = (streamCurrentPage - 1) * streamPageSize;
            const endIndex = Math.min(startIndex + streamPageSize, totalMatching);

            // Show current page items
            for (let i = startIndex; i < endIndex; i++) {
                if (matchingRows[i]) {
                    matchingRows[i].style.display = '';
                }
            }

            // Update range counters
            const startLabel = document.getElementById('streamPageStart');
            const endLabel = document.getElementById('streamPageEnd');
            const totalLabel = document.getElementById('streamTotalRows');
            if (startLabel) startLabel.textContent = totalMatching > 0 ? (startIndex + 1) : 0;
            if (endLabel) endLabel.textContent = endIndex;
            if (totalLabel) totalLabel.textContent = totalMatching;

            // Render Page Number Tabs
            const tabsContainer = document.getElementById('streamPageTabs');
            if (!tabsContainer) return;
            tabsContainer.innerHTML = '';

            if (totalPages <= 1) {
                const singleBtn = document.createElement('button');
                singleBtn.className = 'stream-page-tab active';
                singleBtn.textContent = '1';
                tabsContainer.appendChild(singleBtn);
                return;
            }

            // Previous 10 Tab
            const prevBtn = document.createElement('button');
            prevBtn.className = 'stream-page-tab';
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevBtn.disabled = streamCurrentPage === 1;
            prevBtn.title = 'Previous Page';
            prevBtn.onclick = () => {
                if (streamCurrentPage > 1) {
                    streamCurrentPage--;
                    updateStreamPagination();
                }
            };
            tabsContainer.appendChild(prevBtn);

            // Page Number Tabs
            for (let p = 1; p <= totalPages; p++) {
                if (p === 1 || p === totalPages || (p >= streamCurrentPage - 2 && p <= streamCurrentPage + 2)) {
                    const pageBtn = document.createElement('button');
                    pageBtn.className = 'stream-page-tab' + (p === streamCurrentPage ? ' active' : '');
                    pageBtn.textContent = p;
                    pageBtn.onclick = () => {
                        streamCurrentPage = p;
                        updateStreamPagination();
                    };
                    tabsContainer.appendChild(pageBtn);
                } else if (p === streamCurrentPage - 3 || p === streamCurrentPage + 3) {
                    const dots = document.createElement('span');
                    dots.style.padding = '0 4px';
                    dots.style.color = '#94a3b8';
                    dots.style.fontWeight = '800';
                    dots.textContent = '...';
                    tabsContainer.appendChild(dots);
                }
            }

            // Next 10 Tab
            const nextBtn = document.createElement('button');
            nextBtn.className = 'stream-page-tab';
            nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextBtn.disabled = streamCurrentPage === totalPages;
            nextBtn.title = 'Next Page';
            nextBtn.onclick = () => {
                if (streamCurrentPage < totalPages) {
                    streamCurrentPage++;
                    updateStreamPagination();
                }
            };
            tabsContainer.appendChild(nextBtn);
        }

        // Live stream filter search
        function filterLiveStreamTable() {
            streamCurrentPage = 1;
            updateStreamPagination();
        }

        // Restore active tab & initialize stream pagination
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            const hash = window.location.hash;
            if (activeTab === 'audits' || hash === '#audits') {
                switchTab('audits');
            } else {
                switchTab('analytics');
            }

            // Initialize 10-per-tab stream pagination
            updateStreamPagination();
        });
    </script>
</body>
</html>
