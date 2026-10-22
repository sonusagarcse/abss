<?php
require_once 'includes/auth.php';

$msg = '';
$today = date('Y-m-d');
$selected_date = isset($_GET['date']) ? trim($_GET['date']) : $today;
$filter_dept = isset($_GET['dept']) ? trim($_GET['dept']) : '';
$filter_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// Handle Attendance Submission (Bulk or Single)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_attendance'])) {
    $date = trim($_POST['attendance_date']);
    if (isset($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $tid => $status) {
            $tid = (int)$tid;
            $status = in_array($status, ['present', 'absent', 'half_day', 'on_leave']) ? $status : 'present';
            $remarks = isset($_POST['remarks'][$tid]) ? trim($_POST['remarks'][$tid]) : '';

            $check = $conn->prepare("SELECT id FROM teacher_attendance WHERE teacher_id = ? AND date = ?");
            $check->bind_param("is", $tid, $date);
            $check->execute();
            $res = $check->get_result();

            if ($res && $res->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE teacher_attendance SET status = ?, remarks = ? WHERE teacher_id = ? AND date = ?");
                $stmt->bind_param("ssis", $status, $remarks, $tid, $date);
            } else {
                $stmt = $conn->prepare("INSERT INTO teacher_attendance (teacher_id, date, status, remarks) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $tid, $date, $status, $remarks);
            }
            $stmt->execute();
        }
    }
    $msg = "Staff attendance for " . date('d M Y', strtotime($date)) . " has been saved successfully.";
}

// Fetch distinct departments for filter
$dept_res = $conn->query("SELECT DISTINCT department FROM teachers WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$departments = [];
if ($dept_res) {
    while ($d = $dept_res->fetch_assoc()) {
        $departments[] = $d['department'];
    }
}

// Build query for teachers
$where_clauses = ["t.status = 'active'"];
if (!empty($filter_dept)) {
    $where_clauses[] = "t.department = '" . $conn->real_escape_string($filter_dept) . "'";
}
if ($filter_teacher_id > 0) {
    $where_clauses[] = "t.id = " . $filter_teacher_id;
}
$where_sql = implode(' AND ', $where_clauses);

$query_sql = "
    SELECT t.id, t.name, t.phone, t.email, t.department, t.designation, t.salary, t.photo,
           ta.status, ta.remarks
    FROM teachers t
    LEFT JOIN teacher_attendance ta ON t.id = ta.teacher_id AND ta.date = '$selected_date'
    WHERE $where_sql
    ORDER BY t.name ASC
";
$teachers_query = $conn->query($query_sql);

$teachers_list = [];
$present_count = 0;
$absent_count = 0;
$half_day_count = 0;
$leave_count = 0;

if ($teachers_query) {
    while ($row = $teachers_query->fetch_assoc()) {
        // By default, if not marked, they are "present"
        $st = !empty($row['status']) ? $row['status'] : 'present';
        if ($st === 'present') $present_count++;
        elseif ($st === 'absent') $absent_count++;
        elseif ($st === 'half_day') $half_day_count++;
        elseif ($st === 'on_leave') $leave_count++;

        $row['effective_status'] = $st;
        $teachers_list[] = $row;
    }
}
$total_staff = count($teachers_list);

// Selected month stats for header / quick overview (absent summary for the active month)
$selected_month = date('Y-m', strtotime($selected_date));
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($selected_date));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff & Teacher Attendance | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .filter-panel {
            background: #fff;
            padding: 20px 24px;
            border-radius: 20px;
            border: 1px solid #eef2f6;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 18px;
            border: 2px solid #eef2f6;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            user-select: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
        }
        .stat-card.card-selected-present {
            border-color: #16a34a !important;
            background: #f0fdf4 !important;
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.2) !important;
        }
        .stat-card.card-selected-absent {
            border-color: #dc2626 !important;
            background: #fef2f2 !important;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.2) !important;
        }
        .stat-card.card-selected-half_day {
            border-color: #d97706 !important;
            background: #fffbeb !important;
            box-shadow: 0 6px 20px rgba(217, 119, 6, 0.2) !important;
        }
        .stat-card.card-selected-on_leave {
            border-color: #0284c7 !important;
            background: #f0f9ff !important;
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.2) !important;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: transform 0.2s;
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.08);
        }
        .stat-p { background: #f0fdf4; color: #16a34a; }
        .stat-a { background: #fef2f2; color: #dc2626; }
        .stat-h { background: #fffbeb; color: #d97706; }
        .stat-l { background: #f0f9ff; color: #0284c7; }

        .stat-info h3 { margin: 0; font-size: 1.45rem; font-weight: 800; color: var(--portal-dark); }
        .stat-info p { margin: 2px 0 0; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-info small.selection-hint { font-size: 0.7rem; font-weight: 700; color: #94a3b8; display: block; margin-top: 3px; }

        .portal-input {
            padding: 10px 16px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            font-size: 0.9rem;
            outline: none;
            font-family: inherit;
            font-weight: 600;
            transition: border-color 0.2s;
        }
        .portal-input:focus { border-color: var(--portal-blue); }

        .btn-action-sm {
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.82rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-present-all { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .btn-present-all:hover { background: #bbf7d0; }
        .btn-absent-all { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-absent-all:hover { background: #fecaca; }

        /* Attendance Table */
        .attendance-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .attendance-row td { padding: 16px 20px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
        .attendance-row td:first-child { border-left: 1px solid #f0f4f8; border-radius: 18px 0 0 18px; }
        .attendance-row td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 18px 18px 0; }

        .staff-meta h4 { color: var(--portal-blue); font-weight: 800; font-size: 1.02rem; margin: 0 0 3px 0; }
        .staff-meta p { margin: 0; color: #64748b; font-size: 0.82rem; font-weight: 600; }

        .status-segmented {
            display: inline-flex;
            gap: 6px;
            background: #e2e8f0;
            padding: 5px;
            border-radius: 14px;
            border: 1px solid #cbd5e1;
        }
        .status-radio-label {
            position: relative;
            cursor: pointer;
            user-select: none;
        }
        .status-radio-label input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .status-radio-label span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #64748b;
            background: #f8fafc;
            border: 1.5px solid transparent;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }
        .status-radio-label span:hover {
            color: #1e293b;
            background: #ffffff;
            transform: translateY(-1px);
        }
        /* Active Selected State - High Contrast & Vibrant */
        .status-radio-label input:checked + span.btn-p {
            background: #16a34a !important;
            color: #ffffff !important;
            border-color: #15803d !important;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.45) !important;
            transform: scale(1.04);
        }
        .status-radio-label input:checked + span.btn-a {
            background: #dc2626 !important;
            color: #ffffff !important;
            border-color: #b91c1c !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.45) !important;
            transform: scale(1.04);
        }
        .status-radio-label input:checked + span.btn-h {
            background: #d97706 !important;
            color: #ffffff !important;
            border-color: #b45309 !important;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.45) !important;
            transform: scale(1.04);
        }
        .status-radio-label input:checked + span.btn-l {
            background: #0284c7 !important;
            color: #ffffff !important;
            border-color: #0369a1 !important;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.45) !important;
            transform: scale(1.04);
        }

        /* Row Tint Highlights based on selection */
        .attendance-row {
            transition: background 0.25s, border-color 0.25s;
        }
        .attendance-row.row-status-absent td {
            background: #fef2f2 !important;
            border-color: #fecaca !important;
        }
        .attendance-row.row-status-half_day td {
            background: #fffbeb !important;
            border-color: #fde68a !important;
        }
        .attendance-row.row-status-on_leave td {
            background: #f0f9ff !important;
            border-color: #bae6fd !important;
        }
        .attendance-row.row-status-present td {
            background: #ffffff;
        }

        /* Mobile card Tint Highlights */
        .staff-card.card-status-absent {
            border: 2px solid #f87171 !important;
            background: #fff5f5 !important;
        }
        .staff-card.card-status-half_day {
            border: 2px solid #fbbf24 !important;
            background: #fffdf5 !important;
        }
        .staff-card.card-status-on_leave {
            border: 2px solid #38bdf8 !important;
            background: #f0f9ff !important;
        }

        /* Current status pill badge in table */
        .current-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .badge-present { background: #dcfce7; color: #166534; }
        .badge-absent { background: #fee2e2; color: #991b1b; }
        .badge-half_day { background: #fef3c7; color: #92400e; }
        .badge-on_leave { background: #e0f2fe; color: #0369a1; }

        .save-btn {
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            border: none;
            color: #fff;
            padding: 14px 34px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.98rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(13,71,161,0.25);
            transition: 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(13,71,161,0.35);
        }

        /* Mobile specific card styles */
        @media (max-width: 768px) {
            .portal-table-container { display: none; }
            .mobile-attendance-cards { display: flex; flex-direction: column; gap: 12px; }
            .staff-card {
                background: #ffffff;
                border: 1.5px solid #eef2f6;
                border-radius: 18px;
                padding: 16px;
                box-shadow: 0 3px 12px rgba(0,0,0,0.02);
            }
            .staff-card .card-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }
            .status-segmented-mobile {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr;
                gap: 5px;
                background: #f1f5f9;
                padding: 4px;
                border-radius: 12px;
                width: 100%;
            }
            .status-segmented-mobile .status-radio-label {
                width: 100%;
                text-align: center;
            }
            .status-segmented-mobile .status-radio-label span {
                width: 100%;
                justify-content: center;
                padding: 9px 4px;
                font-size: 0.76rem;
            }
            .floating-mobile-save {
                position: sticky;
                bottom: 16px;
                margin-top: 20px;
                z-index: 100;
            }
            .floating-mobile-save .save-btn {
                width: 100%;
                justify-content: center;
                padding: 16px;
            }
        }
        @media (min-width: 769px) {
            .mobile-attendance-cards { display: none; }
            .floating-mobile-save { display: none; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
            <div>
                <h1 style="font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800; color: var(--portal-dark); margin: 0; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-user-check" style="color: var(--portal-blue);"></i> Staff & Teacher Attendance
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">
                    Daily presence is defaulted to <strong>Present</strong>. Mark <strong>Absent / Half-Day / Leave</strong> when off to deduct from salary invoice.
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <a href="teachers.php" class="btn-action-sm" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;">
                    <i class="fas fa-arrow-left"></i> Teachers Directory
                </a>
                <a href="teacher_invoices.php" class="btn-action-sm" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                    <i class="fas fa-money-check-alt"></i> View Invoices
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; color:#166534; padding:14px 20px; border-radius:14px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle" style="font-size:1.2rem;"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Overview Bar (Interactive: Click any card to filter staff table by that status) -->
        <div class="stats-grid" id="attendanceStatsGrid">
            <div class="stat-card" id="cardPresent" onclick="toggleStatusFilter('present')" title="Click to view only Present staff">
                <div class="stat-icon stat-p"><i class="fas fa-check"></i></div>
                <div class="stat-info">
                    <h3 id="statPresentCount"><?php echo $present_count; ?></h3>
                    <p>Present</p>
                    <small class="selection-hint" id="hintPresent">Click to filter</small>
                </div>
            </div>
            <div class="stat-card" id="cardAbsent" onclick="toggleStatusFilter('absent')" title="Click to view only Absent staff">
                <div class="stat-icon stat-a"><i class="fas fa-times"></i></div>
                <div class="stat-info">
                    <h3 id="statAbsentCount"><?php echo $absent_count; ?></h3>
                    <p>Absent</p>
                    <small class="selection-hint" id="hintAbsent">Click to filter</small>
                </div>
            </div>
            <div class="stat-card" id="cardHalfDay" onclick="toggleStatusFilter('half_day')" title="Click to view only Half-Day staff">
                <div class="stat-icon stat-h"><i class="fas fa-adjust"></i></div>
                <div class="stat-info">
                    <h3 id="statHalfDayCount"><?php echo $half_day_count; ?></h3>
                    <p>Half Day</p>
                    <small class="selection-hint" id="hintHalfDay">Click to filter</small>
                </div>
            </div>
            <div class="stat-card" id="cardLeave" onclick="toggleStatusFilter('on_leave')" title="Click to view only On Leave staff">
                <div class="stat-icon stat-l"><i class="fas fa-calendar-minus"></i></div>
                <div class="stat-info">
                    <h3 id="statLeaveCount"><?php echo $leave_count; ?></h3>
                    <p>On Leave</p>
                    <small class="selection-hint" id="hintLeave">Click to filter</small>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="filter-panel">
            <form action="" method="GET" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:0;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <label style="font-size:0.85rem; font-weight:800; color:#475569;"><i class="fas fa-calendar-day"></i> Date:</label>
                    <input type="date" name="date" value="<?php echo $selected_date; ?>" class="portal-input" onchange="this.form.submit()" style="cursor:pointer;">
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <label style="font-size:0.85rem; font-weight:800; color:#475569;"><i class="fas fa-building"></i> Dept:</label>
                    <select name="dept" class="portal-input" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $filter_dept === $d ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($filter_teacher_id > 0): ?>
                    <input type="hidden" name="teacher_id" value="<?php echo $filter_teacher_id; ?>">
                    <a href="teacher_attendance.php?date=<?php echo urlencode($selected_date); ?>" class="btn-action-sm" style="background:#fee2e2; color:#dc2626;">
                        <i class="fas fa-times"></i> Clear Staff Filter
                    </a>
                <?php endif; ?>
            </form>

            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <input type="text" id="staffSearch" placeholder="Search staff name or role..." onkeyup="filterStaff()" class="portal-input" style="min-width: 200px;">
                <button type="button" class="btn-action-sm btn-present-all" onclick="markAllStatus('present')">
                    <i class="fas fa-check-double"></i> Mark All Present
                </button>
                <button type="button" class="btn-action-sm btn-absent-all" onclick="markAllStatus('absent')">
                    <i class="fas fa-times-circle"></i> Mark All Absent
                </button>
            </div>
        </div>

        <!-- Attendance Form -->
        <form action="" method="POST" id="adminStaffAttendanceForm">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

            <!-- Desktop View -->
            <div class="portal-table-container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th style="padding: 12px 20px; font-size: 0.82rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Staff Member</th>
                            <th style="padding: 12px 20px; font-size: 0.82rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Role & Dept</th>
                            <th style="padding: 12px 20px; font-size: 0.82rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: center;">Status (Default: Present)</th>
                            <th style="padding: 12px 20px; font-size: 0.82rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Remarks (Optional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($teachers_list)): foreach ($teachers_list as $row): 
                            $eff_st = $row['effective_status'];
                        ?>
                            <tr class="attendance-row staff-item row-status-<?= $eff_st ?>" id="staffRow_<?= $row['id'] ?>" data-name="<?= htmlspecialchars(strtolower($row['name'] . ' ' . $row['designation'] . ' ' . $row['department'])) ?>">
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <?php if (!empty($row['photo'])): ?>
                                            <img src="../<?= htmlspecialchars($row['photo']) ?>" alt="Photo" style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid #c7d2fe;">
                                        <?php else: ?>
                                            <div style="width:42px; height:42px; border-radius:50%; background:#eef2ff; color:var(--portal-blue); display:flex; align-items:center; justify-content:center; font-size:0.9rem; font-weight:800;">
                                                <?php $ini = explode(' ', $row['name']); echo htmlspecialchars(substr($ini[0],0,1).(isset($ini[1])?substr($ini[1],0,1):'')); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="staff-meta">
                                            <h4><?= htmlspecialchars($row['name']) ?></h4>
                                            <p><i class="fas fa-phone-alt" style="font-size:0.75rem;"></i> <?= htmlspecialchars($row['phone'] ?: 'No phone') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($row['designation'] ?: 'Faculty') ?></div>
                                    <small style="color:#64748b; font-weight:600;"><i class="fas fa-building"></i> <?= htmlspecialchars($row['department'] ?: 'General') ?></small>
                                    <div id="statusBadge_<?= $row['id'] ?>">
                                        <?php if ($eff_st === 'present'): ?>
                                            <span class="current-status-badge badge-present"><i class="fas fa-check"></i> Present</span>
                                        <?php elseif ($eff_st === 'absent'): ?>
                                            <span class="current-status-badge badge-absent"><i class="fas fa-times"></i> Absent</span>
                                        <?php elseif ($eff_st === 'half_day'): ?>
                                            <span class="current-status-badge badge-half_day"><i class="fas fa-adjust"></i> Half Day</span>
                                        <?php elseif ($eff_st === 'on_leave'): ?>
                                            <span class="current-status-badge badge-on_leave"><i class="fas fa-calendar-minus"></i> On Leave</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div class="status-segmented">
                                        <label class="status-radio-label">
                                            <input type="radio" name="status[<?= $row['id'] ?>]" value="present" <?= $row['effective_status'] == 'present' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                            <span class="btn-p"><i class="fas fa-check"></i> Present</span>
                                        </label>
                                        <label class="status-radio-label">
                                            <input type="radio" name="status[<?= $row['id'] ?>]" value="absent" <?= $row['effective_status'] == 'absent' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                            <span class="btn-a"><i class="fas fa-times"></i> Absent</span>
                                        </label>
                                        <label class="status-radio-label">
                                            <input type="radio" name="status[<?= $row['id'] ?>]" value="half_day" <?= $row['effective_status'] == 'half_day' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                            <span class="btn-h"><i class="fas fa-adjust"></i> Half Day</span>
                                        </label>
                                        <label class="status-radio-label">
                                            <input type="radio" name="status[<?= $row['id'] ?>]" value="on_leave" <?= $row['effective_status'] == 'on_leave' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                            <span class="btn-l"><i class="fas fa-calendar-minus"></i> Leave</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['remarks'] ?? '') ?>" placeholder="e.g. Sick leave, Medical, Casual..." class="portal-input" style="width: 100%; max-width: 260px; padding: 7px 12px; font-size: 0.82rem;">
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding: 40px; color: #94a3b8; font-weight:700;">
                                    No staff records found matching criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View (Cards) -->
            <div class="mobile-attendance-cards">
                <?php if(!empty($teachers_list)): foreach ($teachers_list as $row): 
                    $eff_st = $row['effective_status'];
                ?>
                    <div class="staff-card staff-item card-status-<?= $eff_st ?>" id="staffCard_<?= $row['id'] ?>" data-name="<?= htmlspecialchars(strtolower($row['name'] . ' ' . $row['designation'] . ' ' . $row['department'])) ?>">
                        <div class="card-top">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <?php if (!empty($row['photo'])): ?>
                                    <img src="../<?= htmlspecialchars($row['photo']) ?>" alt="Photo" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid #c7d2fe;">
                                <?php else: ?>
                                    <div style="width:38px; height:38px; border-radius:50%; background:#eef2ff; color:var(--portal-blue); display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:800;">
                                        <?php $ini = explode(' ', $row['name']); echo htmlspecialchars(substr($ini[0],0,1).(isset($ini[1])?substr($ini[1],0,1):'')); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 style="margin:0; font-size:0.95rem; font-weight:800; color:var(--portal-blue);"><?= htmlspecialchars($row['name']) ?></h4>
                                    <p style="margin:2px 0 0; font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($row['designation'] ?: 'Faculty') ?> • <?= htmlspecialchars($row['department'] ?: 'General') ?></p>
                                </div>
                            </div>
                            <div id="statusBadgeMobile_<?= $row['id'] ?>">
                                <?php if ($eff_st === 'present'): ?>
                                    <span class="current-status-badge badge-present"><i class="fas fa-check"></i> Present</span>
                                <?php elseif ($eff_st === 'absent'): ?>
                                    <span class="current-status-badge badge-absent"><i class="fas fa-times"></i> Absent</span>
                                <?php elseif ($eff_st === 'half_day'): ?>
                                    <span class="current-status-badge badge-half_day"><i class="fas fa-adjust"></i> Half</span>
                                <?php elseif ($eff_st === 'on_leave'): ?>
                                    <span class="current-status-badge badge-on_leave"><i class="fas fa-calendar-minus"></i> Leave</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="status-segmented-mobile">
                            <label class="status-radio-label">
                                <input type="radio" name="status[<?= $row['id'] ?>]" value="present" <?= $row['effective_status'] == 'present' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                <span class="btn-p"><i class="fas fa-check"></i> Present</span>
                            </label>
                            <label class="status-radio-label">
                                <input type="radio" name="status[<?= $row['id'] ?>]" value="absent" <?= $row['effective_status'] == 'absent' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                <span class="btn-a"><i class="fas fa-times"></i> Absent</span>
                            </label>
                            <label class="status-radio-label">
                                <input type="radio" name="status[<?= $row['id'] ?>]" value="half_day" <?= $row['effective_status'] == 'half_day' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                <span class="btn-h"><i class="fas fa-adjust"></i> Half</span>
                            </label>
                            <label class="status-radio-label">
                                <input type="radio" name="status[<?= $row['id'] ?>]" value="on_leave" <?= $row['effective_status'] == 'on_leave' ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                <span class="btn-l"><i class="fas fa-calendar-minus"></i> Leave</span>
                            </label>
                        </div>

                        <div style="margin-top: 10px;">
                            <input type="text" name="remarks[<?= $row['id'] ?>]" value="<?= htmlspecialchars($row['remarks'] ?? '') ?>" placeholder="Remarks (optional)" class="portal-input" style="width: 100%; padding: 7px 10px; font-size: 0.8rem;">
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Save Action Button -->
            <div style="margin-top: 24px; display:flex; justify-content: flex-end;" class="desktop-save">
                <button type="submit" name="save_attendance" class="save-btn">
                    <i class="fas fa-save"></i> Save Attendance &rarr;
                </button>
            </div>

            <!-- Floating Save Bar for Mobile -->
            <div class="floating-mobile-save">
                <button type="submit" name="save_attendance" class="save-btn">
                    <i class="fas fa-save"></i> Save Attendance (<?= date('d M', strtotime($selected_date)) ?>)
                </button>
            </div>
        </form>
    </main>

    <script>
        let currentStatusFilter = null;

        function toggleStatusFilter(status) {
            if (currentStatusFilter === status) {
                // Clear selection if clicking the already selected card
                currentStatusFilter = null;
            } else {
                currentStatusFilter = status;
            }
            updateStatusCardStyles();
            applyAllFilters();
        }

        function updateStatusCardStyles() {
            const cards = {
                'present': { id: 'cardPresent', hintId: 'hintPresent', class: 'card-selected-present' },
                'absent': { id: 'cardAbsent', hintId: 'hintAbsent', class: 'card-selected-absent' },
                'half_day': { id: 'cardHalfDay', hintId: 'hintHalfDay', class: 'card-selected-half_day' },
                'on_leave': { id: 'cardLeave', hintId: 'hintLeave', class: 'card-selected-on_leave' }
            };

            for (const [st, meta] of Object.entries(cards)) {
                const el = document.getElementById(meta.id);
                const hint = document.getElementById(meta.hintId);
                if (!el) continue;

                if (currentStatusFilter === st) {
                    el.classList.add(meta.class);
                    if (hint) {
                        hint.innerHTML = '<i class="fas fa-check-circle"></i> Active Filter (Click to show all)';
                        hint.style.color = '#0f172a';
                    }
                } else {
                    el.classList.remove(meta.class);
                    if (hint) {
                        hint.textContent = 'Click to filter';
                        hint.style.color = '#94a3b8';
                    }
                }
            }
        }

        function updateRowAndBadgeVisuals(teacherId, status) {
            const badgeMeta = {
                'present': { text: 'Present', icon: 'fa-check', class: 'badge-present' },
                'absent': { text: 'Absent', icon: 'fa-times', class: 'badge-absent' },
                'half_day': { text: 'Half Day', icon: 'fa-adjust', class: 'badge-half_day' },
                'on_leave': { text: 'On Leave', icon: 'fa-calendar-minus', class: 'badge-on_leave' }
            };

            const meta = badgeMeta[status] || badgeMeta['present'];
            const badgeHtml = `<span class="current-status-badge ${meta.class}"><i class="fas ${meta.icon}"></i> ${meta.text}</span>`;

            // Update desktop row
            const row = document.getElementById(`staffRow_${teacherId}`);
            if (row) {
                row.classList.remove('row-status-present', 'row-status-absent', 'row-status-half_day', 'row-status-on_leave');
                row.classList.add(`row-status-${status}`);
            }
            const badgeEl = document.getElementById(`statusBadge_${teacherId}`);
            if (badgeEl) {
                badgeEl.innerHTML = badgeHtml;
            }

            // Update mobile card
            const card = document.getElementById(`staffCard_${teacherId}`);
            if (card) {
                card.classList.remove('card-status-present', 'card-status-absent', 'card-status-half_day', 'card-status-on_leave');
                card.classList.add(`card-status-${status}`);
            }
            const badgeMob = document.getElementById(`statusBadgeMobile_${teacherId}`);
            if (badgeMob) {
                badgeMob.innerHTML = badgeHtml;
            }
        }

        function markAllStatus(statusVal) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${statusVal}"]`);
            radios.forEach(r => {
                r.checked = true;
                const matches = r.name.match(/\[(\d+)\]/);
                if (matches && matches[1]) {
                    updateRowAndBadgeVisuals(matches[1], statusVal);
                }
            });
            updateCounters();
            applyAllFilters();
        }

        function filterStaff() {
            applyAllFilters();
        }

        function applyAllFilters() {
            const searchInput = document.getElementById('staffSearch');
            const searchFilter = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const items = document.querySelectorAll('.staff-item');

            items.forEach(item => {
                const searchStr = item.getAttribute('data-name') || '';
                const checkedRadio = item.querySelector('input[type="radio"]:checked');
                const itemStatus = checkedRadio ? checkedRadio.value : 'present';

                const matchesSearch = !searchFilter || searchStr.includes(searchFilter);
                const matchesStatus = !currentStatusFilter || itemStatus === currentStatusFilter;

                if (matchesSearch && matchesStatus) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function updateCounters() {
            const checkedRadios = document.querySelectorAll('input[type="radio"]:checked');
            let p = 0, a = 0, h = 0, l = 0;
            let countedIds = new Set();

            checkedRadios.forEach(cr => {
                const nameAttr = cr.name;
                if (!countedIds.has(nameAttr)) {
                    countedIds.add(nameAttr);
                    if (cr.value === 'present') p++;
                    else if (cr.value === 'absent') a++;
                    else if (cr.value === 'half_day') h++;
                    else if (cr.value === 'on_leave') l++;
                }
            });

            const statP = document.getElementById('statPresentCount');
            const statA = document.getElementById('statAbsentCount');
            const statH = document.getElementById('statHalfDayCount');
            const statL = document.getElementById('statLeaveCount');
            if (statP) statP.innerText = p;
            if (statA) statA.innerText = a;
            if (statH) statH.innerText = h;
            if (statL) statL.innerText = l;
        }

        function syncAndRecalc(elem) {
            const name = elem.name;
            const val = elem.value;
            const sameInputs = document.querySelectorAll(`input[name="${name}"][value="${val}"]`);
            sameInputs.forEach(i => i.checked = true);

            // Extract teacher ID from name attribute (e.g. status[12])
            const matches = name.match(/\[(\d+)\]/);
            if (matches && matches[1]) {
                updateRowAndBadgeVisuals(matches[1], val);
            }

            updateCounters();
            applyAllFilters();
        }

        // Initialize counters and cards on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCounters();
        });
    </script>
</body>
</html>
