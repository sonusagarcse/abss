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
    $msg = "Attendance for $date has been recorded successfully.";
}

$students_query = $conn->query("
    SELECT s.id, s.name, s.target_school, a.status 
    FROM students s 
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = '$selected_date'
    WHERE s.status = 'active'
    ORDER BY s.name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .page-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #ede9fe;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            margin-top: 20px;
        }
        .form-control {
            padding: 10px 16px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
        }
        .form-control:focus { border-color: var(--teacher-purple); }

        .btn-purple {
            background: var(--teacher-purple);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-purple:hover { background: var(--teacher-dark); }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #ede9fe; }
        th { background: #f5f3ff; color: var(--teacher-dark); font-weight: 700; text-transform: uppercase; font-size: 0.8rem; }

        .status-group { display: flex; gap: 8px; }
        .status-btn { padding: 8px 16px; border-radius: 8px; border: 2px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 0.8rem; color: #64748b; }
        .status-radio { display: none; }
        .status-radio:checked + .btn-present { background: #dcfce7; border-color: #22c55e; color: #166534; }
        .status-radio:checked + .btn-absent { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
        .status-radio:checked + .btn-late { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="font-size: 1.8rem; color: var(--teacher-dark); font-weight: 800;">Student Attendance</h1>
                <p style="color: #64748b; margin-top: 4px;">Record and manage daily classroom attendance.</p>
            </div>
        </header>

        <?php if ($msg): ?>
            <div style="background:#dcfce7; color:#166534; padding:15px 25px; border-radius:14px; margin-bottom:20px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="page-card">
            <form method="GET" action="attendance.php" style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <label for="date" style="font-weight: 700; color: #475569;">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
            </form>

            <form method="POST" action="attendance.php">
                <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">
                
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Target School</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students_query && $students_query->num_rows > 0): ?>
                            <?php while ($row = $students_query->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td style="font-weight: 700; color: #1e1b4b;"><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['target_school'] ?? 'General') ?></td>
                                    <td>
                                        <div class="status-group">
                                            <label>
                                                <input type="radio" class="status-radio" name="status[<?= $row['id'] ?>]" value="present" <?= ($row['status'] == 'present' || empty($row['status'])) ? 'checked' : '' ?>>
                                                <span class="status-btn btn-present">Present</span>
                                            </label>
                                            <label>
                                                <input type="radio" class="status-radio" name="status[<?= $row['id'] ?>]" value="absent" <?= ($row['status'] == 'absent') ? 'checked' : '' ?>>
                                                <span class="status-btn btn-absent">Absent</span>
                                            </label>
                                            <label>
                                                <input type="radio" class="status-radio" name="status[<?= $row['id'] ?>]" value="late" <?= ($row['status'] == 'late') ? 'checked' : '' ?>>
                                                <span class="status-btn btn-late">Late</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No active students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="margin-top: 25px; text-align: right;">
                    <button type="submit" name="save_attendance" class="btn-purple"><i class="fas fa-save"></i> Save Attendance &rarr;</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
