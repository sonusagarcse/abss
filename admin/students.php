<?php
require_once 'includes/auth.php';

// Handle Add/Edit Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_student'])) {
    $name = $_POST['name'];
    $parent_name = $_POST['parent_name'];
    $phone = $_POST['phone'];
    $target_school = $_POST['target_school'];
    $class_admitted = $_POST['class_admitted'];
    $scholar_mode = $_POST['scholar_mode'];
    $admission_date = $_POST['admission_date'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE students SET name=?, parent_name=?, phone=?, target_school=?, class_admitted=?, scholar_mode=?, admission_date=?, parent_id=? WHERE id=?");
        $stmt->bind_param("sssssssii", $name, $parent_name, $phone, $target_school, $class_admitted, $scholar_mode, $admission_date, $parent_id, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO students (name, parent_name, phone, target_school, class_admitted, scholar_mode, admission_date, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $name, $parent_name, $phone, $target_school, $class_admitted, $scholar_mode, $admission_date, $parent_id);
        $stmt->execute();
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
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 650px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 20px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .btn-glass { background: #f8faff; color: var(--portal-blue); border: 2px solid #eef2ff; padding: 15px 25px; border-radius: 16px; font-weight: 700; cursor: pointer; }
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
                        <th>Student Name</th>
                        <th>Parent Name</th>
                        <th>Class / Mode</th>
                        <th>Target School</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--portal-blue); font-weight:800;"><?php echo htmlspecialchars($row['name']); ?></td>
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
                            <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue);" onclick='editStudent(<?php echo json_encode($row); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f;" onclick="return confirm('Are you sure?')">
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
                <h2 style="margin-bottom: 30px; color: var(--portal-blue); font-weight: 800; font-size: 1.8rem;">Student Registration</h2>
                <form action="" method="POST">
                    <input type="hidden" name="id" id="student_id">
                    <div class="portal-input-group">
                        <label>Candidate Full Name</label>
                        <input type="text" name="name" id="name" placeholder="Full legal name" required>
                    </div>
                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label>Parent/Guardian</label>
                            <input type="text" name="parent_name" id="parent_name" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Contact Number</label>
                            <input type="text" name="phone" id="phone" required>
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Link Parent Account (Optional)</label>
                        <select name="parent_id" id="parent_id">
                            <option value="">-- No Account Linked --</option>
                            <?php foreach ($parents_array as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['parent_name'] . ' (' . $parent['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Target School / Program</label>
                        <input type="text" name="target_school" id="target_school" placeholder="e.g. Netarhat Residential">
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
                            <select name="scholar_mode" id="scholar_mode">
                                <option value="Day Scholar">Day Scholar</option>
                                <option value="Hostler">Hostler</option>
                            </select>
                        </div>
                    </div>
                    <div class="portal-input-group">
                        <label>Admission Date</label>
                        <input type="date" name="admission_date" id="admission_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="portal-btn-row">
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
            document.querySelector('#studentModal form').reset();
        }
        function hideModal() {
            document.getElementById('studentModal').style.display = 'none';
        }
        function editStudent(data) {
            document.getElementById('studentModal').style.display = 'flex';
            document.getElementById('student_id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('parent_name').value = data.parent_name;
            document.getElementById('phone').value = data.phone;
            document.getElementById('target_school').value = data.target_school;
            document.getElementById('class_admitted').value = data.class_admitted;
            document.getElementById('scholar_mode').value = data.scholar_mode || 'Day Scholar';
            document.getElementById('admission_date').value = data.admission_date;
            document.getElementById('parent_id').value = data.parent_id || '';
        }
    </script>
</body>
</html>
