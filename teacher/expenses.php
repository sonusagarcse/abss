<?php
require_once 'includes/auth.php';

$teacher_id = (int)$_SESSION['teacher_id'];
$msg = '';
$err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_claim'])) {
    $expense_type = trim($_POST['expense_type']);
    $amount = (float)$_POST['amount'];
    $expense_date = trim($_POST['expense_date']);
    $description = trim($_POST['description']);

    if (!empty($expense_type) && $amount > 0 && !empty($expense_date)) {
        $status = 'pending';
        $stmt = $conn->prepare("INSERT INTO teacher_expenses (teacher_id, expense_type, amount, expense_date, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $teacher_id, $expense_type, $amount, $expense_date, $description, $status);
        if ($stmt->execute()) {
            $msg = "Expense claim submitted successfully for admin review.";
        } else {
            $err = "Error submitting expense claim.";
        }
    } else {
        $err = "Please enter valid expense type, amount, and date.";
    }
}

// Fetch claims for this teacher only
$my_claims = $conn->query("SELECT * FROM teacher_expenses WHERE teacher_id = $teacher_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Claims | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .grid-layout { display: grid; grid-template-columns: 350px 1fr; gap: 24px; margin-top: 20px; }
        @media (max-width: 992px) { .grid-layout { grid-template-columns: 1fr; } }
        .page-card { background: #ffffff; border-radius: 20px; padding: 28px; border: 1px solid #ede9fe; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 10px 14px; border: 2px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; outline: none; box-sizing: border-box; }
        .form-control:focus { border-color: var(--teacher-purple); }
        textarea.form-control { min-height: 80px; resize: vertical; }
        .btn-purple { width: 100%; background: var(--teacher-purple); color: white; padding: 12px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-purple:hover { background: var(--teacher-dark); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #ede9fe; font-size: 0.9rem; }
        th { background: #f5f3ff; color: var(--teacher-dark); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <h1 style="font-size: 1.8rem; color: var(--teacher-dark); font-weight: 800;">My Expense Claims</h1>
            <p style="color: #64748b; margin-top: 4px;">Submit and track reimbursement claims.</p>
        </header>

        <?php if ($msg): ?>
            <div style="background:#dcfce7; color:#166534; padding:15px 25px; border-radius:14px; margin-top:20px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:15px 25px; border-radius:14px; margin-top:20px; font-weight:700; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <div class="grid-layout">
            <!-- Submit Claim Form -->
            <div class="page-card">
                <h3 style="color: var(--teacher-dark); margin-bottom: 18px; font-size: 1.1rem;"><i class="fas fa-plus-circle"></i> File New Claim</h3>
                <form action="expenses.php" method="POST">
                    <div class="form-group">
                        <label for="expense_type">Expense Type *</label>
                        <input type="text" id="expense_type" name="expense_type" class="form-control" placeholder="e.g. Teaching Supplies, Travel" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount (₹) *</label>
                        <input type="number" step="0.01" id="amount" name="amount" class="form-control" placeholder="500.00" required>
                    </div>

                    <div class="form-group">
                        <label for="expense_date">Date *</label>
                        <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description / Remark</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Describe the expenditure details"></textarea>
                    </div>

                    <button type="submit" name="submit_claim" class="btn-purple"><i class="fas fa-paper-plane"></i> Submit Claim</button>
                </form>
            </div>

            <!-- Claims History -->
            <div class="page-card" style="overflow-x: auto;">
                <h3 style="color: var(--teacher-dark); margin-bottom: 18px; font-size: 1.1rem;"><i class="fas fa-history"></i> My Claim History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($my_claims && $my_claims->num_rows > 0): ?>
                            <?php while ($c = $my_claims->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $c['id'] ?></strong></td>
                                    <td style="font-weight: 700; color: #1e1b4b;"><?= htmlspecialchars($c['expense_type']) ?></td>
                                    <td style="font-weight: 700;">₹<?= number_format($c['amount'], 2) ?></td>
                                    <td><?= date('M d, Y', strtotime($c['expense_date'])) ?></td>
                                    <td style="color: #64748b; font-size: 0.85rem; max-width: 250px;"><?= htmlspecialchars($c['description'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($c['status'] === 'approved'): ?>
                                            <span class="badge badge-approved">Approved</span>
                                        <?php elseif ($c['status'] === 'rejected'): ?>
                                            <span class="badge badge-rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No expense claims filed yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
