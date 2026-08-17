<?php
// admin/whatsapp.php - WhatsApp Template & Direct Messaging Hub

require_once 'includes/auth.php';

// Fetch all active students with calculated unpaid dues
$students_res = $conn->query("
    SELECT 
        s.id, 
        s.name, 
        s.reg_no, 
        s.parent_name, 
        s.guardian_relationship,
        s.phone, 
        s.guardian_email, 
        s.class_admitted, 
        s.scholar_mode, 
        s.target_school, 
        s.base_fee,
        s.admission_date,
        COALESCE(unpaid.total_due, 0) AS due_fee,
        COALESCE(unpaid.months_due, '') AS due_months
    FROM students s
    LEFT JOIN (
        SELECT 
            student_id, 
            SUM(amount) AS total_due,
            GROUP_CONCAT(DISTINCT month_for ORDER BY id SEPARATOR ', ') AS months_due
        FROM fees_generated 
        WHERE status = 'unpaid' 
        GROUP BY student_id
    ) unpaid ON s.id = unpaid.student_id
    WHERE s.status = 'active'
    ORDER BY s.name ASC
");

$students_list = [];
$students_with_dues = [];
$total_due_amount = 0;

if ($students_res) {
    while ($st = $students_res->fetch_assoc()) {
        $st['due_fee'] = (float)$st['due_fee'];
        $students_list[] = $st;
        if ($st['due_fee'] > 0) {
            $students_with_dues[] = $st;
            $total_due_amount += $st['due_fee'];
        }
    }
}

// Pre-selected student ID from query param (if coming from students.php or fees.php)
$selected_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : (isset($students_list[0]['id']) ? (int)$students_list[0]['id'] : 0);
$selected_template = isset($_GET['template']) ? trim($_GET['template']) : 'fee_due';

// Determine base portal URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
$base_app_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
$portal_url = "$base_app_url/parent/login.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Messenger & Templates | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        /* WhatsApp Hub Styling */
        .wa-brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #dcfce7;
            color: #15803d;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .wa-layout-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 30px;
            align-items: start;
        }

        /* Preset Template Buttons Grid */
        .templates-selector-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .template-choice-btn {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            text-align: left;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .template-choice-btn:hover {
            border-color: #22c55e;
            background: #f0fdf4;
            transform: translateY(-2px);
        }
        .template-choice-btn.active {
            border-color: #22c55e;
            background: #f0fdf4;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.18);
        }
        .template-choice-btn .tpl-title {
            font-weight: 800;
            color: var(--portal-dark);
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .template-choice-btn .tpl-desc {
            font-size: 0.76rem;
            color: #64748b;
            font-weight: 600;
        }

        /* Dynamic Tags Pills */
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        .tag-pill {
            background: #eff6ff;
            color: var(--portal-blue);
            border: 1px solid #bfdbfe;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .tag-pill:hover {
            background: var(--portal-blue);
            color: #ffffff;
            border-color: var(--portal-blue);
            transform: scale(1.03);
        }

        /* Message Textarea */
        .wa-textarea {
            width: 100%;
            height: 220px;
            padding: 16px 18px;
            border-radius: var(--radius-md);
            border: 2px solid #cbd5e1;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            box-sizing: border-box;
            resize: vertical;
            line-height: 1.5;
            transition: 0.25s;
            background: #ffffff;
        }
        .wa-textarea:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
        }

        /* Phone Chat Mockup */
        .phone-mockup-wrapper {
            background: #ffffff;
            border-radius: 36px;
            padding: 14px;
            border: 8px solid #0f172a;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.15);
            max-width: 380px;
            margin: 0 auto;
            position: sticky;
            top: 25px;
        }
        .phone-screen {
            background: #efeae2;
            background-image: radial-gradient(#d1d7db 1px, transparent 1px);
            background-size: 14px 14px;
            border-radius: 26px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 520px;
        }
        .phone-chat-header {
            background: #075e54;
            color: #ffffff;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .wa-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffffff;
            color: #075e54;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .phone-chat-body {
            flex: 1;
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .wa-bubble {
            background: #ffffff;
            border-radius: 12px 12px 12px 2px;
            padding: 14px 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.06);
            font-size: 0.88rem;
            line-height: 1.45;
            color: #111b21;
            white-space: pre-wrap;
            word-break: break-word;
            position: relative;
        }
        .wa-bubble-time {
            text-align: right;
            font-size: 0.68rem;
            color: #667781;
            font-weight: 600;
            margin-top: 6px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }

        /* Send Buttons */
        .btn-wa-send {
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #ffffff;
            padding: 16px 28px;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.35);
            text-decoration: none;
            width: 100%;
        }
        .btn-wa-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(37, 211, 102, 0.45);
            color: #ffffff;
        }

        .btn-wa-web {
            background: #ffffff;
            color: #075e54;
            border: 2px solid #25d366;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
        }
        .btn-wa-web:hover {
            background: #f0fdf4;
            color: #075e54;
        }

        /* Tab Switcher */
        .wa-tab-nav {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .wa-tab-btn {
            background: transparent;
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            font-weight: 800;
            color: #64748b;
            font-family: inherit;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .wa-tab-btn.active {
            background: #dcfce7;
            color: #15803d;
        }

        /* Bulk Table */
        .bulk-wa-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .bulk-wa-table th {
            text-align: left;
            padding: 8px 18px;
            color: var(--portal-blue);
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: none;
            background: transparent;
        }
        .bulk-wa-row td {
            padding: 16px 18px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
        }
        .bulk-wa-row td:first-child {
            border-left: 1px solid #f1f5f9;
            border-radius: 14px 0 0 14px;
        }
        .bulk-wa-row td:last-child {
            border-right: 1px solid #f1f5f9;
            border-radius: 0 14px 14px 0;
        }

        @media (max-width: 1024px) {
            .wa-layout-grid {
                grid-template-columns: 1fr;
            }
            .phone-mockup-wrapper {
                position: static;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Header -->
        <header class="page-header" style="margin-bottom: 25px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                        <i class="fab fa-whatsapp" style="color: #25d366;"></i> WhatsApp Templates & Direct Dispatcher
                    </h1>
                    <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Send automated fee due reminders, exam results, attendance alerts & circulars with live dynamic variables.</p>
                </div>
                <div class="wa-brand-badge">
                    <i class="fab fa-whatsapp"></i> Direct API Gateway
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <div class="wa-tab-nav">
            <button class="wa-tab-btn active" id="tabBtnSingle" onclick="switchWaTab('single')">
                <i class="fas fa-paper-plane"></i> Single Message Composer
            </button>
            <button class="wa-tab-btn" id="tabBtnBulk" onclick="switchWaTab('bulk')">
                <i class="fas fa-layer-group"></i> Bulk Due Fee Dispatcher (<?php echo count($students_with_dues); ?> Students)
            </button>
        </div>

        <!-- ============================================== -->
        <!-- TAB 1: SINGLE MESSAGE COMPOSER & PREVIEW       -->
        <!-- ============================================== -->
        <div id="paneSingle">
            <div class="wa-layout-grid">
                <!-- Composer Controls -->
                <div class="portal-card">
                    <h3 style="margin: 0 0 18px; font-size: 1.15rem; color: var(--portal-dark);">
                        <i class="fas fa-sliders-h" style="color: var(--portal-blue); margin-right: 8px;"></i> 1. Select Student & Details
                    </h3>

                    <!-- Student Selector Dropdown -->
                    <div class="portal-input-group">
                        <label for="studentSelectDropdown"><i class="fas fa-user-graduate"></i> Choose Candidate</label>
                        <select id="studentSelectDropdown" class="form-control" onchange="onStudentChange()">
                            <?php foreach ($students_list as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $s['id'] == $selected_student_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']) . (!empty($s['reg_no']) ? ' (' . $s['reg_no'] . ')' : '') . ' — Father: ' . htmlspecialchars($s['parent_name']) . ($s['due_fee'] > 0 ? ' [Due: ₹' . number_format($s['due_fee'], 2) . ']' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Selected Student Quick Info Bar -->
                    <div id="studentInfoBanner" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 800; text-transform: uppercase; display: block;">Recipient Number</span>
                            <span id="bannerPhone" style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">--</span>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 800; text-transform: uppercase; display: block;">Class / Mode</span>
                            <span id="bannerClass" style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">--</span>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 800; text-transform: uppercase; display: block;">Pending Due Fee</span>
                            <span id="bannerDue" style="font-weight: 800; color: #dc2626; font-size: 0.95rem;">₹0.00</span>
                        </div>
                    </div>

                    <h3 style="margin: 0 0 14px; font-size: 1.15rem; color: var(--portal-dark);">
                        <i class="fas fa-th-large" style="color: #22c55e; margin-right: 8px;"></i> 2. Choose Message Template
                    </h3>

                    <!-- Templates Selector Cards -->
                    <div class="templates-selector-grid">
                        <div class="template-choice-btn active" id="tplCard-fee_due" onclick="applyTemplate('fee_due')">
                            <span class="tpl-title"><i class="fas fa-receipt" style="color: #dc2626;"></i> Fee Due Alert</span>
                            <span class="tpl-desc">Outstanding dues payment reminder</span>
                        </div>
                        <div class="template-choice-btn" id="tplCard-result" onclick="applyTemplate('result')">
                            <span class="tpl-title"><i class="fas fa-award" style="color: #2563eb;"></i> Test Result</span>
                            <span class="tpl-desc">Mock test scores & rank notification</span>
                        </div>
                        <div class="template-choice-btn" id="tplCard-attendance" onclick="applyTemplate('attendance')">
                            <span class="tpl-title"><i class="fas fa-calendar-times" style="color: #ea580c;"></i> Absence Alert</span>
                            <span class="tpl-desc">Intimation of student absent notice</span>
                        </div>
                        <div class="template-choice-btn" id="tplCard-circular" onclick="applyTemplate('circular')">
                            <span class="tpl-title"><i class="fas fa-bullhorn" style="color: #7c3aed;"></i> Official Notice</span>
                            <span class="tpl-desc">Holiday, event, or school circular</span>
                        </div>
                        <div class="template-choice-btn" id="tplCard-welcome" onclick="applyTemplate('welcome')">
                            <span class="tpl-title"><i class="fas fa-handshake" style="color: #16a34a;"></i> Welcome Letter</span>
                            <span class="tpl-desc">Admission confirmation letter</span>
                        </div>
                        <div class="template-choice-btn" id="tplCard-custom" onclick="applyTemplate('custom')">
                            <span class="tpl-title"><i class="fas fa-edit" style="color: #0891b2;"></i> Custom Note</span>
                            <span class="tpl-desc">Compose custom free-form message</span>
                        </div>
                    </div>

                    <h3 style="margin: 0 0 10px; font-size: 1.15rem; color: var(--portal-dark); display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-tags" style="color: var(--portal-blue); margin-right: 8px;"></i> 3. Dynamic Variable Tags</span>
                        <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Click tag to insert into template</small>
                    </h3>

                    <!-- Variable Insertion Pills -->
                    <div class="tags-container">
                        <span class="tag-pill" onclick="insertTag('{name}')"><i class="fas fa-plus"></i> {name}</span>
                        <span class="tag-pill" onclick="insertTag('{father_name}')"><i class="fas fa-plus"></i> {father_name}</span>
                        <span class="tag-pill" onclick="insertTag('{due_fee}')"><i class="fas fa-plus"></i> {due_fee}</span>
                        <span class="tag-pill" onclick="insertTag('{month}')"><i class="fas fa-plus"></i> {month}</span>
                        <span class="tag-pill" onclick="insertTag('{reg_no}')"><i class="fas fa-plus"></i> {reg_no}</span>
                        <span class="tag-pill" onclick="insertTag('{class}')"><i class="fas fa-plus"></i> {class}</span>
                        <span class="tag-pill" onclick="insertTag('{target_school}')"><i class="fas fa-plus"></i> {target_school}</span>
                        <span class="tag-pill" onclick="insertTag('{phone}')"><i class="fas fa-plus"></i> {phone}</span>
                        <span class="tag-pill" onclick="insertTag('{portal_url}')"><i class="fas fa-plus"></i> {portal_url}</span>
                    </div>

                    <!-- Message Textarea -->
                    <div class="portal-input-group" style="margin-bottom: 20px;">
                        <label for="templateTextarea"><i class="fas fa-comment-alt"></i> Message Template Content</label>
                        <textarea id="templateTextarea" class="wa-textarea" oninput="updateLiveMockup()"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                        <button type="button" class="btn-wa-send" onclick="sendWhatsApp('app')">
                            <i class="fab fa-whatsapp" style="font-size: 1.3rem;"></i> Send to Student's Parent (App)
                        </button>
                        <button type="button" class="btn-wa-web" onclick="sendWhatsApp('web')">
                            <i class="fas fa-desktop"></i> Open in WhatsApp Web
                        </button>
                        <button type="button" class="btn-portal" onclick="copyFormattedText()" style="background: #f1f5f9; color: #475569; border: 2px solid #cbd5e1; box-shadow: none; width: 100%;">
                            <i class="far fa-copy"></i> Copy Formatted Message to Clipboard
                        </button>
                    </div>
                </div>

                <!-- Live Smartphone Preview Mockup -->
                <div>
                    <div class="phone-mockup-wrapper">
                        <div class="phone-screen">
                            <div class="phone-chat-header">
                                <div class="wa-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 800; font-size: 0.92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="mockupRecipientName">Parent Contact</div>
                                    <div style="font-size: 0.72rem; color: #d1fae5;">online • WhatsApp Business</div>
                                </div>
                                <i class="fas fa-ellipsis-v" style="font-size: 0.9rem; opacity: 0.8;"></i>
                            </div>

                            <div class="phone-chat-body">
                                <div class="wa-bubble">
                                    <div id="mockupMessageBody">Generating message preview...</div>
                                    <div class="wa-bubble-time">
                                        <span id="mockupTime">12:00 PM</span>
                                        <i class="fas fa-check-double" style="color: #53bdeb;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 2: BULK DUE FEE DISPATCHER                 -->
        <!-- ============================================== -->
        <div id="paneBulk" style="display: none;">
            <div class="portal-card" style="padding: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 22px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-money-bill-wave" style="color: #dc2626;"></i> Outstanding Fee Defaulters Roster
                        </h3>
                        <small style="color: #64748b; font-weight: 600;">
                            Total Outstanding Balance Across <?php echo count($students_with_dues); ?> Students: 
                            <b style="color: #dc2626; font-size: 1rem;">₹<?php echo number_format($total_due_amount, 2); ?></b>
                        </small>
                    </div>

                    <div style="position: relative; min-width: 240px;">
                        <input type="text" id="bulkSearchInput" placeholder="Search student, father, phone..." onkeyup="filterBulkTable()" class="form-control" style="padding-left: 36px;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    </div>
                </div>

                <div class="portal-table-container">
                    <table class="bulk-wa-table" id="bulkDueTable">
                        <thead>
                            <tr>
                                <th>Student & Reg No</th>
                                <th>Parent / Guardian</th>
                                <th>Class / Mode</th>
                                <th>Pending Due Amount</th>
                                <th style="text-align: right;">1-Click Dispatch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students_with_dues)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #166534; background: #f0fdf4;">
                                        <i class="fas fa-check-circle" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                                        <b>All student fees are fully cleared! No pending dues found.</b>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students_with_dues as $row): 
                                    $phone_digits = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
                                    $due_text = "₹" . number_format($row['due_fee'], 2);
                                ?>
                                    <tr class="bulk-wa-row" 
                                        data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
                                        data-parent="<?php echo strtolower(htmlspecialchars($row['parent_name'])); ?>"
                                        data-phone="<?php echo strtolower(htmlspecialchars($row['phone'])); ?>"
                                        data-reg="<?php echo strtolower(htmlspecialchars($row['reg_no'])); ?>">
                                        <td>
                                            <div style="font-weight: 800; color: var(--portal-dark); font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </div>
                                            <?php if (!empty($row['reg_no'])): ?>
                                                <small style="color: var(--portal-blue); font-weight: 700; font-family: monospace;"><?php echo htmlspecialchars($row['reg_no']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: #334155;"><?php echo htmlspecialchars($row['parent_name']); ?></div>
                                            <small style="color: #64748b; font-weight: 600;"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['phone']); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--portal-dark);"><?php echo htmlspecialchars($row['class_admitted']); ?></div>
                                            <small style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($row['scholar_mode']); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size: 1.05rem; font-weight: 900; color: #dc2626;"><?php echo $due_text; ?></div>
                                            <?php if (!empty($row['due_months'])): ?>
                                                <small style="color: #64748b; font-size: 0.75rem;"><?php echo htmlspecialchars($row['due_months']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button" class="btn-portal" style="background: #25d366; color: #fff; padding: 10px 18px; border-radius: 12px; font-size: 0.85rem;" 
                                                    onclick="quickSendDueAlert(<?php echo (int)$row['id']; ?>)">
                                                <i class="fab fa-whatsapp"></i> Send Due Alert
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- WhatsApp Engine Scripts -->
    <script>
        const studentsData = <?php echo json_encode($students_list); ?>;
        const portalUrl = <?php echo json_encode($portal_url); ?>;

        // Predefined Multi-type Message Templates
        const templates = {
            fee_due: `*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*
📍 Imamganj, Gaya (Bihar)
------------------------------------------
📢 *FEE PAYMENT REMINDER*

Respected *{father_name}* (Parent of *{name}*),

This is a gentle reminder that an outstanding academic fee of *₹{due_fee}* is currently pending for student *{name}* (Reg No: *{reg_no}*, Class: *{class}*).

💵 *Pending Due Amount:* ₹{due_fee}
📅 *Due For:* {month}
🏫 *Institution:* ABSS Imamganj

Kindly clear the outstanding dues at the earliest via online payment or at the school fee counter.

🔗 *Parent Portal URL:* {portal_url}
📞 *Helpline / Inquiry:* +91 9523012888

_Thank you for your cooperation!_
*ABSS Administration*`,

            result: `*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*
📍 Imamganj, Gaya (Bihar)
------------------------------------------
🏆 *ACADEMIC RESULT NOTIFICATION*

Dear *{father_name}*,

The latest examination/mock test performance for student *{name}* (Reg No: *{reg_no}*) has been recorded and published.

📝 *Student Name:* {name}
📚 *Class / Target:* {class} - {target_school}
📊 *Report & Performance Status:* Available Online

🔗 *View Detailed Marks & Report Card:*
{portal_url}

Congratulations on the consistent progress!
*ABSS Faculty Desk*`,

            attendance: `*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*
📍 Imamganj, Gaya (Bihar)
------------------------------------------
⚠️ *STUDENT ATTENDANCE ALERT*

Dear *{father_name}*,

This is to notify you that student *{name}* (Reg No: *{reg_no}*, Class: *{class}*) was marked *ABSENT* today from scheduled academic sessions.

If this absence was pre-approved or due to medical reasons, please disregard this notice. Otherwise, kindly contact the administration desk.

📞 *Office Contact:* +91 9523012888
🔗 *Parent Dashboard:* {portal_url}

*ABSS Discipline Committee*`,

            circular: `*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*
📍 Imamganj, Gaya (Bihar)
------------------------------------------
📢 *IMPORTANT SCHOOL CIRCULAR / NOTICE*

Dear Parents & Guardians of *{name}*,

Please take note of the upcoming school schedule, examinations, and official holidays.

🔗 *Read Full Circular On Parent Portal:*
{portal_url}

For any queries, feel free to contact the school office.
*Principal / Director*
Awasiya Bal Shikshan Sansthan`,

            welcome: `*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*
📍 Imamganj, Gaya (Bihar)
------------------------------------------
🎉 *WELCOME TO THE ABSS FAMILY!*

Dear *{father_name}*,

We are delighted to confirm that student *{name}* has been successfully enrolled in ABSS for *{target_school}* preparation.

🆔 *Registration No:* {reg_no}
📚 *Class / Batch:* {class}
🏡 *Scholar Mode:* {scholar_mode}

🔗 *Parent Portal Login:* {portal_url}
📱 *Registered Phone Number:* {phone}

We look forward to nurturing your child's academic excellence!
*ABSS Management*`,

            custom: `*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*
------------------------------------------
Dear *{father_name}*,

Regarding student *{name}* (Reg No: *{reg_no}*, Class: *{class}*):

[Type your custom message here...]

🔗 *Parent Portal:* {portal_url}
📞 *Contact:* +91 9523012888`
        };

        let currentTemplateKey = 'fee_due';

        function getSelectedStudent() {
            const sid = parseInt(document.getElementById('studentSelectDropdown').value);
            return studentsData.find(s => parseInt(s.id) === sid) || studentsData[0] || null;
        }

        function onStudentChange() {
            const student = getSelectedStudent();
            if (!student) return;

            // Update banner
            document.getElementById('bannerPhone').textContent = student.phone || 'N/A';
            document.getElementById('bannerClass').textContent = (student.class_admitted || 'Class 5') + ' (' + (student.scholar_mode || 'Day') + ')';
            document.getElementById('bannerDue').textContent = '₹' + (parseFloat(student.due_fee) || 0).toFixed(2);
            document.getElementById('mockupRecipientName').textContent = (student.parent_name || 'Parent') + ' (' + (student.phone || '') + ')';

            updateLiveMockup();
        }

        function applyTemplate(key) {
            currentTemplateKey = key;

            // Update active cards
            document.querySelectorAll('.template-choice-btn').forEach(btn => btn.classList.remove('active'));
            const activeCard = document.getElementById('tplCard-' + key);
            if (activeCard) activeCard.classList.add('active');

            // Populate textarea
            document.getElementById('templateTextarea').value = templates[key] || '';
            updateLiveMockup();
        }

        function insertTag(tag) {
            const textarea = document.getElementById('templateTextarea');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;

            textarea.value = text.substring(0, start) + tag + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;

            updateLiveMockup();
        }

        function compileMessageForStudent(rawTemplate, student) {
            if (!student) return rawTemplate;

            const dueAmt = (parseFloat(student.due_fee) || 0).toFixed(2);
            const dueMonths = student.due_months || 'Current Session';

            return rawTemplate
                .replace(/\{name\}/g, student.name || 'Student')
                .replace(/\{father_name\}/g, student.parent_name || 'Guardian')
                .replace(/\{parent_name\}/g, student.parent_name || 'Guardian')
                .replace(/\{due_fee\}/g, dueAmt)
                .replace(/\{month\}/g, dueMonths)
                .replace(/\{reg_no\}/g, student.reg_no || 'ABSS-2026')
                .replace(/\{class\}/g, student.class_admitted || 'Class 5')
                .replace(/\{scholar_mode\}/g, student.scholar_mode || 'Day Scholar')
                .replace(/\{target_school\}/g, student.target_school || 'Sainik School')
                .replace(/\{phone\}/g, student.phone || '')
                .replace(/\{portal_url\}/g, portalUrl);
        }

        function updateLiveMockup() {
            const student = getSelectedStudent();
            const raw = document.getElementById('templateTextarea').value;
            const compiled = compileMessageForStudent(raw, student);

            // Format WhatsApp bold (*text* -> <b>text</b>) for preview
            const htmlFormatted = compiled
                .replace(/\*(.*?)\*/g, '<b>$1</b>')
                .replace(/_(.*?)_/g, '<i>$1</i>');

            document.getElementById('mockupMessageBody').innerHTML = htmlFormatted;

            // Current time for chat mockup
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            document.getElementById('mockupTime').textContent = hours + ':' + minutes + ' ' + ampm;
        }

        function cleanPhoneNumber(phone) {
            let digits = (phone || '').replace(/[^0-9]/g, '');
            if (digits.length === 10) {
                digits = '91' + digits;
            }
            return digits;
        }

        function sendWhatsApp(mode) {
            const student = getSelectedStudent();
            if (!student || !student.phone) {
                alert('Please select a student with a valid contact phone number.');
                return;
            }

            const phone = cleanPhoneNumber(student.phone);
            const raw = document.getElementById('templateTextarea').value;
            const compiled = compileMessageForStudent(raw, student);
            const encodedText = encodeURIComponent(compiled);

            let url = '';
            if (mode === 'web') {
                url = `https://web.whatsapp.com/send?phone=${phone}&text=${encodedText}`;
            } else {
                url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodedText}`;
            }

            window.open(url, '_blank');
        }

        function copyFormattedText() {
            const student = getSelectedStudent();
            const raw = document.getElementById('templateTextarea').value;
            const compiled = compileMessageForStudent(raw, student);

            navigator.clipboard.writeText(compiled).then(() => {
                alert('✅ WhatsApp message copied to clipboard!');
            }).catch(() => {
                alert('Copied message text.');
            });
        }

        function quickSendDueAlert(studentId) {
            const student = studentsData.find(s => parseInt(s.id) === studentId);
            if (!student || !student.phone) {
                alert('Phone number not found for student.');
                return;
            }

            const phone = cleanPhoneNumber(student.phone);
            const compiled = compileMessageForStudent(templates.fee_due, student);
            const encodedText = encodeURIComponent(compiled);
            const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodedText}`;

            window.open(url, '_blank');
        }

        function switchWaTab(tab) {
            document.getElementById('tabBtnSingle').classList.remove('active');
            document.getElementById('tabBtnBulk').classList.remove('active');
            document.getElementById('paneSingle').style.display = 'none';
            document.getElementById('paneBulk').style.display = 'none';

            if (tab === 'bulk') {
                document.getElementById('tabBtnBulk').classList.add('active');
                document.getElementById('paneBulk').style.display = 'block';
            } else {
                document.getElementById('tabBtnSingle').classList.add('active');
                document.getElementById('paneSingle').style.display = 'block';
            }
        }

        function filterBulkTable() {
            const query = document.getElementById('bulkSearchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#bulkDueTable tbody tr.bulk-wa-row');

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const parent = row.getAttribute('data-parent') || '';
                const phone = row.getAttribute('data-phone') || '';
                const reg = row.getAttribute('data-reg') || '';

                if (name.includes(query) || parent.includes(query) || phone.includes(query) || reg.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const initialTpl = urlParams.get('template') || 'fee_due';
            applyTemplate(initialTpl);
            onStudentChange();
        });
    </script>
</body>
</html>
