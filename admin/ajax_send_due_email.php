<?php
// admin/ajax_send_due_email.php - Dispatch Student Fee Due Statement & Bill PDF directly via Email
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/pdf_helper.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$settings = function_exists('getAllSettings') ? getAllSettings() : [];

// 1. Direct PDF Download Handler (GET)
if (isset($_GET['action'])) {
    $action = trim($_GET['action']);

    if ($action === 'download_due_pdf' && isset($_GET['student_id'])) {
        $student_id = (int)$_GET['student_id'];
        try {
            generate_student_due_pdf($student_id, $conn, $settings, 'D');
            exit;
        } catch (Exception $e) {
            die("PDF Generation Error: " . htmlspecialchars($e->getMessage()));
        }
    }

    if ($action === 'download_bill_pdf' && isset($_GET['bill_id'])) {
        $bill_id = (int)$_GET['bill_id'];
        try {
            generate_single_bill_pdf($bill_id, $conn, $settings, 'D');
            exit;
        } catch (Exception $e) {
            die("PDF Generation Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}

// 2. AJAX Email Dispatch Handlers (POST)
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. POST required.']);
    exit;
}

$action = trim($_POST['action'] ?? '');

try {
    // A. Single Student Due Statement Email Dispatch
    if ($action === 'send_student_due_email') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $target_email = trim($_POST['email'] ?? '');

        if ($student_id <= 0) {
            throw new Exception("Invalid student ID.");
        }

        // Fetch Student Record
        $stmt = $conn->prepare("
            SELECT s.*, p.parent_name as p_name, p.email as p_email
            FROM students s
            LEFT JOIN parents p ON s.parent_id = p.id
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) {
            throw new Exception("Student record not found.");
        }

        // Resolve recipient email
        if (empty($target_email)) {
            $target_email = trim($student['guardian_email'] ?: ($student['p_email'] ?? ''));
        }

        if (empty($target_email) || !filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("No valid email address found for student guardian. Please enter a valid email.");
        }

        // Calculate Dues & Months
        $due_stmt = $conn->prepare("
            SELECT SUM(amount) AS total_due, COUNT(id) AS unpaid_count,
                   GROUP_CONCAT(DISTINCT month_for ORDER BY billing_date ASC SEPARATOR ', ') AS due_months
            FROM fees_generated
            WHERE student_id = ? AND status = 'unpaid'
        ");
        $due_stmt->bind_param("i", $student_id);
        $due_stmt->execute();
        $due_info = $due_stmt->get_result()->fetch_assoc();
        $due_stmt->close();

        $base_due = (float)($due_info['total_due'] ?? 0);
        $unpaid_count = (int)($due_info['unpaid_count'] ?? 0);
        $due_months = $due_info['due_months'] ?? date('F Y');

        $fine_data = function_exists('get_student_total_fine') ? get_student_total_fine($student_id, $conn, $settings) : ['total_fine' => 0.00];
        $fine_amount = (float)$fine_data['total_fine'];
        $total_payable = $base_due + $fine_amount;

        $bill_id = (int)($_POST['bill_id'] ?? 0);
        $raw_pdf_b64 = trim($_POST['pdf_base64'] ?? '');

        // Generate or decode Bill PDF binary matching view_bill.php exact layout
        if (!empty($raw_pdf_b64)) {
            if (strpos($raw_pdf_b64, ',') !== false) {
                $raw_pdf_b64 = explode(',', $raw_pdf_b64)[1];
            }
            $pdfContent = base64_decode($raw_pdf_b64);
            $pdfFilename = "ABSS_Fee_Invoice_" . ($bill_id > 0 ? str_pad($bill_id, 5, '0', STR_PAD_LEFT) : date('Ymd')) . "_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $student['name']) . ".pdf";
        } elseif ($bill_id > 0) {
            $pdfContent = generate_single_bill_pdf($bill_id, $conn, $settings, 'S');
            $pdfFilename = "ABSS_Fee_Invoice_" . str_pad($bill_id, 5, '0', STR_PAD_LEFT) . "_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $student['name']) . ".pdf";
        } else {
            $pdfContent = generate_student_due_pdf($student_id, $conn, $settings, 'S');
            $safe_reg = preg_replace('/[^a-zA-Z0-9_-]/', '', $student['reg_no'] ?: ('STU' . $student['id']));
            $pdfFilename = "ABSS_Fee_Statement_" . $safe_reg . "_" . date('Ymd') . ".pdf";
        }

        if (empty($pdfContent)) {
            throw new Exception("Failed to generate PDF document for student.");
        }

        // Build Portal Link
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
        $portal_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss/parent/login.php" : "$protocol://$host/parent/login.php";

        $parent_name = $student['parent_name'] ?: ($student['p_name'] ?: 'Parent / Guardian');

        if ($bill_id > 0) {
            $bill_query = $conn->query("SELECT * FROM fees_generated WHERE id = $bill_id");
            $bill_row = ($bill_query && $bill_query->num_rows > 0) ? $bill_query->fetch_assoc() : null;
            $bill_month = $bill_row ? $bill_row['month_for'] : $due_months;
            $bill_amt = $bill_row ? (float)$bill_row['amount'] : $total_payable;
            $bill_date = $bill_row ? $bill_row['billing_date'] : date('Y-m-d');
            $bill_rem = $bill_row ? $bill_row['remark'] : '';
            $bill_view_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss/parent/view_bill.php?id=$bill_id" : "$protocol://$host/parent/view_bill.php?id=$bill_id";

            $email_html = get_fee_generated_template(
                $student['name'],
                $bill_amt,
                $bill_month,
                $bill_date,
                $bill_rem,
                $bill_view_url,
                $parent_name
            );
            $subject = "Fee Invoice #" . str_pad($bill_id, 5, '0', STR_PAD_LEFT) . " (" . $bill_month . ") - " . $student['name'] . " - ABSS";
        } else {
            $email_html = get_student_dues_email_template(
                $student['name'],
                $total_payable,
                $due_months,
                $unpaid_count,
                $fine_amount,
                $portal_url,
                $parent_name
            );
            $subject = "Official Fee Due Statement & Bill - " . $student['name'] . " (ABSS)";
        }
        $attachments = [
            [
                'filename' => $pdfFilename,
                'content'  => $pdfContent,
                'mime'     => 'application/pdf'
            ]
        ];

        $sent = send_smtp_email($target_email, $subject, $email_html, $attachments);

        if (!$sent) {
            throw new Exception("SMTP dispatch failed. Please verify email server settings.");
        }

        echo json_encode([
            'success' => true,
            'message' => "Fee Bill PDF & statement successfully delivered to " . htmlspecialchars($target_email) . "!",
            'email' => $target_email,
            'student_name' => $student['name'],
            'total_due' => $total_payable
        ]);
        exit;

    // B. Bulk Email Due Statements Dispatch
    } elseif ($action === 'bulk_send_due_emails') {
        $student_ids = $_POST['student_ids'] ?? [];
        if (is_string($student_ids)) {
            $student_ids = array_filter(array_map('intval', explode(',', $student_ids)));
        }

        // If no specific IDs passed, fetch all students with unpaid dues
        if (empty($student_ids)) {
            $all_due_res = $conn->query("
                SELECT DISTINCT s.id
                FROM students s
                JOIN fees_generated fg ON s.id = fg.student_id
                WHERE fg.status = 'unpaid' AND s.status = 'active'
            ");
            while ($r = $all_due_res->fetch_assoc()) {
                $student_ids[] = (int)$r['id'];
            }
        }

        if (empty($student_ids)) {
            throw new Exception("No students with pending dues found to dispatch emails.");
        }

        $success_count = 0;
        $failed_count = 0;
        $skipped_no_email = 0;
        $details = [];

        foreach ($student_ids as $sid) {
            $sid = (int)$sid;
            if ($sid <= 0) continue;

            $stmt = $conn->prepare("
                SELECT s.*, p.parent_name as p_name, p.email as p_email
                FROM students s
                LEFT JOIN parents p ON s.parent_id = p.id
                WHERE s.id = ?
            ");
            $stmt->bind_param("i", $sid);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$student) continue;

            $target_email = trim($student['guardian_email'] ?: ($student['p_email'] ?? ''));
            if (empty($target_email) || !filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
                $skipped_no_email++;
                continue;
            }

            // Calculate Dues
            $due_stmt = $conn->prepare("
                SELECT SUM(amount) AS total_due, COUNT(id) AS unpaid_count,
                       GROUP_CONCAT(DISTINCT month_for ORDER BY billing_date ASC SEPARATOR ', ') AS due_months
                FROM fees_generated
                WHERE student_id = ? AND status = 'unpaid'
            ");
            $due_stmt->bind_param("i", $sid);
            $due_stmt->execute();
            $due_info = $due_stmt->get_result()->fetch_assoc();
            $due_stmt->close();

            $base_due = (float)($due_info['total_due'] ?? 0);
            if ($base_due <= 0) continue;

            $unpaid_count = (int)($due_info['unpaid_count'] ?? 0);
            $due_months = $due_info['due_months'] ?? date('F Y');

            $fine_data = function_exists('get_student_total_fine') ? get_student_total_fine($sid, $conn, $settings) : ['total_fine' => 0.00];
            $fine_amount = (float)$fine_data['total_fine'];
            $total_payable = $base_due + $fine_amount;

            // Generate PDF
            try {
                $pdfContent = generate_student_due_pdf($sid, $conn, $settings, 'S');
                $safe_reg = preg_replace('/[^a-zA-Z0-9_-]/', '', $student['reg_no'] ?: ('STU' . $student['id']));
                $pdfFilename = "ABSS_Fee_Statement_" . $safe_reg . "_" . date('Ymd') . ".pdf";

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
                $portal_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss/parent/login.php" : "$protocol://$host/parent/login.php";

                $parent_name = $student['parent_name'] ?: ($student['p_name'] ?: 'Parent / Guardian');
                $email_html = get_student_dues_email_template(
                    $student['name'],
                    $total_payable,
                    $due_months,
                    $unpaid_count,
                    $fine_amount,
                    $portal_url,
                    $parent_name
                );

                $subject = "Official Fee Due Statement & Bill - " . $student['name'] . " (ABSS)";
                $attachments = [
                    [
                        'filename' => $pdfFilename,
                        'content'  => $pdfContent,
                        'mime'     => 'application/pdf'
                    ]
                ];

                $sent = send_smtp_email($target_email, $subject, $email_html, $attachments);

                if ($sent) {
                    $success_count++;
                    $details[] = ['student' => $student['name'], 'email' => $target_email, 'status' => 'sent'];
                } else {
                    $failed_count++;
                    $details[] = ['student' => $student['name'], 'email' => $target_email, 'status' => 'failed'];
                }
            } catch (Exception $e) {
                $failed_count++;
                $details[] = ['student' => $student['name'], 'email' => $target_email, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Bulk dispatch complete: $success_count email(s) sent with Bill PDF attachments, $skipped_no_email skipped (no email), $failed_count failed.",
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'skipped_no_email' => $skipped_no_email,
            'details' => $details
        ]);
        exit;

    } else {
        throw new Exception("Unknown action parameter: " . htmlspecialchars($action));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
