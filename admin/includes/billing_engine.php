<?php
// admin/includes/billing_engine.php - Automated Monthly Fee Generation

$batch_size = 5; // Process max 5 students per page load to keep dashboard fast

if (isset($force_student_id) && $force_student_id > 0) {
    // Force mode: Target specific student, ignore date rules
    $query = "
        SELECT id, name, base_fee, monthly_discount, parent_id, admission_date, last_billed_date
        FROM students
        WHERE id = " . (int)$force_student_id . " AND status = 'active'
    ";
} else {
    // Auto mode: Target due students
    // A student is due if last_billed_date is NULL (new) OR last_billed_date < 1st of current month
    $query = "
        SELECT id, name, base_fee, monthly_discount, parent_id, admission_date, last_billed_date
        FROM students
        WHERE status = 'active'
        AND (
            last_billed_date IS NULL
            OR
            last_billed_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')
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
    $portal_url = "$fe_base_url/admin/login.php?role=parent";

    while ($student = $due_students->fetch_assoc()) {
        $sid = (int)$student['id'];
        $base_fee = (float)$student['base_fee'];
        $discount = (float)$student['monthly_discount'];
        
        $is_first_bill = is_null($student['last_billed_date']);

        // Check for existing unpaid bill FIRST to know what period we are rebuilding
        $existing_unpaid = $conn->query("SELECT id, amount, remark, month_for, billing_date FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
        $existing = null;
        if ($existing_unpaid && $existing_unpaid->num_rows > 0) {
            $existing = $existing_unpaid->fetch_assoc();
        }

        if ($is_first_bill || (isset($force_student_id) && $existing && strpos($existing['remark'], 'Prorated') !== false)) {
            // It's the first bill or we are rebuilding a prorated first bill
            if (empty($student['admission_date'])) {
                $adm_date = new DateTime();
            } else {
                $adm_date = new DateTime($student['admission_date']);
            }
            
            $end_of_adm_month = clone $adm_date;
            $end_of_adm_month->modify('last day of this month');
            
            $days_in_month = (int)$adm_date->format('t');
            $days_active = $adm_date->diff($end_of_adm_month)->days + 1;
            
            $proration_factor = $days_active / $days_in_month;
            $bill_month_date = $adm_date->format('Y-m-d');
            $month_for = $adm_date->format('F Y');
            $new_last_billed_date = $end_of_adm_month->format('Y-m-d');
            $is_prorated = ($days_active < $days_in_month);
            $proration_msg = $is_prorated ? " (Prorated: $days_active/$days_in_month days)" : "";
        } else {
            // Regular monthly invoice for the month following last_billed_date
            $last_billed = new DateTime($student['last_billed_date']);
            
            $target_month = clone $last_billed;
            $target_month->modify('first day of next month');
            
            if (!isset($force_student_id)) {
                $today = new DateTime();
                $today->setTime(0,0,0);
                if ($today < $target_month) {
                    continue; // Safeguard
                }
            } else {
                // If it's an update, we should only rebuild the *currently* unpaid months, not advance to the next month unless it's due
                if ($existing) {
                    // We shouldn't advance the month_for if we are just rebuilding. 
                    // Let's use the date from the existing invoice to determine the month_for.
                    $month_for = $existing['month_for']; // We'll keep the existing month string
                    $bill_month_date = $existing['billing_date'];
                    $proration_factor = 1.0;
                    $is_prorated = false;
                    $proration_msg = "";
                    $new_last_billed_date = $student['last_billed_date']; // don't advance
                } else {
                    // If there's no existing bill, do nothing on update unless due
                    $today = new DateTime();
                    $today->setTime(0,0,0);
                    if ($today < $target_month) {
                        continue;
                    }
                    // Otherwise it's due, so proceed as normal
                    $proration_factor = 1.0;
                    $bill_month_date = $target_month->format('Y-m-01');
                    $month_for = $target_month->format('F Y');
                    $end_of_target_month = clone $target_month;
                    $end_of_target_month->modify('last day of this month');
                    $new_last_billed_date = $end_of_target_month->format('Y-m-d');
                    $is_prorated = false;
                    $proration_msg = "";
                }
            }
            
            if (!isset($new_last_billed_date)) {
                // For normal auto-generation when due
                $proration_factor = 1.0;
                $bill_month_date = $target_month->format('Y-m-01');
                $month_for = $target_month->format('F Y');
                
                $end_of_target_month = clone $target_month;
                $end_of_target_month->modify('last day of this month');
                $new_last_billed_date = $end_of_target_month->format('Y-m-d');
                $is_prorated = false;
                $proration_msg = "";
            }
        }

        $already_billed_this_month = false;
        if ($existing) {
            if (strpos($existing['month_for'], $month_for) !== false || (isset($force_student_id) && !isset($target_month))) {
                // If we are just rebuilding and we overrode month_for, consider it billed
                $already_billed_this_month = true;
            }
        }

        $total_amount = 0;
        $remark_parts = [];

        if (isset($force_student_id) && $existing) {
            // Rebuild logic (when updating student profile)
            // Use prorated factor for this month recalculation
            $monthly_recurring = 0;
            $recurring_parts = [];
            
            $calc_base = round($base_fee * $proration_factor, 2);
            $recurring_parts[] = "Base Fee: ₹" . number_format($calc_base, 2) . $proration_msg;
            
            if ($discount > 0) {
                $calc_disc = round($discount * $proration_factor, 2);
                $recurring_parts[] = "Discount applied (-₹" . number_format($calc_disc, 2) . ")";
                $monthly_recurring += max(0, $calc_base - $calc_disc);
            } else {
                $monthly_recurring += $calc_base;
            }

            $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
            if ($addons_query && $addons_query->num_rows > 0) {
                while($addon = $addons_query->fetch_assoc()) {
                    $calc_addon = round($addon['amount'] * $proration_factor, 2);
                    $monthly_recurring += $calc_addon;
                    $recurring_parts[] = $addon['addon_name'] . ": ₹" . number_format($calc_addon, 2);
                }
            }

            $months_list = explode(", ", $existing['month_for']);
            $months_count = count($months_list);
            if (!$already_billed_this_month) {
                $months_count++;
            }

            for ($i = 0; $i < $months_count; $i++) {
                $remark_parts = array_merge($remark_parts, $recurring_parts);
            }
            $total_amount += ($monthly_recurring * $months_count);

            $old_parts = explode("|", str_replace("Auto-generated Bill. ", "", $existing['remark']));
            foreach ($old_parts as $part) {
                $part = trim($part);
                if (strpos($part, '(Expense):') !== false) {
                    $remark_parts[] = $part;
                    $exp_parts = explode(': ₹', $part);
                    if (count($exp_parts) == 2) {
                        $total_amount += (float)str_replace(',', '', trim($exp_parts[1]));
                    }
                }
            }
        } else {
            if (!$already_billed_this_month) {
                $calc_base = round($base_fee * $proration_factor, 2);
                $remark_parts[] = "Base Fee: ₹" . number_format($calc_base, 2) . $proration_msg;
                
                if ($discount > 0) {
                    $calc_disc = round($discount * $proration_factor, 2);
                    $remark_parts[] = "Discount applied (-₹" . number_format($calc_disc, 2) . ")";
                    $calc_base = max(0, $calc_base - $calc_disc);
                }
                $total_amount += $calc_base;

                $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
                if ($addons_query && $addons_query->num_rows > 0) {
                    while($addon = $addons_query->fetch_assoc()) {
                        $calc_addon = round($addon['amount'] * $proration_factor, 2);
                        $total_amount += $calc_addon;
                        $remark_parts[] = $addon['addon_name'] . ": ₹" . number_format($calc_addon, 2);
                    }
                }
            }
        }

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

        $final_remark = "Auto-generated Bill. " . implode(" | ", $remark_parts);

        $conn->begin_transaction();
        try {
            if ($existing) {
                if (isset($force_student_id)) {
                    $new_amount = $total_amount;
                    $new_remark = "Auto-generated Bill. " . implode(" | ", $remark_parts);
                    $new_month = $existing['month_for'];
                    if (!$already_billed_this_month) {
                        $new_month .= ", " . $month_for;
                    }
                } else {
                    $new_amount = $existing['amount'] + $total_amount;
                    $new_remark = $existing['remark'] . (empty($remark_parts) ? "" : " | " . implode(" | ", $remark_parts));
                    $new_month = $existing['month_for'];
                    if (strpos($new_month, $month_for) === false) {
                        $new_month .= ", " . $month_for;
                    }
                }
                
                $update_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, remark = ?, month_for = ? WHERE id = ?");
                $update_stmt->bind_param("dssi", $new_amount, $new_remark, $new_month, $existing['id']);
                $update_stmt->execute();
                $invoice_id = $existing['id'];
            } else {
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
                log_activity('auto_bill_generated', "Automated bill of ₹" . number_format($total_amount, 2) . " generated for student " . $student['name'] . " ($month_for)");
            }

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
