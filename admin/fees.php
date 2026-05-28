<?php
// admin/fees.php - Fee Management & Billing Ledger

require_once 'includes/auth.php';

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

        // Sync with generated bills: mark corresponding unpaid bill as paid
        $update_bill = $conn->prepare("UPDATE fees_generated SET status = 'paid' WHERE student_id = ? AND month_for = ? AND status = 'unpaid' LIMIT 1");
        $update_bill->bind_param("is", $sid, $month);
        $update_bill->execute();

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

// Handle Fee Generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_fee'])) {
    $sid = $_POST['student_id'];
    $amount = $_POST['amount'];
    $date = $_POST['billing_date'];
    $month = $_POST['month_for'];
    $remark = trim($_POST['remark']);

    $stmt = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
    $stmt->bind_param("idsss", $sid, $amount, $month, $date, $remark);
    
    if ($stmt->execute()) {
        $msg = "Fee invoice successfully generated and billed.";
        
        // Fetch student details and parent email for notification
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
        
        log_activity('fee_bill_generated', "Generated invoice of ₹" . number_format($amount, 2) . " for student $st_name (month: $month)");

        if ($student_res && !empty($student_res['parent_email'])) {
            require_once __DIR__ . '/../includes/mail_helper.php';
            // Dynamic host URL builder - works on localhost and live server
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $fe_host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
            $fe_base_url = (strpos($fe_host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$fe_host";
            $portal_url = "$fe_base_url/admin/login.php?role=parent";
            $email_html = get_fee_generated_template(
                $student_res['student_name'], 
                $amount, 
                $month, 
                $date, 
                $remark, 
                $portal_url
            );
            
            send_smtp_email(
                $student_res['parent_email'], 
                "New Tuition Invoice Generated - " . $student_res['student_name'] . " - ABSS", 
                $email_html
            );
        }
    } else {
        $err = "Error generating student fee invoice.";
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
$students_res = $conn->query("SELECT id, name FROM students WHERE status = 'active' ORDER BY name ASC");
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
$bills = $conn->query("
    SELECT fg.*, s.name 
    FROM fees_generated fg 
    JOIN students s ON fg.student_id = s.id 
    ORDER BY fg.billing_date DESC LIMIT 10
");
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
            <!-- Form 1: Generate/Bill Fee -->
            <div class="portal-card">
                <h3 style="margin-bottom: 25px; color:var(--portal-blue);"><i class="fas fa-file-invoice-dollar" style="margin-right:8px; opacity:0.7;"></i> Generate Fee Bill</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Student</label>
                        <select name="student_id" required>
                            <option value="">Select Student...</option>
                            <?php foreach($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="portal-input-group">
                            <label>Amount (₹)</label>
                            <input type="number" name="amount" placeholder="e.g. 3000" required>
                        </div>
                        <div class="portal-input-group">
                            <label>For Month</label>
                            <select name="month_for" required>
                                <?php 
                                $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                                foreach($months as $m) echo "<option value='$m'>$m</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Billing Date</label>
                        <input type="date" name="billing_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Remarks / Description</label>
                        <input type="text" name="remark" placeholder="e.g. Monthly Tuition Fee + Mess Charge" required>
                    </div>
                    <button type="submit" name="generate_fee" class="btn-portal w-100">Bill Fee Outstanding</button>
                </form>
            </div>

            <!-- Form 2: Collect Fee -->
            <div class="portal-card">
                <h3 style="margin-bottom: 25px; color:var(--portal-blue);"><i class="fas fa-hand-holding-usd" style="margin-right:8px; opacity:0.7;"></i> Collect Fee Payment</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Student Name</label>
                        <select name="student_id" required>
                            <option value="">Select Student...</option>
                            <?php foreach($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
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
                                foreach($months as $m) echo "<option value='$m'>$m</option>";
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
        </div>

        <!-- Ledger Logs Section -->
        <div class="list-cols">
            <!-- List 1: Billed Invoices -->
            <div class="list-section">
                <h3 style="margin-bottom: 20px; padding-left: 10px;"><i class="fas fa-file-invoice" style="margin-right:8px; color:var(--portal-blue);"></i> Generated Bills</h3>
                <div class="portal-table-container">
                    <table>
                        <thead>
                            <tr>
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
</body>
</html>
