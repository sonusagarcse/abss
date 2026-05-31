<?php
// parent/verify_payment.php
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['razorpay_payment_id']) || empty($_POST['bill_id'])) {
    header("Location: fees.php");
    exit();
}

$payment_id = $_POST['razorpay_payment_id'];
$bill_id = (int)$_POST['bill_id'];
$parent_id = (int)$_SESSION['parent_id'];

// Fetch API Keys
$settings = getAllSettings();
$key_id = $settings['razorpay_key_id'] ?? '';
$key_secret = $settings['razorpay_key_secret'] ?? '';

if (empty($key_id) || empty($key_secret)) {
    die("Payment gateway not configured properly.");
}

// Fetch Bill
$bill_stmt = $conn->prepare("
    SELECT fg.*, s.parent_id 
    FROM fees_generated fg 
    JOIN students s ON fg.student_id = s.id 
    WHERE fg.id = ? AND fg.status = 'unpaid'
");
$bill_stmt->bind_param("i", $bill_id);
$bill_stmt->execute();
$bill = $bill_stmt->get_result()->fetch_assoc();

if (!$bill || $bill['parent_id'] != $parent_id) {
    die("Invalid invoice or unauthorized access.");
}

// Verify payment with Razorpay API
$url = "https://api.razorpay.com/v1/payments/" . $payment_id;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status === 200) {
    $data = json_decode($response, true);
    
    // Check if payment was authorized or captured and amount matches
    if (isset($data['status']) && ($data['status'] === 'authorized' || $data['status'] === 'captured')) {
        $paid_amount = $data['amount'] / 100; // Convert subunit to INR
        
        // We accept the payment if the amount is roughly equal
        if (abs($paid_amount - $bill['amount']) < 1) {
            $sid = $bill['student_id'];
            $month = $bill['month_for'];
            $payment_date = date('Y-m-d');
            $method = 'Online (Razorpay: ' . $payment_id . ')';

            // Begin Transaction
            $conn->begin_transaction();
            try {
                // Insert Payment
                $pay_stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
                $pay_stmt->bind_param("idsss", $sid, $paid_amount, $payment_date, $month, $method);
                $pay_stmt->execute();
                
                // Update Bill
                $update_bill = $conn->prepare("UPDATE fees_generated SET status = 'paid' WHERE id = ?");
                $update_bill->bind_param("i", $bill_id);
                $update_bill->execute();
                
                $conn->commit();
                
                // Log and redirect
                if (function_exists('log_activity')) {
                    log_activity('online_payment_success', "Parent $parent_id paid ₹$paid_amount via Razorpay for Bill #$bill_id");
                }
                
                header("Location: fees.php?success=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                die("Database error while recording payment.");
            }
        } else {
            die("Payment amount mismatch. Paid: $paid_amount, Expected: {$bill['amount']}");
        }
    } else {
        die("Payment not successful. Current status: " . ($data['status'] ?? 'unknown'));
    }
} else {
    die("Failed to verify payment with Razorpay.");
}
