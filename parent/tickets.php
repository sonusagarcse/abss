<?php
// parent/tickets.php - Parent Helpdesk & Support Tickets

require_once 'includes/auth.php';

$msg = '';
$err = '';

$pid = (int)$_SESSION['parent_id'];

// 1. Fetch children for form dropdown
$children_query = $conn->prepare("SELECT id, name FROM students WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$children_query->bind_param("i", $pid);
$children_query->execute();
$children_res = $children_query->get_result();
$children_list = [];
while($c = $children_res->fetch_assoc()) {
    $children_list[] = $c;
}

// 2. Handle Raise Ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['raise_ticket'])) {
    $student_id = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        $err = "Subject and Message are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO support_tickets (parent_id, student_id, subject, message, status) VALUES (?, ?, ?, ?, 'open')");
        $stmt->bind_param("iiss", $pid, $student_id, $subject, $message);
        
        if ($stmt->execute()) {
            $tkt_id = $conn->insert_id;
            $msg = "Support ticket successfully created. Ref ID: #ABSS-TKT-" . str_pad($tkt_id, 4, '0', STR_PAD_LEFT);
            
            // Log Support Ticket Raised Action
            log_activity('ticket_raised', "Parent raised support ticket #ABSS-TKT-" . str_pad($tkt_id, 4, '0', STR_PAD_LEFT) . " (Subject: $subject)");

            // Fetch parent and school metadata for SMTP email dispatch
            $parent_stmt = $conn->prepare("SELECT parent_name, email FROM parents WHERE id = ?");
            $parent_stmt->bind_param("i", $pid);
            $parent_stmt->execute();
            $parent = $parent_stmt->get_result()->fetch_assoc();

            if ($parent) {
                require_once __DIR__ . '/../includes/mail_helper.php';
                
                // Email 1: Confirmation to Parent
                $parent_html = get_ticket_raised_template(
                    $parent['parent_name'],
                    $subject,
                    $message,
                    'open',
                    $tkt_id
                );
                
                send_smtp_email(
                    $parent['email'],
                    "Support Ticket Raised - #ABSS-TKT-" . str_pad($tkt_id, 4, '0', STR_PAD_LEFT),
                    $parent_html
                );

                // Email 2: Alert to School Admin
                $settings = getAllSettings();
                $admin_email = $settings['email'] ?? 'abssimamganj@gmail.com';
                $admin_html = get_base_template(
                    "New Helpdesk Ticket Billed",
                    '<div class="greeting">New Support Ticket Raised</div>
                     <p>A new support query has been billed by a parent. Please find details below:</p>
                     <div class="info-card">
                         <table role="presentation" width="100%">
                             <tr>
                                 <td style="padding:10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase;">Parent Name</td>
                                 <td style="padding:10px 0; font-weight:800; color:#0d47a1; text-align:right;">' . htmlspecialchars($parent['parent_name']) . '</td>
                             </tr>
                             <tr>
                                 <td style="padding:10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Parent Email</td>
                                 <td style="padding:10px 0; font-weight:800; color:#0d47a1; text-align:right; border-top:1px solid #eef2ff;">' . htmlspecialchars($parent['email']) . '</td>
                             </tr>
                             <tr>
                                 <td style="padding:10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Subject</td>
                                 <td style="padding:10px 0; font-weight:800; color:#0d47a1; text-align:right; border-top:1px solid #eef2ff;">' . htmlspecialchars($subject) . '</td>
                             </tr>
                         </table>
                     </div>
                     <div style="background:#f8faff; padding:15px; border-left:4px solid #0d47a1; border-radius:4px; font-size:14px; color:#333; line-height:1.6;">
                         <strong>Message Body:</strong><br>
                         ' . nl2br(htmlspecialchars($message)) . '
                     </div>'
                );
                
                send_smtp_email(
                    $admin_email,
                    "Alert: New Helpdesk Ticket Raised - " . $parent['parent_name'],
                    $admin_html
                );
            }
        } else {
            $err = "Error submitting support ticket. Please try again.";
        }
    }
}

// 3. Fetch past support tickets
$tickets = $conn->query("
    SELECT t.*, s.name AS student_name 
    FROM support_tickets t 
    LEFT JOIN students s ON t.student_id = s.id 
    WHERE t.parent_id = $pid 
    ORDER BY t.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk Support | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .ticket-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-open { background: #fffbeb; color: #b45309; }
        .status-resolved { background: #e8f5e9; color: #2e7d32; }
        .status-closed { background: #f1f5f9; color: #475569; }
        
        .ticket-message { background: #f8faff; border-radius: 12px; padding: 15px; font-size: 0.85rem; line-height: 1.5; color: #5c6bc0; border: 1px dashed #eef2ff; margin-top: 8px; }
        
        @media (max-width: 900px) {
            .ticket-grid { grid-template-columns: 1fr !important; gap: 30px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Helpdesk & Support</h1>
            <p>Raise questions, report concerns, or contact our school administrative office.</p>
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

        <div class="ticket-grid">
            <!-- Form Card: Raise Ticket -->
            <div class="portal-card" style="height: fit-content;">
                <h3 style="margin-bottom: 25px; color:var(--portal-blue);"><i class="fas fa-paper-plane" style="margin-right:8px; color:var(--portal-purple);"></i> New Support Ticket</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Regarding Student / Ward (Optional)</label>
                        <select name="student_id">
                            <option value="">-- General Question / Other --</option>
                            <?php foreach($children_list as $child): ?>
                                <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Subject / Topic</label>
                        <input type="text" name="subject" placeholder="e.g. Question about monthly fees" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Detailed Message</label>
                        <textarea name="message" rows="6" placeholder="Describe your concern or question in detail..." required></textarea>
                    </div>
                    <button type="submit" name="raise_ticket" class="btn-portal w-100" style="padding:18px;">Submit Support Request</button>
                </form>
            </div>

            <!-- List Card: Ticket History -->
            <div class="list-section">
                <h3 style="margin-bottom: 20px; padding-left: 10px;"><i class="fas fa-history" style="margin-right:8px; color:var(--portal-blue);"></i> Ticket History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket Details</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets->num_rows == 0): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #9aa5ce; padding: 40px;">No support tickets raised yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php while($t = $tickets->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:var(--portal-blue); font-weight:800;">
                                        #ABSS-TKT-<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?><br>
                                        <small style="color:#9aa5ce; font-weight:600;"><?php echo date('d M, Y', strtotime($t['created_at'])); ?></small>
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
