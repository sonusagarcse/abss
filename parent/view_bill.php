<?php
// parent/view_bill.php - Premium Printable Invoice for Unpaid Dues

require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];
$bill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch invoice details and verify security ownership
$bill_query = $conn->prepare("
    SELECT fg.*, s.name AS student_name, s.class_admitted, s.parent_name, s.phone 
    FROM fees_generated fg
    JOIN students s ON fg.student_id = s.id
    WHERE fg.id = ? AND s.parent_id = ? AND fg.status = 'unpaid'
");
$bill_query->bind_param("ii", $bill_id, $pid);
$bill_query->execute();
$bill = $bill_query->get_result()->fetch_assoc();

if (!$bill) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>Invoice not found, already paid, or unauthorized access.</p><a href='fees.php'>Back to Fees Ledger</a></div>");
}

$settings = getAllSettings();
$school_name = $settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan';
$school_address = $settings['address'] ?? 'Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206';
$school_phone = $settings['phone'] ?? '+91 9523012888';
$school_email = $settings['email'] ?? 'abssimamganj@gmail.com';
$razorpay_key_id = $settings['razorpay_key_id'] ?? '';

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
        .btn-pay { background: #d32f2f; color: #fff; }
        .btn-pay:hover { background: #b71c1c; box-shadow: 0 4px 10px rgba(211,47,47,0.3); }

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
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; position: relative; z-index: 2; }
        .item-table th { background: #feeef2; color: #d32f2f; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; padding: 12px 15px; border-top: 1px solid #d32f2f; border-bottom: 2px solid #d32f2f; }
        .item-table td { padding: 15px; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; color: #333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-strip { background: #feeef2; padding: 15px 25px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; position: relative; z-index: 2; border: 1px solid #ffcdd2; }
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
        <a href="fees.php" class="btn-control btn-back"><i class="fas fa-chevron-left"></i> Back to Ledger</a>
        <?php if(!empty($razorpay_key_id)): ?>
            <button onclick="payWithRazorpay()" class="btn-control btn-pay"><i class="fas fa-credit-card"></i> Pay Now (₹<?php echo number_format($bill['amount'], 2); ?>)</button>
        <?php else: ?>
            <span style="color:#d32f2f; font-weight:700;"><i class="fas fa-exclamation-triangle"></i> Online Payment Disabled</span>
        <?php endif; ?>
    </div>

    <!-- Printable Invoice Container -->
    <div class="receipt-container">
        
        <!-- Watermark -->
        <div class="watermark">
            UNPAID<br>INVOICE
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
                        <td class="kv-value"><?php echo htmlspecialchars($bill['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Class:</td>
                        <td class="kv-value"><?php echo htmlspecialchars($bill['class_admitted']); ?></td>
                    </tr>
                    <tr>
                        <td class="kv-label">Status:</td>
                        <td class="kv-value" style="color:#2e7d32;"><i class="fas fa-check-circle"></i> Active Scholar</td>
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
                $sno = 1;
                foreach ($remarks as $rem):
                    $rem = trim($rem);
                    $item_desc = $rem;
                    $item_amt = '-';
                    // Try to split by ': ₹' to put amount in the amount column
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
                    <td style="font-weight: 700; border:none; text-align:right;">Subtotal:</td>
                    <td class="text-right" style="border:none; font-weight: 700; color:#d32f2f;">₹ <?php echo number_format($bill['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Total strip -->
        <div class="total-strip">
            <span class="total-label">Total Amount Due</span>
            <span class="total-value">₹ <?php echo number_format($bill['amount'], 2); ?></span>
        </div>

        <!-- Amount in Words -->
        <div class="words-block">
            Amount due in words: <strong><?php echo $amount_in_words; ?></strong>
        </div>

    </div>

    <?php if(!empty($razorpay_key_id)): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        function payWithRazorpay() {
            var options = {
                "key": "<?php echo htmlspecialchars($razorpay_key_id); ?>",
                "amount": <?php echo $bill['amount'] * 100; ?>,
                "currency": "INR",
                "name": "<?php echo htmlspecialchars($school_name); ?>",
                "description": "Tuition Fee for <?php echo htmlspecialchars($bill['month_for']); ?> - <?php echo htmlspecialchars($bill['student_name']); ?>",
                "image": "../assets/logo.png",
                "handler": function (response){
                    var form = document.createElement("form");
                    form.method = "POST";
                    form.action = "verify_payment.php";

                    var input1 = document.createElement("input");
                    input1.type = "hidden";
                    input1.name = "razorpay_payment_id";
                    input1.value = response.razorpay_payment_id;
                    form.appendChild(input1);

                    var input2 = document.createElement("input");
                    input2.type = "hidden";
                    input2.name = "bill_id";
                    input2.value = "<?php echo $bill['id']; ?>";
                    form.appendChild(input2);

                    document.body.appendChild(form);
                    form.submit();
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($_SESSION['parent_name']); ?>",
                    "email": "<?php echo htmlspecialchars($_SESSION['parent_email']); ?>"
                },
                "theme": {
                    "color": "#d32f2f"
                }
            };
            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                alert("Payment failed! Reason: " + response.error.description);
            });
            rzp.open();
        }
    </script>
    <?php endif; ?>
</body>
</html>
