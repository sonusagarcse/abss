<?php
// admin/call_student.php - Dedicated Student Calling Desk & Dues Recovery Console

if (!defined('IS_COUNSELOR_VIEW')) {
    require_once __DIR__ . '/includes/auth.php';
}

require_once __DIR__ . '/../config/db.php';
$conn = getDB();

$student_id = (int)($_GET['id'] ?? 0);
if ($student_id <= 0) {
    die("Invalid student ID.");
}

$msg = '';
$err = '';

// Handle Call Log & Follow-up Submission
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST" && isset($_POST['save_call_log'])) {
    $caller_name = $_SESSION['counselor_name'] ?? ($_SESSION['username'] ?? 'Desk Counselor');
    $caller_id   = $_SESSION['counselor_id'] ?? ($_SESSION['admin_id'] ?? null);
    $caller_type = isset($_SESSION['counselor_id']) ? 'counselor' : 'admin';
    
    $phone_dialed = trim($_POST['phone_dialed'] ?? '');
    $call_outcome = trim($_POST['call_outcome'] ?? 'Answered');
    $remark       = trim($_POST['remark'] ?? '');
    $suggestions  = trim($_POST['suggestions'] ?? '');
    $followup_date = !empty($_POST['followup_date']) ? trim($_POST['followup_date']) : null;
    $followup_time = trim($_POST['followup_time'] ?? '');
    $call_duration = trim($_POST['call_duration'] ?? '');

    if (!empty($remark)) {
        $stmt = $conn->prepare("INSERT INTO student_call_logs (student_id, caller_type, caller_id, caller_name, phone_dialed, call_outcome, call_duration, remark, suggestions, followup_date, followup_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissssssss", $student_id, $caller_type, $caller_id, $caller_name, $phone_dialed, $call_outcome, $call_duration, $remark, $suggestions, $followup_date, $followup_time);
        if ($stmt->execute()) {
            $msg = "Call record and follow-up saved successfully!";
        } else {
            $err = "Database error: " . $conn->error;
        }
    } else {
        $err = "Please enter call remarks before saving.";
    }
}

// Fetch Complete Student Details
$stmt = $conn->prepare("
    SELECT s.*, 
           p.parent_name AS registered_parent_name, 
           p.email AS parent_email, 
           p.phone AS parent_phone
    FROM students s
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Fetch Generated Fees / Dues for this student
$fees_stmt = $conn->prepare("
    SELECT * FROM fees_generated 
    WHERE student_id = ? 
    ORDER BY billing_date DESC
");
$fees_stmt->bind_param("i", $student_id);
$fees_stmt->execute();
$bills_res = $fees_stmt->get_result();

$unpaid_bills = [];
$paid_bills = [];
$total_due_amount = 0.0;
$total_base_due = 0.0;
$total_fine_due = 0.0;
$unpaid_months = [];

while ($b = $bills_res->fetch_assoc()) {
    if ($b['status'] === 'unpaid') {
        $unpaid_bills[] = $b;
        $amt = (float)$b['amount'];
        $fine = (float)($b['fine_amount'] ?? 0);
        $total_due_amount += $amt;
        $total_fine_due += $fine;
        $total_base_due += max(0, $amt - $fine);
        $unpaid_months[] = $b['month_for'];
    } else {
        $paid_bills[] = $b;
    }
}

// Fetch Payment History
$pay_stmt = $conn->prepare("
    SELECT * FROM fee_payments 
    WHERE student_id = ? 
    ORDER BY payment_date DESC 
    LIMIT 10
");
$pay_stmt->bind_param("i", $student_id);
$pay_stmt->execute();
$payments_res = $pay_stmt->get_result();

// Fetch Call Logs Timeline
$logs_stmt = $conn->prepare("
    SELECT * FROM student_call_logs 
    WHERE student_id = ? 
    ORDER BY created_at DESC
");
$logs_stmt->bind_param("i", $student_id);
$logs_stmt->execute();
$call_logs = $logs_stmt->get_result();

$phone_clean = preg_replace('/[^0-9]/', '', $student['phone'] ?? '');
$wa_phone = (strlen($phone_clean) == 10) ? '91' . $phone_clean : $phone_clean;
$wa_msg = "Namaste " . ($student['parent_name'] ?: 'Guardian') . ", this is ABSS Imamganj regarding " . $student['name'] . " (Reg No: " . ($student['reg_no'] ?: 'N/A') . "). Outstanding fee dues till date: Rs. " . number_format($total_due_amount, 2) . ". Please contact us to clear the pending balance.";
$wa_url = "https://api.whatsapp.com/send?phone=" . $wa_phone . "&text=" . urlencode($wa_msg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Desk — <?php echo htmlspecialchars($student['name']); ?> | ABSS</title>
    <?php 
    if (defined('IS_COUNSELOR_VIEW')) {
        include __DIR__ . '/../counselor/includes/head_css.php';
    } else {
        include __DIR__ . '/includes/head_css.php';
    }
    ?>
    <style>
        :root {
            --call-green: #059669;
            --call-green-dark: #047857;
            --call-bg-light: #ecfdf5;
        }
        .calling-layout {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 24px;
            margin-top: 20px;
        }
        @media (max-width: 1024px) {
            .calling-layout { grid-template-columns: 1fr; }
        }
        .dossier-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
            padding: 24px;
            margin-bottom: 22px;
        }
        .dossier-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 18px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 18px;
        }
        .student-avatar-big {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            background: #f1f5f9;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .badge-dues-alert {
            background: #fee2e2;
            color: #dc2626;
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .data-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .data-item {
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }
        .data-item small {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }
        .data-item span {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.90rem;
        }
        .quick-dial-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            padding: 14px 22px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1.05rem;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.25);
            transition: all 0.2s;
        }
        .quick-dial-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(5, 150, 105, 0.35);
        }
        .chip-suggestion {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            margin: 3px;
            transition: all 0.15s;
        }
        .chip-suggestion:hover {
            background: #0d9488;
            color: #ffffff;
            border-color: #0d9488;
            transform: scale(1.02);
        }
        .quick-date-btn {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 700;
            cursor: pointer;
        }
        .quick-date-btn:hover {
            background: #0d9488;
            color: #fff;
            border-color: #0d9488;
        }
        .timeline-item {
            position: relative;
            padding-left: 28px;
            padding-bottom: 20px;
            border-left: 2px solid #e2e8f0;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
            border-left-color: transparent;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d9488;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px #0d9488;
        }
    </style>
</head>
<body>
    <?php 
    if (defined('IS_COUNSELOR_VIEW')) {
        include __DIR__ . '/../counselor/includes/sidebar.php';
    } else {
        include __DIR__ . '/includes/sidebar.php';
    }
    ?>

    <div class="main-content">
        <!-- Top Nav Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="<?php echo defined('IS_COUNSELOR_VIEW') ? 'fees.php' : 'student_dues.php'; ?>" class="btn-portal" style="background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 9px 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dues Calling Desk
                </a>
                <div>
                    <h1 style="font-size: 1.65rem; font-weight: 900; margin: 0; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-headset" style="color: #059669;"></i> Calling &amp; Follow-up Desk
                    </h1>
                    <small style="color: #64748b; font-weight: 700;">Active Calling Session for <?php echo htmlspecialchars($student['name']); ?></small>
                </div>
            </div>

            <!-- Quick Communication Trigger Bar -->
            <div style="display: flex; align-items: center; gap: 8px;">
                <?php if (!empty($phone_clean)): ?>
                    <a href="tel:<?php echo htmlspecialchars($phone_clean); ?>" class="quick-dial-btn">
                        <i class="fas fa-phone-alt"></i> Call Now (<?php echo htmlspecialchars($student['phone']); ?>)
                    </a>
                    <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-portal" style="background: #25d366; color: #ffffff; padding: 13px 18px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Due Alert
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 700;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
            <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 700;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
                <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <!-- 2-Column Responsive Layout -->
        <div class="calling-layout">
            
            <!-- LEFT COLUMN: COMPLETE STUDENT & DUES DOSSIER -->
            <div>
                <!-- 1. Profile Dossier Card -->
                <div class="dossier-card">
                    <div class="dossier-header">
                        <?php if (!empty($student['photo']) && file_exists(__DIR__ . '/../' . $student['photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($student['photo']); ?>" class="student-avatar-big" alt="Photo">
                        <?php else: ?>
                            <div class="student-avatar-big"><i class="fas fa-user-graduate"></i></div>
                        <?php endif; ?>
                        
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                                <span class="badge-dues-alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Due: ₹ <?php echo number_format($total_due_amount, 2); ?>
                                </span>
                                <span style="background: #f1f5f9; color: #475569; font-weight: 800; font-size: 0.75rem; padding: 4px 10px; border-radius: 50px;">
                                    <?php echo htmlspecialchars($student['scholar_mode'] ?: 'Day Scholar'); ?>
                                </span>
                            </div>
                            <h2 style="margin: 0 0 4px; font-size: 1.35rem; font-weight: 900; color: #0f172a;">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </h2>
                            <div style="color: #64748b; font-size: 0.85rem; font-weight: 700;">
                                Registration No: <b><?php echo htmlspecialchars($student['reg_no'] ?: 'N/A'); ?></b>
                            </div>
                        </div>
                    </div>

                    <!-- Dossier Attributes Grid -->
                    <div class="data-grid-2">
                        <div class="data-item">
                            <small>Parent / Guardian</small>
                            <span><?php echo htmlspecialchars($student['parent_name'] ?: 'N/A'); ?> (<?php echo htmlspecialchars($student['guardian_relationship'] ?: 'Father'); ?>)</span>
                        </div>
                        <div class="data-item">
                            <small>Primary Phone</small>
                            <span style="font-family: monospace; color: #059669;">
                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?>
                            </span>
                        </div>
                        <div class="data-item">
                            <small>Target School / Program</small>
                            <span><?php echo htmlspecialchars($student['target_school'] ?: 'General Academic'); ?></span>
                        </div>
                        <div class="data-item">
                            <small>Class Admitted</small>
                            <span><?php echo htmlspecialchars($student['class_admitted'] ?: 'Standard 6'); ?></span>
                        </div>
                        <div class="data-item">
                            <small>Base Monthly Fee</small>
                            <span>₹ <?php echo number_format($student['base_fee'] ?? 0, 2); ?> / mo</span>
                        </div>
                        <div class="data-item">
                            <small>Monthly Discount</small>
                            <span>₹ <?php echo number_format($student['monthly_discount'] ?? 0, 2); ?></span>
                        </div>
                        <div class="data-item" style="grid-column: span 2;">
                            <small>Full Home Address</small>
                            <span><?php echo htmlspecialchars(trim(($student['home_address'] ?? '') . ' ' . ($student['city'] ?? '') . ' ' . ($student['state'] ?? '') . ' ' . ($student['zip_code'] ?? '')) ?: 'Address not on record'); ?></span>
                        </div>
                        <?php if (!empty($student['emergency_phone'])): ?>
                            <div class="data-item" style="grid-column: span 2;">
                                <small>Emergency Contact</small>
                                <span><?php echo htmlspecialchars($student['emergency_contact_name'] ?? ''); ?> (<?php echo htmlspecialchars($student['emergency_relationship'] ?? ''); ?>) — <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($student['emergency_phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Fee Dues Ledger Card -->
                <div class="dossier-card">
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 14px; color: #0f172a; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-file-invoice-dollar" style="color: #dc2626;"></i> Itemized Dues Statement</span>
                        <span style="font-size: 0.85rem; color: #dc2626; font-weight: 800;">
                            <?php echo count($unpaid_bills); ?> Unpaid Bills
                        </span>
                    </h3>

                    <?php if (empty($unpaid_bills)): ?>
                        <div style="background: #f0fdf4; padding: 16px; border-radius: 12px; text-align: center; color: #166534; font-weight: 700;">
                            <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-bottom: 6px; display: block;"></i>
                            No outstanding dues pending for this candidate! All bills are cleared.
                        </div>
                    <?php else: ?>
                        <div style="max-height: 280px; overflow-y: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                                        <th style="padding: 8px 10px;">Month</th>
                                        <th style="padding: 8px 10px;">Invoice #</th>
                                        <th style="padding: 8px 10px; text-align: right;">Amount</th>
                                        <th style="padding: 8px 10px; text-align: right;">Fine</th>
                                        <th style="padding: 8px 10px; text-align: center;">Bill</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unpaid_bills as $ub): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 10px; font-weight: 800; color: #0f172a;">
                                                <?php echo htmlspecialchars($ub['month_for']); ?>
                                            </td>
                                            <td style="padding: 10px; font-family: monospace; color: #64748b;">
                                                <?php echo htmlspecialchars($ub['invoice_no'] ?: ('#INV-' . $ub['id'])); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: right; font-weight: 800; color: #dc2626;">
                                                ₹ <?php echo number_format($ub['amount'], 2); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: right; font-weight: 700; color: #ea580c;">
                                                ₹ <?php echo number_format($ub['fine_amount'] ?? 0, 2); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <a href="view_bill.php?id=<?php echo $ub['id']; ?>" target="_blank" style="color: #2563eb; font-weight: 700; text-decoration: none;">
                                                    <i class="fas fa-external-link-alt"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. Recent Payment Receipts Card -->
                <?php if ($payments_res && $payments_res->num_rows > 0): ?>
                    <div class="dossier-card">
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0 0 14px; color: #0f172a;">
                            <i class="fas fa-history" style="color: #10b981;"></i> Recent Payment History
                        </h3>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                            <tbody>
                                <?php while ($p = $payments_res->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 8px 0; color: #64748b;">
                                            <?php echo date('d M Y', strtotime($p['payment_date'])); ?>
                                        </td>
                                        <td style="padding: 8px 10px; font-weight: 800; color: #059669;">
                                            ₹ <?php echo number_format($p['amount'] ?? 0, 2); ?>
                                        </td>
                                        <td style="padding: 8px 10px; color: #64748b;">
                                            <?php echo htmlspecialchars($p['payment_method'] ?? 'Cash'); ?>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right;">
                                            <a href="receipt.php?id=<?php echo $p['id']; ?>" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 700;">
                                                <i class="fas fa-receipt"></i> Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT COLUMN: INTERACTIVE CALLING PANEL & REMARK LOG -->
            <div>
                <!-- Call Remark Logging Box -->
                <div class="dossier-card" style="border: 2px solid #ccfbf1; box-shadow: 0 8px 25px rgba(13, 148, 136, 0.08);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <h2 style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-phone-volume" style="color: #0d9488;"></i> Log Call &amp; Follow-up
                        </h2>
                        <span style="font-size: 0.75rem; background: #f0fdfa; color: #0d9488; font-weight: 800; padding: 4px 10px; border-radius: 50px; border: 1px solid #99f6e4;">
                            Live Calling Console
                        </span>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="phone_dialed" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">

                        <!-- Call Outcome Selector -->
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 0.78rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">
                                Call Outcome / Status <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="call_outcome" id="callOutcome" required style="width: 100%; padding: 12px 14px; border-radius: 12px; border: 2px solid #cbd5e1; font-weight: 700; font-size: 0.92rem; background: #ffffff;">
                                <option value="PTP (Promise to Pay)">🟢 PTP (Promise to Pay by Date)</option>
                                <option value="Call Answered - Positive">✅ Call Answered - Positive Follow-up</option>
                                <option value="Requested Extension / Time">⏳ Requested Extension / Salary Awaited</option>
                                <option value="Father Visiting Office">🏢 Father Visiting School Office in Person</option>
                                <option value="Fee Dispute / Fine Waiver">⚠️ Fee Dispute / Requested Fine Waiver</option>
                                <option value="Callback Requested">📞 Callback Requested</option>
                                <option value="Ringing / No Answer">📵 Ringing / No Answer</option>
                                <option value="Switched Off / Not Reachable">📴 Switched Off / Not Reachable</option>
                                <option value="Wrong Number">❌ Wrong / Disconnected Number</option>
                            </select>
                        </div>

                        <!-- Remark Suggestions Chips -->
                        <div style="margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <label style="font-size: 0.78rem; font-weight: 800; color: #475569; text-transform: uppercase;">
                                    💡 Quick Remark Suggestions (Click to insert)
                                </label>
                                <small style="color: #0d9488; font-weight: 700; cursor: pointer;" onclick="clearRemark()">Clear</small>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; margin: -3px;">
                                <span class="chip-suggestion" onclick="addSuggestion('Parent promised to pay dues by this weekend.')">💰 Pay by Weekend</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Requested 3-4 days extension; salary credit awaited.')">⏳ Salary Awaited</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Father said he will visit the school office tomorrow in person.')">🏢 Visit Tomorrow</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Sent QR payment link on WhatsApp; parent will pay via UPI today.')">📲 Sent WhatsApp UPI</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Requested discount or waiver on the accumulated late fine.')">🏷️ Waiver Request</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Number was ringing continuously but not answered; retry evening.')">📵 No Answer Retry</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Mobile number was switched off / out of coverage.')">📴 Switched Off</span>
                                <span class="chip-suggestion" onclick="addSuggestion('Parent states fee was already transferred; verification pending.')">🏦 Verification Pending</span>
                            </div>
                        </div>

                        <!-- Detailed Remark Textarea -->
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 0.78rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">
                                Caller Detailed Remarks &amp; Notes <span style="color:#ef4444;">*</span>
                            </label>
                            <textarea name="remark" id="remarkTextarea" rows="4" required placeholder="Type notes discussed with parent during call..." style="width: 100%; padding: 12px 14px; border-radius: 12px; border: 2px solid #cbd5e1; font-family: inherit; font-size: 0.92rem; font-weight: 600; box-sizing: border-box;"></textarea>
                        </div>

                        <!-- Follow-up Date & Time -->
                        <div style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 18px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <label style="font-size: 0.78rem; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                                    <i class="fas fa-calendar-check" style="color: #0d9488;"></i> Next Follow-up Schedule
                                </label>
                                <div style="display: flex; gap: 4px;">
                                    <button type="button" class="quick-date-btn" onclick="setQuickDate(1)">Tomorrow</button>
                                    <button type="button" class="quick-date-btn" onclick="setQuickDate(2)">In 2 Days</button>
                                    <button type="button" class="quick-date-btn" onclick="setQuickDate(7)">In 1 Week</button>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 10px;">
                                <div>
                                    <small style="display: block; color: #64748b; font-size: 0.72rem; font-weight: 700; margin-bottom: 3px;">FOLLOW-UP DATE</small>
                                    <input type="date" name="followup_date" id="followupDate" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 2px solid #cbd5e1; font-weight: 700; box-sizing: border-box;">
                                </div>
                                <div>
                                    <small style="display: block; color: #64748b; font-size: 0.72rem; font-weight: 700; margin-bottom: 3px;">TIME WINDOW</small>
                                    <select name="followup_time" style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 2px solid #cbd5e1; font-weight: 700; box-sizing: border-box; background: #fff;">
                                        <option value="Morning (10:00 AM - 12:00 PM)">Morning (10 AM - 12 PM)</option>
                                        <option value="Afternoon (02:00 PM - 04:00 PM)">Afternoon (2 PM - 4 PM)</option>
                                        <option value="Evening (05:00 PM - 07:00 PM)" selected>Evening (5 PM - 7 PM)</option>
                                        <option value="Anytime">Anytime during day</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <button type="submit" name="save_call_log" class="btn-portal" style="width: 100%; background: linear-gradient(135deg, #0d9488, #0f766e); color: #ffffff; padding: 14px; border-radius: 14px; font-weight: 900; font-size: 1rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 6px 20px rgba(13, 148, 136, 0.25);">
                            <i class="fas fa-save"></i> Save Call Log &amp; Set Follow-up
                        </button>
                    </form>
                </div>

                <!-- Call History Timeline -->
                <div class="dossier-card">
                    <h3 style="font-size: 1.15rem; font-weight: 900; margin: 0 0 16px; color: #0f172a; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-history" style="color: #0d9488;"></i> Call History &amp; Remarks Timeline</span>
                        <span style="font-size: 0.78rem; background: #f1f5f9; padding: 3px 10px; border-radius: 50px; font-weight: 800; color: #475569;">
                            <?php echo $call_logs ? $call_logs->num_rows : 0; ?> Logged
                        </span>
                    </h3>

                    <?php if (!$call_logs || $call_logs->num_rows == 0): ?>
                        <div style="background: #f8fafc; padding: 22px; border-radius: 14px; text-align: center; color: #94a3b8; font-weight: 700;">
                            <i class="fas fa-phone-slash" style="font-size: 1.8rem; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                            No call records logged for this student yet. Use the form above to record your first interaction!
                        </div>
                    <?php else: ?>
                        <div style="padding-top: 6px;">
                            <?php while ($log = $call_logs->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 4px;">
                                        <div style="font-weight: 800; color: #0f172a; font-size: 0.90rem;">
                                            <i class="fas fa-user-tie" style="color: #0d9488; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($log['caller_name'] ?: 'Counselor'); ?>
                                        </div>
                                        <small style="color: #94a3b8; font-weight: 700; font-size: 0.74rem;">
                                            <?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?>
                                        </small>
                                    </div>

                                    <div style="margin-bottom: 6px;">
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 50px; font-weight: 800; font-size: 0.74rem;">
                                            <?php echo htmlspecialchars($log['call_outcome']); ?>
                                        </span>
                                        <?php if (!empty($log['followup_date'])): ?>
                                            <span style="background: #fef3c7; color: #b45309; padding: 3px 10px; border-radius: 50px; font-weight: 800; font-size: 0.74rem; margin-left: 4px;">
                                                <i class="fas fa-calendar-check"></i> Next: <?php echo date('d M Y', strtotime($log['followup_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p style="margin: 0; color: #334155; font-size: 0.88rem; line-height: 1.5; font-weight: 600;">
                                        <?php echo nl2br(htmlspecialchars($log['remark'])); ?>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        function addSuggestion(text) {
            const textarea = document.getElementById('remarkTextarea');
            if (textarea.value.trim() === '') {
                textarea.value = text;
            } else {
                textarea.value += ' ' + text;
            }
            textarea.focus();
        }

        function clearRemark() {
            document.getElementById('remarkTextarea').value = '';
        }

        function setQuickDate(daysToAdd) {
            const d = new Date();
            d.setDate(d.getDate() + daysToAdd);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            document.getElementById('followupDate').value = `${yyyy}-${mm}-${dd}`;
        }
    </script>
</body>
</html>
