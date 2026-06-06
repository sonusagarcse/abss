<?php
require_once 'includes/auth.php';

// Handle Add/Edit Invoice
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_invoice'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $amount = (float)$_POST['amount'];
    $issue_date = trim($_POST['issue_date']);
    $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
    $status = trim($_POST['status']);
    $month_for = isset($_POST['month_for']) ? trim($_POST['month_for']) : date('Y-m');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $invoice_number = isset($_POST['invoice_number']) ? trim($_POST['invoice_number']) : '';

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE teacher_invoices SET teacher_id=?, invoice_number=?, amount=?, month_for=?, issue_date=?, due_date=?, status=? WHERE id=?");
        $stmt->bind_param("isdssssi", $teacher_id, $invoice_number, $amount, $month_for, $issue_date, $due_date, $status, $id);
        $stmt->execute();
    } else {
        $invoice_number = 'TINV-' . date('Ymd') . '-' . rand(1000, 9999);
        $check = $conn->query("SELECT id FROM teacher_invoices WHERE invoice_number = '$invoice_number'");
        while ($check && $check->num_rows > 0) {
            $invoice_number = 'TINV-' . date('Ymd') . '-' . rand(1000, 9999);
            $check = $conn->query("SELECT id FROM teacher_invoices WHERE invoice_number = '$invoice_number'");
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

        $stmt = $conn->prepare("INSERT INTO teacher_invoices (teacher_id, invoice_number, amount, month_for, issue_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdssss", $teacher_id, $invoice_number, $amount, $month_for, $issue_date, $due_date, $status);
        if ($stmt->execute()) {
            $new_invoice_id = $stmt->insert_id;
            // Link expenses
            $conn->query("UPDATE teacher_expenses SET invoice_id = $new_invoice_id WHERE teacher_id = $teacher_id AND status = 'approved' AND invoice_id IS NULL");
        }
    }
    
    header("Location: teacher_invoices.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teacher_invoices WHERE id = $id");
    header("Location: teacher_invoices.php");
    exit();
}

$invoices = $conn->query("SELECT i.*, t.name as teacher_name FROM teacher_invoices i JOIN teachers t ON i.teacher_id = t.id ORDER BY i.issue_date DESC");
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1>Invoice Generation</h1>
                <p>Generate and manage invoices for teachers.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Create Invoice
            </button>
        </div>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Teacher</th>
                        <th>Month</th>
                        <th>Amount</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($invoices && $invoices->num_rows > 0): while($row = $invoices->fetch_assoc()): ?>
                    <tr>
                        <td><strong style="color:var(--portal-blue);"><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                        <td><?php echo !empty($row['month_for']) ? date('M Y', strtotime($row['month_for'].'-01')) : '-'; ?></td>
                        <td style="color:#2e7d32; font-weight:800;">₹<?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['issue_date'])); ?></td>
                        <td><?php echo !empty($row['due_date']) ? date('d M Y', strtotime($row['due_date'])) : '-'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td>
                            <a href="print_teacher_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" target="_blank" style="border:none; color:#2e7d32;" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue);" onclick='editInvoice(<?php echo json_encode($row); ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f;" onclick="return confirm('Are you sure?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" style="text-align:center;">No invoices found.</td></tr>
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
    </main>

    <script>
        function showModal() {
            document.getElementById('invoiceModal').style.display = 'flex';
            document.getElementById('invoice_id').value = '';
            document.querySelector('#invoiceModal form').reset();
            document.getElementById('issue_date').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('invoice_number').value = '';
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
            document.getElementById('month_for').value = data.month_for || '<?php echo date('Y-m'); ?>';
            document.getElementById('issue_date').value = data.issue_date;
            document.getElementById('due_date').value = data.due_date || '';
            document.getElementById('status').value = data.status;
        }
    </script>
</body>
</html>
