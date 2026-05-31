<?php
require_once 'includes/auth.php';

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
    $admission_date     = trim($_POST['admission_date']);
    $monthly_discount   = isset($_POST['monthly_discount']) ? (float)$_POST['monthly_discount'] : 0.00;
    $base_fee           = isset($_POST['base_fee']) ? (float)$_POST['base_fee'] : 0.00;
    $id                 = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $parent_id          = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

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
            target_school=?, class_admitted=?, scholar_mode=?, monthly_discount=?, base_fee=?, admission_date=?, parent_id=?, photo=?, student_photo=?
            WHERE id=?");
        $types = str_repeat('s', 16) . 'isis' . str_repeat('s', 7) . 'ddsissi';
        $params = [
            $name, $dob, $gender, $home_address, $city, $state, $zip_code, $prev_school,
            $parent_name, $guardian_relationship, $phone, $guardian_email, $guardian_address,
            $emergency_contact_name, $emergency_relationship, $emergency_phone,
            $has_allergies, $allergies_detail, $has_medical_condition, $medical_condition_detail,
            $physician_name, $physician_phone, $insurance_provider, $insurance_policy,
            $target_school, $class_admitted, $scholar_mode, $monthly_discount, $base_fee, $admission_date, $parent_id, $photo_path, $student_photo_path,
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
            target_school, class_admitted, scholar_mode, monthly_discount, base_fee, admission_date, parent_id, photo, student_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $types = str_repeat('s', 16) . 'isis' . str_repeat('s', 7) . 'ddsiss';
        $params = [
            $name, $dob, $gender, $home_address, $city, $state, $zip_code, $prev_school,
            $parent_name, $guardian_relationship, $phone, $guardian_email, $guardian_address,
            $emergency_contact_name, $emergency_relationship, $emergency_phone,
            $has_allergies, $allergies_detail, $has_medical_condition, $medical_condition_detail,
            $physician_name, $physician_phone, $insurance_provider, $insurance_policy,
            $target_school, $class_admitted, $scholar_mode, $monthly_discount, $base_fee, $admission_date, $parent_id, $photo_path, $student_photo_path
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
        // Rename student picture with actual student id
        if (!empty($student_photo_path)) {
            $sp_ext2 = pathinfo($student_photo_path, PATHINFO_EXTENSION);
            $new_sp = 'uploads/students/' . $new_id . '_pic.' . $sp_ext2;
            if (file_exists(__DIR__ . '/../' . $student_photo_path)) {
                rename(__DIR__ . '/../' . $student_photo_path, __DIR__ . '/../' . $new_sp);
            }
            $conn->query("UPDATE students SET student_photo = '" . $conn->real_escape_string($new_sp) . "' WHERE id = $new_id");
        }
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

$students = $conn->query("
    SELECT s.*, p.parent_name AS account_parent_name, p.email AS parent_email 
    FROM students s 
    LEFT JOIN parents p ON s.parent_id = p.id 
    ORDER BY s.created_at DESC
");
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
    // Fallback if settings haven't been updated yet
    $fee_day_scholar = $site_settings['fee_day_scholar'] ?? '3000';
    $fee_hostler = $site_settings['fee_hostler'] ?? '5000';
    $tuition_modes = ['Day Scholar' => $fee_day_scholar, 'Hostler' => $fee_hostler];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 20px 0; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 800px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); margin: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .btn-glass { background: #f8faff; color: var(--portal-blue); border: 2px solid #eef2ff; padding: 15px 25px; border-radius: 16px; font-weight: 700; cursor: pointer; }

        /* Form section headers */
        .form-section-title {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #fff;
            background: var(--portal-blue);
            padding: 8px 20px;
            border-radius: 10px;
            margin: 30px 0 18px;
            display: block;
        }
        .form-section-title:first-of-type { margin-top: 0; }

        /* Photo upload */
        .photo-upload-area {
            border: 2px dashed #c7d2fe;
            border-radius: 18px;
            padding: 28px;
            text-align: center;
            background: #f8faff;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }
        .photo-upload-area:hover { border-color: var(--portal-blue); background: #eef2ff; }
        .photo-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .photo-upload-area i { font-size: 2rem; color: var(--portal-blue); opacity: 0.5; }
        .photo-upload-area p { margin: 8px 0 0; color: #5c6bc0; font-size: 0.85rem; font-weight: 600; }

        /* Medical toggle */
        .yes-no-group { display: flex; gap: 15px; align-items: center; margin: 5px 0 12px; }
        .yes-no-group label { display: flex; align-items: center; gap: 6px; font-weight: 700; color: #5c6bc0; cursor: pointer; }
        .yes-no-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--portal-blue); }

        /* Reg number badge */
        .reg-badge { display: inline-block; background: #eef2ff; color: var(--portal-blue); padding: 3px 10px; border-radius: 8px; font-weight: 800; font-size: 0.78rem; }

        /* Responsive grid */
        @media (max-width: 600px) {
            .modal-content { padding: 25px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <h1>Student Registry</h1>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> New Enrollment
            </button>
        </div>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Reg No.</th>
                        <th>Student</th>
                        <th>Parent / Guardian</th>
                        <th>Class / Mode</th>
                        <th>Target School</th>
                        <th>Adm. Form</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if(!empty($row['reg_no'])): ?>
                                <span class="reg-badge"><?php echo htmlspecialchars($row['reg_no']); ?></span>
                            <?php else: ?>
                                <span style="color:#ccc; font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--portal-blue); font-weight:800;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php if(!empty($row['student_photo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['student_photo']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid #c7d2fe; flex-shrink:0;">
                                <?php else: ?>
                                    <div style="width:42px; height:42px; border-radius:50%; background:#eef2ff; color:var(--portal-blue); display:flex; align-items:center; justify-content:center; font-size:0.9rem; font-weight:800; flex-shrink:0;"><?php $ini = explode(' ', $row['name']); echo htmlspecialchars(substr($ini[0],0,1).(isset($ini[1])?substr($ini[1],0,1):'')); ?></div>
                                <?php endif; ?>
                                <div>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                    <?php if(!empty($row['dob'])): ?>
                                        <br><small style="color:#9aa5ce; font-weight:600; font-size:0.75rem;">DOB: <?php echo date('d M Y', strtotime($row['dob'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['parent_name']); ?>
                            <?php if (!empty($row['parent_email'])): ?>
                                <br><small style="color:var(--portal-blue); font-weight:700;"><i class="fas fa-link"></i> <?php echo htmlspecialchars($row['parent_email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['class_admitted']); ?>
                            <br><small style="color:#5c6bc0; font-weight:700;"><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($row['scholar_mode'] ? $row['scholar_mode'] : 'Day Scholar'); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['target_school']); ?></td>
                        <td>
                            <?php if(!empty($row['photo'])): ?>
                                <a href="../<?php echo htmlspecialchars($row['photo']); ?>" target="_blank" title="View Admission Form">
                                    <i class="fas fa-file-image" style="color:var(--portal-blue); font-size:1.3rem;"></i>
                                </a>
                            <?php else: ?>
                                <span style="color:#ccc; font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="student_addons.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" style="border:none; color:#2e7d32;" title="Manage Addons">
                                <i class="fas fa-plus-circle"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue);" onclick='editStudent(<?php echo json_encode($row); ?>)' title="Edit Student">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f;" onclick="return confirm('Are you sure you want to delete this student?')" title="Delete Student">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="studentModal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.8rem; margin:0;">Student Registration</h2>
                    <button type="button" onclick="hideModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" id="studentForm">
                    <input type="hidden" name="id" id="student_id">
                    <input type="hidden" name="existing_photo" id="existing_photo">

                    <input type="hidden" name="existing_student_photo" id="existing_student_photo">

                    <!-- SECTION 1: STUDENT INFORMATION -->
                    <span class="form-section-title"><i class="fas fa-user-graduate" style="margin-right:8px;"></i>1. Student Information</span>

                    <!-- Student Passport Photo Upload -->
                    <div style="display:flex; gap:30px; align-items:flex-start; margin-bottom:25px;">
                        <div style="flex-shrink:0; text-align:center;">
                            <div id="photoPreviewCircle" style="width:100px; height:100px; border-radius:50%; background:#eef2ff; border:3px dashed #c7d2fe; display:flex; align-items:center; justify-content:center; overflow:hidden; margin:0 auto 8px; cursor:pointer; position:relative;">
                                <img id="photoPreviewImg" src="" alt="" style="width:100%; height:100%; object-fit:cover; display:none; border-radius:50%;">
                                <i id="photoPreviewIcon" class="fas fa-camera" style="font-size:1.8rem; color:#a5b4fc;"></i>
                                <input type="file" name="student_photo" id="student_photo_input" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="previewStudentPhoto(this)">
                            </div>
                            <div style="font-size:0.75rem; color:#9aa5ce; font-weight:600;">Student Photo<br><span style="font-size:0.65rem;">(Max 3MB)</span></div>
                            <div id="current_student_photo_display" style="display:none; margin-top:6px;">
                                <a href="#" id="view_student_photo_link" target="_blank" style="font-size:0.75rem; color:var(--portal-blue); font-weight:700;"><i class="fas fa-eye"></i> View</a>
                            </div>
                        </div>
                        <div style="flex:1;">
                            <div class="portal-input-group" style="margin-bottom:0;">
                                <label>Candidate Full Name <span style="color:red">*</span></label>
                                <input type="text" name="name" id="name" placeholder="Full legal name" required>
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
                        <label>Home Address</label>
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
                            <label>ZIP Code</label>
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

                    <div class="portal-form-row">
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
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Admission Date</label>
                            <input type="date" name="admission_date" id="admission_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="portal-input-group">
                            <label>Base Monthly Fee (₹)</label>
                            <input type="number" name="base_fee" id="base_fee" placeholder="Auto-calculated" step="0.01" required readonly style="background-color: #f8f9fa; cursor: not-allowed; border-color: #eef2ff; color: #5c6bc0; font-weight: 800;">
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Monthly Discount (₹)</label>
                        <input type="number" name="monthly_discount" id="monthly_discount" placeholder="e.g. 500" step="0.01">
                    </div>

                    <!-- Upload Offline Admission Form -->
                    <div class="portal-input-group">
                        <label>Upload Offline Admission Form (Photo / PDF)</label>
                        <div class="photo-upload-area" id="uploadArea">
                            <input type="file" name="photo" id="photo_input" accept="image/*,.pdf" onchange="previewFile(this)">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p id="upload_label">Click or drag to upload admission form scan (JPG, PNG, PDF — max 5MB)</p>
                        </div>
                        <div id="current_photo_display" style="display:none; margin-top:10px; font-size:0.85rem; color:var(--portal-blue);">
                            <i class="fas fa-paperclip"></i> <span id="current_photo_name"></span>
                            <a href="#" id="view_photo_link" target="_blank" style="margin-left:10px;">View</a>
                        </div>
                    </div>

                    <!-- SECTION 2: GUARDIAN INFORMATION -->
                    <span class="form-section-title"><i class="fas fa-user-shield" style="margin-right:8px;"></i>2. Guardian Information</span>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Parent / Guardian Name <span style="color:red">*</span></label>
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
                            <label>Contact Number <span style="color:red">*</span></label>
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
                            <option value="">-- No Account Linked --</option>
                            <?php foreach ($parents_array as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['parent_name'] . ' (' . $parent['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- SECTION 3: EMERGENCY CONTACT -->
                    <span class="form-section-title"><i class="fas fa-phone-alt" style="margin-right:8px;"></i>3. Emergency Contact Information</span>

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
                    <span class="form-section-title"><i class="fas fa-heartbeat" style="margin-right:8px;"></i>4. Medical Information <small style="font-weight:400; font-size:0.75rem; opacity:0.8;">(Optional)</small></span>

                    <div class="portal-input-group">
                        <label>Does the student have any allergies?</label>
                        <div class="yes-no-group">
                            <label><input type="checkbox" name="has_allergies" id="has_allergies" onchange="toggleField('has_allergies','allergies_detail_row')"> Yes</label>
                        </div>
                        <div id="allergies_detail_row" style="display:none;">
                            <input type="text" name="allergies_detail" id="allergies_detail" placeholder="Please list the allergies">
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label>Does the student have any medical conditions we should be aware of?</label>
                        <div class="yes-no-group">
                            <label><input type="checkbox" name="has_medical_condition" id="has_medical_condition" onchange="toggleField('has_medical_condition','medical_condition_detail_row')"> Yes</label>
                        </div>
                        <div id="medical_condition_detail_row" style="display:none;">
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

                    <div class="portal-btn-row" style="margin-top:35px;">
                        <button type="submit" name="save_student" class="btn-portal w-100" style="padding:18px;">Confirm Registration</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('studentModal').style.display = 'flex';
            document.getElementById('student_id').value = '';
            document.getElementById('existing_photo').value = '';
            document.getElementById('existing_student_photo').value = '';
            document.getElementById('current_photo_display').style.display = 'none';
            document.getElementById('current_student_photo_display').style.display = 'none';
            document.getElementById('upload_label').textContent = 'Click or drag to upload admission form scan (JPG, PNG, PDF — max 5MB)';
            // Reset photo preview circle
            document.getElementById('photoPreviewImg').style.display = 'none';
            document.getElementById('photoPreviewImg').src = '';
            document.getElementById('photoPreviewIcon').style.display = 'flex';
            // Reset allergy toggles
            document.getElementById('allergies_detail_row').style.display = 'none';
            document.getElementById('medical_condition_detail_row').style.display = 'none';
            document.querySelector('#studentModal form').reset();
            // Default populate base fee for first mode on new form or clear
            document.getElementById('base_fee').value = '';
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
            document.getElementById('base_fee').value = data.base_fee || '';
            document.getElementById('monthly_discount').value = data.monthly_discount || '';
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

        // Close modal when clicking backdrop
        document.getElementById('studentModal').addEventListener('click', function(e) {
            if (e.target === this) hideModal();
        });
    </script>
</body>
</html>
