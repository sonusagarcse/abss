<?php
// counselor/attendance.php - Student Attendance Viewer
require_once 'includes/auth.php';
require_counselor_module('attendance');

$selected_date = $_GET['date'] ?? date('Y-m-d');

$sql = "SELECT s.id, s.name, s.reg_no, s.target_school, a.status as attendance_status 
        FROM students s 
        LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ? 
        WHERE s.status = 'active' 
        ORDER BY s.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$records = $stmt->get_result();

$present_cnt = 0;
$absent_cnt = 0;
$late_cnt = 0;
$total_cnt = 0;
$rows = [];

while ($r = $records->fetch_assoc()) {
    $rows[] = $r;
    $total_cnt++;
    $st = strtolower($r['attendance_status'] ?? '');
    if ($st === 'present') $present_cnt++;
    elseif ($st === 'absent') $absent_cnt++;
    elseif ($st === 'late') $late_cnt++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .stats-ribbon { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat-card { background: #ffffff; border-radius: 16px; padding: 18px 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 14px; }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-info h3 { margin: 0; font-size: 1.4rem; font-weight: 800; color: #0f172a; }
        .stat-info p { margin: 2px 0 0 0; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .form-input { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.88rem; font-family: inherit; background: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-calendar-check" style="color: #0d9488;"></i> Student Attendance Directory
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Review daily presence records for counseling student engagement and punctuality.
                    </p>
                </div>
            </div>

            <div class="stats-ribbon">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_cnt; ?></h3>
                        <p>Total Active</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dcfce7; color: #166534;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $present_cnt; ?></h3>
                        <p>Present Today</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fee2e2; color: #991b1b;">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $absent_cnt; ?></h3>
                        <p>Absent</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $late_cnt; ?></h3>
                        <p>Late</p>
                    </div>
                </div>
            </div>

            <div class="portal-card">
                <form method="GET" action="attendance.php" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
                    <label style="font-weight: 700; color: #475569; font-size: 0.88rem;">Select Date:</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" class="form-input">
                    <button type="submit" class="btn-portal btn-teal">
                        <i class="fas fa-filter"></i> Load Attendance
                    </button>
                </form>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Roll / Reg No</th>
                                <th>Program / School</th>
                                <th>Date</th>
                                <th>Status on Record</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        No student attendance records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td style="font-weight: 800; color: #0f172a;">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </td>
                                        <td>
                                            <span style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($row['reg_no'] ?: '#' . $row['id']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['target_school'] ?: 'General'); ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo date('d M Y', strtotime($selected_date)); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $st = strtolower($row['attendance_status'] ?? 'unmarked');
                                            if ($st === 'present'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Present</span>
                                            <?php elseif ($st === 'absent'): ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Absent</span>
                                            <?php elseif ($st === 'late'): ?>
                                                <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Late</span>
                                            <?php else: ?>
                                                <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 50px; font-weight: 700; font-size: 0.75rem;">Not Marked</span>
                                            <?php endif; ?>
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
</body>
</html>
