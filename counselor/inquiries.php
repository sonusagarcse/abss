<?php
// counselor/inquiries.php - Admission Inquiries & Lead Management
require_once 'includes/auth.php';
require_counselor_module('inquiries');

$msg = '';

// Handle Status Update
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE inquiries SET status = '$status' WHERE id = $id");
    if (function_exists('log_activity')) {
        log_activity('counselor_inquiry_update', "Counselor updated inquiry #$id status to $status");
    }
    header("Location: inquiries.php?msg=updated");
    exit();
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $msg = "Lead inquiry status updated successfully.";
}

$status_filter = $_GET['filter'] ?? 'all';
$query_sql = "SELECT * FROM inquiries";
if ($status_filter !== 'all') {
    $escaped_f = $conn->real_escape_string($status_filter);
    $query_sql .= " WHERE status = '$escaped_f'";
}
$query_sql .= " ORDER BY created_at DESC";
$inquiries = $conn->query($query_sql);

// Summary Counts
$total_inq = 0;
$new_inq = 0;
$contacted_inq = 0;
$admitted_inq = 0;

$count_res = $conn->query("SELECT status, COUNT(*) as cnt FROM inquiries GROUP BY status");
if ($count_res) {
    while ($r = $count_res->fetch_assoc()) {
        $st = strtolower($r['status'] ?: 'new');
        $cnt = (int)$r['cnt'];
        $total_inq += $cnt;
        if ($st === 'new' || $st === 'pending') $new_inq += $cnt;
        elseif ($st === 'contacted') $contacted_inq += $cnt;
        elseif ($st === 'admitted') $admitted_inq += $cnt;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Inquiries | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .stats-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .stat-info h3 { margin: 0; font-size: 1.4rem; font-weight: 800; color: #0f172a; }
        .stat-info p { margin: 2px 0 0 0; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            color: #64748b;
            background: #f1f5f9;
            transition: all 0.2s ease;
        }
        .filter-tab:hover { background: #e2e8f0; color: #0f172a; }
        .filter-tab.active { background: #0d9488; color: #ffffff; }

        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .custom-table tr:hover { background: #f8fafc; }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #334155;
            background: #fff;
            transition: all 0.15s;
        }
        .action-btn:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
        .action-btn.whatsapp { background: #dcfce7; color: #166534; border-color: #86efac; }
        .action-btn.whatsapp:hover { background: #bbf7d0; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-envelope-open-text" style="color: #2563eb;"></i> Admission Leads &amp; Inquiries
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Manage prospective student queries, consultations, and lead conversions.
                    </p>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Ribbon -->
            <div class="stats-ribbon">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_inq; ?></h3>
                        <p>Total Leads</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $new_inq; ?></h3>
                        <p>New / Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fas fa-phone-volume"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $contacted_inq; ?></h3>
                        <p>Contacted</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dcfce7; color: #166534;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $admitted_inq; ?></h3>
                        <p>Admitted</p>
                    </div>
                </div>
            </div>

            <!-- Inquiries List -->
            <div class="portal-card">
                <div class="filter-tabs">
                    <a href="inquiries.php?filter=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Leads (<?php echo $total_inq; ?>)</a>
                    <a href="inquiries.php?filter=new" class="filter-tab <?php echo $status_filter === 'new' ? 'active' : ''; ?>">New / Pending (<?php echo $new_inq; ?>)</a>
                    <a href="inquiries.php?filter=contacted" class="filter-tab <?php echo $status_filter === 'contacted' ? 'active' : ''; ?>">Contacted (<?php echo $contacted_inq; ?>)</a>
                    <a href="inquiries.php?filter=admitted" class="filter-tab <?php echo $status_filter === 'admitted' ? 'active' : ''; ?>">Converted / Admitted (<?php echo $admitted_inq; ?>)</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Candidate Details</th>
                                <th>Contact &amp; Connect</th>
                                <th>Target Program</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Counselor Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$inquiries || $inquiries->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-inbox" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                                        No inquiries found for this filter.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($inq = $inquiries->fetch_assoc()): ?>
                                    <?php 
                                    $st = strtolower($inq['status'] ?? 'new');
                                    $clean_phone = preg_replace('/[^0-9]/', '', $inq['parent_phone']);
                                    if (strlen($clean_phone) == 10) $clean_phone = '91' . $clean_phone;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a; font-size: 0.92rem;">
                                                <?php echo htmlspecialchars($inq['candidate_name']); ?>
                                            </div>
                                            <?php if (!empty($inq['email'])): ?>
                                                <div style="font-size: 0.78rem; color: #64748b;">
                                                    <?php echo htmlspecialchars($inq['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <a href="tel:<?php echo htmlspecialchars($inq['parent_phone']); ?>" class="action-btn" title="Call Parent">
                                                    <i class="fas fa-phone-alt" style="color: #059669;"></i> <?php echo htmlspecialchars($inq['parent_phone']); ?>
                                                </a>
                                                <?php if (!empty($clean_phone)): ?>
                                                    <a href="https://wa.me/<?php echo $clean_phone; ?>?text=<?php echo urlencode('Hello, this is Amar Bharat Siksha Sansthan regarding your admission inquiry for ' . $inq['candidate_name'] . '. How can we assist you today?'); ?>" target="_blank" class="action-btn whatsapp" title="Chat on WhatsApp">
                                                        <i class="fab fa-whatsapp"></i> Chat
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($inq['target_exam'] ?: 'General Inquiry'); ?>
                                            </span>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo date('d M Y, h:i A', strtotime($inq['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($st === 'admitted'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">
                                                    <i class="fas fa-check-circle"></i> Admitted
                                                </span>
                                            <?php elseif ($st === 'contacted'): ?>
                                                <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">
                                                    <i class="fas fa-phone"></i> Contacted
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">
                                                    <i class="fas fa-clock"></i> New Lead
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <a href="inquiries.php?id=<?php echo $inq['id']; ?>&status=contacted" class="action-btn" title="Mark Contacted">
                                                    <i class="fas fa-check"></i> Contacted
                                                </a>
                                                <a href="inquiries.php?id=<?php echo $inq['id']; ?>&status=admitted" class="action-btn" style="color: #166534; border-color: #86efac; background: #f0fdf4;" title="Mark Enrolled/Admitted">
                                                    <i class="fas fa-graduation-cap"></i> Admitted
                                                </a>
                                            </div>
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

