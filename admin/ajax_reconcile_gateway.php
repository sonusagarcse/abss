<?php
// admin/ajax_reconcile_gateway.php - Server-side Razorpay Gateway Reconciliation Engine
header('Content-Type: application/json; charset=UTF-8');
require_once 'includes/auth.php';

$settings = getAllSettings();
$key_id = $settings['razorpay_key_id'] ?? '';
$key_secret = $settings['razorpay_key_secret'] ?? '';

if (empty($key_id) || empty($key_secret)) {
    echo json_encode([
        'success' => false,
        'message' => 'Razorpay API credentials (Key ID / Secret) are not configured in system settings.'
    ]);
    exit();
}

// Fetch last 100 payments from Razorpay API
$url = "https://api.razorpay.com/v1/payments?count=100";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reach Razorpay API (HTTP Code: ' . $http_code . '). Please verify your internet connection and API keys.'
    ]);
    exit();
}

$data = json_decode($response, true);
if (!isset($data['items']) || !is_array($data['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid response structure from Razorpay.'
    ]);
    exit();
}

$scanned = 0;
$already_synced = 0;
$reconciled = 0;
$orphans = 0;
$reconciled_details = [];
$orphan_details = [];

foreach ($data['items'] as $item) {
    $scanned++;
    $pay_id = $item['id'] ?? '';
    $status = $item['status'] ?? '';

    // Only process captured payments
    if ($status !== 'captured' && $status !== 'authorized') {
        continue;
    }

    $amount = round(((int)($item['amount'] ?? 0)) / 100, 2);
    $notes = $item['notes'] ?? [];
    $email = trim($item['email'] ?? '');
    $contact = trim($item['contact'] ?? '');

    // Check if payment already exists in fee_payments
    $check = $conn->prepare("SELECT id FROM fee_payments WHERE payment_method LIKE ? LIMIT 1");
    $needle = "%$pay_id%";
    $check->bind_param("s", $needle);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $already_synced++;
        continue;
    }

    // Payment is captured in Razorpay but MISSING in our database!
    // Try to resolve student and bill
    $bill_id = !empty($notes['bill_id']) ? (int)$notes['bill_id'] : 0;
    $student_id = !empty($notes['student_id']) ? (int)$notes['student_id'] : 0;

    // Fallback: match by parent phone or email if notes are missing
    if ($student_id <= 0 && (!empty($contact) || !empty($email))) {
        $clean_phone = preg_replace('/[^0-9]/', '', $contact);
        $phone_last10 = (strlen($clean_phone) >= 10) ? substr($clean_phone, -10) : $clean_phone;

        $p_query = $conn->prepare("
            SELECT s.id AS student_id 
            FROM students s 
            LEFT JOIN parents p ON s.parent_id = p.id 
            WHERE (s.phone LIKE ? OR p.phone LIKE ? OR s.guardian_email = ? OR p.email = ?) 
              AND (s.status = 'active' OR s.status IS NULL)
            ORDER BY s.id DESC LIMIT 1
        ");
        $phone_param = "%$phone_last10%";
        $p_query->bind_param("ssss", $phone_param, $phone_param, $email, $email);
        $p_query->execute();
        $p_res = $p_query->get_result()->fetch_assoc();
        if ($p_res) {
            $student_id = (int)$p_res['student_id'];
        }
    }

    // Resolve bill
    $bill = null;
    if ($bill_id > 0) {
        $b_stmt = $conn->prepare("SELECT fg.*, s.name AS student_name, s.parent_id, p.parent_name, p.email AS parent_email FROM fees_generated fg JOIN students s ON fg.student_id = s.id LEFT JOIN parents p ON s.parent_id = p.id WHERE fg.id = ?");
        $b_stmt->bind_param("i", $bill_id);
        $b_stmt->execute();
        $bill = $b_stmt->get_result()->fetch_assoc();
    } elseif ($student_id > 0) {
        $b_stmt = $conn->prepare("SELECT fg.*, s.name AS student_name, s.parent_id, p.parent_name, p.email AS parent_email FROM fees_generated fg JOIN students s ON fg.student_id = s.id LEFT JOIN parents p ON s.parent_id = p.id WHERE fg.student_id = ? AND fg.status = 'unpaid' ORDER BY fg.billing_date ASC, fg.id ASC LIMIT 1");
        $b_stmt->bind_param("i", $student_id);
        $b_stmt->execute();
        $bill = $b_stmt->get_result()->fetch_assoc();
        if ($bill) {
            $bill_id = (int)$bill['id'];
        }
    }

    if (!$bill) {
        // Orphan payment that cannot be automatically matched
        $orphans++;
        $orphan_details[] = [
            'payment_id' => $pay_id,
            'amount' => $amount,
            'contact' => $contact,
            'email' => $email,
            'date' => date('d M Y, h:i A', $item['created_at'])
        ];
        continue;
    }

    // Auto-Reconcile Payment into Database
    $conn->begin_transaction();
    try {
        $sid = (int)$bill['student_id'];
        $month = $bill['month_for'];
        $payment_date = date('Y-m-d', $item['created_at']);
        $method = 'Online (Razorpay: ' . $pay_id . ')';

        // 1. Insert fee_payments
        $pay_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
        $pay_stmt->bind_param("idsss", $sid, $amount, $payment_date, $month, $method);
        $pay_stmt->execute();
        $new_pay_id = $conn->insert_id;

        // 2. Update fees_generated
        $existing_rem = trim($bill['remark'] ?? '');
        $payment_tag = "Payment received via Online (Razorpay Reconciled) on $payment_date (-₹" . number_format($amount, 2) . ") (Rcpt #$new_pay_id)";
        $new_remark = !empty($existing_rem) ? ($existing_rem . " | " . $payment_tag) : $payment_tag;
        
        $new_balance = max(0, (float)$bill['amount'] - $amount);
        $new_status = ($new_balance <= 0) ? 'paid' : 'unpaid';

        $update_bill = $conn->prepare("UPDATE fees_generated SET amount = ?, status = ?, remark = ? WHERE id = ?");
        $update_bill->bind_param("dssi", $new_balance, $new_status, $new_remark, $bill_id);
        $update_bill->execute();

        // 3. Log webhook audit
        $log_evt = 'rec_' . time() . '_' . rand(1000, 9999);
        $log_stmt = $conn->prepare("INSERT INTO gateway_webhook_logs (event_id, event_type, payment_id, order_id, bill_id, amount, status) VALUES (?, 'gateway.reconciliation', ?, ?, ?, ?, 'processed')");
        $ord_id = $item['order_id'] ?? '';
        $log_stmt->bind_param("sssid", $log_evt, $pay_id, $ord_id, $bill_id, $amount);
        $log_stmt->execute();

        $conn->commit();

        $reconciled++;
        $reconciled_details[] = [
            'payment_id' => $pay_id,
            'amount' => $amount,
            'student_name' => $bill['student_name'],
            'invoice_id' => $bill_id,
            'receipt_id' => $new_pay_id
        ];

        if (function_exists('log_activity')) {
            log_activity('gateway_payment_reconciled', "Admin reconciled missing gateway payment $pay_id of ₹$amount for student {$bill['student_name']} (Invoice #$bill_id)");
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Reconciliation Error for $pay_id: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'scanned' => $scanned,
    'already_synced' => $already_synced,
    'reconciled' => $reconciled,
    'orphans' => $orphans,
    'reconciled_details' => $reconciled_details,
    'orphan_details' => $orphan_details
]);
