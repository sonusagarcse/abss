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
 * Calculates late fine for a specific bill based on billing_date and current/target date.
 * Rule: 1st till grace_days (e.g. 5th) is fine-free. 6th onwards = overdue_days * fine_rate.
 * 
 * @param string $billing_date e.g. "2026-08-01"
 * @param array|null $settings
 * @param string|null $as_of_date e.g. "2026-08-17"
 * @return array
 */
if (!function_exists('calculate_bill_fine')) {
    function calculate_bill_fine($billing_date, $settings = null, $as_of_date = null) {
        if (!is_fine_system_enabled($settings)) {
            return [
                'fine_amount'    => 0.00,
                'overdue_days'   => 0,
                'grace_date'     => null,
                'is_fine_active' => false,
                'rate_per_day'   => 0.00,
                'grace_days'     => 5
            ];
        }

        $grace_days = get_fine_grace_days($settings);
        $rate = get_fine_rate_per_day($settings);

        if (empty($billing_date)) {
            $billing_date = date('Y-m-01');
        }

        $bill_time = strtotime($billing_date);
        $bill_year = date('Y', $bill_time);
        $bill_month = date('m', $bill_time);

        // Grace deadline date (e.g., 2026-08-05)
        $grace_date_str = sprintf('%04d-%02d-%02d', $bill_year, $bill_month, min(28, $grace_days));
        $grace_dt = new DateTime($grace_date_str);
        $grace_dt->setTime(23, 59, 59);

        $eval_date_str = !empty($as_of_date) ? $as_of_date : date('Y-m-d');
        $eval_dt = new DateTime($eval_date_str);
        $eval_dt->setTime(0, 0, 0);

        $grace_eval = new DateTime($grace_date_str);
        $grace_eval->setTime(0, 0, 0);

        if ($eval_dt <= $grace_eval) {
            // Within grace period (1st - 5th): Fine Free
            return [
                'fine_amount'    => 0.00,
                'overdue_days'   => 0,
                'grace_date'     => $grace_date_str,
                'is_fine_active' => false,
                'rate_per_day'   => $rate,
                'grace_days'     => $grace_days
            ];
        }

        // Past grace deadline: calculate exact days overdue
        $diff = $grace_eval->diff($eval_dt);
        $overdue_days = (int)$diff->days;
        $fine_amount = round($overdue_days * $rate, 2);

        return [
            'fine_amount'    => $fine_amount,
            'overdue_days'   => $overdue_days,
            'grace_date'     => $grace_date_str,
            'is_fine_active' => ($fine_amount > 0),
            'rate_per_day'   => $rate,
            'grace_days'     => $grace_days
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

        $res = $conn->query("SELECT id, amount, billing_date, month_for FROM fees_generated WHERE student_id = $student_id AND status = 'unpaid'");
        if ($res) {
            while ($b = $res->fetch_assoc()) {
                $calc = calculate_bill_fine($b['billing_date'], $settings, $as_of_date);
                $total_fine += $calc['fine_amount'];
                $unpaid_count++;
                $breakdown[] = [
                    'bill_id'      => (int)$b['id'],
                    'month_for'    => $b['month_for'],
                    'base_amount'  => (float)$b['amount'],
                    'fine_amount'  => $calc['fine_amount'],
                    'overdue_days' => $calc['overdue_days'],
                    'total_payable'=> round((float)$b['amount'] + $calc['fine_amount'], 2)
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

        $total_fine = 0.00;
        $res = $conn->query("SELECT billing_date FROM fees_generated WHERE status = 'unpaid'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $calc = calculate_bill_fine($row['billing_date'], $settings, $as_of_date);
                $total_fine += $calc['fine_amount'];
            }
        }

        return round($total_fine, 2);
    }
}
