<?php
require_once 'includes/auth.php';

$teacher_id = (int)$_SESSION['teacher_id'];

// Fetch invoices for this teacher
$invoices = $conn->query("SELECT * FROM teacher_invoices WHERE teacher_id = $teacher_id ORDER BY issue_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Invoices | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .page-card { background: #ffffff; border-radius: 20px; padding: 28px; border: 1px solid #ede9fe; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #ede9fe; font-size: 0.9rem; }
        th { background: #f5f3ff; color: var(--teacher-dark); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .btn-sm { padding: 6px 14px; font-size: 0.8rem; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-outline { border: 2px solid var(--teacher-purple); color: var(--teacher-purple); }
        .btn-outline:hover { background: var(--teacher-purple); color: white; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <h1 style="font-size: 1.8rem; color: var(--teacher-dark); font-weight: 800;">My Salary Invoices</h1>
            <p style="color: #64748b; margin-top: 4px;">View issued monthly salary statements and payment records.</p>
        </header>

        <div class="page-card" style="overflow-x: auto;">
            <h3 style="color: var(--teacher-dark); margin-bottom: 18px; font-size: 1.1rem;"><i class="fas fa-file-invoice-dollar"></i> Issued Salary Slips</h3>
            <table>
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Month / Period</th>
                        <th>Issue Date</th>
                        <th>Net Amount</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($invoices && $invoices->num_rows > 0): ?>
                        <?php while ($inv = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#INV-<?= $inv['id'] ?></strong></td>
                                <td style="font-weight: 700; color: #1e1b4b;"><?= htmlspecialchars($inv['month_for'] ?? date('F Y', strtotime($inv['issue_date']))) ?></td>
                                <td><?= date('M d, Y', strtotime($inv['issue_date'])) ?></td>
                                <td style="font-weight: 800; font-size: 1rem; color: #059669;">₹<?= number_format($inv['amount'], 2) ?></td>
                                <td>
                                    <?php if ($inv['status'] === 'paid'): ?>
                                        <span class="badge badge-paid"><i class="fas fa-check-circle"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-unpaid"><i class="fas fa-clock"></i> Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="btn-sm btn-outline">
                                        <i class="fas fa-print"></i> Print Slip
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No salary invoices issued yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
