<?php
// admin/document_approvals.php - Review uploaded documents
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_doc'])) {
    $sd_id = (int)$_POST['sd_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE student_documents SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $sd_id);
    if ($stmt->execute()) {
        $msg = "Document has been " . $action . ".";
    } else {
        $err = "Error updating document status.";
    }
}

// Fetch pending documents
$pending_query = $conn->query("
    SELECT sd.id, sd.file_path, sd.uploaded_at, sd.status, 
           dt.name AS doc_name, s.name AS student_name, s.reg_no
    FROM student_documents sd
    JOIN document_types dt ON sd.document_type_id = dt.id
    JOIN students s ON sd.student_id = s.id
    WHERE sd.status = 'pending'
    ORDER BY sd.uploaded_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Documents | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .inline-btn { width: auto !important; display: inline-block; padding: 8px 15px !important; font-size: 0.85rem !important; margin-right: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <h1>Review Pending Documents</h1>
            <a href="documents.php" class="btn-portal" style="text-decoration:none; display:inline-block; width:auto;"><i class="fas fa-cog"></i> Configure Document Types</a>
        </div>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#feeef2; color:#d32f2f; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <div class="portal-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date Uploaded</th>
                        <th>Student</th>
                        <th>Document Type</th>
                        <th>View</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($pending_query->num_rows > 0): ?>
                        <?php while($row = $pending_query->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y, H:i', strtotime($row['uploaded_at'])); ?></td>
                            <td>
                                <strong style="color:var(--portal-blue);"><?php echo htmlspecialchars($row['student_name'] ?? ''); ?></strong>
                                <br><small><?php echo htmlspecialchars($row['reg_no'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['doc_name']); ?></td>
                            <td>
                                <a href="../<?php echo $row['file_path']; ?>" target="_blank" class="btn-portal inline-btn" style="background:#43a047; text-decoration:none;"><i class="fas fa-eye"></i> Open</a>
                            </td>
                            <td>
                                <form action="" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="sd_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action_doc" value="1">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn-portal inline-btn" style="background:#1e88e5;" onclick="return confirm('Approve this document?');"><i class="fas fa-check"></i> Approve</button>
                                </form>
                                <form action="" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="sd_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action_doc" value="1">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn-portal inline-btn" style="background:#d32f2f;" onclick="return confirm('Reject this document? The parent will be asked to re-upload.');"><i class="fas fa-times"></i> Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No pending documents to review.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
