<?php
// parent/documents.php - Upload Required Documents
require_once 'includes/auth.php';

$pid = (int)$_SESSION['parent_id'];
$msg = '';
$err = '';

// Handle document upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_doc'])) {
    $student_id = (int)$_POST['student_id'];
    $doc_type_id = (int)$_POST['doc_type_id'];

    // Verify student belongs to parent
    $verify = $conn->prepare("SELECT id FROM students WHERE id = ? AND parent_id = ?");
    $verify->bind_param("ii", $student_id, $pid);
    $verify->execute();
    if ($verify->get_result()->num_rows > 0) {
        if (!empty($_FILES['document_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed) && $_FILES['document_file']['size'] < 5 * 1024 * 1024) {
                $upload_dir = __DIR__ . '/../uploads/documents/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $filename = 'doc_' . $student_id . '_' . $doc_type_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_dir . $filename)) {
                    $file_path = 'uploads/documents/' . $filename;

                    // Check if already exists
                    $check_exist = $conn->query("SELECT id FROM student_documents WHERE student_id = $student_id AND document_type_id = $doc_type_id");
                    if ($check_exist->num_rows > 0) {
                        $conn->query("UPDATE student_documents SET file_path = '$file_path', status = 'pending', uploaded_at = CURRENT_TIMESTAMP WHERE student_id = $student_id AND document_type_id = $doc_type_id");
                    } else {
                        $conn->query("INSERT INTO student_documents (student_id, document_type_id, file_path, status) VALUES ($student_id, $doc_type_id, '$file_path', 'pending')");
                    }
                    $msg = "Document uploaded successfully and is pending review.";
                } else {
                    $err = "Failed to move uploaded file.";
                }
            } else {
                $err = "Invalid file type (PDF, JPG, PNG only) or file too large (Max 5MB).";
            }
        }
    } else {
        $err = "Unauthorized access.";
    }
}

// Fetch all active children
$children_query = $conn->prepare("SELECT id, name FROM students WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$children_query->bind_param("i", $pid);
$children_query->execute();
$children_res = $children_query->get_result();
$children = [];
while ($c = $children_res->fetch_assoc()) {
    $children[] = $c;
}

// Fetch required document types
$req_docs_query = $conn->query("SELECT * FROM document_types WHERE is_required = 1");
$required_docs = [];
while ($d = $req_docs_query->fetch_assoc()) {
    $required_docs[] = $d;
}

// Calculate missing documents
$all_complete = true;
$missing_count = 0;

foreach ($children as &$child) {
    $child['docs'] = [];
    foreach ($required_docs as $rd) {
        $sid = $child['id'];
        $dtid = $rd['id'];
        $doc_check = $conn->query("SELECT * FROM student_documents WHERE student_id = $sid AND document_type_id = $dtid");
        if ($doc_check->num_rows > 0) {
            $doc_data = $doc_check->fetch_assoc();
            $child['docs'][] = [
                'type' => $rd,
                'status' => $doc_data['status'],
                'file' => $doc_data['file_path']
            ];
            if ($doc_data['status'] == 'rejected') {
                $all_complete = false;
                $missing_count++;
            }
        } else {
            $child['docs'][] = [
                'type' => $rd,
                'status' => 'missing',
                'file' => null
            ];
            $all_complete = false;
            $missing_count++;
        }
    }
}
unset($child);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Required Documents | ABSS Parent Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .doc-card { background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .student-header { color: var(--portal-blue); border-bottom: 2px solid #f0f4f8; padding-bottom: 10px; margin-bottom: 20px; }
        .doc-row { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8faff; border-radius: 12px; margin-bottom: 15px; }
        .doc-status-badge { padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 800; }
        .status-missing, .status-rejected { background: #feeef2; color: #d32f2f; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .inline-btn { width: auto !important; display: inline-block; text-decoration: none; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Required Documents</h1>
            <p>Please complete the profiles of your children by uploading the necessary documents.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#feeef2; color:#d32f2f; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <?php if (!$all_complete): ?>
            <div style="background:#feeef2; border-left: 5px solid #d32f2f; color:#d32f2f; padding:20px; border-radius:15px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-exclamation-triangle" style="font-size:1.5rem; margin-bottom:10px; display:block;"></i>
                Action Required: You have <?php echo $missing_count; ?> required document(s) missing or rejected. You must upload them to fully access the portal.
            </div>
        <?php else: ?>
            <div style="background:#e8f5e9; border-left: 5px solid #2e7d32; color:#2e7d32; padding:20px; border-radius:15px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle" style="font-size:1.5rem; margin-bottom:10px; display:block;"></i>
                All required documents have been uploaded! Thank you. <br><br>
                <a href="dashboard.php" class="btn-portal inline-btn" style="margin-top:10px;"><i class="fas fa-arrow-right"></i> Proceed to Dashboard</a>
            </div>
        <?php endif; ?>

        <?php foreach ($children as $child): ?>
            <div class="doc-card">
                <h3 class="student-header"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($child['name']); ?></h3>
                <?php if(empty($child['docs'])): ?>
                    <p style="color:#9aa5ce;">No required documents configured.</p>
                <?php else: ?>
                    <?php foreach ($child['docs'] as $doc): ?>
                        <div class="doc-row">
                            <div>
                                <h4 style="margin:0 0 5px; color:#2c3e50; font-weight:800;"><?php echo htmlspecialchars($doc['type']['name']); ?></h4>
                                <span class="doc-status-badge status-<?php echo $doc['status']; ?>">
                                    <?php echo strtoupper($doc['status']); ?>
                                </span>
                            </div>
                            <div>
                                <?php if($doc['status'] == 'missing' || $doc['status'] == 'rejected'): ?>
                                    <form action="" method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:10px;">
                                        <input type="hidden" name="student_id" value="<?php echo $child['id']; ?>">
                                        <input type="hidden" name="doc_type_id" value="<?php echo $doc['type']['id']; ?>">
                                        <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png" style="font-size:0.85rem; padding:8px; border:1px solid #c7d2fe; border-radius:8px; background:#fff;">
                                        <button type="submit" name="upload_doc" class="btn-portal inline-btn" style="padding:10px 20px; font-size:0.9rem;"><i class="fas fa-upload"></i> Upload</button>
                                    </form>
                                <?php elseif($doc['status'] == 'pending'): ?>
                                    <span style="color:#f57c00; font-weight:700; font-size:0.9rem;"><i class="fas fa-clock"></i> Under Review</span>
                                <?php else: ?>
                                    <a href="../<?php echo $doc['file']; ?>" target="_blank" class="btn-portal inline-btn" style="padding:8px 15px; font-size:0.9rem; background:#43a047;"><i class="fas fa-eye"></i> View</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </main>
</body>
</html>
