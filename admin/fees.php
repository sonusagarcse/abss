<?php
// admin/fees.php - Fee Management & Billing Ledger (2-Column Grid Redesign)
require_once 'includes/auth.php';

// Automatically run monthly fee billing engine for due students on page load (without blocking on emails)
$skip_email = true;
require_once 'includes/billing_engine.php';

$msg = '';
$err = '';

if (isset($billing_generated_count) && $billing_generated_count > 0) {
    $display_month = isset($current_eval_name) ? $current_eval_name : date('F Y');
    $msg = "Automated Monthly Billing: Fee invoices successfully generated/updated for <strong>$billing_generated_count</strong> student(s) for " . $display_month . ".";
}

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

        // Sequentially allocate payment across unpaid generated bills (oldest to newest)
        $rem_pay = (float)$amount;
        $unpaid_bills_stmt = $conn->prepare("
            SELECT id, amount, remark, billing_date 
            FROM fees_generated 
            WHERE student_id = ? AND status = 'unpaid' 
            ORDER BY billing_date ASC, id ASC
        ");
        $unpaid_bills_stmt->bind_param("i", $sid);
        $unpaid_bills_stmt->execute();
        $unpaid_bills_res = $unpaid_bills_stmt->get_result();

        while ($rem_pay > 0 && ($bill = $unpaid_bills_res->fetch_assoc())) {
            $bill_id = (int)$bill['id'];
            $bill_amt = (float)$bill['amount'];
            $existing_rem = trim($bill['remark'] ?? '');

            if ($rem_pay >= $bill_amt) {
                // Fully cleared this bill
                $payment_tag = "Payment received on $date (-₹" . number_format($bill_amt, 2) . ") (Rcpt #$pay_id)";
                $new_remark = !empty($existing_rem) ? ($existing_rem . " | " . $payment_tag) : $payment_tag;
                
                $u_stmt = $conn->prepare("UPDATE fees_generated SET amount = 0, status = 'paid', remark = ? WHERE id = ?");
                $u_stmt->bind_param("si", $new_remark, $bill_id);
                $u_stmt->execute();
                
                $rem_pay = round($rem_pay - $bill_amt, 2);
            } else {
                // Partial payment towards this bill
                $new_bill_amt = round($bill_amt - $rem_pay, 2);
                $payment_tag = "Payment received on $date (-₹" . number_format($rem_pay, 2) . ") (Rcpt #$pay_id)";
                $new_remark = !empty($existing_rem) ? ($existing_rem . " | " . $payment_tag) : $payment_tag;
                
                $u_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, status = 'unpaid', remark = ? WHERE id = ?");
                $u_stmt->bind_param("dsi", $new_bill_amt, $new_remark, $bill_id);
                $u_stmt->execute();
                
                $rem_pay = 0;
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
    $year = (int)date('Y');
    $remark = trim($_POST['remark']);
    $fee_type = trim($_POST['fee_type']);
    $send_email = isset($_POST['send_email']) ? true : false;

    $month_for_full = "$month $year";
    $billing_date = date('Y-m-d');

    $stmt = $conn->prepare("SELECT name, parent_id, last_billed_date FROM students WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $student_info = $stmt->get_result()->fetch_assoc();

    if ($student_info) {
        $st_name = $student_info['name'];
        $parent_id = $student_info['parent_id'];

        $conn->begin_transaction();
        try {
            $existing_q = $conn->query("SELECT id, amount, remark, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
            $existing = $existing_q->fetch_assoc();

            if ($existing) {
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
                $final_remark = "Manual Bill. " . $remark . " [" . $month_for_full . "]: ₹" . number_format($amount, 2);
                $insert_stmt = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
                $insert_stmt->bind_param("idsss", $sid, $amount, $month_for_full, $billing_date, $final_remark);
                $insert_stmt->execute();
                $invoice_id = $conn->insert_id;
                $msg = "Successfully generated new manual invoice of ₹" . number_format($amount, 2) . " for $st_name.";
            }

            if ($fee_type !== 'Custom') {
                $date_str = "01-$month-$year";
                $dt = DateTime::createFromFormat('d-F-Y', $date_str);
                if ($dt) {
                    $end_of_month = $dt->format('Y-m-t');
                    $conn->query("UPDATE students SET last_billed_date = '$end_of_month' WHERE id = $sid AND (last_billed_date IS NULL OR last_billed_date < '$end_of_month')");
                }
            }

            $conn->commit();

            if (function_exists('log_activity')) {
                log_activity('manual_fee_generated', "Generated manual fee of ₹" . number_format($amount, 2) . " for student $st_name ($month_for_full)");
            }

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

// Handle Add Daily Student Expense POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_daily_expense'])) {
    $exp_sid = (int)$_POST['expense_student_id'];
    $item_name = trim($_POST['item_name']);
    $amount = (float)$_POST['expense_amount'];
    $expense_date = trim($_POST['expense_date'] ?? date('Y-m-d'));

    if ($exp_sid > 0 && !empty($item_name) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO student_expenses (student_id, item_name, amount, expense_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $exp_sid, $item_name, $amount, $expense_date);
        if ($stmt->execute()) {
            $msg = "Daily expense <strong>" . htmlspecialchars($item_name) . " (₹" . number_format($amount, 2) . ")</strong> recorded successfully.";
            if (function_exists('log_activity')) {
                log_activity('daily_expense_added', "Added daily expense of ₹" . number_format($amount, 2) . " for student ID $exp_sid: $item_name");
            }
            // Trigger billing engine to safely update active unpaid bill or retain as unbilled without generating next month bill early
            $force_student_id = $exp_sid;
            ob_start();
            require __DIR__ . '/includes/billing_engine.php';
            ob_end_clean();
        } else {
            $err = "Database error while recording daily expense.";
        }
    } else {
        $err = "Please enter valid student, expense item title, and amount.";
    }
}

// Handle Delete Daily Expense GET Request
if (isset($_GET['delete_expense_id'])) {
    $exp_id = (int)$_GET['delete_expense_id'];
    $chk = $conn->query("SELECT student_id, item_name, amount FROM student_expenses WHERE id = $exp_id AND status = 'unbilled'");
    if ($chk && $chk->num_rows > 0) {
        $st_row = $chk->fetch_assoc();
        $conn->query("DELETE FROM student_expenses WHERE id = $exp_id AND status = 'unbilled'");
        $msg = "Unbilled daily expense deleted successfully.";
    } else {
        $err = "Cannot delete this expense (it has already been billed into a monthly invoice).";
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
        $settings = function_exists('getAllSettings') ? getAllSettings() : [];
        $fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($bill, $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0];
        $fine_amount = (float)$fine_calc['fine_amount'];
        $total_amount = (float)$bill['amount'] + $fine_amount;
        $date = date('Y-m-d');
        $month = $bill['month_for'];
        if ($fine_amount > 0) {
            $month .= " (Incl. ₹" . number_format($fine_amount, 2) . " Late Fine)";
        }
        $method = 'Cash (Offline Direct)';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $sid, $total_amount, $date, $month, $method);
            $stmt->execute();
            $pay_id = $conn->insert_id;
            $payment_tag = "Payment received on $date (-₹" . number_format($total_amount, 2) . ") (Rcpt #$pay_id)";
            $new_remark = !empty($bill['remark']) ? ($bill['remark'] . " | " . $payment_tag) : $payment_tag;
            $conn->query("UPDATE fees_generated SET amount = 0, status = 'paid', remark = '" . $conn->real_escape_string($new_remark) . "' WHERE id = $bill_id");
            $conn->commit();

            $msg = "Successfully collected ₹" . number_format($total_amount, 2) . " cash payment for " . htmlspecialchars($bill['name']) . " (Base: ₹" . number_format($bill['amount'], 2) . ($fine_amount > 0 ? " + Fine: ₹" . number_format($fine_amount, 2) : "") . "). Invoice #$bill_id marked as PAID.";
            if (function_exists('log_activity')) {
                log_activity('fee_payment_recorded', "Quick collected cash ₹" . number_format($total_amount, 2) . " for student " . $bill['name'] . " (Invoice #$bill_id)");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Failed to process collection: " . $e->getMessage();
        }
    }
}

// Fetch active students with total pending dues calculated (including dynamic fine if enabled)
$students_res = $conn->query("
    SELECT s.id, s.name, s.parent_name, s.scholar_mode, s.base_fee, s.monthly_discount
    FROM students s
    WHERE s.status = 'active'
    ORDER BY s.name ASC
");
$students_list = [];
$settings = function_exists('getAllSettings') ? getAllSettings() : [];
while($s = $students_res->fetch_assoc()) {
    $sid_val = (int)$s['id'];
    $fine_info = function_exists('get_student_total_fine') ? get_student_total_fine($sid_val, $conn, $settings) : ['total_fine' => 0.00];
    
    $base_due_q = $conn->query("SELECT COALESCE(SUM(amount), 0) AS base_due FROM fees_generated WHERE student_id = $sid_val AND status = 'unpaid'");
    $base_due = (float)($base_due_q ? $base_due_q->fetch_assoc()['base_due'] : 0);
    $s['total_due'] = $base_due + (float)$fine_info['total_fine'];
    $students_list[] = $s;
}

// Fetch payments log (All non-Razorpay payment logs: Cash, Offline, Admin entries)
$payments = $conn->query("
    SELECT f.*, s.name, s.parent_name 
    FROM fee_payments f 
    JOIN students s ON f.student_id = s.id 
    WHERE (s.status = 'active' OR s.status IS NULL)
      AND NOT (
          f.payment_method LIKE '%Razorpay%' 
          OR f.payment_method LIKE '%pay_%' 
          OR f.payment_method LIKE '%rzp_%'
      )
    ORDER BY f.created_at DESC, f.id DESC LIMIT 10
");

// Fetch Razorpay Online payments with comprehensive student details
$online_payments_res = $conn->query("
    SELECT f.*, s.name AS student_name, s.reg_no, s.class_admitted, s.scholar_mode, s.parent_name, s.phone 
    FROM fee_payments f 
    JOIN students s ON f.student_id = s.id 
    WHERE (
        f.payment_method LIKE '%Razorpay%' 
        OR f.payment_method LIKE '%pay_%' 
        OR f.payment_method LIKE '%rzp_%'
    )
    ORDER BY f.created_at DESC, f.id DESC
");

$online_payments_list = [];
$total_online_amount = 0;
$today_online_amount = 0;
$today_ymd = date('Y-m-d');

if ($online_payments_res) {
    while ($row = $online_payments_res->fetch_assoc()) {
        $online_payments_list[] = $row;
        $total_online_amount += (float)$row['amount'];
        $created_date = substr($row['created_at'], 0, 10);
        if ($created_date === $today_ymd || $row['payment_date'] === $today_ymd) {
            $today_online_amount += (float)$row['amount'];
        }
    }
}
$total_online_count = count($online_payments_list);


// Fetch bills log
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'unpaid';
$status_cond = " WHERE (s.status = 'active' OR s.status IS NULL)";
if ($filter === 'unpaid') $status_cond .= " AND fg.status = 'unpaid'";
if ($filter === 'paid') $status_cond .= " AND fg.status = 'paid'";

$bills = $conn->query("
    SELECT fg.*, s.name, s.parent_name 
    FROM fees_generated fg 
    JOIN students s ON fg.student_id = s.id 
    $status_cond
    ORDER BY s.name ASC, fg.id DESC LIMIT 200
");

// Fetch daily student expenses log (current month only)
$recent_expenses = $conn->query("
    SELECT e.*, s.name AS student_name, s.reg_no 
    FROM student_expenses e 
    JOIN students s ON e.student_id = s.id 
    WHERE (s.status = 'active' OR s.status IS NULL)
      AND MONTH(e.expense_date) = MONTH(CURDATE()) 
      AND YEAR(e.expense_date) = YEAR(CURDATE())
    ORDER BY e.expense_date DESC, e.id DESC 
    LIMIT 100
");

// Retrieve tuition modes from settings database table
$tuition_modes = [];
if (!empty($settings['tuition_modes'])) {
    $tuition_modes = json_decode($settings['tuition_modes'], true);
} else {
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
        .fees-main-2col {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 25px;
            align-items: start;
        }

        .amount-tag { background: #dcfce7; color: #15803d; padding: 5px 10px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-unpaid { background: #fee2e2; color: #b91c1c; }
        
        .btn-quick-collect {
            background: #f1f5f9;
            color: #334155;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            border: 1px solid #cbd5e1;
            min-height: 36px;
            cursor: pointer;
        }
        .btn-quick-collect:hover {
            background: var(--portal-blue);
            color: #ffffff;
            border-color: var(--portal-blue);
            transform: translateY(-1px);
        }
        .btn-quick-collect:active {
            transform: scale(0.96);
        }

        .btn-action-edit { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
        .btn-action-edit:hover { background: #0284c7; color: #ffffff; }

        .btn-action-delete { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .btn-action-delete:hover { background: #dc2626; color: #ffffff; }

        /* Top Metric Cards Strip */
        .top-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
            min-width: 0;
        }

        .fees-col-forms, .fees-col-invoices, .fees-col-logs {
            min-width: 0;
            display: block;
        }

        .form-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Modal Styles */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 2500;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-backdrop.active { display: flex; }
        .edit-modal-box {
            background: #ffffff;
            padding: 22px 24px;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.28);
            box-sizing: border-box;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        /* Edit Invoice Modal: Amount & Sync Card */
        .edit-amount-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        .amount-input-container {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px 12px;
            transition: all 0.2s ease;
        }
        .amount-input-container:focus-within {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .currency-symbol-big {
            font-size: 1.25rem;
            font-weight: 900;
            color: var(--portal-blue);
        }
        .edit-amount-main-input {
            border: none !important;
            outline: none !important;
            font-size: 1.35rem !important;
            font-weight: 900 !important;
            color: #0f172a !important;
            width: 100% !important;
            padding: 0 !important;
            background: transparent !important;
        }
        .badge-sync-active {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        @keyframes pulseHighlight {
            0% { transform: scale(1); background: #dcfce7; }
            50% { transform: scale(1.06); background: #bbf7d0; }
            100% { transform: scale(1); background: #dcfce7; }
        }
        .pulse-sync {
            animation: pulseHighlight 0.4s ease-in-out;
        }
        @keyframes pulseInputField {
            0% { background: transparent; }
            50% { background: rgba(37, 99, 235, 0.08); }
            100% { background: transparent; }
        }
        .pulse-input {
            animation: pulseInputField 0.35s ease-in-out;
        }

        /* Edit Invoice Modal: Itemized Remarks Section */
        .edit-remarks-section {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .edit-items-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 240px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .edit-item-row {
            display: grid;
            grid-template-columns: 1fr 125px 34px;
            gap: 8px;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 8px;
            transition: all 0.15s ease;
        }
        .edit-item-row:hover {
            border-color: #cbd5e1;
        }
        .edit-item-row.is-deduction {
            background: #fffbfb;
            border-color: #fed7aa;
        }
        .edit-item-title-input {
            border: 1px solid transparent !important;
            border-radius: 6px !important;
            padding: 6px 8px !important;
            font-size: 0.86rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            width: 100% !important;
            background: transparent !important;
            transition: all 0.15s;
        }
        .edit-item-title-input:focus {
            background: #ffffff !important;
            border-color: #bfdbfe !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
            outline: none !important;
        }
        .edit-item-col-amount {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 6px;
            transition: all 0.15s;
        }
        .edit-item-col-amount:focus-within {
            background: #ffffff;
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }
        .edit-currency-tag {
            font-size: 0.78rem;
            font-weight: 800;
            color: #64748b;
            margin-right: 3px;
        }
        .edit-item-amount-input {
            border: none !important;
            outline: none !important;
            background: transparent !important;
            font-size: 0.88rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            width: 100% !important;
            text-align: right !important;
            padding: 2px 0 !important;
        }
        .edit-item-amount-input.negative-amt {
            color: #b91c1c !important;
        }
        .btn-delete-item {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.82rem;
            padding: 0;
        }
        .btn-delete-item:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
            transform: scale(1.06);
        }
        .btn-add-item-chip {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.76rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s;
        }
        .btn-add-item-chip:hover {
            background: #2563eb;
            color: #ffffff;
        }
        .edit-items-empty {
            text-align: center;
            padding: 20px 10px;
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .btn-add-first-item {
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 8px;
            cursor: pointer;
        }
        .items-summary-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
        }
        .edit-raw-textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.82rem;
            font-family: monospace;
            color: #334155;
            box-sizing: border-box;
            resize: vertical;
        }

        .mobile-table-hint {
            display: none;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
            padding: 0 4px;
        }

        .channel-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.74rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .channel-badge.razorpay {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .channel-badge.app {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .time-pill {
            font-weight: 700;
            color: #b45309;
            font-size: 0.78rem;
            margin-top: 3px;
            background: #fef3c7;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 4px;
            white-space: nowrap;
            border: 1px solid #fde68a;
        }


        /* Responsive Layouts: Stack forms and ledger naturally, slide tables horizontally */
        @media (max-width: 1024px) {
            .fees-main-2col {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            .fees-col-forms, .fees-col-invoices, .fees-col-logs {
                display: block !important;
            }
        }

        @media (max-width: 768px) {
            .fees-page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px !important;
            }
            .header-action-wrap {
                width: 100%;
            }
            .header-action-wrap a {
                width: 100%;
                justify-content: center;
            }

            .top-metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }
            .top-metrics-grid > .portal-card:last-child:nth-child(odd) {
                grid-column: 1 / -1;
            }
            .top-metrics-grid .portal-card {
                padding: 12px 14px !important;
                gap: 10px !important;
                border-left-width: 3px !important;
            }
            .top-metrics-grid .stat-metric-icon {
                width: 38px !important;
                height: 38px !important;
                font-size: 1.05rem !important;
                border-radius: 10px !important;
            }
            .top-metrics-grid h3 {
                font-size: 1.1rem !important;
            }
            .top-metrics-grid span {
                font-size: 0.65rem !important;
                line-height: 1.1;
                display: block;
            }

            .table-header-controls {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px !important;
            }
            .table-search-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                width: 100%;
            }
            #search_bills_input {
                grid-column: 1 / -1;
                width: 100% !important;
                font-size: 16px !important;
                box-sizing: border-box;
            }
            .table-search-row select {
                grid-column: 1 / -1;
                width: 100% !important;
                font-size: 16px !important;
                box-sizing: border-box;
            }
            #btnBulkDelete {
                grid-column: 1 / -1;
                width: 100% !important;
                justify-content: center;
                box-sizing: border-box;
            }

            .mobile-table-hint {
                display: flex;
            }

            .portal-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 12px;
                width: 100%;
            }
            #billsTable {
                min-width: 580px;
                width: 100%;
            }
            #billsTable td, #billsTable th {
                padding: 12px 10px;
            }
            .bill-action-group {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 6px;
            }
            .btn-quick-collect {
                padding: 7px 10px;
                font-size: 0.8rem;
                min-height: 34px;
            }

            .fees-col-logs .portal-table-container table {
                min-width: 520px;
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .form-grid-2col {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }
            .portal-card {
                padding: 16px 14px !important;
                margin-bottom: 18px !important;
            }
            .edit-modal-box {
                padding: 18px 14px !important;
                border-radius: 16px !important;
                max-height: 92vh !important;
                width: 100% !important;
                margin: 8px !important;
            }
            .edit-modal-box input, 
            .edit-modal-box select, 
            .edit-modal-box textarea {
                font-size: 15px !important;
            }
            .edit-item-row {
                grid-template-columns: 1fr 105px 32px !important;
                gap: 6px !important;
                padding: 5px 6px !important;
            }
        }

        @media (max-width: 400px) {
            .top-metrics-grid {
                grid-template-columns: 1fr;
            }
            .top-metrics-grid > .portal-card:last-child:nth-child(odd) {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="fees-page-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-size: 1.65rem; margin-bottom: 4px;">Fee Management & Ledger</h1>
                <p style="margin: 0; color:#64748b; font-size: 0.9rem;">Collect payments, generate manual fees, and view real-time billing logs.</p>
            </div>
            <div class="header-action-wrap" style="display: flex; gap: 10px;">
                <a href="billing_dry_run.php" class="btn" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 9px 16px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-shield-alt"></i> Billing Simulator (Dry Run)
                </a>
            </div>
        </header>

        <?php
        $sec_totals_q = $conn->query("SELECT SUM(security_amount) AS total_sec, SUM(registration_fee) AS total_reg, SUM(admission_fee) AS total_adm, SUM(advance_amount) AS total_adv FROM students WHERE status = 'active'");
        $sec_row = ($sec_totals_q && $sec_totals_q->num_rows > 0) ? $sec_totals_q->fetch_assoc() : [];
        $total_sec_held = (float)($sec_row['total_sec'] ?? 0);
        $total_reg_fee = (float)($sec_row['total_reg'] ?? 0);
        $total_adm_fee = (float)($sec_row['total_adm'] ?? 0);
        $total_adv_held = (float)($sec_row['total_adv'] ?? 0);
        ?>

        <!-- Admin Top Fee Structure & Deposit Metric Strip -->
        <div class="top-metrics-grid">
            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #7c3aed; background: #faf5ff;">
                <div class="stat-metric-icon" style="width: 44px; height: 44px; border-radius: 12px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #581c87; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">₹ <?php echo number_format($total_sec_held, 2); ?></h3>
                    <span style="font-size: 0.72rem; color: #7c3aed; font-weight: 800; text-transform: uppercase;">Security Deposit</span>
                </div>
            </div>

            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #2563eb; background: #eff6ff;">
                <div class="stat-metric-icon" style="width: 44px; height: 44px; border-radius: 12px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #1e3a8a; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">₹ <?php echo number_format($total_reg_fee, 2); ?></h3>
                    <span style="font-size: 0.72rem; color: #2563eb; font-weight: 800; text-transform: uppercase;">Registration Fees</span>
                </div>
            </div>

            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #16a34a; background: #f0fdf4;">
                <div class="stat-metric-icon" style="width: 44px; height: 44px; border-radius: 12px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #14532d; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">₹ <?php echo number_format($total_adm_fee, 2); ?></h3>
                    <span style="font-size: 0.72rem; color: #16a34a; font-weight: 800; text-transform: uppercase;">Admission Fees</span>
                </div>
            </div>

            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #0284c7; background: #f0f9ff;">
                <div class="stat-metric-icon" style="width: 44px; height: 44px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-bolt"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #0369a1; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">₹ <?php echo number_format($total_online_amount, 2); ?></h3>
                    <span style="font-size: 0.72rem; color: #0284c7; font-weight: 800; text-transform: uppercase;">Online Collections (<?php echo $total_online_count; ?>)</span>
                </div>
            </div>

            <?php if ($total_adv_held > 0): ?>
            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #ea580c; background: #fff7ed;">
                <div class="stat-metric-icon" style="width: 44px; height: 44px; border-radius: 12px; background: #ffedd5; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #7c2d12; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">₹ <?php echo number_format($total_adv_held, 2); ?></h3>
                    <span style="font-size: 0.72rem; color: #ea580c; font-weight: 800; text-transform: uppercase;">Advance Credits</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

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

        <!-- 2-COLUMN SIDE-BY-SIDE GRID LAYOUT (Stacks cleanly on mobile) -->
        <div class="fees-main-2col">
            
            <!-- LEFT COLUMN: CONTROLS & FORMS -->
            <div class="fees-col-forms" id="feesColForms">
                <!-- Form 1: Collect Fee -->
                <div class="portal-card" style="margin-bottom: 25px;">
                    <h3 style="margin-bottom: 20px; font-size: 1.15rem; color:var(--portal-dark); font-weight:800; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
                        <i class="fas fa-hand-holding-usd" style="color:var(--portal-blue); margin-right:8px;"></i> Collect Fee Payment
                    </h3>
                    <form action="" method="POST">
                        <input type="hidden" name="record_payment" value="1">
                        <div class="portal-input-group">
                            <label style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:4px;">
                                <span>Select Student</span>
                                <span id="display_total_due" style="font-weight:800; display:none;"></span>
                            </label>
                            <select name="student_id" id="collect_student_id" required>
                                <option value="" data-due="0">-- Select Student --</option>
                                <?php foreach($students_list as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" data-due="<?php echo $student['total_due']; ?>"><?php echo htmlspecialchars($student['name']); ?><?php if(!empty($student['parent_name'])): ?> — S/o <?php echo htmlspecialchars($student['parent_name']); ?><?php endif; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-grid-2col">
                            <div class="portal-input-group">
                                <label>Amount Paid (₹)</label>
                                <input type="number" step="0.01" name="amount" placeholder="3000" required>
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
                        <div class="form-grid-2col">
                            <div class="portal-input-group">
                                <label>Method</label>
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
                        <button type="submit" class="btn-portal" style="width: 100%; padding: 13px;">
                            <i class="fas fa-receipt"></i> Record Fee Payment
                        </button>
                    </form>
                </div>

                <!-- Form 2: Manual Fee Generation -->
                <div class="portal-card">
                    <h3 style="margin-bottom: 20px; font-size: 1.15rem; color:var(--portal-dark); font-weight:800; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
                        <i class="fas fa-file-invoice-dollar" style="color:#7c3aed; margin-right:8px;"></i> Generate Manual Fee
                    </h3>
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
                                        <?php echo htmlspecialchars($student['name']); ?><?php if(!empty($student['parent_name'])): ?> — S/o <?php echo htmlspecialchars($student['parent_name']); ?><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-grid-2col">
                            <div class="portal-input-group">
                                <label>Category</label>
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
                            <small id="manual_fee_discount_hint" style="display:block; margin-top:4px; font-weight:700; color:#2563eb;"></small>
                        </div>
                        <div class="portal-input-group">
                            <label>Remarks / Item Description</label>
                            <input type="text" name="remark" placeholder="e.g. Monthly Tuition, Exam Fee, Uniform" required>
                        </div>
                        <button type="submit" class="btn-portal" style="width: 100%; padding: 13px; background: linear-gradient(135deg, #7c3aed, #6d28d9);">
                            <i class="fas fa-plus-circle"></i> Generate Manual Invoice
                        </button>
                    </form>
                </div>

                <!-- Form 3: Add Daily Student Expense -->
                <div class="portal-card" style="margin-top: 25px;">
                    <h3 style="margin-bottom: 20px; font-size: 1.15rem; color:var(--portal-dark); font-weight:800; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
                        <i class="fas fa-receipt" style="color:#d97706; margin-right:8px;"></i> Add Daily Student Expense
                    </h3>
                    <form action="" method="POST">
                        <input type="hidden" name="add_daily_expense" value="1">
                        <div class="portal-input-group">
                            <label>Select Student</label>
                            <select name="expense_student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach($students_list as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?><?php if(!empty($student['parent_name'])): ?> — S/o <?php echo htmlspecialchars($student['parent_name']); ?><?php endif; ?> (<?php echo htmlspecialchars($student['scholar_mode'] ?? 'Day Scholar'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Expense Item Title / Description</label>
                            <input type="text" name="item_name" placeholder="e.g. Mess Charges, Books, Medical, Transport" required>
                        </div>
                        <div class="form-grid-2col">
                            <div class="portal-input-group">
                                <label>Amount (₹)</label>
                                <input type="number" step="0.01" name="expense_amount" placeholder="250.00" required>
                            </div>
                            <div class="portal-input-group">
                                <label>Expense Date</label>
                                <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-portal" style="width: 100%; padding: 13px; background: linear-gradient(135deg, #d97706, #b45309);">
                            <i class="fas fa-plus"></i> Record Daily Expense
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: MASTER LEDGER & INVOICES TABLES -->
            <div style="display:flex; flex-direction:column; gap:25px; min-width:0;">
                <!-- List 1: Billed Invoices -->
                <div class="portal-card fees-col-invoices" id="feesColInvoices" style="margin-bottom: 0;">
                    <form id="bulkDeleteForm" method="POST">
                        <input type="hidden" name="bulk_delete_bills" value="1">
                        
                        <div class="table-header-controls" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; border-bottom:2px solid #f1f5f9; padding-bottom:12px;">
                            <h3 style="font-size: 1.15rem; margin:0; font-weight:800;"><i class="fas fa-file-invoice" style="color:var(--portal-blue); margin-right:8px;"></i> Generated Invoices</h3>
                            
                            <div class="table-search-row" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <button type="button" id="btnBulkDelete" onclick="submitBulkDelete()" class="btn-quick-collect btn-action-delete" style="display:none; padding:6px 12px; font-weight:800;">
                                    <i class="fas fa-trash-alt"></i> Delete Selected (<span id="selectedCount">0</span>)
                                </button>

                                <input type="text" id="search_bills_input" onkeyup="filterBillsTable()" placeholder="🔍 Search student..." style="padding:8px 12px; border-radius:10px; border:1px solid #cbd5e1; font-size:0.85rem; min-width:140px;">
                                
                                <select name="filter" onchange="window.location.href='fees.php?filter=' + this.value" style="padding: 8px 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-size:0.85rem;">
                                    <option value="all" <?php echo ($filter=='all')?'selected':''; ?>>All Invoices</option>
                                    <option value="unpaid" <?php echo ($filter=='unpaid')?'selected':''; ?>>Unpaid Only</option>
                                    <option value="paid" <?php echo ($filter=='paid')?'selected':''; ?>>Paid Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="mobile-table-hint">
                            <i class="fas fa-arrows-left-right"></i> Scroll table sideways to view full invoice &amp; actions
                        </div>
                        <div class="portal-table-container">
                            <table id="billsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 35px; text-align: center;">
                                            <input type="checkbox" id="selectAllBills" onclick="toggleSelectAllBills(this)" style="cursor:pointer; width:16px; height:16px;">
                                        </th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Actions & Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bills->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 25px;">No invoice records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while($b = $bills->fetch_assoc()): 
                                             $fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($b, $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0];
                                             $fine_amount = ($b['status'] === 'unpaid') ? (float)$fine_calc['fine_amount'] : 0.00;
                                             $total_payable = (float)$b['amount'] + $fine_amount;
                                        ?>
                                            <tr class="bill-row">
                                                <td style="text-align: center;">
                                                    <input type="checkbox" name="selected_bill_ids[]" value="<?php echo $b['id']; ?>" class="bill-checkbox" onclick="updateBulkDeleteState()" style="cursor:pointer; width:16px; height:16px;">
                                                </td>
                                                <td>
                                                    <strong class="bill-student-name" style="color:var(--portal-dark); font-size:0.92rem;"><?php echo htmlspecialchars($b['name']); ?></strong>
                                                    <?php if (!empty($b['parent_name'])): ?>
                                                        <div style="font-size:0.75rem; color:#64748b; font-weight:600;"><i class="fas fa-user-friends" style="font-size:0.7rem; color:#94a3b8;"></i> S/o <?php echo htmlspecialchars($b['parent_name']); ?></div>
                                                    <?php endif; ?>
                                                    <small style="color:#64748b; font-weight:600;">Inv #<?php echo $b['id']; ?> • <?php echo date('d M, Y', strtotime($b['billing_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <div style="font-weight:900; color:var(--portal-dark); font-size:1rem;">
                                                        ₹ <?php echo number_format($total_payable, 2); ?>
                                                    </div>
                                                    <?php if ($fine_amount > 0): ?>
                                                        <div style="font-size: 0.72rem; color: #ea580c; font-weight: 700; margin-top: 1px;">
                                                            (Base: ₹<?php echo number_format($b['amount'], 2); ?> + Fine: ₹<?php echo number_format($fine_amount, 2); ?>)
                                                        </div>
                                                    <?php endif; ?>
                                                    <small class="bill-month-for" style="color:var(--portal-blue); font-weight:700;"><?php echo htmlspecialchars($b['month_for']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="bill-action-group" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                        <span class="status-badge status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span>
                                                        
                                                        <a href="view_bill.php?id=<?php echo $b['id']; ?>" class="btn-quick-collect" title="View Bill">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        
                                                        <button type="button" class="btn-quick-collect btn-action-edit" 
                                                                data-id="<?php echo $b['id']; ?>"
                                                                data-amount="<?php echo $b['amount']; ?>"
                                                                data-month="<?php echo htmlspecialchars($b['month_for'] ?? '', ENT_QUOTES); ?>"
                                                                data-remark="<?php echo htmlspecialchars($b['remark'] ?? '', ENT_QUOTES); ?>"
                                                                data-status="<?php echo htmlspecialchars($b['status'] ?? '', ENT_QUOTES); ?>"
                                                                data-student="<?php echo htmlspecialchars($b['name'] ?? '', ENT_QUOTES); ?>"
                                                                onclick="openEditModalFromButton(this)" 
                                                                title="Edit Bill Details">
                                                            <i class="fas fa-edit"></i>
                                                        </button>

                                                        <button type="button" class="btn-quick-collect btn-action-delete" onclick="submitSingleDelete(<?php echo $b['id']; ?>)" title="Delete Invoice">
                                                            <i class="fas fa-trash"></i>
                                                        </button>

                                                        <?php if ($b['status'] === 'unpaid'): ?>
                                                            <a href="?collect_offline=<?php echo $b['id']; ?>" class="btn-quick-collect" style="background:#dcfce7; color:#15803d; border-color:#bbf7d0; font-weight:800;" onclick="return confirm('Record cash payment of ₹<?php echo number_format($total_payable, 2); ?> (<?php echo $fine_amount > 0 ? 'Base ₹' . number_format($b['amount'], 2) . ' + Fine ₹' . number_format($fine_amount, 2) : 'Full Dues'; ?>) for <?php echo htmlspecialchars($b['name']); ?>?')" title="Quick Collect Cash">
                                                                <i class="fas fa-check"></i> Cash (₹<?php echo number_format($total_payable, 2); ?>)
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

                <!-- List 2: Recent Collections & List 3: Daily Student Expenses Log -->
                <div class="fees-col-logs" id="feesColLogs">
                    
                    <!-- NEW SECTION: Online App & Razorpay Payments Ledger with Detailed Timestamps -->
                    <div class="portal-card" id="onlineLedgerCard" style="margin-bottom: 25px; border-top: 4px solid #0284c7;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; border-bottom:2px solid #f1f5f9; padding-bottom:14px;">
                            <div>
                                <h3 style="margin:0; font-size: 1.18rem; font-weight:800; color:var(--portal-dark); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <i class="fas fa-bolt" style="color:#0284c7;"></i> Online Payments (Razorpay Gateway)
                                    <span style="font-size:0.75rem; font-weight:800; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:6px; text-transform:uppercase;">
                                        <i class="fas fa-circle-check"></i> Live Gateway
                                    </span>
                                </h3>
                                <div style="font-size:0.8rem; color:#64748b; margin-top:4px;">
                                    Transactions processed strictly via Razorpay Payment Gateway with real-time timestamps
                                </div>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <span style="font-size:0.8rem; font-weight:800; color:#15803d; background:#dcfce7; padding:6px 12px; border-radius:8px; border:1px solid #bbf7d0;">
                                    Total: ₹ <?php echo number_format($total_online_amount, 2); ?>
                                </span>
                                <input type="text" id="search_online_input" onkeyup="filterOnlineTable()" placeholder="🔍 Search online payment..." style="padding:7px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:0.82rem; min-width:160px;">
                            </div>
                        </div>

                        <div class="mobile-table-hint">
                            <i class="fas fa-arrows-left-right"></i> Scroll table sideways to view online transaction details &amp; exact time
                        </div>
                        <div class="portal-table-container">
                            <table id="onlinePaymentsTable">
                                <thead>
                                    <tr>
                                        <th>Txn / Rcpt ID</th>
                                        <th>Exact Date &amp; Time</th>
                                        <th>Student Details</th>
                                        <th>Month / For</th>
                                        <th>Payment Channel</th>
                                        <th>Amount Paid</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($online_payments_list)): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px 20px;">
                                                <div style="margin-bottom:8px; color:#cbd5e1; font-size:2rem;"><i class="fas fa-credit-card"></i></div>
                                                <strong style="color:#64748b; font-size:0.95rem;">No online or app payments recorded yet.</strong>
                                                <div style="font-size:0.8rem; color:#94a3b8; margin-top:4px;">When parents pay through Razorpay or the mobile app, transactions with exact timestamps will appear here.</div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($online_payments_list as $op): 
                                            $clean_method = $op['payment_method'];
                                            $rzp_code = '';
                                            if (preg_match('/(pay_[a-zA-Z0-9]+)/', $clean_method, $matches)) {
                                                $rzp_code = $matches[1];
                                            }
                                            $is_rzp = (stripos($clean_method, 'Razorpay') !== false || !empty($rzp_code));
                                            
                                            // Format exact date and time
                                            $dt_timestamp = !empty($op['created_at']) ? strtotime($op['created_at']) : strtotime($op['payment_date']);
                                            $formatted_date = date('d M, Y', $dt_timestamp);
                                            $formatted_time = date('h:i:s A', $dt_timestamp);
                                        ?>
                                            <tr class="online-payment-row">
                                                <td>
                                                    <span style="font-family:monospace; font-weight:800; color:#1e293b; font-size:0.85rem;">
                                                        #RCPT-<?php echo str_pad($op['id'], 4, '0', STR_PAD_LEFT); ?>
                                                    </span>
                                                    <?php if (!empty($rzp_code)): ?>
                                                        <div style="font-family:monospace; font-size:0.75rem; color:#0284c7; font-weight:700; margin-top:2px;" title="Razorpay Payment ID">
                                                            <i class="fas fa-receipt"></i> <?php echo htmlspecialchars($rzp_code); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-weight:800; color:#1e293b; font-size:0.85rem; white-space:nowrap;">
                                                        <i class="far fa-calendar-check" style="color:#0284c7; margin-right:4px;"></i> <?php echo $formatted_date; ?>
                                                    </div>
                                                    <div class="time-pill">
                                                        <i class="far fa-clock"></i> <?php echo $formatted_time; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong style="color:var(--portal-dark); font-size:0.9rem;"><?php echo htmlspecialchars($op['student_name']); ?></strong>
                                                    <?php if (!empty($op['parent_name'])): ?>
                                                        <div style="font-size:0.75rem; color:#64748b; font-weight:600;"><i class="fas fa-user-friends" style="font-size:0.7rem; color:#94a3b8;"></i> S/o <?php echo htmlspecialchars($op['parent_name']); ?></div>
                                                    <?php endif; ?>
                                                    <div style="display:flex; gap:5px; align-items:center; flex-wrap:wrap; margin-top:2px;">
                                                        <?php if (!empty($op['reg_no'])): ?>
                                                            <span style="font-size:0.72rem; font-family:monospace; color:#475569; background:#f1f5f9; padding:1px 5px; border-radius:4px;">
                                                                <?php echo htmlspecialchars($op['reg_no']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <span style="font-size:0.72rem; color:#64748b;">
                                                            <?php echo htmlspecialchars($op['class_admitted'] ?? 'Class'); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="font-weight:700; color:var(--portal-blue); font-size:0.85rem; white-space:nowrap;">
                                                        <?php echo htmlspecialchars($op['month_for']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($is_rzp): ?>
                                                        <span class="channel-badge razorpay">
                                                            <i class="fas fa-bolt"></i> Razorpay Gateway
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="channel-badge app">
                                                            <i class="fas fa-mobile-screen"></i> Parent Mobile App
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="amount-tag" style="background:#dcfce7; color:#15803d; font-size:0.92rem; font-weight:800; white-space:nowrap;">
                                                        ₹ <?php echo number_format($op['amount'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-paid" style="display:inline-flex; align-items:center; gap:4px; font-size:0.72rem; white-space:nowrap;">
                                                        <i class="fas fa-check-circle"></i> Captured
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="receipt.php?id=<?php echo $op['id']; ?>" target="_blank" class="btn-quick-collect" style="padding:5px 10px; font-size:0.78rem; text-decoration:none; white-space:nowrap;" title="View & Print Official Receipt">
                                                        <i class="fas fa-receipt"></i> Print Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- List 2: Recent Collections -->
                    <div class="portal-card" style="margin-bottom: 25px;">
                        <h3 style="margin-bottom: 18px; font-size: 1.15rem; font-weight:800; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
                            <i class="fas fa-history" style="color:var(--portal-blue); margin-right:8px;"></i> Recent Payment Logs
                        </h3>
                        <div class="mobile-table-hint">
                            <i class="fas fa-arrows-left-right"></i> Scroll table sideways to view details
                        </div>
                        <div class="portal-table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Month</th>
                                        <th>Amount</th>
                                        <th>Date &amp; Time / Method</th>
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
                                                 <td>
                                                     <strong style="color:var(--portal-dark); font-size:0.9rem;"><?php echo htmlspecialchars($p['name']); ?></strong>
                                                     <?php if (!empty($p['parent_name'])): ?>
                                                         <div style="font-size:0.75rem; color:#64748b; font-weight:600;"><i class="fas fa-user-friends" style="font-size:0.7rem; color:#94a3b8;"></i> S/o <?php echo htmlspecialchars($p['parent_name']); ?></div>
                                                     <?php endif; ?>
                                                 </td>
                                                <td><span style="font-weight:700; color:var(--portal-blue); font-size:0.85rem;"><?php echo htmlspecialchars($p['month_for']); ?></span></td>
                                                <td><span class="amount-tag">₹ <?php echo number_format($p['amount'], 2); ?></span></td>
                                                <td>
                                                    <div style="font-weight:700; color:#334155; font-size:0.82rem; white-space:nowrap;">
                                                        <?php echo date('d M, Y', strtotime($p['payment_date'])); ?>
                                                        <?php if (!empty($p['created_at'])): ?>
                                                            <span style="color:#b45309; font-weight:600; font-size:0.75rem; margin-left:4px;">
                                                                <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($p['created_at'])); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-top:2px;">
                                                        <small style="color:#64748b;"><i class="fas fa-wallet"></i> <?php echo htmlspecialchars($p['payment_method']); ?></small>
                                                        <a href="receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn-quick-collect" style="padding:2px 7px; font-size:0.72rem; min-height:24px;" title="Print Receipt">
                                                            <i class="fas fa-receipt"></i>
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

                    <!-- List 3: Daily Student Expenses Log -->
                    <div class="portal-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; border-bottom:2px solid #f1f5f9; padding-bottom:12px;">
                            <h3 style="font-size: 1.15rem; margin:0; font-weight:800;">
                                <i class="fas fa-receipt" style="color:#d97706; margin-right:8px;"></i> Daily Student Expenses Log
                            </h3>
                            <span style="font-size: 0.78rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 4px 10px; border-radius: 6px;">
                                <i class="fas fa-calendar-alt"></i> <?php echo date('F Y'); ?> Entries
                            </span>
                        </div>

                        <div class="mobile-table-hint">
                            <i class="fas fa-arrows-left-right"></i> Scroll table sideways to view details
                        </div>
                        <div class="portal-table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Item & Amount</th>
                                        <th>Status & Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$recent_expenses || $recent_expenses->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 25px;">No daily expenses logged yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while($exp = $recent_expenses->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight:700; color:#334155; font-size:0.82rem;"><?php echo date('d M, Y', strtotime($exp['expense_date'])); ?></div>
                                                </td>
                                                <td>
                                                    <strong style="color:var(--portal-dark); font-size:0.88rem;"><?php echo htmlspecialchars($exp['student_name']); ?></strong>
                                                    <?php if(!empty($exp['reg_no'])): ?>
                                                        <div><small style="color:#94a3b8; font-family:monospace;"><?php echo htmlspecialchars($exp['reg_no']); ?></small></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-weight:700; color:#1e293b; font-size:0.88rem;"><?php echo htmlspecialchars($exp['item_name']); ?></div>
                                                    <span class="amount-tag" style="background:#fef3c7; color:#b45309; padding:3px 8px; font-size:0.8rem;">₹ <?php echo number_format($exp['amount'], 2); ?></span>
                                                </td>
                                                <td>
                                                    <?php if($exp['status'] === 'billed'): ?>
                                                        <span class="status-badge status-paid" style="background:#dcfce7; color:#15803d;"><i class="fas fa-check-circle"></i> Billed</span>
                                                    <?php else: ?>
                                                        <div style="display:flex; align-items:center; gap:6px;">
                                                            <span class="status-badge status-unpaid" style="background:#fef3c7; color:#b45309;"><i class="fas fa-clock"></i> Unbilled</span>
                                                            <a href="fees.php?delete_expense_id=<?php echo $exp['id']; ?>" class="btn-quick-collect btn-action-delete" style="padding:3px 8px;" onclick="return confirm('Delete this unbilled expense?');" title="Delete Expense">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
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
            </div>

        </div>
    </main>

    <!-- Modal for Editing Generated Invoice -->
    <div id="editBillModal" class="modal-backdrop">
        <div class="edit-modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div>
                    <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--portal-dark); display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-edit" style="color:var(--portal-blue);"></i> 
                        <span>Edit Invoice #<span id="modal_bill_id_title"></span></span>
                    </h3>
                    <div id="modal_bill_student_subtitle" style="font-size:0.78rem; color:#64748b; font-weight:600; margin-top:2px;"></div>
                </div>
                <button type="button" onclick="closeEditModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b; padding:0; width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:50%;" title="Close dialog">&times;</button>
            </div>
            <form method="POST" id="editInvoiceForm" onsubmit="prepareInvoiceFormSubmit(event)">
                <input type="hidden" name="edit_bill" value="1">
                <input type="hidden" name="bill_id" id="edit_bill_id">

                <!-- Prominent Amount Card with Real-time Sync Indicator -->
                <div class="edit-amount-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label style="font-size:0.78rem; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.4px; margin:0;">
                            Total Invoice Amount
                        </label>
                        <span id="amount_sync_pill" class="badge-sync-active" title="Automatically recalculated when items are edited or deleted">
                            <i class="fas fa-bolt"></i> Realtime Synced
                        </span>
                    </div>
                    <div class="amount-input-container">
                        <span class="currency-symbol-big">₹</span>
                        <input type="number" step="0.01" name="amount" id="edit_bill_amount" class="edit-amount-main-input" required>
                    </div>
                    <div style="font-size:0.72rem; color:#64748b; margin-top:4px; font-weight:600;">
                        Auto-updates whenever items below are added, changed, or deleted.
                    </div>
                </div>

                <!-- 2-Column Month & Status Grid -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div class="portal-input-group" style="margin-bottom:0;">
                        <label style="font-size:0.78rem; font-weight:700;">Bill Month(s)</label>
                        <input type="text" name="month_for" id="edit_bill_month_for" required style="padding:8px 10px; font-size:0.88rem;">
                    </div>

                    <div class="portal-input-group" style="margin-bottom:0;">
                        <label style="font-size:0.78rem; font-weight:700;">Invoice Status</label>
                        <select name="status" id="edit_bill_status" required style="padding:8px 10px; font-size:0.88rem;">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>

                <!-- Itemized Remarks / Fee Breakdown Section -->
                <div class="edit-remarks-section">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label style="font-size:0.82rem; font-weight:800; color:var(--portal-dark); margin:0;">
                                <i class="fas fa-layer-group" style="color:var(--portal-blue);"></i> Fee Items & Breakdown
                            </label>
                            <span id="items_count_badge" style="background:#e0e7ff; color:#3730a3; font-size:0.7rem; font-weight:800; padding:2px 7px; border-radius:12px;">0 items</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <button type="button" onclick="toggleRawRemarkMode()" id="btn_toggle_raw" style="background:none; border:none; color:#64748b; font-size:0.75rem; font-weight:700; cursor:pointer; text-decoration:underline;">
                                <i class="fas fa-code"></i> Raw Text
                            </button>
                            <button type="button" onclick="addEditModalItem()" class="btn-add-item-chip">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic List of Item Rows -->
                    <div id="edit_items_container" class="edit-items-container">
                        <!-- Rendered via JS -->
                    </div>

                    <!-- Raw Remark Textarea (Hidden by default, synchronized in real-time) -->
                    <div id="edit_raw_remark_wrap" style="display:none; margin-top:10px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:0.73rem; color:#64748b; font-weight:600;">Direct Remark String (pipe '|' separated):</span>
                            <span style="font-size:0.7rem; color:#0284c7; font-weight:700;">Synced with DB</span>
                        </div>
                        <textarea name="remark" id="edit_bill_remark" rows="3" class="edit-raw-textarea" oninput="onRawRemarkInput()"></textarea>
                    </div>

                    <!-- Breakdown Summary Bar -->
                    <div class="items-summary-bar">
                        <span style="font-size:0.78rem; font-weight:700; color:#475569;">Breakdown Sum:</span>
                        <span id="items_sum_display" style="font-size:0.92rem; font-weight:900; color:var(--portal-dark);">₹ 0.00</span>
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:16px;">
                    <button type="button" onclick="closeEditModal()" style="flex:1; background:#f1f5f9; color:#475569; border:none; padding:11px; border-radius:var(--radius-md); font-weight:800; cursor:pointer;">Cancel</button>
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
                cb.checked = master.checked;
            });
            updateBulkDeleteState();
        }

        // Update Bulk Delete Button Visibility & Counter
        function updateBulkDeleteState() {
            const checkedCount = document.querySelectorAll('.bill-checkbox:checked').length;
            const btnBulk = document.getElementById('btnBulkDelete');
            const counter = document.getElementById('selectedCount');
            
            if (checkedCount > 0) {
                btnBulk.style.display = 'inline-flex';
                counter.innerText = checkedCount;
            } else {
                btnBulk.style.display = 'none';
            }
        }

        // Confirm Bulk Delete Action
        function submitBulkDelete() {
            const checkedCount = document.querySelectorAll('.bill-checkbox:checked').length;
            if (checkedCount === 0) return;
            
            if (confirm(`Are you sure you want to permanently delete ${checkedCount} selected invoice(s)? This action cannot be undone.`)) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        // Single Delete Handler
        function submitSingleDelete(billId) {
            if (confirm(`Are you sure you want to delete Invoice #${billId}?`)) {
                document.getElementById('single_delete_bill_id').value = billId;
                document.getElementById('singleDeleteForm').submit();
            }
        }

        // State for Edit Invoice Modal
        let currentEditItems = [];
        let currentRemarkPrefix = '';
        let isSyncingAmount = false;

        // Button Click Handler for Edit Modal
        function openEditModalFromButton(btn) {
            const id = btn.getAttribute('data-id');
            const amount = btn.getAttribute('data-amount');
            const month = btn.getAttribute('data-month');
            const remark = btn.getAttribute('data-remark');
            const status = btn.getAttribute('data-status');
            const student = btn.getAttribute('data-student') || '';
            openEditModal(id, amount, month, remark, status, student);
        }

        // Modal Open Handler
        function openEditModal(id, amount, month, remark, status, studentName = '') {
            const titleEl = document.getElementById('modal_bill_id_title');
            if (titleEl) titleEl.innerText = id;

            const subtitleEl = document.getElementById('modal_bill_student_subtitle');
            if (subtitleEl) {
                subtitleEl.innerHTML = studentName ? `<i class="fas fa-user-graduate" style="color:var(--portal-blue);"></i> Student: <strong>${escapeHtml(studentName)}</strong>` : '';
            }

            document.getElementById('edit_bill_id').value = id;
            document.getElementById('edit_bill_month_for').value = month || '';
            document.getElementById('edit_bill_status').value = status || 'unpaid';

            // Reset raw text mode visibility
            const rawWrap = document.getElementById('edit_raw_remark_wrap');
            if (rawWrap) rawWrap.style.display = 'none';
            const btnToggle = document.getElementById('btn_toggle_raw');
            if (btnToggle) btnToggle.innerHTML = '<i class="fas fa-code"></i> Raw Text';

            // Parse existing remarks into separate item entries
            const parsed = parseRemarkToItems(remark, amount);
            currentRemarkPrefix = parsed.prefix;
            currentEditItems = parsed.items;

            // Render separated items
            renderEditItems();

            // Set amount input & calculate total
            recalculateAndSyncModal(false);

            document.getElementById('editBillModal').classList.add('active');
        }

        // Modal Close Handler
        function closeEditModal() {
            document.getElementById('editBillModal').classList.remove('active');
        }

        // Helper to escape HTML characters
        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Parse remark string into structured items array
        function parseRemarkToItems(rawRemark, billAmount) {
            let text = (rawRemark || '').trim();
            let prefix = '';

            // Detect and separate system prefixes like "Auto-generated Bill." or "Manual Bill."
            const prefixMatch = text.match(/^(Auto-generated Bill\.|Manual Bill\.|Daily Expense\.)\s*/i);
            if (prefixMatch) {
                prefix = prefixMatch[1];
                text = text.slice(prefixMatch[0].length).trim();
            }

            const fallbackAmt = parseFloat(billAmount) || 0;

            if (!text) {
                return { 
                    prefix: prefix, 
                    items: [{ title: 'Fee / Charges', amount: fallbackAmt }] 
                };
            }

            // Split by pipe '|'
            const parts = text.split('|').map(s => s.trim()).filter(Boolean);
            const items = [];

            for (const part of parts) {
                // 1. Check for negative deduction/payment: e.g. Payment received on 2026-09-09 (-₹3,000.00) (Rcpt #18)
                const negMatch = part.match(/^(.*?)\s*\(\s*-\s*[₹Rs\.]*\s*([0-9,]+(?:\.[0-9]+)?)\s*\)\s*(.*)$/i);
                if (negMatch) {
                    const amt = -Math.abs(parseFloat(negMatch[2].replace(/,/g, '')) || 0);
                    const title = (negMatch[1].trim() + (negMatch[3].trim() ? ' ' + negMatch[3].trim() : '')).trim();
                    items.push({ title: title || 'Payment Deduction', amount: amt });
                    continue;
                }

                // 2. Check for colon with amount: e.g. "Hostler Fee: ₹2,000.00 (September 2026)" or "Books: 500"
                const colonMatch = part.match(/^(.*?):\s*[₹Rs\.]*\s*([0-9,]+(?:\.[0-9]+)?)\s*(.*)$/i);
                if (colonMatch) {
                    const amt = parseFloat(colonMatch[2].replace(/,/g, '')) || 0;
                    const title = (colonMatch[1].trim() + (colonMatch[3].trim() ? ' ' + colonMatch[3].trim() : '')).trim();
                    items.push({ title: title || 'Fee Item', amount: amt });
                    continue;
                }

                // 3. Fallback check for currency symbol without colon: e.g. "Tuition ₹1,500.00"
                const currMatch = part.match(/^(.*?)[₹Rs\.]\s*([0-9,]+(?:\.[0-9]+)?)\s*(.*)$/i);
                if (currMatch) {
                    const amt = parseFloat(currMatch[2].replace(/,/g, '')) || 0;
                    const title = (currMatch[1].trim() + (currMatch[3].trim() ? ' ' + currMatch[3].trim() : '')).trim();
                    items.push({ title: title || 'Fee Item', amount: amt });
                    continue;
                }

                // 4. Plain text without any number: e.g. "Dudh" or "Previous carryover dues"
                items.push({
                    title: part,
                    amount: parts.length === 1 ? fallbackAmt : 0
                });
            }

            if (items.length === 0) {
                items.push({ title: 'Fee / Charges', amount: fallbackAmt });
            }

            return { prefix: prefix, items: items };
        }

        // Render separated item rows
        function renderEditItems() {
            const container = document.getElementById('edit_items_container');
            const badge = document.getElementById('items_count_badge');
            if (!container) return;

            if (currentEditItems.length === 0) {
                container.innerHTML = `
                    <div class="edit-items-empty">
                        <i class="fas fa-receipt" style="font-size:1.5rem; color:#cbd5e1; margin-bottom:6px; display:block;"></i>
                        No breakdown items currently added.<br>
                        <button type="button" onclick="addEditModalItem()" class="btn-add-first-item">
                            <i class="fas fa-plus"></i> Add Item / Fee Breakdown
                        </button>
                    </div>
                `;
                if (badge) badge.innerText = '0 items';
                return;
            }

            if (badge) {
                badge.innerText = `${currentEditItems.length} item${currentEditItems.length > 1 ? 's' : ''}`;
            }

            let html = '';
            currentEditItems.forEach((item, index) => {
                const isNegative = (parseFloat(item.amount) || 0) < 0;
                const safeTitle = escapeHtml(item.title || '');
                html += `
                    <div class="edit-item-row ${isNegative ? 'is-deduction' : ''}" data-index="${index}">
                        <div class="edit-item-col-title">
                            <input type="text" 
                                   class="edit-item-title-input" 
                                   placeholder="Item name / note (e.g. Tuition Fee)" 
                                   value="${safeTitle}" 
                                   oninput="onItemTitleChanged(${index}, this.value)">
                        </div>
                        <div class="edit-item-col-amount">
                            <span class="edit-currency-tag">₹</span>
                            <input type="number" 
                                   step="0.01" 
                                   class="edit-item-amount-input ${isNegative ? 'negative-amt' : ''}" 
                                   placeholder="0.00" 
                                   value="${item.amount}" 
                                   oninput="onItemAmountChanged(${index}, this.value)">
                        </div>
                        <div class="edit-item-col-delete">
                            <button type="button" 
                                    class="btn-delete-item" 
                                    onclick="deleteEditModalItem(${index})" 
                                    title="Delete this item">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Realtime Handler: Item Title Changed
        function onItemTitleChanged(index, newTitle) {
            if (currentEditItems[index]) {
                currentEditItems[index].title = newTitle;
                buildRemarkFromItems();
            }
        }

        // Realtime Handler: Item Amount Changed
        function onItemAmountChanged(index, newAmount) {
            if (currentEditItems[index]) {
                currentEditItems[index].amount = parseFloat(newAmount) || 0;
                recalculateAndSyncModal(true);
            }
        }

        // Handler: Delete Item
        function deleteEditModalItem(index) {
            if (currentEditItems[index]) {
                currentEditItems.splice(index, 1);
                renderEditItems();
                recalculateAndSyncModal(true);
            }
        }

        // Handler: Add New Item
        function addEditModalItem(defaultTitle = '', defaultAmount = 0) {
            currentEditItems.push({
                title: defaultTitle,
                amount: defaultAmount
            });
            renderEditItems();
            recalculateAndSyncModal(true);

            // Focus newly added title input
            const container = document.getElementById('edit_items_container');
            const inputs = container.querySelectorAll('.edit-item-title-input');
            if (inputs.length > 0) {
                inputs[inputs.length - 1].focus();
            }
        }

        // Recalculate Sum and Reflect to Main Amount Field in Real Time
        function recalculateAndSyncModal(pulse = false) {
            isSyncingAmount = true;
            let total = 0;
            currentEditItems.forEach(it => {
                total += (parseFloat(it.amount) || 0);
            });
            total = Math.round(total * 100) / 100;

            const amountInput = document.getElementById('edit_bill_amount');
            if (amountInput) {
                amountInput.value = total.toFixed(2);
                if (pulse) {
                    amountInput.classList.remove('pulse-input');
                    void amountInput.offsetWidth;
                    amountInput.classList.add('pulse-input');
                }
            }

            const sumDisplay = document.getElementById('items_sum_display');
            if (sumDisplay) {
                sumDisplay.innerText = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            const pill = document.getElementById('amount_sync_pill');
            if (pill && pulse) {
                pill.classList.remove('pulse-sync');
                void pill.offsetWidth;
                pill.classList.add('pulse-sync');
            }

            buildRemarkFromItems();
            isSyncingAmount = false;
        }

        // Rebuild Remark string from current items
        function buildRemarkFromItems() {
            const rawTextarea = document.getElementById('edit_bill_remark');
            if (!rawTextarea) return;

            if (currentEditItems.length === 0) {
                rawTextarea.value = '';
                return;
            }

            const parts = currentEditItems.map(it => {
                const title = (it.title || '').trim() || 'Fee Item';
                const amt = parseFloat(it.amount) || 0;
                if (amt < 0) {
                    const formatted = Math.abs(amt).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    return `${title} (-₹${formatted})`;
                } else {
                    const formatted = amt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    return `${title}: ₹${formatted}`;
                }
            });

            let full = parts.join(' | ');
            if (currentRemarkPrefix) {
                full = currentRemarkPrefix + ' ' + full;
            }
            rawTextarea.value = full;
        }

        // Direct editing of main amount input reflects back to items
        document.addEventListener('DOMContentLoaded', function() {
            const mainAmtInput = document.getElementById('edit_bill_amount');
            if (mainAmtInput) {
                mainAmtInput.addEventListener('input', function() {
                    if (isSyncingAmount) return;
                    const val = parseFloat(this.value) || 0;
                    if (currentEditItems.length === 1) {
                        currentEditItems[0].amount = val;
                        renderEditItems();
                        buildRemarkFromItems();
                    }
                    const sumDisplay = document.getElementById('items_sum_display');
                    if (sumDisplay) {
                        sumDisplay.innerText = '₹ ' + val.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                });
            }
        });

        // Toggle Raw Remarks View
        function toggleRawRemarkMode() {
            const wrap = document.getElementById('edit_raw_remark_wrap');
            const btn = document.getElementById('btn_toggle_raw');
            if (!wrap) return;

            if (wrap.style.display === 'none') {
                wrap.style.display = 'block';
                if (btn) btn.innerHTML = '<i class="fas fa-table-cells"></i> Visual Mode';
            } else {
                wrap.style.display = 'none';
                if (btn) btn.innerHTML = '<i class="fas fa-code"></i> Raw Text';
            }
        }

        // Realtime Handler when editing Raw Remark String
        function onRawRemarkInput() {
            const rawVal = document.getElementById('edit_bill_remark').value;
            const billAmt = document.getElementById('edit_bill_amount').value;
            const parsed = parseRemarkToItems(rawVal, billAmt);
            currentRemarkPrefix = parsed.prefix;
            currentEditItems = parsed.items;
            renderEditItems();

            let total = currentEditItems.reduce((acc, it) => acc + (parseFloat(it.amount) || 0), 0);
            total = Math.round(total * 100) / 100;
            document.getElementById('edit_bill_amount').value = total.toFixed(2);
            const sumDisplay = document.getElementById('items_sum_display');
            if (sumDisplay) {
                sumDisplay.innerText = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }

        // Form Submit Handler: Final sync & validation
        function prepareInvoiceFormSubmit(event) {
            // Remove completely empty items before submitting
            currentEditItems = currentEditItems.filter(it => (it.title || '').trim() !== '' || (parseFloat(it.amount) || 0) !== 0);
            buildRemarkFromItems();
            return true;
        }

        // Live Fee Rate & Discount Auto-Calculation
        document.addEventListener('DOMContentLoaded', function() {
            const studentSelect = document.getElementById('manual_student_id');
            const feeTypeSelect = document.getElementById('manual_fee_type');
            const amountInput = document.getElementById('manual_amount');
            const discountHint = document.getElementById('manual_fee_discount_hint');
            const collectSelect = document.getElementById('collect_student_id');
            const dueDisplay = document.getElementById('display_total_due');

            function updateManualAmount() {
                const opt = studentSelect.options[studentSelect.selectedIndex];
                if (!opt || !opt.value) {
                    discountHint.innerText = '';
                    return;
                }

                const baseFee = parseFloat(opt.getAttribute('data-base-fee')) || 0;
                const monthlyDiscount = parseFloat(opt.getAttribute('data-monthly-discount')) || 0;
                const selectedType = feeTypeSelect.value;

                if (selectedType !== 'Custom') {
                    let calcAmount = baseFee > 0 ? baseFee : 0;
                    if (calcAmount <= 0) {
                        <?php foreach($tuition_modes as $mName => $mRate): ?>
                            if (selectedType === '<?php echo addslashes($mName); ?>') calcAmount = <?php echo (float)$mRate; ?>;
                        <?php endforeach; ?>
                    }

                    const finalAmount = Math.max(0, calcAmount - monthlyDiscount);
                    amountInput.value = finalAmount.toFixed(2);

                    if (monthlyDiscount > 0) {
                        discountHint.innerText = `Standard Rate: ₹${calcAmount.toFixed(2)} - Monthly Discount: ₹${monthlyDiscount.toFixed(2)} = Net ₹${finalAmount.toFixed(2)}`;
                    } else {
                        discountHint.innerText = `Standard Rate: ₹${finalAmount.toFixed(2)}`;
                    }
                }
            }

            if (studentSelect && feeTypeSelect && amountInput) {
                studentSelect.addEventListener('change', updateManualAmount);
                feeTypeSelect.addEventListener('change', updateManualAmount);
            }

            if (collectSelect && dueDisplay) {
                collectSelect.addEventListener('change', function() {
                    const opt = collectSelect.options[collectSelect.selectedIndex];
                    const due = parseFloat(opt.getAttribute('data-due')) || 0;
                    if (due > 0) {
                        dueDisplay.innerText = `Pending Due: ₹${due.toFixed(2)}`;
                        dueDisplay.style.color = '#dc2626';
                        dueDisplay.style.display = 'inline';
                    } else if (opt.value) {
                        dueDisplay.innerText = `All Dues Paid`;
                        dueDisplay.style.color = '#16a34a';
                        dueDisplay.style.display = 'inline';
                    } else {
                        dueDisplay.style.display = 'none';
                    }
                });
            }
        });

        // Quick Search Filter for Bills Table
        function filterBillsTable() {
            const input = document.getElementById("search_bills_input");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("billsTable");
            const rows = table.getElementsByClassName("bill-row");

            for (let i = 0; i < rows.length; i++) {
                const nameCol = rows[i].querySelector(".bill-student-name");
                const monthCol = rows[i].querySelector(".bill-month-for");
                const remarkCol = rows[i].querySelector(".bill-remark");

                const nameText = nameCol ? nameCol.textContent.toLowerCase() : "";
                const monthText = monthCol ? monthCol.textContent.toLowerCase() : "";
                const remarkText = remarkCol ? remarkCol.textContent.toLowerCase() : "";

                if (nameText.includes(filter) || monthText.includes(filter) || remarkText.includes(filter)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        // Quick Search Filter for Online Payments Table
        function filterOnlineTable() {
            const input = document.getElementById("search_online_input");
            if (!input) return;
            const filter = input.value.toLowerCase();
            const table = document.getElementById("onlinePaymentsTable");
            if (!table) return;
            const rows = table.getElementsByClassName("online-payment-row");

            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent || rows[i].innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
