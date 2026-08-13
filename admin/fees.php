<?php
// admin/fees.php - Fee Management & Billing Ledger
require_once 'includes/auth.php';

// Automatically run monthly fee billing engine for due students on page load (without blocking on emails)
$skip_email = true;
require_once 'includes/billing_engine.php';

$msg = '';
$err = '';

// Handle Bulk Delete Generated Bills POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_delete_bills'])) {
    if (!empty($_POST['selected_bill_ids']) && is_array($_POST['selected_bill_ids'])) {
        $ids = array_map('intval', $_POST['selected_bill_ids']);
        $ids_str = implode(',', $ids);
        if (!empty($ids_str)) {
            $conn->query("DELETE FROM fees_generated WHERE id IN ($ids_str)");
            $affected = $conn->affected_rows;
            $msg = "Successfully deleted <strong>$affected</strong> selected invoices.";
            if (function_exists('log_activity')) {
                log_activity('bulk_bills_deleted', "Bulk deleted $affected invoices ($ids_str)");
            }
        }
    } else {
        $err = "No invoices selected for deletion.";
    }
}

// Handle Edit Generated Bill POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_bill'])) {
    $bill_id = (int)$_POST['bill_id'];
    $amount = (float)$_POST['amount'];
    $month_for = trim($_POST['month_for']);
    $remark = trim($_POST['remark']);
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, month_for = ?, remark = ?, status = ? WHERE id = ?");
    $stmt->bind_param("dsssi", $amount, $month_for, $remark, $status, $bill_id);
    if ($stmt->execute()) {
        $msg = "Invoice #$bill_id updated successfully.";
        if (function_exists('log_activity')) {
            log_activity('bill_edited', "Edited invoice #$bill_id: amount ₹$amount, month: $month_for");
        }
    } else {
        $err = "Failed to update invoice #$bill_id.";
    }
}

// Handle Single Delete Generated Bill POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_bill'])) {
    $bill_id = (int)$_POST['bill_id'];
    $stmt = $conn->prepare("DELETE FROM fees_generated WHERE id = ?");
    $stmt->bind_param("i", $bill_id);
    if ($stmt->execute()) {
        $msg = "Invoice #$bill_id deleted successfully.";
        if (function_exists('log_activity')) {
            log_activity('bill_deleted', "Deleted invoice #$bill_id");
        }
    } else {
        $err = "Failed to delete invoice #$bill_id.";
    }
}

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
        if (function_exists('log_activity')) {
            log_activity('fee_payment_recorded', "Recorded payment of ₹" . number_format($amount, 2) . " for student $st_name (month: $month)");
        }

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

            // If it's a standard monthly fee, update last_billed_date
            if ($fee_type !== 'Custom') {
                $date_str = "01-$month-$year";
                $dt = DateTime::createFromFormat('d-F-Y', $date_str);
                if ($dt) {
                    $end_of_month = $dt->format('Y-m-t');
                    $conn->query("UPDATE students SET last_billed_date = '$end_of_month' WHERE id = $sid");
                }
            }

            $conn->commit();

            if (function_exists('log_activity')) {
                log_activity('manual_fee_generated', "Generated manual fee of ₹" . number_format($amount, 2) . " for student $st_name ($month_for_full)");
            }

            // 2. Email notification to parent
            if ($send_email && $parent_id) {
                $parent_stmt = $conn->prepare("SELECT email FROM parents WHERE id = ?");
                $parent_stmt->bind_param("i", $parent_id);
                $parent_stmt->execute();
                $parent_res = $parent_stmt->get_result()->fetch_assoc();

                if ($parent_res && !empty($parent_res['email'])) {
                    require_once __DIR__ . '/../includes/mail_helper.php';

                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
                    $base_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
                    $portal_url = "$base_url/parent/login.php";

                    $email_html = get_fee_generated_template(
                        $st_name,
                        $amount,
                        $month_for_full,
                        $billing_date,
                        $remark,
                        $portal_url
                    );

                    send_smtp_email(
                        $parent_res['email'],
                        "New Tuition Fee Invoice Generated - " . $st_name . " - ABSS",
                        $email_html
                    );
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Database Error: " . $e->getMessage();
        }
    } else {
        $err = "Student not found.";
    }
}

// Handle Quick Collect Offline Action via GET
if (isset($_GET['collect_offline'])) {
    $bill_id = (int)$_GET['collect_offline'];
    $bill_q = $conn->prepare("SELECT fg.*, s.name FROM fees_generated fg JOIN students s ON fg.student_id = s.id WHERE fg.id = ? AND fg.status = 'unpaid'");
    $bill_q->bind_param("i", $bill_id);
    $bill_q->execute();
    $bill = $bill_q->get_result()->fetch_assoc();

    if ($bill) {
        $sid = $bill['student_id'];
        $amount = $bill['amount'];
        $date = date('Y-m-d');
        $month = $bill['month_for'];
        $method = 'Cash (Offline Direct)';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $sid, $amount, $date, $month, $method);
            $stmt->execute();
            $pay_id = $conn->insert_id;

            $conn->query("UPDATE fees_generated SET status = 'paid' WHERE id = $bill_id");
            $conn->commit();

            $msg = "Successfully collected ₹" . number_format($amount, 2) . " cash payment for " . htmlspecialchars($bill['name']) . ". Invoice #$bill_id marked as PAID.";
            if (function_exists('log_activity')) {
                log_activity('fee_payment_recorded', "Quick collected cash ₹" . number_format($amount, 2) . " for student " . $bill['name'] . " (Invoice #$bill_id)");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Failed to process collection: " . $e->getMessage();
        }
    }
}

// Fetch active students with total pending dues calculated
$students_res = $conn->query("
    SELECT s.id, s.name, s.scholar_mode, s.base_fee, s.monthly_discount,
           COALESCE(SUM(fg.amount), 0) AS total_due
    FROM students s
    LEFT JOIN fees_generated fg ON s.id = fg.student_id AND fg.status = 'unpaid'
    WHERE s.status = 'active'
    GROUP BY s.id
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
    ORDER BY fg.billing_date DESC, fg.id DESC LIMIT 100
");

// Retrieve tuition modes from settings database table
$tuition_modes = [];
if (!empty($settings['tuition_modes'])) {
    $tuition_modes = json_decode($settings['tuition_modes'], true);
    $fee_day_scholar = $settings['fee_day_scholar'] ?? '3000';
    $fee_hostler = $settings['fee_hostler'] ?? '5000';
    $fee_tuition = $settings['fee_tuition'] ?? '1500';
    $tuition_modes = ['Day Scholar' => $fee_day_scholar, 'Hostler' => $fee_hostler, 'Tuition' => $fee_tuition];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management & Ledger | ABSS Admin</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .form-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 35px; }
        .list-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .amount-tag { background: #dcfce7; color: #15803d; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 0.88rem; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-unpaid { background: #fee2e2; color: #b91c1c; }
        
        .btn-quick-collect {
            background: #f1f5f9;
            color: #334155;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.25s ease;
            border: 1px solid #cbd5e1;
        }
        .btn-quick-collect:hover {
            background: var(--portal-blue);
            color: #ffffff;
            border-color: var(--portal-blue);
            transform: translateY(-1px);
        }

        .btn-action-edit { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
        .btn-action-edit:hover { background: #0284c7; color: #ffffff; }

        .btn-action-delete { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .btn-action-delete:hover { background: #dc2626; color: #ffffff; }

        /* Modal Styles */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 2500;
            align-items: center;
            justify-content: center;
        }
        .modal-backdrop.active { display: flex; }
        .edit-modal-box {
            background: #ffffff;
            padding: 30px;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 30px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Fee Management & Ledger</h1>
            <p style="margin: 0;">Generate monthly invoices, collect payments, and manage fee records.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <!-- Form Section -->
        <div class="form-cols">
            <!-- Form 1: Collect Fee -->
            <div class="portal-card">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;"><i class="fas fa-hand-holding-usd" style="color:var(--portal-blue); margin-right:8px;"></i> Collect Fee Payment</h3>
                <form action="" method="POST">
                    <input type="hidden" name="record_payment" value="1">
                    <div class="portal-input-group">
                        <label style="display:flex; justify-content:space-between;">
                            <span>Select Student</span>
                            <span id="display_total_due" style="font-weight:800; display:none;"></span>
                        </label>
                        <select name="student_id" id="collect_student_id" required>
                            <option value="" data-due="0">-- Select Student --</option>
                            <?php foreach($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>" data-due="<?php echo $student['total_due']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="portal-input-group">
                            <label>Amount Paid (₹)</label>
                            <input type="number" step="0.01" name="amount" placeholder="e.g. 3000" required>
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
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="portal-input-group">
                            <label>Payment Method</label>
                            <select name="payment_method" required>
                                <option value="Cash">Cash (Offline Direct)</option>
                                <option value="UPI / Online">UPI / Online Transfer</option>
                                <option value="Bank Transfer">Bank Transfer / Cheque</option>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Payment Date</label>
                            <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-portal" style="width: 100%;">
                        <i class="fas fa-receipt"></i> Record Fee Payment
                    </button>
                </form>
            </div>

            <!-- Form 2: Manual Fee Generation -->
            <div class="portal-card">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;"><i class="fas fa-file-invoice-dollar" style="color:#7c3aed; margin-right:8px;"></i> Generate Manual Fee</h3>
                <form action="" method="POST">
                    <input type="hidden" name="generate_manual_fee" value="1">
                    <div class="portal-input-group">
                        <label>Select Student</label>
                        <select name="student_id" id="manual_student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>" 
                                        data-scholar-mode="<?php echo htmlspecialchars($student['scholar_mode'] ?? ''); ?>"
                                        data-base-fee="<?php echo $student['base_fee']; ?>"
                                        data-monthly-discount="<?php echo $student['monthly_discount']; ?>">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="portal-input-group">
                            <label>Fee Category / Type</label>
                            <select name="fee_type" id="manual_fee_type" required>
                                <?php foreach($tuition_modes as $modeName => $modeRate): ?>
                                    <option value="<?php echo htmlspecialchars($modeName); ?>"><?php echo htmlspecialchars($modeName); ?> (₹<?php echo number_format($modeRate); ?>)</option>
                                <?php endforeach; ?>
                                <option value="Custom">Custom / Add-on Fee</option>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>For Month</label>
                            <select name="month_for" required>
                                <?php 
                                foreach($months as $m) {
                                    $sel = (date('F') == $m) ? 'selected' : '';
                                    echo "<option value='$m' $sel>$m</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" id="manual_amount" placeholder="Calculated rate" required>
                        <small id="manual_fee_discount_hint" style="display:block; margin-top:5px; font-weight:700;"></small>
                    </div>
                    <div class="portal-input-group">
                        <label>Remarks / Item Description</label>
                        <input type="text" name="remark" placeholder="e.g. Monthly Tuition, Exam Fee, Uniform" required>
                    </div>
                    <button type="submit" class="btn-portal" style="width: 100%; background: linear-gradient(135deg, #7c3aed, #6d28d9);">
                        <i class="fas fa-plus-circle"></i> Generate Manual Invoice
                    </button>
                </form>
            </div>
        </div>

        <!-- Ledger Logs Section -->
        <div class="list-cols">
            <!-- List 1: Billed Invoices -->
            <div class="portal-card">
                <form id="bulkDeleteForm" method="POST">
                    <input type="hidden" name="bulk_delete_bills" value="1">
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
                        <h3 style="font-size: 1.2rem; margin:0;"><i class="fas fa-file-invoice" style="color:var(--portal-blue); margin-right:8px;"></i> Generated Invoices</h3>
                        
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <button type="button" id="btnBulkDelete" onclick="submitBulkDelete()" class="btn-quick-collect btn-action-delete" style="display:none; padding:7px 14px; font-weight:800;">
                                <i class="fas fa-trash-alt"></i> Delete Selected (<span id="selectedCount">0</span>)
                            </button>

                            <input type="text" id="search_bills_input" onkeyup="filterBillsTable()" placeholder="🔍 Search student, month..." style="padding:8px 14px; border-radius:10px; border:1px solid #cbd5e1; font-size:0.85rem; width:180px;">
                            
                            <select name="filter" onchange="window.location.href='fees.php?filter=' + this.value" style="padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1; font-size:0.85rem;">
                                <option value="all" <?php echo ($filter=='all')?'selected':''; ?>>All Invoices</option>
                                <option value="unpaid" <?php echo ($filter=='unpaid')?'selected':''; ?>>Unpaid Only</option>
                                <option value="paid" <?php echo ($filter=='paid')?'selected':''; ?>>Paid Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-table-container">
                        <table id="billsTable">
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align: center;">
                                        <input type="checkbox" id="selectAllBills" onclick="toggleSelectAllBills(this)" style="cursor:pointer; width:16px; height:16px;">
                                    </th>
                                    <th>Student</th>
                                    <th>Amount / Month</th>
                                    <th>Remarks & Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bills->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 25px;">No invoice records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($b = $bills->fetch_assoc()): ?>
                                        <tr class="bill-row">
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="selected_bill_ids[]" value="<?php echo $b['id']; ?>" class="bill-checkbox" onclick="updateBulkDeleteState()" style="cursor:pointer; width:16px; height:16px;">
                                            </td>
                                            <td>
                                                <strong class="bill-student-name" style="color:var(--portal-dark); font-size:0.95rem;"><?php echo htmlspecialchars($b['name']); ?></strong><br>
                                                <small style="color:#64748b; font-weight:600;">Inv #<?php echo $b['id']; ?> • <?php echo date('d M, Y', strtotime($b['billing_date'])); ?></small>
                                            </td>
                                            <td>
                                                <div style="font-weight:800; color:var(--portal-dark); font-size:1.05rem;">₹ <?php echo number_format($b['amount'], 2); ?></div>
                                                <small class="bill-month-for" style="color:var(--portal-blue); font-weight:700;"><?php echo htmlspecialchars($b['month_for']); ?></small>
                                            </td>
                                            <td>
                                                <div class="bill-remark" style="font-size:0.82rem; margin-bottom:10px; line-height:1.4; color:#475569; max-width:260px; word-break:break-word;">
                                                    <?php echo htmlspecialchars($b['remark']); ?>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                    <span class="status-badge status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span>
                                                    
                                                    <a href="view_bill.php?id=<?php echo $b['id']; ?>" class="btn-quick-collect" title="View Printable Bill">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    
                                                    <button type="button" class="btn-quick-collect btn-action-edit" 
                                                            onclick="openEditModal(<?php echo $b['id']; ?>, <?php echo $b['amount']; ?>, '<?php echo addslashes($b['month_for']); ?>', '<?php echo addslashes($b['remark']); ?>', '<?php echo $b['status']; ?>')" 
                                                            title="Edit Bill Details">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>

                                                    <button type="button" class="btn-quick-collect btn-action-delete" onclick="submitSingleDelete(<?php echo $b['id']; ?>)" title="Delete Invoice">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>

                                                    <?php if ($b['status'] === 'unpaid'): ?>
                                                        <a href="?collect_offline=<?php echo $b['id']; ?>" class="btn-quick-collect" style="background:#dcfce7; color:#15803d; border-color:#bbf7d0;" onclick="return confirm('Record offline cash collection of ₹<?php echo number_format($b['amount'], 2); ?> for <?php echo htmlspecialchars($b['name']); ?>?')" title="Quick Collect Cash">
                                                            <i class="fas fa-check"></i> Collect Cash
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
                </form>
            </div>

            <!-- Single Delete Hidden Form -->
            <form id="singleDeleteForm" method="POST">
                <input type="hidden" name="delete_bill" value="1">
                <input type="hidden" name="bill_id" id="single_delete_bill_id">
            </form>

            <!-- List 2: Recent Collections -->
            <div class="portal-card">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;"><i class="fas fa-history" style="color:var(--portal-blue); margin-right:8px;"></i> Recent Payment Logs</h3>
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
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 25px;">No payments recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($p = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong style="color:var(--portal-dark);"><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                        <td><span style="font-weight:700; color:var(--portal-blue); font-size:0.88rem;"><?php echo htmlspecialchars($p['month_for']); ?></span></td>
                                        <td><span class="amount-tag">₹ <?php echo number_format($p['amount'], 2); ?></span></td>
                                        <td>
                                            <div style="font-weight:700; color:#334155; font-size:0.85rem;"><?php echo date('d M, Y', strtotime($p['payment_date'])); ?></div>
                                            <small style="color:#64748b;"><i class="fas fa-wallet"></i> <?php echo htmlspecialchars($p['payment_method']); ?></small>
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

    <!-- Modal for Editing Generated Invoice -->
    <div id="editBillModal" class="modal-backdrop">
        <div class="edit-modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; font-size:1.25rem;"><i class="fas fa-edit" style="color:var(--portal-blue);"></i> Edit Invoice #<span id="modal_bill_id_title"></span></h3>
                <button type="button" onclick="closeEditModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="edit_bill" value="1">
                <input type="hidden" name="bill_id" id="edit_bill_id">

                <div class="portal-input-group">
                    <label>Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" id="edit_bill_amount" required>
                </div>

                <div class="portal-input-group">
                    <label>Bill Month(s)</label>
                    <input type="text" name="month_for" id="edit_bill_month_for" required>
                </div>

                <div class="portal-input-group">
                    <label>Remarks / Description</label>
                    <textarea name="remark" id="edit_bill_remark" rows="3" required></textarea>
                </div>

                <div class="portal-input-group">
                    <label>Status</label>
                    <select name="status" id="edit_bill_status" required>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeEditModal()" style="flex:1; background:#f1f5f9; color:#475569; border:none; padding:12px; border-radius:var(--radius-md); font-weight:800; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn-portal" style="flex:1;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Select All Checkbox Handler
        function toggleSelectAllBills(master) {
            const checkboxes = document.querySelectorAll('.bill-checkbox');
            checkboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = master.checked;
                }
            });
            updateBulkDeleteState();
        }

        // Update Bulk Delete Button Visibility & Counter
        function updateBulkDeleteState() {
            const selected = document.querySelectorAll('.bill-checkbox:checked');
            const btn = document.getElementById('btnBulkDelete');
            const counter = document.getElementById('selectedCount');
            
            if (counter) counter.textContent = selected.length;
            if (btn) {
                if (selected.length > 0) {
                    btn.style.display = 'inline-flex';
                } else {
                    btn.style.display = 'none';
                }
            }
        }

        // Submit Bulk Delete Form
        function submitBulkDelete() {
            const selected = document.querySelectorAll('.bill-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one invoice to delete.');
                return;
            }
            if (confirm('Are you sure you want to delete ' + selected.length + ' selected invoice(s)? This action cannot be undone.')) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        // Submit Single Delete Form
        function submitSingleDelete(id) {
            if (confirm('Are you sure you want to delete Invoice #' + id + '?')) {
                document.getElementById('single_delete_bill_id').value = id;
                document.getElementById('singleDeleteForm').submit();
            }
        }

        // Live Bills Search Filter
        function filterBillsTable() {
            const input = document.getElementById('search_bills_input');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.bill-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                    // Uncheck hidden row checkboxes so select all doesn't delete hidden items
                    const cb = row.querySelector('.bill-checkbox');
                    if (cb) cb.checked = false;
                }
            });
            updateBulkDeleteState();
        }

        // Open Edit Bill Modal
        function openEditModal(id, amount, monthFor, remark, status) {
            document.getElementById('edit_bill_id').value = id;
            document.getElementById('modal_bill_id_title').textContent = id;
            document.getElementById('edit_bill_amount').value = amount;
            document.getElementById('edit_bill_month_for').value = monthFor;
            document.getElementById('edit_bill_remark').value = remark;
            document.getElementById('edit_bill_status').value = status;
            
            document.getElementById('editBillModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editBillModal').classList.remove('active');
        }

        // Student selection helper for collection form
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
                        display.style.color = '#dc2626';
                    } else {
                        display.textContent = 'No Pending Dues';
                        display.style.color = '#166534';
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

            if (scholarMode && manualFeeTypeSelect) {
                for (let i = 0; i < manualFeeTypeSelect.options.length; i++) {
                    if (manualFeeTypeSelect.options[i].value.toLowerCase() === scholarMode.toLowerCase()) {
                        manualFeeTypeSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            const isCustom = manualFeeTypeSelect && manualFeeTypeSelect.value === 'Custom';

            if (isCustom) {
                if (discountHint) discountHint.textContent = '';
            } else {
                const finalAmount = Math.max(0, baseFee - discount);
                manualAmountInput.value = baseFee > 0 ? finalAmount : '';
                if (discountHint) {
                    discountHint.innerHTML = `<i class="fas fa-check-circle"></i> Net Monthly Fee: ₹${finalAmount.toFixed(2)}`;
                    discountHint.style.color = '#166534';
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
                            discountHint.innerHTML = `<i class="fas fa-check-circle"></i> Net Monthly Fee: ₹${finalAmount.toFixed(2)}`;
                            discountHint.style.color = '#166534';
                        }
                    } else {
                        const standardFee = parseFloat(tuitionModes[selectedMode] || 0);
                        manualAmountInput.value = standardFee > 0 ? standardFee : '';
                        if (discountHint) {
                            discountHint.innerHTML = `Standard Rate: ₹${standardFee.toFixed(2)}`;
                            discountHint.style.color = '#475569';
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
