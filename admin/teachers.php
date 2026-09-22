<?php
require_once 'includes/auth.php';

// Ensure upload directory exists
$upload_dir = __DIR__ . '/../uploads/teachers/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Add/Edit Teacher
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['save_teacher'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department'] ?? '');
    if (empty($department) && !empty($_POST['department_select']) && $_POST['department_select'] !== 'Other') {
        $department = trim($_POST['department_select']);
    }
    $designation = trim($_POST['designation']);
    $join_date = trim($_POST['join_date']);
    $salary = (float)$_POST['salary'];
    $status = trim($_POST['status']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Handle photo upload
    $photo_path = $_POST['existing_photo'] ?? '';
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] < 3 * 1024 * 1024) {
            $tmp_name = 'teacher_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $tmp_name)) {
                $photo_path = 'uploads/teachers/' . $tmp_name;
            }
        }
    }

    // Ensure password column exists
    $checkPassCol = $conn->query("SHOW COLUMNS FROM teachers LIKE 'password'");
    if ($checkPassCol && $checkPassCol->num_rows == 0) {
        $conn->query("ALTER TABLE teachers ADD COLUMN password VARCHAR(255) NULL AFTER phone");
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE teachers SET name=?, email=?, phone=?, department=?, designation=?, join_date=?, salary=?, status=?, photo=? WHERE id=?");
        $stmt->bind_param("ssssssdssi", $name, $email, $phone, $department, $designation, $join_date, $salary, $status, $photo_path, $id);
        $stmt->execute();
    } else {
        // Automatic Credentials: Default password is the mobile number (phone)
        $default_password = !empty($phone) ? $phone : (!empty($email) ? $email : '123456');
        $pass_hash = password_hash($default_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO teachers (name, email, phone, password, department, designation, join_date, salary, status, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssdss", $name, $email, $phone, $pass_hash, $department, $designation, $join_date, $salary, $status, $photo_path);
        $stmt->execute();
    }
    
    header("Location: teachers.php");
    exit();
}

// Handle Status Toggle Action (Deactivate / Reactivate)
if (isset($_GET['toggle_status'])) {
    $toggle_id = (int)$_GET['toggle_status'];
    $target_status = $_GET['status'] ?? 'inactive';
    if ($toggle_id > 0 && in_array($target_status, ['active', 'inactive'])) {
        $stmt = $conn->prepare("UPDATE teachers SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $target_status, $toggle_id);
        $stmt->execute();
        header("Location: teachers.php?status=" . $target_status);
        exit();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teachers WHERE id = $id");
    header("Location: teachers.php");
    exit();
}

// Counts for filter pills
$active_res = $conn->query("SELECT COUNT(*) as c FROM teachers WHERE status = 'active'");
$active_count = $active_res ? (int)$active_res->fetch_assoc()['c'] : 0;

$inactive_res = $conn->query("SELECT COUNT(*) as c FROM teachers WHERE status = 'inactive'");
$inactive_count = $inactive_res ? (int)$inactive_res->fetch_assoc()['c'] : 0;

$total_count = $active_count + $inactive_count;

// Default to 'active' list if not specified
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'active';
$dept_filter = isset($_GET['dept']) ? trim($_GET['dept']) : '';

$where_clauses = [];
if ($status_filter === 'active') {
    $where_clauses[] = "status = 'active'";
} elseif ($status_filter === 'inactive') {
    $where_clauses[] = "status = 'inactive'";
}
if (!empty($dept_filter)) {
    $safe_dept = $conn->real_escape_string($dept_filter);
    $where_clauses[] = "department = '$safe_dept'";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
$teachers = $conn->query("SELECT * FROM teachers $where_sql ORDER BY created_at DESC");

// Fetch distinct departments for the filter dropdown
$distinct_depts_res = $conn->query("SELECT DISTINCT department FROM teachers WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$all_existing_depts = [];
if ($distinct_depts_res) {
    while($dr = $distinct_depts_res->fetch_assoc()) {
        $all_existing_depts[] = $dr['department'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher & Staff Management | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 24px; 
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-tab-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.86rem;
            font-weight: 700;
            color: #64748b;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .filter-tab:hover {
            border-color: #cbd5e1;
            color: var(--portal-dark);
        }
        .filter-tab.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }
        .filter-tab .tab-badge {
            background: rgba(255, 255, 255, 0.2);
            color: inherit;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .filter-tab:not(.active) .tab-badge {
            background: #f1f5f9;
            color: #475569;
        }

        .teacher-action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-action-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
            font-size: 0.88rem;
        }
        .btn-act-act { background: #dcfce7; color: #166534; }
        .btn-act-act:hover { background: #166534; color: #fff; }
        .btn-act-deact { background: #fee2e2; color: #dc2626; }
        .btn-act-deact:hover { background: #dc2626; color: #fff; }
        .btn-act-edit { background: #eff6ff; color: #2563eb; }
        .btn-act-edit:hover { background: #2563eb; color: #fff; }
        .btn-act-del { background: #fef2f2; color: #dc2626; }
        .btn-act-del:hover { background: #dc2626; color: #fff; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 20px 0; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 800px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); margin: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .btn-glass { background: #f8faff; color: var(--portal-blue); border: 2px solid #eef2ff; padding: 15px 25px; border-radius: 16px; font-weight: 700; cursor: pointer; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-active { background: #f0fdf4; color: #166534; }
        .status-inactive { background: #fef2f2; color: #b91c1c; }

        @media (max-width: 768px) {
            .action-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            .action-bar-right .btn-portal {
                width: 100%;
                justify-content: center;
            }
            .filter-tab-bar {
                gap: 8px;
            }
            .filter-tab {
                flex: 1;
                justify-content: center;
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            .portal-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            table {
                min-width: 720px;
            }
            .modal-content {
                padding: 26px 18px !important;
                border-radius: 20px !important;
            }
            .portal-form-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--portal-dark); margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-chalkboard-teacher" style="color: var(--portal-blue);"></i> Teacher & Staff Registry
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Manage school teachers, faculty members, and administrative staff profiles.</p>
            </div>
            <div class="action-bar-right">
                <button class="btn-portal" onclick="showModal()">
                    <i class="fas fa-user-plus"></i> Add Teacher & Staff
                </button>
            </div>
        </div>

        <!-- Filter Bar: Status Tabs & Department Filter -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
            <div class="filter-tab-bar" style="margin-bottom:0;">
                <a href="teachers.php?status=active<?php echo !empty($dept_filter) ? '&dept='.urlencode($dept_filter) : ''; ?>" class="filter-tab <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i> Active Staff <span class="tab-badge"><?php echo $active_count; ?></span>
                </a>
                <a href="teachers.php?status=inactive<?php echo !empty($dept_filter) ? '&dept='.urlencode($dept_filter) : ''; ?>" class="filter-tab <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>">
                    <i class="fas fa-user-slash"></i> Inactive Staff <span class="tab-badge"><?php echo $inactive_count; ?></span>
                </a>
                <a href="teachers.php?status=all<?php echo !empty($dept_filter) ? '&dept='.urlencode($dept_filter) : ''; ?>" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> All Staff <span class="tab-badge"><?php echo $total_count; ?></span>
                </a>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:0.85rem; font-weight:700; color:#64748b; white-space:nowrap;"><i class="fas fa-filter"></i> Department:</label>
                <select onchange="location.href='teachers.php?status=<?php echo urlencode($status_filter); ?>' + (this.value ? '&dept=' + encodeURIComponent(this.value) : '')" style="padding:8px 14px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:0.85rem; font-weight:700; color:#1e293b; background:#fff; outline:none; cursor:pointer;">
                    <option value="">All Departments</option>
                    <?php foreach($all_existing_depts as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $dept_filter === $d ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Staff Profile</th>
                        <th>Contact</th>
                        <th>Role & Department</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($teachers && $teachers->num_rows > 0): while($row = $teachers->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--portal-blue); font-weight:800;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php if(!empty($row['photo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid #c7d2fe; flex-shrink:0;">
                                <?php else: ?>
                                    <div style="width:42px; height:42px; border-radius:50%; background:#eef2ff; color:var(--portal-blue); display:flex; align-items:center; justify-content:center; font-size:0.9rem; font-weight:800; flex-shrink:0;"><?php $ini = explode(' ', $row['name']); echo htmlspecialchars(substr($ini[0],0,1).(isset($ini[1])?substr($ini[1],0,1):'')); ?></div>
                                <?php endif; ?>
                                <div>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['phone']); ?><br>
                            <small style="color:#9aa5ce;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['designation']); ?><br>
                            <small style="color:#5c6bc0; font-weight:700;"><i class="fas fa-building"></i> <?php echo htmlspecialchars($row['department']); ?></small>
                        </td>
                        <td><?php echo !empty($row['join_date']) ? date('d M Y', strtotime($row['join_date'])) : '-'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td>
                            <div class="teacher-action-buttons">
                                <?php if (($row['status'] ?? 'active') === 'active'): ?>
                                    <a href="?toggle_status=<?php echo $row['id']; ?>&status=inactive" class="btn-action-icon btn-act-deact" title="Deactivate Staff Member" onclick="return confirm('Deactivate <?php echo addslashes($row['name']); ?>? They will be marked inactive.')">
                                        <i class="fas fa-user-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?toggle_status=<?php echo $row['id']; ?>&status=active" class="btn-action-icon btn-act-act" title="Reactivate Staff Member" onclick="return confirm('Reactivate <?php echo addslashes($row['name']); ?>?')">
                                        <i class="fas fa-user-check"></i>
                                    </a>
                                <?php endif; ?>
                                <button class="btn-action-icon btn-act-edit" onclick='editTeacher(<?php echo json_encode($row); ?>)' title="Edit Profile">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-action-icon btn-act-del" onclick="return confirm('Are you sure you want to delete <?php echo addslashes($row['name']); ?>?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8; font-weight:700;">No staff records found in this category.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="teacherModal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.8rem; margin:0;" id="teacherModalTitle">Teacher & Staff Profile</h2>
                    <button type="button" onclick="hideModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" id="teacherForm" onsubmit="syncDepartmentValue()">
                    <input type="hidden" name="id" id="teacher_id">
                    <input type="hidden" name="existing_photo" id="existing_photo">

                    <div style="display:flex; gap:30px; align-items:flex-start; margin-bottom:25px;">
                        <div style="flex-shrink:0; text-align:center;">
                            <div id="photoPreviewCircle" style="width:100px; height:100px; border-radius:50%; background:#eef2ff; border:3px dashed #c7d2fe; display:flex; align-items:center; justify-content:center; overflow:hidden; margin:0 auto 8px; cursor:pointer; position:relative;">
                                <img id="photoPreviewImg" src="" alt="" style="width:100%; height:100%; object-fit:cover; display:none; border-radius:50%;">
                                <i id="photoPreviewIcon" class="fas fa-camera" style="font-size:1.8rem; color:#a5b4fc;"></i>
                                <input type="file" name="photo" id="photo_input" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="previewPhoto(this)">
                            </div>
                            <div style="font-size:0.75rem; color:#9aa5ce; font-weight:600;">Profile Photo</div>
                        </div>
                        <div style="flex:1;">
                            <div class="portal-input-group">
                                <label>Full Name <span style="color:red">*</span></label>
                                <input type="text" name="name" id="name" required>
                            </div>
                            <div class="portal-form-row">
                                <div class="portal-input-group">
                                    <label>Email Address <span style="color:red">*</span></label>
                                    <input type="email" name="email" id="email" required>
                                </div>
                                <div class="portal-input-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" id="phone">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="portal-form-row" style="grid-template-columns: 1fr 1fr;">
                        <div class="portal-input-group">
                            <label>Department <span style="color:red">*</span></label>
                            <select name="department_select" id="department_select" required onchange="handleDepartmentChange(this.value)">
                                <option value="">-- Select Department --</option>
                                
                                <optgroup label="Staff & Operations Departments">
                                    <option value="Food Department">Food Department (Mess / Kitchen)</option>
                                    <option value="Hostel & Residential">Hostel & Residential</option>
                                    <option value="Administration & Office">Administration & Office</option>
                                    <option value="Accounts & Finance">Accounts & Finance</option>
                                    <option value="Transport & Bus Service">Transport & Bus Service</option>
                                    <option value="Maintenance & Housekeeping">Maintenance & Housekeeping</option>
                                    <option value="Security & Guard">Security & Guard</option>
                                    <option value="Medical & Health">Medical & Health</option>
                                </optgroup>

                                <optgroup label="Academic & Teaching Departments">
                                    <option value="Basic">Basic (Junior / Primary)</option>
                                    <option value="Navodya Special">Navodya Special</option>
                                    <option value="Competitive">Competitive Preparation</option>
                                    <option value="Science & Mathematics">Science & Mathematics</option>
                                    <option value="Social Studies">Social Studies</option>
                                    <option value="Languages">Languages (Hindi / English / Sanskrit)</option>
                                    <option value="Computer & IT">Computer & IT</option>
                                    <option value="Physical Education & Sports">Physical Education & Sports</option>
                                    <option value="Art & Cultural">Art & Cultural</option>
                                </optgroup>

                                <optgroup label="Custom Department">
                                    <option value="Other">Other / Custom Department...</option>
                                </optgroup>
                            </select>
                            <input type="hidden" name="department" id="department">
                            <div id="custom_dept_wrapper" style="display:none; margin-top:8px;">
                                <input type="text" id="custom_department_input" placeholder="Type custom department name..." oninput="syncDepartmentValue()">
                            </div>
                        </div>
                        <div class="portal-input-group">
                            <label>Designation</label>
                            <input type="text" name="designation" id="designation" list="designation_options" placeholder="e.g. Senior Teacher, Head Cook">
                            <datalist id="designation_options">
                                <option value="Senior Teacher">
                                <option value="Assistant Teacher">
                                <option value="Navodaya Faculty">
                                <option value="Head Cook / Chef">
                                <option value="Kitchen Helper / Mess Staff">
                                <option value="Hostel Warden">
                                <option value="Bus Driver">
                                <option value="Bus Conductor">
                                <option value="Accountant / Office Clerk">
                                <option value="Security Guard">
                                <option value="Maintenance Staff">
                                <option value="Peon / Attendant">
                            </datalist>
                        </div>
                    </div>

                    <div class="portal-form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="portal-input-group">
                            <label>Join Date</label>
                            <input type="date" name="join_date" id="join_date">
                        </div>
                        <div class="portal-input-group">
                            <label>Base Salary (₹)</label>
                            <input type="number" name="salary" id="salary" step="0.01" value="0.00">
                        </div>
                        <div class="portal-input-group">
                            <label>Status</label>
                            <select name="status" id="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="portal-btn-row" style="margin-top:35px;">
                        <button type="submit" name="save_teacher" class="btn-portal w-100" style="padding:18px;"><i class="fas fa-save"></i> Save Teacher & Staff</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function handleDepartmentChange(val) {
            const customWrapper = document.getElementById('custom_dept_wrapper');
            const customInput = document.getElementById('custom_department_input');
            const hiddenDept = document.getElementById('department');
            
            if (val === 'Other') {
                customWrapper.style.display = 'block';
                customInput.focus();
                hiddenDept.value = customInput.value.trim();
            } else {
                customWrapper.style.display = 'none';
                hiddenDept.value = val;
            }
        }

        function syncDepartmentValue() {
            const sel = document.getElementById('department_select');
            if (sel.value === 'Other') {
                document.getElementById('department').value = document.getElementById('custom_department_input').value.trim();
            } else {
                document.getElementById('department').value = sel.value;
            }
        }

        function showModal() {
            document.getElementById('teacherModal').style.display = 'flex';
            document.getElementById('teacher_id').value = '';
            document.getElementById('existing_photo').value = '';
            document.getElementById('photoPreviewImg').style.display = 'none';
            document.getElementById('photoPreviewImg').src = '';
            document.getElementById('photoPreviewIcon').style.display = 'flex';
            document.querySelector('#teacherModal form').reset();
            document.getElementById('department_select').value = '';
            document.getElementById('department').value = '';
            document.getElementById('custom_dept_wrapper').style.display = 'none';
            document.getElementById('custom_department_input').value = '';
            document.getElementById('status').value = 'active';
            document.getElementById('teacherModalTitle').textContent = 'Add Teacher & Staff Profile';
        }

        function hideModal() {
            document.getElementById('teacherModal').style.display = 'none';
        }

        function previewPhoto(input) {
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

        function editTeacher(data) {
            document.getElementById('teacherModal').style.display = 'flex';
            document.getElementById('teacherModalTitle').textContent = 'Edit Profile - ' + data.name;
            document.getElementById('teacher_id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone;
            document.getElementById('designation').value = data.designation;
            document.getElementById('join_date').value = data.join_date;
            document.getElementById('salary').value = data.salary;
            document.getElementById('status').value = data.status;
            
            // Handle Department Option
            const deptSel = document.getElementById('department_select');
            const customWrapper = document.getElementById('custom_dept_wrapper');
            const customInput = document.getElementById('custom_department_input');
            const hiddenDept = document.getElementById('department');

            const currentDept = data.department || '';
            hiddenDept.value = currentDept;

            let matched = false;
            for (let i = 0; i < deptSel.options.length; i++) {
                if (deptSel.options[i].value && deptSel.options[i].value.toLowerCase() === currentDept.toLowerCase()) {
                    deptSel.selectedIndex = i;
                    matched = true;
                    break;
                }
            }

            if (!matched && currentDept !== '') {
                deptSel.value = 'Other';
                customWrapper.style.display = 'block';
                customInput.value = currentDept;
            } else {
                if (!matched) deptSel.value = '';
                customWrapper.style.display = 'none';
                customInput.value = '';
            }
            
            document.getElementById('existing_photo').value = data.photo || '';
            if (data.photo) {
                var img = document.getElementById('photoPreviewImg');
                var icon = document.getElementById('photoPreviewIcon');
                img.src = '../' + data.photo;
                img.style.display = 'block';
                icon.style.display = 'none';
            } else {
                document.getElementById('photoPreviewImg').style.display = 'none';
                document.getElementById('photoPreviewIcon').style.display = 'flex';
            }
        }
    </script>
</body>
</html>
