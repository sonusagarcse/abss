<?php
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Delete Individual Payment Installment
if (isset($_GET['delete_payment']) && isset($_GET['invoice_id'])) {
    $pay_id = (int)$_GET['delete_payment'];
    $inv_id = (int)$_GET['invoice_id'];
    $conn->query("DELETE FROM teacher_invoice_payments WHERE id = $pay_id AND invoice_id = $inv_id");

    // Recalculate invoice totals
    $tot_q = $conn->query("SELECT SUM(amount) as total_paid, MAX(payment_date) as last_date FROM teacher_invoice_payments WHERE invoice_id = $inv_id");
    $tot_row = $tot_q ? $tot_q->fetch_assoc() : null;
    $total_paid = (float)($tot_row['total_paid'] ?? 0);
    $last_date = $tot_row['last_date'] ?? null;

    $inv_q = $conn->query("SELECT amount FROM teacher_invoices WHERE id = $inv_id");
    $inv = $inv_q ? $inv_q->fetch_assoc() : null;
    $inv_amount = (float)($inv['amount'] ?? 0);
    $status = ($total_paid >= $inv_amount && $inv_amount > 0) ? 'paid' : 'unpaid';

    $stmt = $conn->prepare("UPDATE teacher_invoices SET paid_amount = ?, paid_date = ?, status = ? WHERE id = ?");
    $stmt->bind_param("dssi", $total_paid, $last_date, $status, $inv_id);
    $stmt->execute();

    header("Location: teacher_invoices.php");
    exit();
}

// Handle Record Payment Action (Supports Partial & Full Installments)
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['record_payment'])) {
    $invoice_id = (int)$_POST['invoice_id'];
    $pay_amount = (float)$_POST['pay_amount'];
    $pay_date = !empty($_POST['pay_date']) ? trim($_POST['pay_date']) : date('Y-m-d');
    $pay_method = !empty($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Cash';
    $pay_notes = trim($_POST['payment_notes'] ?? '');
    
    // Fetch invoice details
    $inv_q = $conn->query("SELECT teacher_id, amount, paid_amount FROM teacher_invoices WHERE id = $invoice_id");
    if ($inv_q && $inv_q->num_rows > 0) {
        $inv = $inv_q->fetch_assoc();
        $teacher_id = (int)$inv['teacher_id'];
        
        if ($pay_amount > 0) {
            // Insert installment into teacher_invoice_payments
            $ins = $conn->prepare("INSERT INTO teacher_invoice_payments (invoice_id, teacher_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iidsss", $invoice_id, $teacher_id, $pay_amount, $pay_date, $pay_method, $pay_notes);
            $ins->execute();

            // Recalculate total paid
            $tot_q = $conn->query("SELECT SUM(amount) as total_paid, MAX(payment_date) as last_date FROM teacher_invoice_payments WHERE invoice_id = $invoice_id");
            $tot_row = $tot_q ? $tot_q->fetch_assoc() : null;
            $new_paid = (float)($tot_row['total_paid'] ?? 0);
            $last_date = $tot_row['last_date'] ?? $pay_date;
            $status = ($new_paid >= $inv['amount']) ? 'paid' : 'unpaid';
            
            $stmt = $conn->prepare("UPDATE teacher_invoices SET paid_amount = ?, paid_date = ?, status = ? WHERE id = ?");
            $stmt->bind_param("dssi", $new_paid, $last_date, $status, $invoice_id);
            if ($stmt->execute()) {
                $is_partial = ($new_paid < $inv['amount']);
                $type_label = $is_partial ? "Partial payment" : "Payment";
                $rem = max(0, $inv['amount'] - $new_paid);
                $rem_str = $is_partial ? " (Remaining balance: ₹" . number_format($rem, 2) . ")" : "";
                $msg = "$type_label of ₹" . number_format($pay_amount, 2) . " on " . date('d M Y', strtotime($pay_date)) . " recorded successfully.$rem_str";
            } else {
                $err = "Error recording payment.";
            }
        } else {
            $err = "Payment amount must be greater than zero.";
        }
    }
}

// Handle Add/Edit Invoice
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['save_invoice'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $amount = (float)$_POST['amount'];
    $paid_amount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0.00;
    $paid_date = !empty($_POST['paid_date']) ? trim($_POST['paid_date']) : ($paid_amount > 0 ? date('Y-m-d') : null);
    $issue_date = trim($_POST['issue_date']);
    $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
    $status = trim($_POST['status']);
    $month_for = isset($_POST['month_for']) ? trim($_POST['month_for']) : date('Y-m');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $invoice_number = isset($_POST['invoice_number']) ? trim($_POST['invoice_number']) : '';

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE teacher_invoices SET teacher_id=?, invoice_number=?, amount=?, paid_amount=?, paid_date=?, month_for=?, issue_date=?, due_date=?, status=? WHERE id=?");
        $stmt->bind_param("isddsssssi", $teacher_id, $invoice_number, $amount, $paid_amount, $paid_date, $month_for, $issue_date, $due_date, $status, $id);
        $stmt->execute();

        if ($paid_amount > 0) {
            $chk_p = $conn->query("SELECT COUNT(*) as c FROM teacher_invoice_payments WHERE invoice_id = $id");
            if ($chk_p && (int)$chk_p->fetch_assoc()['c'] === 0) {
                $p_date = !empty($paid_date) ? $paid_date : date('Y-m-d');
                $ins_p = $conn->prepare("INSERT INTO teacher_invoice_payments (invoice_id, teacher_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, 'Cash', 'Initial Payment')");
                $ins_p->bind_param("iids", $id, $teacher_id, $paid_amount, $p_date);
                $ins_p->execute();
            }
        }
    } else {
        $prefix = 'INV' . date('ym');
        $check = $conn->query("SELECT invoice_number FROM teacher_invoices WHERE invoice_number LIKE '{$prefix}%' ORDER BY invoice_number DESC LIMIT 1");
        if ($check && $row = $check->fetch_assoc()) {
            $last_num = (int)substr($row['invoice_number'], 7);
            $invoice_number = $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $invoice_number = $prefix . '0001';
        }
        
        // Auto-calculate the amount based on salary and pending expenses
        $t_res = $conn->query("SELECT salary FROM teachers WHERE id = $teacher_id");
        if($t_res && $t_res->num_rows > 0) {
            $t_sal = $t_res->fetch_assoc()['salary'];
            $exp_res = $conn->query("SELECT SUM(amount) as total_exp FROM teacher_expenses WHERE teacher_id = $teacher_id AND status = 'approved' AND invoice_id IS NULL");
            $exp_total = $exp_res->fetch_assoc()['total_exp'] ?? 0;
            $amount = $t_sal - $exp_total;
            if ($amount < 0) $amount = 0;
        }

        $stmt = $conn->prepare("INSERT INTO teacher_invoices (teacher_id, invoice_number, amount, paid_amount, paid_date, month_for, issue_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddsssss", $teacher_id, $invoice_number, $amount, $paid_amount, $paid_date, $month_for, $issue_date, $due_date, $status);
        if ($stmt->execute()) {
            $new_invoice_id = $stmt->insert_id;
            // Link expenses
            $conn->query("UPDATE teacher_expenses SET invoice_id = $new_invoice_id WHERE teacher_id = $teacher_id AND status = 'approved' AND invoice_id IS NULL");
            
            if ($paid_amount > 0) {
                $p_date = !empty($paid_date) ? $paid_date : date('Y-m-d');
                $ins_p = $conn->prepare("INSERT INTO teacher_invoice_payments (invoice_id, teacher_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, 'Cash', 'Initial Payment')");
                $ins_p->bind_param("iids", $new_invoice_id, $teacher_id, $paid_amount, $p_date);
                $ins_p->execute();
            }
        }
    }
    
    header("Location: teacher_invoices.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teacher_invoice_payments WHERE invoice_id = $id");
    $conn->query("DELETE FROM teacher_invoices WHERE id = $id");
    header("Location: teacher_invoices.php");
    exit();
}

$invoices = $conn->query("SELECT i.*, t.name as teacher_name FROM teacher_invoices i JOIN teachers t ON i.teacher_id = t.id ORDER BY i.issue_date DESC");
$payments_by_invoice = [];
$pay_res = $conn->query("SELECT * FROM teacher_invoice_payments ORDER BY payment_date ASC, id ASC");
if ($pay_res) {
    while($p = $pay_res->fetch_assoc()) {
        $payments_by_invoice[$p['invoice_id']][] = $p;
    }
}

$teachers = $conn->query("SELECT id, name FROM teachers WHERE status = 'active' ORDER BY name ASC");
$teachers_array = [];
if($teachers){
    while ($t = $teachers->fetch_assoc()) {
        $teachers_array[] = $t;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Invoices | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 20px 0; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 600px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); margin: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .btn-glass { background: #f8faff; color: var(--portal-blue); border: 2px solid #eef2ff; padding: 15px 25px; border-radius: 16px; font-weight: 700; cursor: pointer; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-unpaid { background: #fef2f2; color: #b91c1c; }
        .status-paid { background: #f0fdf4; color: #166534; }
        .status-partial { background: #fff3e0; color: #e65100; }

        .teacher-action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-action-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
            font-size: 0.88rem;
        }
        .btn-act-pay { background: #dcfce7; color: #166534; }
        .btn-act-pay:hover { background: #166534; color: #fff; }
        .btn-act-print { background: #eff6ff; color: #2563eb; }
        .btn-act-print:hover { background: #2563eb; color: #fff; }
        .btn-act-edit { background: #f3e8ff; color: #7c3aed; }
        .btn-act-edit:hover { background: #7c3aed; color: #fff; }
        .btn-act-del { background: #fef2f2; color: #dc2626; }
        .btn-act-del:hover { background: #dc2626; color: #fff; }

        .btn-payments-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e0f2fe;
            color: #0369a1;
            border: 1.5px solid #bae6fd;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 800;
            cursor: pointer;
            margin-top: 3px;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-payments-badge:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.2);
        }

        @media (max-width: 768px) {
            .action-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            .action-bar .btn-portal {
                width: 100%;
                justify-content: center;
            }
            .portal-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            table {
                min-width: 780px;
            }
            .modal-content {
                padding: 26px 20px !important;
                border-radius: 20px !important;
            }
            .portal-form-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1>Invoice Generation</h1>
                <p>Generate and manage invoices for teachers and staff payouts.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Create Invoice
            </button>
        </div>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#feeef2; color:#d32f2f; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Teacher</th>
                        <th>Month</th>
                        <th>Net Salary</th>
                        <th>Paid Amount & Date</th>
                        <th>Balance</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($invoices && $invoices->num_rows > 0): while($row = $invoices->fetch_assoc()): 
                        $balance = max(0, $row['amount'] - $row['paid_amount']);
                        $status_label = $row['status'];
                        $status_class = $row['status'];
                        if ($row['status'] !== 'paid' && $row['paid_amount'] > 0) {
                            $status_label = 'partial';
                            $status_class = 'partial';
                        }
                    ?>
                    <tr>
                        <td><strong style="color:var(--portal-blue);"><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                        <td><?php echo !empty($row['month_for']) ? date('M Y', strtotime($row['month_for'].'-01')) : '-'; ?></td>
                        <td style="color:var(--portal-blue); font-weight:700;">₹<?php echo number_format($row['amount'], 2); ?></td>
                        <td style="color:#2e7d32; font-weight:700;">
                            <?php 
                            $inv_payments = $payments_by_invoice[$row['id']] ?? [];
                            $pay_count = count($inv_payments);
                            ?>
                            ₹<?php echo number_format($row['paid_amount'], 2); ?>
                            <?php if ($row['paid_amount'] > 0): ?>
                                <?php if ($pay_count > 1): ?>
                                    <button type="button" class="btn-payments-badge" onclick='openPaymentHistoryModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>, <?php echo htmlspecialchars(json_encode($inv_payments), ENT_QUOTES, "UTF-8"); ?>)' title="Multiple payments made for this month. Click to view all <?php echo $pay_count; ?> payment installments with dates.">
                                        <i class="fas fa-history"></i> <?php echo $pay_count; ?> Payments (View Details)
                                    </button>
                                <?php else: 
                                    $single_date = !empty($inv_payments[0]['payment_date']) ? $inv_payments[0]['payment_date'] : $row['paid_date'];
                                ?>
                                    <small onclick='openPaymentHistoryModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>, <?php echo htmlspecialchars(json_encode($inv_payments), ENT_QUOTES, "UTF-8"); ?>)' style="display:inline-block; color:#15803d; font-size:0.75rem; font-weight:600; margin-top:2px; cursor:pointer;" title="Click to view payment breakdown">
                                        <i class="fas fa-calendar-check"></i> <?php echo !empty($single_date) ? date('d M Y', strtotime($single_date)) : 'Paid'; ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small style="display:block; color:#94a3b8; font-size:0.75rem; font-weight:600; margin-top:2px;">Unpaid</small>
                            <?php endif; ?>
                        </td>
                        <td style="color:#d32f2f; font-weight:800;">₹<?php echo number_format($balance, 2); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['issue_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                        </td>
                        <td>
                            <div class="teacher-action-buttons">
                                <?php if ($balance > 0): ?>
                                    <button class="btn-action-icon btn-act-pay" onclick='openPaymentModal(<?php echo $row['id']; ?>, <?php echo $balance; ?>, "<?php echo htmlspecialchars($row['invoice_number']); ?>")' title="Record Payment">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="print_teacher_invoice.php?id=<?php echo $row['id']; ?>" class="btn-action-icon btn-act-print" target="_blank" title="Print Invoice">
                                    <i class="fas fa-print"></i>
                                </a>
                                <button class="btn-action-icon btn-act-edit" onclick='editInvoice(<?php echo json_encode($row); ?>)' title="Edit Profile">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-action-icon btn-act-del" onclick="return confirm('Are you sure you want to delete invoice <?php echo addslashes($row['invoice_number']); ?>?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="9" style="text-align:center;">No invoices found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="invoiceModal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.8rem; margin:0;">Invoice Entry</h2>
                    <button type="button" onclick="hideModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <form action="" method="POST" id="invoiceForm">
                    <input type="hidden" name="id" id="invoice_id">

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Teacher <span style="color:red">*</span></label>
                            <select name="teacher_id" id="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach($teachers_array as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Invoice Number</label>
                            <input type="text" name="invoice_number" id="invoice_number" placeholder="Auto-generated on save" readonly style="background-color: #f8f9fa; border-color: #eef2ff; color: #9aa5ce;">
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Amount (₹)</label>
                            <input type="number" name="amount" id="amount" step="0.01" placeholder="Auto-calculated" readonly style="background-color: #f8f9fa; border-color: #eef2ff; color: #9aa5ce;">
                            <small style="color: #666; font-size: 0.8rem;">Calculated automatically as (Salary - Expenses) on Save</small>
                        </div>
                        <div class="portal-input-group">
                            <label>Paid Amount (₹)</label>
                            <input type="number" name="paid_amount" id="paid_amount" step="0.01" value="0.00">
                        </div>
                        <div class="portal-input-group">
                            <label>Paid Date</label>
                            <input type="date" name="paid_date" id="paid_date">
                        </div>
                        <div class="portal-input-group">
                            <label>Month <span style="color:red">*</span></label>
                            <input type="month" name="month_for" id="month_for" required>
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Status</label>
                            <select name="status" id="status">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Issue Date <span style="color:red">*</span></label>
                            <input type="date" name="issue_date" id="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Due Date</label>
                            <input type="date" name="due_date" id="due_date">
                        </div>
                    </div>

                    <div class="portal-btn-row" style="margin-top:35px;">
                        <button type="submit" name="save_invoice" class="btn-portal w-100" style="padding:18px;">Save Invoice</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Record Payment Modal (Supports Partial or Full Payment) -->
        <div class="modal" id="paymentModal">
            <div class="modal-content" style="max-width: 480px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                    <div>
                        <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.45rem; margin:0;" id="paymentModalTitle">Record Salary Payment</h2>
                        <small style="color: #64748b; font-weight: 600;">Option to enter partial installment or full settlement</small>
                    </div>
                    <button type="button" onclick="hidePaymentModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="invoice_id" id="pay_invoice_id">
                    
                    <div class="portal-input-group">
                        <label>Outstanding Balance (₹)</label>
                        <input type="text" id="pay_balance_display" readonly style="background-color: #f8f9fa; border-color: #eef2ff; color: #0f172a; font-weight: 800; font-size: 1.15rem;">
                    </div>
                    
                    <div class="portal-input-group">
                        <label>Amount to Pay (₹) <span style="color:red">*</span></label>
                        <input type="number" name="pay_amount" id="pay_amount" step="0.01" min="0.01" required oninput="updatePaymentTypeIndicator()" style="font-weight: 800; color: #166534; font-size: 1.1rem;">
                        <div id="pay_type_indicator" style="margin-top: 6px; font-size: 0.8rem; font-weight: 700;"></div>
                    </div>

                    <div class="portal-form-row" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="portal-input-group">
                            <label>Payment Date <span style="color:red">*</span></label>
                            <input type="date" name="pay_date" id="pay_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Payment Method</label>
                            <select name="payment_method" id="pay_method">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI / Online">UPI / Online</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Payment Note / Installment Tag (Optional)</label>
                        <input type="text" name="payment_notes" id="pay_notes" placeholder="e.g. Partial payment 1, Balance installment, Cash advance...">
                    </div>
                    
                    <div class="portal-btn-row" style="margin-top:25px;">
                        <button type="submit" name="record_payment" class="btn-portal w-100" style="padding:15px; font-weight: 800;">
                            <i class="fas fa-check-circle"></i> Confirm & Submit Payment
                        </button>
                        <button type="button" class="btn-glass w-100" onclick="hidePaymentModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment History Details Popup Modal -->
        <div class="modal" id="paymentHistoryModal">
            <div class="modal-content" style="max-width: 680px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                    <div>
                        <h2 style="color: var(--portal-dark); font-weight: 800; font-size: 1.45rem; margin:0;" id="historyModalTitle">Payment Details</h2>
                        <small style="color: #64748b; font-weight: 600;" id="historyModalSubtitle">Invoice Payments History Ledger</small>
                    </div>
                    <button type="button" onclick="hidePaymentHistoryModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <div id="historySummaryStrip" style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:12px 18px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px; font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                    <div><b>Net Payable:</b> <span id="histNetPayable" style="color:var(--portal-blue); font-weight:800;">₹0.00</span></div>
                    <div><b>Total Paid:</b> <span id="histTotalPaid" style="color:#16a34a; font-weight:800;">₹0.00</span></div>
                    <div><b>Balance Due:</b> <span id="histBalanceDue" style="color:#dc2626; font-weight:800;">₹0.00</span></div>
                </div>

                <div class="portal-table-container" style="margin-bottom: 22px; max-height: 280px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse; border-spacing: 0;" id="historyPaymentsTable">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; background: #f8fafc;">
                                <th style="padding: 10px 14px; font-size: 0.78rem; text-align: left; color: #475569;">#</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; text-align: left; color: #475569;">Payment Date</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; text-align: left; color: #475569;">Amount Paid</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; text-align: left; color: #475569;">Method</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; text-align: left; color: #475569;">Note / Remarks</th>
                                <th style="padding: 10px 14px; font-size: 0.78rem; text-align: right; color: #475569;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="historyPaymentsTbody">
                            <!-- Injected dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn-portal" id="btnRecordMorePay" onclick="recordMorePaymentFromHistory()" style="padding: 10px 18px; font-size: 0.85rem; background: #16a34a; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Record Another Partial Payment
                    </button>
                    <button type="button" class="btn-glass" onclick="hidePaymentHistoryModal()" style="padding: 10px 22px;">Close</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('invoiceModal').style.display = 'flex';
            document.getElementById('invoice_id').value = '';
            document.querySelector('#invoiceModal form').reset();
            document.getElementById('issue_date').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('invoice_number').value = '';
            document.getElementById('paid_amount').value = '0.00';
            document.getElementById('paid_date').value = '';
            document.getElementById('month_for').value = '<?php echo date('Y-m'); ?>';
            document.getElementById('status').value = 'unpaid';
        }

        function hideModal() {
            document.getElementById('invoiceModal').style.display = 'none';
        }

        function editInvoice(data) {
            document.getElementById('invoiceModal').style.display = 'flex';
            document.getElementById('invoice_id').value = data.id;
            document.getElementById('teacher_id').value = data.teacher_id;
            document.getElementById('invoice_number').value = data.invoice_number;
            document.getElementById('amount').value = data.amount;
            document.getElementById('paid_amount').value = data.paid_amount;
            document.getElementById('paid_date').value = data.paid_date || '';
            document.getElementById('month_for').value = data.month_for || '<?php echo date('Y-m'); ?>';
            document.getElementById('issue_date').value = data.issue_date;
            document.getElementById('due_date').value = data.due_date || '';
            document.getElementById('status').value = data.status;
        }

        let currentPayBalance = 0;

        function openPaymentModal(invoiceId, balance, invoiceNumber) {
            document.getElementById('paymentModal').style.display = 'flex';
            document.getElementById('pay_invoice_id').value = invoiceId;
            document.getElementById('pay_balance_display').value = '₹' + balance.toFixed(2);
            
            currentPayBalance = balance;
            const payAmtInput = document.getElementById('pay_amount');
            payAmtInput.value = balance.toFixed(2);
            payAmtInput.max = balance;
            document.getElementById('pay_date').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('pay_method').value = 'Cash';
            document.getElementById('pay_notes').value = '';
            document.getElementById('paymentModalTitle').textContent = 'Pay Invoice ' + invoiceNumber;
            
            updatePaymentTypeIndicator();
        }

        function updatePaymentTypeIndicator() {
            const entered = parseFloat(document.getElementById('pay_amount').value) || 0;
            const indicator = document.getElementById('pay_type_indicator');
            if (!indicator) return;

            if (entered <= 0) {
                indicator.innerHTML = '<span style="color:#dc2626;"><i class="fas fa-exclamation-circle"></i> Please enter an amount</span>';
            } else if (entered < currentPayBalance) {
                const remaining = currentPayBalance - entered;
                indicator.innerHTML = '<span style="background:#fffbeb; color:#b45309; padding:3px 10px; border-radius:6px; border:1px solid #fde68a; display:inline-block;"><i class="fas fa-clock"></i> Partial Payment &bull; Remaining balance after payment: ₹' + remaining.toFixed(2) + '</span>';
            } else {
                indicator.innerHTML = '<span style="background:#f0fdf4; color:#15803d; padding:3px 10px; border-radius:6px; border:1px solid #bbf7d0; display:inline-block;"><i class="fas fa-check-circle"></i> Full Settlement &bull; Clears balance completely</span>';
            }
        }
        
        function hidePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Payment History Popup Logic
        let currentHistoryInvoice = null;

        function openPaymentHistoryModal(invoice, payments) {
            currentHistoryInvoice = invoice;
            const modal = document.getElementById('paymentHistoryModal');
            document.getElementById('historyModalTitle').textContent = 'Payment Details - ' + invoice.invoice_number;
            document.getElementById('historyModalSubtitle').textContent = 'Teacher: ' + invoice.teacher_name + (invoice.month_for ? ' • ' + invoice.month_for : '');

            const netPayable = parseFloat(invoice.amount) || 0;
            const totalPaid = parseFloat(invoice.paid_amount) || 0;
            const balance = Math.max(0, netPayable - totalPaid);

            document.getElementById('histNetPayable').textContent = '₹' + netPayable.toFixed(2);
            document.getElementById('histTotalPaid').textContent = '₹' + totalPaid.toFixed(2);
            document.getElementById('histBalanceDue').textContent = '₹' + balance.toFixed(2);

            const btnRecordMore = document.getElementById('btnRecordMorePay');
            if (balance > 0) {
                btnRecordMore.style.display = 'inline-flex';
            } else {
                btnRecordMore.style.display = 'none';
            }

            const tbody = document.getElementById('historyPaymentsTbody');
            tbody.innerHTML = '';

            if (!payments || payments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#94a3b8; font-weight:700;">No payment records found.</td></tr>';
            } else {
                payments.forEach((p, idx) => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #f1f5f9';
                    
                    const pDate = p.payment_date ? new Date(p.payment_date + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
                    const pAmt = parseFloat(p.amount) || 0;
                    const pMethod = p.payment_method || 'Cash';
                    const pNote = p.notes || '—';

                    tr.innerHTML = `
                        <td style="padding: 10px 14px; color: #64748b; font-weight: 700;">${idx + 1}</td>
                        <td style="padding: 10px 14px; font-weight: 700; color: #0f172a;"><i class="fas fa-calendar-alt" style="color: #2563eb; margin-right: 5px;"></i>${pDate}</td>
                        <td style="padding: 10px 14px; color: #16a34a; font-weight: 800;">₹${pAmt.toFixed(2)}</td>
                        <td style="padding: 10px 14px;"><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 700; color: #334155;">${pMethod}</span></td>
                        <td style="padding: 10px 14px; color: #475569; font-size: 0.82rem;">${pNote}</td>
                        <td style="padding: 10px 14px; text-align: right;">
                            <a href="?delete_payment=${p.id}&invoice_id=${invoice.id}" onclick="return confirm('Delete this payment of ₹${pAmt.toFixed(2)} recorded on ${pDate}?')" style="color: #dc2626; text-decoration: none; font-size: 0.9rem;" title="Delete Installment">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            modal.style.display = 'flex';
        }

        function hidePaymentHistoryModal() {
            document.getElementById('paymentHistoryModal').style.display = 'none';
        }

        function recordMorePaymentFromHistory() {
            if (!currentHistoryInvoice) return;
            hidePaymentHistoryModal();
            const balance = Math.max(0, parseFloat(currentHistoryInvoice.amount) - parseFloat(currentHistoryInvoice.paid_amount));
            openPaymentModal(currentHistoryInvoice.id, balance, currentHistoryInvoice.invoice_number);
        }
    </script>
</body>
</html>
