<?php
// admin/view_bill.php - Professional Fee Invoice View
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$bill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$inv_param = isset($_GET['inv']) ? trim($_GET['inv']) : (isset($_GET['invoice']) ? trim($_GET['invoice']) : '');

if ($bill_id > 0) {
    $stmt = $conn->prepare("
        SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, s.guardian_email,
               COALESCE(p.phone, s.phone, '') AS phone, p.parent_name, COALESCE(p.email, s.guardian_email, '') as parent_email
        FROM fees_generated fg
        JOIN students s ON fg.student_id = s.id
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE fg.id = ?
    ");
    $stmt->bind_param("i", $bill_id);
} else {
    $stmt = $conn->prepare("
        SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, s.guardian_email,
               COALESCE(p.phone, s.phone, '') AS phone, p.parent_name, COALESCE(p.email, s.guardian_email, '') as parent_email
        FROM fees_generated fg
        JOIN students s ON fg.student_id = s.id
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE fg.invoice_no = ?
    ");
    $stmt->bind_param("s", $inv_param);
}
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

// Fetch all recorded payments for this student for instant O(1) receipt & payment mode lookup
$student_payments = [];
if (!empty($bill['student_id'])) {
    $pay_res = $conn->query("SELECT id, amount, payment_date, payment_method FROM fee_payments WHERE student_id = " . (int)$bill['student_id']);
    if ($pay_res) {
        while ($p_row = $pay_res->fetch_assoc()) {
            $student_payments[$p_row['id']] = $p_row;
        }
    }
}

// Helper to classify payment method and return badge metadata
if (!function_exists('classify_payment_info')) {
    function classify_payment_info($rem, $student_payments = [], $paid_num = 0.0) {
        $rcpt_id = 0;
        if (preg_match('/Rcpt\s*#?([0-9]+)/i', $rem, $rm)) {
            $rcpt_id = (int)$rm[1];
        }

        // If no rcpt_id in remark, attempt to match via payment_date and amount in student_payments
        if ($rcpt_id == 0 && !empty($student_payments)) {
            $rem_date = '';
            if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $rem, $dm)) {
                $rem_date = $dm[1];
            }
            if ($rem_date && $paid_num > 0) {
                foreach ($student_payments as $pid => $sp) {
                    if ($sp['payment_date'] === $rem_date && abs((float)$sp['amount'] - (float)$paid_num) < 0.01) {
                        $rcpt_id = (int)$pid;
                        break;
                    }
                }
            }
        }
        
        $raw_method = '';
        if ($rcpt_id > 0 && isset($student_payments[$rcpt_id])) {
            $raw_method = $student_payments[$rcpt_id]['payment_method'];
        } elseif (preg_match('/via\s+([A-Za-z0-9_\-\s\(\):]+?)\s+on/i', $rem, $vm)) {
            $raw_method = trim($vm[1]);
        } elseif (stripos($rem, 'online') !== false || stripos($rem, 'razorpay') !== false || stripos($rem, 'upi') !== false || stripos($rem, 'phonepe') !== false || stripos($rem, 'gpay') !== false || stripos($rem, 'paytm') !== false) {
            $raw_method = 'Online';
        } elseif (stripos($rem, 'cash') !== false) {
            $raw_method = 'Cash';
        } elseif (stripos($rem, 'bank') !== false || stripos($rem, 'cheque') !== false || stripos($rem, 'neft') !== false) {
            $raw_method = 'Bank Transfer';
        } else {
            $raw_method = 'Cash';
        }

        $mode_type = 'cash';
        $mode_label = 'Cash';
        $txn_id = '';

        if (stripos($raw_method, 'razorpay') !== false) {
            $mode_type = 'online';
            $mode_label = 'Online (Razorpay)';
            if (preg_match('/pay_[a-zA-Z0-9]+/', $raw_method, $txnm)) {
                $txn_id = $txnm[0];
            }
        } elseif (stripos($raw_method, 'phonepe') !== false) {
            $mode_type = 'online';
            $mode_label = 'Online (PhonePe)';
        } elseif (stripos($raw_method, 'gpay') !== false || stripos($raw_method, 'google pay') !== false) {
            $mode_type = 'online';
            $mode_label = 'Online (GPay)';
        } elseif (stripos($raw_method, 'paytm') !== false) {
            $mode_type = 'online';
            $mode_label = 'Online (Paytm)';
        } elseif (stripos($raw_method, 'upi') !== false || stripos($raw_method, 'qr') !== false) {
            $mode_type = 'online';
            $mode_label = 'Online (UPI)';
        } elseif (stripos($raw_method, 'online') !== false || stripos($raw_method, 'netbanking') !== false || stripos($raw_method, 'card') !== false) {
            $mode_type = 'online';
            $mode_label = 'Online';
        } elseif (stripos($raw_method, 'cash') !== false) {
            $mode_type = 'cash';
            $mode_label = 'Cash';
        } elseif (stripos($raw_method, 'bank') !== false || stripos($raw_method, 'neft') !== false || stripos($raw_method, 'rtgs') !== false || stripos($raw_method, 'imps') !== false) {
            $mode_type = 'bank';
            $mode_label = 'Bank Transfer';
        } elseif (stripos($raw_method, 'cheque') !== false || stripos($raw_method, 'dd') !== false) {
            $mode_type = 'cheque';
            $mode_label = 'Cheque / DD';
        } else {
            $mode_type = 'other';
            $mode_label = $raw_method;
        }

        return [
            'rcpt_id' => $rcpt_id,
            'raw_method' => $raw_method,
            'mode_type' => $mode_type,
            'mode_label' => $mode_label,
            'txn_id' => $txn_id
        ];
    }
}

// Calculate dynamic late fine (if enabled in settings)
$fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($bill, $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0, 'rate_per_day' => 5.00];
$fine_amount = ($bill['status'] === 'unpaid') ? $fine_calc['fine_amount'] : 0.00;

// Pre-parse all items and payment deductions from remark string
$remarks = explode('|', $bill['remark'] ? $bill['remark'] : 'Tuition Fee');
$parsed_items = [];
$total_charges_sum = 0.0;
$total_paid_so_far = 0.0;
$payment_records = [];

foreach ($remarks as $rem) {
    $rem = trim($rem);
    if (strpos($rem, 'Auto-generated Bill.') !== false) {
        $rem = trim(str_replace('Auto-generated Bill.', '', $rem));
    }
    if (empty($rem)) continue;

    $is_payment_row = (
        stripos($rem, 'payment received') !== false || 
        stripos($rem, 'partial payment') !== false || 
        stripos($rem, 'payment of') !== false || 
        stripos($rem, 'paid') !== false || 
        strpos($rem, '-₹') !== false
    );

    if ($is_payment_row) {
        // Extract paid amount
        $paid_num = 0.0;
        if (preg_match('/\(-?\s*[₹Rs\.]*\s*([0-9\.,]+)\)/i', $rem, $amt_match)) {
            $paid_num = (float)str_replace(',', '', $amt_match[1]);
        } elseif (preg_match('/[₹Rs\.]\s*([0-9\.,]+)/i', $rem, $amt_match)) {
            $paid_num = (float)str_replace(',', '', $amt_match[1]);
        }
        $total_paid_so_far += $paid_num;

        // Extract payment date
        $pay_date_formatted = $bill['month_for'];
        if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $rem, $d_match)) {
            $pay_date_formatted = date('d M, Y', strtotime($d_match[0]));
        }

        // Classify payment method and get tagging
        $pay_info = classify_payment_info($rem, $student_payments, $paid_num);
        
        $payment_records[] = [
            'amount' => $paid_num,
            'date' => $pay_date_formatted,
            'rcpt_id' => $pay_info['rcpt_id'],
            'mode_type' => $pay_info['mode_type'],
            'mode_label' => $pay_info['mode_label'],
            'txn_id' => $pay_info['txn_id'],
            'raw_method' => $pay_info['raw_method']
        ];

        $parsed_items[] = [
            'is_payment' => true,
            'desc' => 'Payment Received',
            'month' => $pay_date_formatted,
            'amount_formatted' => '-₹ ' . number_format($paid_num, 2),
            'amount_num' => $paid_num,
            'pay_info' => $pay_info
        ];
    } else {
        // Charge row
        $item_month = $bill['month_for'];
        if (preg_match('/\((.*?)\)/', $rem, $m_match)) {
            $item_month = trim($m_match[1]);
            $rem = trim(str_replace($m_match[0], '', $rem));
        } elseif (preg_match('/\[(.*?)\]/', $rem, $m_match)) {
            $item_month = trim($m_match[1]);
            $rem = trim(str_replace($m_match[0], '', $rem));
        }

        $item_desc = '';
        $charge_num = 0.0;
        if (strpos($rem, ': ₹') !== false) {
            $parts = explode(': ₹', $rem);
            $item_desc = trim($parts[0]);
            $charge_num = (float)str_replace(',', '', trim($parts[1]));
        } elseif (strpos($rem, ':') !== false) {
            $parts = explode(':', $rem);
            $item_desc = trim($parts[0]);
            $charge_num = (float)str_replace(',', '', trim($parts[1]));
        } else {
            $item_desc = trim($rem);
            $charge_num = (float)$bill['amount'];
        }

        if (preg_match('/₹\s*[0-9\.,]+/', $item_desc, $amt_match)) {
            $item_desc = trim(str_replace($amt_match[0], '', $item_desc));
        }
        if (empty($item_desc)) $item_desc = "Tuition Fee";

        $total_charges_sum += $charge_num;

        $parsed_items[] = [
            'is_payment' => false,
            'desc' => $item_desc,
            'month' => $item_month,
            'amount_formatted' => '₹ ' . number_format($charge_num, 2),
            'amount_num' => $charge_num,
            'pay_info' => null
        ];
    }
}

// Compute accurate totals: original billed, total paid, and net balance due
if ($total_paid_so_far > 0) {
    $total_billed_amount = ($total_charges_sum > 0) ? $total_charges_sum : ((float)$bill['amount'] + $total_paid_so_far);
} else {
    $total_billed_amount = ($total_charges_sum > 0) ? $total_charges_sum : (float)$bill['amount'];
}
$remaining_balance = (float)$bill['amount'] + (float)$fine_amount;
$total_payable_amount = $remaining_balance;

$amount_in_words = amountToWords($remaining_balance > 0 ? $remaining_balance : $total_billed_amount);
$invoice_no = get_invoice_no($bill);
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

        /* Payment mode and receipt tagging */
        .pay-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2.5px 8px;
            border-radius: 6px;
            letter-spacing: 0.3px;
            vertical-align: middle;
            line-height: 1.3;
        }
        .pay-mode-online {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .pay-mode-cash {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .pay-mode-bank {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .pay-mode-other {
            background: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #e9d5ff;
        }
        .rcpt-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 5px;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            line-height: 1.3;
        }
        .txn-ref-badge {
            font-family: monospace;
            font-size: 0.68rem;
            font-weight: 700;
            color: #0284c7;
            background: #f0f9ff;
            border: 1px dashed #7dd3fc;
            padding: 1px 6px;
            border-radius: 4px;
        }

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
            <?php 
            if ($remaining_balance > 0 && $total_paid_so_far > 0) {
                echo 'PARTIAL PAID<br>INVOICE';
            } elseif ($remaining_balance <= 0) {
                echo 'PAID<br>INVOICE';
            } else {
                echo 'UNPAID<br>INVOICE';
            }
            ?>
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
                <div style="margin-top: 6px;">
                    <?php if ($remaining_balance > 0 && $total_paid_so_far > 0): ?>
                        <span class="pay-mode-badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; font-size:0.75rem; padding:3px 9px;">
                            <i class="fas fa-clock"></i> PARTIALLY PAID (₹<?php echo number_format($remaining_balance, 2); ?> Due)
                        </span>
                    <?php elseif ($remaining_balance <= 0): ?>
                        <span class="pay-mode-badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-size:0.75rem; padding:3px 9px;">
                            <i class="fas fa-check-circle"></i> FULLY PAID
                        </span>
                    <?php else: ?>
                        <span class="pay-mode-badge" style="background:#fff1f2; color:#e11d48; border:1px solid #ffe4e6; font-size:0.75rem; padding:3px 9px;">
                            <i class="fas fa-exclamation-circle"></i> UNPAID BILL
                        </span>
                    <?php endif; ?>
                </div>
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
                $sno = 1;
                foreach ($parsed_items as $item):
                    if ($item['is_payment']):
                        $pinfo = $item['pay_info'];
                        $mtype = $pinfo['mode_type'];
                        $mlabel = $pinfo['mode_label'];
                        $txid = $pinfo['txn_id'];
                ?>
                    <tr style="background: #f0fdf4; border-bottom: 1px solid #bbf7d0;">
                        <td class="text-center" style="color: #15803d; font-weight: 800; vertical-align: middle;"><?php echo $sno++; ?></td>
                        <td style="font-weight: 700; color: #15803d; vertical-align: middle;">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-check-circle" style="color: #16a34a; font-size: 0.95rem;"></i>
                                    <span>Payment Received</span>
                                    <?php if (!empty($pinfo['rcpt_id'])): ?>
                                        <span class="rcpt-badge"><i class="fas fa-receipt"></i> Rcpt #<?php echo $pinfo['rcpt_id']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span class="pay-mode-badge <?php echo ($mtype === 'online' ? 'pay-mode-online' : ($mtype === 'cash' ? 'pay-mode-cash' : ($mtype === 'bank' ? 'pay-mode-bank' : 'pay-mode-other'))); ?>">
                                        <?php if ($mtype === 'online'): ?>
                                            <i class="fas fa-bolt"></i> <?php echo htmlspecialchars($mlabel); ?>
                                        <?php elseif ($mtype === 'cash'): ?>
                                            <i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($mlabel); ?>
                                        <?php else: ?>
                                            <i class="fas fa-university"></i> <?php echo htmlspecialchars($mlabel); ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (!empty($txid)): ?>
                                        <span class="txn-ref-badge" title="Razorpay Payment ID"><?php echo htmlspecialchars($txid); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 700; color: #166534; vertical-align: middle;">
                            <?php echo htmlspecialchars($item['month']); ?>
                        </td>
                        <td class="text-right" style="font-weight: 800; color: #15803d; font-size: 0.95rem; vertical-align: middle;">
                            <?php echo htmlspecialchars($item['amount_formatted']); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td class="text-center" style="vertical-align: middle;"><?php echo $sno++; ?></td>
                        <td style="font-weight: 700; color: #1a237e; vertical-align: middle;">
                            <?php echo htmlspecialchars($item['desc']); ?>
                        </td>
                        <td style="font-weight: 700; color: #2563eb; vertical-align: middle;">
                            <?php echo htmlspecialchars($item['month']); ?>
                        </td>
                        <td class="text-right" style="font-weight: 800; color: #b71c1c; vertical-align: middle;">
                            <?php echo htmlspecialchars($item['amount_formatted']); ?>
                        </td>
                    </tr>
                <?php 
                    endif;
                endforeach; 
                ?>
                <?php if ($fine_amount > 0): ?>
                    <tr style="background: #fff7ed;">
                        <td class="text-center" style="font-weight: 700; color: #ea580c; vertical-align: middle;"><?php echo $sno++; ?></td>
                        <td style="font-weight: 700; color: #9a3412; vertical-align: middle;">
                            <div style="display: block; font-size: 0.92rem; font-weight: 800; color: #9a3412; line-height: 1.35; margin-bottom: 3px;">
                                <i class="fas fa-coins" style="color:#ea580c; margin-right: 5px;"></i>Late Fine (विलंब शुल्क)
                            </div>
                            <div style="display: block;">
                                <span style="display: inline-block; font-size: 0.72rem; font-weight: 700; color: #c2410c; background: #ffedd5; border: 1px solid #fed7aa; padding: 2px 8px; border-radius: 4px; line-height: 1.3;">
                                    <?php echo $fine_calc['overdue_days']; ?> Days Overdue @ ₹<?php echo number_format($fine_calc['rate_per_day'], 2); ?>/day
                                </span>
                            </div>
                        </td>
                        <td style="font-weight: 700; color: #ea580c; vertical-align: middle;">
                            <?php echo htmlspecialchars($bill['month_for']); ?>
                        </td>
                        <td class="text-right" style="font-weight: 800; color:#ea580c; vertical-align: middle;">
                            ₹ <?php echo number_format($fine_amount, 2); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Total Strip -->
        <div class="total-strip <?php echo ($remaining_balance <= 0 ? 'is-paid' : ''); ?>" style="margin-top: 20px; <?php echo ($remaining_balance <= 0 ? 'background: #f0fdf4; border-color: #bbf7d0;' : ''); ?>">
            <div>
                <span class="total-label" style="<?php echo ($remaining_balance <= 0 ? 'color: #166534;' : ''); ?>">
                    <?php if ($remaining_balance <= 0): ?>
                        <i class="fas fa-check-circle" style="color:#16a34a; margin-right:6px;"></i> Paid in Full (कुल भुगतान):
                    <?php elseif ($total_paid_so_far > 0): ?>
                        <i class="fas fa-exclamation-circle" style="color:#dc2626; margin-right:6px;"></i> Remaining Balance Due (शेष बकाया राशि):
                    <?php else: ?>
                        <i class="fas fa-receipt" style="color:#dc2626; margin-right:6px;"></i> Total Amount Due (कुल बकाया राशि):
                    <?php endif; ?>
                </span>
                <?php if ($total_paid_so_far > 0): ?>
                    <div style="font-size: 0.8rem; font-weight: 600; color: #475569; margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span>Billed: ₹<?php echo number_format($total_billed_amount, 2); ?></span>
                        <span>•</span>
                        <span style="color: #15803d; font-weight: 700;">Paid: -₹<?php echo number_format($total_paid_so_far, 2); ?></span>
                        <?php foreach ($payment_records as $prec): ?>
                            <span class="pay-mode-badge <?php echo ($prec['mode_type'] === 'online' ? 'pay-mode-online' : 'pay-mode-cash'); ?>" style="font-size: 0.65rem; padding: 1px 6px;">
                                <?php echo htmlspecialchars($prec['mode_label']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="total-value" style="color: <?php echo ($remaining_balance > 0 ? '#dc2626' : '#15803d'); ?>;">
                ₹ <?php echo number_format(max(0, $remaining_balance), 2); ?>
            </div>
        </div>

        <!-- Amount in Words -->
        <div class="words-block">
            <?php if ($remaining_balance > 0): ?>
                Remaining balance due in words: <strong><?php echo amountToWords($remaining_balance); ?></strong>
            <?php else: ?>
                Invoice payment status: <strong>Paid in Full (Zero balance due)</strong>
            <?php endif; ?>
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
                fineAmount: <?php echo json_encode($fine_amount > 0 ? number_format($fine_amount, 2) : ''); ?>,
                overdueDays: <?php echo json_encode($fine_amount > 0 ? (int)$fine_calc['overdue_days'] : 0); ?>,
                date: <?php echo json_encode(date('d M, Y', strtotime($bill['billing_date']))); ?>,
                phone: <?php echo json_encode($bill['phone'] ?? ''); ?>,
                btnId: 'btnShareWaImg'
            });
        }
    </script>
    <script src="../js/invoice-share-bridge.js?v=<?php echo time(); ?>"></script>
</body>
</html>
