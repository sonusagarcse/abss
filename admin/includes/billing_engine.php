<?php
// admin/includes/billing_engine.php - Bulletproof Automated Monthly Fee Generation Engine
// CRITICAL: Designed to be 100% idempotent — never adds duplicate amounts on page reloads.

date_default_timezone_set('Asia/Kolkata');

if (!isset($skip_email)) {
    $skip_email = true;
}

// Current Evaluation Cycle (Single Active Month)
$engine_eval_date = (!empty($simulated_test_date)) ? $simulated_test_date : date('Y-m-d');
$current_eval_first = date('Y-m-01', strtotime($engine_eval_date));
$current_eval_last  = date('Y-m-t', strtotime($engine_eval_date));
$current_eval_name  = date('F Y', strtotime($engine_eval_date));  // e.g., "September 2026"
$current_eval_month = date('F', strtotime($engine_eval_date));    // e.g., "September"

$billing_generated_count = 0;
$billing_processed_students = [];

if (!function_exists('monthAlreadyBilled')) {
    function monthAlreadyBilled($conn, $sid, $month_name_full, $month_name_only) {
        $stmt = $conn->prepare(
            "SELECT id FROM fees_generated WHERE student_id = ? AND (month_for LIKE ? OR month_for LIKE ?) LIMIT 1"
        );
        $like_full  = "%" . $month_name_full . "%";
        $like_short = "%" . $month_name_only . "%";
        $stmt->bind_param("iss", $sid, $like_full, $like_short);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
}

// Build student query
if (isset($force_student_id) && $force_student_id > 0) {
    $query = "
        SELECT id, name, scholar_mode, base_fee, monthly_discount, parent_id, admission_date, last_billed_date
        FROM students
        WHERE id = " . (int)$force_student_id . " AND status = 'active'
    ";
} else {
    $query = "
        SELECT id, name, scholar_mode, base_fee, monthly_discount, parent_id, admission_date, last_billed_date
        FROM students
        WHERE status = 'active'
        ORDER BY id ASC
    ";
}

$active_students = $conn->query($query);

if ($active_students && $active_students->num_rows > 0) {
    if (!$skip_email) {
        require_once __DIR__ . '/../../includes/mail_helper.php';
    }

    $settings = function_exists('getAllSettings') ? getAllSettings() : [];
    $tuition_modes = !empty($settings['tuition_modes']) ? json_decode($settings['tuition_modes'], true) : [];

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $fe_host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
    $fe_base_url = (strpos($fe_host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$fe_host";

    while ($student = $active_students->fetch_assoc()) {
        $sid     = (int)$student['id'];
        $sname   = $student['name'];
        $base_fee = (float)$student['base_fee'];
        $discount = (float)$student['monthly_discount'];
        $scholar_mode = trim($student['scholar_mode'] ?? '');

        // ── 1. Check if student already has a bill for current evaluation month ──
        $already_billed = monthAlreadyBilled($conn, $sid, $current_eval_name, $current_eval_month);
        
        // ── 2. Collect any unbilled daily expenses ──
        $exp_query = $conn->query("SELECT id, item_name, amount FROM student_expenses WHERE student_id = $sid AND status = 'unbilled'");
        $exp_ids = [];
        $exp_amount = 0;
        $exp_remarks = [];
        if ($exp_query && $exp_query->num_rows > 0) {
            while ($exp = $exp_query->fetch_assoc()) {
                $exp_amount += (float)$exp['amount'];
                $exp_ids[] = $exp['id'];
                $exp_remarks[] = $exp['item_name'] . " (Expense): ₹" . number_format($exp['amount'], 2);
            }
        }

        // If student is ALREADY billed for this month:
        if ($already_billed && !isset($force_student_id)) {
            // Attach unbilled daily expenses only if any exist
            if ($exp_amount > 0) {
                $exp_remark_str = implode(" | ", $exp_remarks);
                $existing_unpaid_res = $conn->query("SELECT id, amount, remark FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
                if ($existing_unpaid_res && $existing_unpaid_res->num_rows > 0) {
                    $eu = $existing_unpaid_res->fetch_assoc();
                    $new_total = (float)$eu['amount'] + $exp_amount;
                    $new_remark = $eu['remark'] . " | " . $exp_remark_str;
                    $u = $conn->prepare("UPDATE fees_generated SET amount = ?, remark = ? WHERE id = ?");
                    $u->bind_param("dsi", $new_total, $new_remark, $eu['id']);
                    $u->execute();
                } else {
                    $billing_date = date('Y-m-d', strtotime($engine_eval_date));
                    $final_remark = "Daily Expense. " . $exp_remark_str;
                    $s = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
                    $s->bind_param("idsss", $sid, $exp_amount, $current_eval_name, $billing_date, $final_remark);
                    $s->execute();
                }
                $ids_str = implode(",", $exp_ids);
                $conn->query("UPDATE student_expenses SET status = 'billed', billed_at = NOW() WHERE id IN ($ids_str)");
            }

            // Sync last_billed_date
            $conn->query("UPDATE students SET last_billed_date = '$current_eval_last' WHERE id = $sid AND (last_billed_date IS NULL OR last_billed_date < '$current_eval_last')");
            continue; // 100% IDEMPOTENT: Exits immediately with 0 additions!
        }

        // ── 3. Check mid-month admission rule for current month ──
        if (!empty($student['admission_date'])) {
            $adm_dt = new DateTime($student['admission_date']);
            $adm_month_first = $adm_dt->format('Y-m-01');
            $adm_day = (int)$adm_dt->format('j');
            // If admitted mid-month in this same current month, first auto regular bill starts 1st of next month
            if ($adm_month_first === $current_eval_first && $adm_day > 1 && !isset($force_student_id)) {
                continue;
            }
        }

        // ── 4. Calculate Fee for Current Month ──
        if ($base_fee <= 0) {
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
        $net_base_fee = max(0, $base_fee - $discount);

        $month_for = $current_eval_name;
        $bill_month_date = $current_eval_first;
        $end_of_target = $current_eval_last;

        $total_amount = 0;
        $remark_parts = [];

        // Base fee
        $calc_net = round($net_base_fee, 2);
        $fee_title = !empty($scholar_mode) ? "$scholar_mode Fee" : "Tuition Fee";
        $remark_parts[] = "$fee_title: ₹" . number_format($calc_net, 2) . " ($month_for)";
        $total_amount += $calc_net;

        // Recurring monthly addons
        $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
        if ($addons_query && $addons_query->num_rows > 0) {
            while ($addon = $addons_query->fetch_assoc()) {
                $calc_addon = round((float)$addon['amount'], 2);
                $total_amount += $calc_addon;
                $remark_parts[] = $addon['addon_name'] . ": ₹" . number_format($calc_addon, 2) . " ($month_for)";
            }
        }

        // Unbilled daily expenses
        if (!empty($exp_ids)) {
            $total_amount += $exp_amount;
            $remark_parts = array_merge($remark_parts, $exp_remarks);
        }

        if ($total_amount <= 0 && empty($remark_parts)) {
            $conn->query("UPDATE students SET last_billed_date = '$end_of_target' WHERE id = $sid");
            continue;
        }

        $new_month_remark = implode(" | ", $remark_parts);

        // ── 5. Apply In-Place Consolidation into Existent Unpaid Bill OR Create New Bill ──
        $conn->begin_transaction();
        try {
            $existing_unpaid_res = $conn->query("SELECT id, amount, remark, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
            $existing_unpaid = ($existing_unpaid_res && $existing_unpaid_res->num_rows > 0) ? $existing_unpaid_res->fetch_assoc() : null;

            if ($existing_unpaid) {
                // Secondary safeguard: ensure this month is NOT already in month_for
                $month_parts = array_map('trim', explode(',', $existing_unpaid['month_for']));
                if (!in_array($month_for, $month_parts)) {
                    $month_parts[] = $month_for;
                    $updated_month_for = implode(', ', $month_parts);
                    $updated_amount = round((float)$existing_unpaid['amount'] + $total_amount, 2);

                    $existing_rem = trim($existing_unpaid['remark'] ?? '');
                    if (empty($existing_rem)) {
                        $prev_label = "Previous Balance (" . $existing_unpaid['month_for'] . "): ₹" . number_format((float)$existing_unpaid['amount'], 2);
                        $updated_remark = $prev_label . " | " . $new_month_remark;
                    } else {
                        $updated_remark = $existing_rem . " | " . $new_month_remark;
                    }

                    $update_stmt = $conn->prepare("UPDATE fees_generated SET amount = ?, month_for = ?, remark = ? WHERE id = ?");
                    $update_stmt->bind_param("dssi", $updated_amount, $updated_month_for, $updated_remark, $existing_unpaid['id']);
                    $update_stmt->execute();
                    $invoice_id = $existing_unpaid['id'];
                } else {
                    $invoice_id = $existing_unpaid['id'];
                }
            } else {
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

            $conn->query("UPDATE students SET last_billed_date = '$end_of_target' WHERE id = $sid");
            $conn->commit();

            $billing_generated_count++;
            $billing_processed_students[] = $sname;

            if (function_exists('log_activity')) {
                log_activity('auto_bill_generated', "Automated bill of ₹" . number_format($total_amount, 2) . " processed for student " . $sname . " ($month_for)");
            }

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Billing Engine Error for Student ID $sid: " . $e->getMessage());
        }
    }
}
?>
