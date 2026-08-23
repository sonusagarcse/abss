<?php
require_once 'includes/auth.php';

$teacher_id = (int)$_SESSION['teacher_id'];

// Fetch teacher base info
$teacher_res = $conn->query("SELECT salary FROM teachers WHERE id = $teacher_id")->fetch_assoc();
$base_salary = (float)($teacher_res['salary'] ?? 0);

// Fetch stats
$total_paid = (float)($conn->query("SELECT SUM(amount) as s FROM teacher_invoices WHERE teacher_id = $teacher_id AND status = 'paid'")->fetch_assoc()['s'] ?? 0);
$total_invoices_cnt = (int)($conn->query("SELECT COUNT(id) as c FROM teacher_invoices WHERE teacher_id = $teacher_id")->fetch_assoc()['c'] ?? 0);

// Fetch invoices for this teacher
$invoices = $conn->query("SELECT * FROM teacher_invoices WHERE teacher_id = $teacher_id ORDER BY issue_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Invoices | ABSS Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .btn-print-slip {
            background: rgba(124, 58, 237, 0.1);
            color: var(--teacher-purple);
            border: 1px solid rgba(124, 58, 237, 0.3);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.82rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.25s;
        }
        .btn-print-slip:hover {
            background: var(--teacher-purple);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 25px;">
            <h1 style="font-size: 1.85rem; color: var(--teacher-dark); font-weight: 900; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-file-invoice-dollar" style="color: var(--teacher-purple);"></i> Faculty Salary Invoices & Slips
            </h1>
            <p style="color: #64748b; margin-top: 4px; font-size: 0.95rem;">View monthly issued salary statements, breakdown slips, and payout records.</p>
        </header>

        <!-- Salary Stats Ribbon -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="stat-lbl">Issued Slips</div>
                    <div class="stat-val"><?= number_format($total_invoices_cnt) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-hand-holding-usd"></i></div>
                <div>
                    <div class="stat-lbl">Total Paid Out</div>
                    <div class="stat-val">₹<?= number_format($total_paid, 2) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="stat-lbl">Base Monthly Salary</div>
                    <div class="stat-val">₹<?= number_format($base_salary, 2) ?></div>
                </div>
            </div>
        </div>

        <div class="page-card" style="padding: 26px;">
            <h3 style="color: var(--teacher-dark); margin-bottom: 20px; font-size: 1.15rem; font-weight: 900; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-list-alt" style="color: var(--teacher-purple);"></i> Issued Salary Statements
            </h3>

            <div class="portal-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Ref</th>
                            <th>Month / Period</th>
                            <th>Issue Date</th>
                            <th>Net Amount</th>
                            <th>Payment Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($invoices && $invoices->num_rows > 0): ?>
                            <?php while ($inv = $invoices->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color: var(--teacher-purple);">#INV-<?= $inv['id'] ?></strong></td>
                                    <td style="font-weight: 800; color: #1e1b4b;"><?= htmlspecialchars($inv['month_for'] ?? date('F Y', strtotime($inv['issue_date']))) ?></td>
                                    <td><i class="far fa-calendar-alt" style="color: #64748b; font-size: 0.8rem;"></i> <?= date('M d, Y', strtotime($inv['issue_date'])) ?></td>
                                    <td style="font-weight: 900; font-size: 1.05rem; color: #059669;">₹<?= number_format($inv['amount'], 2) ?></td>
                                    <td>
                                        <?php if ($inv['status'] === 'paid'): ?>
                                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Paid</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i class="fas fa-clock"></i> Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td align="right">
                                        <a href="print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="btn-print-slip">
                                            <i class="fas fa-print"></i> Print Slip &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 40px; font-weight: 600;">No salary invoices issued yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
