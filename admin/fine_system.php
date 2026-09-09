<?php
// admin/fine_system.php - Centralized Late Fine Management & Per-Student Fine Control System

require_once 'includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fine_helper.php';

$conn = getDB();
$settings = function_exists('getAllSettings') ? getAllSettings() : [];

$msg = '';
$err = '';

// Handle AJAX or POST: Toggle Single Student Fine Status (ON / OFF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_student_fine') {
    $student_id = (int)$_POST['student_id'];
    $fine_status = (int)$_POST['fine_status']; // 1 = ON, 0 = OFF (Exempt)
    
    if ($student_id > 0) {
        $stmt = $conn->prepare("UPDATE students SET fine_applicable = ? WHERE id = ?");
        $stmt->bind_param("ii", $fine_status, $student_id);
        $success = $stmt->execute();
        
        if (function_exists('log_activity')) {
            $act_name = $fine_status ? 'fine_enabled_for_student' : 'fine_exempted_for_student';
            log_activity($act_name, "Late fine set to " . ($fine_status ? 'ON (Applied)' : 'OFF (Exempt)') . " for student ID $student_id");
        }
        
        // Return JSON for AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'student_id' => $student_id,
                'fine_applicable' => $fine_status,
                'message' => $fine_status ? 'Late fine turned ON for student' : 'Late fine turned OFF (Exempt) for student'
            ]);
            exit;
        }
        
        $msg = "Student late fine status updated successfully.";
    } else {
        $err = "Invalid student ID.";
    }
}

// Handle Bulk Student Fine Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['selected_students'])) {
    $bulk_action = trim($_POST['bulk_action']);
    $student_ids = array_map('intval', $_POST['selected_students']);
    
    if (!empty($student_ids)) {
        $target_val = ($bulk_action === 'enable') ? 1 : 0;
        $ids_str = implode(',', $student_ids);
        $conn->query("UPDATE students SET fine_applicable = $target_val WHERE id IN ($ids_str)");
        
        if (function_exists('log_activity')) {
            log_activity('bulk_fine_status_updated', "Updated fine status to " . ($target_val ? 'ON' : 'OFF') . " for " . count($student_ids) . " students");
        }
        $msg = "Successfully updated fine status to " . ($target_val ? 'ON (Applied)' : 'OFF (Exempt)') . " for " . count($student_ids) . " student(s).";
    }
}

// Handle Global Fine System Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_global_settings'])) {
    $fine_enabled = isset($_POST['fine_system_enabled']) ? '1' : '0';
    $rate = max(0, (float)$_POST['fine_rate_per_day']);
    $grace = max(1, (int)$_POST['fine_grace_days']);
    $start_month = trim($_POST['fine_start_month'] ?? '');
    $escalation = isset($_POST['fine_escalation_enabled']) ? '1' : '0';
    $esc_start = trim($_POST['fine_escalation_start_month'] ?? '');
    
    saveSetting('fine_system_enabled', $fine_enabled);
    saveSetting('fine_rate_per_day', $rate);
    saveSetting('fine_grace_days', $grace);
    saveSetting('fine_start_month', $start_month);
    saveSetting('fine_escalation_enabled', $escalation);
    saveSetting('fine_escalation_start_month', $esc_start);
    
    $settings = function_exists('getAllSettings') ? getAllSettings() : [];
    
    if (function_exists('log_activity')) {
        log_activity('fine_settings_updated', "Saved global late fine settings: Enabled=$fine_enabled, Rate=₹$rate/day, Grace=$grace days");
    }
    $msg = "Global late fine system rules updated successfully.";
}

// Global System Status
$is_global_fine_on = is_fine_system_enabled($settings);
$fine_rate = get_fine_rate_per_day($settings);
$fine_grace = get_fine_grace_days($settings);
$fine_start_month = get_fine_start_month($settings);
$fine_escalation = is_fine_escalation_enabled($settings);

// Fetch All Students with Fine & Dues Breakdown
$students_res = $conn->query("
    SELECT s.id, s.name, s.reg_no, s.class_admitted, s.scholar_mode, s.parent_name, s.phone, s.fine_applicable, s.status
    FROM students s
    WHERE s.status = 'active'
    ORDER BY s.name ASC
");

$students_data = [];
$total_school_fine = 0.00;
$count_fine_on = 0;
$count_fine_off = 0;
$count_with_dues = 0;

if ($students_res) {
    while ($st = $students_res->fetch_assoc()) {
        $sid = (int)$st['id'];
        $is_fine_on = (int)($st['fine_applicable'] ?? 1);
        
        if ($is_fine_on) {
            $count_fine_on++;
        } else {
            $count_fine_off++;
        }
        
        // Query unpaid bills for this student
        $bills_q = $conn->query("SELECT id, amount, billing_date, month_for FROM fees_generated WHERE student_id = $sid AND status = 'unpaid'");
        $unpaid_bills_count = 0;
        $unpaid_base_total = 0.00;
        $student_calculated_fine = 0.00;
        $max_overdue_days = 0;
        
        if ($bills_q) {
            while ($b = $bills_q->fetch_assoc()) {
                $unpaid_bills_count++;
                $unpaid_base_total += (float)$b['amount'];
                
                // If fine is enabled for this student and system is on, calculate fine
                if ($is_fine_on && $is_global_fine_on) {
                    $fine_info = calculate_bill_fine($b, $settings);
                    $student_calculated_fine += (float)$fine_info['fine_amount'];
                    if ($fine_info['overdue_days'] > $max_overdue_days) {
                        $max_overdue_days = $fine_info['overdue_days'];
                    }
                }
            }
        }
        
        if ($unpaid_bills_count > 0) {
            $count_with_dues++;
        }
        
        $total_school_fine += $student_calculated_fine;
        
        $st['unpaid_count'] = $unpaid_bills_count;
        $st['unpaid_base'] = $unpaid_base_total;
        $st['fine_amount'] = $student_calculated_fine;
        $st['overdue_days'] = $max_overdue_days;
        $st['total_due'] = $unpaid_base_total + $student_calculated_fine;
        
        $students_data[] = $st;
    }
}
$total_students_count = count($students_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Late Fine Management System | ABSS Admin</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .fine-metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }
        
        /* Modern Switch Styling */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            vertical-align: middle;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .25s ease;
            border-radius: 26px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .25s ease;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.18);
        }
        input:checked + .slider {
            background-color: #16a34a;
        }
        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .badge-fine-on {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-fine-off {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .filter-tab-btn {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-tab-btn:hover {
            background: #e2e8f0;
            color: var(--portal-dark);
        }
        .filter-tab-btn.active {
            background: var(--portal-blue);
            color: #ffffff;
            border-color: var(--portal-blue);
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.25);
        }

        .btn-quick-toggle {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-quick-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
        }

        .rules-drawer-body {
            display: none;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px dashed #e2e8f0;
        }
        .rules-drawer-body.open {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Top Title Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:25px;">
            <div>
                <h1 style="margin:0; font-size: 1.65rem; font-weight:800; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-hand-holding-usd" style="color:#dc2626;"></i> Late Fine Management System
                </h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.9rem;">
                    Manage global late fee rules and toggle late fine ON or OFF individually for each student.
                </p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" onclick="toggleRulesDrawer()" class="btn-portal" style="background:#f1f5f9; color:#1e293b; border:1px solid #cbd5e1; padding:10px 18px; font-size:0.88rem; box-shadow:none;">
                    <i class="fas fa-sliders-h"></i> Global Fine Rules <i class="fas fa-chevron-down" id="rulesChevron" style="margin-left:5px; font-size:0.8rem; transition:transform 0.2s;"></i>
                </button>
                <a href="fees.php" class="btn-portal" style="padding:10px 18px; font-size:0.88rem; text-decoration:none;">
                    <i class="fas fa-history"></i> Fee Ledger
                </a>
            </div>
        </div>

        <?php if($msg): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <!-- Top Metrics Grid -->
        <div class="fine-metric-grid">
            <!-- Metric 1: System Status -->
            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid <?php echo $is_global_fine_on ? '#16a34a' : '#64748b'; ?>; background: <?php echo $is_global_fine_on ? '#f0fdf4' : '#f8fafc'; ?>; margin-bottom: 0;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: <?php echo $is_global_fine_on ? '#dcfce7' : '#e2e8f0'; ?>; color: <?php echo $is_global_fine_on ? '#16a34a' : '#64748b'; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-power-off"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.15rem; color: <?php echo $is_global_fine_on ? '#15803d' : '#475569'; ?>; font-weight: 800;">
                        <?php echo $is_global_fine_on ? 'SYSTEM ACTIVE' : 'SYSTEM DISABLED'; ?>
                    </h3>
                    <span style="font-size: 0.72rem; color: #64748b; font-weight: 700;">
                        Rate: ₹<?php echo number_format($fine_rate, 2); ?>/day (Grace: <?php echo $fine_grace; ?> days)
                    </span>
                </div>
            </div>

            <!-- Metric 2: Total Unpaid Fine -->
            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #dc2626; background: #fef2f2; margin-bottom: 0;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-coins"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #991b1b; font-weight: 800;">
                        ₹ <?php echo number_format($total_school_fine, 2); ?>
                    </h3>
                    <span style="font-size: 0.72rem; color: #dc2626; font-weight: 800; text-transform: uppercase;">
                        Total Active Unpaid Fines
                    </span>
                </div>
            </div>

            <!-- Metric 3: Fine ON Students -->
            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #2563eb; background: #eff6ff; margin-bottom: 0;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #1e3a8a; font-weight: 800;">
                        <?php echo $count_fine_on; ?> Students
                    </h3>
                    <span style="font-size: 0.72rem; color: #2563eb; font-weight: 800; text-transform: uppercase;">
                        Fine ON (Applicable)
                    </span>
                </div>
            </div>

            <!-- Metric 4: Fine OFF Students -->
            <div class="portal-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #7c3aed; background: #faf5ff; margin-bottom: 0;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #581c87; font-weight: 800;">
                        <?php echo $count_fine_off; ?> Students
                    </h3>
                    <span style="font-size: 0.72rem; color: #7c3aed; font-weight: 800; text-transform: uppercase;">
                        Fine OFF (Exempt)
                    </span>
                </div>
            </div>
        </div>

        <!-- Collapsible Card: Global Fine Configuration Rules -->
        <div class="portal-card" id="globalRulesDrawer" style="margin-bottom: 25px;">
            <div style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="toggleRulesDrawer()">
                <h3 style="margin:0; font-size: 1.15rem; font-weight:800; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-cog" style="color:var(--portal-blue);"></i> Global Late Fee System Configuration
                </h3>
                <span style="font-size:0.8rem; font-weight:700; color:var(--portal-blue);">
                    Click to Expand / Collapse
                </span>
            </div>

            <div class="rules-drawer-body" id="rulesDrawerBody">
                <form action="" method="POST">
                    <input type="hidden" name="save_global_settings" value="1">

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:20px;">
                        <!-- Master Toggle -->
                        <div style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <label style="font-weight:800; color:#1e293b; font-size:0.92rem;">
                                    Master Late Fine System
                                </label>
                                <label class="switch">
                                    <input type="checkbox" name="fine_system_enabled" value="1" <?php echo $is_global_fine_on ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <small style="color:#64748b; display:block; font-size:0.8rem;">
                                Turn late fines ON or OFF across the entire portal. If turned OFF, all student fines drop to ₹0.00.
                            </small>
                        </div>

                        <!-- Daily Rate -->
                        <div class="portal-input-group" style="margin-bottom:0;">
                            <label style="font-weight:800; font-size:0.88rem;">Daily Fine Rate (₹ per day)</label>
                            <input type="number" step="0.50" min="0" name="fine_rate_per_day" value="<?php echo htmlspecialchars($fine_rate); ?>" required style="font-weight:700;">
                            <small style="color:#64748b; font-size:0.78rem;">e.g. ₹5.00 charged for every overdue day past the grace period.</small>
                        </div>

                        <!-- Grace Days -->
                        <div class="portal-input-group" style="margin-bottom:0;">
                            <label style="font-weight:800; font-size:0.88rem;">Grace Period Deadline (Day of Month)</label>
                            <input type="number" min="1" max="28" name="fine_grace_days" value="<?php echo htmlspecialchars($fine_grace); ?>" required style="font-weight:700;">
                            <small style="color:#64748b; font-size:0.78rem;">e.g. 5 means late fine starts calculating from the 6th onwards.</small>
                        </div>

                        <!-- Fine Start Month -->
                        <div class="portal-input-group" style="margin-bottom:0;">
                            <label style="font-weight:800; font-size:0.88rem;">Fine System Start Month (YYYY-MM)</label>
                            <input type="text" name="fine_start_month" placeholder="2026-09" value="<?php echo htmlspecialchars($fine_start_month ?? ''); ?>" style="font-weight:700;">
                            <small style="color:#64748b; font-size:0.78rem;">Invoices issued before this month incur ₹0 fine. Leave blank for no restriction.</small>
                        </div>

                        <!-- Escalation Toggle -->
                        <div style="background:#f8fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <label style="font-weight:800; color:#1e293b; font-size:0.92rem;">
                                    Carry-Over Multiplier Escalation
                                </label>
                                <label class="switch">
                                    <input type="checkbox" name="fine_escalation_enabled" value="1" <?php echo $fine_escalation ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <small style="color:#64748b; display:block; font-size:0.8rem;">
                                Multiplies daily fine rate for unpaid invoices carried over from previous months past grace deadline.
                            </small>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn-portal" style="padding:11px 24px;">
                            <i class="fas fa-save"></i> Save Global Rules
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Per-Student Fine Application & Exemption Control -->
        <div class="portal-card">
            <!-- Header Controls & Filters -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px; border-bottom:2px solid #f1f5f9; padding-bottom:15px;">
                <div>
                    <h3 style="margin:0; font-size: 1.25rem; font-weight:800; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-user-shield" style="color:#2563eb;"></i> Per-Student Fine Control
                    </h3>
                    <div style="font-size:0.82rem; color:#64748b; margin-top:3px;">
                        Toggle late fine ON or OFF (Exempt) separately for individual students.
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <!-- Filter Pills -->
                    <button type="button" class="filter-tab-btn active" onclick="filterTableByTab('all', this)">
                        All (<?php echo $total_students_count; ?>)
                    </button>
                    <button type="button" class="filter-tab-btn" onclick="filterTableByTab('on', this)">
                        Fine ON (<?php echo $count_fine_on; ?>)
                    </button>
                    <button type="button" class="filter-tab-btn" onclick="filterTableByTab('off', this)">
                        Fine OFF / Exempt (<?php echo $count_fine_off; ?>)
                    </button>
                    <button type="button" class="filter-tab-btn" onclick="filterTableByTab('dues', this)">
                        With Dues (<?php echo $count_with_dues; ?>)
                    </button>

                    <!-- Search Input -->
                    <input type="text" id="student_search_input" onkeyup="filterStudentFineTable()" placeholder="🔍 Search student, reg no, class..." style="padding:8px 14px; border-radius:8px; border:1px solid #cbd5e1; font-size:0.84rem; min-width:200px;">
                </div>
            </div>

            <!-- Bulk Actions Form -->
            <form id="bulkFineForm" method="POST" style="margin-bottom:15px;">
                <input type="hidden" name="bulk_action" id="bulk_action_input" value="">
                
                <div id="bulkControlsRow" style="display:none; align-items:center; gap:10px; padding:10px 16px; background:#eff6ff; border-radius:8px; margin-bottom:15px; border:1px solid #bfdbfe;">
                    <span style="font-size:0.85rem; font-weight:800; color:#1e40af;">
                        <span id="selectedStudentsCount">0</span> student(s) selected:
                    </span>
                    <button type="button" onclick="submitBulkFineAction('enable')" class="btn-quick-toggle" style="background:#16a34a; color:#fff; border-color:#16a34a;">
                        <i class="fas fa-check-circle"></i> Turn ON Fine
                    </button>
                    <button type="button" onclick="submitBulkFineAction('exempt')" class="btn-quick-toggle" style="background:#64748b; color:#fff; border-color:#64748b;">
                        <i class="fas fa-shield-alt"></i> Turn OFF / Exempt Fine
                    </button>
                </div>

                <div class="mobile-table-hint">
                    <i class="fas fa-arrows-left-right"></i> Scroll table sideways to view details &amp; toggles
                </div>
                <div class="portal-table-container">
                    <table id="studentFineTable">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align:center;">
                                    <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" style="cursor:pointer; width:16px; height:16px;">
                                </th>
                                <th>Student Details</th>
                                <th>Class &amp; Mode</th>
                                <th>Parent Contact</th>
                                <th>Unpaid Invoices</th>
                                <th>Late Fine Incurred</th>
                                <th>Total Payable</th>
                                <th style="text-align:center; min-width:140px;">Fine ON / OFF Switch</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($students_data)): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:35px; color:#94a3b8;">
                                        No active students found in the portal.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($students_data as $s): 
                                    $fine_active = (int)$s['fine_applicable'];
                                    $has_dues = ($s['unpaid_count'] > 0);
                                ?>
                                    <tr class="student-fine-row" 
                                        data-status="<?php echo $fine_active ? 'on' : 'off'; ?>"
                                        data-dues="<?php echo $has_dues ? 'yes' : 'no'; ?>"
                                        id="student_row_<?php echo $s['id']; ?>">
                                        
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="selected_students[]" value="<?php echo $s['id']; ?>" class="student-checkbox" onclick="updateBulkSelection()" style="cursor:pointer; width:16px; height:16px;">
                                        </td>
                                        
                                        <td>
                                            <strong class="search-name" style="color:var(--portal-dark); font-size:0.92rem;">
                                                <?php echo htmlspecialchars($s['name']); ?>
                                            </strong>
                                            <?php if(!empty($s['reg_no'])): ?>
                                                <div class="search-reg" style="font-family:monospace; font-size:0.75rem; color:#64748b; font-weight:700;">
                                                    <?php echo htmlspecialchars($s['reg_no']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="search-class" style="font-weight:700; color:#334155; font-size:0.85rem;">
                                                <?php echo htmlspecialchars($s['class_admitted'] ?? 'Class'); ?>
                                            </span>
                                            <div style="font-size:0.74rem; color:#64748b;">
                                                <?php echo htmlspecialchars($s['scholar_mode'] ?? 'Day Scholar'); ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div style="font-weight:700; color:#334155; font-size:0.85rem;">
                                                <?php echo htmlspecialchars($s['parent_name'] ? $s['parent_name'] : 'N/A'); ?>
                                            </div>
                                            <?php if(!empty($s['phone'])): ?>
                                                <small style="color:#64748b;"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($s['phone']); ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($s['unpaid_count'] > 0): ?>
                                                <span style="font-weight:800; color:#dc2626; font-size:0.85rem;">
                                                    <?php echo $s['unpaid_count']; ?> Unpaid
                                                </span>
                                                <div style="font-size:0.75rem; color:#64748b;">
                                                    Base: ₹<?php echo number_format($s['unpaid_base'], 2); ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-weight:700; color:#16a34a; font-size:0.82rem;">
                                                    <i class="fas fa-check-circle"></i> Clear (₹0)
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td id="fine_amt_cell_<?php echo $s['id']; ?>">
                                            <?php if (!$fine_active): ?>
                                                <span class="badge-fine-off" title="Fine is turned OFF for this student">
                                                    <i class="fas fa-shield-alt"></i> EXEMPT (₹0.00)
                                                </span>
                                            <?php elseif (!$is_global_fine_on): ?>
                                                <span class="badge-fine-off" title="Global fine system is turned off">
                                                    <i class="fas fa-power-off"></i> Disabled (₹0.00)
                                                </span>
                                            <?php elseif ($s['fine_amount'] > 0): ?>
                                                <span style="font-weight:900; color:#dc2626; font-size:0.95rem;">
                                                    ₹ <?php echo number_format($s['fine_amount'], 2); ?>
                                                </span>
                                                <div style="font-size:0.72rem; color:#ea580c; font-weight:700;">
                                                    <?php echo $s['overdue_days']; ?> Days Overdue
                                                </div>
                                            <?php else: ?>
                                                <span style="font-weight:700; color:#16a34a; font-size:0.85rem;">
                                                    ₹ 0.00
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td id="total_due_cell_<?php echo $s['id']; ?>">
                                            <span style="font-weight:800; color:var(--portal-dark); font-size:0.95rem;">
                                                ₹ <?php echo number_format($s['total_due'], 2); ?>
                                            </span>
                                        </td>

                                        <!-- ON / OFF Interactive Switch -->
                                        <td style="text-align:center;">
                                            <div style="display:inline-flex; align-items:center; gap:8px;">
                                                <label class="switch">
                                                    <input type="checkbox" 
                                                           id="fine_switch_<?php echo $s['id']; ?>"
                                                           onchange="toggleStudentFineAjax(<?php echo $s['id']; ?>, this.checked)"
                                                           <?php echo $fine_active ? 'checked' : ''; ?>>
                                                    <span class="slider"></span>
                                                </label>
                                                <span id="fine_badge_<?php echo $s['id']; ?>" class="<?php echo $fine_active ? 'badge-fine-on' : 'badge-fine-off'; ?>">
                                                    <?php echo $fine_active ? 'ON' : 'OFF'; ?>
                                                </span>
                                            </div>
                                        </td>

                                        <td style="text-align:center;">
                                            <a href="fees.php?filter=unpaid" class="btn-quick-toggle" title="View in Fee Ledger">
                                                <i class="fas fa-file-invoice"></i> Ledger
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Toggle Global Rules Drawer
        function toggleRulesDrawer() {
            const body = document.getElementById('rulesDrawerBody');
            const icon = document.getElementById('rulesChevron');
            if (body.classList.contains('open')) {
                body.classList.remove('open');
                if (icon) icon.style.transform = 'rotate(0deg)';
            } else {
                body.classList.add('open');
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
        }

        // Live AJAX Toggle for Student Fine (ON / OFF)
        function toggleStudentFineAjax(studentId, isChecked) {
            const badge = document.getElementById('fine_badge_' + studentId);
            const row = document.getElementById('student_row_' + studentId);
            const statusInt = isChecked ? 1 : 0;
            
            // Visual optimistic update
            if (badge) {
                badge.className = isChecked ? 'badge-fine-on' : 'badge-fine-off';
                badge.innerText = isChecked ? 'ON' : 'OFF';
            }
            if (row) {
                row.setAttribute('data-status', isChecked ? 'on' : 'off');
            }

            const formData = new FormData();
            formData.append('action', 'toggle_student_fine');
            formData.append('student_id', studentId);
            formData.append('fine_status', statusInt);

            fetch('fine_system.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update fine cell if exempt
                    const fineCell = document.getElementById('fine_amt_cell_' + studentId);
                    if (!isChecked && fineCell) {
                        fineCell.innerHTML = '<span class="badge-fine-off" title="Fine is turned OFF for this student"><i class="fas fa-shield-alt"></i> EXEMPT (₹0.00)</span>';
                    } else if (isChecked && fineCell) {
                        // Reload page or let user see live changes
                        window.location.reload();
                    }
                } else {
                    alert('Failed to update fine status: ' + (data.message || 'Unknown error'));
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error('AJAX Error:', err);
                window.location.reload();
            });
        }

        // Select All Handler
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = master.checked;
                }
            });
            updateBulkSelection();
        }

        // Update Bulk Controls Visibility
        function updateBulkSelection() {
            const checked = document.querySelectorAll('.student-checkbox:checked');
            const row = document.getElementById('bulkControlsRow');
            const counter = document.getElementById('selectedStudentsCount');
            
            if (checked.length > 0) {
                row.style.display = 'flex';
                counter.innerText = checked.length;
            } else {
                row.style.display = 'none';
            }
        }

        // Submit Bulk Action
        function submitBulkFineAction(actionType) {
            const checked = document.querySelectorAll('.student-checkbox:checked');
            if (checked.length === 0) return;

            const actionLabel = actionType === 'enable' ? 'TURN ON Fine' : 'TURN OFF / EXEMPT Fine';
            if (confirm(`Are you sure you want to ${actionLabel} for ${checked.length} selected student(s)?`)) {
                document.getElementById('bulk_action_input').value = actionType;
                document.getElementById('bulkFineForm').submit();
            }
        }

        // Live Search Filter for Students Table
        function filterStudentFineTable() {
            const input = document.getElementById('student_search_input');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('studentFineTable');
            const rows = table.getElementsByClassName('student-fine-row');

            for (let i = 0; i < rows.length; i++) {
                const nameCol = rows[i].querySelector('.search-name');
                const regCol = rows[i].querySelector('.search-reg');
                const classCol = rows[i].querySelector('.search-class');

                const nameText = nameCol ? nameCol.textContent.toLowerCase() : '';
                const regText = regCol ? regCol.textContent.toLowerCase() : '';
                const classText = classCol ? classCol.textContent.toLowerCase() : '';

                if (nameText.includes(filter) || regText.includes(filter) || classText.includes(filter)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        // Filter Table by Status Tab (All / Fine ON / Fine OFF / With Dues)
        function filterTableByTab(type, btn) {
            // Update active button styling
            const buttons = document.querySelectorAll('.filter-tab-btn');
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const table = document.getElementById('studentFineTable');
            const rows = table.getElementsByClassName('student-fine-row');

            for (let i = 0; i < rows.length; i++) {
                const status = rows[i].getAttribute('data-status');
                const dues = rows[i].getAttribute('data-dues');

                if (type === 'all') {
                    rows[i].style.display = '';
                } else if (type === 'on') {
                    rows[i].style.display = (status === 'on') ? '' : 'none';
                } else if (type === 'off') {
                    rows[i].style.display = (status === 'off') ? '' : 'none';
                } else if (type === 'dues') {
                    rows[i].style.display = (dues === 'yes') ? '' : 'none';
                }
            }
        }
    </script>
</body>
</html>
