<?php
// admin/view_bill.php - Detailed Fee Invoice View
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$bill_id = (int)$_GET['id'];

// Fetch bill details
$stmt = $conn->prepare("
    SELECT fg.*, s.name as student_name, p.parent_name, p.phone
    FROM fees_generated fg
    JOIN students s ON fg.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE fg.id = ?
");
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>Invoice not found, already paid, or unauthorized access.</p><a href='fees.php'>Back to Fees Ledger</a></div>");
}

$settings = getAllSettings();
$school_name = $settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan';
$school_address = $settings['address'] ?? 'Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206';
$school_phone = $settings['phone'] ?? '+91 9523012888';
$school_email = $settings['email'] ?? 'abssimamganj@gmail.com';

// Function to convert amount to words
function amountToWords($number) {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two',
        3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
        7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );
    $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter].$plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10].' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . ($paise ? 'and ' . $paise : '') . 'Only';
}

$amount_in_words = amountToWords($bill['amount']);
$invoice_no = "ABSS-INV-" . date('Y', strtotime($bill['billing_date'])) . "-" . str_pad($bill['id'], 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Invoice - <?php echo $invoice_no; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #525659; margin: 0; padding: 30px 0; -webkit-print-color-adjust: exact; }
        
        /* Control Bar styling */
        .control-bar { max-width: 800px; margin: 0 auto 20px; background: #fff; padding: 15px 30px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-control { text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-family: inherit; transition: 0.3s; }
        .btn-back { background: #f0f4f8; color: #1a237e; }
        .btn-back:hover { background: #e2ebf0; }

        /* Receipt Canvas */
        .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); box-sizing: border-box; position: relative; overflow: hidden; border: 1px solid #dcdcdc; }
        
        /* Subtle Watermark logo background */
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 8rem; color: rgba(211, 47, 47, 0.05); font-weight: 800; pointer-events: none; text-align: center; width: 120%; z-index: 1; user-select: none; border: 15px double rgba(211, 47, 47, 0.05); padding: 20px; }

        .receipt-header { display: flex; justify-content: space-between; border-bottom: 3px double #e0e0e0; padding-bottom: 30px; margin-bottom: 35px; position: relative; z-index: 2; }
        .school-branding { display: flex; align-items: center; gap: 20px; }
        .school-branding img { height: 75px; }
        .school-info h2 { margin: 0 0 5px 0; color: #1a237e; font-size: 1.6rem; font-weight: 800; }
        .school-info p { margin: 0; color: #555; font-size: 0.85rem; line-height: 1.4; font-weight: 500; }
        
        .receipt-meta { text-align: right; }
        .receipt-title { font-size: 1.3rem; font-weight: 800; color: #d32f2f; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
        .receipt-no { font-family: monospace; font-size: 1rem; font-weight: 700; color: #333; margin-bottom: 5px; }
        .receipt-date { font-size: 0.85rem; color: #666; font-weight: 600; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; position: relative; z-index: 2; }
        .details-col h4 { margin: 0 0 12px 0; color: #1a237e; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; padding-bottom: 5px; letter-spacing: 0.05em; }
        
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 6px 0; font-size: 0.9rem; border: none; background: transparent; }
        .kv-label { color: #666; font-weight: 500; width: 35%; }
        .kv-value { color: #111; font-weight: 700; }

        /* Itemized table */
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; position: relative; z-index: 2; }
        .item-table th { background: #feeef2; color: #d32f2f; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; padding: 8px 12px; border-top: 1px solid #d32f2f; border-bottom: 2px solid #d32f2f; }
        .item-table td { padding: 10px 15px; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; color: #333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-strip { background: #feeef2; padding: 12px 25px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; position: relative; z-index: 2; border: 1px solid #ffcdd2; }
        .total-label { font-size: 1.05rem; font-weight: 800; color: #b71c1c; }
        .total-value { font-size: 1.3rem; font-weight: 800; color: #b71c1c; }

        .words-block { font-size: 0.85rem; color: #555; margin-bottom: 50px; font-style: italic; border-left: 3px solid #d32f2f; padding-left: 15px; position: relative; z-index: 2; }
        .words-block strong { color: #d32f2f; font-style: normal; font-weight: 700; }

        /* Print Media queries */
        @media print {
            body { background: #fff; padding: 0; }
            .control-bar { display: none; }
            .receipt-container { box-shadow: none; border: none; padding: 10px; max-width: 100%; }
            .watermark { color: rgba(211, 47, 47, 0.05); }
        }
    </style>
</head>
<body>

    <!-- Control Panel -->
    <div class="control-bar">
        <div style="display:flex; gap: 10px;">
            <a href="fees.php" class="btn-control btn-back"><i class="fas fa-chevron-left"></i> Back to Ledger</a>
            <button onclick="window.print()" class="btn-control btn-back"><i class="fas fa-print"></i> Print / Download PDF</button>
        </div>
    </div>

    <!-- Printable Invoice Container -->
    <div class="receipt-container">
        
        <!-- Watermark -->
        <div class="watermark">
            <?php echo strtoupper($bill['status']); ?><br>INVOICE
        </div>
        
        <!-- Header -->
        <div class="receipt-header">
            <div class="school-branding">
                <img src="../assets/logo.png" alt="ABSS School Logo">
                <div class="school-info">
                    <h2><?php echo htmlspecialchars($school_name); ?></h2>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($school_address); ?></p>
                    <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($school_phone); ?> | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($school_email); ?></p>
                </div>
            </div>
            <div class="receipt-meta">
                <div class="receipt-title">FEE INVOICE</div>
                <div class="receipt-no"><?php echo $invoice_no; ?></div>
                <div class="receipt-date">Billed On: <strong><?php echo date('d M, Y', strtotime($bill['billing_date'])); ?></strong></div>
            </div>
        </div>

        <!-- Payer & Student Details -->
        <div class="details-grid">
            <div class="details-col">
                <h4>Bill To</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Parent Name:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($bill['parent_name'] ? $bill['parent_name'] : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Phone:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($bill['phone'] ? $bill['phone'] : 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="details-col">
                <h4>Student Information</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Student Name:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($bill['student_name']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Ledger itemization -->
        <table class="item-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 8%;">S.No</th>
                    <th>Fee Description</th>
                    <th>Billing Cycle</th>
                    <th class="text-right" style="width: 25%;">Amount Due</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $remarks = explode('|', $bill['remark'] ? $bill['remark'] : 'Monthly Tuition Fee');
                
                $monthly_items = [];
                $expense_items = [];
                $payment_items = [];
                $expense_subtotal = 0;
                $payment_subtotal = 0;
                
                foreach ($remarks as $rem) {
                    $rem = trim($rem);
                    if (strpos($rem, 'Auto-generated Bill.') !== false) {
                        $rem = trim(str_replace('Auto-generated Bill.', '', $rem));
                    }
                    if (empty($rem)) continue;
                    
                    $rem = str_replace('Auto-generated Bill. Base Fee', 'Monthly Fee', $rem);
                    $rem = str_replace('Base Fee', 'Monthly Fee', $rem);
                    
                    if (strpos(strtolower($rem), 'payment received') !== false) {
                        $payment_items[] = $rem;
                        if (preg_match('/-₹([0-9\.,]+)/', $rem, $matches)) {
                            $payment_subtotal += (float)str_replace(',', '', $matches[1]);
                        }
                    } elseif (strpos($rem, '(Expense):') !== false) {
                        $rem = str_replace('(Expense):', ':', $rem);
                        $expense_items[] = $rem;
                        
                        if (strpos($rem, ': ₹') !== false) {
                            $parts = explode(': ₹', $rem);
                            $expense_subtotal += (float)str_replace(',', '', trim($parts[1]));
                        }
                    } else {
                        $monthly_items[] = $rem;
                    }
                }
                
                // Monthly subtotal before partial payments
                $monthly_subtotal = max(0, $bill['amount'] - $expense_subtotal + $payment_subtotal);
                $sno = 1;
                ?>

                <!-- Section 1: Monthly Fee -->
                <tr>
                    <td colspan="4" style="background:#f8fafc; color:#1a237e; font-weight:800; font-size:0.85rem; text-transform:uppercase;">Monthly Tuition & Fees</td>
                </tr>
                <?php 
                foreach ($monthly_items as $rem):
                    $item_desc = $rem;
                    $item_amt = '-';
                    if (strpos($rem, ': ₹') !== false) {
                        $parts = explode(': ₹', $rem);
                        $item_desc = trim($parts[0]);
                        $item_amt = '₹ ' . trim($parts[1]);
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $sno++; ?></td>
                    <td style="font-weight: 700; color: #1a237e;">
                        <?php echo htmlspecialchars($item_desc); ?>
                    </td>
                    <td><?php echo htmlspecialchars($bill['month_for']); ?></td>
                    <td class="text-right" style="font-weight: 700; color:#d32f2f;"><?php echo htmlspecialchars($item_amt); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2" style="border:none;"></td>
                    <td style="font-weight: 700; border:none; text-align:right;">Monthly Subtotal:</td>
                    <td class="text-right" style="border:none; font-weight: 700; color:#d32f2f;">₹ <?php echo number_format($monthly_subtotal, 2); ?></td>
                </tr>

                <!-- Section 2: Other Fees/Dues -->
                <?php if (!empty($expense_items)): ?>
                <tr>
                    <td colspan="4" style="background:#f8fafc; color:#1a237e; font-weight:800; font-size:0.85rem; text-transform:uppercase; border-top: 2px solid #e2e8f0;">Other Fees & Dues</td>
                </tr>
                <?php 
                foreach ($expense_items as $rem):
                    $item_desc = $rem;
                    $item_amt = '-';
                    if (strpos($rem, ': ₹') !== false) {
                        $parts = explode(': ₹', $rem);
                        $item_desc = trim($parts[0]);
                        $item_amt = '₹ ' . trim($parts[1]);
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $sno++; ?></td>
                    <td style="font-weight: 700; color: #1a237e;">
                        <?php echo htmlspecialchars($item_desc); ?>
                    </td>
                    <td><?php echo htmlspecialchars($bill['month_for']); ?></td>
                    <td class="text-right" style="font-weight: 700; color:#d32f2f;"><?php echo htmlspecialchars($item_amt); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2" style="border:none;"></td>
                    <td style="font-weight: 700; border:none; text-align:right;">Other Dues Subtotal:</td>
                    <td class="text-right" style="border:none; font-weight: 700; color:#d32f2f;">₹ <?php echo number_format($expense_subtotal, 2); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Payments received block (if any) -->
        <?php if (!empty($payment_items)): ?>
            <div style="margin-bottom: 20px; font-weight: 700; color: #2e7d32; text-align: right; padding-right: 15px; border-bottom: 1px dashed #c8e6c9; padding-bottom: 10px;">
                <?php foreach ($payment_items as $p_item): ?>
                    <div style="margin-bottom: 5px;">
                        <?php echo htmlspecialchars($p_item); ?>
                    </div>
                <?php endforeach; ?>
                <div style="font-size: 0.95rem; margin-top: 8px;">
                    Total Payments Received: <span>-₹ <?php echo number_format($payment_subtotal, 2); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grand Total Strip -->
        <div class="total-strip">
            <div class="total-label">Total Amount Due</div>
            <div class="total-value">₹ <?php echo number_format($bill['amount'], 2); ?></div>
        </div>

        <!-- Amount in Words -->
        <div class="words-block">
            Amount due in words: <strong><?php echo $amount_in_words; ?></strong>
        </div>

    </div>

</body>
</html>
