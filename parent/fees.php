<?php
// parent/fees.php - Mobile-Friendly Parent Portal Fees Ledger
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

$selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (!empty($children) ? $children[0]['id'] : 0);
$selected_child = null;
foreach ($children as $c) {
    if ($c['id'] == $selected_child_id) {
        $selected_child = $c;
        break;
    }
}
if (!$selected_child && !empty($children)) {
    $selected_child = $children[0];
}

$settings = getAllSettings();
$razorpay_key_id = $settings['razorpay_key_id'] ?? '';
$tuition_modes = [];
if (!empty($settings['tuition_modes'])) {
    $tuition_modes = json_decode($settings['tuition_modes'], true);
} else {
    $tuition_modes = ['Day Scholar' => 3000, 'Hostler' => 5000, 'Tuition' => 1500];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Ledger & Payments | ABSS Parent Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .child-fee-card { 
            margin-bottom: 40px; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            border: 1px solid #e2e8f0; 
        }

        .student-header-banner {
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 20px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            flex-wrap: wrap; 
            gap: 15px;
        }

        .fee-stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 18px; 
            margin-bottom: 30px; 
        }

        .fee-mini-card { 
            background: #f8fafc; 
            border-radius: 16px; 
            padding: 20px; 
            border: 1px solid #e2e8f0; 
            display: flex; 
            align-items: center; 
            gap: 16px; 
        }
        .fee-mini-card.paid { border-color: #bbf7d0; background: #f0fdf4; }
        .fee-mini-card.dues { border-color: #fecaca; background: #fef2f2; }
        
        .fee-mini-icon { 
            width: 48px; 
            height: 48px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.2rem; 
            flex-shrink: 0;
        }
        
        .amount-tag { 
            background: #f0fdf4; 
            color: #166534; 
            padding: 6px 14px; 
            border-radius: 10px; 
            font-weight: 800; 
            font-size: 0.9rem; 
            display: inline-block; 
        }

        .btn-receipt { 
            background: #f1f5f9; 
            color: #334155; 
            border: 1px solid #cbd5e1; 
            padding: 8px 14px; 
            border-radius: 10px; 
            font-weight: 700; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            font-size: 0.82rem; 
            transition: all 0.25s ease; 
        }
        .btn-receipt:hover { 
            background: var(--portal-blue); 
            color: #ffffff; 
            border-color: var(--portal-blue); 
            transform: translateY(-1px); 
        }

        .btn-pay-action {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff !important;
            border-color: #059669;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }
        .btn-pay-action:hover {
            background: linear-gradient(135deg, #047857, #065f46);
            border-color: #047857;
        }

        /* Mobile Responsive View Styling */
        @media (max-width: 640px) {
            .child-fee-card { padding: 20px 15px; border-radius: 18px; }
            .student-header-banner { flex-direction: column; align-items: flex-start; gap: 8px; }
            .fee-stats { grid-template-columns: 1fr; gap: 12px; }
            .fee-mini-card { padding: 15px; border-radius: 12px; }
            
            /* Hide desktop table and show touch-friendly cards on mobile */
            .desktop-table-view { display: none !important; }
            .mobile-card-list { display: block !important; }
        }

        @media (min-width: 641px) {
            .mobile-card-list { display: none !important; }
            .desktop-table-view { display: block !important; }
        }

        .mobile-invoice-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 30px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Dues & Fee Ledger</h1>
            <p style="margin:0;">View student fee invoices, pay online via Razorpay, and download official receipts.</p>
        </header>

        <?php if(isset($_GET['success'])): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> Online payment successfully processed! Your fee invoice has been marked as PAID.
            </div>
        <?php endif; ?>

        <?php if (empty($children)): ?>
            <div class="portal-card" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-users-slash" style="font-size: 3.5rem; color: #94a3b8; margin-bottom: 20px;"></i>
                <h2>No Students Linked</h2>
                <p>Please contact the school office to link your student accounts to your parent portal.</p>
            </div>
        <?php else: ?>
            
            <?php if (count($children) > 1): ?>
                <div class="portal-card" style="margin-bottom: 25px; background: #eef2ff; border: 1px solid #c7d2fe;">
                    <form action="" method="GET" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <label style="font-weight: 800; color: var(--portal-dark);"><i class="fas fa-user-graduate"></i> Select Student:</label>
                        <select name="child_id" class="portal-input" style="max-width: 280px; margin: 0; padding:8px 12px; border-radius:10px;" onchange="this.form.submit()">
                            <?php foreach ($children as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $selected_child['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']) . ' (' . htmlspecialchars($c['class_admitted']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            <?php endif; ?>

            <?php 
                $child = $selected_child;
                $sid = (int)$child['id'];
                
                $scholar_mode = isset($child['scholar_mode']) && $child['scholar_mode'] ? $child['scholar_mode'] : 'Day Scholar';
                $monthly_standard = (float)($child['base_fee'] > 0 ? ($child['base_fee'] - $child['monthly_discount']) : ($tuition_modes[$scholar_mode] ?? 1500));

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
                    <!-- Student Info Header -->
                    <div class="student-header-banner">
                        <div>
                            <h2 style="margin:0; font-size:1.4rem; color:var(--portal-dark); display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <i class="fas fa-user-graduate" style="color:var(--portal-blue);"></i>
                                <?php echo htmlspecialchars($child['name']); ?>
                                <span style="font-size:0.8rem; font-weight:800; color:#2563eb; background:#eff6ff; padding:4px 12px; border-radius:50px;">Mode: <?php echo htmlspecialchars($scholar_mode); ?></span>
                            </h2>
                        </div>
                        <span style="font-weight:700; color:#64748b; font-size:0.88rem;"><i class="fas fa-school"></i> Class: <?php echo htmlspecialchars($child['class_admitted'] ? $child['class_admitted'] : 'N/A'); ?></span>
                    </div>

                    <!-- Fee Ledger Metric Cards -->
                    <div class="fee-stats">
                        <div class="fee-mini-card">
                            <div class="fee-mini-icon" style="background:#eef2ff; color:#2563eb;"><i class="fas fa-calculator"></i></div>
                            <div class="stat-info">
                                <h3 style="margin:0; font-size:1.3rem;">₹ <?php echo number_format($monthly_standard, 2); ?></h3>
                                <span style="font-size:0.75rem; color:#64748b; font-weight:800; text-transform:uppercase;">Net Fee / Month</span>
                            </div>
                        </div>
                        
                        <div class="fee-mini-card paid">
                            <div class="fee-mini-icon" style="background:#dcfce7; color:#15803d;"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-info">
                                <h3 style="margin:0; font-size:1.3rem; color:#15803d;">₹ <?php echo number_format($total_paid, 2); ?></h3>
                                <span style="font-size:0.75rem; color:#15803d; font-weight:800; text-transform:uppercase;">Total Payments Settled</span>
                            </div>
                        </div>

                        <div class="fee-mini-card dues">
                            <div class="fee-mini-icon" style="background:#fee2e2; color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-info">
                                <h3 style="margin:0; font-size:1.3rem; color:#dc2626;">₹ <?php echo number_format($outstanding_dues, 2); ?></h3>
                                <span style="font-size:0.75rem; color:#dc2626; font-weight:800; text-transform:uppercase;">Pending Outstanding Dues</span>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 1: UNPAID INVOICES -->
                    <h3 style="font-size:1.1rem; margin-bottom:15px; color:#dc2626; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-file-invoice-dollar"></i> Outstanding Fee Invoices
                    </h3>

                    <!-- Desktop View Table -->
                    <div class="desktop-table-view portal-table-container" style="margin-bottom: 35px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Billing Month</th>
                                    <th>Amount Due</th>
                                    <th>Billing Date</th>
                                    <th>Remarks</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($unpaid_bills)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#15803d; padding:25px; font-weight: 700;">
                                            <i class="fas fa-check-circle"></i> No pending dues! All invoices for this student are fully settled.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($unpaid_bills as $bill): ?>
                                        <tr>
                                            <td style="color:var(--portal-dark); font-weight:800;"><?php echo htmlspecialchars($bill['month_for']); ?></td>
                                            <td><span class="amount-tag" style="background:#fee2e2; color:#dc2626;">₹ <?php echo number_format($bill['amount'], 2); ?></span></td>
                                            <td><?php echo date('d M, Y', strtotime($bill['billing_date'])); ?></td>
                                            <td style="font-size:0.85rem; color:#475569; max-width:250px;"><?php echo htmlspecialchars($bill['remark']); ?></td>
                                            <td style="text-align:right;">
                                                <a href="view_bill.php?id=<?php echo $bill['id']; ?>" class="btn-receipt btn-pay-action">
                                                    <i class="fas fa-credit-card"></i> View & Pay Invoice
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View Touch Cards for Unpaid Invoices -->
                    <div class="mobile-card-list" style="margin-bottom: 35px;">
                        <?php if (empty($unpaid_bills)): ?>
                            <div style="background:#f0fdf4; color:#15803d; padding:20px; border-radius:12px; text-align:center; font-weight:700; border:1px solid #bbf7d0;">
                                <i class="fas fa-check-circle"></i> No pending dues! All invoices settled.
                            </div>
                        <?php else: ?>
                            <?php foreach ($unpaid_bills as $bill): ?>
                                <div class="mobile-invoice-card" style="border-left:4px solid #dc2626;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <strong style="font-size:1rem; color:var(--portal-dark);"><?php echo htmlspecialchars($bill['month_for']); ?></strong>
                                        <span class="amount-tag" style="background:#fee2e2; color:#dc2626;">₹ <?php echo number_format($bill['amount'], 2); ?></span>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b; margin-bottom:12px;">
                                        Inv #<?php echo $bill['id']; ?> • Billed: <?php echo date('d M, Y', strtotime($bill['billing_date'])); ?>
                                    </div>
                                    <a href="view_bill.php?id=<?php echo $bill['id']; ?>" class="btn-receipt btn-pay-action" style="width:100%; justify-content:center; padding:10px;">
                                        <i class="fas fa-credit-card"></i> View & Pay Invoice (₹<?php echo number_format($bill['amount'], 2); ?>)
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- SECTION 2: PAYMENT HISTORY -->
                    <h3 style="font-size:1.1rem; margin-bottom:15px; color:var(--portal-dark); display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-history" style="color:var(--portal-blue);"></i> Payment Settlement History
                    </h3>

                    <!-- Desktop View Table -->
                    <div class="desktop-table-view portal-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment Month</th>
                                    <th>Amount Paid</th>
                                    <th>Transaction Date</th>
                                    <th>Method</th>
                                    <th style="text-align:right;">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#94a3b8; padding:25px;">No payment records found for this student.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td style="color:var(--portal-dark); font-weight:800;"><?php echo htmlspecialchars($pay['month_for']); ?></td>
                                            <td><span class="amount-tag">₹ <?php echo number_format($pay['amount'], 2); ?></span></td>
                                            <td><?php echo date('d M, Y', strtotime($pay['payment_date'])); ?></td>
                                            <td style="font-weight: 700; color: #475569;"><i class="fas fa-wallet" style="margin-right:6px; color:#64748b;"></i> <?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                            <td style="text-align:right;">
                                                <a href="receipt.php?id=<?php echo $pay['id']; ?>" target="_blank" class="btn-receipt">
                                                    <i class="fas fa-file-pdf"></i> Download Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View Touch Cards for Payment History -->
                    <div class="mobile-card-list">
                        <?php if (empty($payments)): ?>
                            <div style="background:#f8fafc; color:#94a3b8; padding:20px; border-radius:12px; text-align:center;">
                                No payment records found.
                            </div>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <div class="mobile-invoice-card" style="border-left:4px solid #15803d;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <strong style="font-size:1rem; color:var(--portal-dark);"><?php echo htmlspecialchars($pay['month_for']); ?></strong>
                                        <span class="amount-tag">₹ <?php echo number_format($pay['amount'], 2); ?></span>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b; margin-bottom:12px;">
                                        Paid: <?php echo date('d M, Y', strtotime($pay['payment_date'])); ?> • <?php echo htmlspecialchars($pay['payment_method']); ?>
                                    </div>
                                    <a href="receipt.php?id=<?php echo $pay['id']; ?>" target="_blank" class="btn-receipt" style="width:100%; justify-content:center; padding:10px;">
                                        <i class="fas fa-file-pdf"></i> Download Official Receipt
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div> <!-- End of child-fee-card -->
            
        <?php endif; ?>

    </main>
</body>
</html>
