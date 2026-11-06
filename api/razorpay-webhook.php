<?php
// api/razorpay-webhook.php - Robust Server-to-Server Webhook Endpoint for Zero-Drop Payment Capture
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
$conn = getDB();

$settings = getAllSettings();
$key_id = $settings['razorpay_key_id'] ?? '';
$key_secret = $settings['razorpay_key_secret'] ?? '';
$webhook_secret = $settings['razorpay_webhook_secret'] ?? '';

// 1. Read Raw Payload & Signature Header
$raw_payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($raw_payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty webhook payload']);
    exit();
}

// 2. Cryptographic Signature Verification (if webhook secret or key secret is configured)
$secret_to_verify = !empty($webhook_secret) ? $webhook_secret : $key_secret;
if (!empty($secret_to_verify) && !empty($signature)) {
    $expected_sig = hash_hmac('sha256', $raw_payload, $secret_to_verify);
    if (!hash_equals($expected_sig, $signature)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature']);
        exit();
    }
}

// 3. Parse JSON Event Payload
$data = json_decode($raw_payload, true);
if (!$data || empty($data['event'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON structure']);
    exit();
}

$event = $data['event'];
$event_id = $data['event_id'] ?? ($data['id'] ?? ('evt_' . time() . '_' . rand(1000, 9999)));

// Only process payment capture / order paid events
if ($event !== 'payment.captured' && $event !== 'order.paid') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => "Event $event ignored"]);
    exit();
}

// Extract Payment Details
$payment_entity = null;
if (isset($data['payload']['payment']['entity'])) {
    $payment_entity = $data['payload']['payment']['entity'];
} elseif (isset($data['payload']['order']['entity'])) {
    // If order.paid and payments array exists
    $order_entity = $data['payload']['order']['entity'];
    if (!empty($data['payload']['order']['entity']['payments'])) {
        $payment_entity = $data['payload']['order']['entity']['payments'][0] ?? null;
    }
}

if (!$payment_entity) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => 'No payment entity found in payload']);
    exit();
}

$payment_id = $payment_entity['id'] ?? '';
$order_id = $payment_entity['order_id'] ?? '';
$amount_paise = (int)($payment_entity['amount'] ?? 0);
$paid_amount = round($amount_paise / 100, 2);
$notes = $payment_entity['notes'] ?? [];

$bill_id = !empty($notes['bill_id']) ? (int)$notes['bill_id'] : 0;
$student_id = !empty($notes['student_id']) ? (int)$notes['student_id'] : 0;

// 4. Idempotency Check: Was this payment already recorded?
$dup_check = $conn->prepare("SELECT id FROM fee_payments WHERE payment_method LIKE ? LIMIT 1");
$pay_needle = "%$payment_id%";
$dup_check->bind_param("s", $pay_needle);
$dup_check->execute();
$dup_res = $dup_check->get_result();

if ($dup_res->num_rows > 0) {
    // Already recorded (e.g. by client-side verify_payment.php)
    $existing_id = $dup_res->fetch_assoc()['id'];
    
    // Log as processed / ignored in gateway_webhook_logs
    $log_stmt = $conn->prepare("INSERT INTO gateway_webhook_logs (event_id, event_type, payment_id, order_id, bill_id, amount, payload, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, 'ignored', 'Already recorded via Rcpt #$existing_id') ON DUPLICATE KEY UPDATE status='ignored'");
    $log_stmt->bind_param("ssssids", $event_id, $event, $payment_id, $order_id, $bill_id, $paid_amount, $raw_payload);
    $log_stmt->execute();

    http_response_code(200);
    echo json_encode([
        'status' => 'already_recorded',
        'message' => "Payment $payment_id is already registered as Receipt #$existing_id",
        'receipt_id' => $existing_id
    ]);
    exit();
}

// 5. Resolve Bill if bill_id was not explicitly in notes
if ($bill_id <= 0 && $student_id > 0) {
    // Attempt to locate oldest unpaid bill for this student
    $find_bill = $conn->prepare("SELECT id FROM fees_generated WHERE student_id = ? AND status = 'unpaid' ORDER BY billing_date ASC, id ASC LIMIT 1");
    $find_bill->bind_param("i", $student_id);
    $find_bill->execute();
    $found = $find_bill->get_result()->fetch_assoc();
    if ($found) {
        $bill_id = (int)$found['id'];
    }
}

if ($bill_id <= 0) {
    // Log orphan payment
    $log_stmt = $conn->prepare("INSERT INTO gateway_webhook_logs (event_id, event_type, payment_id, order_id, bill_id, amount, payload, status, error_message) VALUES (?, ?, ?, ?, NULL, ?, ?, 'failed', 'Orphan payment: Could not resolve invoice ID') ON DUPLICATE KEY UPDATE status='failed'");
    $log_stmt->bind_param("ssssds", $event_id, $event, $payment_id, $order_id, $paid_amount, $raw_payload);
    $log_stmt->execute();

    http_response_code(200); // Return 200 so Razorpay does not endlessly retry orphan
    echo json_encode(['status' => 'orphan_payment', 'message' => 'Payment captured but invoice could not be resolved automatically']);
    exit();
}

// 6. Fetch Invoice Details
$bill_stmt = $conn->prepare("
    SELECT fg.*, s.name AS student_name, s.parent_id, p.parent_name, p.email AS parent_email 
    FROM fees_generated fg 
    JOIN students s ON fg.student_id = s.id 
    LEFT JOIN parents p ON s.parent_id = p.id 
    WHERE fg.id = ?
");
$bill_stmt->bind_param("i", $bill_id);
$bill_stmt->execute();
$bill = $bill_stmt->get_result()->fetch_assoc();

if (!$bill) {
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => "Invoice #$bill_id not found in database"]);
    exit();
}

// 7. Execute Database Transaction to Record Payment & Clear Bill
$conn->begin_transaction();
try {
    $sid = (int)$bill['student_id'];
    $month = $bill['month_for'];
    $payment_date = date('Y-m-d');
    $method = 'Online (Razorpay: ' . $payment_id . ')';

    // A. Insert into fee_payments
    $pay_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
    $pay_stmt->bind_param("idsss", $sid, $paid_amount, $payment_date, $month, $method);
    $pay_stmt->execute();
    $new_pay_id = $conn->insert_id;

    // B. Update fees_generated
    $existing_rem = trim($bill['remark'] ?? '');
    $payment_tag = "Payment received via Online (Razorpay) on $payment_date (-₹" . number_format($paid_amount, 2) . ") (Rcpt #$new_pay_id)";
    $new_remark = !empty($existing_rem) ? ($existing_rem . " | " . $payment_tag) : $payment_tag;
    
    $new_balance = max(0, (float)$bill['amount'] - $paid_amount);
    $new_status = ($new_balance <= 0) ? 'paid' : 'unpaid';

    $update_bill = $conn->prepare("UPDATE fees_generated SET amount = ?, status = ?, remark = ? WHERE id = ?");
    $update_bill->bind_param("dssi", $new_balance, $new_status, $new_remark, $bill_id);
    $update_bill->execute();

    // C. Log in gateway_webhook_logs
    $log_stmt = $conn->prepare("INSERT INTO gateway_webhook_logs (event_id, event_type, payment_id, order_id, bill_id, amount, payload, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'processed') ON DUPLICATE KEY UPDATE status='processed'");
    $log_stmt->bind_param("ssssids", $event_id, $event, $payment_id, $order_id, $bill_id, $paid_amount, $raw_payload);
    $log_stmt->execute();

    $conn->commit();

    // D. Log Activity
    if (function_exists('log_activity')) {
        log_activity('online_payment_webhook_captured', "Auto-captured ₹" . number_format($paid_amount, 2) . " via Razorpay Webhook ($payment_id) for student " . $bill['student_name'] . " (Invoice #$bill_id)");
    }

    // E. Dispatch Receipt Email to Parent (if available)
    if (!empty($bill['parent_email'])) {
        require_once __DIR__ . '/../includes/mail_helper.php';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
        $base_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
        
        $receipt_url = "$base_url/parent/receipt.php?id=" . $new_pay_id;
        $email_html = get_fee_paid_template(
            $bill['student_name'], 
            $paid_amount, 
            $month, 
            $payment_date, 
            $receipt_url
        );
        send_smtp_email(
            $bill['parent_email'], 
            "Fee Payment Receipt - " . $bill['student_name'] . " - ABSS", 
            $email_html
        );
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment processed successfully via server-to-server webhook',
        'payment_id' => $payment_id,
        'receipt_id' => $new_pay_id,
        'bill_id' => $bill_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Razorpay Webhook Error: " . $e->getMessage());

    $err_msg = $e->getMessage();
    $log_stmt = $conn->prepare("INSERT INTO gateway_webhook_logs (event_id, event_type, payment_id, order_id, bill_id, amount, payload, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, 'failed', ?) ON DUPLICATE KEY UPDATE status='failed', error_message=?");
    $log_stmt->bind_param("ssssidsss", $event_id, $event, $payment_id, $order_id, $bill_id, $paid_amount, $raw_payload, $err_msg, $err_msg);
    $log_stmt->execute();

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $err_msg]);
}

