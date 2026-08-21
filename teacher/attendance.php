<?php
require_once 'includes/auth.php';

$msg = '';
$today = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Handle Attendance Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_attendance'])) {
    $date = $_POST['attendance_date'];
    if (isset($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $sid => $status) {
            $sid = (int)$sid;
            $status = in_array($status, ['present', 'absent', 'late']) ? $status : 'present';
            
            $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
            $check->bind_param("is", $sid, $date);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND date = ?");
                $stmt->bind_param("sis", $status, $sid, $date);
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $sid, $date, $status);
            }
            $stmt->execute();
        }
    }
    $msg = "Attendance for " . date('d M Y', strtotime($date)) . " has been recorded successfully.";
}

$students_query = $conn->query("
    SELECT s.id, s.name, s.target_school, s.scholar_mode, a.status 
    FROM students s 
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = '$selected_date'
    WHERE s.status = 'active'
    ORDER BY s.name ASC
");

$students_list = [];
$present_count = 0;
$absent_count = 0;
$late_count = 0;

if ($students_query) {
    while ($row = $students_query->fetch_assoc()) {
        $st = $row['status'] ?: 'present'; // Default to present
        if ($st === 'present') $present_count++;
        elseif ($st === 'absent') $absent_count++;
        elseif ($st === 'late') $late_count++;
        
        $row['effective_status'] = $st;
        $students_list[] = $row;
    }
}
$total_students = count($students_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mark Attendance | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        :root {
            --teacher-purple: #7c3aed;
            --teacher-dark: #5b21b6;
        }

        .page-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 26px;
            border: 1px solid #ede9fe;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.03);
            margin-top: 15px;
        }

        .stats-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-pill {
            background: #ffffff;
            border: 1px solid #ede9fe;
            border-radius: 16px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .stat-pill .icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .action-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
            background: #f8faff;
            padding: 14px 18px;
            border-radius: 18px;
            border: 1px solid #ede9fe;
        }

        .form-control {
            padding: 10px 14px;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            font-size: 0.92rem;
            outline: none;
            font-family: inherit;
            font-weight: 600;
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: var(--teacher-purple); }

        .btn-action-sm {
            padding: 9px 16px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.82rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-present-all { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .btn-present-all:hover { background: #bbf7d0; }
        .btn-absent-all { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-absent-all:hover { background: #fecaca; }

        /* Desktop Table & Mobile Cards */
        .attendance-table-wrap { width: 100%; overflow-x: auto; }
        .attendance-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .attendance-table th { background: #f5f3ff; color: var(--teacher-dark); font-weight: 800; text-transform: uppercase; font-size: 0.78rem; padding: 12px 16px; border: none; }
        .attendance-table th:first-child { border-radius: 12px 0 0 12px; }
        .attendance-table th:last-child { border-radius: 0 12px 12px 0; }
        .attendance-row td { padding: 16px; background: #fff; border-top: 1px solid #ede9fe; border-bottom: 1px solid #ede9fe; font-size: 0.92rem; vertical-align: middle; }
        .attendance-row td:first-child { border-left: 1px solid #ede9fe; border-radius: 16px 0 0 16px; }
        .attendance-row td:last-child { border-right: 1px solid #ede9fe; border-radius: 0 16px 16px 0; }

        /* Segmented Radio Buttons */
        .status-segmented {
            display: inline-flex;
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
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
        }
        .status-radio-label span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #64748b;
            transition: all 0.2s ease;
        }
        .status-radio-label input:checked + span.btn-p {
            background: #16a34a;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.35);
        }
        .status-radio-label input:checked + span.btn-a {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35);
        }
        .status-radio-label input:checked + span.btn-l {
            background: #d97706;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.35);
        }

        /* Sticky Save Action Bar */
        .save-bar-desktop {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #ede9fe;
        }
        .btn-submit-attendance {
            background: linear-gradient(135deg, var(--teacher-purple) 0%, var(--teacher-dark) 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.3);
            transition: all 0.25s;
        }
        .btn-submit-attendance:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.4);
        }

        /* Mobile specific card styles */
        @media (max-width: 768px) {
            .page-card { padding: 16px; border-radius: 18px; margin-top: 10px; }
            .attendance-table-wrap { display: none; }
            .mobile-attendance-cards { display: flex; flex-direction: column; gap: 12px; }
            .student-card {
                background: #ffffff;
                border: 1.5px solid #ede9fe;
                border-radius: 18px;
                padding: 16px;
                box-shadow: 0 3px 12px rgba(0,0,0,0.02);
            }
            .student-card .card-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }
            .status-segmented-mobile {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 6px;
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
                padding: 10px 6px;
                font-size: 0.85rem;
            }
            .floating-mobile-save {
                position: sticky;
                bottom: 16px;
                z-index: 1000;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(12px);
                border: 1px solid #ede9fe;
                border-radius: 18px;
                padding: 12px 16px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.12);
                margin-top: 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .floating-mobile-save .btn-submit-attendance {
                width: 100%;
                justify-content: center;
                padding: 14px;
                font-size: 0.95rem;
            }
            .action-toolbar { flex-direction: column; align-items: stretch; }
            .action-toolbar > div { width: 100%; }
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
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h1 style="font-size: clamp(1.4rem, 2.5vw, 1.8rem); color: var(--teacher-dark); font-weight: 900; margin: 0;">
                    Student Attendance
                </h1>
                <p style="color: #64748b; margin-top: 2px; font-size: 0.88rem; font-weight: 600;">
                    Record and manage daily classroom attendance.
                </p>
            </div>
            <div style="font-size: 0.85rem; font-weight: 800; color: #7c3aed; background: #ede9fe; padding: 6px 14px; border-radius: 50px;">
                <i class="fas fa-calendar-day"></i> <?= date('l, d M Y', strtotime($selected_date)) ?>
            </div>
        </header>

        <?php if ($msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 14px 20px; border-radius: 14px; margin-bottom: 18px; font-weight: 700; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px; font-size: 0.9rem;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem; color: #22c55e;"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Summary Stats Ribbon -->
        <div class="stats-ribbon">
            <div class="stat-pill">
                <div class="icon" style="background: #ede9fe; color: #7c3aed;"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #0f172a;"><?= $total_students ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Total Active</span>
                </div>
            </div>
            <div class="stat-pill">
                <div class="icon" style="background: #dcfce7; color: #16a34a;"><i class="fas fa-user-check"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #16a34a;" id="statPresentCount"><?= $present_count ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Present</span>
                </div>
            </div>
            <div class="stat-pill">
                <div class="icon" style="background: #fee2e2; color: #dc2626;"><i class="fas fa-user-times"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #dc2626;" id="statAbsentCount"><?= $absent_count ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Absent</span>
                </div>
            </div>
            <div class="stat-pill">
                <div class="icon" style="background: #fef3c7; color: #d97706;"><i class="fas fa-user-clock"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #d97706;" id="statLateCount"><?= $late_count ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Late</span>
                </div>
            </div>
        </div>

        <div class="page-card">
            
            <!-- Controls Toolbar: Date Picker, Search & Bulk Select -->
            <div class="action-toolbar">
                <form method="GET" action="attendance.php" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <label for="date" style="font-weight: 800; color: #334155; font-size: 0.85rem;">Date:</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
                </form>

                <div style="flex: 1; min-width: 180px; max-width: 280px;">
                    <input type="text" id="studentSearch" placeholder="Search student name or ID..." class="form-control" style="width: 100%;" onkeyup="filterStudents()">
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn-action-sm btn-present-all" onclick="markAllStatus('present')">
                        <i class="fas fa-check-double"></i> All Present
                    </button>
                    <button type="button" class="btn-action-sm btn-absent-all" onclick="markAllStatus('absent')">
                        <i class="fas fa-times"></i> All Absent
                    </button>
                </div>
            </div>

            <form method="POST" action="attendance.php" id="attendanceForm">
                <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">
                
                <!-- 1. Desktop Table View -->
                <div class="attendance-table-wrap">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Roll/ID</th>
                                <th>Student Details</th>
                                <th>Target School / Program</th>
                                <th>Scholar Mode</th>
                                <th style="text-align: right;">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students_list)): ?>
                                <?php foreach ($students_list as $row): ?>
                                    <tr class="attendance-row student-item" data-name="<?= htmlspecialchars(strtolower($row['name'] . ' ' . $row['id'])) ?>">
                                        <td><strong style="color: #64748b;">#<?= $row['id'] ?></strong></td>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($row['name']) ?></div>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; background: #ede9fe; color: #6d28d9; padding: 3px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                <?= htmlspecialchars($row['target_school'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
                                                <?= htmlspecialchars($row['scholar_mode'] ?? 'Day Scholar') ?>
                                            </span>
                                        </td>
                                        <td align="right">
                                            <div class="status-segmented">
                                                <label class="status-radio-label">
                                                    <input type="radio" name="status[<?= $row['id'] ?>]" value="present" <?= ($row['effective_status'] == 'present') ? 'checked' : '' ?> onchange="updateCounters()">
                                                    <span class="btn-p"><i class="fas fa-check"></i> Present</span>
                                                </label>
                                                <label class="status-radio-label">
                                                    <input type="radio" name="status[<?= $row['id'] ?>]" value="absent" <?= ($row['effective_status'] == 'absent') ? 'checked' : '' ?> onchange="updateCounters()">
                                                    <span class="btn-a"><i class="fas fa-times"></i> Absent</span>
                                                </label>
                                                <label class="status-radio-label">
                                                    <input type="radio" name="status[<?= $row['id'] ?>]" value="late" <?= ($row['effective_status'] == 'late') ? 'checked' : '' ?> onchange="updateCounters()">
                                                    <span class="btn-l"><i class="fas fa-clock"></i> Late</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No active students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 2. Mobile Responsive Card List (Visible on phones) -->
                <div class="mobile-attendance-cards">
                    <?php if (!empty($students_list)): ?>
                        <?php foreach ($students_list as $row): ?>
                            <div class="student-card student-item" data-name="<?= htmlspecialchars(strtolower($row['name'] . ' ' . $row['id'])) ?>">
                                <div class="card-top">
                                    <div>
                                        <div style="font-weight: 800; font-size: 1rem; color: #0f172a; margin-bottom: 2px;">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.76rem;">
                                            <span style="color: #7c3aed; font-weight: 800;">#<?= $row['id'] ?></span>
                                            <span style="background: #ede9fe; color: #6d28d9; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                                                <?= htmlspecialchars($row['target_school'] ?? 'General') ?>
                                            </span>
                                            <span style="color: #64748b; font-weight: 600;">
                                                <?= htmlspecialchars($row['scholar_mode'] ?? 'Day Scholar') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="status-segmented-mobile">
                                    <label class="status-radio-label">
                                        <input type="radio" name="status[<?= $row['id'] ?>]" value="present" <?= ($row['effective_status'] == 'present') ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                        <span class="btn-p"><i class="fas fa-check"></i> Present</span>
                                    </label>
                                    <label class="status-radio-label">
                                        <input type="radio" name="status[<?= $row['id'] ?>]" value="absent" <?= ($row['effective_status'] == 'absent') ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                        <span class="btn-a"><i class="fas fa-times"></i> Absent</span>
                                    </label>
                                    <label class="status-radio-label">
                                        <input type="radio" name="status[<?= $row['id'] ?>]" value="late" <?= ($row['effective_status'] == 'late') ? 'checked' : '' ?> onchange="syncAndRecalc(this)">
                                        <span class="btn-l"><i class="fas fa-clock"></i> Late</span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: #94a3b8; padding: 30px;">No active students found.</div>
                    <?php endif; ?>
                </div>

                <!-- Desktop Bottom Bar -->
                <div class="save-bar-desktop">
                    <span style="color: #64748b; font-size: 0.88rem; font-weight: 600;">
                        Date: <strong><?= date('d M, Y', strtotime($selected_date)) ?></strong>
                    </span>
                    <button type="submit" name="save_attendance" class="btn-submit-attendance">
                        <i class="fas fa-save"></i> Save Attendance &rarr;
                    </button>
                </div>

                <!-- Mobile Floating Sticky Save Bar -->
                <div class="floating-mobile-save">
                    <button type="submit" name="save_attendance" class="btn-submit-attendance">
                        <i class="fas fa-save"></i> Save Attendance (<?= date('d M', strtotime($selected_date)) ?>)
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function markAllStatus(statusVal) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${statusVal}"]`);
            radios.forEach(r => r.checked = true);
            updateCounters();
        }

        function filterStudents() {
            const input = document.getElementById('studentSearch');
            const filter = input.value.toLowerCase().trim();
            const items = document.querySelectorAll('.student-item');

            items.forEach(item => {
                const searchStr = item.getAttribute('data-name') || '';
                if (searchStr.includes(filter)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function updateCounters() {
            // Count from visible radio groups
            const presentInputs = document.querySelectorAll('.attendance-table input[value="present"]:checked, .mobile-attendance-cards input[value="present"]:checked');
            // Since we might have both desktop and mobile rendered, count distinct student names/keys
            const checkedRadios = document.querySelectorAll('input[type="radio"]:checked');
            let p = 0, a = 0, l = 0;
            let countedIds = new Set();

            checkedRadios.forEach(cr => {
                const nameAttr = cr.name;
                if (!countedIds.has(nameAttr)) {
                    countedIds.add(nameAttr);
                    if (cr.value === 'present') p++;
                    else if (cr.value === 'absent') a++;
                    else if (cr.value === 'late') l++;
                }
            });

            const statP = document.getElementById('statPresentCount');
            const statA = document.getElementById('statAbsentCount');
            const statL = document.getElementById('statLateCount');
            if (statP) statP.innerText = p;
            if (statA) statA.innerText = a;
            if (statL) statL.innerText = l;
        }

        function syncAndRecalc(elem) {
            // Synchronize desktop table radio with mobile card radio
            const name = elem.name;
            const val = elem.value;
            const sameInputs = document.querySelectorAll(`input[name="${name}"][value="${val}"]`);
            sameInputs.forEach(i => i.checked = true);
            updateCounters();
        }
    </script>
</body>
</html>

