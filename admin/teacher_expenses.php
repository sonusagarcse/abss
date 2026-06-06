<?php
require_once 'includes/auth.php';

// Handle Add/Edit Expense
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_expense'])) {
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
    } else {
        $stmt = $conn->prepare("INSERT INTO teacher_expenses (teacher_id, expense_type, amount, expense_date, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $teacher_id, $expense_type, $amount, $expense_date, $description, $status);
        $stmt->execute();
    }
    
    header("Location: teacher_expenses.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teacher_expenses WHERE id = $id");
    header("Location: teacher_expenses.php");
    exit();
}

$expenses = $conn->query("SELECT e.*, t.name as teacher_name FROM teacher_expenses e JOIN teachers t ON e.teacher_id = t.id ORDER BY e.expense_date DESC");
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
    <title>Teacher Expenses | ABSS Portal</title>
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
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-approved { background: #f0fdf4; color: #166534; }
        .status-rejected { background: #fef2f2; color: #b91c1c; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1>Expense Management</h1>
                <p>Track and approve expenses submitted by teachers.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Log Expense
            </button>
        </div>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Teacher</th>
                        <th>Expense Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($expenses && $expenses->num_rows > 0): while($row = $expenses->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($row['expense_date'])); ?></td>
                        <td><strong style="color:var(--portal-blue);"><?php echo htmlspecialchars($row['teacher_name']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($row['expense_type']); ?>
                            <?php if(!empty($row['description'])): ?>
                                <br><small style="color:#9aa5ce;"><?php echo htmlspecialchars(strlen($row['description']) > 30 ? substr($row['description'],0,30).'...' : $row['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="color:#2e7d32; font-weight:800;">₹<?php echo number_format($row['amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue);" onclick='editExpense(<?php echo json_encode($row); ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f;" onclick="return confirm('Are you sure?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center;">No expenses found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="expenseModal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.8rem; margin:0;">Expense Entry</h2>
                    <button type="button" onclick="hideModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <form action="" method="POST" id="expenseForm">
                    <input type="hidden" name="id" id="expense_id">

                    <div class="portal-input-group">
                        <label>Teacher <span style="color:red">*</span></label>
                        <select name="teacher_id" id="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach($teachers_array as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Expense Type <span style="color:red">*</span></label>
                            <input type="text" name="expense_type" id="expense_type" placeholder="e.g. Travel, Supplies" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Amount (₹) <span style="color:red">*</span></label>
                            <input type="number" name="amount" id="amount" step="0.01" required>
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Date <span style="color:red">*</span></label>
                            <input type="date" name="expense_date" id="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Status</label>
                            <select name="status" id="status">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Provide details of the expense..."></textarea>
                    </div>

                    <div class="portal-btn-row" style="margin-top:35px;">
                        <button type="submit" name="save_expense" class="btn-portal w-100" style="padding:18px;">Save Expense</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('expenseModal').style.display = 'flex';
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
