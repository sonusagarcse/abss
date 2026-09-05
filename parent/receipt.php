<?php
// parent/receipt.php - Premium Printable Cash Receipt

require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];
$pay_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch payment details and verify security ownership
$pay_query = $conn->prepare("
    SELECT f.*, s.name AS student_name, s.class_admitted, s.parent_name, s.phone 
    FROM fee_payments f
    JOIN students s ON f.student_id = s.id
    WHERE f.id = ? AND s.parent_id = ?
");
$pay_query->bind_param("ii", $pay_id, $pid);
$pay_query->execute();
$pay = $pay_query->get_result()->fetch_assoc();

if (!$pay) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>Transaction not found or unauthorized access.</p><a href='fees.php'>Back to Fees Ledger</a></div>");
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

$amount_in_words = amountToWords($pay['amount']);
$receipt_no = "ABSS-REC-" . date('Y') . "-" . str_pad($pay['id'], 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt - <?php echo $receipt_no; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #525659; margin: 0; padding: 30px 0; -webkit-print-color-adjust: exact; }
        
        /* Control Bar styling */
        .control-bar { max-width: 800px; margin: 0 auto 20px; background: #fff; padding: 15px 30px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-control { text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-family: inherit; transition: 0.3s; }
        .btn-back { background: #f0f4f8; color: #1a237e; }
        .btn-back:hover { background: #e2ebf0; }
        .btn-print { background: #3f51b5; color: #fff; }
        .btn-print:hover { background: #1a237e; box-shadow: 0 4px 10px rgba(63,81,181,0.3); }

        /* Receipt Canvas */
        .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); box-sizing: border-box; position: relative; overflow: hidden; border: 1px solid #dcdcdc; }
        
        /* Subtle Watermark logo background */
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 8rem; color: rgba(26, 35, 126, 0.025); font-weight: 800; pointer-events: none; text-align: center; width: 120%; z-index: 1; user-select: none; }

        .receipt-header { display: flex; justify-content: space-between; border-bottom: 3px double #e0e0e0; padding-bottom: 30px; margin-bottom: 35px; position: relative; z-index: 2; }
        .school-branding { display: flex; align-items: center; gap: 20px; }
        .school-branding img { height: 75px; }
        .school-info h2 { margin: 0 0 5px 0; color: #1a237e; font-size: 1.6rem; font-weight: 800; }
        .school-info p { margin: 0; color: #555; font-size: 0.85rem; line-height: 1.4; font-weight: 500; }
        
        .receipt-meta { text-align: right; }
        .receipt-title { font-size: 1.3rem; font-weight: 800; color: #1a237e; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
        .receipt-no { font-family: monospace; font-size: 1rem; font-weight: 700; color: #333; margin-bottom: 5px; }
        .receipt-date { font-size: 0.85rem; color: #666; font-weight: 600; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; position: relative; z-index: 2; }
        .details-col h4 { margin: 0 0 12px 0; color: #1a237e; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; padding-bottom: 5px; letter-spacing: 0.05em; }
        
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 6px 0; font-size: 0.9rem; border: none; background: transparent; }
        .kv-label { color: #666; font-weight: 500; width: 35%; }
        .kv-value { color: #111; font-weight: 700; }

        /* Itemized table */
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; position: relative; z-index: 2; }
        .item-table th { background: #f8fafc; color: #1a237e; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; padding: 12px 15px; border-top: 1px solid #1a237e; border-bottom: 2px solid #1a237e; }
        .item-table td { padding: 15px; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; color: #333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-strip { background: #f1f5f9; padding: 15px 25px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; position: relative; z-index: 2; }
        .total-label { font-size: 1.05rem; font-weight: 800; color: #1a237e; }
        .total-value { font-size: 1.3rem; font-weight: 800; color: #1a237e; }

        .words-block { font-size: 0.85rem; color: #555; margin-bottom: 50px; font-style: italic; border-left: 3px solid #1a237e; padding-left: 15px; position: relative; z-index: 2; }
        .words-block strong { color: #1a237e; font-style: normal; font-weight: 700; }

        /* Verification QR and Signatures */
        .footer-receipt { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; align-items: flex-end; position: relative; z-index: 2; }
        .qr-section { display: flex; align-items: center; gap: 15px; }
        .qr-code { width: 90px; height: 90px; border: 1px solid #e0e0e0; padding: 5px; border-radius: 6px; background: #fff; }
        .qr-info { font-size: 0.75rem; color: #666; line-height: 1.4; }
        .qr-info strong { color: #1a237e; }

        .sig-section { text-align: center; }
        .sig-line { border-bottom: 1px solid #999; margin-bottom: 10px; height: 40px; }
        .sig-title { font-size: 0.8rem; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.05em; }

        /* Print Media queries */
        @media print {
            body { background: #fff; padding: 0; }
            .control-bar { display: none; }
            .receipt-container { box-shadow: none; border: none; padding: 10px; max-width: 100%; }
            .watermark { color: rgba(26, 35, 126, 0.015); }
        }

        /* Mobile Responsiveness for Receipt */
        @media (max-width: 800px) {
            body { padding: 10px 0; background: #f4f7fa; }
            .control-bar { margin: 10px; padding: 15px; border-radius: 10px; max-width: calc(100% - 20px); }
            .receipt-container { margin: 10px; padding: 25px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); max-width: calc(100% - 20px); }
            .receipt-header { flex-direction: column; align-items: flex-start; gap: 20px; }
            .receipt-meta { text-align: left; }
        }

        @media (max-width: 600px) {
            .details-grid { grid-template-columns: 1fr; gap: 20px; margin-bottom: 25px; }
            .footer-receipt { grid-template-columns: 1fr; gap: 30px; text-align: center; justify-items: center; }
            .qr-section { flex-direction: column; text-align: center; }
            .total-strip { padding: 15px; flex-direction: column; gap: 10px; text-align: center; }
            .sig-section { width: 100%; max-width: 250px; margin: 0 auto; }
            .receipt-container { padding: 20px 15px; }
            .school-branding { flex-direction: column; align-items: flex-start; gap: 15px; }
            .item-table th, .item-table td { padding: 10px 8px; font-size: 0.85rem; }
        }
    </style>
</head>
<body>

    <!-- Print Control Panel -->
    <div class="control-bar">
        <a href="fees.php" class="btn-control btn-back"><i class="fas fa-chevron-left"></i> Back to Ledger</a>
        <button type="button" onclick="shareReceiptWhatsAppDirect()" class="btn-control" id="btnShareWaImg" style="background:#25d366; color:#ffffff; font-weight:800; border:none; box-shadow: 0 4px 12px rgba(37,211,102,0.25);">
            <i class="fab fa-whatsapp"></i> 📲 Share Receipt
        </button>
        <button onclick="window.print()" class="btn-control btn-print"><i class="fas fa-print"></i> Print / Save PDF</button>
    </div>

    <!-- Printable Receipt Container -->
    <div class="receipt-container" id="receiptContainer">
        
        <!-- Watermark -->
        <div class="watermark">
            ABSS CERTIFIED<br>OFFICIAL RECEIPT
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
                <div class="receipt-title">Payment Receipt</div>
                <div class="receipt-no"><?php echo $receipt_no; ?></div>
                <div class="receipt-date">Date: <strong><?php echo date('d M, Y', strtotime($pay['payment_date'])); ?></strong></div>
            </div>
        </div>

        <!-- Payer & Student Details -->
        <div class="details-grid">
            <div class="details-col">
                <h4>Payer Information</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Parent Name:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['parent_name'] ? $pay['parent_name'] : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Phone:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['phone'] ? $pay['phone'] : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Email:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($_SESSION['parent_email']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="details-col">
                <h4>Student Information</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Student Name:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Class:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['class_admitted']); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Status:</td>
                        <td class="kv-value" style="color:#2e7d32;"><i class="fas fa-check-circle"></i> Active Scholar</td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
        // Fetch invoice details for gross subtotal and remaining balance calculation
        $sid = (int)$pay['student_id'];
        $month_for = $pay['month_for'];
        $paid_amount = (float)$pay['amount'];
        $paid_date = date('d M, Y', strtotime($pay['payment_date']));

        $bill_res = $conn->query("SELECT amount, remark, status FROM fees_generated WHERE student_id = $sid AND month_for LIKE '%" . $conn->real_escape_string($month_for) . "%' ORDER BY id DESC LIMIT 1");
        $bill_row = ($bill_res && $bill_res->num_rows > 0) ? $bill_res->fetch_assoc() : null;

        $subtotal = $bill_row ? (float)$bill_row['amount'] : $paid_amount;
        if ($subtotal < $paid_amount) $subtotal = $paid_amount;
        $remaining_due = max(0, $subtotal - $paid_amount);
        ?>

        <!-- Ledger itemization -->
        <table class="item-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 8%;">S.No</th>
                    <th>Fee Description</th>
                    <th>Bill Month</th>
                    <th>Method</th>
                    <th class="text-right" style="width: 25%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $fee_remarks = !empty($bill_row['remark']) ? explode('|', $bill_row['remark']) : ['Monthly Fee Payment'];
                $sno = 1;
                foreach ($fee_remarks as $rem):
                    $rem = trim($rem);
                    if (strpos($rem, 'Auto-generated Bill.') !== false) {
                        $rem = trim(str_replace('Auto-generated Bill.', '', $rem));
                    }
                    if (empty($rem)) continue;
                    if (strpos(strtolower($rem), 'payment received') !== false || strpos($rem, '-₹') !== false || strpos($rem, '-Rs') !== false) {
                        continue;
                    }
                ?>
                    <tr>
                        <td class="text-center"><?php echo $sno++; ?></td>
                        <td style="font-weight: 700; color: #1a237e;">
                            <?php echo htmlspecialchars($rem); ?>
                        </td>
                        <td><?php echo htmlspecialchars($pay['month_for']); ?></td>
                        <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                        <td class="text-right" style="font-weight: 700;">₹ <?php echo number_format($paid_amount, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Grand Summary Breakdown -->
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 18px 24px; margin-bottom: 25px; position: relative; z-index: 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 1rem; font-weight: 700; color: #475569;">Subtotal (Gross Charges & Expenses):</span>
                <span style="font-size: 1.15rem; font-weight: 700; color: #1e293b;">₹ <?php echo number_format($subtotal, 2); ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div>
                    <span style="font-size: 1rem; font-weight: 700; color: #166534;">Less: Amount Paid:</span>
                    <div style="font-size: 0.8rem; font-weight: 600; color: #15803d; margin-top: 2px;">
                        <i class="fas fa-check-circle"></i> Paid on <?php echo $paid_date; ?> via <?php echo htmlspecialchars($pay['payment_method']); ?>
                    </div>
                </div>
                <span style="font-size: 1.15rem; font-weight: 700; color: #166534;">- ₹ <?php echo number_format($paid_amount, 2); ?></span>
            </div>

            <hr style="border: 0; border-top: 2px dashed #cbd5e1; margin: 12px 0;">

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 1.15rem; font-weight: 800; color: <?php echo $remaining_due > 0 ? '#b71c1c' : '#15803d'; ?>;">
                    <?php echo $remaining_due > 0 ? 'Final Remaining Balance Due:' : 'Final Balance Due:'; ?>
                </span>
                <span style="font-size: 1.45rem; font-weight: 800; color: <?php echo $remaining_due > 0 ? '#b71c1c' : '#15803d'; ?>;">
                    ₹ <?php echo number_format($remaining_due, 2); ?>
                </span>
            </div>
        </div>

        <!-- Amount in Words -->
        <div class="words-block">
            Amount received in words: <strong><?php echo $amount_in_words; ?></strong>
        </div>

        <!-- QR Verification & Signatures -->
        <div class="footer-receipt">
            <div class="qr-section">
                <!-- Inline highly premium QR code mockup -->
                <svg class="qr-code" viewBox="0 0 100 100">
                    <!-- Outer borders -->
                    <rect x="5" y="5" width="20" height="20" fill="none" stroke="#1a237e" stroke-width="4"/>
                    <rect x="9" y="9" width="12" height="12" fill="#1a237e"/>
                    <rect x="75" y="5" width="20" height="20" fill="none" stroke="#1a237e" stroke-width="4"/>
                    <rect x="79" y="9" width="12" height="12" fill="#1a237e"/>
                    <rect x="5" y="75" width="20" height="20" fill="none" stroke="#1a237e" stroke-width="4"/>
                    <rect x="9" y="79" width="12" height="12" fill="#1a237e"/>
                    <!-- QR Mock random blocks -->
                    <rect x="35" y="10" width="8" height="8" fill="#1a237e"/>
                    <rect x="47" y="15" width="12" height="6" fill="#1a237e"/>
                    <rect x="63" y="10" width="6" height="10" fill="#1a237e"/>
                    <rect x="35" y="30" width="10" height="10" fill="#1a237e"/>
                    <rect x="50" y="35" width="15" height="5" fill="#1a237e"/>
                    <rect x="70" y="30" width="8" height="12" fill="#1a237e"/>
                    <rect x="35" y="50" width="6" height="15" fill="#1a237e"/>
                    <rect x="55" y="50" width="12" height="8" fill="#1a237e"/>
                    <rect x="75" y="50" width="10" height="10" fill="#1a237e"/>
                    <rect x="35" y="75" width="8" height="15" fill="#1a237e"/>
                    <rect x="50" y="80" width="15" height="10" fill="#1a237e"/>
                </svg>
                <div class="qr-info">
                    <strong>ABSS Verification QR</strong><br>
                    Scan this code to verify payment authenticity.<br>
                    No: <?php echo $pay['id']; ?> / <?php echo date('Ymd'); ?>
                </div>
            </div>
            
            <div class="sig-section">
                <div class="sig-line"></div>
                <div class="sig-title">Cashier / Staff</div>
            </div>
            
            <div class="sig-section">
                <div class="sig-line"></div>
                <div class="sig-title">Authorized Seal</div>
            </div>
        </div>

    </div>

    <script>
        function shareReceiptWhatsAppDirect() {
            shareInvoiceOnWhatsApp({
                containerId: 'receiptContainer',
                studentName: <?php echo json_encode($pay['student_name']); ?>,
                invoiceNo: <?php echo json_encode($receipt_no); ?>,
                amount: <?php echo json_encode(number_format((float)$pay['amount'], 2)); ?>,
                date: <?php echo json_encode(date('d M, Y', strtotime($pay['payment_date']))); ?>,
                phone: <?php echo json_encode($pay['phone'] ?? ''); ?>,
                btnId: 'btnShareWaImg'
            });
        }
    </script>
    <script src="../js/invoice-share-bridge.js"></script>
</body>
</html>
