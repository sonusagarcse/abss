<?php
// parent/verify_payment.php - Resilient, Hardened Razorpay Payment Verification
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

$conn = getDB();
verify_and_restore_parent_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['razorpay_payment_id']) || empty($_POST['bill_id'])) {
    header("Location: fees.php");
    exit();
}

$payment_id = trim($_POST['razorpay_payment_id']);
$order_id = trim($_POST['razorpay_order_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');
$bill_id = (int)$_POST['bill_id'];
$session_parent_id = (int)($_SESSION['parent_id'] ?? 0);

// Fetch Gateway Credentials
$settings = getAllSettings();
$key_id = $settings['razorpay_key_id'] ?? '';
$key_secret = $settings['razorpay_key_secret'] ?? '';

if (empty($key_id) || empty($key_secret)) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Payment Configuration Error</h2><p>Payment gateway keys are not configured properly.</p></div>");
}

// 1. Idempotency Check: Check if Webhook or previous request already recorded this payment
$already_check = $conn->prepare("SELECT id FROM fee_payments WHERE payment_method LIKE ? LIMIT 1");
$pay_needle = "%$payment_id%";
$already_check->bind_param("s", $pay_needle);
$already_check->execute();
if ($already_check->get_result()->num_rows > 0) {
    // Payment was already safely captured (e.g. by background webhook)
    header("Location: fees.php?success=1");
    exit();
}

// 2. Fetch Invoice Details
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
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Invalid Invoice</h2><p>Invoice #$bill_id not found in records.</p><a href='fees.php'>Back to Fees</a></div>");
}

// 3. Cryptographic Signature Verification
$is_sig_valid = false;
if (!empty($order_id) && !empty($signature)) {
    $expected_sig = hash_hmac('sha256', $order_id . '|' . $payment_id, $key_secret);
    if (hash_equals($expected_sig, $signature)) {
        $is_sig_valid = true;
    }
}

// 4. Session Protection with Cryptographic Fallback
if ($session_parent_id > 0) {
    if ($bill['parent_id'] != $session_parent_id) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Unauthorized</h2><p>You are not authorized to pay for this invoice.</p></div>");
    }
} else {
    // Session dropped during mobile UPI app switching: allow verification ONLY if cryptographic signature matches or Razorpay API confirms
    if (!$is_sig_valid) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Session Expired</h2><p>Your session expired while in the payment app. Please <a href='login.php'>log in</a> to verify your transaction.</p></div>");
    }
    // Restore parent session automatically for a seamless user experience
    if (!empty($bill['parent_id'])) {
        $_SESSION['parent_id'] = (int)$bill['parent_id'];
        $_SESSION['parent_name'] = $bill['parent_name'] ?? 'Parent';
        $_SESSION['parent_email'] = $bill['parent_email'] ?? '';
    }
}

// 5. Query Razorpay API to verify payment status and amount
$url = "https://api.razorpay.com/v1/payments/" . $payment_id;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status === 200) {
    $data = json_decode($response, true);
    
    // Auto-capture if payment status is authorized
    if (isset($data['status']) && $data['status'] === 'authorized') {
        $cap_ch = curl_init("https://api.razorpay.com/v1/payments/" . $payment_id . "/capture");
        curl_setopt($cap_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cap_ch, CURLOPT_POST, true);
        curl_setopt($cap_ch, CURLOPT_POSTFIELDS, http_build_query([
            'amount' => $data['amount'],
            'currency' => 'INR'
        ]));
        curl_setopt($cap_ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
        curl_setopt($cap_ch, CURLOPT_TIMEOUT, 15);
        $cap_resp = curl_exec($cap_ch);
        $cap_code = curl_getinfo($cap_ch, CURLINFO_HTTP_CODE);
        curl_close($cap_ch);
        if ($cap_code === 200) {
            $data['status'] = 'captured';
        }
    }

    if (isset($data['status']) && ($data['status'] === 'captured' || $data['status'] === 'authorized')) {
        $paid_amount = round((float)($data['amount'] / 100), 2); // Convert subunit to INR

        // Calculate expected amount including fine
        $fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($bill, $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0];
        $fine_amount = (float)$fine_calc['fine_amount'];
        $expected_with_fine = (float)$bill['amount'] + $fine_amount;
        $expected_base = (float)$bill['amount'];

        // Resilient validation: Accept total with fine, base amount, or any valid positive payment towards bill
        $is_acceptable = (
            abs($paid_amount - $expected_with_fine) < 1.00 ||
            abs($paid_amount - $expected_base) < 1.00 ||
            ($paid_amount > 0 && $paid_amount <= $expected_with_fine + 1.00)
        );

        if ($is_acceptable) {
            $sid = (int)$bill['student_id'];
            $month = $bill['month_for'];
            if ($fine_amount > 0 && abs($paid_amount - $expected_with_fine) < 1.00) {
                $month .= " (Incl. ₹" . number_format($fine_amount, 2) . " Late Fine)";
            }
            $payment_date = date('Y-m-d');
            $method = 'Online (Razorpay: ' . $payment_id . ')';

            // Double check before insert to ensure webhook didn't race us in the last second
            $race_check = $conn->prepare("SELECT id FROM fee_payments WHERE payment_method LIKE ? LIMIT 1");
            $race_check->bind_param("s", $pay_needle);
            $race_check->execute();
            if ($race_check->get_result()->num_rows > 0) {
                header("Location: fees.php?success=1");
                exit();
            }

            // Begin Transaction
            $conn->begin_transaction();
            try {
                // Insert into fee_payments
                $pay_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
                $pay_stmt->bind_param("idsss", $sid, $paid_amount, $payment_date, $month, $method);
                $pay_stmt->execute();
                $new_pay_id = $conn->insert_id;
                
                // Update Invoice
                $existing_rem = trim($bill['remark'] ?? '');
                $payment_tag = "Payment received via Online (Razorpay) on $payment_date (-₹" . number_format($paid_amount, 2) . ") (Rcpt #$new_pay_id)";
                $new_remark = !empty($existing_rem) ? ($existing_rem . " | " . $payment_tag) : $payment_tag;
                
                $new_balance = max(0, (float)$bill['amount'] - $paid_amount);
                $new_status = ($new_balance <= 0) ? 'paid' : 'unpaid';

                $update_bill = $conn->prepare("UPDATE fees_generated SET amount = ?, status = ?, remark = ? WHERE id = ?");
                $update_bill->bind_param("dssi", $new_balance, $new_status, $new_remark, $bill_id);
                $update_bill->execute();
                
                $conn->commit();
                
                // Log activity
                if (function_exists('log_activity')) {
                    $pid_for_log = $session_parent_id > 0 ? $session_parent_id : ($bill['parent_id'] ?? 0);
                    log_activity('online_payment_success', "Parent $pid_for_log paid ₹$paid_amount via Razorpay ($payment_id) for Invoice #$bill_id (Receipt #$new_pay_id)");
                }

                // Send Email
                if (!empty($bill['parent_email'])) {
                    require_once __DIR__ . '/../../includes/mail_helper.php';
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
                
                header("Location: fees.php?success=1");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Database Error</h2><p>Payment recorded with Razorpay ($payment_id) but database update failed: " . htmlspecialchars($e->getMessage()) . "</p></div>");
            }
        } else {
            die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Payment Amount Mismatch</h2><p>Paid: ₹" . number_format($paid_amount, 2) . ", Expected: ₹" . number_format($expected_with_fine, 2) . "</p><a href='fees.php'>Back to Fees</a></div>");
        }
    } else {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Payment Incomplete</h2><p>Current Razorpay payment status: " . htmlspecialchars($data['status'] ?? 'unknown') . "</p><a href='fees.php'>Back to Fees</a></div>");
    }
} else {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Gateway Verification Failed</h2><p>Could not verify payment with Razorpay servers (HTTP $http_status). Please contact school administration with Payment ID: <strong>" . htmlspecialchars($payment_id) . "</strong></p><a href='fees.php'>Back to Fees</a></div>");
}
