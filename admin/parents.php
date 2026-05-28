<?php
// admin/parents.php - Parent Registry Management

require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Add/Edit Parent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_parent'])) {
    $parent_name = trim($_POST['parent_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $selected_students = isset($_POST['students']) ? $_POST['students'] : []; // Array of student IDs

    if (empty($parent_name) || empty($email)) {
        $err = "Name and Email are required fields.";
    } else {
        if ($id > 0) {
            // Edit Parent
            // Check if email already exists for another parent
            $check = $conn->prepare("SELECT id FROM parents WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "A parent account with this email already exists.";
            } else {
                if (!empty($password)) {
                    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE parents SET parent_name = ?, email = ?, password = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $parent_name, $email, $pass_hash, $phone, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE parents SET parent_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $parent_name, $email, $phone, $id);
                }
                
                if ($stmt->execute()) {
                    // Update student links
                    $conn->query("UPDATE students SET parent_id = NULL WHERE parent_id = $id");
                    if (!empty($selected_students)) {
                        $ids_str = implode(',', array_map('intval', $selected_students));
                        $conn->query("UPDATE students SET parent_id = $id WHERE id IN ($ids_str)");
                    }
                    $msg = "Parent account updated successfully.";
                    log_activity('parent_updated', "Updated parent credentials & linkages for $parent_name ($email)");
                } else {
                    $err = "Error updating parent account.";
                }
            }
        } else {
            // New Parent
            if (empty($password)) {
                $err = "Password is required for new parent accounts.";
            } else {
                $check = $conn->prepare("SELECT id FROM parents WHERE email = ?");
                $check->bind_param("s", $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $err = "A parent account with this email already exists.";
                } else {
                    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO parents (parent_name, email, password, phone) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $parent_name, $email, $pass_hash, $phone);
                    
                    if ($stmt->execute()) {
                        $new_parent_id = $conn->insert_id;
                        if (!empty($selected_students)) {
                            $ids_str = implode(',', array_map('intval', $selected_students));
                            $conn->query("UPDATE students SET parent_id = $new_parent_id WHERE id IN ($ids_str)");
                        }
                        $msg = "Parent account created successfully.";
                        log_activity('parent_created', "Created parent registry account for $parent_name ($email)");
                    } else {
                        $err = "Error creating parent account.";
                    }
                }
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Fetch details before delete for activity logging
    $p_stmt = $conn->query("SELECT parent_name, email FROM parents WHERE id = $id");
    $p_row = $p_stmt ? $p_stmt->fetch_assoc() : null;
    $del_pname = $p_row['parent_name'] ?? 'ID ' . $id;
    
    // Students parent_id set to NULL handled by DB constraint
    $conn->query("DELETE FROM parents WHERE id = $id");
    log_activity('parent_deleted', "Deleted parent account for $del_pname");
    
    header("Location: parents.php");
    exit();
}

// Fetch all parents and their children
$parents = $conn->query("
    SELECT p.*, GROUP_CONCAT(s.name SEPARATOR ', ') AS children_names
    FROM parents p
    LEFT JOIN students s ON s.parent_id = p.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");

// Fetch active students for the assignment checkbox list
$students_list = $conn->query("SELECT id, name, parent_name FROM students ORDER BY name ASC");
$all_students = [];
while ($s = $students_list->fetch_assoc()) {
    $all_students[] = $s;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Registry | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 650px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); overflow-y: auto; max-height: 90vh; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 20px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .btn-glass { background: #f8faff; color: var(--portal-blue); border: 2px solid #eef2ff; padding: 15px 25px; border-radius: 16px; font-weight: 700; cursor: pointer; }
        .children-tag { background: #eef2ff; color: var(--portal-blue); padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; margin-right: 5px; display: inline-block; margin-bottom: 4px; }
        .no-children { color: #9aa5ce; font-style: italic; font-size: 0.85rem; }
        .students-selector { border: 2px solid #f0f4f8; border-radius: 16px; padding: 15px; background: #f8faff; max-height: 180px; overflow-y: auto; }
        .student-option { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 0.95rem; color: var(--portal-dark); cursor: pointer; }
        .student-option input { width: auto; margin: 0; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <div>
                <h1>Parent Registry</h1>
                <p>Manage parent login credentials and student mappings.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Create Parent Login
            </button>
        </div>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#ffebee; color:#d32f2f; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700; border: 1px solid rgba(211,47,47,0.1);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Parent Name</th>
                        <th>Email / Username</th>
                        <th>Phone</th>
                        <th>Linked Student(s)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($parents->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #9aa5ce;">No parent accounts created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = $parents->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--portal-blue); font-weight:800;"><?php echo htmlspecialchars($row['parent_name']); ?></td>
                            <td style="font-family: monospace; font-size:0.9rem;"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ? $row['phone'] : 'N/A'); ?></td>
                            <td>
                                <?php 
                                if (!empty($row['children_names'])) {
                                    $names = explode(', ', $row['children_names']);
                                    foreach ($names as $name) {
                                        echo "<span class='children-tag'><i class='fas fa-child'></i> " . htmlspecialchars($name) . "</span>";
                                    }
                                } else {
                                    echo "<span class='no-children'>No children linked</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue); cursor:pointer; background:none;" onclick='editParent(<?php 
                                    // Fetch child IDs linked
                                    $pid = $row['id'];
                                    $c_res = $conn->query("SELECT id FROM students WHERE parent_id = $pid");
                                    $c_ids = [];
                                    while($cr = $c_res->fetch_assoc()) $c_ids[] = (int)$cr['id'];
                                    $row['student_ids'] = $c_ids;
                                    echo json_encode($row); 
                                ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f; margin-left: 10px;" onclick="return confirm('Are you sure you want to delete this parent account? Students will be unlinked but not deleted.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="parentModal">
            <div class="modal-content">
                <h2 id="modalTitle" style="margin-bottom: 30px; color: var(--portal-blue); font-weight: 800; font-size: 1.8rem;">Create Parent Login</h2>
                <form action="" method="POST">
                    <input type="hidden" name="id" id="parent_id">
                    <div class="portal-input-group">
                        <label>Parent / Guardian Name</label>
                        <input type="text" name="parent_name" id="parent_name" placeholder="Full name of parent" required>
                    </div>
                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Email (Login Username)</label>
                            <input type="email" name="email" id="email" placeholder="name@example.com" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Contact Number</label>
                            <input type="text" name="phone" id="phone" placeholder="Contact number">
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Security Password <span id="passHelp" style="text-transform:none; font-weight:normal; opacity:0.7;">(Required for new accounts)</span></label>
                        <input type="password" name="password" id="password" placeholder="••••••••">
                    </div>
                    <div class="portal-input-group">
                        <label>Link Child / Student(s)</label>
                        <div class="students-selector">
                            <?php foreach ($all_students as $student): ?>
                                <label class="student-option">
                                    <input type="checkbox" name="students[]" value="<?php echo $student['id']; ?>" class="student-chk" id="student_chk_<?php echo $student['id']; ?>">
                                    <span><?php echo htmlspecialchars($student['name']); ?> <small style="color:#9aa5ce;">(Class: <?php echo htmlspecialchars($student['parent_name']); ?>)</small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="portal-btn-row">
                        <button type="submit" name="save_parent" class="btn-portal w-100" style="padding:18px;">Confirm Credentials</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('parentModal').style.display = 'flex';
            document.getElementById('parent_id').value = '';
            document.getElementById('modalTitle').innerText = 'Create Parent Login';
            document.getElementById('passHelp').innerText = '(Required for new accounts)';
            document.getElementById('password').required = true;
            document.querySelector('#parentModal form').reset();
            
            // Clear checks
            document.querySelectorAll('.student-chk').forEach(c => c.checked = false);
        }
        
        function hideModal() {
            document.getElementById('parentModal').style.display = 'none';
        }
        
        function editParent(data) {
            document.getElementById('parentModal').style.display = 'flex';
            document.getElementById('parent_id').value = data.id;
            document.getElementById('modalTitle').innerText = 'Edit Parent Login';
            document.getElementById('passHelp').innerText = '(Leave blank to keep current)';
            document.getElementById('password').required = false;
            document.getElementById('parent_name').value = data.parent_name;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone;
            document.getElementById('password').value = '';
            
            // Reset checks
            document.querySelectorAll('.student-chk').forEach(c => c.checked = false);
            
            // Set checks
            if (data.student_ids && data.student_ids.length > 0) {
                data.student_ids.forEach(id => {
                    const chk = document.getElementById('student_chk_' + id);
                    if (chk) chk.checked = true;
                });
            }
        }
    </script>
</body>
</html>
