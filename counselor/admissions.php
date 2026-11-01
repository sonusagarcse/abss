<?php
// counselor/admissions.php - Online Admission Applications & Candidate Enrolment
require_once 'includes/auth.php';
require_counselor_module('admissions');

$msg = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && isset($_POST['admission_id'])) {
    $id = (int)$_POST['admission_id'];
    $status = trim($_POST['status'] ?? '');
    
    $stmt = $conn->prepare("UPDATE admissions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        $msg = "Application status updated successfully.";
        
        // If status is Admitted, migrate candidate to students directory
        if ($status === 'Admitted') {
            $res = $conn->query("SELECT * FROM admissions WHERE id = $id");
            if ($row = $res->fetch_assoc()) {
                $name = mb_strtoupper(trim($row['student_name']), 'UTF-8');
                $parent = $row['parent_name'] ?? '';
                $phone = $row['phone'] ?? '';
                $school = $row['target_program'] ?? '';
                $today = date('Y-m-d');
                
                // Check if already in students
                $check = $conn->prepare("SELECT id FROM students WHERE name = ? AND phone = ?");
                $check->bind_param("ss", $name, $phone);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                
                if (!$exists) {
                    $insert = $conn->prepare("INSERT INTO students (name, parent_name, phone, target_school, class_admitted, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                    $insert->bind_param("ssssss", $name, $parent, $phone, $school, $school, $today);
                    $insert->execute();
                    $msg .= " Candidate successfully enrolled into the student directory!";
                    
                    if (function_exists('log_activity')) {
                        log_activity('counselor_admit_student', "Counselor enrolled candidate: $name ($school)");
                    }
                }
            }
        }
    }
}

// Fetch admissions
$status_filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM admissions";
if ($status_filter !== 'all') {
    $clean_status = $conn->real_escape_string($status_filter);
    $query .= " WHERE status = '$clean_status'";
}
$query .= " ORDER BY created_at DESC";
$admissions = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Admissions | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .custom-table tr:hover { background: #f8fafc; }
        .status-badge { padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-admitted { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-interview { background: #e0f2fe; color: #0369a1; }
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 50px; font-size: 0.82rem; font-weight: 700; text-decoration: none; color: #64748b; background: #f1f5f9; transition: all 0.2s ease; }
        .filter-tab:hover { background: #e2e8f0; color: #0f172a; }
        .filter-tab.active { background: #059669; color: #ffffff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-user-plus" style="color: #059669;"></i> Online Admission Applications
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Review student registration forms, schedule counseling interviews, and enroll students.
                    </p>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="portal-card">
                <div class="filter-tabs">
                    <a href="admissions.php?filter=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Applications</a>
                    <a href="admissions.php?filter=Pending" class="filter-tab <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="admissions.php?filter=Interview Scheduled" class="filter-tab <?php echo $status_filter === 'Interview Scheduled' ? 'active' : ''; ?>">Interview Scheduled</a>
                    <a href="admissions.php?filter=Admitted" class="filter-tab <?php echo $status_filter === 'Admitted' ? 'active' : ''; ?>">Admitted / Enrolled</a>
                    <a href="admissions.php?filter=Rejected" class="filter-tab <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Parent &amp; Phone</th>
                                <th>Target Coaching Program</th>
                                <th>Application Date</th>
                                <th>Current Status</th>
                                <th style="text-align: right;">Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$admissions || $admissions->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                                        No admission applications found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($adm = $admissions->fetch_assoc()): ?>
                                    <?php 
                                    $st = $adm['status'] ?: 'Pending';
                                    $clean_phone = preg_replace('/[^0-9]/', '', $adm['phone']);
                                    if (strlen($clean_phone) == 10) $clean_phone = '91' . $clean_phone;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a; font-size: 0.92rem;">
                                                <?php echo htmlspecialchars($adm['student_name']); ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                DOB: <?php echo htmlspecialchars($adm['dob'] ?: 'N/A'); ?> • <?php echo htmlspecialchars($adm['gender'] ?: 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #334155;">
                                                <?php echo htmlspecialchars($adm['parent_name']); ?>
                                            </div>
                                            <div style="margin-top: 3px; display: flex; gap: 8px;">
                                                <a href="tel:<?php echo htmlspecialchars($adm['phone']); ?>" style="color: #059669; font-weight: 700; text-decoration: none; font-size: 0.8rem;">
                                                    <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($adm['phone']); ?>
                                                </a>
                                                <?php if (!empty($clean_phone)): ?>
                                                    <a href="https://wa.me/<?php echo $clean_phone; ?>" target="_blank" style="color: #16a34a; font-weight: 700; text-decoration: none; font-size: 0.8rem;">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: #f0fdf4; color: #059669; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($adm['target_program'] ?: 'General Program'); ?>
                                            </span>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo date('d M Y', strtotime($adm['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = 'status-pending';
                                            if ($st === 'Admitted') $badge_class = 'status-admitted';
                                            elseif ($st === 'Rejected') $badge_class = 'status-rejected';
                                            elseif ($st === 'Interview Scheduled') $badge_class = 'status-interview';
                                            ?>
                                            <span class="status-badge <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($st); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <form method="POST" action="admissions.php" style="display: inline-flex; gap: 6px;">
                                                <input type="hidden" name="admission_id" value="<?php echo $adm['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <select name="status" style="padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.8rem; font-family: inherit;">
                                                    <option value="Pending" <?php echo $st === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Interview Scheduled" <?php echo $st === 'Interview Scheduled' ? 'selected' : ''; ?>>Interview Scheduled</option>
                                                    <option value="Admitted" <?php echo $st === 'Admitted' ? 'selected' : ''; ?>>Admitted (Enroll)</option>
                                                    <option value="Rejected" <?php echo $st === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                                <button type="submit" class="btn-portal btn-secondary" style="padding: 5px 10px; font-size: 0.78rem;">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>

