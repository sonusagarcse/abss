<?php
// counselor/students.php - Student Directory & Profiles Lookup
require_once 'includes/auth.php';
require_counselor_module('students');

$search = trim($_GET['search'] ?? '');
$school_filter = trim($_GET['school'] ?? '');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR reg_no LIKE ? OR phone LIKE ? OR parent_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($school_filter)) {
    $sql .= " AND target_school = ?";
    $params[] = $school_filter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

// Distinct programs for filter dropdown
$schools_res = $conn->query("SELECT DISTINCT target_school FROM students WHERE target_school IS NOT NULL AND target_school != ''");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Directory | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .custom-table tr:hover { background: #f8fafc; }
        .search-box { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .form-input { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.88rem; font-family: inherit; background: #fff; }
        .form-input:focus { outline: none; border-color: #0d9488; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-user-graduate" style="color: #7c3aed;"></i> Enrolled Student Directory
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Student database lookup for academic counseling and parent consultations.
                    </p>
                </div>
            </div>

            <div class="portal-card">
                <form method="GET" action="students.php" class="search-box">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student name, reg no, parent or phone..." class="form-input" style="flex: 1; min-width: 250px;">
                    
                    <select name="school" class="form-input">
                        <option value="">All Programs / Schools</option>
                        <?php if ($schools_res): ?>
                            <?php while ($s = $schools_res->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($s['target_school']); ?>" <?php echo $school_filter === $s['target_school'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['target_school']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>

                    <button type="submit" class="btn-portal btn-teal">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search) || !empty($school_filter)): ?>
                        <a href="students.php" class="btn-portal btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Reg. No / ID</th>
                                <th>Parent Information</th>
                                <th>Target School / Class</th>
                                <th>Admission Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$students || $students->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-user-slash" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                                        No student records found matching your query.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($st = $students->fetch_assoc()): ?>
                                    <?php 
                                    $clean_phone = preg_replace('/[^0-9]/', '', $st['phone']);
                                    if (strlen($clean_phone) == 10) $clean_phone = '91' . $clean_phone;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a; font-size: 0.92rem;">
                                                <?php echo htmlspecialchars($st['name']); ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                Gender: <?php echo htmlspecialchars($st['gender'] ?: 'N/A'); ?> • DOB: <?php echo htmlspecialchars($st['dob'] ?: 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; color: #334155;">
                                                <?php echo htmlspecialchars($st['reg_no'] ?: '#' . $st['id']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #334155;">
                                                <?php echo htmlspecialchars($st['parent_name']); ?>
                                            </div>
                                            <div style="display: flex; gap: 8px; margin-top: 3px;">
                                                <a href="tel:<?php echo htmlspecialchars($st['phone']); ?>" style="color: #059669; text-decoration: none; font-weight: 700; font-size: 0.8rem;">
                                                    <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($st['phone']); ?>
                                                </a>
                                                <?php if (!empty($clean_phone)): ?>
                                                    <a href="https://wa.me/<?php echo $clean_phone; ?>" target="_blank" style="color: #16a34a; font-size: 0.8rem;">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: #f5f3ff; color: #7c3aed; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($st['target_school'] ?: $st['class_admitted'] ?: 'General'); ?>
                                            </span>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.82rem;">
                                            <?php echo !empty($st['admission_date']) ? date('d M Y', strtotime($st['admission_date'])) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <?php if ($st['status'] === 'active'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
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

