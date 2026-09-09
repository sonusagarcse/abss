<?php
// admin/receipt.php - Official ABSS Payment Receipt for Admin Portal

require_once 'includes/auth.php';

$pay_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pay_id <= 0) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Invalid Request</h2><p>No payment transaction ID specified.</p><a href='fees.php'>Back to Fees Ledger</a></div>");
}

// Fetch payment details along with student details
$pay_query = $conn->prepare("
    SELECT f.*, s.name AS student_name, s.reg_no, s.class_admitted, s.scholar_mode, s.parent_name, s.phone, s.guardian_email 
    FROM fee_payments f
    JOIN students s ON f.student_id = s.id
    WHERE f.id = ?
");
$pay_query->bind_param("i", $pay_id);
$pay_query->execute();
$pay = $pay_query->get_result()->fetch_assoc();

if (!$pay) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Payment Record Not Found</h2><p>Payment transaction #$pay_id does not exist.</p><a href='fees.php'>Back to Fees Ledger</a></div>");
}

$settings = function_exists('getAllSettings') ? getAllSettings() : [];
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
$receipt_no = "ABSS-REC-" . date('Y', strtotime($pay['payment_date'])) . "-" . str_pad($pay['id'], 5, '0', STR_PAD_LEFT);

// Parse Razorpay Payment ID if present
$is_online = (stripos($pay['payment_method'], 'Online') !== false || stripos($pay['payment_method'], 'Razorpay') !== false || stripos($pay['payment_method'], 'App') !== false || stripos($pay['payment_method'], 'UPI') !== false);
$rzp_id = '';
if (preg_match('/(pay_[a-zA-Z0-9]+)/', $pay['payment_method'], $m)) {
    $rzp_id = $m[1];
}

// Inline Base64 Logo for Canvas and PDF security
$logo_path = __DIR__ . '/../assets/logo.png';
$logo_base64 = '';
if (file_exists($logo_path)) {
    $logo_data = file_get_contents($logo_path);
    $logo_base64 = 'data:image/png;base64,' . base64_encode($logo_data);
} else {
    $logo_base64 = '../assets/logo.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt - <?php echo htmlspecialchars($receipt_no); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: #334155; margin: 0; padding: 25px 0; -webkit-print-color-adjust: exact; }
        
        .control-bar { 
            max-width: 800px; 
            margin: 0 auto 20px; 
            background: #ffffff; 
            padding: 14px 24px; 
            border-radius: 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); 
        }
        .btn-control { 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 0.88rem; 
            padding: 10px 18px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            font-family: inherit; 
            transition: all 0.2s ease; 
        }
        .btn-back { background: #f1f5f9; color: #1e293b; }
        .btn-back:hover { background: #e2e8f0; }
        .btn-print { background: #2563eb; color: #ffffff; }
        .btn-print:hover { background: #1d4ed8; transform: translateY(-1px); }

        .receipt-container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: #ffffff; 
            padding: 45px 50px; 
            border-radius: 6px; 
            box-shadow: 0 12px 35px rgba(0,0,0,0.2); 
            position: relative; 
            overflow: hidden; 
            border: 1px solid #cbd5e1; 
        }
        
        .watermark { 
            position: absolute; 
            top: 52%; 
            left: 50%; 
            transform: translate(-50%, -50%) rotate(-30deg); 
            font-size: 5.5rem; 
            color: rgba(30, 41, 59, 0.03); 
            font-weight: 900; 
            pointer-events: none; 
            text-align: center; 
            width: 140%; 
            z-index: 1; 
            user-select: none; 
            line-height: 1.1;
        }

        .receipt-header { 
            display: flex; 
            justify-content: space-between; 
            border-bottom: 3px double #cbd5e1; 
            padding-bottom: 25px; 
            margin-bottom: 30px; 
            position: relative; 
            z-index: 2; 
        }
        .school-branding { display: flex; align-items: center; gap: 18px; }
        .school-branding img { height: 75px; object-fit: contain; }
        .school-info h2 { margin: 0 0 4px 0; color: #0f172a; font-size: 1.5rem; font-weight: 900; letter-spacing: -0.01em; }
        .school-info p { margin: 0; color: #64748b; font-size: 0.83rem; line-height: 1.4; font-weight: 500; }
        
        .receipt-meta { text-align: right; }
        .receipt-title { font-size: 1.35rem; font-weight: 900; color: #0284c7; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em; }
        .receipt-no { font-family: monospace; font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .receipt-date { font-size: 0.84rem; color: #475569; font-weight: 600; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; position: relative; z-index: 2; }
        .details-col h4 { margin: 0 0 10px 0; color: #0f172a; font-size: 0.82rem; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; padding-bottom: 5px; letter-spacing: 0.05em; font-weight: 800; }
        
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 5px 0; font-size: 0.88rem; border: none; background: transparent; }
        .kv-label { color: #64748b; font-weight: 500; width: 40%; }
        .kv-value { color: #0f172a; font-weight: 700; }

        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; position: relative; z-index: 2; }
        .item-table th { background: #f8fafc; color: #0f172a; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; padding: 12px 14px; border-top: 1px solid #0f172a; border-bottom: 2px solid #0f172a; letter-spacing: 0.05em; }
        .item-table td { padding: 14px; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        .text-right { text-align: right; }

        .total-strip { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 22px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; position: relative; z-index: 2; }
        .total-label { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
        .total-value { font-size: 1.35rem; font-weight: 900; color: #15803d; }

        .words-block { font-size: 0.85rem; color: #475569; margin-bottom: 40px; font-style: italic; border-left: 3px solid #0284c7; padding-left: 14px; position: relative; z-index: 2; }
        .words-block strong { color: #0f172a; font-style: normal; font-weight: 700; }

        .footer-receipt { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 20px; align-items: flex-end; position: relative; z-index: 2; }
        .qr-section { display: flex; align-items: center; gap: 14px; }
        .qr-code { width: 85px; height: 85px; border: 1px solid #e2e8f0; padding: 5px; border-radius: 6px; background: #fff; }
        .qr-info { font-size: 0.75rem; color: #64748b; line-height: 1.4; }
        .qr-info strong { color: #0f172a; }

        .sig-section { text-align: center; }
        .sig-line { border-bottom: 1px dashed #94a3b8; margin-bottom: 8px; height: 35px; }
        .sig-title { font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }

        @media print {
            body { background: #fff; padding: 0; }
            .control-bar { display: none; }
            .receipt-container { box-shadow: none; border: none; padding: 10px; max-width: 100%; }
            .watermark { color: rgba(30, 41, 59, 0.02); }
        }

        @media (max-width: 768px) {
            body { padding: 10px 0; background: #f8fafc; }
            .control-bar { margin: 10px; padding: 12px; }
            .receipt-container { margin: 10px; padding: 25px 20px; max-width: calc(100% - 20px); }
            .receipt-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .receipt-meta { text-align: left; }
            .details-grid { grid-template-columns: 1fr; gap: 15px; }
            .footer-receipt { grid-template-columns: 1fr; gap: 25px; text-align: center; justify-items: center; }
            .qr-section { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Print Control Bar -->
    <div class="control-bar">
        <a href="fees.php" class="btn-control btn-back"><i class="fas fa-arrow-left"></i> Back to Fees Ledger</a>
        <button type="button" onclick="shareReceiptWhatsAppDirect()" class="btn-control" id="btnShareWaImg" style="background:#16a34a; color:#ffffff; font-weight:800; border:none; box-shadow: 0 4px 12px rgba(22,163,74,0.25);">
            <i class="fab fa-whatsapp"></i> 📲 WhatsApp Share
        </button>
        <button onclick="window.print()" class="btn-control btn-print"><i class="fas fa-print"></i> Print / Save PDF</button>
    </div>

    <!-- Printable Receipt -->
    <div class="receipt-container" id="receiptContainer">
        
        <div class="watermark">
            ABSS CERTIFIED<br>OFFICIAL RECEIPT
        </div>
        
        <!-- Header -->
        <div class="receipt-header">
            <div class="school-branding">
                <img src="<?php echo $logo_base64; ?>" alt="ABSS School Logo">
                <div class="school-info">
                    <h2><?php echo htmlspecialchars($school_name); ?></h2>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($school_address); ?></p>
                    <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($school_phone); ?> | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($school_email); ?></p>
                </div>
            </div>
            <div class="receipt-meta">
                <div class="receipt-title"><?php echo $is_online ? 'ONLINE E-RECEIPT' : 'PAYMENT RECEIPT'; ?></div>
                <div class="receipt-no"><?php echo htmlspecialchars($receipt_no); ?></div>
                <div class="receipt-date">
                    Date: <strong><?php echo date('d M, Y', strtotime($pay['payment_date'])); ?></strong>
                    <?php if (!empty($pay['created_at'])): ?>
                        <span style="display:block; font-size:0.75rem; color:#64748b; font-weight:600; margin-top:2px;">
                            <i class="far fa-clock"></i> <?php echo date('h:i:s A', strtotime($pay['created_at'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student & Parent Information -->
        <div class="details-grid">
            <div class="details-col">
                <h4>Student Information</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Student Name:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Reg. Number:</td>
                        <td class="kv-value"><?php echo !empty($pay['reg_no']) ? htmlspecialchars($pay['reg_no']) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Class:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['class_admitted'] ?? 'Class 6'); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Scholar Mode:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['scholar_mode'] ?? 'Day Scholar'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="details-col">
                <h4>Payment &amp; Gateway Meta</h4>
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Payer / Parent:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['parent_name'] ? $pay['parent_name'] : 'School Office'); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Payment Mode:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                    </tr>
                    <?php if (!empty($rzp_id)): ?>
                    <tr>
                        <td class="kv-label">Razorpay Txn:</td>
                        <td class="kv-value" style="font-family:monospace; color:#0284c7;"><?php echo htmlspecialchars($rzp_id); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="kv-label">Payment Status:</td>
                        <td class="kv-value" style="color:#16a34a;"><i class="fas fa-circle-check"></i> Verified / Captured</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Itemized Table -->
        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th>Fee Description / Month</th>
                    <th>Transaction Channel</th>
                    <th class="text-right" style="width: 25%;">Amount Paid (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong style="color:#0f172a; font-size:0.95rem;"><?php echo htmlspecialchars($pay['month_for']); ?></strong>
                        <div style="font-size:0.78rem; color:#64748b; margin-top:3px;">
                            Official school tuition &amp; institutional services fee
                        </div>
                    </td>
                    <td>
                        <span style="font-weight:700; color:#334155;">
                            <?php echo htmlspecialchars($pay['payment_method']); ?>
                        </span>
                    </td>
                    <td class="text-right" style="font-weight:800; font-size:1.05rem; color:#0f172a;">
                        ₹ <?php echo number_format($pay['amount'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Total Paid Strip -->
        <div class="total-strip">
            <span class="total-label">Total Amount Received:</span>
            <span class="total-value">₹ <?php echo number_format($pay['amount'], 2); ?></span>
        </div>

        <!-- Words Block -->
        <div class="words-block">
            Amount in words: <strong><?php echo htmlspecialchars($amount_in_words); ?></strong>
        </div>

        <!-- Footer Signatures & QR -->
        <div class="footer-receipt">
            <div class="qr-section">
                <?php
                $qr_data = "ABSS RECEIPT|" . $receipt_no . "|Student: " . $pay['student_name'] . "|Amt: INR " . $pay['amount'] . "|Date: " . $pay['payment_date'];
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
                ?>
                <img src="<?php echo $qr_url; ?>" class="qr-code" alt="Verification QR">
                <div class="qr-info">
                    <strong>Digital Verification</strong><br>
                    Scan to verify original record in school ERP portal.
                </div>
            </div>
            
            <div class="sig-section">
                <div class="sig-line"></div>
                <div class="sig-title">Cashier / Operator</div>
            </div>

            <div class="sig-section">
                <div class="sig-line"></div>
                <div class="sig-title">Authorized Signatory</div>
            </div>
        </div>

    </div>

    <!-- WhatsApp Share Bridge -->
    <script src="../js/invoice-share-bridge.js?v=<?= time() ?>"></script>
    <script>
        function shareReceiptWhatsAppDirect() {
            var btn = document.getElementById('btnShareWaImg');
            var origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
            }

            var studentName = "<?php echo addslashes($pay['student_name']); ?>";
            var receiptNo = "<?php echo addslashes($receipt_no); ?>";
            var amountStr = "₹ <?php echo number_format($pay['amount'], 2); ?>";
            var dateStr = "<?php echo date('d M, Y', strtotime($pay['payment_date'])); ?>";
            var monthStr = "<?php echo addslashes($pay['month_for']); ?>";
            var phone = "<?php echo preg_replace('/[^0-9]/', '', $pay['phone'] ?? ''); ?>";

            var msg = "Greetings from *<?php echo addslashes($school_name); ?>*!\n\n"
                    + "Official Fee Payment Receipt Confirmation:\n"
                    + "• *Receipt No:* " + receiptNo + "\n"
                    + "• *Student:* " + studentName + "\n"
                    + "• *Billing Month:* " + monthStr + "\n"
                    + "• *Amount Received:* " + amountStr + "\n"
                    + "• *Payment Date:* " + dateStr + "\n\n"
                    + "Thank you for your timely payment.\n"
                    + "*ABSS Administration*";

            shareInvoiceOnWhatsApp({
                containerId: 'receiptContainer',
                shareMessage: msg,
                fileName: 'Receipt_' + receiptNo + '.png',
                phone: phone,
                buttonElement: btn,
                originalButtonHtml: origHtml
            });
        }
    </script>
</body>
</html>
