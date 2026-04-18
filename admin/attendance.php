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
    $msg = "Attendance for $date has been saved.";
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
    <title>Attendance | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .date-picker-wrap { margin-bottom: 30px; display: flex; align-items: flex-end; gap: 20px; }
        .attendance-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .attendance-row td { padding: 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; }
        .attendance-row td:first-child { border-left: 1px solid #f0f4f8; border-radius: 25px 0 0 25px; }
        .attendance-row td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 25px 25px 0; }
        .student-meta h4 { color: var(--portal-blue); font-weight: 800; font-size: 1.1rem; margin-bottom: 4px; }
        .status-group { display: flex; gap: 10px; }
        .status-btn { padding: 10px 20px; border-radius: 12px; border: 2px solid #f0f4f8; background: #fff; font-weight: 800; cursor: pointer; transition: 0.2s; font-size: 0.8rem; color: #9aa5ce; }
        .status-radio { display: none; }
        .status-radio:checked + .status-btn-present { background: #f0fdf4; border-color: #22c55e; color: #166534; }
        .status-radio:checked + .status-btn-absent { background: #feeef2; border-color: #d32f2f; color: #991b1b; }
        .status-radio:checked + .status-btn-late { background: #fff7ed; border-color: #f97316; color: #9a3412; }
        .floating-footer { position: fixed; bottom: 40px; right: 40px; z-index: 200; display: flex; gap: 20px; }
        .save-btn { background: var(--portal-blue); border: none; color: #fff; padding: 18px 45px; border-radius: 100px; font-weight: 800; font-size: 1.1rem; cursor: pointer; box-shadow: 0 15px 35px rgba(13,71,161,0.3); transition: 0.3s; font-family: 'Outfit'; }
        .mark-present-btn { background: #fff; border: 2px solid var(--portal-blue); color: var(--portal-blue); padding: 18px 35px; border-radius: 100px; font-weight: 800; font-size: 1rem; cursor: pointer; transition: 0.3s; font-family: 'Outfit'; }
        .mark-present-btn:hover { background: #eef2ff; }
        
        #studentSearch { background: #fff; padding: 15px 25px; border-radius: 16px; border: 2px solid #f0f4f8; font-family: 'Outfit'; font-weight: 600; width: 300px; outline: none; transition: 0.3s; }
        #studentSearch:focus { border-color: var(--portal-blue); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Student Attendance</h1>
            <p>Track daily presence and punctuality.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="date-picker-wrap">
            <form action="" method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
                <div class="portal-input-group" style="margin-bottom:0;">
                    <label>Select Date</label>
                    <input type="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()" style="max-width:300px;">
                </div>
            </form>
            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <input type="text" id="studentSearch" placeholder="Search students..." onkeyup="filterStudents()">
            </div>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
            <table class="attendance-table">
                <?php while($row = $students_query->fetch_assoc()): ?>
                <tr class="attendance-row">
                    <td>
                        <div class="student-meta">
                            <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                            <p style="margin:0; font-size:0.8rem; font-weight:600; opacity:0.6;"><?php echo htmlspecialchars($row['target_school']); ?></p>
                        </div>
                    </td>
                    <td align="right">
                        <div class="status-group">
                            <label>
                                <input type="radio" name="status[<?php echo $row['id']; ?>]" value="present" class="status-radio" <?php echo ($row['status'] == 'present' || empty($row['status'])) ? 'checked' : ''; ?>>
                                <span class="status-btn status-btn-present">PRESENT</span>
                            </label>
                            <label>
                                <input type="radio" name="status[<?php echo $row['id']; ?>]" value="absent" class="status-radio" <?php echo ($row['status'] == 'absent') ? 'checked' : ''; ?>>
                                <span class="status-btn status-btn-absent">ABSENT</span>
                            </label>
                            <label>
                                <input type="radio" name="status[<?php echo $row['id']; ?>]" value="late" class="status-radio" <?php echo ($row['status'] == 'late') ? 'checked' : ''; ?>>
                                <span class="status-btn status-btn-late">LATE</span>
                            </label>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>

            <div class="floating-footer">
                <button type="button" class="mark-present-btn" onclick="markAllPresent()">
                    <i class="fas fa-check-double"></i> Mark All Present
                </button>
                <button type="submit" name="save_attendance" class="save-btn">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
            </div>
        </form>
    </main>

    <script>
        function markAllPresent() {
            const radios = document.querySelectorAll('input[value="present"]');
            radios.forEach(r => r.checked = true);
        }

        function filterStudents() {
            const input = document.getElementById('studentSearch');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.attendance-row');

            rows.forEach(row => {
                const name = row.querySelector('h4').textContent.toLowerCase();
                if (name.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
