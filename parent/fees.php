<?php
// parent/fees.php - Parent Portal Fees Ledger

require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];

// 1. Fetch children
$children_query = $conn->prepare("SELECT * FROM students WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$children_query->bind_param("i", $pid);
$children_query->execute();
$children_res = $children_query->get_result();
$children = [];
while ($c = $children_res->fetch_assoc()) {
    $children[] = $c;
}

$settings = getAllSettings();
$day_fee = isset($settings['day_fee']) ? (float)$settings['day_fee'] : 3000.00;
$res_fee = isset($settings['res_fee']) ? (float)$settings['res_fee'] : 5000.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Ledger | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .child-fee-card { margin-bottom: 50px; background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); border: 1px solid #f0f4f8; }
        .fee-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .fee-mini-card { background: #f8faff; border-radius: 20px; padding: 25px; border: 1px solid #eef2ff; display: flex; align-items: center; gap: 20px; }
        .fee-mini-card.paid { border-color: #e8f5e9; background: #f9fbf9; }
        .fee-mini-card.dues { border-color: #feeef2; background: #fffdfd; }
        .fee-mini-icon { width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        
        .amount-tag { background: #f0fdf4; color: #166534; padding: 8px 18px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; display: inline-block; }
        .btn-receipt { background: #f0f4f8; color: var(--portal-indigo); border: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; transition: 0.3s; }
        .btn-receipt:hover { background: var(--portal-purple); color: #fff; transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Dues & Fees Ledger</h1>
            <p>Review standard monthly tuition matrices, pending outstanding invoices, paid fee transactions, and download official receipts.</p>
        </header>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 80px 40px;">
                <i class="fas fa-users-slash" style="font-size: 4rem; color: #9aa5ce; margin-bottom: 30px;"></i>
                <h2>No Children Associated</h2>
                <p>Please contact the school office to link your student accounts.</p>
            </div>
        <?php else: ?>
            <?php foreach ($children as $child): ?>
                <?php 
                $sid = (int)$child['id'];
                
                $scholar_mode = isset($child['scholar_mode']) ? $child['scholar_mode'] : 'Day Scholar';
                $monthly_standard = ($scholar_mode === 'Hostler') ? $res_fee : $day_fee;

                // Fetch payment ledger
                $payments = [];
                $pay_query = $conn->query("
                    SELECT * FROM fee_payments 
                    WHERE student_id = $sid 
                    ORDER BY payment_date DESC
                ");
                $total_paid = 0;
                if ($pay_query) {
                    while ($p = $pay_query->fetch_assoc()) {
                        $payments[] = $p;
                        $total_paid += (float)$p['amount'];
                    }
                }

                // Fetch generated bills ledger
                $unpaid_bills = [];
                $outstanding_dues = 0;
                $bills_query = $conn->query("
                    SELECT * FROM fees_generated 
                    WHERE student_id = $sid 
                    ORDER BY billing_date DESC
                ");
                if ($bills_query) {
                    while ($b = $bills_query->fetch_assoc()) {
                        if ($b['status'] === 'unpaid') {
                            $unpaid_bills[] = $b;
                            $outstanding_dues += (float)$b['amount'];
                        }
                    }
                }
                ?>
                
                <div class="child-fee-card">
                    <div style="border-bottom: 2px solid #f0f4f8; padding-bottom: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                        <h2 style="margin:0; font-size:1.6rem; color:var(--portal-indigo); display:flex; align-items:center; gap:12px;">
                            <i class="fas fa-wallet" style="color:var(--portal-purple);"></i>
                            <?php echo htmlspecialchars($child['name']); ?>
                            <span style="font-size:0.85rem; font-weight:700; color:#5c6bc0; background:#f0f4f8; padding:5px 15px; border-radius:100px;">Mode: <?php echo htmlspecialchars($scholar_mode); ?></span>
                        </h2>
                        <span style="font-weight:700; color:#9aa5ce; font-size:0.9rem;">Admission Class: <?php echo htmlspecialchars($child['class_admitted']); ?></span>
                    </div>

                    <!-- Fee ledger counters -->
                    <div class="fee-stats">
                        <div class="fee-mini-card">
                            <div class="fee-mini-icon" style="background:#eef2ff; color:var(--portal-purple);"><i class="fas fa-coins"></i></div>
                            <div class="stat-info">
                                <h3 style="margin:0; font-size:1.4rem;">₹ <?php echo number_format($monthly_standard, 2); ?></h3>
                                <span style="font-size:0.75rem; color:#9aa5ce; font-weight:700; text-transform:uppercase;">Standard Tuition / Month</span>
                            </div>
                        </div>
                        <div class="fee-mini-card paid">
                            <div class="fee-mini-icon" style="background:#e8f5e9; color:#2e7d32;"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-info">
                                <h3 style="margin:0; font-size:1.4rem; color:#2e7d32;">₹ <?php echo number_format($total_paid, 2); ?></h3>
                                <span style="font-size:0.75rem; color:#9aa5ce; font-weight:700; text-transform:uppercase;">Total Payments Completed</span>
                            </div>
                        </div>
                        <div class="fee-mini-card dues">
                            <div class="fee-mini-icon" style="background:#feeef2; color:#d32f2f;"><i class="fas fa-exclamation-circle"></i></div>
                            <div class="stat-info">
                                <h3 style="margin:0; font-size:1.4rem; color:#d32f2f;">₹ <?php echo number_format($outstanding_dues, 2); ?></h3>
                                <span style="font-size:0.75rem; color:#9aa5ce; font-weight:700; text-transform:uppercase;">Outstanding Dues</span>
                            </div>
                        </div>
                    </div>

                    <!-- Unpaid Invoices Log -->
                    <h3 style="font-size:1.1rem; margin-bottom:15px; color:#d32f2f;"><i class="fas fa-file-invoice-dollar" style="margin-right:8px; opacity:0.7;"></i> Outstanding Invoices (Unpaid Fees)</h3>
                    <div class="portal-table-container" style="margin-bottom: 40px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Billing Month</th>
                                    <th>Amount Due</th>
                                    <th>Billing Date</th>
                                    <th>Remarks / Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($unpaid_bills)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#2e7d32; padding:30px; font-weight: 700;"><i class="fas fa-glass-cheers"></i> No pending dues! All invoices are fully settled.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($unpaid_bills as $bill): ?>
                                        <tr>
                                            <td style="color:var(--portal-indigo); font-weight:800;"><?php echo htmlspecialchars($bill['month_for']); ?></td>
                                            <td><span class="amount-tag" style="background:#feeef2; color:#d32f2f;">₹ <?php echo number_format($bill['amount'], 2); ?></span></td>
                                            <td><?php echo date('d F, Y', strtotime($bill['billing_date'])); ?></td>
                                            <td style="font-weight: 600; color: #5c6bc0;"><?php echo htmlspecialchars($bill['remark'] ? $bill['remark'] : 'Tuition Fee Billed'); ?></td>
                                            <td><span class="badge badge-danger">Unpaid</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payments Log -->
                    <h3 style="font-size:1.1rem; margin-bottom:15px; color:var(--portal-indigo);"><i class="fas fa-history" style="margin-right:8px; opacity:0.7;"></i> Recorded Payment Ledger</h3>
                    <div class="portal-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment Month</th>
                                    <th>Amount Received</th>
                                    <th>Transaction Date</th>
                                    <th>Payment Method</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#9aa5ce; padding:30px;">No payments recorded for this child in current term.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td style="color:var(--portal-indigo); font-weight:800;"><?php echo htmlspecialchars($pay['month_for']); ?></td>
                                            <td><span class="amount-tag">₹ <?php echo number_format($pay['amount'], 2); ?></span></td>
                                            <td><?php echo date('d F, Y', strtotime($pay['payment_date'])); ?></td>
                                            <td style="font-weight: 700; color: #5c6bc0;"><i class="fas fa-credit-card" style="margin-right:8px; opacity:0.5;"></i> <?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                            <td>
                                                <a href="receipt?id=<?php echo $pay['id']; ?>" target="_blank" class="btn-receipt">
                                                    <i class="fas fa-file-pdf"></i> View & Print
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
