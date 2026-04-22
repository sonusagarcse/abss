<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Status Update
if (isset($_POST['update_status']) && isset($_POST['admission_id'])) {
    $id = (int)$_POST['admission_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE admissions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        $msg = "Application status updated successfully.";
        
        // If admitted, migrate to students table
        if ($status === 'Admitted') {
            // Fetch admission details
            $res = $conn->query("SELECT * FROM admissions WHERE id = $id");
            if ($row = $res->fetch_assoc()) {
                $name = $row['student_name'];
                $parent = $row['parent_name'];
                $phone = $row['phone'];
                $school = $row['target_program'];
                $today = date('Y-m-d');
                
                // Check if already exists in students table
                $check = $conn->prepare("SELECT id FROM students WHERE name = ? AND phone = ?");
                $check->bind_param("ss", $name, $phone);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                
                if (!$exists) {
                    $insert = $conn->prepare("INSERT INTO students (name, parent_name, phone, target_school, class_admitted, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                    $class = $school; // Using school name as initial class/program reference
                    $insert->bind_param("ssssss", $name, $parent, $phone, $school, $class, $today);
                    $insert->execute();
                    $msg .= " Student has been successfully enrolled in the main database.";
                }
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM admissions WHERE id = $id");
    header("Location: admissions.php");
    exit();
}

// Fetch all admissions
$admissions = $conn->query("SELECT * FROM admissions ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Applications | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .table-container { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--portal-blue); font-weight: 800; border-bottom: 2px solid #f0f4f8; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
        tr:hover { background: #fafafa; }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: inline-block; }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Reviewed { background: #cce5ff; color: #004085; }
        .status-Admitted { background: #d4edda; color: #155724; }
        .status-Rejected { background: #f8d7da; color: #721c24; }
        .action-select { padding: 8px; border-radius: 10px; border: 1px solid #ddd; font-family: inherit; }
        .btn-update { background: var(--portal-blue); color: white; border: none; padding: 8px 15px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.8rem; }
        .btn-update:hover { background: var(--portal-dark); }
        .delete-btn { color: #d32f2f; font-weight: 800; text-decoration: none; font-size: 0.85rem; padding: 5px 10px; }
        .view-btn { color: var(--portal-yellow); font-weight: 800; text-decoration: none; font-size: 0.85rem; cursor: pointer;}
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 40px; border: 1px solid #888; width: 80%; max-width: 700px; border-radius: 30px; position: relative; }
        .close { color: #aaa; position: absolute; top: 20px; right: 30px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .info-box { background: #f8faff; padding: 15px; border-radius: 15px; }
        .info-box h4 { margin-top: 0; color: var(--portal-blue); margin-bottom: 5px; font-size: 0.9rem;}
        .info-box p { margin: 0; font-weight: 600; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 40px;">
            <h1>Admission Applications</h1>
            <p>Review and manage online admission inquiries.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Parent Phone</th>
                        <th>School</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($admissions && $admissions->num_rows > 0): ?>
                        <?php while($row = $admissions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['target_program']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['scholar_mode']); ?></strong></td>
                            <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                            <td>
                                <form action="" method="POST" style="display:inline-block; margin-right: 10px;">
                                    <input type="hidden" name="admission_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="action-select">
                                        <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Reviewed" <?php if($row['status']=='Reviewed') echo 'selected'; ?>>Reviewed</option>
                                        <option value="Admitted" <?php if($row['status']=='Admitted') echo 'selected'; ?>>Admitted</option>
                                        <option value="Rejected" <?php if($row['status']=='Rejected') echo 'selected'; ?>>Rejected</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-update">Update</button>
                                </form>
                                <a onclick='openModal(<?php echo json_encode($row); ?>)' class="view-btn">VIEW</a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this application?')">DEL</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #888;">No admission applications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal for viewing details -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="color: var(--portal-blue); margin-top: 0;">Application Details</h2>
            <div class="modal-grid" id="modalContent">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById("detailsModal");
        
        function openModal(data) {
            const content = document.getElementById("modalContent");
            content.innerHTML = `
                <div class="info-box"><h4>Student Name</h4><p>${escapeHtml(data.student_name)}</p></div>
                <div class="info-box"><h4>Date of Birth</h4><p>${escapeHtml(data.dob || 'N/A')}</p></div>
                <div class="info-box"><h4>Gender</h4><p>${escapeHtml(data.gender || 'N/A')}</p></div>
                <div class="info-box"><h4>Target School</h4><p>${escapeHtml(data.target_program || 'N/A')}</p></div>
                <div class="info-box"><h4>Scholar Mode</h4><p><strong>${escapeHtml(data.scholar_mode || 'N/A')}</strong></p></div>
                <div class="info-box"><h4>Parent Name</h4><p>${escapeHtml(data.parent_name || 'N/A')}</p></div>
                <div class="info-box"><h4>Phone Number</h4><p>${escapeHtml(data.phone)}</p></div>
                <div class="info-box"><h4>Email Address</h4><p>${escapeHtml(data.email || 'N/A')}</p></div>
                <div class="info-box"><h4>Previous School</h4><p>${escapeHtml(data.prev_school || 'N/A')}</p></div>
                <div class="info-box" style="grid-column: 1 / -1;"><h4>Full Address</h4><p>${escapeHtml(data.address || 'N/A')}</p></div>
                <div class="info-box" style="grid-column: 1 / -1;"><h4>Application Date</h4><p>${new Date(data.created_at).toLocaleString()}</p></div>
            `;
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
