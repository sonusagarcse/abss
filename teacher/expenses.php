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

// Fetch stats for this teacher
$total_claimed = (float)($conn->query("SELECT SUM(amount) as s FROM teacher_expenses WHERE teacher_id = $teacher_id")->fetch_assoc()['s'] ?? 0);
$approved_claimed = (float)($conn->query("SELECT SUM(amount) as s FROM teacher_expenses WHERE teacher_id = $teacher_id AND status = 'approved'")->fetch_assoc()['s'] ?? 0);
$pending_claimed = (float)($conn->query("SELECT SUM(amount) as s FROM teacher_expenses WHERE teacher_id = $teacher_id AND status = 'pending'")->fetch_assoc()['s'] ?? 0);

// Fetch claims for this teacher only
$my_claims = $conn->query("SELECT * FROM teacher_expenses WHERE teacher_id = $teacher_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Claims | ABSS Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .grid-layout { display: grid; grid-template-columns: 360px 1fr; gap: 24px; margin-top: 20px; }
        @media (max-width: 992px) { .grid-layout { grid-template-columns: 1fr; } }
        textarea.form-control { min-height: 85px; resize: vertical; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 25px;">
            <h1 style="font-size: 1.85rem; color: var(--teacher-dark); font-weight: 900; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-receipt" style="color: var(--teacher-purple);"></i> Faculty Expense Claims
            </h1>
            <p style="color: #64748b; margin-top: 4px; font-size: 0.95rem;">Submit official expenditures and track reimbursement approval statuses.</p>
        </header>

        <?php if ($msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: 700; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem; color: #22c55e;"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem; color: #ef4444;"></i>
                <span><?= htmlspecialchars($err) ?></span>
            </div>
        <?php endif; ?>

        <!-- Expense Claims KPI Stats Ribbon -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="stat-lbl">Total Claimed</div>
                    <div class="stat-val">₹<?= number_format($total_claimed, 2) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="stat-lbl">Approved Amount</div>
                    <div class="stat-val">₹<?= number_format($approved_claimed, 2) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-amber"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="stat-lbl">Pending Review</div>
                    <div class="stat-val">₹<?= number_format($pending_claimed, 2) ?></div>
                </div>
            </div>
        </div>

        <div class="grid-layout">
            <!-- Submit Claim Form -->
            <div class="page-card">
                <h3 style="color: var(--teacher-dark); margin-bottom: 20px; font-size: 1.15rem; font-weight: 900; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus-circle" style="color: var(--teacher-purple);"></i> File New Claim
                </h3>
                <form action="expenses.php" method="POST">
                    <div class="form-group">
                        <label for="expense_type"><i class="fas fa-tag"></i> Expense Category / Type *</label>
                        <input type="text" id="expense_type" name="expense_type" class="form-control" placeholder="e.g. Teaching Supplies, Lab Materials, Travel" required>
                    </div>

                    <div class="form-group">
                        <label for="amount"><label for="amount"><i class="fas fa-rupee-sign"></i> Amount (₹) *</label></label>
                        <input type="number" step="0.01" id="amount" name="amount" class="form-control" placeholder="500.00" required>
                    </div>

                    <div class="form-group">
                        <label for="expense_date"><i class="fas fa-calendar-alt"></i> Date of Expenditure *</label>
                        <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Details / Purpose</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Specify itemized expenditure purpose..."></textarea>
                    </div>

                    <button type="submit" name="submit_claim" class="btn-purple" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-paper-plane"></i> Submit Claim for Review
                    </button>
                </form>
            </div>

            <!-- Claims History -->
            <div class="page-card" style="padding: 24px;">
                <h3 style="color: var(--teacher-dark); margin-bottom: 20px; font-size: 1.15rem; font-weight: 900; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-history" style="color: var(--teacher-purple);"></i> Claim History Ledger
                </h3>
                
                <div class="portal-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Category</th>
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
                                        <td><strong style="color: var(--teacher-purple);">#<?= $c['id'] ?></strong></td>
                                        <td style="font-weight: 800; color: #1e1b4b;"><?= htmlspecialchars($c['expense_type']) ?></td>
                                        <td style="font-weight: 900; color: #1e1b4b;">₹<?= number_format($c['amount'], 2) ?></td>
                                        <td><i class="far fa-calendar-alt" style="color: #64748b; font-size: 0.8rem;"></i> <?= date('M d, Y', strtotime($c['expense_date'])) ?></td>
                                        <td style="color: #64748b; font-size: 0.85rem; max-width: 250px; font-weight: 600;"><?= htmlspecialchars($c['description'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($c['status'] === 'approved'): ?>
                                                <span class="badge badge-approved"><i class="fas fa-check"></i> Approved</span>
                                            <?php elseif ($c['status'] === 'rejected'): ?>
                                                <span class="badge badge-rejected"><i class="fas fa-times"></i> Rejected</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending Review</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 35px; font-weight: 600;">No expense claims filed yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
