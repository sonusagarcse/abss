<?php
require_once 'includes/auth.php';

// Ensure upload directory exists
$upload_dir = __DIR__ . '/../uploads/teachers/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Add/Edit Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_teacher'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
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

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE teachers SET name=?, email=?, phone=?, department=?, designation=?, join_date=?, salary=?, status=?, photo=? WHERE id=?");
        $stmt->bind_param("ssssssdssi", $name, $email, $phone, $department, $designation, $join_date, $salary, $status, $photo_path, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO teachers (name, email, phone, department, designation, join_date, salary, status, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdss", $name, $email, $phone, $department, $designation, $join_date, $salary, $status, $photo_path);
        $stmt->execute();
    }
    
    header("Location: teachers.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teachers WHERE id = $id");
    header("Location: teachers.php");
    exit();
}

$teachers = $conn->query("SELECT * FROM teachers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management | ABSS Portal</title>
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
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-active { background: #f0fdf4; color: #166534; }
        .status-inactive { background: #fef2f2; color: #b91c1c; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1>Teacher Registry</h1>
                <p>Manage school teachers and their profiles.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Add Teacher
            </button>
        </div>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Teacher Info</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $teachers->fetch_assoc()): ?>
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
                            <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue);" onclick='editTeacher(<?php echo json_encode($row); ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f;" onclick="return confirm('Are you sure?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="teacherModal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h2 style="color: var(--portal-blue); font-weight: 800; font-size: 1.8rem; margin:0;">Teacher Profile</h2>
                    <button type="button" onclick="hideModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#9aa5ce;">✕</button>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" id="teacherForm">
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
                            <label>Department</label>
                            <input type="text" name="department" id="department" placeholder="e.g. Science, Mathematics">
                        </div>
                        <div class="portal-input-group">
                            <label>Designation</label>
                            <input type="text" name="designation" id="designation" placeholder="e.g. Senior Teacher">
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
                        <button type="submit" name="save_teacher" class="btn-portal w-100" style="padding:18px;">Save Teacher</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('teacherModal').style.display = 'flex';
            document.getElementById('teacher_id').value = '';
            document.getElementById('existing_photo').value = '';
            document.getElementById('photoPreviewImg').style.display = 'none';
            document.getElementById('photoPreviewImg').src = '';
            document.getElementById('photoPreviewIcon').style.display = 'flex';
            document.querySelector('#teacherModal form').reset();
            document.getElementById('status').value = 'active';
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
            document.getElementById('teacher_id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone;
            document.getElementById('department').value = data.department;
            document.getElementById('designation').value = data.designation;
            document.getElementById('join_date').value = data.join_date;
            document.getElementById('salary').value = data.salary;
            document.getElementById('status').value = data.status;
            
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
