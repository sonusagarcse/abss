<?php
// counselor/dashboard.php - Counselor Command Center
require_once 'includes/auth.php';

$counselor_name = $_SESSION['counselor_name'] ?? 'Counselor';
$user_perms = $counselor_permissions ?? [];
$catalog = get_counselor_modules_catalog();

// ----------------------------------------------------
// Metrics Calculation (Respecting module permissions)
// ----------------------------------------------------
$total_inquiries = 0;
$new_inquiries = 0;
if (in_array('inquiries', $user_perms)) {
    $r = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status IN ('new', 'pending', '') OR status IS NULL THEN 1 ELSE 0 END) as new_cnt FROM inquiries");
    if ($r && $row = $r->fetch_assoc()) {
        $total_inquiries = (int)$row['total'];
        $new_inquiries = (int)$row['new_cnt'];
    }
}

$total_admissions = 0;
$pending_admissions = 0;
if (in_array('admissions', $user_perms)) {
    $r = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Pending' OR status = '' OR status IS NULL THEN 1 ELSE 0 END) as pending_cnt FROM admissions");
    if ($r && $row = $r->fetch_assoc()) {
        $total_admissions = (int)$row['total'];
        $pending_admissions = (int)$row['pending_cnt'];
    }
}

$today_visitors = 0;
if (in_array('visitors', $user_perms)) {
    $r = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM site_visitors WHERE DATE(visited_at) = CURDATE()");
    if ($r && $row = $r->fetch_assoc()) {
        $today_visitors = (int)$row['cnt'];
    }
}

$total_students = 0;
if (in_array('students', $user_perms)) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE status = 'active'");
    if ($r && $row = $r->fetch_assoc()) {
        $total_students = (int)$row['cnt'];
    }
}

$open_tickets = 0;
if (in_array('tickets', $user_perms)) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM support_tickets WHERE status != 'resolved'");
    if ($r && $row = $r->fetch_assoc()) {
        $open_tickets = (int)$row['cnt'];
    }
}

// Recent Inquiries for quick lead conversion
$recent_inquiries = [];
if (in_array('inquiries', $user_perms)) {
    $res = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
    if ($res) {
        while ($inq = $res->fetch_assoc()) {
            $recent_inquiries[] = $inq;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard | ABSS Counseling Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 60%, #115e59 100%);
            border-radius: 24px;
            padding: 32px;
            color: #ffffff;
            margin-bottom: 28px;
            box-shadow: 0 15px 35px rgba(13, 148, 136, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .welcome-meta h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .welcome-meta p {
            margin: 6px 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .perm-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .stat-info h3 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 900;
            color: #0f172a;
        }
        .stat-info p {
            margin: 2px 0 0 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .modules-hub {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 14px;
        }
        .hub-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 22px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .hub-card:hover {
            border-color: #0d9488;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(13, 148, 136, 0.12);
        }
        .hub-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }
        .hub-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .hub-title {
            font-weight: 800;
            font-size: 1rem;
            color: #0f172a;
        }
        .hub-desc {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.45;
            margin-bottom: 16px;
        }
        .hub-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            font-weight: 800;
            color: #0d9488;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }

        .table-responsive {
            overflow-x: auto;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }
        .custom-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.76rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .custom-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.86rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <!-- Welcome Header -->
            <div class="welcome-card">
                <div class="welcome-meta">
                    <h1>Welcome, <?php echo htmlspecialchars($counselor_name); ?>!</h1>
                    <p>
                        ABSS Admission Counseling &amp; Student Guidance Desk • 
                        <strong><?php echo date('l, d F Y'); ?></strong>
                    </p>
                </div>
                <div>
                    <div class="perm-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span><?php echo count($user_perms); ?> Modules Authorized by Admin</span>
                    </div>
                </div>
            </div>

            <!-- Key Metrics Grid -->
            <div class="stats-grid">
                <?php if (in_array('inquiries', $user_perms)): ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_inquiries; ?></h3>
                            <p>Leads &amp; Inquiries (<?php echo $new_inquiries; ?> New)</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('admissions', $user_perms)): ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f0fdf4; color: #059669;">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_admissions; ?></h3>
                            <p>Admissions (<?php echo $pending_admissions; ?> Pending)</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('students', $user_perms)): ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f5f3ff; color: #7c3aed;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('visitors', $user_perms)): ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff7ed; color: #ea580c;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $today_visitors; ?></h3>
                            <p>Today's Portal Visitors</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('tickets', $user_perms)): ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f0f9ff; color: #0284c7;">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $open_tickets; ?></h3>
                            <p>Open Helpdesk Tickets</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Authorized Modules Quick Access Grid -->
            <div class="portal-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <div>
                        <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0;">
                            <i class="fas fa-th" style="color: #0d9488;"></i> Your Authorized Workspaces
                        </h2>
                        <p style="color: #64748b; font-size: 0.84rem; margin: 3px 0 0 0;">
                            Modules enabled for your counselor account by the Institute Administrator.
                        </p>
                    </div>
                </div>

                <?php if (empty($user_perms)): ?>
                    <div style="text-align: center; padding: 50px 20px; color: #94a3b8;">
                        <i class="fas fa-lock" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                        <h3 style="color: #475569; font-weight: 800; margin-bottom: 6px;">No Modules Enabled Yet</h3>
                        <p style="font-size: 0.88rem; max-width: 440px; margin: 0 auto;">
                            The administrator has not granted access to any modules yet. Please contact the administration office to configure your permissions.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="modules-hub">
                        <?php foreach ($user_perms as $mod_key): ?>
                            <?php if (isset($catalog[$mod_key])): 
                                $m = $catalog[$mod_key];
                            ?>
                                <a href="<?php echo $mod_key; ?>.php" class="hub-card">
                                    <div>
                                        <div class="hub-top">
                                            <div class="hub-icon" style="background: <?php echo $m['color']; ?>18; color: <?php echo $m['color']; ?>;">
                                                <i class="<?php echo $m['icon']; ?>"></i>
                                            </div>
                                            <div class="hub-title"><?php echo htmlspecialchars($m['label']); ?></div>
                                        </div>
                                        <div class="hub-desc"><?php echo htmlspecialchars($m['desc']); ?></div>
                                    </div>
                                    <div class="hub-footer">
                                        <span>Open Workspace</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Inquiries Quick Lead List (If permitted) -->
            <?php if (in_array('inquiries', $user_perms) && !empty($recent_inquiries)): ?>
                <div class="portal-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <h2 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">
                                <i class="fas fa-user-clock" style="color: #2563eb;"></i> Fresh Admission Inquiries
                            </h2>
                            <p style="color: #64748b; font-size: 0.82rem; margin: 2px 0 0 0;">Recent candidate inquiries waiting for consultation follow-up.</p>
                        </div>
                        <a href="inquiries.php" class="btn-portal btn-secondary" style="font-size: 0.8rem; padding: 6px 14px;">
                            View All Inquiries →
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Candidate Name</th>
                                    <th>Parent Phone</th>
                                    <th>Target Exam / Class</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_inquiries as $inq): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #0f172a;">
                                            <?php echo htmlspecialchars($inq['candidate_name']); ?>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($inq['parent_phone']); ?>" style="color: #059669; text-decoration: none; font-weight: 700;">
                                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($inq['parent_phone']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span style="background: #eff6ff; color: #1d4ed8; padding: 3px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($inq['target_exam'] ?: 'General'); ?>
                                            </span>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo date('d M Y, h:i A', strtotime($inq['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $st = strtolower($inq['status'] ?? 'new');
                                            $st_color = ($st === 'admitted') ? '#166534; background: #dcfce7;' : (($st === 'contacted') ? '#0369a1; background: #e0f2fe;' : '#92400e; background: #fef3c7;');
                                            ?>
                                            <span style="padding: 3px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem; color: <?php echo $st_color; ?>">
                                                <?php echo ucfirst($st); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="inquiries.php?filter=all" class="btn-portal btn-secondary" style="padding: 4px 10px; font-size: 0.78rem;">
                                                Consult →
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>
