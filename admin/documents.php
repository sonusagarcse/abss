<?php
// admin/documents.php - Manage Document Types
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM document_types WHERE id = $id")) {
        $msg = "Document type deleted.";
    } else {
        $err = "Cannot delete document type. It may be in use.";
    }
}

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_doc'])) {
    $name = trim($_POST['name']);
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE document_types SET name=?, is_required=? WHERE id=?");
        $stmt->bind_param("sii", $name, $is_required, $id);
        if ($stmt->execute()) {
            $msg = "Document updated successfully.";
        } else {
            $err = "Error updating document.";
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO document_types (name, is_required) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $is_required);
        if ($stmt->execute()) {
            $msg = "Document added successfully.";
        } else {
            $err = "Error adding document.";
        }
    }
}

$docs = $conn->query("SELECT * FROM document_types ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Document Types | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .modal-backdrop { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 20px 0; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 500px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); border: 1px solid rgba(13,71,161,0.1); margin: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .close-modal { background: none; border: none; font-size: 1.5rem; color: var(--portal-blue); cursor: pointer; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        
        .doc-badge {
            padding: 5px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 800; display: inline-block;
        }
        .badge-req { background: #feeef2; color: #d32f2f; }
        .badge-opt { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <h1>Manage Required Documents</h1>
            <div>
                <a href="document_approvals.php" class="btn-portal" style="background:#43a047; text-decoration:none; display:inline-block; margin-right:10px;">
                    <i class="fas fa-check-double"></i> Review Pending
                </a>
                <button class="btn-portal" onclick="showModal()">
                    <i class="fas fa-plus"></i> Add Document Type
                </button>
            </div>
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
                        <th>ID</th>
                        <th>Document Name</th>
                        <th>Requirement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $docs->fetch_assoc()): ?>
                    <tr>
                        <td style="color:#9aa5ce; font-weight:700;">#<?php echo $row['id']; ?></td>
                        <td style="font-weight:800; color:var(--portal-blue);"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <?php if($row['is_required']): ?>
                                <span class="doc-badge badge-req">Required</span>
                            <?php else: ?>
                                <span class="doc-badge badge-opt">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" style="border:none; color:var(--portal-blue);" onclick='editDoc(<?php echo json_encode($row); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" style="border:none; color:#d32f2f;" onclick="return confirm('Delete this document type? This will not delete previously uploaded files.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal-backdrop" id="docModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Document Configuration</h2>
                    <button class="close-modal" onclick="hideModal()"><i class="fas fa-times"></i></button>
                </div>
                <form action="" method="POST" class="premium-form">
                    <input type="hidden" name="id" id="doc_id">
                    
                    <div class="portal-input-group">
                        <label>Document Name <span style="color:red">*</span></label>
                        <input type="text" name="name" id="doc_name" placeholder="e.g. Aadhar Card" required>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display:flex; align-items:center; gap:10px; font-weight:700; color:#0d47a1; cursor:pointer;">
                            <input type="checkbox" name="is_required" id="doc_required" value="1" style="width:20px; height:20px;">
                            Mark as Required Document
                        </label>
                        <p style="font-size:0.85rem; color:#5c6bc0; margin:5px 0 0 30px;">Parents will be forced to upload this document when they log into the parent portal.</p>
                    </div>

                    <button type="submit" name="save_doc" class="btn-portal" style="width:100%; padding:15px; font-size:1.1rem;">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('docModal').style.display = 'flex';
            document.getElementById('doc_id').value = '';
            document.getElementById('doc_name').value = '';
            document.getElementById('doc_required').checked = false;
        }
        function hideModal() {
            document.getElementById('docModal').style.display = 'none';
        }
        function editDoc(data) {
            document.getElementById('docModal').style.display = 'flex';
            document.getElementById('doc_id').value = data.id;
            document.getElementById('doc_name').value = data.name;
            document.getElementById('doc_required').checked = data.is_required == 1;
        }
        document.getElementById('docModal').addEventListener('click', function(e) {
            if (e.target === this) hideModal();
        });
    </script>
</body>
</html>
