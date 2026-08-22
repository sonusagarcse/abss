<?php
// admin/includes/billing_engine.php - Perfected Automated Fee Generation Engine

if (isset($skip_email) && $skip_email) {
    $batch_size = 500; // Process up to 500 students in one request if email is skipped
} else {
    $batch_size = 5; // Process max 5 students per page load to keep dashboard fast
}

// Simulated custom test date override support
$engine_eval_date = (!empty($simulated_test_date)) ? $simulated_test_date : date('Y-m-d');

if (isset($force_student_id) && $force_student_id > 0) {
    // Force mode: Target specific student, ignore date rules
    $query = "
        SELECT id, name, scholar_mode, base_fee, monthly_discount, parent_id, admission_date, last_billed_date
        FROM students
        WHERE id = " . (int)$force_student_id . " AND status = 'active'
    ";
} else {
    // Auto mode: Target due students
    // A student is due if last_billed_date is NULL (new admission) OR last_billed_date < 1st of evaluation month
    $query = "
        SELECT id, name, scholar_mode, base_fee, monthly_discount, parent_id, admission_date, last_billed_date
        FROM students
        WHERE status = 'active'
        AND (
            last_billed_date IS NULL
            OR
            last_billed_date < DATE_FORMAT('$engine_eval_date', '%Y-%m-01')
        )
        LIMIT $batch_size
    ";
}

$due_students = $conn->query($query);

if ($due_students && $due_students->num_rows > 0) {
    require_once __DIR__ . '/../../includes/mail_helper.php';

    // Dynamic host URL builder for emails
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $fe_host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
    $fe_base_url = (strpos($fe_host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$fe_host";
    $portal_url = "$fe_base_url/parent/login.php";

    while ($student = $due_students->fetch_assoc()) {
        $sid = (int)$student['id'];
        $base_fee = (float)$student['base_fee'];
        $discount = (float)$student['monthly_discount'];
        $scholar_mode = trim($student['scholar_mode'] ?? '');

        // Dynamic base fee resolution if base_fee is unset or 0 in student profile
        if ($base_fee <= 0) {
            $settings = function_exists('getAllSettings') ? getAllSettings() : [];
            $tuition_modes = !empty($settings['tuition_modes']) ? json_decode($settings['tuition_modes'], true) : [];
            
            if (isset($tuition_modes[$scholar_mode]) && (float)$tuition_modes[$scholar_mode] > 0) {
                $base_fee = (float)$tuition_modes[$scholar_mode];
            } elseif (strpos(strtolower($scholar_mode), 'tuition') !== false) {
                $base_fee = (float)($settings['fee_tuition'] ?? 1500);
            } elseif (strpos(strtolower($scholar_mode), 'host') !== false) {
                $base_fee = (float)($settings['fee_hostler'] ?? 5000);
            } else {
                $base_fee = (float)($settings['fee_day_scholar'] ?? 3000);
            }
        }
        
        // Direct Net Base Fee calculation (base_fee minus monthly discount)
        $net_base_fee = max(0, $base_fee - $discount);
        // Full 100% monthly fee calculation (No mid-month day proration)
        $proration_factor = 1.0;
        $is_prorated = false;
        $proration_msg = "";

        $is_first_bill = is_null($student['last_billed_date']);

        if ($is_first_bill) {
            // New Admission: Full billing starting on 1st of month
            if (empty($student['admission_date'])) {
                $adm_date = new DateTime($engine_eval_date);
            } else {
                $adm_date = new DateTime($student['admission_date']);
            }
            
            $adm_day = (int)$adm_date->format('j'); // Day of month (1-31)
            $eval_dt = new DateTime($engine_eval_date);
            
            // If admitted between the month (after the 1st):
            // Do NOT generate mid-month partial bill. First auto bill will generate on 1st of next month.
            if ($adm_day > 1 && !isset($force_student_id)) {
                $end_of_adm_month = clone $adm_date;
                $end_of_adm_month->modify('last day of this month');
                
                // If evaluation date is still within the admission month, skip generating partial bill
                if ($eval_dt <= $end_of_adm_month) {
                    $new_last_billed_date = $end_of_adm_month->format('Y-m-d');
                    $conn->query("UPDATE students SET last_billed_date = '$new_last_billed_date' WHERE id = $sid");
                    continue;
                }
            }

            // Target Month is 1st of the billing month
            $bill_month_date = $adm_date->format('Y-m-01');
            $month_for = $adm_date->format('F Y');
            
            $end_of_adm_month = clone $adm_date;
            $end_of_adm_month->modify('last day of this month');
            $new_last_billed_date = $end_of_adm_month->format('Y-m-d');
        } else {
            // Subsequent Months: Full 100% monthly calculation starting on 1st of month
            $last_billed = new DateTime($student['last_billed_date']);
            
            $target_month = clone $last_billed;
            $target_month->modify('first day of next month');
            
            if (!isset($force_student_id)) {
                $today = new DateTime($engine_eval_date);
                $today->setTime(0,0,0);
                if ($today < $target_month) {
                    continue; // Safeguard: Not due yet for next month
                }
            }
            
            $bill_month_date = $target_month->format('Y-m-01');
            $month_for = $target_month->format('F Y');
            
            $end_of_target_month = clone $target_month;
            $end_of_target_month->modify('last day of this month');
            $new_last_billed_date = $end_of_target_month->format('Y-m-d');
        }

        // Check if an invoice for THIS exact month string already exists for this student (Prevent Duplicate)
        $month_check = $conn->prepare("SELECT id FROM fees_generated WHERE student_id = ? AND month_for LIKE ? LIMIT 1");
        $like_month = "%" . $month_for . "%";
        $month_check->bind_param("is", $sid, $like_month);
        $month_check->execute();
        $already_billed = $month_check->get_result()->fetch_assoc();

        if ($already_billed && !isset($force_student_id)) {
            // Already billed for this month string, advance last_billed_date to prevent loop
            $conn->query("UPDATE students SET last_billed_date = '$new_last_billed_date' WHERE id = $sid");
            continue;
        }

        // Check for existing UNPAID invoice for this student
        $existing_unpaid_res = $conn->query("SELECT id, amount, remark, month_for, billing_date FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
        $existing_unpaid = ($existing_unpaid_res && $existing_unpaid_res->num_rows > 0) ? $existing_unpaid_res->fetch_assoc() : null;

        // Calculate line items for this new month
        $total_amount = 0;
        $remark_parts = [];

        $calc_net = round($net_base_fee, 2);
        $fee_title = !empty($scholar_mode) ? "$scholar_mode Fee" : "Tuition Fee";
        $remark_parts[] = "$fee_title: ₹" . number_format($calc_net, 2) . " ($month_for)";
        $total_amount += $calc_net;

        // Add monthly addons
        $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
        if ($addons_query && $addons_query->num_rows > 0) {
            while($addon = $addons_query->fetch_assoc()) {
                $calc_addon = round($addon['amount'] * $proration_factor, 2);
                $total_amount += $calc_addon;
                $remark_parts[] = $addon['addon_name'] . ": ₹" . number_format($calc_addon, 2);
            }
        }

        // Add unbilled expenses
        $exp_query = $conn->query("SELECT id, item_name, amount FROM student_expenses WHERE student_id = $sid AND status = 'unbilled'");
        $exp_ids = [];
        if ($exp_query && $exp_query->num_rows > 0) {
            while($exp = $exp_query->fetch_assoc()) {
                $total_amount += (float)$exp['amount'];
                $exp_ids[] = $exp['id'];
                $remark_parts[] = $exp['item_name'] . " (Expense): ₹" . number_format($exp['amount'], 2);
            }
        }

        if ($total_amount <= 0 && empty($remark_parts)) {
            if (!isset($force_student_id)) {
                $conn->query("UPDATE students SET last_billed_date = '$new_last_billed_date' WHERE id = $sid");
            }
            continue;
        }

        $new_month_remark = implode(" | ", $remark_parts);

        $conn->begin_transaction();
        try {
            if ($existing_unpaid) {
                // Rule B: Append new month into existing unpaid invoice
                $updated_amount = (float)$existing_unpaid['amount'] + $total_amount;
                
                $month_parts = array_map('trim', explode(',', $existing_unpaid['month_for']));
                if (!in_array($month_for, $month_parts)) {
                    $month_parts[] = $month_for;
                }
                $updated_month_for = implode(', ', $month_parts);
                $updated_remark = $existing_unpaid['remark'] . " | " . $new_month_remark;

                $update_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, month_for = ?, remark = ? WHERE id = ?");
                $update_stmt->bind_param("dssi", $updated_amount, $updated_month_for, $updated_remark, $existing_unpaid['id']);
                $update_stmt->execute();
                $invoice_id = $existing_unpaid['id'];
            } else {
                // Rule A: Student has no unpaid invoices (paid previous months) -> Create NEW standalone invoice
                $final_remark = "Auto-generated Bill. " . $new_month_remark;
                $stmt = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
                $stmt->bind_param("idsss", $sid, $total_amount, $month_for, $bill_month_date, $final_remark);
                $stmt->execute();
                $invoice_id = $conn->insert_id;
            }

            if (!empty($exp_ids)) {
                $ids_str = implode(",", $exp_ids);
                $conn->query("UPDATE student_expenses SET status = 'billed', billed_at = NOW() WHERE id IN ($ids_str)");
            }

            $conn->query("UPDATE students SET last_billed_date = '$new_last_billed_date' WHERE id = $sid");
            $conn->commit();

            if (function_exists('log_activity')) {
                log_activity('auto_bill_generated', "Automated bill of ₹" . number_format($total_amount, 2) . " processed for student " . $student['name'] . " ($month_for)");
            }

            // Create In-Built Portal Notification for Parent
            if (function_exists('create_portal_notification')) {
                create_portal_notification(
                    'bill',
                    "New Fee Invoice Generated ($month_for)",
                    "Academic fee invoice of ₹" . number_format($total_amount, 2) . " for " . $student['name'] . " is available for payment.",
                    "view_bill.php?id=$invoice_id",
                    !empty($student['parent_id']) ? (int)$student['parent_id'] : null,
                    $sid,
                    'fa-file-invoice-dollar',
                    '#dc2626'
                );
            }

            if (!empty($student['parent_id'])) {
                $parent_res = $conn->query("SELECT email, parent_name FROM parents WHERE id = " . (int)$student['parent_id']);
                if ($parent_res && $parent_res->num_rows > 0) {
                    $parent = $parent_res->fetch_assoc();
                    $bill_view_url = "$fe_base_url/parent/view_bill.php?id=$invoice_id";
                    
                    $email_html = get_fee_generated_template(
                        $student['name'], 
                        $total_amount, 
                        $month_for, 
                        $bill_month_date, 
                        $new_month_remark . " | Bill ID: #$invoice_id", 
                        $bill_view_url
                    );

                    if (!empty($parent['email'])) {
                        send_smtp_email(
                            $parent['email'], 
                            "New Tuition Fee Invoice #" . $invoice_id . " Generated - " . $student['name'] . " - ABSS", 
                            $email_html
                        );
                    }
                    send_smtp_email('abssimamganj@gmail.com', "Fee Invoice #" . $invoice_id . " Generated - " . $student['name'] . " (₹" . number_format($total_amount, 2) . ")", $email_html);
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Billing Engine Error for Student ID $sid: " . $e->getMessage());
        }
    }
}
?>
