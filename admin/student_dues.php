<?php
// admin/student_dues.php - Student Dues & Arrears Statement (Printable & Exportable)

require_once 'includes/auth.php';

// Trigger automated billing engine to ensure latest month bills are calculated
$skip_email = true;
require_once 'includes/billing_engine.php';

// Current session and cutoff details
$current_month_name = date('F Y');

// Fetch all students who have unpaid generated bills
$dues_query = $conn->query("
    SELECT 
        s.id,
        s.reg_no,
        s.name,
        s.parent_name,
        s.guardian_relationship,
        s.phone,
        s.guardian_email,
        p.email AS parent_email,
        s.home_address,
        s.city,
        s.state,
        s.zip_code,
        s.guardian_address,
        s.class_admitted,
        s.scholar_mode,
        s.target_school,
        s.base_fee,
        s.security_amount,
        COALESCE(unpaid.latest_bill_id, 0) AS latest_bill_id,
        COALESCE(unpaid.total_due, 0) AS total_due,
        COALESCE(unpaid.unpaid_count, 0) AS unpaid_count,
        COALESCE(unpaid.due_months, '') AS due_months
    FROM students s
    LEFT JOIN parents p ON s.parent_id = p.id
    INNER JOIN (
        SELECT 
            student_id,
            MAX(id) AS latest_bill_id,
            SUM(amount) AS total_due,
            COUNT(id) AS unpaid_count,
            GROUP_CONCAT(DISTINCT month_for ORDER BY billing_date ASC SEPARATOR ', ') AS due_months
        FROM fees_generated
        WHERE status = 'unpaid'
        GROUP BY student_id
    ) unpaid ON s.id = unpaid.student_id
    WHERE s.status = 'active'
    ORDER BY total_due DESC, s.name ASC
");

$dues_list = [];
$total_due_amount = 0.0;
$total_base_due = 0.0;
$total_fine_due = 0.0;
$total_defaulters = 0;
$total_unpaid_invoices = 0;

if ($dues_query) {
    $settings = function_exists('getAllSettings') ? getAllSettings() : [];
    while ($row = $dues_query->fetch_assoc()) {
        $sid = (int)$row['id'];
        $fine_info = function_exists('get_student_total_fine') ? get_student_total_fine($sid, $conn, $settings) : ['total_fine' => 0.00];
        $row['fine_amount'] = (float)$fine_info['total_fine'];
        $row['base_due'] = (float)$row['total_due'];
        $row['total_due'] = $row['base_due'] + $row['fine_amount'];
        $row['unpaid_count'] = (int)$row['unpaid_count'];
        
        // Build formatted complete address
        $addr_parts = [];
        if (!empty($row['home_address'])) $addr_parts[] = trim($row['home_address']);
        elseif (!empty($row['guardian_address'])) $addr_parts[] = trim($row['guardian_address']);
        
        if (!empty($row['city'])) $addr_parts[] = trim($row['city']);
        if (!empty($row['state'])) $addr_parts[] = trim($row['state']);
        if (!empty($row['zip_code'])) $addr_parts[] = trim($row['zip_code']);
        
        $row['full_address'] = !empty($addr_parts) ? implode(', ', $addr_parts) : 'Address not specified';
        
        $dues_list[] = $row;
        $total_base_due += $row['base_due'];
        $total_fine_due += $row['fine_amount'];
        $total_due_amount += $row['total_due'];
        $total_defaulters++;
        $total_unpaid_invoices += $row['unpaid_count'];
    }
}

$avg_due_per_student = $total_defaulters > 0 ? ($total_due_amount / $total_defaulters) : 0;

// Determine portal URL for parent WhatsApp links
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
$base_app_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
$parent_portal_url = "$base_app_url/parent/login.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dues & Arrears Statement | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* Dues Dashboard & Table Styling */
        .dues-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stats-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        /* Filter Console */
        .filter-glass-box {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            padding: 22px 26px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            margin-bottom: 25px;
        }

        .filter-grid-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 14px;
            align-items: center;
        }

        .search-field-box {
            position: relative;
            width: 100%;
        }
        .search-field-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
        }
        .search-field-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border-radius: var(--radius-md);
            border: 2px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.92rem;
            font-weight: 600;
            outline: none;
            transition: 0.25s;
            box-sizing: border-box;
        }
        .search-field-box input:focus {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .select-filter-box {
            width: 100%;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            border: 2px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            color: #334155;
            outline: none;
            box-sizing: border-box;
        }

        /* Dues Table */
        .dues-table {
            width: 100%;
            min-width: 900px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .dues-table th {
            text-align: left;
            padding: 10px 18px;
            color: var(--portal-blue);
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: transparent;
            border: none;
        }
        .dues-row td {
            padding: 18px 20px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
            transition: background 0.2s;
        }
        .dues-row:hover td {
            background: #f8fafc;
        }
        .dues-row td:first-child {
            border-left: 1px solid #f1f5f9;
            border-radius: 14px 0 0 14px;
        }
        .dues-row td:last-child {
            border-right: 1px solid #f1f5f9;
            border-radius: 0 14px 14px 0;
        }

        .due-amount-pill {
            font-size: 1.12rem;
            font-weight: 900;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .due-month-tag {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-block;
            margin: 2px 3px 2px 0;
            border: 1px solid #fecaca;
        }

        .address-snippet {
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 600;
            line-height: 1.35;
            max-width: 250px;
            word-break: break-word;
        }

        .scholar-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .mode-hostler { background: #f3e8ff; color: #7c3aed; }
        .mode-day { background: #dcfce7; color: #166534; }

        .act-btn {
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .act-btn:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
        }

        /* Email Due Bill Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-card {
            background: #ffffff;
            border-radius: 24px;
            max-width: 540px;
            width: 100%;
            padding: 30px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            border: 1px solid #e2e8f0;
            position: relative;
            animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalPop {
            0% { transform: scale(0.94); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-close-btn {
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            color: #64748b;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .modal-close-btn:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .modal-info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
        }

        /* Print Header Box (Hidden on Web, Visible on Print) */
        .print-only-header {
            display: none;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-family: Arial, sans-serif !important;
            }
            .sidebar, .page-header, .filter-glass-box, .btn-portal, .dues-actions, .stats-kpi-grid, .no-print {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .portal-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                background: transparent !important;
            }
            .print-only-header {
                display: block !important;
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            .print-only-header h1 {
                margin: 0;
                font-size: 1.5rem;
                font-weight: 900;
                color: #000;
                text-transform: uppercase;
            }
            .print-only-header p {
                margin: 3px 0;
                font-size: 0.85rem;
                color: #333;
            }
            .dues-table {
                min-width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9pt !important;
            }
            .dues-table th {
                background: #f1f5f9 !important;
                color: #000 !important;
                border: 1px solid #94a3b8 !important;
                padding: 8px 6px !important;
                font-weight: bold !important;
            }
            .dues-row td {
                background: transparent !important;
                border: 1px solid #cbd5e1 !important;
                padding: 8px 6px !important;
                color: #000 !important;
                border-radius: 0 !important;
            }
            .due-amount-pill {
                color: #000 !important;
                font-weight: 900 !important;
            }
            .due-month-tag {
                background: transparent !important;
                color: #000 !important;
                border: none !important;
                padding: 0 !important;
            }
            .print-footer {
                display: block !important;
                margin-top: 40px;
                padding-top: 20px;
            }
        }

        @media (max-width: 900px) {
            .filter-grid-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Print Header Layout (A4 Print / PDF View) -->
        <div class="print-only-header">
            <h1>AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)</h1>
            <p><strong>Campus:</strong> Imamganj, Gaya (Bihar) - 824206 | <strong>Contact:</strong> +91 9523012888 | <strong>Email:</strong> info@abss.in</p>
            <p style="font-size: 0.95rem; font-weight: 800; margin-top: 6px; text-transform: uppercase;">
                OFFICIAL OUTSTANDING DUES & ARREARS STATEMENT (TILL <?php echo strtoupper($current_month_name); ?>)
            </p>
            <p style="font-size: 0.78rem; color: #555;">Generated on: <?php echo date('d M Y, h:i A'); ?> | Session: 2026-27</p>
        </div>

        <!-- Top Web Navigation Header -->
        <div class="dues-header-row no-print">
            <div>
                <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file-invoice-dollar" style="color: #dc2626;"></i> Student Dues & Arrears Statement
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Comprehensive statement of all pending fee balances & due amounts till <b><?php echo $current_month_name; ?></b>.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn-portal" onclick="bulkSendDueEmails()" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; padding: 12px 18px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);">
                    <i class="fas fa-paper-plane"></i> Email All Dues &amp; PDFs
                </button>
                <?php $all_bill_ids = array_filter(array_column($dues_list, 'latest_bill_id')); ?>
                <?php if (!empty($all_bill_ids)): ?>
                    <a href="bulk_print.php?ids=<?php echo implode(',', $all_bill_ids); ?>" target="_blank" class="btn-portal" style="background: #0f172a; color: #ffffff; padding: 12px 18px; text-decoration: none;">
                        <i class="fas fa-file-pdf"></i> Download All Invoices
                    </a>
                <?php endif; ?>
                <button type="button" class="btn-portal" onclick="window.print()" style="background: #475569; color: #ffffff; padding: 12px 18px;">
                    <i class="fas fa-print"></i> Print Statement
                </button>
                <button type="button" class="btn-portal" onclick="exportDuesToCSV()" style="background: #ffffff; color: #0284c7; border: 2px solid #38bdf8; box-shadow: none; padding: 12px 18px;">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <a href="whatsapp.php" class="btn-portal" style="background: #25d366; color: #ffffff; padding: 12px 18px;">
                    <i class="fab fa-whatsapp"></i> WhatsApp Hub
                </a>
            </div>
        </div>

        <!-- KPI Metrics Summary Cards -->
        <div class="stats-kpi-grid no-print">
            <div class="stat-card" style="border-left: 4px solid #dc2626;">
                <div class="stat-icon icon-red"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h3 style="color: #dc2626;">₹ <?php echo number_format($total_due_amount, 2); ?></h3>
                    <p style="font-weight: 700;">Total Outstanding Dues</p>
                    <small style="color: #dc2626; font-weight: 800; font-size: 0.75rem;">
                        <?php echo number_format($total_unpaid_invoices); ?> Unpaid Invoices
                        <?php if ($total_fine_due > 0): ?>
                            • <span style="color: #64748b; font-weight: 700;">(Base: ₹<?php echo number_format($total_base_due, 2); ?> + Fine: ₹<?php echo number_format($total_fine_due, 2); ?>)</span>
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <div class="stat-card" style="border-left: 4px solid #ea580c;">
                <div class="stat-icon icon-orange"><i class="fas fa-user-times"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_defaulters); ?></h3>
                    <p style="font-weight: 700;">Students With Dues</p>
                    <small style="color: #ea580c; font-weight: 800; font-size: 0.75rem;">Active Enrolled Defaulters</small>
                </div>
            </div>

            <div class="stat-card" style="border-left: 4px solid #7c3aed;">
                <div class="stat-icon icon-purple"><i class="fas fa-calculator"></i></div>
                <div class="stat-info">
                    <h3>₹ <?php echo number_format($avg_due_per_student, 2); ?></h3>
                    <p style="font-weight: 700;">Average Due / Student</p>
                    <small style="color: #7c3aed; font-weight: 800; font-size: 0.75rem;">Per Defaulter Average</small>
                </div>
            </div>

            <div class="stat-card" style="border-left: 4px solid #0284c7;">
                <div class="stat-icon icon-teal"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3 style="font-size: 1.25rem;"><?php echo $current_month_name; ?></h3>
                    <p style="font-weight: 700;">Current Statement Cutoff</p>
                    <small style="color: #0284c7; font-weight: 800; font-size: 0.75rem;">All Dues Computed</small>
                </div>
            </div>
        </div>

        <!-- Filter & Search Box -->
        <div class="filter-glass-box no-print">
            <div class="filter-grid-row">
                <div class="search-field-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="duesSearchInput" placeholder="Search by student name, father name, phone, address, or reg no..." onkeyup="filterDuesTable()">
                </div>

                <div>
                    <select id="classFilterSelect" class="select-filter-box" onchange="filterDuesTable()">
                        <option value="">-- All Classes --</option>
                        <option value="class 5">Class 5 (Preparation)</option>
                        <option value="class 6">Class 6</option>
                        <option value="class 7">Class 7</option>
                        <option value="senior">Senior Section</option>
                    </select>
                </div>

                <div>
                    <select id="modeFilterSelect" class="select-filter-box" onchange="filterDuesTable()">
                        <option value="">-- All Scholar Modes --</option>
                        <option value="hostler">Hostler (Boarding)</option>
                        <option value="day scholar">Day Scholar</option>
                    </select>
                </div>

                <div>
                    <button type="button" class="btn-portal" onclick="resetDuesFilters()" style="background: #f1f5f9; color: #475569; border: 2px solid #cbd5e1; box-shadow: none; padding: 12px 18px;">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 0.85rem; color: #64748b; font-weight: 700;">
                <div>
                    Showing <span id="duesVisibleCount" style="color: var(--portal-blue); font-weight: 800;"><?php echo $total_defaulters; ?></span> of <?php echo $total_defaulters; ?> students with pending fee dues
                </div>
                <div>
                    <span style="color: #dc2626;"><i class="fas fa-shield-alt"></i> Official School Arrears Registry</span>
                </div>
            </div>
        </div>

        <!-- Dues Statement Table Card -->
        <div class="portal-card" style="padding: 20px;">
            <div class="portal-table-container">
                <table class="dues-table" id="duesTable">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Student & Reg No</th>
                            <th>Father / Parent</th>
                            <th>Complete Address</th>
                            <th>Mobile Number</th>
                            <th>Class / Mode</th>
                            <th>Total Due Till Date</th>
                            <th>Due Months Breakdown</th>
                            <th class="no-print" style="text-align: right; width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dues_list)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 50px; color: #166534; background: #f0fdf4;">
                                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 12px; display: block;"></i>
                                    <b style="font-size: 1.15rem;">No Pending Student Dues! All accounts are fully cleared till <?php echo $current_month_name; ?>.</b>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $s_idx = 1;
                            foreach ($dues_list as $row): 
                                $phone_digits = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
                                $scholar_slug = strtolower($row['scholar_mode'] ?? 'day');
                                $badge_class = (strpos($scholar_slug, 'hostler') !== false) ? 'mode-hostler' : 'mode-day';
                                
                                $s_name = (string)($row['name'] ?? 'Student');
                                $p_name = (string)($row['parent_name'] ?? 'Parent / Guardian');
                                $r_reg = (string)($row['reg_no'] ?? '');
                                $d_months = (string)($row['due_months'] ?? 'Current Session');
                                
                                // Clean WhatsApp message
                                $wa_text = "*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*\n"
                                         . "📍 Imamganj, Gaya (Bihar)\n"
                                         . "------------------------------------------\n"
                                         . "📢 *OUTSTANDING FEE DUE INTIMATION*\n\n"
                                         . "Respected *" . addslashes($p_name) . "* (Parent of *" . addslashes($s_name) . "*),\n\n"
                                         . "This is an intimation that an outstanding academic fee of *₹" . number_format($row['total_due'], 2) . "* is pending for student *" . addslashes($s_name) . "*" . (!empty($r_reg) ? " (Reg No: *" . addslashes($r_reg) . "*)" : "") . ".\n\n"
                                         . "💵 *Total Pending Amount:* ₹" . number_format($row['total_due'], 2) . "\n"
                                         . "📅 *Due For Months:* " . addslashes($d_months) . "\n"
                                         . "🏫 *Institution:* ABSS Imamganj\n\n"
                                         . "Kindly clear the outstanding amount at the earliest.\n\n"
                                         . "⚠️ *Late Fine Notice (विलंब शुल्क):* Please note that a late fee of *₹" . number_format((float)($settings['fine_rate_per_day'] ?? 5), 2) . " per day* is applicable on unpaid dues after the " . (int)($settings['fine_grace_days'] ?? 5) . "th of each month.\n\n"
                                         . "🔗 *Parent Portal Link:* " . $parent_portal_url . "\n"
                                         . "📞 *Accounts Desk:* +91 9523012888\n\n"
                                         . "_ABSS Administration_";
                                $encoded_wa = rawurlencode($wa_text);
                            ?>
                                <tr class="dues-row"
                                    data-name="<?php echo strtolower(htmlspecialchars((string)($row['name'] ?? ''))); ?>"
                                    data-parent="<?php echo strtolower(htmlspecialchars((string)($row['parent_name'] ?? ''))); ?>"
                                    data-phone="<?php echo strtolower(htmlspecialchars((string)($row['phone'] ?? ''))); ?>"
                                    data-address="<?php echo strtolower(htmlspecialchars((string)($row['full_address'] ?? ''))); ?>"
                                    data-reg="<?php echo strtolower(htmlspecialchars((string)($row['reg_no'] ?? ''))); ?>"
                                    data-class="<?php echo strtolower(htmlspecialchars((string)($row['class_admitted'] ?? ''))); ?>"
                                    data-mode="<?php echo strtolower(htmlspecialchars((string)($row['scholar_mode'] ?? ''))); ?>"
                                    data-due="<?php echo (float)($row['total_due'] ?? 0); ?>">

                                    <td style="font-weight: 800; color: #94a3b8;"><?php echo $s_idx++; ?></td>

                                    <td>
                                        <div style="font-weight: 800; color: var(--portal-dark); font-size: 0.98rem;">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </div>
                                        <?php if (!empty($row['reg_no'])): ?>
                                            <small style="color: var(--portal-blue); font-weight: 800; font-family: monospace;"><?php echo htmlspecialchars($row['reg_no']); ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($row['parent_name']); ?></div>
                                        <small style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($row['guardian_relationship'] ?: 'Parent'); ?></small>
                                    </td>

                                    <td>
                                        <div class="address-snippet">
                                            <i class="fas fa-map-marker-alt" style="color: #dc2626; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($row['full_address']); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div style="font-family: monospace; font-weight: 800; color: var(--portal-dark); font-size: 0.92rem;">
                                            <?php echo htmlspecialchars($row['phone']); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div style="font-weight: 700; color: var(--portal-dark); font-size: 0.88rem;"><?php echo htmlspecialchars($row['class_admitted']); ?></div>
                                        <span class="scholar-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($row['scholar_mode']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="due-amount-pill">
                                            ₹ <?php echo number_format($row['total_due'], 2); ?>
                                        </div>
                                        <?php if ($row['fine_amount'] > 0): ?>
                                            <div style="font-size: 0.72rem; color: #ea580c; font-weight: 700; margin-top: 2px;">
                                                (Base: ₹<?php echo number_format($row['base_due'], 2); ?> + Fine: ₹<?php echo number_format($row['fine_amount'], 2); ?>)
                                            </div>
                                        <?php endif; ?>
                                        <small style="color: #64748b; font-size: 0.72rem; display: block; font-weight: 600; margin-top: 2px;">
                                            <?php echo $row['unpaid_count']; ?> Bill(s)
                                        </small>
                                    </td>

                                    <td>
                                        <div style="max-width: 240px;">
                                            <?php 
                                            $months_arr = explode(', ', $row['due_months']);
                                            foreach ($months_arr as $m) {
                                                if (!empty($m)) {
                                                    echo '<span class="due-month-tag">' . htmlspecialchars($m) . '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>

                                    <td class="no-print" style="text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <!-- Direct Email Bill & PDF -->
                                            <?php 
                                            $eff_email = $row['guardian_email'] ?: ($row['parent_email'] ?? ''); 
                                            $latest_bid = (int)($row['latest_bill_id'] ?? 0);
                                            ?>
                                            <button type="button" 
                                                    class="act-btn" 
                                                    onclick="openEmailDuesModal(<?php echo $row['id']; ?>, '<?php echo addslashes($s_name); ?>', '<?php echo addslashes($p_name); ?>', '<?php echo addslashes($eff_email); ?>', <?php echo (float)$row['total_due']; ?>, '<?php echo addslashes($d_months); ?>', <?php echo (int)$row['unpaid_count']; ?>, <?php echo $latest_bid; ?>)" 
                                                    style="background: #eff6ff; color: #1d4ed8; width: 34px; height: 34px; border-radius: 8px;" 
                                                    title="Email Official Fee Invoice &amp; Statement PDF">
                                                <i class="fas fa-envelope-open-text"></i>
                                            </button>

                                            <!-- Direct Download PDF Statement (Exact layout from view_bill.php) -->
                                            <a href="<?php echo ($latest_bid > 0 ? 'ajax_send_due_email.php?action=download_bill_pdf&bill_id=' . $latest_bid : 'ajax_send_due_email.php?action=download_due_pdf&student_id=' . $row['id']); ?>" 
                                               target="_blank" 
                                               class="act-btn" 
                                               style="background: #fee2e2; color: #b91c1c; width: 34px; height: 34px; border-radius: 8px;" 
                                               title="Download Official Fee Invoice PDF (view_bill.php layout)">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>

                                            <?php if (!empty($phone_digits)): ?>
                                                <a href="https://api.whatsapp.com/send?phone=<?php echo (strlen($phone_digits) == 10 ? '91' . $phone_digits : $phone_digits); ?>&text=<?php echo $encoded_wa; ?>" 
                                                   target="_blank" 
                                                   class="act-btn" 
                                                   style="background: #dcfce7; color: #15803d; width: 34px; height: 34px; border-radius: 8px;" 
                                                   title="Send Due Fee WhatsApp Alert">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?php echo ($latest_bid > 0 ? 'view_bill.php?id=' . $latest_bid : 'fees.php'); ?>" 
                                               target="_blank"
                                               class="act-btn" 
                                               style="background: #f1f5f9; color: var(--portal-dark); width: 34px; height: 34px; border-radius: 8px;" 
                                               title="View Official Fee Invoice (view_bill.php)">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Print Statement Signatures Footer (Only for PDF / Print Output) -->
            <div class="print-footer" style="display: none; justify-content: space-between; align-items: flex-end; margin-top: 50px; padding: 20px 10px; border-top: 1px dashed #000;">
                <div style="text-align: center; width: 220px;">
                    <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 9pt;">Prepared By (Accounts Desk)</div>
                </div>
                <div style="text-align: center; width: 220px;">
                    <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 9pt;">Verified By (Finance In-Charge)</div>
                </div>
                <div style="text-align: center; width: 220px;">
                    <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 9pt;">Principal / Director Seal & Sign</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Client-Side Filter & Export Scripts -->
    <script>
        function filterDuesTable() {
            const query = (document.getElementById('duesSearchInput').value || '').toLowerCase().trim();
            const classFilter = (document.getElementById('classFilterSelect').value || '').toLowerCase().trim();
            const modeFilter = (document.getElementById('modeFilterSelect').value || '').toLowerCase().trim();

            const rows = document.querySelectorAll('#duesTable tbody tr.dues-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const parent = row.getAttribute('data-parent') || '';
                const phone = row.getAttribute('data-phone') || '';
                const address = row.getAttribute('data-address') || '';
                const reg = row.getAttribute('data-reg') || '';
                const sClass = row.getAttribute('data-class') || '';
                const sMode = row.getAttribute('data-mode') || '';

                const matchesQuery = query === '' || 
                    name.includes(query) || 
                    parent.includes(query) || 
                    phone.includes(query) || 
                    address.includes(query) || 
                    reg.includes(query);

                const matchesClass = classFilter === '' || sClass.includes(classFilter);
                const matchesMode = modeFilter === '' || sMode.includes(modeFilter);

                if (matchesQuery && matchesClass && matchesMode) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('duesVisibleCount').textContent = visibleCount;
        }

        function resetDuesFilters() {
            document.getElementById('duesSearchInput').value = '';
            document.getElementById('classFilterSelect').value = '';
            document.getElementById('modeFilterSelect').value = '';
            filterDuesTable();
        }

        function exportDuesToCSV() {
            const rows = document.querySelectorAll('#duesTable tbody tr.dues-row');
            if (!rows.length) {
                alert('No student dues records to export.');
                return;
            }

            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "S.No,Student Name,Registration No,Father/Parent Name,Complete Address,Mobile Number,Class,Scholar Mode,Total Due Amount,Due Months\n";

            let idx = 1;
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const name = '"' + (row.getAttribute('data-name') || '').replace(/"/g, '""') + '"';
                    const reg = '"' + (row.getAttribute('data-reg') || '').replace(/"/g, '""') + '"';
                    const parent = '"' + (row.getAttribute('data-parent') || '').replace(/"/g, '""') + '"';
                    const address = '"' + (row.getAttribute('data-address') || '').replace(/"/g, '""') + '"';
                    const phone = '"' + (row.getAttribute('data-phone') || '').replace(/"/g, '""') + '"';
                    const sClass = '"' + (row.getAttribute('data-class') || '').replace(/"/g, '""') + '"';
                    const sMode = '"' + (row.getAttribute('data-mode') || '').replace(/"/g, '""') + '"';
                    const due = (row.getAttribute('data-due') || '0');
                    
                    csvContent += `${idx++},${name},${reg},${parent},${address},${phone},${sClass},${sMode},${due}\n`;
                }
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `ABSS_Student_Dues_Statement_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Email Due Statement Modal Logic
        function openEmailDuesModal(studentId, studentName, parentName, email, totalDue, dueMonths, unpaidCount, billId) {
            document.getElementById('modalStudentId').value = studentId;
            document.getElementById('modalBillId').value = billId || 0;
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalParentName').textContent = parentName;
            document.getElementById('modalTotalDue').textContent = '₹ ' + Number(totalDue).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('modalDueMonths').textContent = dueMonths || 'Current Session';
            document.getElementById('modalUnpaidCount').textContent = (unpaidCount || 1) + ' Bill(s)';
            
            const emailInput = document.getElementById('modalRecipientEmail');
            emailInput.value = email || '';
            
            const errBox = document.getElementById('modalErrorBox');
            if (errBox) errBox.style.display = 'none';

            const btn = document.getElementById('btnSubmitEmailDue');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Email with Bill PDF';

            document.getElementById('emailDueModal').style.display = 'flex';
            if (!email) {
                emailInput.focus();
            }
        }

        function closeEmailDuesModal() {
            document.getElementById('emailDueModal').style.display = 'none';
        }

        // Close on backdrop click
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('emailDueModal');
            if (e.target === modal) {
                closeEmailDuesModal();
            }
        });

        // Submit Single Email Dispatch via AJAX with exact view_bill.php Receipt PDF
        function submitEmailDueForm(e) {
            if (e) e.preventDefault();

            const studentId = document.getElementById('modalStudentId').value;
            const billId = document.getElementById('modalBillId').value;
            const email = (document.getElementById('modalRecipientEmail').value || '').trim();
            const btn = document.getElementById('btnSubmitEmailDue');
            const errBox = document.getElementById('modalErrorBox');

            if (!email || !email.includes('@')) {
                errBox.style.display = 'block';
                errBox.textContent = 'Please provide a valid recipient email address.';
                return false;
            }

            errBox.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rendering Receipt PDF &amp; Sending...';

            const formData = new FormData();
            formData.append('action', 'send_student_due_email');
            formData.append('student_id', studentId);
            if (billId && parseInt(billId) > 0) {
                formData.append('bill_id', billId);
            }
            formData.append('email', email);

            const dispatchEmail = (fd) => {
                fetch('ajax_send_due_email.php', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        btn.innerHTML = '<i class="fas fa-check"></i> Dispatched!';
                        setTimeout(() => {
                            closeEmailDuesModal();
                            alert('✅ ' + data.message);
                        }, 500);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Try Again';
                        errBox.style.display = 'block';
                        errBox.textContent = (data && data.error) ? data.error : 'Failed to dispatch email.';
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Try Again';
                    errBox.style.display = 'block';
                    errBox.textContent = 'Network or server error: ' + err.message;
                });
            };

            // If a bill_id exists, fetch its view_bill.php receipt DOM & render to base64 PDF using html2pdf
            if (billId && parseInt(billId) > 0 && typeof html2pdf !== 'undefined') {
                fetch(`view_bill.php?id=${billId}&embed=1`)
                .then(res => res.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.style.cssText = 'position:absolute;left:-9999px;top:-9999px;width:800px;background:#fff;';
                    tempDiv.innerHTML = html;
                    document.body.appendChild(tempDiv);

                    const targetContainer = tempDiv.querySelector('.receipt-container') || tempDiv;
                    const opt = {
                        margin: [5, 5, 5, 5],
                        filename: `Invoice_ABSS_${billId}.pdf`,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, useCORS: true, logging: false },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                    };

                    return html2pdf().set(opt).from(targetContainer).outputPdf('datauristring').then(pdfDataUri => {
                        document.body.removeChild(tempDiv);
                        formData.append('pdf_base64', pdfDataUri);
                        dispatchEmail(formData);
                    });
                })
                .catch(err => {
                    console.warn("DOM PDF rendering fallback:", err);
                    dispatchEmail(formData);
                });
            } else {
                dispatchEmail(formData);
            }

            return false;
        }

        // Bulk Send Due Statements & Bill PDFs
        function bulkSendDueEmails() {
            const count = document.querySelectorAll('#duesTable tbody tr.dues-row').length;
            if (count === 0) {
                alert('No students with pending dues available to email.');
                return;
            }

            if (!confirm(`Are you sure you want to generate Bill PDFs and email official Dues Statements to all ${count} student parents/guardians with registered email addresses?`)) {
                return;
            }

            const overlay = document.createElement('div');
            overlay.id = 'bulkLoadingOverlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.85);backdrop-filter:blur(6px);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px;';
            overlay.innerHTML = `
                <i class="fas fa-paper-plane fa-spin fa-3x" style="color:#60a5fa;margin-bottom:16px;"></i>
                <h2 style="font-size:1.5rem;margin:0 0 8px 0;font-weight:800;">Generating PDFs &amp; Dispatched Emails...</h2>
                <p style="color:#cbd5e1;font-size:0.95rem;max-width:400px;margin:0;">Please wait while official A4 Bill PDFs are rendered and delivered to student parents via SMTP.</p>
            `;
            document.body.appendChild(overlay);

            const formData = new FormData();
            formData.append('action', 'bulk_send_due_emails');

            fetch('ajax_send_due_email.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.body.removeChild(overlay);
                if (data && data.success) {
                    alert('🎉 ' + data.message);
                } else {
                    alert('⚠️ ' + ((data && data.error) ? data.error : 'Bulk dispatch encountered an error.'));
                }
            })
            .catch(err => {
                if (document.getElementById('bulkLoadingOverlay')) {
                    document.body.removeChild(overlay);
                }
                alert('Connection or dispatch error: ' + err.message);
            });
        }
    </script>

    <!-- Email Due Statement & Bill PDF Modal -->
    <div class="modal-overlay" id="emailDueModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-envelope-open-text" style="color:#2563eb;"></i> Email Fee Bill &amp; Statement
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeEmailDuesModal()">&times;</button>
            </div>

            <div id="modalErrorBox" style="display:none;background:#fee2e2;color:#b91c1c;padding:10px 14px;border-radius:10px;font-size:0.85rem;font-weight:700;margin-bottom:15px;border:1px solid #fca5a5;"></div>

            <div class="modal-info-box">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:0.85rem;">
                    <div>
                        <span style="color:#64748b;font-weight:700;display:block;font-size:0.75rem;text-transform:uppercase;">Student</span>
                        <strong id="modalStudentName" style="color:#0f172a;font-size:0.95rem;">-</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;font-weight:700;display:block;font-size:0.75rem;text-transform:uppercase;">Parent / Guardian</span>
                        <strong id="modalParentName" style="color:#0f172a;font-size:0.95rem;">-</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;font-weight:700;display:block;font-size:0.75rem;text-transform:uppercase;">Total Due Amount</span>
                        <strong id="modalTotalDue" style="color:#dc2626;font-size:1.1rem;font-weight:900;">-</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;font-weight:700;display:block;font-size:0.75rem;text-transform:uppercase;">Unpaid Bills</span>
                        <strong id="modalUnpaidCount" style="color:#2563eb;">-</strong>
                    </div>
                </div>
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:0.82rem;color:#475569;">
                    <span style="font-weight:700;">Due For Months:</span> <span id="modalDueMonths" style="font-weight:800;color:#1e293b;">-</span>
                </div>
            </div>

            <form onsubmit="return submitEmailDueForm(event)">
                <input type="hidden" id="modalStudentId" value="">
                <input type="hidden" id="modalBillId" value="0">

                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:0.82rem;font-weight:800;color:#0f172a;text-transform:uppercase;margin-bottom:6px;">
                        Guardian / Parent Email Address <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="email" id="modalRecipientEmail" required placeholder="parent@example.com" 
                           style="width:100%;padding:12px 14px;border-radius:12px;border:2px solid #cbd5e1;font-size:0.95rem;font-weight:600;color:#0f172a;box-sizing:border-box;outline:none;">
                    <small style="color:#64748b;font-size:0.76rem;margin-top:4px;display:block;">The generated official PDF Statement will be attached directly to this email.</small>
                </div>

                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px 14px;margin-bottom:20px;font-size:0.82rem;color:#1e40af;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-file-pdf" style="font-size:1.4rem;color:#2563eb;"></i>
                    <div>
                        <b>Automated A4 PDF Attachment:</b> Includes complete itemized fee breakup, overdue fine calculation, and school bank/portal instructions.
                    </div>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn-portal" onclick="closeEmailDuesModal()" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;box-shadow:none;padding:10px 18px;">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitEmailDue" class="btn-portal" style="background:linear-gradient(135deg, #2563eb, #1d4ed8);color:#fff;padding:10px 22px;border:none;">
                        <i class="fas fa-paper-plane"></i> Send Email with Bill PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
