<?php
// counselor/tickets.php - Parent & Student Support Helpdesk
require_once 'includes/auth.php';
require_counselor_module('tickets');

$msg = '';

// Handle Status Update
if (isset($_GET['id']) && isset($_GET['status'])) {
    $tid = (int)$_GET['id'];
    $st = in_array($_GET['status'], ['open', 'resolved', 'closed']) ? $_GET['status'] : 'open';
    $conn->query("UPDATE support_tickets SET status = '$st' WHERE id = $tid");
    $msg = "Ticket status updated to " . strtoupper($st) . ".";
}

$status_filter = $_GET['filter'] ?? 'all';
$sql = "SELECT t.*, p.name as parent_name, p.phone as parent_phone, s.name as student_name 
        FROM support_tickets t 
        LEFT JOIN parents p ON t.parent_id = p.id 
        LEFT JOIN students s ON t.student_id = s.id";
if ($status_filter !== 'all') {
    $clean_f = $conn->real_escape_string($status_filter);
    $sql .= " WHERE t.status = '$clean_f'";
}
$sql .= " ORDER BY t.created_at DESC";
$tickets = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .custom-table tr:hover { background: #f8fafc; }
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 50px; font-size: 0.82rem; font-weight: 700; text-decoration: none; color: #64748b; background: #f1f5f9; }
        .filter-tab.active { background: #0284c7; color: #ffffff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-ticket-alt" style="color: #0284c7;"></i> Support &amp; Helpdesk Inquiries
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Resolve questions and assistance requests from parents and students.
                    </p>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="portal-card">
                <div class="filter-tabs">
                    <a href="tickets.php?filter=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Tickets</a>
                    <a href="tickets.php?filter=open" class="filter-tab <?php echo $status_filter === 'open' ? 'active' : ''; ?>">Open / Pending</a>
                    <a href="tickets.php?filter=resolved" class="filter-tab <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                    <a href="tickets.php?filter=closed" class="filter-tab <?php echo $status_filter === 'closed' ? 'active' : ''; ?>">Closed</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Ticket Subject</th>
                                <th>Submitted By</th>
                                <th>Message / Details</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$tickets || $tickets->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-ticket-alt" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                                        No support tickets found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($t = $tickets->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a;">
                                                <?php echo htmlspecialchars($t['subject'] ?: 'Support Request #' . $t['id']); ?>
                                            </div>
                                            <small style="color: #64748b;">Ticket #<?php echo $t['id']; ?></small>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #334155;">
                                                <?php echo htmlspecialchars($t['parent_name'] ?: $t['student_name'] ?: 'Parent / Student'); ?>
                                            </div>
                                            <?php if (!empty($t['parent_phone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($t['parent_phone']); ?>" style="color: #059669; font-size: 0.8rem; text-decoration: none;">
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($t['parent_phone']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 320px; color: #475569;">
                                            <?php echo nl2br(htmlspecialchars($t['message'])); ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($t['status'] === 'resolved'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Resolved</span>
                                            <?php elseif ($t['status'] === 'closed'): ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Closed</span>
                                            <?php else: ?>
                                                <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Open</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if ($t['status'] !== 'resolved'): ?>
                                                <a href="tickets.php?id=<?php echo $t['id']; ?>&status=resolved" class="btn-portal btn-secondary" style="padding: 5px 10px; font-size: 0.78rem;">
                                                    <i class="fas fa-check"></i> Resolve
                                                </a>
                                            <?php else: ?>
                                                <a href="tickets.php?id=<?php echo $t['id']; ?>&status=open" class="btn-portal btn-secondary" style="padding: 5px 10px; font-size: 0.78rem;">
                                                    Reopen
                                                </a>
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
    </main>
</body>
</html>

