<?php
// admin/tickets.php - Admin Helpdesk & Support Ticketing Panel

require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Resolve Ticket action
if (isset($_GET['resolve'])) {
    $tkt_id = (int)$_GET['resolve'];
    
    // Fetch parent details before updating to send email
    $parent_stmt = $conn->prepare("
        SELECT t.subject, p.parent_name, p.email 
        FROM support_tickets t 
        JOIN parents p ON t.parent_id = p.id 
        WHERE t.id = ?
    ");
    $parent_stmt->bind_param("i", $tkt_id);
    $parent_stmt->execute();
    $parent = $parent_stmt->get_result()->fetch_assoc();

    if ($parent) {
        // Update ticket status
        $update_stmt = $conn->prepare("UPDATE support_tickets SET status = 'resolved' WHERE id = ?");
        $update_stmt->bind_param("i", $tkt_id);
        
        if ($update_stmt->execute()) {
            $msg = "Ticket #ABSS-TKT-" . str_pad($tkt_id, 4, '0', STR_PAD_LEFT) . " marked as resolved.";
            
            // Log Support Ticket Resolved Action
            log_activity('ticket_resolved', "Resolved support ticket #ABSS-TKT-" . str_pad($tkt_id, 4, '0', STR_PAD_LEFT) . " (Subject: " . $parent['subject'] . ")");

            // Send SMTP ticket resolved email
            require_once __DIR__ . '/../includes/mail_helper.php';
            $email_html = get_ticket_resolved_template(
                $parent['parent_name'],
                $parent['subject'],
                $tkt_id
            );
            
            send_smtp_email(
                $parent['email'],
                "Resolved: Support Ticket - #ABSS-TKT-" . str_pad($tkt_id, 4, '0', STR_PAD_LEFT),
                $email_html
            );
        } else {
            $err = "Error updating ticket status.";
        }
    } else {
        $err = "Ticket or associated parent profile not found.";
    }
}

// Fetch all support tickets
$tickets = $conn->query("
    SELECT t.*, p.parent_name, p.email AS parent_email, s.name AS student_name 
    FROM support_tickets t 
    JOIN parents p ON t.parent_id = p.id 
    LEFT JOIN students s ON t.student_id = s.id 
    ORDER BY t.status ASC, t.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk Desk | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .portal-table-container { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        th { text-align: left; padding: 15px 20px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; border: none; }
        td { padding: 25px 20px; background: #f8faff; border-top: 1px solid #eef2ff; border-bottom: 1px solid #eef2ff; color: #5c6bc0; font-weight: 600; vertical-align: top; transition: all 0.3s ease; }
        td:first-child { border-left: 1px solid #eef2ff; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #eef2ff; border-radius: 0 20px 20px 0; }
        tr:hover td { background: #f1f5ff; border-color: #dbe4ff; }
        
        .status-badge { padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; text-align: center; }
        .status-open { background: #fffbeb; color: #b45309; }
        .status-resolved { background: #e8f5e9; color: #2e7d32; }
        
        .ticket-message { background: #fff; border-radius: 12px; padding: 15px; font-size: 0.85rem; line-height: 1.5; color: #5c6bc0; border: 1px solid #eef2ff; margin-top: 8px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.01); }
        .btn-resolve { background: #e8f5e9; color: #2e7d32; border: none; padding: 10px 16px; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; box-shadow: 0 4px 10px rgba(46, 125, 50, 0.1); }
        .btn-resolve:hover { background: #2e7d32; color: #fff; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(46, 125, 50, 0.2); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Helpdesk Ticket Center</h1>
            <p>Track, manage, and resolve parents' queries and problems.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#ffebee; color:#d32f2f; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700; border: 1px solid rgba(211,47,47,0.1);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <div class="portal-card">
            <h3 style="margin-bottom: 25px; color:var(--portal-blue);"><i class="fas fa-ticket-alt" style="margin-right:8px;"></i> Support Ticket Registry</h3>
            <div class="portal-table-container">
                <table style="width: 100%; table-layout: fixed;">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Ticket ID / Date</th>
                            <th style="width: 22%;">Parent Details</th>
                            <th style="width: 41%;">Subject / Query</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 12%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets->num_rows == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #9aa5ce; padding: 40px;">No support tickets raised by parents.</td>
                            </tr>
                        <?php else: ?>
                            <?php while($t = $tickets->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:var(--portal-blue); font-weight:800; word-break: break-word;">
                                        #ABSS-TKT-<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?><br>
                                        <small style="color:#9aa5ce; font-weight:600;"><?php echo date('d M, Y', strtotime($t['created_at'])); ?></small>
                                    </td>
                                    <td style="word-break: break-word;">
                                        <div style="font-weight:800; color:#333;"><?php echo htmlspecialchars($t['parent_name']); ?></div>
                                        <small style="font-family:monospace; color:#5c6bc0; word-break: break-all; display: block; margin-top: 3px;"><?php echo htmlspecialchars($t['parent_email']); ?></small>
                                    </td>
                                    <td>
                                        <div style="font-weight:700; color:var(--portal-indigo); font-size:0.95rem;"><?php echo htmlspecialchars($t['subject']); ?></div>
                                        <?php if ($t['student_name']): ?>
                                            <div style="font-size:0.75rem; color:var(--portal-purple); font-weight:700; margin-top:2px;"><i class="fas fa-child"></i> Ward: <?php echo htmlspecialchars($t['student_name']); ?></div>
                                        <?php endif; ?>
                                        <div class="ticket-message">
                                            <?php echo nl2br(htmlspecialchars($t['message'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $t['status']; ?>"><?php echo $t['status']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($t['status'] === 'open'): ?>
                                            <a href="?resolve=<?php echo $t['id']; ?>" class="btn-resolve" onclick="return confirm('Mark this support ticket as resolved and send confirmation email?')">
                                                <i class="fas fa-check"></i> Mark Resolved
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#9aa5ce; font-size:0.85rem; font-style:italic;"><i class="fas fa-check-circle" style="color:#2e7d32;"></i> Settled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
