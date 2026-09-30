<?php
// parent/view_bill.php - Professional Parent Fee Invoice View with Mobile Responsiveness & Razorpay Integration
require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];
if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$bill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$inv_param = isset($_GET['inv']) ? trim($_GET['inv']) : (isset($_GET['invoice']) ? trim($_GET['invoice']) : '');

if ($bill_id > 0) {
    $stmt = $conn->prepare("
        SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, p.parent_name, p.phone, p.email as parent_email
        FROM fees_generated fg
        JOIN students s ON fg.student_id = s.id
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE fg.id = ? AND s.parent_id = ?
    ");
    $stmt->bind_param("ii", $bill_id, $pid);
} else {
    $stmt = $conn->prepare("
        SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, p.parent_name, p.phone, p.email as parent_email
        FROM fees_generated fg
        JOIN students s ON fg.student_id = s.id
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE fg.invoice_no = ? AND s.parent_id = ?
    ");
    $stmt->bind_param("si", $inv_param, $pid);
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

// Razorpay Key Configuration
$razorpay_key = $settings['razorpay_key_id'] ?? '';
$razorpay_secret = $settings['razorpay_key_secret'] ?? '';

// Function to convert amount to words
if (!function_exists('amountToWords')) {
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

// Calculate dynamic late fine
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
if ($bill['status'] === 'paid' && (float)$bill['amount'] <= 0) {
    $remaining_balance = 0.00;
    $total_billed_amount = ($total_charges_sum > 0) ? $total_charges_sum : $total_paid_so_far;
} else {
    $total_billed_amount = ($total_charges_sum > 0) ? $total_charges_sum : ((float)$bill['amount'] + $total_paid_so_far);
    $calculated_balance = max(0, $total_billed_amount - $total_paid_so_far);
    $base_due = ((float)$bill['amount'] > 0) ? (float)$bill['amount'] : $calculated_balance;
    $remaining_balance = $base_due + (float)$fine_amount;
}
$total_payable_amount = $remaining_balance;

$amount_in_words = amountToWords($remaining_balance > 0 ? $remaining_balance : $total_billed_amount);
$invoice_no = get_invoice_no($bill);

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        }

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
        </div> <!-- Close .table-responsive -->

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

        <?php if ($remaining_balance > 0): ?>
            <!-- Highlighted Pay Now Action Block Below Total Amount -->
            <div class="receipt-paynow-highlight-box" style="margin-top: 25px; padding: 22px 24px; background: linear-gradient(135deg, #fef2f2 0%, #fff7ed 100%); border: 2px dashed #f87171; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.08);">
                <div>
                    <div style="font-size: 1.18rem; font-weight: 900; color: #991b1b; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-circle" style="color: #dc2626; font-size: 1.3rem;"></i> Immediate Fee Payment Due
                    </div>
                    <p style="margin: 4px 0 0; color: #475569; font-size: 0.88rem; font-weight: 600;">
                        Payable Balance: <strong style="color: #dc2626; font-size: 1.05rem;">₹ <?php echo number_format($remaining_balance, 2); ?></strong>
                        <?php if ($total_paid_so_far > 0): ?>
                            (Original: ₹ <?php echo number_format($total_billed_amount, 2); ?> • Already Paid: ₹ <?php echo number_format($total_paid_so_far, 2); ?>)
                        <?php elseif ($fine_amount > 0): ?>
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
    <script src="../js/invoice-share-bridge.js?v=<?php echo time(); ?>"></script>
</body>
</html>
