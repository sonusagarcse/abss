<?php
require_once 'includes/auth.php';

$msg = '';
$msg_type = 'success';

// Handle Single Student Group Update (AJAX / POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_single_group'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $new_group = trim($_POST['academic_group'] ?? 'Group A');

    if ($student_id > 0 && in_array($new_group, ['Group A', 'Group B', 'Group C', 'Group D'])) {
        $stmt = $conn->prepare("UPDATE students SET academic_group = ? WHERE id = ?");
        $stmt->bind_param("si", $new_group, $student_id);
        if ($stmt->execute()) {
            $msg = "Successfully assigned student to <strong>$new_group</strong>.";
            $msg_type = 'success';
        } else {
            $msg = "Error updating group: " . $stmt->error;
            $msg_type = 'danger';
        }
    }
}

// Handle Bulk Group Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_bulk_groups'])) {
    $student_ids = $_POST['student_ids'] ?? [];
    $bulk_group = trim($_POST['bulk_academic_group'] ?? '');

    if (!empty($student_ids) && is_array($student_ids) && in_array($bulk_group, ['Group A', 'Group B', 'Group C', 'Group D'])) {
        $ids_clean = array_map('intval', $student_ids);
        $ids_str = implode(',', $ids_clean);

        if (!empty($ids_str)) {
            $conn->query("UPDATE students SET academic_group = '$bulk_group' WHERE id IN ($ids_str)");
            $updated_cnt = count($ids_clean);
            $msg = "Successfully updated <strong>$updated_cnt candidates</strong> to <strong>$bulk_group</strong>.";
            $msg_type = 'success';
        }
    } else {
        $msg = "Please select candidates and a valid target group for bulk assignment.";
        $msg_type = 'warning';
    }
}

// Fetch active students
$students_res = $conn->query("SELECT id, reg_no, name, class_admitted, target_school, scholar_mode, academic_group, phone, student_photo FROM students WHERE (status = 'active' OR status IS NULL) ORDER BY name ASC");
$all_students = [];
$group_counts = ['Group A' => 0, 'Group B' => 0, 'Group C' => 0, 'Group D' => 0];

if ($students_res && $students_res->num_rows > 0) {
    while ($row = $students_res->fetch_assoc()) {
        $grp = $row['academic_group'] ?? 'Group A';
        if (!isset($group_counts[$grp])) $group_counts[$grp] = 0;
        $group_counts[$grp]++;
        $all_students[] = $row;
    }
}

$filter_grp = $_GET['group'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Student Groups | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .group-assign-container {
            max-width: 1250px;
            margin: 0 auto;
        }

        .group-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .group-stat-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease;
        }
        .group-stat-card:hover { transform: translateY(-3px); }

        .group-pill-badge {
            font-size: 0.76rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 50px;
            text-transform: uppercase;
        }
        .badge-grp-a { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .badge-grp-b { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-grp-c { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
        .badge-grp-d { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }

        .filter-bar-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .student-assign-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .student-assign-table th {
            padding: 12px 16px;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: none;
        }

        .student-assign-table td {
            padding: 14px 16px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .student-assign-table tr td:first-child {
            border-left: 1px solid #e2e8f0;
            border-radius: 14px 0 0 14px;
        }
        .student-assign-table tr td:last-child {
            border-right: 1px solid #e2e8f0;
            border-radius: 0 14px 14px 0;
        }

        .group-inline-select {
            padding: 8px 14px;
            border-radius: 10px;
            border: 2px solid #cbd5e1;
            font-weight: 800;
            font-size: 0.88rem;
            color: #0f172a;
            outline: none;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .group-inline-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="group-assign-container">

            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="font-size: 1.8rem; font-weight: 900; color: var(--portal-dark); margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-users-gear" style="color: #2563eb;"></i> Assign Student Academic Groups
                    </h1>
                    <p style="color: #64748b; margin: 4px 0 0 0; font-size: 0.92rem; font-weight: 500;">
                        Quickly categorize candidates into Group A, Group B, Group C, or Group D for targeted syllabus tracking.
                    </p>
                </div>

                <a href="students.php" class="btn" style="background: #eff6ff; color: #2563eb; font-weight: 800; border-radius: 12px; padding: 10px 18px; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #bfdbfe;">
                    <i class="fas fa-user-graduate"></i> Student Registry Directory
                </a>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-<?php echo $msg_type; ?>" style="border-radius: 14px; padding: 14px 20px; font-weight: 700; margin-bottom: 25px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- Group Counts Overview -->
            <div class="group-stat-grid">
                <a href="assign_groups.php?group=Group A" style="text-decoration: none;" class="group-stat-card">
                    <div>
                        <span class="group-pill-badge badge-grp-a">Group A</span>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-top: 6px;">
                            <?php echo number_format($group_counts['Group A'] ?? 0); ?> <small style="font-size: 0.8rem; color: #64748b;">students</small>
                        </div>
                        <small style="color: #64748b; font-weight: 600; font-size: 0.75rem;">Primary Foundation</small>
                    </div>
                    <i class="fas fa-cubes" style="font-size: 1.8rem; color: #2563eb; opacity: 0.4;"></i>
                </a>

                <a href="assign_groups.php?group=Group B" style="text-decoration: none;" class="group-stat-card">
                    <div>
                        <span class="group-pill-badge badge-grp-b">Group B</span>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-top: 6px;">
                            <?php echo number_format($group_counts['Group B'] ?? 0); ?> <small style="font-size: 0.8rem; color: #64748b;">students</small>
                        </div>
                        <small style="color: #64748b; font-weight: 600; font-size: 0.75rem;">Middle Competitive</small>
                    </div>
                    <i class="fas fa-microscope" style="font-size: 1.8rem; color: #059669; opacity: 0.4;"></i>
                </a>

                <a href="assign_groups.php?group=Group C" style="text-decoration: none;" class="group-stat-card">
                    <div>
                        <span class="group-pill-badge badge-grp-c">Group C</span>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-top: 6px;">
                            <?php echo number_format($group_counts['Group C'] ?? 0); ?> <small style="font-size: 0.8rem; color: #64748b;">students</small>
                        </div>
                        <small style="color: #64748b; font-weight: 600; font-size: 0.75rem;">Sainik & RMS</small>
                    </div>
                    <i class="fas fa-shield-alt" style="font-size: 1.8rem; color: #7c3aed; opacity: 0.4;"></i>
                </a>

                <a href="assign_groups.php?group=Group D" style="text-decoration: none;" class="group-stat-card">
                    <div>
                        <span class="group-pill-badge badge-grp-d">Group D</span>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-top: 6px;">
                            <?php echo number_format($group_counts['Group D'] ?? 0); ?> <small style="font-size: 0.8rem; color: #64748b;">students</small>
                        </div>
                        <small style="color: #64748b; font-weight: 600; font-size: 0.75rem;">Netarhat & Simultala</small>
                    </div>
                    <i class="fas fa-crown" style="font-size: 1.8rem; color: #d97706; opacity: 0.4;"></i>
                </a>
            </div>

            <!-- Filter Console & Bulk Action Bar -->
            <form action="" method="POST" id="bulkForm">
                <input type="hidden" name="assign_bulk_groups" value="1">

                <div class="filter-bar-card">
                    <!-- Search Input & Tabs -->
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; flex: 1;">
                        <div style="position: relative; min-width: 260px;">
                            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                            <input type="text" id="searchInput" placeholder="Search by student name, reg no..." onkeyup="filterTable()" style="width: 100%; padding: 10px 14px 10px 38px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.9rem; outline: none;">
                        </div>

                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <a href="assign_groups.php?group=all" class="btn" style="padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; text-decoration: none; <?php echo $filter_grp === 'all' ? 'background: #0f172a; color: #ffffff;' : 'background: #f1f5f9; color: #475569;'; ?>">
                                All Students (<?php echo count($all_students); ?>)
                            </a>
                            <a href="assign_groups.php?group=Group A" class="btn" style="padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; text-decoration: none; <?php echo $filter_grp === 'Group A' ? 'background: #2563eb; color: #ffffff;' : 'background: #eff6ff; color: #2563eb;'; ?>">
                                Group A
                            </a>
                            <a href="assign_groups.php?group=Group B" class="btn" style="padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; text-decoration: none; <?php echo $filter_grp === 'Group B' ? 'background: #059669; color: #ffffff;' : 'background: #ecfdf5; color: #059669;'; ?>">
                                Group B
                            </a>
                            <a href="assign_groups.php?group=Group C" class="btn" style="padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; text-decoration: none; <?php echo $filter_grp === 'Group C' ? 'background: #7c3aed; color: #ffffff;' : 'background: #f5f3ff; color: #7c3aed;'; ?>">
                                Group C
                            </a>
                            <a href="assign_groups.php?group=Group D" class="btn" style="padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; text-decoration: none; <?php echo $filter_grp === 'Group D' ? 'background: #d97706; color: #ffffff;' : 'background: #fffbeb; color: #d97706;'; ?>">
                                Group D
                            </a>
                        </div>
                    </div>

                    <!-- Bulk Action Select -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <select name="bulk_academic_group" style="padding: 9px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 0.88rem;">
                            <option value="">Bulk Assign To...</option>
                            <option value="Group A">Group A (Primary)</option>
                            <option value="Group B">Group B (Middle)</option>
                            <option value="Group C">Group C (Sainik & RMS)</option>
                            <option value="Group D">Group D (Netarhat)</option>
                        </select>
                        <button type="submit" onclick="return confirm('Apply bulk group assignment to checked students?')" style="background: #0f172a; color: #ffffff; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 800; font-size: 0.88rem; cursor: pointer;">
                            Apply Bulk
                        </button>
                    </div>
                </div>

                <!-- Students Table -->
                <div style="overflow-x: auto;">
                    <table class="student-assign-table" id="studentsAssignTable">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">
                                    <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                                </th>
                                <th>Candidate Info</th>
                                <th>Class & Target School</th>
                                <th>Scholar Mode</th>
                                <th>Assigned Academic Group</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $displayed_students = array_filter($all_students, function($st) use ($filter_grp) {
                                if ($filter_grp === 'all') return true;
                                return ($st['academic_group'] ?? 'Group A') === $filter_grp;
                            });

                            if (!empty($displayed_students)):
                                foreach ($displayed_students as $st):
                                    $grp_val = $st['academic_group'] ?? 'Group A';
                                    $grp_badge_class = 'badge-grp-a';
                                    if ($grp_val === 'Group B') $grp_badge_class = 'badge-grp-b';
                                    elseif ($grp_val === 'Group C') $grp_badge_class = 'badge-grp-c';
                                    elseif ($grp_val === 'Group D') $grp_badge_class = 'badge-grp-d';
                            ?>
                                <tr class="student-row-item" data-name="<?php echo strtolower(htmlspecialchars($st['name'])); ?>" data-reg="<?php echo strtolower(htmlspecialchars($st['reg_no'] ?? '')); ?>">
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="student_ids[]" value="<?php echo $st['id']; ?>" class="st-checkbox" style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                                    </td>

                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if (!empty($st['student_photo'])): ?>
                                                <img src="../<?php echo htmlspecialchars($st['student_photo']); ?>" alt="" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid #cbd5e1;">
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; font-weight: 900; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                                    <?php echo strtoupper(substr($st['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong style="color: #0f172a; font-size: 0.95rem; display: block;"><?php echo htmlspecialchars($st['name']); ?></strong>
                                                <small style="color: #64748b; font-weight: 700; font-size: 0.76rem;"><?php echo htmlspecialchars($st['reg_no'] ?: 'No Reg No'); ?></small>
                                            </div>
                                        </div>
                                    </td>

                                    <td style="color: #334155; font-weight: 700; font-size: 0.88rem;">
                                        <div><?php echo htmlspecialchars($st['class_admitted'] ?: '—'); ?></div>
                                        <small style="color: #64748b; font-weight: 600; font-size: 0.76rem;"><?php echo htmlspecialchars($st['target_school'] ?: 'Competitive Entrance'); ?></small>
                                    </td>

                                    <td>
                                        <span style="background: #f1f5f9; color: #475569; font-size: 0.76rem; font-weight: 800; padding: 4px 10px; border-radius: 50px;">
                                            <?php echo htmlspecialchars($st['scholar_mode'] ?: 'Day Scholar'); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="group-pill-badge <?php echo $grp_badge_class; ?>">
                                            <i class="fas fa-book-open" style="font-size: 0.65rem; margin-right: 3px;"></i>
                                            <?php echo htmlspecialchars($grp_val); ?>
                                        </span>
                                    </td>

                                    <td style="text-align: right;">
                                        <form action="" method="POST" style="display: inline-block;">
                                            <input type="hidden" name="assign_single_group" value="1">
                                            <input type="hidden" name="student_id" value="<?php echo $st['id']; ?>">
                                            <select name="academic_group" onchange="this.form.submit()" class="group-inline-select">
                                                <option value="Group A" <?php echo $grp_val === 'Group A' ? 'selected' : ''; ?>>Group A (Primary)</option>
                                                <option value="Group B" <?php echo $grp_val === 'Group B' ? 'selected' : ''; ?>>Group B (Middle)</option>
                                                <option value="Group C" <?php echo $grp_val === 'Group C' ? 'selected' : ''; ?>>Group C (Sainik)</option>
                                                <option value="Group D" <?php echo $grp_val === 'Group D' ? 'selected' : ''; ?>>Group D (Netarhat)</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 600;">
                                        No students found in this filter group.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

        </div>
    </main>

    <script>
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.st-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
        }

        function filterTable() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.student-row-item');

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const reg = row.getAttribute('data-reg') || '';
                if (query === '' || name.includes(query) || reg.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
