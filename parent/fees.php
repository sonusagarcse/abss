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
    <!-- Google Font: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
    <?php include 'includes/head_css.php'; ?>
    <style>
        body, input, select, textarea, button, h1, h2, h3, h4, h5, h6, p, span:not(.fas):not(.far):not(.fab):not(.fa), a, table, th, td, summary, details, label, strong, small {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .fa, .fas, .far, .fal, .fad, .fab, i[class*="fa-"], span[class*="fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 5 Free", FontAwesome !important;
        }

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

        details > summary::-webkit-details-marker { display: none; }
        details > summary { list-style: none; }
        details[open] summary .fa-chevron-down { transform: rotate(180deg); }
        details summary .fa-chevron-down { transition: transform 0.2s ease; }
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
                $security_amount = (float)($child['security_amount'] ?? 0);
                $registration_fee = (float)($child['registration_fee'] ?? 0);
                $admission_fee = (float)($child['admission_fee'] ?? 0);
                $advance_submitted = (float)($child['advance_amount'] ?? 0);

                // Fetch student addons assigned from admin panel
                $addons_list = [];
                $addons_query = $conn->query("SELECT * FROM student_addons WHERE student_id = $sid ORDER BY id ASC");
                if ($addons_query) {
                    while ($ad = $addons_query->fetch_assoc()) {
                        $addons_list[] = $ad;
                    }
                }

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
                $total_bill_fines = 0;
                $bills_query = $conn->query("
                    SELECT * FROM fees_generated 
                    WHERE student_id = $sid 
                    ORDER BY billing_date DESC
                ");
                if ($bills_query) {
                    while ($b = $bills_query->fetch_assoc()) {
                        if ($b['status'] === 'unpaid') {
                            $fine_info = function_exists('calculate_bill_fine') ? calculate_bill_fine($b['billing_date'], $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0];
                            $b['fine_amount'] = (float)$fine_info['fine_amount'];
                            $b['overdue_days'] = (int)$fine_info['overdue_days'];
                            $b['total_payable'] = (float)$b['amount'] + $b['fine_amount'];
                            $total_bill_fines += $b['fine_amount'];
                            $outstanding_dues += $b['total_payable'];
                            $unpaid_bills[] = $b;
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

                    <!-- FEE DETAILS COLLAPSIBLE DROPDOWN -->
                    <details style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 22px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <summary style="padding: 13px 18px; font-size: 0.88rem; font-weight: 800; color: #334155; cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none; background: #f8fafc; list-style: none;">
                            <span style="display: flex; align-items: center; gap: 9px;">
                                <i class="fas fa-list-alt" style="color: #64748b; font-size: 0.95rem;"></i>
                                <span>Fee Details</span>
                            </span>
                            <span style="font-size: 0.76rem; color: #64748b; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                                View Details <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                            </span>
                        </summary>
                        
                        <div style="padding: 16px 20px; border-top: 1px solid #e2e8f0; background: #ffffff;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px 24px; font-size: 0.85rem; color: #475569;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                    <span>Admission Fee:</span>
                                    <strong style="color: #0f172a;">₹ <?php echo number_format($admission_fee, 2); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                    <span>Registration Fee:</span>
                                    <strong style="color: #0f172a;">₹ <?php echo number_format($registration_fee, 2); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                    <span>Monthly Tuition Fee:</span>
                                    <strong style="color: #0f172a;">₹ <?php echo number_format($monthly_standard, 2); ?> / month</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                    <span>Security Money:</span>
                                    <strong style="color: #0f172a;">₹ <?php echo number_format($security_amount, 2); ?></strong>
                                </div>
                                <?php if ($advance_submitted > 0): ?>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                    <span>Advance / Credit:</span>
                                    <strong style="color: #0f172a;">₹ <?php echo number_format($advance_submitted, 2); ?></strong>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($addons_list)): ?>
                                    <?php foreach ($addons_list as $ad): ?>
                                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                            <span>Add-on (<?php echo htmlspecialchars($ad['addon_name']); ?>):</span>
                                            <strong style="color: #0f172a;">₹ <?php echo number_format((float)$ad['amount'], 2); ?> / month</strong>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #f1f5f9; padding-bottom: 6px;">
                                        <span>Add-ons (Milk / Special):</span>
                                        <strong style="color: #64748b;">None</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>

                    <!-- Fee Ledger Metric Cards (Exactly 2 Cards: Recent Payment & Total Due Amount) -->
                    <?php 
                    $recent_payment = !empty($payments) ? $payments[0] : null; 
                    ?>
                    <div class="fee-stats" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 30px;">
                        <!-- Card 1: Recent Payment with last date -->
                        <div class="fee-mini-card paid" style="border-left: 4px solid #16a34a; background: #f0fdf4; border-color: #bbf7d0;">
                            <div class="fee-mini-icon" style="background:#dcfce7; color:#15803d;"><i class="fas fa-receipt"></i></div>
                            <div class="stat-info">
                                <span style="font-size:0.75rem; color:#15803d; font-weight:800; text-transform:uppercase;">Recent Payment</span>
                                <?php if ($recent_payment): ?>
                                    <h3 style="margin:2px 0 0; font-size:1.45rem; color:#15803d; font-weight:800;">₹ <?php echo number_format($recent_payment['amount'], 2); ?></h3>
                                    <small style="color:#166534; font-weight:700; font-size:0.78rem; display:block; margin-top:3px;">
                                        Paid on: <?php echo date('d M, Y', strtotime($recent_payment['payment_date'])); ?> (<?php echo htmlspecialchars($recent_payment['month_for']); ?>)
                                    </small>
                                <?php else: ?>
                                    <h3 style="margin:2px 0 0; font-size:1.3rem; color:#64748b; font-weight:700;">₹ 0.00</h3>
                                    <small style="color:#64748b; font-weight:600; font-size:0.78rem; display:block; margin-top:3px;">No payment recorded yet</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card 2: Total Due Amount -->
                        <div class="fee-mini-card dues" style="border-left: 4px solid #dc2626; background: #fef2f2; border-color: #fecaca;">
                            <div class="fee-mini-icon" style="background:#fee2e2; color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-info" style="width: 100%;">
                                <span style="font-size:0.75rem; color:#dc2626; font-weight:800; text-transform:uppercase;">Total Due Amount</span>
                                <h3 style="margin:2px 0 0; font-size:1.45rem; color:#dc2626; font-weight:800;">₹ <?php echo number_format($outstanding_dues, 2); ?></h3>
                                <?php if ($outstanding_dues > 0 && !empty($unpaid_bills)): ?>
                                    <small style="color:#991b1b; font-weight:700; font-size:0.78rem; display:block; margin-top:3px;">
                                        <?php echo count($unpaid_bills); ?> Pending Invoice(s)
                                        <?php if ($total_bill_fines > 0): ?>
                                            • <span style="color:#ea580c;">(Includes ₹ <?php echo number_format($total_bill_fines, 2); ?> Late Fine)</span>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <small style="color:#15803d; font-weight:700; font-size:0.78rem; display:block; margin-top:3px;">
                                        <i class="fas fa-check-circle"></i> All Dues Cleared
                                    </small>
                                <?php endif; ?>
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
                                    <th>Base Fee</th>
                                    <th>Late Fine (₹5/day)</th>
                                    <th>Total Payable</th>
                                    <th>Billing Date</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($unpaid_bills)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; color:#15803d; padding:25px; font-weight: 700;">
                                            <i class="fas fa-check-circle"></i> No pending dues! All invoices for this student are fully settled.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($unpaid_bills as $bill): ?>
                                        <tr>
                                            <td style="color:var(--portal-dark); font-weight:800;"><?php echo htmlspecialchars($bill['month_for']); ?></td>
                                            <td><span class="amount-tag" style="background:#f1f5f9; color:#334155;">₹ <?php echo number_format($bill['amount'], 2); ?></span></td>
                                            <td>
                                                <?php if ($bill['fine_amount'] > 0): ?>
                                                    <span class="amount-tag" style="background:#ffedd5; color:#ea580c; font-weight:800;">
                                                        + ₹ <?php echo number_format($bill['fine_amount'], 2); ?>
                                                        <small style="font-size:0.7rem; font-weight:700;">(<?php echo $bill['overdue_days']; ?>d)</small>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color:#15803d; font-weight:700; font-size:0.82rem;">₹ 0.00 (Grace Period)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong style="color:#dc2626; font-size:1rem;">₹ <?php echo number_format($bill['total_payable'], 2); ?></strong>
                                            </td>
                                            <td><?php echo date('d M, Y', strtotime($bill['billing_date'])); ?></td>
                                            <td style="text-align:right;">
                                                <a href="view_bill.php?id=<?php echo $bill['id']; ?>" class="btn-receipt btn-pay-action">
                                                    <i class="fas fa-credit-card"></i> Pay ₹<?php echo number_format($bill['total_payable'], 2); ?>
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
                                        <span class="amount-tag" style="background:#fee2e2; color:#dc2626; font-weight:800;">₹ <?php echo number_format($bill['total_payable'], 2); ?></span>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b; margin-bottom:8px;">
                                        Base Fee: ₹<?php echo number_format($bill['amount'], 2); ?>
                                        <?php if ($bill['fine_amount'] > 0): ?>
                                            • <span style="color:#ea580c; font-weight:700;">+₹<?php echo number_format($bill['fine_amount'], 2); ?> Fine (<?php echo $bill['overdue_days']; ?> days)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:12px;">
                                        Inv #<?php echo $bill['id']; ?> • Billed: <?php echo date('d M, Y', strtotime($bill['billing_date'])); ?>
                                    </div>
                                    <a href="view_bill.php?id=<?php echo $bill['id']; ?>" class="btn-receipt btn-pay-action" style="width:100%; justify-content:center; padding:10px;">
                                        <i class="fas fa-credit-card"></i> Pay Now (₹<?php echo number_format($bill['total_payable'], 2); ?>)
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
