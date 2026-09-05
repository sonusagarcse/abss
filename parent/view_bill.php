<?php
// parent/view_bill.php - Professional Parent Fee Invoice View with Mobile Responsiveness & Razorpay Integration
require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];
if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$bill_id = (int)$_GET['id'];

// Fetch bill details with student scholar mode & class admitted
$stmt = $conn->prepare("
    SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, p.parent_name, p.phone, p.email as parent_email
    FROM fees_generated fg
    JOIN students s ON fg.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE fg.id = ? AND s.parent_id = ?
");
$stmt->bind_param("ii", $bill_id, $pid);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>Invoice not found or unauthorized access.</p><a href='fees.php'>Back to Fees Ledger</a></div>");
}

$settings = getAllSettings();
$school_name = $settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan';
$school_address = $settings['address'] ?? 'Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206';
$school_phone = $settings['phone'] ?? '+91 9523012888';
$school_email = $settings['email'] ?? 'abssimamganj@gmail.com';

// Razorpay Key Configuration
$razorpay_key = $settings['razorpay_key_id'] ?? '';
$razorpay_secret = $settings['razorpay_key_secret'] ?? '';

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
    $paise = ($decimal > 0) ? "." . ($words[(int)floor($decimal / 10)] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . ($paise ? 'and ' . $paise : '') . 'Only';
}

// Calculate dynamic late fine
$fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($bill, $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0, 'rate_per_day' => 5.00];
$fine_amount = ($bill['status'] === 'unpaid') ? $fine_calc['fine_amount'] : 0.00;
$total_payable_amount = (float)$bill['amount'] + $fine_amount;

$amount_in_words = amountToWords($total_payable_amount);
$invoice_no = "ABSS-INV-" . date('Y', strtotime($bill['billing_date'])) . "-" . str_pad($bill['id'], 5, '0', STR_PAD_LEFT);

// Server-side Razorpay Order Generation for UPI Intent & WebViews
$razorpay_order_id = '';
if ($bill['status'] === 'unpaid' && !empty($razorpay_key) && !empty($razorpay_secret) && strpos($razorpay_key, 'rzp_') === 0) {
    try {
        $order_payload = [
            'amount' => (int)round($total_payable_amount * 100),
            'currency' => 'INR',
            'receipt' => 'RCPT_' . $bill['id'] . '_' . substr(md5(uniqid()), 0, 8),
            'notes' => [
                'bill_id' => (string)$bill['id'],
                'student_id' => (string)$bill['student_id'],
                'parent_id' => (string)$_SESSION['parent_id']
            ]
        ];

        $ch_ord = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch_ord, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_ord, CURLOPT_POST, true);
        curl_setopt($ch_ord, CURLOPT_POSTFIELDS, json_encode($order_payload));
        curl_setopt($ch_ord, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch_ord, CURLOPT_USERPWD, $razorpay_key . ':' . $razorpay_secret);
        curl_setopt($ch_ord, CURLOPT_TIMEOUT, 8);
        $order_resp = curl_exec($ch_ord);
        $order_code = curl_getinfo($ch_ord, CURLINFO_HTTP_CODE);
        curl_close($ch_ord);

        if ($order_code === 200) {
            $order_json = json_decode($order_resp, true);
            if (!empty($order_json['id'])) {
                $razorpay_order_id = $order_json['id'];
            }
        }
    } catch (Exception $oe) {
        // Fallback gracefully to direct checkout if order API is unreachable
        error_log("Razorpay Order API Error: " . $oe->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Invoice - <?php echo $invoice_no; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #525659; margin: 0; padding: 20px 10px; -webkit-print-color-adjust: exact; }
        
        .control-bar { max-width: 800px; margin: 0 auto 20px; background: #fff; padding: 15px 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); flex-wrap: wrap; gap: 12px; }
        .btn-control { text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-family: inherit; transition: 0.3s; }
        .btn-back { background: #f0f4f8; color: #1a237e; }
        .btn-back:hover { background: #e2ebf0; }

        .btn-pay-rzp { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .btn-pay-rzp:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); }

        .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px 45px; border-radius: 6px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); box-sizing: border-box; position: relative; overflow: hidden; border: 1px solid #dcdcdc; }
        
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 7rem; color: rgba(211, 47, 47, 0.04); font-weight: 800; pointer-events: none; text-align: center; width: 120%; z-index: 1; user-select: none; border: 15px double rgba(211, 47, 47, 0.04); padding: 20px; }

        .receipt-header { display: flex; justify-content: space-between; border-bottom: 3px double #e0e0e0; padding-bottom: 25px; margin-bottom: 30px; position: relative; z-index: 2; flex-wrap: wrap; gap: 20px; }
        .school-branding { display: flex; align-items: center; gap: 18px; }
        .school-branding img { height: 65px; width: auto; }
        .school-info h2 { margin: 0 0 4px 0; color: #1a237e; font-size: 1.45rem; font-weight: 800; }
        .school-info p { margin: 0; color: #555; font-size: 0.82rem; line-height: 1.4; font-weight: 500; }
        
        .receipt-meta { text-align: right; }
        .receipt-title { font-size: 1.25rem; font-weight: 800; color: #d32f2f; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .receipt-no { font-family: monospace; font-size: 0.92rem; font-weight: 700; color: #333; margin-bottom: 4px; }
        .receipt-date { font-size: 0.82rem; color: #666; font-weight: 600; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; position: relative; z-index: 2; }
        .details-col h4 { margin: 0 0 10px 0; color: #1a237e; font-size: 0.82rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; padding-bottom: 4px; letter-spacing: 0.05em; }
        
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 5px 0; font-size: 0.88rem; border: none; background: transparent; }
        .kv-label { color: #666; font-weight: 500; width: 38%; }
        .kv-value { color: #111; font-weight: 700; }

        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 20px; }
        .item-table { width: 100%; border-collapse: collapse; min-width: 500px; position: relative; z-index: 2; }
        .item-table th { background: #feeef2; color: #d32f2f; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; padding: 10px 12px; border-top: 1px solid #d32f2f; border-bottom: 2px solid #d32f2f; }
        .item-table td { padding: 12px 14px; font-size: 0.88rem; border-bottom: 1px solid #e2e8f0; color: #333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-strip { background: #feeef2; padding: 14px 25px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 2; border: 1px solid #ffcdd2; flex-wrap: wrap; gap: 10px; }
        .total-label { font-size: 1.05rem; font-weight: 800; color: #b71c1c; }
        .total-value { font-size: 1.35rem; font-weight: 800; color: #b71c1c; }

        .words-block { font-size: 0.85rem; color: #555; margin-bottom: 30px; font-style: italic; border-left: 3px solid #d32f2f; padding-left: 14px; position: relative; z-index: 2; }
        .words-block strong { color: #d32f2f; font-style: normal; font-weight: 700; }

        /* Mobile Responsiveness Rules (max-width: 640px) */
        @media (max-width: 640px) {
            body { padding: 10px 5px; }
            .control-bar { padding: 12px 15px; border-radius: 10px; flex-direction: column; align-items: stretch; }
            .btn-control { justify-content: center; width: 100%; }
            .receipt-container { padding: 25px 18px; border-radius: 12px; }
            .watermark { font-size: 4rem; }
            .receipt-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .school-branding { flex-direction: column; align-items: flex-start; text-align: left; gap: 10px; }
            .school-branding img { height: 50px; }
            .school-info h2 { font-size: 1.25rem; }
            .receipt-meta { text-align: left; width: 100%; border-top: 1px dashed #e2e8f0; padding-top: 10px; }
            .details-grid { grid-template-columns: 1fr; gap: 20px; }
            .item-table th, .item-table td { padding: 8px 10px; font-size: 0.82rem; }
            .total-strip { flex-direction: column; align-items: flex-start; gap: 6px; }
            .total-value { font-size: 1.2rem; }
        @media print {
            body { background: #fff; padding: 0; }
            .control-bar, .receipt-paynow-highlight-box { display: none !important; }
            .receipt-container { box-shadow: none; border: none; padding: 10px; max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Control Panel -->
    <div class="control-bar">
        <div style="display:flex; gap: 10px; flex-wrap:wrap; width:100%; align-items:center;">
            <a href="fees.php" class="btn-control btn-back"><i class="fas fa-chevron-left"></i> Back to Dues</a>
            <button onclick="window.print()" class="btn-control btn-back"><i class="fas fa-print"></i> Print / PDF</button>
            <button type="button" onclick="shareInvoiceWhatsAppDirect()" class="btn-control" id="btnShareWaImg" style="background:#25d366; color:#ffffff; font-weight:800; border:none; box-shadow: 0 4px 12px rgba(37,211,102,0.25);">
                <i class="fab fa-whatsapp"></i> 📲 Share on WhatsApp
            </button>
            <?php if ($bill['status'] === 'unpaid'): ?>
                <button type="button" onclick="payWithRazorpay()" class="btn-control btn-pay-rzp" style="margin-left:auto;">
                    <i class="fas fa-credit-card"></i> Pay Online (Razorpay)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Printable Invoice Container -->
    <div class="receipt-container" id="receiptContainer">
        
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
                <h4>Parent Details</h4>
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
                <h4>Student Details</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Student Name:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($bill['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Class:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($bill['class_admitted'] ? $bill['class_admitted'] : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Scholar Mode:</td>
                        <td class="kv-value" style="color:#2563eb; font-weight:800;"><?php echo htmlspecialchars($bill['scholar_mode'] ? $bill['scholar_mode'] : 'Day Scholar'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Ledger itemization table -->
        <div class="table-responsive">
            <table class="item-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 8%;">S.No</th>
                        <th>Fee Description</th>
                        <th style="width: 25%;">Bill Month</th>
                        <th class="text-right" style="width: 22%;">Amount Due</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $remarks = explode('|', $bill['remark'] ? $bill['remark'] : 'Tuition Fee');
                $sno = 1;

                foreach ($remarks as $rem) {
                    $rem = trim($rem);
                    if (strpos($rem, 'Auto-generated Bill.') !== false) {
                        $rem = trim(str_replace('Auto-generated Bill.', '', $rem));
                    }
                    if (empty($rem)) continue;

                    $is_payment_row = (strpos(strtolower($rem), 'payment received') !== false || strpos($rem, '-₹') !== false);
                    $item_desc = $rem;
                    $item_month = $bill['month_for'];
                    $item_amt = '';

                    if ($is_payment_row) {
                        if (preg_match('/\(-?\s*[₹Rs\.]*\s*([0-9\.,]+)\)/i', $rem, $amt_match)) {
                            $item_amt = '-₹ ' . number_format((float)str_replace(',', '', $amt_match[1]), 2);
                        } elseif (preg_match('/-?\s*[₹Rs\.]\s*([0-9\.,]+)/i', $rem, $amt_match)) {
                            $item_amt = '-₹ ' . number_format((float)str_replace(',', '', $amt_match[1]), 2);
                        } else {
                            $item_amt = '-₹ 0.00';
                        }
                        if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $rem, $d_match)) {
                            $item_month = date('d M, Y', strtotime($d_match[0]));
                            $item_desc = "Payment received on " . date('d M, Y', strtotime($d_match[0]));
                        } else {
                            $item_desc = "Payment received";
                        }
                    } else {
                        // Extract month string from () or [] if present in item remark
                        if (preg_match('/\((.*?)\)/', $rem, $m_match)) {
                            $item_month = trim($m_match[1]);
                            $rem = trim(str_replace($m_match[0], '', $rem));
                        } elseif (preg_match('/\[(.*?)\]/', $rem, $m_match)) {
                            $item_month = trim($m_match[1]);
                            $rem = trim(str_replace($m_match[0], '', $rem));
                        }

                        // Extract amount from : ₹ or :
                        if (strpos($rem, ': ₹') !== false) {
                            $parts = explode(': ₹', $rem);
                            $item_desc = trim($parts[0]);
                            $item_amt = '₹ ' . trim($parts[1]);
                        } elseif (strpos($rem, ':') !== false) {
                            $parts = explode(':', $rem);
                            $item_desc = trim($parts[0]);
                            $item_amt = '₹ ' . trim($parts[1]);
                        } else {
                            $item_desc = trim($rem);
                            $item_amt = '₹ ' . number_format($bill['amount'], 2);
                        }

                        // Clean up description if any residual amounts were left in description string
                        if (preg_match('/₹\s*[0-9\.,]+/', $item_desc, $amt_match)) {
                            $item_desc = trim(str_replace($amt_match[0], '', $item_desc));
                        }

                        if (empty($item_desc)) $item_desc = "Tuition Fee";
                    }
                    ?>
                    <tr style="<?php echo $is_payment_row ? 'background: #f0fdf4;' : ''; ?>">
                        <td class="text-center"><?php echo $sno++; ?></td>
                        <td style="font-weight: 700; color: <?php echo $is_payment_row ? '#15803d' : '#1a237e'; ?>;">
                            <?php if ($is_payment_row): ?><i class="fas fa-check-circle" style="margin-right: 5px;"></i><?php endif; ?>
                            <?php echo htmlspecialchars($item_desc); ?>
                        </td>
                        <td style="font-weight: 700; color: <?php echo $is_payment_row ? '#166534' : '#2563eb'; ?>;">
                            <?php echo htmlspecialchars($item_month); ?>
                        </td>
                        <td class="text-right" style="font-weight: 800; color: <?php echo $is_payment_row ? '#15803d' : '#b71c1c'; ?>;">
                            <?php echo htmlspecialchars($item_amt); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <?php if ($fine_amount > 0): ?>
                    <tr style="background: #fff7ed;">
                        <td class="text-center" style="font-weight: 700; color: #ea580c;"><?php echo $sno++; ?></td>
                        <td style="font-weight: 700; color: #9a3412;">
                            <i class="fas fa-coins" style="color:#ea580c;"></i> Late Fine (विलंब शुल्क)
                            <span style="font-size:0.75rem; font-weight:600; color:#ea580c; background:#ffedd5; padding:2px 8px; border-radius:50px; margin-left:6px;">
                                <?php echo $fine_calc['overdue_days']; ?> Days Overdue @ ₹<?php echo number_format($fine_calc['rate_per_day'], 2); ?>/day
                            </span>
                        </td>
                        <td style="font-weight: 700; color: #ea580c;">
                            <?php echo htmlspecialchars($bill['month_for']); ?>
                        </td>
                        <td class="text-right" style="font-weight: 800; color:#ea580c;">
                            ₹ <?php echo number_format($fine_amount, 2); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $total_payable = (float)$bill['amount'] + (float)$fine_amount;
        $words_payable = amountToWords($total_payable);
        $remaining_balance = $total_payable;
        $final_words = $words_payable;
        ?>

        <!-- Total Amount Strip -->
        <div class="total-strip">
            <div class="total-label">Total Amount Due</div>
            <div class="total-value">₹ <?php echo number_format($total_payable, 2); ?></div>
        </div>

        <!-- Amount in Words -->
        <div class="words-block">
            Amount due in words: <strong><?php echo $words_payable; ?></strong>
        </div>

        <?php if ($remaining_balance > 0): ?>
            <!-- Highlighted Pay Now Action Block Below Total Amount -->
            <div class="receipt-paynow-highlight-box" style="margin-top: 25px; padding: 22px 24px; background: linear-gradient(135deg, #fef2f2 0%, #fff7ed 100%); border: 2px dashed #f87171; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.08);">
                <div>
                    <div style="font-size: 1.18rem; font-weight: 900; color: #991b1b; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-circle" style="color: #dc2626; font-size: 1.3rem;"></i> Immediate Fee Payment Due
                    </div>
                    <p style="margin: 4px 0 0; color: #475569; font-size: 0.88rem; font-weight: 600;">
                        Payable Balance: <strong style="color: #dc2626; font-size: 1.05rem;">₹ <?php echo number_format($remaining_balance, 2); ?></strong>
                        <?php if ($fine_amount > 0): ?>
                            (Includes ₹ <?php echo number_format($fine_amount, 2); ?> Late Fine)
                        <?php endif; ?>
                        • Instant Receipt & Verification.
                    </p>
                </div>
                <div>
                    <button type="button" onclick="payWithRazorpay()" class="btn-pay-highlight" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: #ffffff; padding: 15px 32px; border-radius: 50px; font-size: 1.1rem; font-weight: 900; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 25px rgba(22, 163, 74, 0.45); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                        <i class="fas fa-lock"></i> PAY NOW (₹ <?php echo number_format($remaining_balance, 2); ?>) <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Hidden form for Razorpay Payment Verification -->
    <form id="razorpayForm" action="verify_payment.php" method="POST" style="display:none;">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
    </form>

    <script>
        function payWithRazorpay() {
            var options = {
                "key": "<?php echo htmlspecialchars($razorpay_key); ?>",
                "amount": "<?php echo round($total_payable_amount * 100); ?>", // Amount in paise
                "currency": "INR",
                "name": "<?php echo addslashes($school_name); ?>",
                "description": "Fee Invoice #<?php echo $bill['id']; ?> (<?php echo addslashes($bill['month_for']); ?>)",
                <?php if (!empty($razorpay_order_id)): ?>
                "order_id": "<?php echo htmlspecialchars($razorpay_order_id); ?>",
                <?php endif; ?>
                <?php 
                $is_ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                $is_local = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
                if ($is_ssl && !$is_local && !empty($settings['site_logo'])): ?>
                "image": "<?php echo htmlspecialchars($settings['site_logo']); ?>",
                <?php endif; ?>
                "webview_intent": true,
                "method": {
                    "upi": true,
                    "card": true,
                    "netbanking": true,
                    "wallet": true
                },
                "retry": {
                    "enabled": true
                },
                "handler": function (response){
                    console.log('Razorpay Payment Success Returned:', response);
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    if (response.razorpay_order_id) {
                        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                    }
                    if (response.razorpay_signature) {
                        document.getElementById('razorpay_signature').value = response.razorpay_signature;
                    }
                    document.getElementById('razorpayForm').submit();
                },
                "prefill": {
                    "name": "<?php echo addslashes($bill['parent_name'] ?? ''); ?>",
                    "email": "<?php echo addslashes($bill['parent_email'] ?? ''); ?>",
                    "contact": "<?php echo addslashes($bill['phone'] ?? ''); ?>"
                },
                "notes": {
                    "student_name": "<?php echo addslashes($bill['student_name']); ?>",
                    "invoice_id": "<?php echo $bill['id']; ?>"
                },
                "theme": {
                    "color": "#1d4ed8"
                },
                "modal": {
                    "ondismiss": function() {
                        console.log('Razorpay checkout modal closed by user.');
                    }
                }
            };

            console.log('Razorpay Checkout Initialized with webview_intent: true', {
                order_id: options.order_id || 'direct_payment',
                amount: options.amount
            });

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                console.warn('Razorpay Payment Failed:', response.error);
                alert('Payment Failed: ' + (response.error.description || 'Transaction cancelled or declined.'));
            });
            rzp.open();
        }

        function shareInvoiceWhatsAppDirect() {
            shareInvoiceOnWhatsApp({
                containerId: 'receiptContainer',
                studentName: <?php echo json_encode($bill['student_name']); ?>,
                invoiceNo: <?php echo json_encode($invoice_no); ?>,
                amount: <?php echo json_encode(number_format($total_payable_amount, 2)); ?>,
                date: <?php echo json_encode(date('d M, Y', strtotime($bill['billing_date']))); ?>,
                phone: <?php echo json_encode($bill['phone'] ?? ''); ?>,
                btnId: 'btnShareWaImg'
            });
        }
    </script>
    <script src="../js/invoice-share-bridge.js"></script>
</body>
</html>
