<?php
// admin/fees.php - Fee Management & Billing Ledger

require_once 'includes/auth.php';

// Automatically run monthly fee billing engine for due students on page load (without blocking on emails)
$skip_email = true;
require_once 'includes/billing_engine.php';

$msg = '';
$err = '';

// Handle Payment Receipt
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_payment'])) {
    $sid = $_POST['student_id'];
    $amount = $_POST['amount'];
    $date = $_POST['payment_date'];
    $month = $_POST['month_for'];
    $method = $_POST['payment_method'];

    $stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $sid, $amount, $date, $month, $method);
    
    if ($stmt->execute()) {
        $pay_id = $conn->insert_id;
        $msg = "Payment recorded successfully.";

        // Sync with generated bills: handle partial or full payment
        $bill_q = $conn->prepare("SELECT id, amount, remark FROM fees_generated WHERE student_id = ? AND status = 'unpaid' LIMIT 1");
        $bill_q->bind_param("i", $sid);
        $bill_q->execute();
        $bill_res = $bill_q->get_result();
        
        if ($bill_res && $bill_res->num_rows > 0) {
            $bill = $bill_res->fetch_assoc();
            $new_amount = round($bill['amount'] - $amount, 2);
            
            if ($new_amount <= 0) {
                // Fully paid
                $conn->query("UPDATE fees_generated SET status = 'paid' WHERE id = " . $bill['id']);
            } else {
                // Partially paid
                $new_remark = $bill['remark'] . " | Payment received on $date (-₹" . number_format($amount, 2) . ")";
                $update_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, remark = ? WHERE id = ?");
                $update_stmt->bind_param("dsi", $new_amount, $new_remark, $bill['id']);
                $update_stmt->execute();
            }
        }

        // Fetch parent email if linked for billing receipt
        $student_stmt = $conn->prepare("
            SELECT s.name AS student_name, p.parent_name, p.email AS parent_email 
            FROM students s 
            LEFT JOIN parents p ON s.parent_id = p.id 
            WHERE s.id = ?
        ");
        $student_stmt->bind_param("i", $sid);
        $student_stmt->execute();
        $student_res = $student_stmt->get_result()->fetch_assoc();
        
        // Log Fee Payment Recorded
        $st_name = $student_res['student_name'] ?? 'ID ' . $sid;
        log_activity('fee_payment_recorded', "Recorded payment of ₹" . number_format($amount, 2) . " for student $st_name (month: $month)");

        if ($student_res && !empty($student_res['parent_email'])) {
            require_once __DIR__ . '/../includes/mail_helper.php';
            
            // Dynamic host url builder
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
            $base_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
            
            $receipt_url = "$base_url/parent/receipt.php?id=" . $pay_id;
            $email_html = get_fee_paid_template(
                $student_res['student_name'], 
                $amount, 
                $month, 
                $date, 
                $receipt_url
            );
            
            send_smtp_email(
                $student_res['parent_email'], 
                "Fee Payment Receipt - " . $student_res['student_name'] . " - ABSS", 
                $email_html
            );
        }
    } else {
        $err = "Error recording payment.";
    }
}

// Handle Manual Fee Generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_manual_fee'])) {
    $sid = (int)$_POST['student_id'];
    $amount = (float)$_POST['amount'];
    $month = trim($_POST['month_for']);
    $year = (int)date('Y'); // Dynamic current year
    $remark = trim($_POST['remark']);
    $fee_type = trim($_POST['fee_type']); // e.g. 'Day Scholar', 'Hostler', or 'Custom'
    $send_email = isset($_POST['send_email']) ? true : false;

    $month_for_full = "$month $year";
    $billing_date = date('Y-m-d');

    // 1. Fetch student info
    $stmt = $conn->prepare("SELECT name, parent_id, last_billed_date FROM students WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $student_info = $stmt->get_result()->fetch_assoc();

    if ($student_info) {
        $st_name = $student_info['name'];
        $parent_id = $student_info['parent_id'];

        $conn->begin_transaction();
        try {
            // Check for existing unpaid bill
            $existing_q = $conn->query("SELECT id, amount, remark, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
            $existing = $existing_q->fetch_assoc();

            if ($existing) {
                // Update existing unpaid invoice
                $new_amount = $existing['amount'] + $amount;
                $new_remark = $existing['remark'] . (empty($remark) ? "" : " | " . $remark . " [" . $month_for_full . "]: ₹" . number_format($amount, 2));
                $new_month = $existing['month_for'];
                if (strpos($new_month, $month_for_full) === false) {
                    $new_month .= ", " . $month_for_full;
                }

                $update_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, remark = ?, month_for = ? WHERE id = ?");
                $update_stmt->bind_param("dssi", $new_amount, $new_remark, $new_month, $existing['id']);
                $update_stmt->execute();
                $invoice_id = $existing['id'];
                $msg = "Successfully added ₹" . number_format($amount, 2) . " to $st_name's existing unpaid invoice.";
            } else {
                // Create a new unpaid invoice
                $final_remark = "Manual Bill. " . $remark . " [" . $month_for_full . "]: ₹" . number_format($amount, 2);
                $insert_stmt = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
                $insert_stmt->bind_param("idsss", $sid, $amount, $month_for_full, $billing_date, $final_remark);
                $insert_stmt->execute();
                $invoice_id = $conn->insert_id;
                $msg = "Successfully generated new manual invoice of ₹" . number_format($amount, 2) . " for $st_name.";
            }

            // If it's a standard monthly fee (any type other than Custom), update last_billed_date to prevent double auto-billing
            if ($fee_type !== 'Custom') {
                // Calculate the end of the selected month/year
                $date_str = "01-$month-$year";
                $dt = DateTime::createFromFormat('d-F-Y', $date_str);
                if ($dt) {
                    $dt->modify('last day of this month');
                    $new_last_billed = $dt->format('Y-m-d');
                    
                    // Only update if it extends their current last_billed_date
                    $should_update_date = true;
                    if (!empty($student_info['last_billed_date'])) {
                        $curr_last_billed = new DateTime($student_info['last_billed_date']);
                        if ($dt <= $curr_last_billed) {
                            $should_update_date = false;
                        }
                    }
                    if ($should_update_date) {
                        $conn->query("UPDATE students SET last_billed_date = '$new_last_billed' WHERE id = $sid");
                    }
                }
            }

            $conn->commit();
            log_activity('fee_bill_generated', "Manually generated fee of ₹" . number_format($amount, 2) . " for student $st_name ($month_for_full)");

            // Send Email Notification if requested
            if ($send_email && !empty($parent_id)) {
                $parent_res = $conn->query("SELECT email, parent_name FROM parents WHERE id = " . (int)$parent_id);
                if ($parent_res && $parent_res->num_rows > 0) {
                    $parent = $parent_res->fetch_assoc();
                    if (!empty($parent['email'])) {
                        require_once __DIR__ . '/../includes/mail_helper.php';
                        
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $fe_host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
                        $fe_base_url = (strpos($fe_host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$fe_host";
                        $portal_url = "$fe_base_url/admin/login.php?role=parent";

                        $email_html = get_fee_generated_template(
                            $st_name, 
                            $amount, 
                            $month_for_full, 
                            $billing_date, 
                            "Manual Fee: $remark", 
                            $portal_url
                        );
                        send_smtp_email(
                            $parent['email'], 
                            "Fee Invoice Generated - " . $st_name . " - ABSS", 
                            $email_html
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Error generating manual invoice: " . $e->getMessage();
        }
    } else {
        $err = "Student not found or inactive.";
    }
}




// Handle Quick Offline Collect Action (Collect Particular Due Amount)
if (isset($_GET['collect_offline'])) {
    $bill_id = (int)$_GET['collect_offline'];
    
    // Fetch outstanding bill details
    $bill_stmt = $conn->prepare("SELECT student_id, amount, month_for FROM fees_generated WHERE id = ? AND status = 'unpaid'");
    $bill_stmt->bind_param("i", $bill_id);
    $bill_stmt->execute();
    $bill = $bill_stmt->get_result()->fetch_assoc();
    
    if ($bill) {
        $sid = $bill['student_id'];
        $amount = $bill['amount'];
        $month = $bill['month_for'];
        $payment_date = date('Y-m-d');
        $method = 'Cash'; // Default offline cash collection
        
        // 1. Record payment in fee_payments
        $pay_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
        $pay_stmt->bind_param("idsss", $sid, $amount, $payment_date, $month, $method);
        
        if ($pay_stmt->execute()) {
            $pay_id = $conn->insert_id;
            
            // 2. Mark this particular bill as paid
            $update_bill = $conn->prepare("UPDATE fees_generated SET status = 'paid' WHERE id = ?");
            $update_bill->bind_param("i", $bill_id);
            $update_bill->execute();
            
            $msg = "Offline payment recorded successfully via Quick Collect.";
            
            // 3. Fetch student/parent details for email & activity logging
            $student_stmt = $conn->prepare("
                SELECT s.name AS student_name, p.parent_name, p.email AS parent_email 
                FROM students s 
                LEFT JOIN parents p ON s.parent_id = p.id 
                WHERE s.id = ?
            ");
            $student_stmt->bind_param("i", $sid);
            $student_stmt->execute();
            $student_res = $student_stmt->get_result()->fetch_assoc();
            
            $st_name = $student_res['student_name'] ?? 'ID ' . $sid;
            
            // Audit action log
            log_activity('fee_payment_recorded', "Recorded payment of ₹" . number_format($amount, 2) . " for student $st_name via Quick Collect (month: $month)");
            
            // 4. Dispatch Email Billing Receipt to Parent
            if ($student_res && !empty($student_res['parent_email'])) {
                require_once __DIR__ . '/../includes/mail_helper.php';
                // Dynamic host URL builder - works on localhost and live server
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $qc_host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
                $qc_base_url = (strpos($qc_host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$qc_host";
                $receipt_url = "$qc_base_url/parent/receipt.php?id=" . $pay_id;
                $email_html = get_fee_paid_template(
                    $student_res['student_name'], 
                    $amount, 
                    $month, 
                    $payment_date, 
                    $receipt_url
                );
                
                send_smtp_email(
                    $student_res['parent_email'], 
                    "Fee Payment Receipt - " . $student_res['student_name'] . " - ABSS", 
                    $email_html
                );
            }
        } else {
            $err = "Error recording quick offline payment.";
        }
    } else {
        $err = "Invalid bill ID or invoice has already been settled.";
    }
}

// Fetch active students into reusable array
$students_res = $conn->query("
    SELECT s.id, s.name, s.base_fee, s.monthly_discount, s.scholar_mode, COALESCE(SUM(fg.amount), 0) AS total_due
    FROM students s
    LEFT JOIN fees_generated fg ON s.id = fg.student_id AND fg.status = 'unpaid'
    WHERE s.status = 'active'
    GROUP BY s.id, s.name, s.base_fee, s.monthly_discount, s.scholar_mode
    ORDER BY s.name ASC
");
$students_list = [];
while($s = $students_res->fetch_assoc()) {
    $students_list[] = $s;
}

// Fetch payments log
$payments = $conn->query("
    SELECT f.*, s.name 
    FROM fee_payments f 
    JOIN students s ON f.student_id = s.id 
    ORDER BY f.payment_date DESC LIMIT 10
");

// Fetch bills log
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'unpaid';
$status_cond = "";
if ($filter === 'unpaid') $status_cond = " WHERE fg.status = 'unpaid'";
if ($filter === 'paid') $status_cond = " WHERE fg.status = 'paid'";

$bills = $conn->query("
    SELECT fg.*, s.name 
    FROM fees_generated fg 
    JOIN students s ON fg.student_id = s.id 
    $status_cond
    ORDER BY fg.billing_date DESC LIMIT 50
");

// Check how many students are due for billing
$due_query = $conn->query("
    SELECT COUNT(id) as due_count
    FROM students
    WHERE status = 'active'
    AND DATE_ADD(COALESCE(last_billed_date, admission_date), INTERVAL 1 MONTH) <= CURDATE()
");
$due_count = $due_query ? $due_query->fetch_assoc()['due_count'] : 0;

// Retrieve tuition modes from settings database table
$tuition_modes = [];
if (!empty($settings['tuition_modes'])) {
    $tuition_modes = json_decode($settings['tuition_modes'], true);
} else {
    $fee_day_scholar = $settings['fee_day_scholar'] ?? '3000';
    $fee_hostler = $settings['fee_hostler'] ?? '5000';
    $tuition_modes = ['Day Scholar' => $fee_day_scholar, 'Hostler' => $fee_hostler];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Ledger | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .form-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .list-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; }
        td { padding: 20px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; font-weight: 600; color: #5c6bc0; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .amount-tag { background: #f0fdf4; color: #166534; padding: 8px 15px; border-radius: 10px; font-weight: 800; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-paid { background: #e8f5e9; color: #2e7d32; }
        .status-unpaid { background: #feeef2; color: #d32f2f; }
        
        .btn-quick-collect {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.3s;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }
        .btn-quick-collect:hover {
            background: #2e7d32;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.15);
        }
        
        @media (max-width: 900px) {
            .form-cols, .list-cols { grid-template-columns: 1fr !important; gap: 30px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Fee Management</h1>
            <p>Generate fee invoices (tuition dues) and record cash/online payments.</p>
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

        <!-- Form Section -->
        <div class="form-cols">

            <!-- Form 2: Collect Fee -->
            <div class="portal-card">
                <h3 style="margin-bottom: 25px; color:var(--portal-blue);"><i class="fas fa-hand-holding-usd" style="margin-right:8px; opacity:0.7;"></i> Collect Fee Payment</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label style="display:flex; justify-content:space-between;">
                            <span>Student Name</span>
                            <span id="display_total_due" style="font-weight:800; display:none;"></span>
                        </label>
                        <select name="student_id" id="collect_student_id" required>
                            <option value="" data-due="0">Select Student...</option>
                            <?php foreach($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>" data-due="<?php echo $student['total_due']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="portal-input-group">
                            <label>Amount Paid (₹)</label>
                            <input type="number" name="amount" placeholder="e.g. 3000" required>
                        </div>
                        <div class="portal-input-group">
                            <label>For Month</label>
                            <select name="month_for" required>
                                <?php 
                                $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                                foreach($months as $m) {
                                    $sel = (date('F') == $m) ? 'selected' : '';
                                    echo "<option value='$m' $sel>$m</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Method</label>
                        <select name="payment_method">
                            <option>Cash</option>
                            <option>PhonePe / GPay</option>
                            <option>Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" name="record_payment" class="btn-portal w-100">Submit Payment</button>
                </form>
            </div>

            <!-- Form 1: Generate Manual Fee / Invoice -->
            <div class="portal-card">
                <h3 style="margin-bottom: 25px; color:var(--portal-blue);"><i class="fas fa-file-invoice-dollar" style="margin-right:8px; opacity:0.7;"></i> Generate Custom/Manual Fee</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Student Name</label>
                        <select name="student_id" id="manual_student_id" required>
                            <option value="" data-base-fee="0" data-monthly-discount="0" data-scholar-mode="">Select Student...</option>
                            <?php foreach($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>" data-base-fee="<?php echo $student['base_fee']; ?>" data-monthly-discount="<?php echo $student['monthly_discount']; ?>" data-scholar-mode="<?php echo htmlspecialchars($student['scholar_mode'] ?? 'Day Scholar'); ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="portal-input-group">
                            <label>Fee Type</label>
                            <select name="fee_type" id="manual_fee_type" required>
                                <option value="Custom">Custom / Extra Charge</option>
                                <?php foreach(array_keys($tuition_modes) as $mode): ?>
                                    <option value="<?php echo htmlspecialchars($mode); ?>"><?php echo htmlspecialchars($mode); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Amount (₹)</label>
                            <input type="number" name="amount" id="manual_amount" placeholder="e.g. 3000" step="0.01" required>
                            <small id="manual_fee_discount_hint" style="color: #2e7d32; font-weight: 700; display: block; margin-top: 5px;"></small>
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Billing Month</label>
                        <select name="month_for" required>
                            <?php 
                            foreach($months as $m) {
                                $sel = (date('F') == $m) ? 'selected' : '';
                                echo "<option value='$m' $sel>$m</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Remarks / Description</label>
                        <input type="text" name="remark" placeholder="e.g. Monthly Tuition, Uniform Fee, Exam Fee" required>
                    </div>
                    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="send_email" id="send_email" style="cursor: pointer; width: 16px; height: 16px;" checked>
                        <label for="send_email" style="cursor: pointer; margin: 0; font-size: 0.9rem; color: #5c6bc0; font-weight: 600;">Send Email Notification to Parent</label>
                    </div>
                    <button type="submit" name="generate_manual_fee" class="btn-portal w-100">Generate Fee</button>
                </form>
            </div>
        </div>

        <!-- Ledger Logs Section -->
        <div class="list-cols">
            <!-- List 1: Billed Invoices -->
            <div class="list-section">
                <h3 style="margin-bottom: 20px; padding-left: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fas fa-file-invoice" style="margin-right:8px; color:var(--portal-blue);"></i> Generated Bills</span>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <form method="GET" action="fees.php" style="display:flex; align-items:center; gap:5px;">
                            <select name="filter" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                <option value="all" <?php echo ($filter=='all')?'selected':''; ?>>All Invoices</option>
                                <option value="unpaid" <?php echo ($filter=='unpaid')?'selected':''; ?>>Unpaid Only</option>
                                <option value="paid" <?php echo ($filter=='paid')?'selected':''; ?>>Paid Only</option>
                            </select>
                        </form>
                        <button type="button" class="btn-portal" style="padding: 5px 15px; font-size: 0.8rem; width: auto;" onclick="printSelectedInvoices()">
                            <i class="fas fa-file-archive"></i> Bulk Download (ZIP)
                        </button>
                    </div>
                </h3>
                <div class="portal-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAllInvoices" onclick="toggleAllInvoices(this)" style="cursor:pointer; width:16px; height:16px;"></th>
                                <th>Student</th>
                                <th>Amount / Month</th>
                                <th>Remarks / Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bills->num_rows == 0): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #9aa5ce;">No bills generated yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($b = $bills->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="invoice-checkbox" value="<?php echo $b['id']; ?>" style="cursor:pointer; width:16px; height:16px;">
                                        </td>
                                        <td style="color:var(--portal-blue); font-weight:800;">
                                            <?php echo htmlspecialchars($b['name']); ?><br>
                                            <small style="color:#9aa5ce; font-weight:600;"><?php echo date('d M, Y', strtotime($b['billing_date'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-weight:800; color:#333;">₹ <?php echo number_format($b['amount']); ?></div>
                                            <small style="color:#5c6bc0; font-weight:700;"><?php echo htmlspecialchars($b['month_for']); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size:0.8rem; margin-bottom:8px; line-height:1.3; color:#5c6bc0;"><?php echo htmlspecialchars($b['remark']); ?></div>
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <span class="status-badge status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span>
                                                <a href="view_bill.php?id=<?php echo $b['id']; ?>" class="btn-quick-collect" style="background:#eef2ff; color:#3949ab; border-color:rgba(57,73,171,0.1);" title="View Invoice">
                                                    <i class="fas fa-eye"></i> View Invoice
                                                </a>
                                                <?php if ($b['status'] === 'unpaid'): ?>
                                                    <a href="?collect_offline=<?php echo $b['id']; ?>" class="btn-quick-collect" onclick="return confirm('Record offline cash collection of ₹<?php echo number_format($b['amount']); ?> for <?php echo htmlspecialchars($b['name']); ?>?')" title="Mark as Paid (Offline Cash)">
                                                        <i class="fas fa-hand-holding-usd"></i> Collect Offline
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- List 2: Recent Collections -->
            <div class="list-section">
                <h3 style="margin-bottom: 20px; padding-left: 10px;"><i class="fas fa-history" style="margin-right:8px; color:var(--portal-blue);"></i> Recent Payments</h3>
                <div class="portal-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Date / Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #9aa5ce;">No payments recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($p = $payments->fetch_assoc()): ?>
                                    <tr class="attendance-row">
                                        <td style="color:var(--portal-blue); font-weight:800;"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo $p['month_for']; ?></td>
                                        <td><span class="amount-tag">₹ <?php echo number_format($p['amount']); ?></span></td>
                                        <td>
                                            <div style="font-weight:700; color:#333; font-size:0.85rem;"><?php echo date('d M, Y', strtotime($p['payment_date'])); ?></div>
                                            <small style="color:#9aa5ce; font-weight:700;"><i class="fas fa-wallet"></i> <?php echo htmlspecialchars($p['payment_method']); ?></small>
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

    <script>
        const collectStudentSelect = document.getElementById('collect_student_id');
        if (collectStudentSelect) {
            collectStudentSelect.addEventListener('change', function() {
                var selected = this.options[this.selectedIndex];
                var due = parseFloat(selected.getAttribute('data-due') || 0);
                var display = document.getElementById('display_total_due');
                
                if(!this.value) {
                    display.style.display = 'none';
                } else {
                    display.style.display = 'inline-block';
                    if (due > 0) {
                        display.textContent = 'Total Dues: ₹' + due.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        display.style.color = '#d32f2f'; // Red for dues
                    } else {
                        display.textContent = 'No Pending Dues';
                        display.style.color = '#2e7d32'; // Green for cleared
                    }
                }
            });
        }

        const manualStudentSelect = document.getElementById('manual_student_id');
        const manualAmountInput = document.getElementById('manual_amount');
        const manualFeeTypeSelect = document.getElementById('manual_fee_type');
        const discountHint = document.getElementById('manual_fee_discount_hint');
        const tuitionModes = <?php echo json_encode($tuition_modes); ?>;

        function updateManualFeeFields() {
            if (!manualStudentSelect) return;
            const selectedStudent = manualStudentSelect.options[manualStudentSelect.selectedIndex];
            if (!selectedStudent || !selectedStudent.value) {
                manualAmountInput.value = '';
                if (discountHint) discountHint.textContent = '';
                return;
            }

            const baseFee = parseFloat(selectedStudent.getAttribute('data-base-fee') || 0);
            const discount = parseFloat(selectedStudent.getAttribute('data-monthly-discount') || 0);
            const scholarMode = selectedStudent.getAttribute('data-scholar-mode');

            // Auto-select fee type matching student's scholar mode
            if (scholarMode && manualFeeTypeSelect) {
                let matched = false;
                for (let i = 0; i < manualFeeTypeSelect.options.length; i++) {
                    if (manualFeeTypeSelect.options[i].value.toLowerCase() === scholarMode.toLowerCase()) {
                        manualFeeTypeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
                if (!matched) {
                    manualFeeTypeSelect.value = 'Custom';
                }
            }

            const isCustom = manualFeeTypeSelect && manualFeeTypeSelect.value === 'Custom';

            if (isCustom) {
                if (discountHint) discountHint.textContent = '';
            } else {
                const finalAmount = Math.max(0, baseFee - discount);
                manualAmountInput.value = baseFee > 0 ? finalAmount : '';
                if (discountHint) {
                    if (discount > 0) {
                        discountHint.innerHTML = `<i class="fas fa-percentage"></i> Discount applied: -₹${discount.toFixed(2)} (Base: ₹${baseFee.toFixed(2)})`;
                        discountHint.style.color = '#2e7d32';
                    } else {
                        discountHint.innerHTML = `Base Fee: ₹${baseFee.toFixed(2)} (No discount)`;
                        discountHint.style.color = '#5c6bc0';
                    }
                }
            }
        }

        if (manualStudentSelect) {
            manualStudentSelect.addEventListener('change', updateManualFeeFields);
        }

        if (manualFeeTypeSelect) {
            manualFeeTypeSelect.addEventListener('change', function() {
                const selectedMode = this.value;
                const selectedStudent = manualStudentSelect.options[manualStudentSelect.selectedIndex];
                
                if (selectedMode === 'Custom') {
                    if (discountHint) discountHint.textContent = '';
                } else {
                    const studentScholarMode = selectedStudent ? selectedStudent.getAttribute('data-scholar-mode') : null;
                    const baseFee = selectedStudent ? parseFloat(selectedStudent.getAttribute('data-base-fee') || 0) : 0;
                    const discount = selectedStudent ? parseFloat(selectedStudent.getAttribute('data-monthly-discount') || 0) : 0;

                    if (studentScholarMode && selectedMode.toLowerCase() === studentScholarMode.toLowerCase()) {
                        const finalAmount = Math.max(0, baseFee - discount);
                        manualAmountInput.value = baseFee > 0 ? finalAmount : (tuitionModes[selectedMode] || '');
                        if (discountHint) {
                            if (discount > 0) {
                                discountHint.innerHTML = `<i class="fas fa-percentage"></i> Discount applied: -₹${discount.toFixed(2)} (Base: ₹${baseFee.toFixed(2)})`;
                                discountHint.style.color = '#2e7d32';
                            } else {
                                discountHint.innerHTML = `Base Fee: ₹${baseFee.toFixed(2)} (No discount)`;
                                discountHint.style.color = '#5c6bc0';
                            }
                        }
                    } else {
                        const standardFee = parseFloat(tuitionModes[selectedMode] || 0);
                        manualAmountInput.value = standardFee > 0 ? standardFee : '';
                        if (discountHint) {
                            discountHint.innerHTML = `Standard Rate: ₹${standardFee.toFixed(2)}`;
                            discountHint.style.color = '#5c6bc0';
                        }
                    }
                }
            });
        }

        function toggleAllInvoices(source) {
            var checkboxes = document.querySelectorAll('.invoice-checkbox');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        
        function printSelectedInvoices() {
            var checkboxes = document.querySelectorAll('.invoice-checkbox:checked');
            var ids = [];
            for(var i=0; i<checkboxes.length; i++) {
                ids.push(checkboxes[i].value);
            }
            if(ids.length === 0) {
                alert("Please select at least one invoice to print.");
                return;
            }
            window.open('bulk_print.php?ids=' + ids.join(','), '_blank');
        }
    </script>
</body>
</html>
