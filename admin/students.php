<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Deactivate / Reactivate Student Action
if (isset($_GET['toggle_status'])) {
    $toggle_id = (int)$_GET['toggle_status'];
    $target_status = $_GET['status'] ?? 'inactive';
    if ($toggle_id > 0 && in_array($target_status, ['active', 'inactive'])) {
        $stmt = $conn->prepare("UPDATE students SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $target_status, $toggle_id);
        if ($stmt->execute()) {
            $action_label = ($target_status === 'inactive') ? 'deactivated' : 'reactivated';
            $msg = "Candidate status has been successfully $action_label.";
            if (function_exists('log_activity')) {
                log_activity('student_status_changed', "Changed student #$toggle_id status to $target_status");
            }
        }
    }
}

// Ensure upload directory exists
$upload_dir = __DIR__ . '/../uploads/students/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Add/Edit Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_student'])) {
    $name               = trim($_POST['name']);
    $dob                = trim($_POST['dob'] ?? '');
    $gender             = trim($_POST['gender'] ?? '');
    $home_address       = trim($_POST['home_address'] ?? '');
    $city               = trim($_POST['city'] ?? '');
    $state              = trim($_POST['state'] ?? '');
    $zip_code           = trim($_POST['zip_code'] ?? '');
    $prev_school        = trim($_POST['prev_school'] ?? '');

    $parent_name        = trim($_POST['parent_name']);
    $guardian_relationship = trim($_POST['guardian_relationship'] ?? '');
    $phone              = trim($_POST['phone']);
    $guardian_email     = trim($_POST['guardian_email'] ?? '');
    $guardian_address   = trim($_POST['guardian_address'] ?? '');

    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_relationship = trim($_POST['emergency_relationship'] ?? '');
    $emergency_phone    = trim($_POST['emergency_phone'] ?? '');

    $has_allergies      = isset($_POST['has_allergies']) ? 1 : 0;
    $allergies_detail   = trim($_POST['allergies_detail'] ?? '');
    $has_medical_condition = isset($_POST['has_medical_condition']) ? 1 : 0;
    $medical_condition_detail = trim($_POST['medical_condition_detail'] ?? '');
    $physician_name     = trim($_POST['physician_name'] ?? '');
    $physician_phone    = trim($_POST['physician_phone'] ?? '');
    $insurance_provider = trim($_POST['insurance_provider'] ?? '');
    $insurance_policy   = trim($_POST['insurance_policy'] ?? '');

    $target_school      = trim($_POST['target_school']);
    $class_admitted     = trim($_POST['class_admitted']);
    $scholar_mode       = trim($_POST['scholar_mode']);
    $academic_group     = trim($_POST['academic_group'] ?? 'Group A');
    $admission_date     = trim($_POST['admission_date']);
    $monthly_discount   = isset($_POST['monthly_discount']) ? (float)$_POST['monthly_discount'] : 0.00;
    $base_fee           = isset($_POST['base_fee']) ? (float)$_POST['base_fee'] : 0.00;
    $security_amount    = isset($_POST['security_amount']) ? (float)$_POST['security_amount'] : 0.00;
    $registration_fee   = isset($_POST['registration_fee']) ? (float)$_POST['registration_fee'] : 0.00;
    $admission_fee      = isset($_POST['admission_fee']) ? (float)$_POST['admission_fee'] : 0.00;
    $advance_amount     = isset($_POST['advance_amount']) ? (float)$_POST['advance_amount'] : 0.00;
    $id                 = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $parent_id          = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

    // Auto-create parent registry account if no parent_id is linked
    if (!$parent_id && !empty($parent_name)) {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        $p_email = !empty($guardian_email) ? $guardian_email : ($clean_phone ? "parent_{$clean_phone}@abss.in" : "parent_" . time() . "@abss.in");
        
        // Check if a parent with this email or phone already exists
        $p_check = $conn->prepare("SELECT id FROM parents WHERE email = ? OR (phone = ? AND phone != '') LIMIT 1");
        $p_check->bind_param("ss", $p_email, $phone);
        $p_check->execute();
        $p_res = $p_check->get_result();
        
        if ($p_res->num_rows > 0) {
            $parent_id = $p_res->fetch_assoc()['id'];
            // Ensure phone is attached to parent account
            $u_stmt = $conn->prepare("UPDATE parents SET phone = ? WHERE id = ? AND (phone IS NULL OR phone = '')");
            $u_stmt->bind_param("si", $phone, $parent_id);
            $u_stmt->execute();
        } else {
            // Create a new parent account: BOTH ID & PASSWORD are the Mobile Number (phone)
            $default_password = !empty($phone) ? $phone : '123456';
            $p_pass = password_hash($default_password, PASSWORD_DEFAULT);
            $p_insert = $conn->prepare("INSERT INTO parents (parent_name, email, password, phone) VALUES (?, ?, ?, ?)");
            $p_insert->bind_param("ssss", $parent_name, $p_email, $p_pass, $phone);
            if ($p_insert->execute()) {
                $parent_id = $conn->insert_id;
                if (function_exists('log_activity')) {
                    log_activity('parent_auto_created', "Auto-provisioned parent portal login for $parent_name (Mobile ID/Pass: $phone)");
                }
            }
        }
    }

    // Handle admission form scan upload (field: photo)
    $photo_path = $_POST['existing_photo'] ?? '';
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] < 5 * 1024 * 1024) {
            $tmp_name = 'adm_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $tmp_name);
            $photo_path = 'uploads/students/' . $tmp_name;
        }
    }

    // Handle admission test paper scan upload (field: admission_test_paper)
    $admission_test_paper_path = $_POST['existing_admission_test_paper'] ?? '';
    if (!empty($_FILES['admission_test_paper']['name'])) {
        $tp_ext = strtolower(pathinfo($_FILES['admission_test_paper']['name'], PATHINFO_EXTENSION));
        $tp_allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];
        if (in_array($tp_ext, $tp_allowed) && $_FILES['admission_test_paper']['size'] < 5 * 1024 * 1024) {
            $tp_tmp = 'test_' . time() . '_' . rand(1000, 9999) . '.' . $tp_ext;
            move_uploaded_file($_FILES['admission_test_paper']['tmp_name'], $upload_dir . $tp_tmp);
            $admission_test_paper_path = 'uploads/students/' . $tp_tmp;
        }
    }

    // Handle student picture upload (field: student_photo)
    $student_photo_path = $_POST['existing_student_photo'] ?? '';
    if (!empty($_FILES['student_photo']['name'])) {
        $sp_ext = strtolower(pathinfo($_FILES['student_photo']['name'], PATHINFO_EXTENSION));
        $sp_allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($sp_ext, $sp_allowed) && $_FILES['student_photo']['size'] < 3 * 1024 * 1024) {
            $sp_tmp = 'pic_' . time() . '_' . rand(1000, 9999) . '.' . $sp_ext;
            move_uploaded_file($_FILES['student_photo']['tmp_name'], $upload_dir . $sp_tmp);
            $student_photo_path = 'uploads/students/' . $sp_tmp;
        }
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE students SET name=?, dob=?, gender=?, home_address=?, city=?, state=?, zip_code=?, prev_school=?,
            parent_name=?, guardian_relationship=?, phone=?, guardian_email=?, guardian_address=?,
            emergency_contact_name=?, emergency_relationship=?, emergency_phone=?,
            has_allergies=?, allergies_detail=?, has_medical_condition=?, medical_condition_detail=?,
            physician_name=?, physician_phone=?, insurance_provider=?, insurance_policy=?,
            target_school=?, class_admitted=?, scholar_mode=?, academic_group=?, monthly_discount=?, base_fee=?, security_amount=?, registration_fee=?, admission_fee=?, advance_amount=?, admission_date=?, parent_id=?, photo=?, admission_test_paper=?, student_photo=?
            WHERE id=?");
        $types = str_repeat('s', 16) . 'isis' . str_repeat('s', 8) . 'ddddddsisssi';
        $params = [
            $name, $dob, $gender, $home_address, $city, $state, $zip_code, $prev_school,
            $parent_name, $guardian_relationship, $phone, $guardian_email, $guardian_address,
            $emergency_contact_name, $emergency_relationship, $emergency_phone,
            $has_allergies, $allergies_detail, $has_medical_condition, $medical_condition_detail,
            $physician_name, $physician_phone, $insurance_provider, $insurance_policy,
            $target_school, $class_admitted, $scholar_mode, $academic_group, $monthly_discount, $base_fee, $security_amount, $registration_fee, $admission_fee, $advance_amount, $admission_date, $parent_id, $photo_path, $admission_test_paper_path, $student_photo_path,
            $id
        ];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO students (name, dob, gender, home_address, city, state, zip_code, prev_school,
            parent_name, guardian_relationship, phone, guardian_email, guardian_address,
            emergency_contact_name, emergency_relationship, emergency_phone,
            has_allergies, allergies_detail, has_medical_condition, medical_condition_detail,
            physician_name, physician_phone, insurance_provider, insurance_policy,
            target_school, class_admitted, scholar_mode, academic_group, monthly_discount, base_fee, security_amount, registration_fee, admission_fee, advance_amount, admission_date, parent_id, photo, admission_test_paper, student_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $types = str_repeat('s', 16) . 'isis' . str_repeat('s', 8) . 'ddddddsisss';
        $params = [
            $name, $dob, $gender, $home_address, $city, $state, $zip_code, $prev_school,
            $parent_name, $guardian_relationship, $phone, $guardian_email, $guardian_address,
            $emergency_contact_name, $emergency_relationship, $emergency_phone,
            $has_allergies, $allergies_detail, $has_medical_condition, $medical_condition_detail,
            $physician_name, $physician_phone, $insurance_provider, $insurance_policy,
            $target_school, $class_admitted, $scholar_mode, $academic_group, $monthly_discount, $base_fee, $security_amount, $registration_fee, $admission_fee, $advance_amount, $admission_date, $parent_id, $photo_path, $admission_test_paper_path, $student_photo_path
        ];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $new_id = $conn->insert_id;
        // Generate Registration Number: ABSS-YEAR-XXXX
        $reg_no = 'ABSS-' . date('Y') . '-' . str_pad($new_id, 4, '0', STR_PAD_LEFT);
        $conn->query("UPDATE students SET reg_no = '$reg_no' WHERE id = $new_id");
        // Rename admission form scan with actual student id
        if (!empty($photo_path)) {
            $ext2 = pathinfo($photo_path, PATHINFO_EXTENSION);
            $new_photo = 'uploads/students/' . $new_id . '_admission.' . $ext2;
            if (file_exists(__DIR__ . '/../' . $photo_path)) {
                rename(__DIR__ . '/../' . $photo_path, __DIR__ . '/../' . $new_photo);
            }
            $conn->query("UPDATE students SET photo = '" . $conn->real_escape_string($new_photo) . "' WHERE id = $new_id");
        }
        // Rename admission test paper scan with actual student id
        if (!empty($admission_test_paper_path)) {
            $tp_ext2 = pathinfo($admission_test_paper_path, PATHINFO_EXTENSION);
            $new_tp = 'uploads/students/' . $new_id . '_testpaper.' . $tp_ext2;
            if (file_exists(__DIR__ . '/../' . $admission_test_paper_path)) {
                rename(__DIR__ . '/../' . $admission_test_paper_path, __DIR__ . '/../' . $new_tp);
            }
            $conn->query("UPDATE students SET admission_test_paper = '" . $conn->real_escape_string($new_tp) . "' WHERE id = $new_id");
        }
        // Rename student picture with actual student id
        if (!empty($student_photo_path)) {
            $sp_ext2 = pathinfo($student_photo_path, PATHINFO_EXTENSION);
            $new_sp = 'uploads/students/' . $new_id . '_pic.' . $sp_ext2;
            if (file_exists(__DIR__ . '/../' . $student_photo_path)) {
                rename(__DIR__ . '/../' . $student_photo_path, __DIR__ . '/../' . $new_sp);
            }
            $conn->query("UPDATE students SET student_photo = '" . $conn->real_escape_string($new_sp) . "' WHERE id = $new_id");
        }

        // Dispatch Student Registration Email to Parent & Admin
        require_once __DIR__ . '/../includes/mail_helper.php';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
        $base_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
        $login_url = "$base_url/parent/login.php";

        $welcome_html = get_base_template(
            "Student Enrollment Confirmation",
            '<div class="greeting">Welcome to ABSS Family!</div>
             <p>Dear <strong>' . htmlspecialchars($parent_name) . '</strong>,</p>
             <p>Student <strong>' . htmlspecialchars($name) . '</strong> has been enrolled successfully in <strong>Awasiya Bal Shikshan Sansthan</strong>.</p>
             <div class="info-card">
                 <table role="presentation" width="100%">
                     <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Registration No</td><td style="padding:8px 0; font-weight:900; color:#2563eb; text-align:right;">' . htmlspecialchars($reg_no) . '</td></tr>
                     <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Student Name</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($name) . '</td></tr>
                     <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Target School</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($target_school) . '</td></tr>
                     <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Class / Batch</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($class_admitted) . '</td></tr>
                     <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Scholar Mode</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($scholar_mode) . '</td></tr>
                 </table>
             </div>
             <div style="background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0; font-size:14px; margin-bottom:20px;">
                 <div><strong>Parent Portal Login:</strong> <a href="' . $login_url . '" style="color:#2563eb;">' . $login_url . '</a></div>
                 <div><strong>Registered Phone / Password:</strong> ' . htmlspecialchars($phone) . '</div>
             </div>
             <a href="' . $login_url . '" style="background:#2563eb; color:#fff; padding:12px 24px; border-radius:50px; text-decoration:none; font-weight:800; display:inline-block;">Access Parent Portal →</a>'
        );

        if (!empty($guardian_email)) {
            send_smtp_email($guardian_email, "Welcome to ABSS - Student Registration (" . $name . ")", $welcome_html);
        }
        send_smtp_email('abssimamganj@gmail.com', "New Student Enrolled - " . $name . " (" . $reg_no . ")", $welcome_html);
    }
    
    header("Location: students.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM students WHERE id = $id");
    header("Location: students.php");
    exit();
}

$students_query = $conn->query("
    SELECT s.*, p.parent_name AS account_parent_name, p.email AS parent_email 
    FROM students s 
    LEFT JOIN parents p ON s.parent_id = p.id 
    ORDER BY s.created_at DESC
");

$students_data = [];
$total_count = 0;
$total_active_count = 0;
$inactive_count = 0;
$day_scholars_count = 0;
$hostlers_count = 0;
$tuition_count = 0;
$unique_classes = [];
$unique_schools = [];

if ($students_query) {
    while($row = $students_query->fetch_assoc()) {
        $students_data[] = $row;
        $total_count++;
        $st_status = $row['status'] ?? 'active';
        if ($st_status === 'inactive') {
            $inactive_count++;
        } else {
            $total_active_count++;
            $mode = $row['scholar_mode'] ?? 'Day Scholar';
            if (strcasecmp($mode, 'Hostler') === 0) $hostlers_count++;
            elseif (strcasecmp($mode, 'Tuition') === 0) $tuition_count++;
            else $day_scholars_count++;

            if (!empty($row['class_admitted'])) $unique_classes[$row['class_admitted']] = true;
            if (!empty($row['target_school'])) $unique_schools[$row['target_school']] = true;
        }
    }
}

$parents_list = $conn->query("SELECT id, parent_name, email FROM parents ORDER BY parent_name ASC");
$parents_array = [];
while($p = $parents_list->fetch_assoc()) {
    $parents_array[] = $p;
}

$settings_list = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$site_settings = [];
if ($settings_list) {
    while($set = $settings_list->fetch_assoc()) {
        $site_settings[$set['setting_key']] = $set['setting_value'];
    }
}
$tuition_modes = [];
if (!empty($site_settings['tuition_modes'])) {
    $tuition_modes = json_decode($site_settings['tuition_modes'], true);
} else {
    $fee_day_scholar = $site_settings['fee_day_scholar'] ?? '3000';
    $fee_hostler = $site_settings['fee_hostler'] ?? '5000';
    $fee_tuition = $site_settings['fee_tuition'] ?? '1500';
    $tuition_modes = ['Day Scholar' => $fee_day_scholar, 'Hostler' => $fee_hostler, 'Tuition' => $fee_tuition];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registry & Profiles | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        /* Modern Action & Stats Header */
        .students-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stats-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        /* Filter Controls Glass Box */
        .search-filter-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            padding: 22px 26px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .filter-controls-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto;
            gap: 14px;
            align-items: center;
        }

        .search-field-wrapper {
            position: relative;
            width: 100%;
        }
        .search-field-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }
        .search-field-wrapper input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border-radius: var(--radius-md);
            border: 2px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            transition: 0.25s;
            box-sizing: border-box;
        }
        .search-field-wrapper input:focus {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .filter-select {
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
            transition: 0.25s;
        }
        .filter-select:focus {
            border-color: var(--portal-blue);
        }

        /* View Mode Switcher */
        .view-mode-toggle {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
        }
        .view-mode-btn {
            background: transparent;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 800;
            color: #64748b;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .view-mode-btn.active {
            background: #ffffff;
            color: var(--portal-blue);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        /* Student Card Grid */
        .students-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 22px;
            margin-bottom: 40px;
        }

        .student-profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            padding: 24px;
            transition: transform 0.25s, box-shadow 0.25s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        .student-profile-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 35px rgba(37, 99, 235, 0.08);
        }

        .student-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 16px;
        }
        .student-avatar {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            object-fit: cover;
            border: 2px solid #e0e7ff;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        .student-avatar-fallback {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .student-name-text {
            font-size: 1.12rem;
            font-weight: 800;
            color: var(--portal-dark);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .reg-badge-pill {
            display: inline-block;
            background: #eff6ff;
            color: var(--portal-blue);
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.76rem;
            font-family: monospace;
            border: 1px solid #dbeafe;
        }

        .scholar-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .scholar-hostler { background: #f3e8ff; color: #7c3aed; }
        .scholar-day { background: #dcfce7; color: #166534; }
        .scholar-tuition { background: #dbeafe; color: #1e40af; }

        .student-details-list {
            background: rgba(248, 250, 252, 0.8);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin: 12px 0 16px;
            border: 1px solid #f1f5f9;
            font-size: 0.86rem;
        }
        .student-detail-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 0;
            color: #475569;
        }
        .student-detail-item b {
            color: var(--portal-dark);
        }

        .student-contact-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        .contact-btn {
            flex: 1;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            transition: 0.2s;
        }
        .contact-btn-wa { background: #dcfce7; color: #15803d; }
        .contact-btn-wa:hover { background: #22c55e; color: #fff; }
        .contact-btn-call { background: #eff6ff; color: #1d4ed8; }
        .contact-btn-call:hover { background: #2563eb; color: #fff; }

        .student-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            margin-top: 5px;
        }

        .action-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-edit-act { background: #eff6ff; color: var(--portal-blue); }
        .btn-edit-act:hover { background: var(--portal-blue); color: #fff; }
        .btn-addon-act { background: #f0fdf4; color: #16a34a; }
        .btn-addon-act:hover { background: #16a34a; color: #fff; }
        .btn-del-act { background: #fef2f2; color: #dc2626; }
        .btn-del-act:hover { background: #dc2626; color: #fff; }

        /* Modal Styles */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.5); 
            backdrop-filter: blur(8px); 
            z-index: 4000; 
            align-items: flex-start; 
            justify-content: center; 
            overflow-y: auto; 
            padding: 30px 15px; 
            box-sizing: border-box;
        }
        .modal-content { 
            background: #ffffff; 
            padding: 42px 48px; 
            border-radius: 28px; 
            width: 100%; 
            max-width: 1120px; 
            box-shadow: 0 40px 100px rgba(15, 23, 42, 0.25); 
            border: 1px solid #e2e8f0; 
            margin: auto; 
            box-sizing: border-box;
        }

        .form-section-title {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #ffffff;
            background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark));
            padding: 8px 18px;
            border-radius: 10px;
            margin: 25px 0 16px;
            display: block;
        }
        .form-section-title:first-of-type { margin-top: 0; }

        .photo-upload-area {
            border: 2px dashed #c7d2fe;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            background: #f8faff;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }
        .photo-upload-area:hover { border-color: var(--portal-blue); background: #eef2ff; }
        .photo-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

        .yes-no-group { display: flex; gap: 15px; align-items: center; margin: 5px 0 12px; }
        .yes-no-group label { display: flex; align-items: center; gap: 6px; font-weight: 700; color: #475569; cursor: pointer; }
        .yes-no-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--portal-blue); }

        .portal-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 900px) {
            .filter-controls-row {
                grid-template-columns: 1fr 1fr;
            }
            .search-field-wrapper {
                grid-column: span 2;
            }
        }

        @media (max-width: 600px) {
            .filter-controls-row {
                grid-template-columns: 1fr;
            }
            .search-field-wrapper {
                grid-column: span 1;
            }
            .portal-form-row {
                grid-template-columns: 1fr !important;
            }
            .modal-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Top Action & Navigation Header -->
        <div class="students-header-row">
            <div>
                <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-graduate" style="color: var(--portal-blue);"></i> Student Registry & Profiles
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Manage academic enrollment records, medical profiles, and fee configurations.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="btn-portal" onclick="showModal()">
                    <i class="fas fa-user-plus"></i> New Enrollment
                </button>
            </div>
        </div>

        <!-- KPI Metrics Grid -->
        <div class="stats-kpi-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_active_count); ?></h3>
                    <span>Active Enrolled</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-sun"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($day_scholars_count); ?></h3>
                    <span>Day Scholars</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-bed"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($hostlers_count); ?></h3>
                    <span>Hostlers (Boarders)</span>
                </div>
            </div>
            <div class="stat-card" onclick="if(document.getElementById('statusFilterSelect')){document.getElementById('statusFilterSelect').value='inactive'; filterStudents();}" style="cursor: pointer;" title="Click to view inactive / deactivated candidates">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;"><i class="fas fa-user-slash"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($inactive_count); ?></h3>
                    <span>Inactive Candidates</span>
                </div>
            </div>
        </div>

        <!-- Interactive Search & Filtering Console -->
        <div class="search-filter-card">
            <div class="filter-controls-row">
                <!-- Search Input -->
                <div class="search-field-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearchInput" placeholder="Search by student name, reg no, parent, phone..." onkeyup="filterStudents()">
                </div>

                <!-- Class Filter -->
                <div>
                    <select id="classFilterSelect" class="filter-select" onchange="filterStudents()">
                        <option value="">-- All Classes --</option>
                        <?php foreach(array_keys($unique_classes) as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mode Filter -->
                <div>
                    <select id="modeFilterSelect" class="filter-select" onchange="filterStudents()">
                        <option value="">-- All Modes --</option>
                        <option value="Day Scholar">Day Scholar</option>
                        <option value="Hostler">Hostler</option>
                        <option value="Tuition">Tuition</option>
                    </select>
                </div>

                <!-- Target School Filter -->
                <div>
                    <select id="schoolFilterSelect" class="filter-select" onchange="filterStudents()">
                        <option value="">-- All Target Schools --</option>
                        <?php foreach(array_keys($unique_schools) as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Academic Group Filter -->
                <div>
                    <select id="groupFilterSelect" class="filter-select" onchange="filterStudents()">
                        <option value="">-- All Syllabus Groups --</option>
                        <option value="Group A">Group A</option>
                        <option value="Group B">Group B</option>
                        <option value="Group C">Group C</option>
                        <option value="Group D">Group D</option>
                    </select>
                </div>

                <!-- Active / Inactive Status Filter -->
                <div>
                    <select id="statusFilterSelect" class="filter-select" onchange="filterStudents()">
                        <option value="active" selected>Active Candidates</option>
                        <option value="inactive">Inactive / Deactivated</option>
                        <option value="">-- All Statuses --</option>
                    </select>
                </div>

                <!-- View Mode Switcher -->
                <div class="view-mode-toggle">
                    <button class="view-mode-btn active" id="btnGridMode" onclick="toggleViewMode('grid')" title="Cards View">
                        <i class="fas fa-th-large"></i> Cards
                    </button>
                    <button class="view-mode-btn" id="btnTableMode" onclick="toggleViewMode('table')" title="Dense Table View">
                        <i class="fas fa-list"></i> Table
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 0.85rem; color: #64748b; font-weight: 700;">
                <div>
                    Showing <span id="visibleCount" style="color: var(--portal-blue); font-weight: 800;"><?php echo $total_count; ?></span> of <?php echo $total_count; ?> registered candidates
                </div>
                <div>
                    <a href="javascript:void(0);" onclick="resetAllFilters()" style="color: var(--portal-blue); text-decoration: none;"><i class="fas fa-undo"></i> Reset Filters</a>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- VIEW 1: MODERN PROFILE CARDS (GRID VIEW)     -->
        <!-- ============================================ -->
        <div id="cardsViewContainer" class="students-cards-grid">
            <?php if (empty($students_data)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-user-graduate" style="font-size: 3rem; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                    <p style="font-size: 1.1rem; font-weight: 700; margin: 0;">No enrolled students registered yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($students_data as $row): 
                    $mode = $row['scholar_mode'] ?? 'Day Scholar';
                    $scholar_class = 'scholar-day';
                    if (strcasecmp($mode, 'Hostler') === 0) $scholar_class = 'scholar-hostler';
                    elseif (strcasecmp($mode, 'Tuition') === 0) $scholar_class = 'scholar-tuition';

                    $initials = '';
                    $parts = explode(' ', trim($row['name']));
                    if (!empty($parts[0])) $initials .= strtoupper(substr($parts[0], 0, 1));
                    if (!empty($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                    if (empty($initials)) $initials = 'S';
                    
                    $phone_digits = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
                ?>
                    <div class="student-profile-card student-item-card"
                         data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
                         data-reg="<?php echo strtolower(htmlspecialchars($row['reg_no'] ?? '')); ?>"
                         data-parent="<?php echo strtolower(htmlspecialchars($row['parent_name'] ?? '')); ?>"
                         data-phone="<?php echo strtolower(htmlspecialchars($row['phone'] ?? '')); ?>"
                         data-email="<?php echo strtolower(htmlspecialchars($row['guardian_email'] ?? $row['parent_email'] ?? '')); ?>"
                         data-class="<?php echo htmlspecialchars($row['class_admitted'] ?? ''); ?>"
                         data-mode="<?php echo htmlspecialchars($row['scholar_mode'] ?? 'Day Scholar'); ?>"
                         data-group="<?php echo htmlspecialchars($row['academic_group'] ?? 'Group A'); ?>"
                         data-status="<?php echo htmlspecialchars($row['status'] ?? 'active'); ?>"
                         data-school="<?php echo htmlspecialchars($row['target_school'] ?? ''); ?>">

                        <div>
                            <div class="student-card-header">
                                <?php if (!empty($row['student_photo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['student_photo']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="student-avatar">
                                <?php else: ?>
                                    <div class="student-avatar-fallback"><?php echo $initials; ?></div>
                                <?php endif; ?>

                                <div style="flex: 1; min-width: 0;">
                                    <div class="student-name-text"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <?php if(!empty($row['reg_no'])): ?>
                                            <span class="reg-badge-pill"><?php echo htmlspecialchars($row['reg_no']); ?></span>
                                        <?php endif; ?>
                                        <span class="scholar-badge <?php echo $scholar_class; ?>">
                                            <i class="fas fa-circle" style="font-size: 0.45rem;"></i> <?php echo htmlspecialchars($mode); ?>
                                        </span>
                                        <span class="scholar-badge" style="background:#eff6ff; color:#2563eb; border:1px solid #dbeafe;">
                                            <i class="fas fa-book-open" style="font-size: 0.55rem;"></i> <?php echo htmlspecialchars($row['academic_group'] ?? 'Group A'); ?>
                                        </span>
                                        <?php if (($row['status'] ?? 'active') === 'inactive'): ?>
                                            <span class="scholar-badge" style="background:#fee2e2; color:#ef4444; border:1px solid #fecaca;">
                                                <i class="fas fa-user-slash" style="font-size: 0.55rem;"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Snapshot -->
                            <div class="student-details-list">
                                <div class="student-detail-item">
                                    <span>Class / Target:</span>
                                    <b><?php echo htmlspecialchars($row['class_admitted'] ?: '—'); ?></b>
                                </div>
                                <div class="student-detail-item">
                                    <span>Target School:</span>
                                    <b><?php echo htmlspecialchars($row['target_school'] ?: 'Standard'); ?></b>
                                </div>
                                <div class="student-detail-item">
                                    <span>Parent / Guardian:</span>
                                    <b><?php echo htmlspecialchars($row['parent_name'] ?: '—'); ?></b>
                                </div>
                                <div class="student-detail-item">
                                    <span>Base Monthly Fee:</span>
                                    <b style="color: var(--portal-blue);">₹<?php echo number_format((float)($row['base_fee'] ?? 0), 2); ?></b>
                                </div>
                            </div>

                            <!-- Quick Contact Actions -->
                            <?php if(!empty($phone_digits)): ?>
                                <div class="student-contact-actions">
                                    <a href="whatsapp.php?student_id=<?php echo $row['id']; ?>" class="contact-btn contact-btn-wa" title="Open WhatsApp Template Messenger">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                    <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" class="contact-btn contact-btn-call">
                                        <i class="fas fa-phone-alt"></i> Call
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer Toolbar -->
                        <div class="student-card-footer">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if(!empty($row['photo'])): ?>
                                    <a href="../<?php echo htmlspecialchars($row['photo']); ?>" target="_blank" class="action-icon-btn btn-addon-act" title="View Offline Admission Form Scan">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($row['admission_test_paper'])): ?>
                                    <a href="../<?php echo htmlspecialchars($row['admission_test_paper']); ?>" target="_blank" class="action-icon-btn" style="background: #f5f3ff; color: #7c3aed;" title="View Admission Test Paper Scan">
                                        <i class="fas fa-file-signature"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="student_addons.php?id=<?php echo $row['id']; ?>" class="action-icon-btn btn-addon-act" title="Manage Addons & Expenses">
                                    <i class="fas fa-plus-circle"></i>
                                </a>
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if (($row['status'] ?? 'active') === 'inactive'): ?>
                                    <a href="?toggle_status=<?php echo $row['id']; ?>&status=active" class="action-icon-btn" style="background:#dcfce7; color:#166534;" onclick="return confirm('Reactivate candidate <?php echo addslashes($row['name']); ?>?')" title="Reactivate Candidate">
                                        <i class="fas fa-user-check"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?toggle_status=<?php echo $row['id']; ?>&status=inactive" class="action-icon-btn" style="background:#fee2e2; color:#ef4444;" onclick="return confirm('Deactivate candidate <?php echo addslashes($row['name']); ?>? They will be hidden from website listings.')" title="Deactivate Candidate (Leave Student)">
                                        <i class="fas fa-user-slash"></i>
                                    </a>
                                <?php endif; ?>
                                <button class="action-icon-btn btn-edit-act" onclick='editStudent(<?php echo json_encode($row); ?>)' title="Edit Profile">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="action-icon-btn btn-del-act" onclick="return confirm('Are you sure you want to delete <?php echo addslashes((string)($row['name'] ?? 'Student')); ?>?')" title="Delete Student">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- VIEW 2: DENSE DATA TABLE (TABLE VIEW)        -->
        <!-- ============================================ -->
        <div id="tableViewContainer" class="portal-card" style="display: none; padding: 20px;">
            <div class="portal-table-container">
                <table id="studentsMainTable" style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                    <thead>
                        <tr>
                            <th style="padding: 10px 16px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Reg No.</th>
                            <th style="padding: 10px 16px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Student Profile</th>
                            <th style="padding: 10px 16px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Parent / Phone</th>
                            <th style="padding: 10px 16px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Class & Mode</th>
                            <th style="padding: 10px 16px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Target School</th>
                            <th style="padding: 10px 16px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students_data as $row): 
                            $mode = $row['scholar_mode'] ?? 'Day Scholar';
                            $scholar_class = 'scholar-day';
                            if (strcasecmp($mode, 'Hostler') === 0) $scholar_class = 'scholar-hostler';
                            elseif (strcasecmp($mode, 'Tuition') === 0) $scholar_class = 'scholar-tuition';

                            $initials = '';
                            $parts = explode(' ', trim($row['name']));
                            if (!empty($parts[0])) $initials .= strtoupper(substr($parts[0], 0, 1));
                            if (!empty($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                            if (empty($initials)) $initials = 'S';
                        ?>
                             <tr class="student-item-row"
                                data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
                                data-reg="<?php echo strtolower(htmlspecialchars($row['reg_no'] ?? '')); ?>"
                                data-parent="<?php echo strtolower(htmlspecialchars($row['parent_name'] ?? '')); ?>"
                                data-phone="<?php echo strtolower(htmlspecialchars($row['phone'] ?? '')); ?>"
                                data-email="<?php echo strtolower(htmlspecialchars($row['guardian_email'] ?? $row['parent_email'] ?? '')); ?>"
                                data-class="<?php echo htmlspecialchars($row['class_admitted'] ?? ''); ?>"
                                data-mode="<?php echo htmlspecialchars($row['scholar_mode'] ?? 'Day Scholar'); ?>"
                                data-group="<?php echo htmlspecialchars($row['academic_group'] ?? 'Group A'); ?>"
                                data-status="<?php echo htmlspecialchars($row['status'] ?? 'active'); ?>"
                                data-school="<?php echo htmlspecialchars($row['target_school'] ?? ''); ?>">

                                <td style="padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; border-radius: 14px 0 0 14px;">
                                    <?php if(!empty($row['reg_no'])): ?>
                                        <span class="reg-badge-pill"><?php echo htmlspecialchars($row['reg_no']); ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if(!empty($row['student_photo'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['student_photo']); ?>" alt="" style="width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 2px solid #e0e7ff; flex-shrink: 0;">
                                        <?php else: ?>
                                            <div style="width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: var(--portal-blue); font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;">
                                                <?php echo $initials; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 800; color: var(--portal-dark);"><?php echo htmlspecialchars($row['name']); ?></div>
                                            <?php if(!empty($row['dob'])): ?>
                                                <small style="color: #64748b; font-weight: 600; font-size: 0.75rem;">DOB: <?php echo date('d M Y', strtotime($row['dob'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td style="padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; color: #475569; font-weight: 600;">
                                    <div><b><?php echo htmlspecialchars($row['parent_name']); ?></b></div>
                                    <small style="color: #64748b; font-weight: 700;"><?php echo htmlspecialchars($row['phone']); ?></small>
                                </td>

                                <td style="padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    <div style="font-weight: 700; color: var(--portal-dark);"><?php echo htmlspecialchars($row['class_admitted']); ?></div>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 3px;">
                                        <span class="scholar-badge <?php echo $scholar_class; ?>">
                                            <?php echo htmlspecialchars($mode); ?>
                                        </span>
                                        <span class="scholar-badge" style="background:#eff6ff; color:#2563eb; border:1px solid #dbeafe;">
                                            <?php echo htmlspecialchars($row['academic_group'] ?? 'Group A'); ?>
                                        </span>
                                    </div>
                                </td>

                                <td style="padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; color: #334155; font-weight: 700;">
                                    <?php echo htmlspecialchars($row['target_school'] ?: 'Standard'); ?>
                                </td>

                                <td style="padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-radius: 0 14px 14px 0; text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <a href="whatsapp.php?student_id=<?php echo $row['id']; ?>" class="action-icon-btn" style="background: #dcfce7; color: #15803d;" title="Send WhatsApp Message">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                        <?php if(!empty($row['photo'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['photo']); ?>" target="_blank" class="action-icon-btn btn-addon-act" title="View Form">
                                                <i class="fas fa-file-image"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if(!empty($row['admission_test_paper'])): ?>
                                            <a href="../<?php echo htmlspecialchars($row['admission_test_paper']); ?>" target="_blank" class="action-icon-btn" style="background: #f5f3ff; color: #7c3aed;" title="View Admission Test Paper">
                                                <i class="fas fa-file-signature"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="student_addons.php?id=<?php echo $row['id']; ?>" class="action-icon-btn btn-addon-act" title="Addons">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <?php if (($row['status'] ?? 'active') === 'inactive'): ?>
                                            <a href="?toggle_status=<?php echo $row['id']; ?>&status=active" class="action-icon-btn" style="background:#dcfce7; color:#166534;" onclick="return confirm('Reactivate candidate <?php echo addslashes($row['name']); ?>?')" title="Reactivate Candidate">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?toggle_status=<?php echo $row['id']; ?>&status=inactive" class="action-icon-btn" style="background:#fee2e2; color:#ef4444;" onclick="return confirm('Deactivate candidate <?php echo addslashes($row['name']); ?>? They will be hidden from website listings.')" title="Deactivate Candidate (Leave Student)">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="action-icon-btn btn-edit-act" onclick='editStudent(<?php echo json_encode($row); ?>)' title="Edit Profile">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-icon-btn btn-del-act" onclick="return confirm('Are you sure you want to delete <?php echo addslashes((string)($row['name'] ?? 'Student')); ?>?')" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- ENROLLMENT & EDIT MODAL                      -->
        <!-- ============================================ -->
        <div class="modal" id="studentModal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h2 style="color: var(--portal-dark); font-weight: 800; font-size: 1.6rem; margin: 0;">Student Registration</h2>
                        <small style="color: #64748b; font-weight: 600;">Complete offline & online admission ledger</small>
                    </div>
                    <button type="button" onclick="hideModal()" style="background: #f1f5f9; border: none; font-size: 1.2rem; cursor: pointer; color: #475569; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">✕</button>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" id="studentForm">
                    <input type="hidden" name="id" id="student_id">
                    <input type="hidden" name="existing_photo" id="existing_photo">
                    <input type="hidden" name="existing_admission_test_paper" id="existing_admission_test_paper">
                    <input type="hidden" name="existing_student_photo" id="existing_student_photo">

                    <!-- SECTION 1: STUDENT INFORMATION -->
                    <span class="form-section-title"><i class="fas fa-user-graduate" style="margin-right: 8px;"></i>1. Student Information</span>

                    <!-- Student Passport Photo Upload -->
                    <div style="display: flex; gap: 25px; align-items: center; margin-bottom: 22px;">
                        <div style="flex-shrink: 0; text-align: center;">
                            <div id="photoPreviewCircle" style="width: 90px; height: 90px; border-radius: 50%; background: #eef2ff; border: 3px dashed #c7d2fe; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto 6px; cursor: pointer; position: relative;">
                                <img id="photoPreviewImg" src="" alt="" style="width: 100%; height: 100%; object-fit: cover; display: none; border-radius: 50%;">
                                <i id="photoPreviewIcon" class="fas fa-camera" style="font-size: 1.6rem; color: #a5b4fc;"></i>
                                <input type="file" name="student_photo" id="student_photo_input" accept="image/*" style="position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;" onchange="previewStudentPhoto(this)">
                            </div>
                            <div style="font-size: 0.72rem; color: #64748b; font-weight: 700;">Student Picture<br><span style="font-size: 0.65rem;">(Max 3MB)</span></div>
                            <div id="current_student_photo_display" style="display: none; margin-top: 4px;">
                                <a href="#" id="view_student_photo_link" target="_blank" style="font-size: 0.75rem; color: var(--portal-blue); font-weight: 700;"><i class="fas fa-eye"></i> View Current</a>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <div class="portal-input-group" style="margin-bottom: 0;">
                                <label>Candidate Full Legal Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="name" id="name" placeholder="Candidate's full name" required>
                            </div>
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" id="dob">
                        </div>
                        <div class="portal-input-group">
                            <label>Gender</label>
                            <select name="gender" id="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Home Street Address</label>
                        <input type="text" name="home_address" id="home_address" placeholder="Street address">
                    </div>

                    <div class="portal-form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="portal-input-group">
                            <label>City</label>
                            <input type="text" name="city" id="city" placeholder="City">
                        </div>
                        <div class="portal-input-group">
                            <label>State</label>
                            <input type="text" name="state" id="state" placeholder="State">
                        </div>
                        <div class="portal-input-group">
                            <label>ZIP / PIN Code</label>
                            <input type="text" name="zip_code" id="zip_code" placeholder="PIN / ZIP">
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Previous School (if any)</label>
                            <input type="text" name="prev_school" id="prev_school" placeholder="Name of previous school">
                        </div>
                        <div class="portal-input-group">
                            <label>Target School / Program</label>
                            <select name="target_school" id="target_school">
                                <option value="">Select Target School</option>
                                <?php 
                                $ts_query = $conn->query("SELECT * FROM schools ORDER BY school_name ASC");
                                if ($ts_query && $ts_query->num_rows > 0):
                                    while($ts = $ts_query->fetch_assoc()):
                                ?>
                                        <option value="<?php echo htmlspecialchars($ts['school_name']); ?>"><?php echo htmlspecialchars($ts['school_name']); ?></option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="portal-form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="portal-input-group">
                            <label>Class for Admission</label>
                            <select name="class_admitted" id="class_admitted">
                                <option>Class 5 (Preparation)</option>
                                <option>Class 6</option>
                                <option>Class 7</option>
                                <option>Senior Section</option>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Scholar Mode</label>
                            <select name="scholar_mode" id="scholar_mode" required>
                                <option value="">Select Mode...</option>
                                <?php foreach($tuition_modes as $mode => $fee): ?>
                                    <option value="<?php echo htmlspecialchars($mode); ?>"><?php echo htmlspecialchars($mode); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Academic Syllabus Group</label>
                            <select name="academic_group" id="academic_group">
                                <option value="Group A">Group A (Primary Foundation)</option>
                                <option value="Group B">Group B (Middle Competitive)</option>
                                <option value="Group C">Group C (Sainik & RMS Entrance)</option>
                                <option value="Group D">Group D (Netarhat & Simultala Special)</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-form-row" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="portal-input-group">
                            <label>Admission Date</label>
                            <input type="date" name="admission_date" id="admission_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="portal-input-group">
                            <label>Base Monthly Tuition Fee (₹)</label>
                            <input type="number" name="base_fee" id="base_fee" placeholder="Auto-calculated" step="0.01" required readonly style="background-color: #f8fafc; cursor: not-allowed; color: var(--portal-blue); font-weight: 800;">
                        </div>
                        <div class="portal-input-group">
                            <label>Monthly Discount (₹)</label>
                            <input type="number" name="monthly_discount" id="monthly_discount" placeholder="e.g. 500" step="0.01" value="0.00">
                        </div>
                    </div>

                    <div class="portal-form-row" style="background: #f8fafc; padding: 14px 16px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-shield-alt" style="color: #7c3aed;"></i> Security Deposit (₹)</label>
                            <input type="number" name="security_amount" id="security_amount" placeholder="0.00" step="0.01" value="0.00">
                            <small style="color: #64748b; font-size: 0.72rem; font-weight: 600; display: block; margin-top: 3px;">Refundable caution money</small>
                        </div>
                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-id-card" style="color: #2563eb;"></i> Registration Fee (₹)</label>
                            <input type="number" name="registration_fee" id="registration_fee" placeholder="0.00" step="0.01" value="0.00">
                            <small style="color: #64748b; font-size: 0.72rem; font-weight: 600; display: block; margin-top: 3px;">One-time registration</small>
                        </div>
                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-file-invoice-dollar" style="color: #16a34a;"></i> Admission Fee (₹)</label>
                            <input type="number" name="admission_fee" id="admission_fee" placeholder="0.00" step="0.01" value="0.00">
                            <small style="color: #64748b; font-size: 0.72rem; font-weight: 600; display: block; margin-top: 3px;">One-time admission charge</small>
                        </div>
                        <div class="portal-input-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-hand-holding-usd" style="color: #ea580c;"></i> Advance Amount (₹)</label>
                            <input type="number" name="advance_amount" id="advance_amount" placeholder="0.00" step="0.01" value="0.00">
                            <small style="color: #64748b; font-size: 0.72rem; font-weight: 600; display: block; margin-top: 3px;">Advance credit balance</small>
                        </div>
                    </div>

                    <!-- Upload Offline Admission Form -->
                    <div class="portal-input-group">
                        <label>Upload Offline Admission Form Scan (Photo / PDF)</label>
                        <div class="photo-upload-area" id="uploadArea">
                            <input type="file" name="photo" id="photo_input" accept="image/*,.pdf" onchange="previewFile(this)">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--portal-blue); opacity: 0.5;"></i>
                            <p id="upload_label" style="margin: 8px 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600;">Click or drag to upload admission form scan (JPG, PNG, PDF — max 5MB)</p>
                        </div>
                        <div id="current_photo_display" style="display: none; margin-top: 10px; font-size: 0.85rem; color: var(--portal-blue); font-weight: 700;">
                            <i class="fas fa-paperclip"></i> <span id="current_photo_name"></span>
                            <a href="#" id="view_photo_link" target="_blank" style="margin-left: 10px; color: var(--portal-blue);">View Scan</a>
                        </div>
                    </div>

                    <!-- Upload Admission Test Paper -->
                    <div class="portal-input-group">
                        <label><i class="fas fa-file-signature" style="color: #7c3aed; margin-right: 4px;"></i> Upload Admission Test Paper (Evaluated Answer Sheet / Scan PDF / Image)</label>
                        <div class="photo-upload-area" id="uploadTestPaperArea" style="border-color: #ddd6fe; background: #faf5ff;">
                            <input type="file" name="admission_test_paper" id="admission_test_paper_input" accept="image/*,.pdf" onchange="previewTestPaperFile(this)">
                            <i class="fas fa-file-signature" style="font-size: 2rem; color: #7c3aed; opacity: 0.6;"></i>
                            <p id="upload_test_paper_label" style="margin: 8px 0 0; color: #6b21a8; font-size: 0.85rem; font-weight: 600;">Click or drag to upload Admission Test Paper (JPG, PNG, PDF — max 5MB)</p>
                        </div>
                        <div id="current_test_paper_display" style="display: none; margin-top: 10px; font-size: 0.85rem; color: #7c3aed; font-weight: 700;">
                            <i class="fas fa-paperclip"></i> <span id="current_test_paper_name"></span>
                            <a href="#" id="view_test_paper_link" target="_blank" style="margin-left: 10px; color: #7c3aed;">View Test Paper Scan</a>
                        </div>
                    </div>

                    <!-- SECTION 2: GUARDIAN INFORMATION -->
                    <span class="form-section-title"><i class="fas fa-user-shield" style="margin-right: 8px;"></i>2. Guardian Information</span>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Parent / Guardian Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="parent_name" id="parent_name" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Relationship to Student</label>
                            <select name="guardian_relationship" id="guardian_relationship">
                                <option value="">Select Relationship</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Guardian">Guardian</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Contact Number (Used for Login & WhatsApp) <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="phone" id="phone" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Guardian Email</label>
                            <input type="email" name="guardian_email" id="guardian_email" placeholder="Email address">
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Guardian Home Address (if different from student)</label>
                        <input type="text" name="guardian_address" id="guardian_address" placeholder="Leave blank if same as student">
                    </div>

                    <div class="portal-input-group">
                        <label>Link Parent Portal Account (Optional)</label>
                        <select name="parent_id" id="parent_id">
                            <option value="">-- Auto-create / Link Parent Account --</option>
                            <?php foreach ($parents_array as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['parent_name'] . ' (' . $parent['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- SECTION 3: EMERGENCY CONTACT -->
                    <span class="form-section-title"><i class="fas fa-phone-alt" style="margin-right: 8px;"></i>3. Emergency Contact Information</span>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" placeholder="Full name">
                        </div>
                        <div class="portal-input-group">
                            <label>Relationship to Student</label>
                            <input type="text" name="emergency_relationship" id="emergency_relationship" placeholder="e.g. Uncle, Aunt">
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Emergency Phone Number</label>
                        <input type="tel" name="emergency_phone" id="emergency_phone" placeholder="+91 XXXXX XXXXX">
                    </div>

                    <!-- SECTION 4: MEDICAL INFORMATION (OPTIONAL) -->
                    <span class="form-section-title"><i class="fas fa-heartbeat" style="margin-right: 8px;"></i>4. Medical Information <small style="font-weight: 400; font-size: 0.75rem; opacity: 0.8;">(Optional)</small></span>

                    <div class="portal-input-group">
                        <label>Does the student have any allergies?</label>
                        <div class="yes-no-group">
                            <label><input type="checkbox" name="has_allergies" id="has_allergies" onchange="toggleField('has_allergies','allergies_detail_row')"> Yes</label>
                        </div>
                        <div id="allergies_detail_row" style="display: none;">
                            <input type="text" name="allergies_detail" id="allergies_detail" placeholder="Please list the allergies">
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Does the student have any medical conditions we should be aware of?</label>
                        <div class="yes-no-group">
                            <label><input type="checkbox" name="has_medical_condition" id="has_medical_condition" onchange="toggleField('has_medical_condition','medical_condition_detail_row')"> Yes</label>
                        </div>
                        <div id="medical_condition_detail_row" style="display: none;">
                            <input type="text" name="medical_condition_detail" id="medical_condition_detail" placeholder="Please specify the medical condition">
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Primary Physician Name</label>
                            <input type="text" name="physician_name" id="physician_name" placeholder="Doctor's name">
                        </div>
                        <div class="portal-input-group">
                            <label>Physician Phone Number</label>
                            <input type="tel" name="physician_phone" id="physician_phone" placeholder="Doctor's phone">
                        </div>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Health Insurance Provider</label>
                            <input type="text" name="insurance_provider" id="insurance_provider" placeholder="Insurance company name">
                        </div>
                        <div class="portal-input-group">
                            <label>Policy Number</label>
                            <input type="text" name="insurance_policy" id="insurance_policy" placeholder="Policy / Card number">
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" name="save_student" class="btn-portal" style="flex: 1; padding: 16px;">
                            <i class="fas fa-check-circle"></i> Save Student Record
                        </button>
                        <button type="button" class="btn-portal" onclick="hideModal()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; box-shadow: none; padding: 16px 24px;">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Modal Handlers
        function showModal() {
            document.getElementById('studentModal').style.display = 'flex';
            document.getElementById('student_id').value = '';
            document.getElementById('existing_photo').value = '';
            document.getElementById('existing_admission_test_paper').value = '';
            document.getElementById('existing_student_photo').value = '';
            document.getElementById('current_photo_display').style.display = 'none';
            document.getElementById('current_test_paper_display').style.display = 'none';
            document.getElementById('current_student_photo_display').style.display = 'none';
            document.getElementById('upload_label').textContent = 'Click or drag to upload admission form scan (JPG, PNG, PDF — max 5MB)';
            document.getElementById('upload_test_paper_label').textContent = 'Click or drag to upload Admission Test Paper (JPG, PNG, PDF — max 5MB)';
            document.getElementById('photoPreviewImg').style.display = 'none';
            document.getElementById('photoPreviewImg').src = '';
            document.getElementById('photoPreviewIcon').style.display = 'flex';
            document.getElementById('allergies_detail_row').style.display = 'none';
            document.getElementById('medical_condition_detail_row').style.display = 'none';
            document.querySelector('#studentModal form').reset();
            document.getElementById('base_fee').value = '';
            document.getElementById('security_amount').value = '0.00';
            document.getElementById('registration_fee').value = '0.00';
            document.getElementById('admission_fee').value = '0.00';
            document.getElementById('advance_amount').value = '0.00';
        }

        const feeSettings = <?php echo json_encode($tuition_modes); ?>;

        document.getElementById('scholar_mode').addEventListener('change', function() {
            const mode = this.value;
            if (feeSettings[mode]) {
                document.getElementById('base_fee').value = feeSettings[mode];
            }
        });

        function hideModal() {
            document.getElementById('studentModal').style.display = 'none';
        }

        function toggleField(checkboxId, rowId) {
            var cb = document.getElementById(checkboxId);
            document.getElementById(rowId).style.display = cb.checked ? 'block' : 'none';
        }

        function previewFile(input) {
            if (input.files && input.files[0]) {
                document.getElementById('upload_label').textContent = '✅ ' + input.files[0].name + ' selected';
            }
        }

        function previewTestPaperFile(input) {
            if (input.files && input.files[0]) {
                document.getElementById('upload_test_paper_label').textContent = '✅ ' + input.files[0].name + ' selected';
            }
        }

        function previewStudentPhoto(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.getElementById('photoPreviewImg');
                    var icon = document.getElementById('photoPreviewIcon');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    icon.style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function editStudent(data) {
            document.getElementById('studentModal').style.display = 'flex';
            document.getElementById('student_id').value = data.id;
            document.getElementById('existing_photo').value = data.photo || '';
            document.getElementById('existing_admission_test_paper').value = data.admission_test_paper || '';

            // Student info
            document.getElementById('name').value = data.name || '';
            document.getElementById('dob').value = data.dob || '';
            document.getElementById('gender').value = data.gender || '';
            document.getElementById('home_address').value = data.home_address || '';
            document.getElementById('city').value = data.city || '';
            document.getElementById('state').value = data.state || '';
            document.getElementById('zip_code').value = data.zip_code || '';
            document.getElementById('prev_school').value = data.prev_school || '';
            document.getElementById('target_school').value = data.target_school || '';
            document.getElementById('class_admitted').value = data.class_admitted || '';
            document.getElementById('scholar_mode').value = data.scholar_mode || 'Day Scholar';
            if (document.getElementById('academic_group')) {
                document.getElementById('academic_group').value = data.academic_group || 'Group A';
            }
            document.getElementById('base_fee').value = data.base_fee || '';
            document.getElementById('monthly_discount').value = data.monthly_discount || '0.00';
            document.getElementById('security_amount').value = data.security_amount || '0.00';
            document.getElementById('registration_fee').value = data.registration_fee || '0.00';
            document.getElementById('admission_fee').value = data.admission_fee || '0.00';
            document.getElementById('advance_amount').value = data.advance_amount || '0.00';
            document.getElementById('admission_date').value = data.admission_date || '';

            // Guardian info
            document.getElementById('parent_name').value = data.parent_name || '';
            document.getElementById('guardian_relationship').value = data.guardian_relationship || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('guardian_email').value = data.guardian_email || '';
            document.getElementById('guardian_address').value = data.guardian_address || '';
            document.getElementById('parent_id').value = data.parent_id || '';

            // Emergency
            document.getElementById('emergency_contact_name').value = data.emergency_contact_name || '';
            document.getElementById('emergency_relationship').value = data.emergency_relationship || '';
            document.getElementById('emergency_phone').value = data.emergency_phone || '';

            // Medical
            document.getElementById('has_allergies').checked = data.has_allergies == 1;
            document.getElementById('allergies_detail_row').style.display = data.has_allergies == 1 ? 'block' : 'none';
            document.getElementById('allergies_detail').value = data.allergies_detail || '';

            document.getElementById('has_medical_condition').checked = data.has_medical_condition == 1;
            document.getElementById('medical_condition_detail_row').style.display = data.has_medical_condition == 1 ? 'block' : 'none';
            document.getElementById('medical_condition_detail').value = data.medical_condition_detail || '';

            document.getElementById('physician_name').value = data.physician_name || '';
            document.getElementById('physician_phone').value = data.physician_phone || '';
            document.getElementById('insurance_provider').value = data.insurance_provider || '';
            document.getElementById('insurance_policy').value = data.insurance_policy || '';

            // Existing admission form scan
            if (data.photo) {
                document.getElementById('current_photo_display').style.display = 'block';
                document.getElementById('current_photo_name').textContent = data.photo.split('/').pop();
                document.getElementById('view_photo_link').href = '../' + data.photo;
            } else {
                document.getElementById('current_photo_display').style.display = 'none';
            }

            // Existing admission test paper scan
            if (data.admission_test_paper) {
                document.getElementById('current_test_paper_display').style.display = 'block';
                document.getElementById('current_test_paper_name').textContent = data.admission_test_paper.split('/').pop();
                document.getElementById('view_test_paper_link').href = '../' + data.admission_test_paper;
            } else {
                document.getElementById('current_test_paper_display').style.display = 'none';
            }

            // Existing student picture in circle
            document.getElementById('existing_student_photo').value = data.student_photo || '';
            if (data.student_photo) {
                var img = document.getElementById('photoPreviewImg');
                var icon = document.getElementById('photoPreviewIcon');
                img.src = '../' + data.student_photo;
                img.style.display = 'block';
                icon.style.display = 'none';
                document.getElementById('current_student_photo_display').style.display = 'block';
                document.getElementById('view_student_photo_link').href = '../' + data.student_photo;
            } else {
                document.getElementById('photoPreviewImg').style.display = 'none';
                document.getElementById('photoPreviewIcon').style.display = 'flex';
                document.getElementById('current_student_photo_display').style.display = 'none';
            }
        }

        // View Mode Switcher (Cards vs Table)
        function toggleViewMode(mode) {
            const cardsView = document.getElementById('cardsViewContainer');
            const tableView = document.getElementById('tableViewContainer');
            const btnGrid = document.getElementById('btnGridMode');
            const btnTable = document.getElementById('btnTableMode');

            if (mode === 'table') {
                cardsView.style.display = 'none';
                tableView.style.display = 'block';
                btnTable.classList.add('active');
                btnGrid.classList.remove('active');
            } else {
                cardsView.style.display = 'grid';
                tableView.style.display = 'none';
                btnGrid.classList.add('active');
                btnTable.classList.remove('active');
            }
        }

        // Dynamic Instant Search & Filtering
        function filterStudents() {
            const query = document.getElementById('studentSearchInput').value.toLowerCase().trim();
            const classVal = document.getElementById('classFilterSelect').value.toLowerCase().trim();
            const modeVal = document.getElementById('modeFilterSelect').value.toLowerCase().trim();
            const schoolVal = document.getElementById('schoolFilterSelect').value.toLowerCase().trim();
            const groupVal = document.getElementById('groupFilterSelect') ? document.getElementById('groupFilterSelect').value.toLowerCase().trim() : '';
            const statusVal = document.getElementById('statusFilterSelect') ? document.getElementById('statusFilterSelect').value.toLowerCase().trim() : 'active';

            const cards = document.querySelectorAll('.student-item-card');
            const rows = document.querySelectorAll('.student-item-row');
            let visibleCount = 0;

            function matches(el) {
                const name = el.getAttribute('data-name') || '';
                const reg = el.getAttribute('data-reg') || '';
                const parent = el.getAttribute('data-parent') || '';
                const phone = el.getAttribute('data-phone') || '';
                const email = el.getAttribute('data-email') || '';
                const cls = (el.getAttribute('data-class') || '').toLowerCase();
                const mode = (el.getAttribute('data-mode') || '').toLowerCase();
                const school = (el.getAttribute('data-school') || '').toLowerCase();
                const group = (el.getAttribute('data-group') || '').toLowerCase();
                const status = (el.getAttribute('data-status') || 'active').toLowerCase();

                const textSearchMatch = query === '' || 
                    name.includes(query) || 
                    reg.includes(query) || 
                    parent.includes(query) || 
                    phone.includes(query) || 
                    email.includes(query);

                const classMatch = classVal === '' || cls === classVal;
                const modeMatch = modeVal === '' || mode === modeVal;
                const schoolMatch = schoolVal === '' || school === schoolVal;
                const groupMatch = groupVal === '' || group === groupVal;
                const statusMatch = statusVal === '' || status === statusVal;

                return textSearchMatch && classMatch && modeMatch && schoolMatch && groupMatch && statusMatch;
            }

            cards.forEach(card => {
                if (matches(card)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            rows.forEach(row => {
                row.style.display = matches(row) ? '' : 'none';
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function resetAllFilters() {
            document.getElementById('studentSearchInput').value = '';
            document.getElementById('classFilterSelect').value = '';
            document.getElementById('modeFilterSelect').value = '';
            document.getElementById('schoolFilterSelect').value = '';
            if (document.getElementById('groupFilterSelect')) {
                document.getElementById('groupFilterSelect').value = '';
            }
            if (document.getElementById('statusFilterSelect')) {
                document.getElementById('statusFilterSelect').value = 'active';
            }
            filterStudents();
        }

        // Run filter on initial page load
        document.addEventListener('DOMContentLoaded', function() {
            filterStudents();
        });
    </script>
</body>
</html>
