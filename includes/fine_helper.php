<?php
// includes/fine_helper.php - Centralized Late Fee / Fine Management Module for ABSS

if (!function_exists('is_fine_system_enabled')) {
    function is_fine_system_enabled($settings = null) {
        if ($settings === null && function_exists('getAllSettings')) {
            $settings = getAllSettings();
        }
        $val = $settings['fine_system_enabled'] ?? '1';
        return ($val === '1' || strtolower($val) === 'yes' || strtolower($val) === 'true' || strtolower($val) === 'on');
    }
}

if (!function_exists('get_fine_grace_days')) {
    function get_fine_grace_days($settings = null) {
        if ($settings === null && function_exists('getAllSettings')) {
            $settings = getAllSettings();
        }
        return isset($settings['fine_grace_days']) ? max(1, (int)$settings['fine_grace_days']) : 5;
    }
}

if (!function_exists('get_fine_rate_per_day')) {
    function get_fine_rate_per_day($settings = null) {
        if ($settings === null && function_exists('getAllSettings')) {
            $settings = getAllSettings();
        }
        return isset($settings['fine_rate_per_day']) ? max(0.00, (float)$settings['fine_rate_per_day']) : 5.00;
    }
}

/**
 * Returns the fine start month as 'YYYY-MM', or null if not set.
 * Bills issued BEFORE this month are exempt from fine.
 */
if (!function_exists('get_fine_start_month')) {
    function get_fine_start_month($settings = null) {
        if ($settings === null && function_exists('getAllSettings')) {
            $settings = getAllSettings();
        }
        $val = trim($settings['fine_start_month'] ?? '');
        // Validate YYYY-MM format
        if (preg_match('/^\d{4}-\d{2}$/', $val)) {
            return $val;
        }
        return null; // null = no restriction, apply fine to all bills
    }
}

/**
 * Returns whether the escalating double-rate fine is enabled.
 * When enabled: carry-over unpaid bills from previous months incur 2x daily rate
 * from the current month's grace deadline onwards.
 */
if (!function_exists('is_fine_escalation_enabled')) {
    function is_fine_escalation_enabled($settings = null) {
        if ($settings === null && function_exists('getAllSettings')) {
            $settings = getAllSettings();
        }
        $val = $settings['fine_escalation_enabled'] ?? '0';
        return ($val === '1' || strtolower($val) === 'yes' || strtolower($val) === 'true' || strtolower($val) === 'on');
    }
}

/**
 * Returns the escalation start month as 'YYYY-MM', or null if not set.
 * Escalation logic applies only from this month onwards.
 */
if (!function_exists('get_fine_escalation_start_month')) {
    function get_fine_escalation_start_month($settings = null) {
        if ($settings === null && function_exists('getAllSettings')) {
            $settings = getAllSettings();
        }
        $val = trim($settings['fine_escalation_start_month'] ?? '');
        if (preg_match('/^\d{4}-\d{2}$/', $val)) {
            return $val;
        }
        return null;
    }
}

/**
 * Calculates late fine for a specific bill.
 *
 * Rules:
 * 1. If fine_start_month is set and bill is from before that month → zero fine.
 * 2. Grace period (1st to grace_days): no fine.
 * 3. After grace period: overdue_days * rate.
 * 4. [Escalation] If escalation is enabled, the bill is a carry-over (from a previous month),
 *    and the current month >= escalation_start_month: the rate doubles (×2) for days
 *    past the *current month's* grace deadline.
 *
 * @param string      $billing_date   e.g. "2026-08-01"
 * @param array|null  $settings
 * @param string|null $as_of_date     e.g. "2026-09-17" (defaults to today)
 * @param bool        $is_carry_over  True if this bill is from a previous month (caller sets this)
 * @return array
 */
/**
 * Helper to parse all distinct YYYY-MM months from a bill's month_for string (e.g. "September 2026, August 2026")
 */
if (!function_exists('parse_bill_months_for')) {
    function parse_bill_months_for($month_for_str, $default_billing_date = null) {
        $months = [];
        $default_year = !empty($default_billing_date) ? date('Y', strtotime($default_billing_date)) : date('Y');
        
        $parts = explode(',', (string)$month_for_str);
        
        $last_year = $default_year;
        foreach (array_reverse($parts) as $p) {
            if (preg_match('/\b(20\d\d)\b/', $p, $m)) {
                $last_year = $m[1];
                break;
            }
        }
        
        $month_names = [
            'january' => '01', 'jan' => '01',
            'february' => '02', 'feb' => '02',
            'march' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'may' => '05',
            'june' => '06', 'jun' => '06',
            'july' => '07', 'jul' => '07',
            'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'december' => '12', 'dec' => '12',
        ];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            $year = $last_year;
            if (preg_match('/\b(20\d\d)\b/', $part, $ym)) {
                $year = $ym[1];
            }
            
            foreach ($month_names as $m_name => $m_num) {
                if (stripos($part, $m_name) !== false) {
                    $ym_key = "$year-$m_num";
                    if (!in_array($ym_key, $months)) {
                        $months[] = $ym_key;
                    }
                    break;
                }
            }
        }

        sort($months);

        if (empty($months) && !empty($default_billing_date)) {
            $months[] = date('Y-m', strtotime($default_billing_date));
        }

        return $months;
    }
}

/**
 * Calculates late fine for a single billing month (YYYY-MM).
 */
if (!function_exists('calculate_single_month_fine')) {
    function calculate_single_month_fine($bill_ym, $settings = null, $as_of_date = null) {
        $grace_days = get_fine_grace_days($settings);
        $base_rate  = get_fine_rate_per_day($settings);

        $eval_date_str = !empty($as_of_date) ? $as_of_date : date('Y-m-d');
        $eval_dt = new DateTime($eval_date_str);
        $eval_dt->setTime(0, 0, 0);

        $current_month = date('Y-m', strtotime($eval_date_str));

        // Fine Start Month Gate
        $fine_start_month = get_fine_start_month($settings);
        if ($fine_start_month !== null && $current_month < $fine_start_month) {
            return [
                'fine_amount'        => 0.00,
                'overdue_days'       => 0,
                'grace_date'         => null,
                'is_fine_active'     => false,
                'rate_per_day'       => $base_rate,
                'grace_days'         => $grace_days,
                'escalation_applied' => false,
            ];
        }

        $parts = explode('-', $bill_ym);
        $bill_year = (int)($parts[0] ?? date('Y'));
        $bill_month_num = (int)($parts[1] ?? date('m'));

        // If a start month is set in settings, bills from before start month begin counting from fine_start_month grace date
        if ($fine_start_month !== null && $bill_ym < $fine_start_month) {
            $fsm_parts = explode('-', $fine_start_month);
            $eff_year  = (int)($fsm_parts[0] ?? $bill_year);
            $eff_month = (int)($fsm_parts[1] ?? $bill_month_num);
        } else {
            $eff_year  = $bill_year;
            $eff_month = $bill_month_num;
        }

        $grace_date_str = sprintf('%04d-%02d-%02d', $eff_year, $eff_month, min(28, $grace_days));
        $grace_dt_bill  = new DateTime($grace_date_str);
        $grace_dt_bill->setTime(0, 0, 0);

        if ($eval_dt <= $grace_dt_bill) {
            return [
                'fine_amount'        => 0.00,
                'overdue_days'       => 0,
                'grace_date'         => $grace_date_str,
                'is_fine_active'     => false,
                'rate_per_day'       => $base_rate,
                'grace_days'         => $grace_days,
                'escalation_applied' => false,
            ];
        }

        $diff_normal = $grace_dt_bill->diff($eval_dt);
        $total_overdue_days = (int)$diff_normal->days;

        $is_carry_over = ($bill_ym < $current_month);
        $escalation_enabled     = is_fine_escalation_enabled($settings);
        $escalation_start_month = get_fine_escalation_start_month($settings);

        $escalation_qualifies = (
            $escalation_enabled &&
            $is_carry_over &&
            ($escalation_start_month === null || $current_month >= $escalation_start_month || $bill_ym >= $escalation_start_month)
        );

        if ($escalation_qualifies) {
            $cur_year_int  = (int)date('Y', strtotime($eval_date_str));
            $cur_month_int = (int)date('m', strtotime($eval_date_str));

            // Months outstanding inclusive of bill month and current month
            $months_since = max(1, ($cur_year_int - $bill_year) * 12 + ($cur_month_int - $bill_month_num) + 1);

            $cur_grace_date_str = sprintf('%04d-%02d-%02d', $cur_year_int, $cur_month_int, min(28, $grace_days));
            $grace_dt_cur = new DateTime($cur_grace_date_str);
            $grace_dt_cur->setTime(0, 0, 0);

            if ($eval_dt > $grace_dt_cur) {
                // Segment A: bill grace -> current month grace @ base rate * 1
                $diff_a = $grace_dt_bill->diff($grace_dt_cur);
                $days_a = (int)$diff_a->days;

                // Segment B: current month grace -> eval date @ base rate * months_since
                $diff_b = $grace_dt_cur->diff($eval_dt);
                $days_b = (int)$diff_b->days;

                $escalated_rate     = $base_rate * $months_since;
                $fine_amount        = round(($days_a * $base_rate) + ($days_b * $escalated_rate), 2);
                $escalation_applied = ($days_b > 0);
            } else {
                $fine_amount = round($total_overdue_days * $base_rate, 2);
                $escalation_applied = false;
            }
        } else {
            $fine_amount = round($total_overdue_days * $base_rate, 2);
            $escalation_applied = false;
        }

        return [
            'fine_amount'        => $fine_amount,
            'overdue_days'       => $total_overdue_days,
            'grace_date'         => $grace_date_str,
            'is_fine_active'     => ($fine_amount > 0),
            'rate_per_day'       => $base_rate,
            'grace_days'         => $grace_days,
            'escalation_applied' => $escalation_applied,
        ];
    }
}

/**
 * Calculates late fee/fine for an invoice/bill.
 * Accepts either:
 *  - string billing_date (e.g. "2026-09-01")
 *  - array bill row (containing 'billing_date' and 'month_for')
 */
if (!function_exists('calculate_bill_fine')) {
    function calculate_bill_fine($bill_input, $settings = null, $as_of_date = null, $is_carry_over = false, $month_for_str = null) {
        if (!is_fine_system_enabled($settings)) {
            return [
                'fine_amount'        => 0.00,
                'overdue_days'       => 0,
                'grace_date'         => null,
                'is_fine_active'     => false,
                'rate_per_day'       => 0.00,
                'grace_days'         => 5,
                'escalation_applied' => false,
            ];
        }

        $grace_days = get_fine_grace_days($settings);
        $base_rate  = get_fine_rate_per_day($settings);

        if (is_array($bill_input)) {
            $billing_date  = $bill_input['billing_date'] ?? date('Y-m-01');
            $month_for_str = $bill_input['month_for'] ?? '';
        } else {
            $billing_date = (string)$bill_input;
        }

        if (empty($billing_date)) {
            $billing_date = date('Y-m-01');
        }

        // Parse all unpaid months covered by this bill (e.g. ["2026-08", "2026-09"])
        $months = parse_bill_months_for($month_for_str, $billing_date);

        $total_fine = 0.00;
        $max_overdue_days = 0;
        $earliest_grace_date = null;
        $any_escalation = false;

        foreach ($months as $m_ym) {
            $m_calc = calculate_single_month_fine($m_ym, $settings, $as_of_date);
            $total_fine += $m_calc['fine_amount'];
            if ($m_calc['overdue_days'] > $max_overdue_days) {
                $max_overdue_days = $m_calc['overdue_days'];
            }
            if ($m_calc['grace_date'] !== null && ($earliest_grace_date === null || $m_calc['grace_date'] < $earliest_grace_date)) {
                $earliest_grace_date = $m_calc['grace_date'];
            }
            if ($m_calc['escalation_applied']) {
                $any_escalation = true;
            }
        }

        return [
            'fine_amount'        => round($total_fine, 2),
            'overdue_days'       => $max_overdue_days,
            'grace_date'         => $earliest_grace_date,
            'is_fine_active'     => ($total_fine > 0),
            'rate_per_day'       => $base_rate,
            'grace_days'         => $grace_days,
            'escalation_applied' => $any_escalation,
            'months_counted'     => $months,
        ];
    }
}

/**
 * Calculates total fine across all unpaid bills for a specific student.
 */
if (!function_exists('get_student_total_fine')) {
    function get_student_total_fine($student_id, $conn, $settings = null, $as_of_date = null) {
        $student_id = (int)$student_id;
        $total_fine = 0.00;
        $unpaid_count = 0;
        $breakdown = [];

        if (!is_fine_system_enabled($settings)) {
            return [
                'total_fine'   => 0.00,
                'unpaid_count' => 0,
                'breakdown'    => []
            ];
        }

        $eval_date_str = !empty($as_of_date) ? $as_of_date : date('Y-m-d');

        $res = $conn->query("SELECT id, amount, billing_date, month_for FROM fees_generated WHERE student_id = $student_id AND status = 'unpaid'");
        if ($res) {
            while ($b = $res->fetch_assoc()) {
                $calc = calculate_bill_fine($b, $settings, $eval_date_str);
                $total_fine   += $calc['fine_amount'];
                $unpaid_count++;
                $breakdown[] = [
                    'bill_id'            => (int)$b['id'],
                    'month_for'          => $b['month_for'],
                    'base_amount'        => (float)$b['amount'],
                    'fine_amount'        => $calc['fine_amount'],
                    'overdue_days'       => $calc['overdue_days'],
                    'total_payable'      => round((float)$b['amount'] + $calc['fine_amount'], 2),
                    'escalation_applied' => $calc['escalation_applied'],
                    'months_counted'     => $calc['months_counted'] ?? [],
                ];
            }
        }

        return [
            'total_fine'   => round($total_fine, 2),
            'unpaid_count' => $unpaid_count,
            'breakdown'    => $breakdown
        ];
    }
}

/**
 * Calculates aggregate total late fine across all unpaid bills school-wide (for Admin Dashboard).
 */
if (!function_exists('get_all_unpaid_total_fine')) {
    function get_all_unpaid_total_fine($conn, $settings = null, $as_of_date = null) {
        if (!is_fine_system_enabled($settings)) {
            return 0.00;
        }

        $eval_date_str = !empty($as_of_date) ? $as_of_date : date('Y-m-d');
        $total_fine    = 0.00;

        $res = $conn->query("SELECT id, amount, billing_date, month_for FROM fees_generated WHERE status = 'unpaid'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $calc = calculate_bill_fine($row, $settings, $eval_date_str);
                $total_fine += $calc['fine_amount'];
            }
        }

        return round($total_fine, 2);
    }
}
