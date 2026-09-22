<?php
require_once 'includes/auth.php';

// Helper function to sync approved expenses to unpaid salary invoices
function syncTeacherExpenseDeduction($conn, $expense_id, $action = 'approve') {
    $exp_q = $conn->query("SELECT * FROM teacher_expenses WHERE id = $expense_id");
    if (!$exp_q || $exp_q->num_rows == 0) return;
    $exp = $exp_q->fetch_assoc();
    $teacher_id = (int)$exp['teacher_id'];
    $amount = (float)$exp['amount'];

    if ($action === 'approve') {
        // If not already linked to an invoice, link to the latest unpaid invoice and deduct
        if (empty($exp['invoice_id'])) {
            $inv_res = $conn->query("SELECT id, amount FROM teacher_invoices WHERE teacher_id = $teacher_id AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
            if ($inv_res && $inv_res->num_rows > 0) {
                $inv = $inv_res->fetch_assoc();
                $new_amount = max(0, (float)$inv['amount'] - $amount);
                $conn->query("UPDATE teacher_invoices SET amount = $new_amount WHERE id = " . $inv['id']);
                $conn->query("UPDATE teacher_expenses SET invoice_id = " . $inv['id'] . ", status = 'approved' WHERE id = $expense_id");
            } else {
                $conn->query("UPDATE teacher_expenses SET status = 'approved' WHERE id = $expense_id");
            }
        } else {
            $conn->query("UPDATE teacher_expenses SET status = 'approved' WHERE id = $expense_id");
        }
    } elseif ($action === 'reject' || $action === 'delete') {
        // If linked to an unpaid invoice, restore the invoice amount
        if (!empty($exp['invoice_id'])) {
            $inv_id = (int)$exp['invoice_id'];
            $inv_res = $conn->query("SELECT id, amount, status FROM teacher_invoices WHERE id = $inv_id AND status = 'unpaid'");
            if ($inv_res && $inv_res->num_rows > 0) {
                $inv = $inv_res->fetch_assoc();
                $restored_amount = (float)$inv['amount'] + $amount;
                $conn->query("UPDATE teacher_invoices SET amount = $restored_amount WHERE id = $inv_id");
            }
        }
        if ($action === 'reject') {
            $conn->query("UPDATE teacher_expenses SET status = 'rejected', invoice_id = NULL WHERE id = $expense_id");
        } elseif ($action === 'delete') {
            $conn->query("DELETE FROM teacher_expenses WHERE id = $expense_id");
        }
    }
}

// Handle Approve via GET
if (isset($_GET['approve'])) {
    $exp_id = (int)$_GET['approve'];
    syncTeacherExpenseDeduction($conn, $exp_id, 'approve');
    header("Location: teacher_expenses.php");
    exit();
}

// Handle Reject via GET
if (isset($_GET['reject'])) {
    $exp_id = (int)$_GET['reject'];
    syncTeacherExpenseDeduction($conn, $exp_id, 'reject');
    header("Location: teacher_expenses.php");
    exit();
}

// Handle Add/Edit Expense
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['save_expense'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $expense_type = trim($_POST['expense_type']);
    $amount = (float)$_POST['amount'];
    $expense_date = trim($_POST['expense_date']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE teacher_expenses SET teacher_id=?, expense_type=?, amount=?, expense_date=?, description=?, status=? WHERE id=?");
        $stmt->bind_param("isdsssi", $teacher_id, $expense_type, $amount, $expense_date, $description, $status, $id);
        $stmt->execute();
        
        if ($status === 'approved') {
            syncTeacherExpenseDeduction($conn, $id, 'approve');
        } elseif ($status === 'rejected') {
            syncTeacherExpenseDeduction($conn, $id, 'reject');
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO teacher_expenses (teacher_id, expense_type, amount, expense_date, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $teacher_id, $expense_type, $amount, $expense_date, $description, $status);
        if ($stmt->execute()) {
            $new_expense_id = $stmt->insert_id;
            if ($status === 'approved') {
                syncTeacherExpenseDeduction($conn, $new_expense_id, 'approve');
            }
        }
    }
    
    header("Location: teacher_expenses.php");
    exit();
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    syncTeacherExpenseDeduction($conn, $id, 'delete');
    header("Location: teacher_expenses.php");
    exit();
}

// Status Filter logic
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$teacher_filter = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

$where_clauses = [];
if (in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $where_clauses[] = "e.status = '$status_filter'";
}
if ($teacher_filter > 0) {
    $where_clauses[] = "e.teacher_id = $teacher_filter";
}
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch expenses with teacher & invoice data
$expenses = $conn->query("
    SELECT e.*, t.name as teacher_name, t.department as teacher_department, i.invoice_number 
    FROM teacher_expenses e 
    JOIN teachers t ON e.teacher_id = t.id 
    LEFT JOIN teacher_invoices i ON e.invoice_id = i.id 
    $where_sql 
    ORDER BY e.expense_date DESC, e.id DESC
");

// Metrics for filter tabs and stats cards
$cnt_res = $conn->query("
    SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(amount), 0) as total_sum,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_sum,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) as approved_count,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_sum,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_count,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END), 0) as rejected_sum
    FROM teacher_expenses
");
$metrics = $cnt_res ? $cnt_res->fetch_assoc() : [];
$total_count = (int)($metrics['total_count'] ?? 0);
$pending_count = (int)($metrics['pending_count'] ?? 0);
$approved_count = (int)($metrics['approved_count'] ?? 0);
$rejected_count = (int)($metrics['rejected_count'] ?? 0);

$teachers = $conn->query("SELECT id, name, department FROM teachers WHERE status = 'active' ORDER BY name ASC");
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
    <title>Teacher Expenses | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; flex-wrap: wrap; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 20px 0; }
        .modal-content { background: #fff; padding: 40px; border-radius: 32px; width: 100%; max-width: 620px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); margin: auto; }
        
        .portal-table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 20px;
        }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; min-width: 780px; }
        th { text-align: left; padding: 14px 20px; color: var(--portal-blue); font-weight: 800; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { padding: 14px 20px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; vertical-align: middle; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 16px 0 0 16px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 16px 16px 0; }
        .btn-glass { background: #f8faff; color: var(--portal-blue); border: 2px solid #eef2ff; padding: 14px 24px; border-radius: 16px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-glass:hover { background: #eef2ff; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 12px; font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; }
        .status-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-approved { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .status-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        /* User-Friendly Action Buttons */
        .expense-action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
        }
        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 800;
            text-decoration: none;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            line-height: 1.2;
            font-family: inherit;
        }
        .btn-act:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }
        .btn-act-icon {
            width: 34px;
            height: 34px;
            padding: 0;
            justify-content: center;
            border-radius: 10px;
            font-size: 0.88rem;
        }
        
        .btn-act-approve {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .btn-act-approve:hover {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        .btn-act-reject {
            background: #fff1f2;
            color: #be123c;
            border-color: #fecdd3;
        }
        .btn-act-reject:hover {
            background: #e11d48;
            color: #ffffff;
            border-color: #e11d48;
        }

        .btn-act-reapprove {
            background: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }
        .btn-act-reapprove:hover {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
        }

        .btn-act-edit {
            background: #f5f3ff;
            color: #6d28d9;
            border-color: #ddd6fe;
        }
        .btn-act-edit:hover {
            background: #7c3aed;
            color: #ffffff;
            border-color: #7c3aed;
        }

        .btn-act-del {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .btn-act-del:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        .btn-act-revert {
            background: #f8fafc;
            color: #475569;
            border-color: #e2e8f0;
        }
        .btn-act-revert:hover {
            background: #475569;
            color: #ffffff;
            border-color: #475569;
        }

        .filter-tab-bar {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 14px;
            gap: 4px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .filter-tab.active {
            background: #ffffff;
            color: var(--portal-blue);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .tab-badge {
            background: #e2e8f0;
            color: #475569;
            font-size: 0.72rem;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 800;
        }
        .filter-tab.active .tab-badge {
            background: #eef2ff;
            color: var(--portal-blue);
        }

        @media (max-width: 768px) {
            .modal-content {
                padding: 26px 20px !important;
                border-radius: 20px !important;
            }
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .action-bar .btn-portal {
                width: 100%;
                justify-content: center;
            }
            .filter-tab-bar {
                width: 100%;
            }
            .filter-tab {
                flex: 1;
                justify-content: center;
                text-align: center;
                padding: 8px 10px;
                font-size: 0.78rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--portal-dark); margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-hand-holding-usd" style="color: var(--portal-blue);"></i> Staff & Teacher Expense Management
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Track, verify, and approve advance cash claims & deductions for school staff and teachers.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Log Expense Claim
            </button>
        </div>

        <!-- Metric Summary Cards -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 25px;">
            <div style="background:#fff; padding:18px 22px; border-radius:18px; border:1px solid #f1f5f9; box-shadow:0 4px 15px rgba(0,21,113,0.03);">
                <div style="font-size:0.78rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Total Claims Logged</div>
                <div style="font-size:1.6rem; font-weight:800; color:var(--portal-dark); margin-top:4px;">₹<?php echo number_format((float)($metrics['total_sum'] ?? 0), 2); ?></div>
                <small style="color:#94a3b8; font-weight:700;"><?php echo $total_count; ?> total expense entries</small>
            </div>
            <div style="background:#fff; padding:18px 22px; border-radius:18px; border:1.5px solid #fed7aa; box-shadow:0 4px 15px rgba(249,115,22,0.04);">
                <div style="font-size:0.78rem; font-weight:800; color:#c2410c; text-transform:uppercase; letter-spacing:0.04em;"><i class="fas fa-clock"></i> Pending Approval</div>
                <div style="font-size:1.6rem; font-weight:800; color:#ea580c; margin-top:4px;">₹<?php echo number_format((float)($metrics['pending_sum'] ?? 0), 2); ?></div>
                <small style="color:#ea580c; font-weight:700;"><?php echo $pending_count; ?> claims awaiting admin review</small>
            </div>
            <div style="background:#fff; padding:18px 22px; border-radius:18px; border:1.5px solid #bbf7d0; box-shadow:0 4px 15px rgba(34,197,94,0.04);">
                <div style="font-size:0.78rem; font-weight:800; color:#15803d; text-transform:uppercase; letter-spacing:0.04em;"><i class="fas fa-check-circle"></i> Approved & Deducted</div>
                <div style="font-size:1.6rem; font-weight:800; color:#16a34a; margin-top:4px;">₹<?php echo number_format((float)($metrics['approved_sum'] ?? 0), 2); ?></div>
                <small style="color:#15803d; font-weight:700;"><?php echo $approved_count; ?> claims approved / deducted</small>
            </div>
        </div>

        <!-- Filter Tab Bar & Staff Dropdown -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
            <div class="filter-tab-bar">
                <a href="teacher_expenses.php?status=all<?php echo $teacher_filter ? '&teacher_id='.$teacher_filter : ''; ?>" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i> All Claims <span class="tab-badge"><?php echo $total_count; ?></span>
                </a>
                <a href="teacher_expenses.php?status=pending<?php echo $teacher_filter ? '&teacher_id='.$teacher_filter : ''; ?>" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending <span class="tab-badge" style="background:#ffedd5; color:#c2410c;"><?php echo $pending_count; ?></span>
                </a>
                <a href="teacher_expenses.php?status=approved<?php echo $teacher_filter ? '&teacher_id='.$teacher_filter : ''; ?>" class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Approved <span class="tab-badge" style="background:#dcfce7; color:#15803d;"><?php echo $approved_count; ?></span>
                </a>
                <a href="teacher_expenses.php?status=rejected<?php echo $teacher_filter ? '&teacher_id='.$teacher_filter : ''; ?>" class="filter-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Rejected <span class="tab-badge" style="background:#fee2e2; color:#b91c1c;"><?php echo $rejected_count; ?></span>
                </a>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:0.85rem; font-weight:700; color:#64748b; white-space:nowrap;"><i class="fas fa-user"></i> Staff Member:</label>
                <select onchange="location.href='teacher_expenses.php?status=<?php echo urlencode($status_filter); ?>' + (this.value ? '&teacher_id=' + this.value : '')" style="padding:8px 14px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:0.85rem; font-weight:700; color:#1e293b; background:#fff; outline:none; cursor:pointer;">
                    <option value="">All Staff & Teachers</option>
                    <?php foreach($teachers_array as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo $teacher_filter === (int)$t['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['department'] ?? 'General'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Staff / Teacher</th>
                        <th>Expense Type & Description</th>
                        <th>Claim Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($expenses && $expenses->num_rows > 0): while($row = $expenses->fetch_assoc()): ?>
                    <tr>
                        <td style="white-space:nowrap;">
                            <i class="fas fa-calendar-alt" style="color:var(--portal-blue); margin-right:5px;"></i>
                            <?php echo date('d M Y', strtotime($row['expense_date'])); ?>
                        </td>
                        <td>
                            <strong style="color:var(--portal-blue); display:block;"><?php echo htmlspecialchars($row['teacher_name']); ?></strong>
                            <?php if(!empty($row['teacher_department'])): ?>
                                <small style="color:#64748b; font-weight:700;"><i class="fas fa-building" style="font-size:0.75rem;"></i> <?php echo htmlspecialchars($row['teacher_department']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($row['expense_type']); ?></span>
                            <?php if(!empty($row['description'])): ?>
                                <br><small style="color:#64748b;"><?php echo htmlspecialchars(strlen($row['description']) > 45 ? substr($row['description'],0,45).'...' : $row['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="color:#2e7d32; font-weight:800; font-size:1.05rem; white-space:nowrap;">₹<?php echo number_format($row['amount'], 2); ?></td>
                        <td>
                            <?php 
                            $st = $row['status'];
                            $icon = ($st === 'approved') ? 'fa-check' : (($st === 'pending') ? 'fa-clock' : 'fa-times');
                            ?>
                            <span class="status-badge status-<?php echo $st; ?>">
                                <i class="fas <?php echo $icon; ?>"></i> <?php echo ucfirst($st); ?>
                            </span>
                        </td>
                        <td>
                            <div class="expense-action-buttons">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="?approve=<?php echo $row['id']; ?>" class="btn-act btn-act-approve" onclick="return confirm('Approve expense claim of ₹<?php echo number_format($row['amount'],2); ?> for <?php echo addslashes($row['teacher_name']); ?>?\n\nIt will be deducted from their monthly salary invoice.');" title="Approve & Deduct from Salary">
                                        <i class="fas fa-check-circle"></i> Approve
                                    </a>
                                    <a href="?reject=<?php echo $row['id']; ?>" class="btn-act btn-act-reject" onclick="return confirm('Reject expense claim of ₹<?php echo number_format($row['amount'],2); ?> for <?php echo addslashes($row['teacher_name']); ?>?');" title="Reject Claim">
                                        <i class="fas fa-times-circle"></i> Reject
                                    </a>
                                <?php elseif ($row['status'] === 'rejected'): ?>
                                    <a href="?approve=<?php echo $row['id']; ?>" class="btn-act btn-act-reapprove" onclick="return confirm('Re-approve this previously rejected expense of ₹<?php echo number_format($row['amount'],2); ?>?');" title="Re-approve Expense">
                                        <i class="fas fa-redo"></i> Re-approve
                                    </a>
                                <?php elseif ($row['status'] === 'approved'): ?>
                                    <?php if (!empty($row['invoice_number'])): ?>
                                        <a href="print_teacher_invoice.php?id=<?php echo $row['invoice_id']; ?>" target="_blank" class="btn-act" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:0.75rem;" title="View Invoice where deduction was applied">
                                            <i class="fas fa-file-invoice"></i> <?php echo htmlspecialchars($row['invoice_number']); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="?reject=<?php echo $row['id']; ?>" class="btn-act btn-act-revert" onclick="return confirm('Revoke approval for this claim of ₹<?php echo number_format($row['amount'],2); ?>?');" title="Revoke Approval">
                                            <i class="fas fa-undo"></i> Revoke
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <button type="button" class="btn-act btn-act-icon btn-act-edit" onclick='editExpense(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)' title="Edit Expense Details">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-act btn-act-icon btn-act-del" onclick="return confirm('Are you sure you want to delete this expense entry? If approved, deductions will be reverted.')" title="Delete Expense">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:35px; color:#94a3b8; font-weight:700;">No expense records found matching criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="expenseModal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #f1f5f9; padding-bottom:14px;">
                    <div>
                        <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.5rem; margin:0;" id="expenseModalTitle">Log Expense Claim</h2>
                        <small style="color: #64748b; font-weight: 600;">Record staff advance, travel, or teaching expense deduction</small>
                    </div>
                    <button type="button" onclick="hideModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <form action="" method="POST" id="expenseForm">
                    <input type="hidden" name="id" id="expense_id">

                    <div class="portal-input-group">
                        <label>Staff / Teacher Member <span style="color:red">*</span></label>
                        <select name="teacher_id" id="teacher_id" required>
                            <option value="">-- Select Staff / Teacher --</option>
                            <?php foreach($teachers_array as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['department'] ?? 'General'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Expense Type <span style="color:red">*</span></label>
                            <input type="text" name="expense_type" id="expense_type" list="expense_type_suggestions" placeholder="e.g. Advance Salary, Travel" required>
                            <datalist id="expense_type_suggestions">
                                <option value="Advance Salary">
                                <option value="Cash Advance">
                                <option value="Travel & Conveyance">
                                <option value="Food & Mess Advance">
                                <option value="Books & Study Material">
                                <option value="Classroom Supplies">
                                <option value="Medical & Health Expense">
                                <option value="Event / Festival Advance">
                                <option value="Emergency Advance">
                            </datalist>
                        </div>
                        <div class="portal-input-group">
                            <label>Amount (₹) <span style="color:red">*</span></label>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" placeholder="0.00" required style="font-weight: 800; color: #166534; font-size: 1.1rem;">
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Expense Date <span style="color:red">*</span></label>
                            <input type="date" name="expense_date" id="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Approval Status</label>
                            <select name="status" id="status">
                                <option value="pending">Pending (Review)</option>
                                <option value="approved">Approved (Deduct from Salary)</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Description / Reason</label>
                        <textarea name="description" id="description" rows="3" placeholder="Provide reason or itemized details of the advance/claim..."></textarea>
                    </div>

                    <div class="portal-btn-row" style="margin-top:30px;">
                        <button type="submit" name="save_expense" class="btn-portal w-100" style="padding:16px; font-weight:800;">
                            <i class="fas fa-save"></i> Save Expense Entry
                        </button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('expenseModal').style.display = 'flex';
            document.getElementById('expenseModalTitle').textContent = 'Log Expense Claim';
            document.getElementById('expense_id').value = '';
            document.querySelector('#expenseModal form').reset();
            document.getElementById('expense_date').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('status').value = 'pending';
        }

        function hideModal() {
            document.getElementById('expenseModal').style.display = 'none';
        }

        function editExpense(data) {
            document.getElementById('expenseModal').style.display = 'flex';
            document.getElementById('expenseModalTitle').textContent = 'Edit Expense Claim #' + data.id;
            document.getElementById('expense_id').value = data.id;
            document.getElementById('teacher_id').value = data.teacher_id;
            document.getElementById('expense_type').value = data.expense_type;
            document.getElementById('amount').value = data.amount;
            document.getElementById('expense_date').value = data.expense_date;
            document.getElementById('description').value = data.description || '';
            document.getElementById('status').value = data.status;
        }
    </script>
</body>
</html>
