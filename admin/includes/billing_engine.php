<?php
// admin/includes/billing_engine.php - Robust Automated Monthly Fee Generation Engine
// CRITICAL: This handles financial data - every calculation must be exact.

// Ensure timezone matches school's physical location (India Standard Time)
// Without this, PHP may use a different server timezone (e.g., Europe/Berlin UTC+1)
// causing billing to evaluate for the wrong month when IST is already past midnight.
date_default_timezone_set('Asia/Kolkata');

if (!isset($skip_email)) {
    $skip_email = true; // Default to skipping SMTP on web page hits
}

// Determine evaluation date (supports simulated test date for testing)
$engine_eval_date = (!empty($simulated_test_date)) ? $simulated_test_date : date('Y-m-d');
$current_eval_first = date('Y-m-01', strtotime($engine_eval_date));
$current_eval_last  = date('Y-m-t', strtotime($engine_eval_date));
$current_eval_name  = date('F Y', strtotime($engine_eval_date));  // e.g., "September 2026"
$current_eval_month = date('F', strtotime($engine_eval_date));    // e.g., "September"
$current_eval_year  = (int)date('Y', strtotime($engine_eval_date));

$current_eval_dt = new DateTime($current_eval_first);
$current_eval_dt->setTime(0, 0, 0);

$billing_generated_count = 0;
$billing_processed_students = [];

/**
 * Check if a specific month's bill already exists for a student.
 * Handles BOTH legacy format ("May", "June") AND new format ("May 2026", "June 2026")
 * AND comma-separated formats ("June, July 2026", "May, August 2026").
 *
 * The LIKE '%MonthName 2026%' check FAILS for:
 *   - "May"              → does NOT contain "May 2026"  (legacy, no year)
 *   - "June, July 2026"  → does NOT contain "June 2026" (comma separates "June" from year)
 *
 * So we ALSO check LIKE '%MonthName%' (just month name, no year) which matches all formats.
 * This is safe within a single academic year (no month name is a substring of another).
 */
if (!function_exists('monthAlreadyBilled')) {
    function monthAlreadyBilled($conn, $sid, $month_name_full, $month_name_only) {
        $stmt = $conn->prepare(
            "SELECT id FROM fees_generated WHERE student_id = ? AND (month_for LIKE ? OR month_for LIKE ?) LIMIT 1"
        );
        $like_full  = "%" . $month_name_full . "%";   // e.g., "%September 2026%"
        $like_short = "%" . $month_name_only . "%";    // e.g., "%September%"
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

    // Dynamic host URL builder
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $fe_host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
    $fe_base_url = (strpos($fe_host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$fe_host";
    $portal_url = "$fe_base_url/parent/login.php";

    while ($student = $active_students->fetch_assoc()) {
        $sid     = (int)$student['id'];
        $sname   = $student['name'];
        $base_fee = (float)$student['base_fee'];
        $discount = (float)$student['monthly_discount'];
        $scholar_mode = trim($student['scholar_mode'] ?? '');

        // ── Resolve base fee if not set in student profile ──
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

        // ── Collect unbilled daily expenses ──
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

        // ════════════════════════════════════════════════════════════════
        // PRIMARY CHECK: Does this student ALREADY have a bill for the current evaluation month?
        // ════════════════════════════════════════════════════════════════
        $has_current_month = monthAlreadyBilled($conn, $sid, $current_eval_name, $current_eval_month);

        if ($has_current_month && !isset($force_student_id)) {
            // Already billed for current month. Attach any daily unbilled expenses if present
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

            // Sync last_billed_date to end of current month if it was lagging behind
            $conn->query("UPDATE students SET last_billed_date = '$current_eval_last' WHERE id = $sid AND (last_billed_date IS NULL OR last_billed_date < '$current_eval_last')");
            continue; // Skip tuition generation since current month is already generated
        }

        // ════════════════════════════════════════════════════════════════
        // Student does NOT have current month's bill yet:
        // Determine starting cycle month
        // ════════════════════════════════════════════════════════════════
        $start_month = null;

        if (!empty($student['last_billed_date']) && $student['last_billed_date'] < $current_eval_first) {
            $last_billed = new DateTime($student['last_billed_date']);
            $start_month = clone $last_billed;
            $start_month->modify('first day of next month');
            $start_month->setTime(0, 0, 0);
        } elseif (!empty($student['admission_date'])) {
            $adm_dt  = new DateTime($student['admission_date']);
            $adm_day = (int)$adm_dt->format('j');

            if ($adm_day > 1) {
                // Mid-month admission: first regular tuition starts 1st of next month
                $start_month = clone $adm_dt;
                $start_month->modify('first day of next month');
                $start_month->setTime(0, 0, 0);
            } else {
                // Admitted on 1st → start billing from this month
                $start_month = new DateTime($adm_dt->format('Y-m-01'));
                $start_month->setTime(0, 0, 0);
            }
        } else {
            // Default to current evaluation month
            $start_month = new DateTime($current_eval_first);
            $start_month->setTime(0, 0, 0);
        }

        // Safety: if start_month is beyond current eval month, skip tuition
        if ($start_month > $current_eval_dt && !isset($force_student_id)) {
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
            continue;
        }

        // ════════════════════════════════════════════════════════════════
        // SEQUENTIAL MONTH LOOP: Generate bills from start_month → current eval month
        // ════════════════════════════════════════════════════════════════
        $cur_month_dt = clone $start_month;
        $cur_month_dt->setTime(0, 0, 0);
        $student_billed_any = false;

        while ($cur_month_dt <= $current_eval_dt) {
            $month_for        = $cur_month_dt->format('F Y');    // "September 2026"
            $month_name_only  = $cur_month_dt->format('F');      // "September"
            $bill_month_date  = $cur_month_dt->format('Y-m-01');
            $end_of_target    = $cur_month_dt->format('Y-m-t');

            // ── Check if this month is already billed (handles legacy + new formats) ──
            $already_billed = monthAlreadyBilled($conn, $sid, $month_for, $month_name_only);

            if ($already_billed && !isset($force_student_id)) {
                // Month already exists in fees_generated → just sync last_billed_date forward
                $conn->query("UPDATE students SET last_billed_date = '$end_of_target' WHERE id = $sid AND (last_billed_date IS NULL OR last_billed_date < '$end_of_target')");
                $cur_month_dt->modify('first day of next month');
                $cur_month_dt->setTime(0, 0, 0);
                continue;
            }

            // ── Calculate fee line items for this month ──
            $total_amount = 0;
            $remark_parts = [];

            // Base tuition fee
            $calc_net  = round($net_base_fee, 2);
            $fee_title = !empty($scholar_mode) ? "$scholar_mode Fee" : "Tuition Fee";
            $remark_parts[] = "$fee_title: ₹" . number_format($calc_net, 2) . " ($month_for)";
            $total_amount  += $calc_net;

            // Monthly recurring addons (tagged with the specific month)
            $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
            if ($addons_query && $addons_query->num_rows > 0) {
                while ($addon = $addons_query->fetch_assoc()) {
                    $calc_addon = round((float)$addon['amount'], 2);
                    $total_amount  += $calc_addon;
                    $remark_parts[] = $addon['addon_name'] . ": ₹" . number_format($calc_addon, 2) . " ($month_for)";
                }
            }

            // Attach unbilled daily expenses ONLY to the first generated month (avoid double-counting)
            if (!empty($exp_ids)) {
                $total_amount  += $exp_amount;
                $remark_parts   = array_merge($remark_parts, $exp_remarks);
            }

            if ($total_amount <= 0 && empty($remark_parts)) {
                if (!isset($force_student_id)) {
                    $conn->query("UPDATE students SET last_billed_date = '$end_of_target' WHERE id = $sid AND (last_billed_date IS NULL OR last_billed_date < '$end_of_target')");
                }
                $cur_month_dt->modify('first day of next month');
                $cur_month_dt->setTime(0, 0, 0);
                continue;
            }

            $new_month_remark = implode(" | ", $remark_parts);

            // ── Transaction: Add as new rows in existent unpaid bill or create new bill ──
            $conn->begin_transaction();
            try {
                // Check if student already has an existent unpaid bill
                $existing_unpaid_res = $conn->query("SELECT id, amount, remark, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
                $existing_unpaid = ($existing_unpaid_res && $existing_unpaid_res->num_rows > 0) ? $existing_unpaid_res->fetch_assoc() : null;

                if ($existing_unpaid) {
                    // Consolidate into existing unpaid bill
                    $updated_amount = round((float)$existing_unpaid['amount'] + $total_amount, 2);
                    $month_parts = array_map('trim', explode(',', $existing_unpaid['month_for']));
                    if (!in_array($month_for, $month_parts)) {
                        $month_parts[] = $month_for;
                    }
                    $updated_month_for = implode(', ', $month_parts);

                    // Ensure legacy bills with empty remarks have their previous balance represented
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
                    // No existent unpaid bill: Create new bill
                    $final_remark = "Auto-generated Bill. " . $new_month_remark;
                    $stmt = $conn->prepare("INSERT INTO fees_generated (student_id, amount, month_for, billing_date, remark, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
                    $stmt->bind_param("idsss", $sid, $total_amount, $month_for, $bill_month_date, $final_remark);
                    $stmt->execute();
                    $invoice_id = $conn->insert_id;
                }

                // Mark expenses as billed (only once, for the first month)
                if (!empty($exp_ids)) {
                    $ids_str = implode(",", $exp_ids);
                    $conn->query("UPDATE student_expenses SET status = 'billed', billed_at = NOW() WHERE id IN ($ids_str)");
                    $exp_ids     = [];  // Clear after first use
                    $exp_amount  = 0;
                    $exp_remarks = [];
                }

                // Update last_billed_date to end of this month
                $conn->query("UPDATE students SET last_billed_date = '$end_of_target' WHERE id = $sid");
                $conn->commit();

                $student_billed_any = true;

                if (function_exists('log_activity')) {
                    log_activity('auto_bill_generated', "Automated bill of ₹" . number_format($total_amount, 2) . " processed for student " . $sname . " ($month_for)");
                }

                // Create In-Built Portal Notification for Parent
                if (function_exists('create_portal_notification')) {
                    create_portal_notification(
                        'bill',
                        "New Fee Invoice Generated ($month_for)",
                        "Academic fee invoice of ₹" . number_format($total_amount, 2) . " for " . $sname . " is available for payment.",
                        "view_bill.php?id=$invoice_id",
                        !empty($student['parent_id']) ? (int)$student['parent_id'] : null,
                        $sid,
                        'fa-file-invoice-dollar',
                        '#dc2626'
                    );
                }

                // Send email if enabled
                if (!$skip_email && !empty($student['parent_id'])) {
                    $parent_res = $conn->query("SELECT email, parent_name FROM parents WHERE id = " . (int)$student['parent_id']);
                    if ($parent_res && $parent_res->num_rows > 0) {
                        $parent = $parent_res->fetch_assoc();
                        $bill_view_url = "$fe_base_url/parent/view_bill.php?id=$invoice_id";

                        $email_html = get_fee_generated_template(
                            $sname,
                            $total_amount,
                            $month_for,
                            $bill_month_date,
                            $new_month_remark . " | Bill ID: #$invoice_id",
                            $bill_view_url
                        );

                        if (!empty($parent['email']) && function_exists('send_smtp_email')) {
                            send_smtp_email(
                                $parent['email'],
                                "New Tuition Fee Invoice #" . $invoice_id . " Generated - " . $sname . " - ABSS",
                                $email_html
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Billing Engine Error for Student ID $sid: " . $e->getMessage());
            }

            $cur_month_dt->modify('first day of next month');
            $cur_month_dt->setTime(0, 0, 0);
        }

        if ($student_billed_any) {
            $billing_generated_count++;
            $billing_processed_students[] = $sname;
        }
    }
}
?>
