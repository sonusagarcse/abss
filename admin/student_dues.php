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
        COALESCE(unpaid.total_due, 0) AS total_due,
        COALESCE(unpaid.unpaid_count, 0) AS unpaid_count,
        COALESCE(unpaid.due_months, '') AS due_months
    FROM students s
    INNER JOIN (
        SELECT 
            student_id,
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
                <button type="button" class="btn-portal" onclick="window.print()" style="background: #0f172a; color: #ffffff; padding: 12px 20px;">
                    <i class="fas fa-print"></i> Print / Download PDF
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
                    <small style="color: #dc2626; font-weight: 800; font-size: 0.75rem;"><?php echo number_format($total_unpaid_invoices); ?> Unpaid Invoices</small>
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
                                        <small style="color: #64748b; font-size: 0.72rem; display: block; font-weight: 600;">
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
                                            <?php if (!empty($phone_digits)): ?>
                                                <a href="https://api.whatsapp.com/send?phone=<?php echo (strlen($phone_digits) == 10 ? '91' . $phone_digits : $phone_digits); ?>&text=<?php echo $encoded_wa; ?>" 
                                                   target="_blank" 
                                                   class="act-btn" 
                                                   style="background: #dcfce7; color: #15803d; width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" 
                                                   title="Send Due Fee WhatsApp Alert">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="fees.php" 
                                               class="act-btn" 
                                               style="background: #eff6ff; color: var(--portal-blue); width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" 
                                               title="View in Fee Ledger">
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
    </script>
</body>
</html>
