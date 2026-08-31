<?php
// admin/billing_dry_run.php - Safe Read-Only Simulation for Automated Monthly Billing
require_once 'includes/auth.php';

$settings = getAllSettings();
$tuition_modes = !empty($settings['tuition_modes']) ? json_decode($settings['tuition_modes'], true) : [];

// Evaluation date (Default: Today / 1st of current month in IST)
date_default_timezone_set('Asia/Kolkata');
$engine_eval_date = date('Y-m-d');
$current_eval_first = date('Y-m-01', strtotime($engine_eval_date));
$current_eval_last  = date('Y-m-t', strtotime($engine_eval_date));
$current_eval_name  = date('F Y', strtotime($engine_eval_date));
$current_eval_month = date('F', strtotime($engine_eval_date));
$current_eval_dt    = new DateTime($current_eval_first);
$current_eval_dt->setTime(0, 0, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Simulator (Dry-Run) - ABSS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/head_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; margin: 0; }
        .sim-table th { padding: 14px 16px; background: #f1f5f9; color: #334155; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        .sim-table td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 0.88rem; vertical-align: top; }
        .badge-skip { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 700; font-size: 0.78rem; display: inline-block; }
        .badge-consolidate { background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 50px; font-weight: 700; font-size: 0.78rem; display: inline-block; }
        .badge-new { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 50px; font-weight: 700; font-size: 0.78rem; display: inline-block; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 25px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-shield-alt" style="color: #2563eb;"></i> Safe Billing Simulator (Dry-Run Mode)
                    </h1>
                    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">
                        Preview exact calculations for <strong><?php echo $current_eval_name; ?></strong>. <span style="color: #16a34a; font-weight: 700;">100% READ-ONLY</span> (Does NOT modify database).
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="fees.php" class="btn" style="background: #0f172a; color: #fff; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Fees Ledger
                    </a>
                </div>
            </div>

            <!-- Simulation Results Table -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow-x: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                <table class="sim-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Scholar Mode</th>
                            <th>Current Status</th>
                            <th>Action Proposed</th>
                            <th>New Month Fee</th>
                            <th>Updated Total Due</th>
                            <th>Itemized Breakdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $students_res = $conn->query("SELECT * FROM students WHERE status = 'active' ORDER BY id ASC");
                        $total_due_students = 0;
                        $total_simulated_fee = 0;

                        while ($st = $students_res->fetch_assoc()) {
                            $sid = (int)$st['id'];
                            $sname = $st['name'];
                            $scholar_mode = trim($st['scholar_mode'] ?? '');
                            $base_fee = (float)$st['base_fee'];
                            $discount = (float)$st['monthly_discount'];

                            // Resolve fee
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

                            // Check addons
                            $addons_sum = 0;
                            $addon_parts = [];
                            $addons_query = $conn->query("SELECT addon_name, amount FROM student_addons WHERE student_id = $sid");
                            while ($ad = $addons_query->fetch_assoc()) {
                                $ad_amt = (float)$ad['amount'];
                                $addons_sum += $ad_amt;
                                $addon_parts[] = $ad['addon_name'] . ": ₹" . number_format($ad_amt, 2);
                            }

                            // Check unbilled expenses
                            $exp_sum = 0;
                            $exp_parts = [];
                            $exp_query = $conn->query("SELECT item_name, amount FROM student_expenses WHERE student_id = $sid AND status = 'unbilled'");
                            while ($ex = $exp_query->fetch_assoc()) {
                                $ex_amt = (float)$ex['amount'];
                                $exp_sum += $ex_amt;
                                $exp_parts[] = $ex['item_name'] . ": ₹" . number_format($ex_amt, 2);
                            }

                            $month_new_total = $net_base_fee + $addons_sum + $exp_sum;

                            // Check if already billed for current month
                            $chk = $conn->prepare("SELECT id, amount, month_for, status FROM fees_generated WHERE student_id = ? AND (month_for LIKE ? OR month_for LIKE ?) LIMIT 1");
                            $l_full = "%" . $current_eval_name . "%";
                            $l_short = "%" . $current_eval_month . "%";
                            $chk->bind_param("iss", $sid, $l_full, $l_short);
                            $chk->execute();
                            $existing_bill = $chk->get_result()->fetch_assoc();

                            // Check if student has an existing unpaid bill
                            $unpaid_res = $conn->query("SELECT id, amount, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid' ORDER BY id DESC LIMIT 1");
                            $existing_unpaid = $unpaid_res->fetch_assoc();

                            if ($existing_bill) {
                                $action_badge = '<span class="badge-skip">Already Billed (Skip)</span>';
                                $action_desc = "Bill #{$existing_bill['id']} ({$existing_bill['status']}) covers {$current_eval_name}";
                                $proposed_amt = "₹ 0.00";
                                $final_total = "₹ " . number_format($existing_bill['amount'], 2);
                                $breakdown = "No new charges required for $current_eval_name.";
                            } else {
                                $total_due_students++;
                                $total_simulated_fee += $month_new_total;

                                if ($existing_unpaid) {
                                    $action_badge = '<span class="badge-consolidate">Consolidate into Bill #' . $existing_unpaid['id'] . '</span>';
                                    $action_desc = "Append $current_eval_name to existing unpaid balance";
                                    $proposed_amt = "<strong style='color: #2563eb;'>+ ₹ " . number_format($month_new_total, 2) . "</strong>";
                                    $updated_sum = (float)$existing_unpaid['amount'] + $month_new_total;
                                    $final_total = "<strong style='color: #dc2626;'>₹ " . number_format($updated_sum, 2) . "</strong><br><small style='color:#64748b;'>(Previous: ₹" . number_format($existing_unpaid['amount'], 2) . ")</small>";
                                } else {
                                    $action_badge = '<span class="badge-new">Create New Bill</span>';
                                    $action_desc = "Create standalone invoice for $current_eval_name";
                                    $proposed_amt = "<strong style='color: #2563eb;'>₹ " . number_format($month_new_total, 2) . "</strong>";
                                    $final_total = "<strong style='color: #dc2626;'>₹ " . number_format($month_new_total, 2) . "</strong>";
                                }

                                $breakdown_items = [];
                                $fee_lbl = !empty($scholar_mode) ? "$scholar_mode Fee" : "Tuition Fee";
                                $breakdown_items[] = "$fee_lbl: ₹" . number_format($net_base_fee, 2);
                                if (!empty($addon_parts)) $breakdown_items = array_merge($breakdown_items, $addon_parts);
                                if (!empty($exp_parts)) $breakdown_items = array_merge($breakdown_items, $exp_parts);
                                $breakdown = implode("<br>", $breakdown_items);
                            }
                            ?>
                            <tr>
                                <td style="font-weight: 700; color: #0f172a;">
                                    <?php echo htmlspecialchars($sname); ?><br>
                                    <small style="color: #64748b; font-weight: 500;">ID: #<?php echo $sid; ?></small>
                                </td>
                                <td style="color: #334155; font-weight: 600;">
                                    <?php echo htmlspecialchars($scholar_mode ?: 'Day Scholar'); ?>
                                </td>
                                <td>
                                    <?php echo $action_badge; ?><br>
                                    <small style="color: #64748b;"><?php echo $action_desc; ?></small>
                                </td>
                                <td style="font-weight: 600; color: #475569;">
                                    <?php echo $existing_bill ? 'No Action' : 'Add Monthly Fee'; ?>
                                </td>
                                <td>
                                    <?php echo $proposed_amt; ?>
                                </td>
                                <td>
                                    <?php echo $final_total; ?>
                                </td>
                                <td style="font-size: 0.82rem; color: #475569; line-height: 1.5;">
                                    <?php echo $breakdown; ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Footer Card -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 25px;">
                <div style="background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Students Requiring Bill</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #0f172a; margin-top: 5px;"><?php echo $total_due_students; ?> Active Student(s)</div>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Billing Amount (New Month)</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #2563eb; margin-top: 5px;">₹ <?php echo number_format($total_simulated_fee, 2); ?></div>
                </div>
                <div style="background: #eff6ff; padding: 20px; border-radius: 10px; border: 1px solid #bfdbfe; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 0.92rem;"><i class="fas fa-check-circle"></i> Simulation Safe</div>
                    <div style="font-size: 0.82rem; color: #1e3a8a; margin-top: 4px;">Zero changes were written to your database.</div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
