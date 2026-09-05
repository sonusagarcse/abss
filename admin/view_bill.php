<?php
// admin/view_bill.php - Professional Fee Invoice View
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$bill_id = (int)$_GET['id'];

// Fetch bill details with student scholar mode & class admitted
$stmt = $conn->prepare("
    SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, s.guardian_email,
           COALESCE(p.phone, s.phone, '') AS phone, p.parent_name, COALESCE(p.email, s.guardian_email, '') as parent_email
    FROM fees_generated fg
    JOIN students s ON fg.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE fg.id = ?
");
$stmt->bind_param("i", $bill_id);
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

// Function to convert amount to words
if (!function_exists('amountToWords')) {
    function amountToWords($number) {
    $decimal = (int)round(($number - floor($number)) * 100);
    $no = (int)floor($number);
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
        7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
        50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    
    $str = array();
    $digits_length = strlen((string)$no);
    $i = 0;
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number_part = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number_part) {
            $counter = count($str);
            $hundred = ($counter == 1 && !empty($str[0])) ? ' and ' : null;
            $str[] = ($number_part < 21) 
                ? $words[$number_part] . ' ' . $digits[$counter] . ' ' . $hundred
                : $words[floor($number_part / 10) * 10] . ($number_part % 10 ? ' ' . $words[$number_part % 10] : '') . ' ' . $digits[$counter] . ' ' . $hundred;
        } else {
            $str[] = null;
        }
    }
    $Rupees = trim(implode('', array_reverse(array_filter($str))));
    
    $paise = '';
    if ($decimal > 0) {
        if ($decimal < 21) {
            $paise_words = $words[$decimal];
        } else {
            $paise_words = $words[floor($decimal / 10) * 10] . ($decimal % 10 ? ' ' . $words[$decimal % 10] : '');
        }
        $paise = $paise_words . ' Paise';
    }
    
    if (!empty($Rupees) && !empty($paise)) {
        return $Rupees . ' Rupees and ' . $paise . ' Only';
    } elseif (!empty($Rupees)) {
        return $Rupees . ' Rupees Only';
    } elseif (!empty($paise)) {
        return $paise . ' Only';
    }
    return 'Zero Rupees Only';
    }
}

// Calculate dynamic late fine (if enabled in settings)
$fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($bill, $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0, 'rate_per_day' => 5.00];
$fine_amount = ($bill['status'] === 'unpaid') ? $fine_calc['fine_amount'] : 0.00;
$total_payable_amount = (float)$bill['amount'] + $fine_amount;

$amount_in_words = amountToWords($total_payable_amount);
$invoice_no = "ABSS-INV-" . date('Y', strtotime($bill['billing_date'])) . "-" . str_pad($bill['id'], 5, '0', STR_PAD_LEFT);
$is_embed = isset($_GET['embed']) && $_GET['embed'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Invoice - <?php echo $invoice_no; ?> | ABSS Admin</title>
    <?php if (!$is_embed) include 'includes/head_css.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php
$logo_path = __DIR__ . '/../assets/logo.png';
$logo_src = file_exists($logo_path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path)) : '../assets/logo.png';
?>
    <style>
        body { 
            font-family: 'Outfit', sans-serif; 
            background: <?php echo $is_embed ? '#ffffff' : 'radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.06) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(124, 58, 237, 0.06) 0%, transparent 40%), #f8fafc'; ?>; 
            margin: 0; 
            padding: 0;
            -webkit-print-color-adjust: exact; 
        }
        
        .view-bill-wrapper {
            max-width: 880px;
            margin: 0 auto;
            width: 100%;
        }

        .control-bar { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 14px 20px; 
            border-radius: var(--radius-md, 16px); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06); 
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-control { 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 0.86rem; 
            padding: 9px 16px; 
            border-radius: 10px; 
            border: 1px solid #cbd5e1; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 7px; 
            font-family: inherit; 
            transition: all 0.2s ease; 
        }
        .btn-control:hover {
            transform: translateY(-1px);
        }
        .btn-back { background: #f8fafc; color: #1e293b; border-color: #cbd5e1; }
        .btn-back:hover { background: #f1f5f9; color: var(--portal-blue, #2563eb); }

        .receipt-container { 
            background: #ffffff; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); 
            box-sizing: border-box; 
            position: relative; 
            overflow: hidden; 
            border: 1px solid #e2e8f0; 
            width: 100%;
            margin-bottom: 30px;
        }
        
        .watermark { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%) rotate(-30deg); 
            font-size: 6rem; 
            color: rgba(211, 47, 47, 0.04); 
            font-weight: 800; 
            pointer-events: none; 
            text-align: center; 
            width: 100%; 
            z-index: 1; 
            user-select: none; 
            padding: 20px; 
        }

        .receipt-header { 
            display: flex; 
            justify-content: space-between; 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 22px; 
            margin-bottom: 25px; 
            position: relative; 
            z-index: 2; 
            gap: 20px;
        }
        .school-branding { display: flex; align-items: center; gap: 16px; }
        .school-branding img { height: 64px; }
        .school-info h2 { margin: 0 0 4px 0; color: #1e293b; font-size: 1.45rem; font-weight: 800; }
        .school-info p { margin: 0; color: #64748b; font-size: 0.82rem; line-height: 1.4; font-weight: 500; }
        
        .receipt-meta { text-align: right; min-width: 180px; }
        .receipt-title { font-size: 1.25rem; font-weight: 800; color: #dc2626; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
        .receipt-no { font-family: monospace; font-size: 0.92rem; font-weight: 700; color: #334155; margin-bottom: 3px; }
        .receipt-date { font-size: 0.82rem; color: #64748b; font-weight: 600; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 25px; position: relative; z-index: 2; }
        .details-col h4 { margin: 0 0 10px 0; color: #1e293b; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; padding-bottom: 5px; letter-spacing: 0.05em; font-weight: 800; }
        
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 5px 0; font-size: 0.88rem; border: none; background: transparent; }
        .kv-label { color: #64748b; font-weight: 600; width: 38%; }
        .kv-value { color: #0f172a; font-weight: 700; }

        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; position: relative; z-index: 2; }
        .item-table th { background: #fef2f2; color: #b91c1c; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; padding: 10px 12px; border-top: 1px solid #fecaca; border-bottom: 2px solid #fecaca; }
        .item-table td { padding: 12px 14px; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-strip { background: #fef2f2; padding: 14px 22px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 2; border: 1px solid #fecaca; flex-wrap: wrap; gap: 10px; }
        .total-label { font-size: 1.05rem; font-weight: 800; color: #991b1b; }
        .total-value { font-size: 1.35rem; font-weight: 900; color: #991b1b; }

        .words-block { font-size: 0.84rem; color: #64748b; margin-bottom: 30px; font-style: italic; border-left: 3px solid #dc2626; padding-left: 12px; position: relative; z-index: 2; }
        .words-block strong { color: #dc2626; font-style: normal; font-weight: 700; }

        /* Android & Mobile Responsive Rules */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px 10px !important;
                margin-left: 0 !important;
                width: 100% !important;
            }
            .control-bar {
                padding: 12px 14px;
                flex-direction: column;
                gap: 10px;
            }
            .control-bar > div {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .btn-control {
                flex: 1 1 auto;
                justify-content: center;
                font-size: 0.82rem;
                padding: 10px 14px;
            }
            .receipt-container {
                padding: 24px 16px !important;
                border-radius: 14px;
            }
            .receipt-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }
            .school-branding {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .school-branding img {
                height: 50px;
            }
            .school-info h2 {
                font-size: 1.22rem;
            }
            .receipt-meta {
                text-align: left;
                width: 100%;
                border-top: 1px dashed #e2e8f0;
                padding-top: 10px;
            }
            .details-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .item-table th, .item-table td {
                padding: 8px 10px;
                font-size: 0.82rem;
            }
            .total-strip {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .total-value {
                font-size: 1.25rem;
            }
            .watermark {
                font-size: 3.5rem;
            }
        }

        @media print {
            body { background: #fff !important; padding: 0 !important; }
            .sidebar, .mobile-header, .sidebar-overlay, .control-bar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
            .receipt-container { box-shadow: none !important; border: none !important; padding: 10px !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>

<?php if (!$is_embed): ?>
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="view-bill-wrapper">
            <!-- Control Panel -->
            <div class="control-bar">
                <div style="display:flex; gap: 8px; flex-wrap: wrap;">
                    <a href="fees.php" class="btn-control btn-back"><i class="fas fa-chevron-left"></i> Fees Ledger</a>
                    <a href="student_dues.php" class="btn-control btn-back"><i class="fas fa-file-invoice-dollar"></i> Student Dues</a>
                </div>
                <div style="display:flex; gap: 8px; flex-wrap: wrap;">
                    <button onclick="downloadInvoicePDF()" class="btn-control" id="btnDownloadInvoice" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </button>
                    <button onclick="emailInvoicePdf(<?php echo $bill['student_id']; ?>)" class="btn-control" id="btnEmailInvoice" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                        <i class="fas fa-envelope-open-text"></i> Email Bill PDF
                    </button>
                    <button type="button" onclick="shareInvoiceWhatsAppDirect()" class="btn-control" id="btnShareWaImg" style="background:#25d366; color:#ffffff; font-weight:800; border:none; box-shadow: 0 4px 12px rgba(37,211,102,0.25);">
                        <i class="fab fa-whatsapp"></i> 📲 Share on WhatsApp
                    </button>
                    <button onclick="window.print()" class="btn-control btn-back"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
<?php endif; ?>

    <!-- Printable Invoice Container -->
    <div class="receipt-container" id="receiptContainer">
        
        <!-- Watermark -->
        <div class="watermark">
            <?php echo strtoupper($bill['status']); ?><br>INVOICE
        </div>
        
        <!-- Header -->
        <div class="receipt-header">
            <div class="school-branding">
                <img src="<?php echo $logo_src; ?>" alt="ABSS School Logo">
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
                <h4>Bill To (Parent)</h4>
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

    </div> <!-- Close #receiptContainer -->

<?php if (!$is_embed): ?>
        </div> <!-- Close .view-bill-wrapper -->
    </main> <!-- Close .main-content -->
<?php endif; ?>

    <script>
        function downloadInvoicePDF() {
            const btn = document.getElementById('btnDownloadInvoice');
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rendering PDF...';
            }

            const element = document.getElementById('receiptContainer');
            const invoiceNo = <?php echo json_encode($invoice_no); ?>;
            const studentName = <?php echo json_encode(preg_replace('/[^a-zA-Z0-9]+/', '_', $bill['student_name'])); ?>;
            
            const opt = {
                margin: [5, 5, 5, 5],
                filename: `Invoice_${invoiceNo}_${studentName}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }).catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
                console.error("PDF generation error:", err);
                window.location.href = "ajax_send_due_email.php?action=download_bill_pdf&bill_id=<?php echo $bill['id']; ?>";
            });
        }

        // Auto trigger download if requested in query parameter
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('download') === '1' || urlParams.get('auto_download') === '1') {
                setTimeout(downloadInvoicePDF, 300);
            }
        });

        function emailInvoicePdf(studentId) {
            const defaultEmail = <?php echo json_encode(trim($bill['parent_email'] ?: ($bill['guardian_email'] ?? ''))); ?>;
            const promptEmail = prompt("Enter parent/guardian email address to deliver this Bill PDF statement:", defaultEmail);
            if (promptEmail === null) return;
            
            const cleanEmail = promptEmail.trim();
            if (!cleanEmail || !cleanEmail.includes('@')) {
                alert("Please enter a valid email address.");
                return;
            }

            const btn = document.getElementById('btnEmailInvoice');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Receipt PDF...';
            }

            const element = document.getElementById('receiptContainer');
            const invoiceNo = <?php echo json_encode($invoice_no); ?>;
            const studentName = <?php echo json_encode(preg_replace('/[^a-zA-Z0-9]+/', '_', $bill['student_name'])); ?>;
            const billId = <?php echo (int)$bill['id']; ?>;

            const opt = {
                margin: [5, 5, 5, 5],
                filename: `Invoice_${invoiceNo}_${studentName}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).outputPdf('datauristring').then(pdfDataUri => {
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Delivering Email...';
                }

                const formData = new FormData();
                formData.append('action', 'send_student_due_email');
                formData.append('student_id', studentId);
                formData.append('bill_id', billId);
                formData.append('email', cleanEmail);
                formData.append('pdf_base64', pdfDataUri);

                return fetch('ajax_send_due_email.php', {
                    method: 'POST',
                    body: formData
                });
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-envelope-open-text"></i> Email Bill PDF';
                }
                if (data && data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('⚠️ ' + ((data && data.error) ? data.error : 'Failed to send email.'));
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-envelope-open-text"></i> Email Bill PDF';
                }
                alert('Dispatch error: ' + err.message);
            });
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
    <script src="../js/invoice-share-bridge.js?v=<?php echo time(); ?>"></script>
</body>
</html>
