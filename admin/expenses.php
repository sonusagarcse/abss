<?php
// admin/expenses.php - Manage Daily Ad-hoc Student Expenses
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $student_id = (int)$_POST['student_id'];
    $item_name = trim($_POST['item_name']);
    $amount = (float)$_POST['amount'];
    $date = trim($_POST['expense_date']);
    
    if ($student_id > 0 && !empty($item_name) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO student_expenses (student_id, item_name, amount, expense_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $student_id, $item_name, $amount, $date);
        if ($stmt->execute()) {
            $msg = "Expense logged successfully.";
            
            $force_student_id = $student_id;
            ob_start();
            require __DIR__ . '/includes/billing_engine.php';
            ob_end_clean();
        } else {
            $err = "Error logging expense.";
        }
    } else {
        $err = "Please provide valid expense details.";
    }
}

// Handle Delete Unbilled Expense
if (isset($_GET['delete'])) {
    $exp_id = (int)$_GET['delete'];
    // Fetch student_id first to trigger rebuild
    $chk = $conn->query("SELECT student_id FROM student_expenses WHERE id = $exp_id AND status = 'unbilled'");
    if ($chk && $chk->num_rows > 0) {
        $st_row = $chk->fetch_assoc();
        $student_id = $st_row['student_id'];
        
        $del = $conn->prepare("DELETE FROM student_expenses WHERE id = ? AND status = 'unbilled'");
        $del->bind_param("i", $exp_id);
        if ($del->execute() && $conn->affected_rows > 0) {
            $msg = "Expense removed.";
            
            $force_student_id = $student_id;
            ob_start();
            require __DIR__ . '/includes/billing_engine.php';
            ob_end_clean();
        } else {
            $err = "Cannot delete this expense (it may have already been billed).";
        }
    } else {
        $err = "Cannot delete this expense (it may have already been billed).";
    }
}

// Fetch Students for Dropdown
$students_res = $conn->query("SELECT id, name, reg_no FROM students WHERE status = 'active' ORDER BY name ASC");
$students = [];
while($s = $students_res->fetch_assoc()) {
    $students[] = $s;
}

// Fetch Expenses
$expenses_res = $conn->query("
    SELECT e.*, s.name, s.reg_no 
    FROM student_expenses e 
    JOIN students s ON e.student_id = s.id 
    ORDER BY e.created_at DESC 
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Expenses | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-unbilled { background: #fff3e0; color: #e65100; }
        .status-billed { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <h1>Daily Student Expenses</h1>
            <p style="margin:0;">Log ad-hoc expenses (e.g. pens, books). They will be automatically added to the student's next monthly bill.</p>
        </div>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#feeef2; color:#d32f2f; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <div class="form-grid">
            <div class="portal-card" style="align-self: start;">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-cart-plus"></i> Log New Expense</h3>
                <form action="expenses.php" method="POST">
                    <div class="portal-input-group">
                        <label>Student</label>
                        <select name="student_id" required>
                            <option value="">Select Student...</option>
                            <?php foreach($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name'] . ' (' . $s['reg_no'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Item / Description</label>
                        <input type="text" name="item_name" placeholder="e.g., 1 Packet Pen, Drawing Book" required>
                    </div>
                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Amount (₹)</label>
                            <input type="number" name="amount" placeholder="e.g. 50" step="0.01" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Date</label>
                            <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="add_expense" class="btn-portal w-100"><i class="fas fa-plus"></i> Add Expense</button>
                </form>
            </div>

            <div class="portal-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Item</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($expenses_res->num_rows > 0): ?>
                            <?php while($row = $expenses_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['expense_date'])); ?></td>
                                <td><strong style="color:var(--portal-blue);"><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><strong style="color:#d32f2f;">₹<?php echo number_format($row['amount'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                                <td>
                                    <?php if($row['status'] == 'unbilled'): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f; padding:5px 10px; background:#feeef2; border-radius:8px;" onclick="return confirm('Delete this unbilled expense?');"><i class="fas fa-trash"></i></a>
                                    <?php else: ?>
                                        <small style="color:#9aa5ce;"><i class="fas fa-lock"></i></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No expenses logged yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
