<?php
// admin/includes/billing_engine.php - Automated Monthly Fee Generation

// Note: This script assumes $conn is already established by the calling script (e.g. dashboard.php).
// To prevent multiple triggers running simultaneously, we can use a basic lock or just rely on quick execution.
// We'll process a small batch to avoid slowing down page loads.

$batch_size = 5; // Process max 5 students per page load to keep dashboard fast

// Find students whose next billing date is due (or past due).
// If last_billed_date is NULL, use admission_date as the baseline.
if (isset($force_student_id) && $force_student_id > 0) {
    // Force mode: Target specific student, ignore date rules
    $query = "
        SELECT id, name, base_fee, monthly_discount, parent_id,
               COALESCE(last_billed_date, admission_date) as ref_date
        FROM students
        WHERE id = " . (int)$force_student_id . " AND status = 'active'
    ";
} else {
    // Auto mode: Target due students
    $query = "
        SELECT id, name, base_fee, monthly_discount, parent_id,
               COALESCE(last_billed_date, admission_date) as ref_date
        FROM students
        WHERE status = 'active'
        AND DATE_ADD(COALESCE(last_billed_date, admission_date), INTERVAL 1 MONTH) <= CURDATE()
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
    $portal_url = "$fe_base_url/admin/login.php?role=parent";

    while ($student = $due_students->fetch_assoc()) {
        $sid = (int)$student['id'];
        $base_fee = (float)$student['base_fee'];
        $discount = (float)$student['monthly_discount'];
        
        // Month for the bill
        $bill_month_date = date('Y-m-d'); // Today's billing date
        $month_for = date('F', strtotime($bill_month_date));

        // Check for existing unpaid bill
        $existing_unpaid = $conn->query("SELECT id, amount, remark, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
        $existing = null;
        $already_billed_this_month = false;
        if ($existing_unpaid && $existing_unpaid->num_rows > 0) {
            $existing = $existing_unpaid->fetch_assoc();
            if (strpos($existing['month_for'], $month_for) !== false) {
                $already_billed_this_month = true;
            }
        }

        $total_amount = 0;
        $remark_parts = [];

        // Add base fee and addons only if they haven't been billed for this month in the existing unpaid invoice
        if (!$already_billed_this_month) {
            $remark_parts[] = "Base Fee: ₹" . number_format($base_fee, 2);
            if ($discount > 0) {
                $remark_parts[] = "Discount applied (-₹" . number_format($discount, 2) . ")";
                $base_fee = max(0, $base_fee - $discount);
            }
            $total_amount += $base_fee;

            // Fetch recurring addons
            $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
            if ($addons_query && $addons_query->num_rows > 0) {
                while($addon = $addons_query->fetch_assoc()) {
                    $total_amount += (float)$addon['amount'];
                    $remark_parts[] = $addon['addon_name'] . ": ₹" . number_format($addon['amount'], 2);
                }
            }
        }

        // Fetch daily unbilled expenses
        $exp_query = $conn->query("SELECT id, item_name, amount FROM student_expenses WHERE student_id = $sid AND status = 'unbilled'");
        $exp_ids = [];
        if ($exp_query && $exp_query->num_rows > 0) {
            while($exp = $exp_query->fetch_assoc()) {
                $total_amount += (float)$exp['amount'];
                $exp_ids[] = $exp['id'];
                $remark_parts[] = $exp['item_name'] . " (Expense): ₹" . number_format($exp['amount'], 2);
            }
        }

        // If there is nothing to bill (e.g. already billed and no new expenses), skip this student
        if ($total_amount == 0 && empty($remark_parts)) {
            // Still advance last_billed_date if it was auto-triggered and date is due
            if (!isset($force_student_id)) {
                $conn->query("UPDATE students SET last_billed_date = DATE_ADD('{$student['ref_date']}', INTERVAL 1 MONTH) WHERE id = $sid");
            }
            continue;
        }

        // Compile final remark
        $final_remark = "Auto-generated Bill. " . implode(" | ", $remark_parts);

        // Begin transaction
        $conn->begin_transaction();

        try {
            if ($existing) {
                // Update existing
                $new_amount = $existing['amount'] + $total_amount;
                $new_remark = $existing['remark'] . " | " . implode(" | ", $remark_parts);
                // Try to append month if it's not already there
                $new_month = $existing['month_for'];
                if (strpos($new_month, $month_for) === false) {
                    $new_month .= ", " . $month_for;
                }
                
                $update_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, remark = ?, month_for = ? WHERE id = ?");
                $update_stmt->bind_param("dssi", $new_amount, $new_remark, $new_month, $existing['id']);
                $update_stmt->execute();
                $invoice_id = $existing['id'];
            } else {
                // 1. Insert into fees_generated
                $stmt = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
                $stmt->bind_param("idsss", $sid, $total_amount, $month_for, $bill_month_date, $final_remark);
                $stmt->execute();
                $invoice_id = $conn->insert_id;
            }

            // 2. Mark expenses as billed
            if (!empty($exp_ids)) {
                $ids_str = implode(",", $exp_ids);
                $conn->query("UPDATE student_expenses SET status = 'billed', billed_at = NOW() WHERE id IN ($ids_str)");
            }

            // 3. Advance last_billed_date
            // We advance it exactly by 1 month from the previous reference date to maintain cycle consistency, 
            // even if the script runs a few days late.
            $conn->query("UPDATE students SET last_billed_date = DATE_ADD('{$student['ref_date']}', INTERVAL 1 MONTH) WHERE id = $sid");

            $conn->commit();

            // Log activity
            if (function_exists('log_activity')) {
                log_activity('auto_bill_generated', "Automated bill of ₹" . number_format($total_amount, 2) . " generated for student " . $student['name']);
            }

            // 4. Send Email to Parent if linked
            if (!empty($student['parent_id'])) {
                $parent_res = $conn->query("SELECT email, parent_name FROM parents WHERE id = " . (int)$student['parent_id']);
                if ($parent_res && $parent_res->num_rows > 0) {
                    $parent = $parent_res->fetch_assoc();
                    if (!empty($parent['email'])) {
                        $email_html = get_fee_generated_template(
                            $student['name'], 
                            $total_amount, 
                            $month_for, 
                            $bill_month_date, 
                            $final_remark, 
                            $portal_url
                        );
                        send_smtp_email(
                            $parent['email'], 
                            "New Tuition Invoice Generated - " . $student['name'] . " - ABSS", 
                            $email_html
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Billing Engine Error for Student ID $sid: " . $e->getMessage());
        }
    }
}
?>
