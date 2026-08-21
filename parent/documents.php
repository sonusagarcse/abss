<?php
// parent/documents.php - Mobile-Friendly Upload Required Documents
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
$children_query = $conn->prepare("SELECT id, name, class_admitted, photo, admission_test_paper FROM students WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
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
        .doc-card { 
            background: #ffffff; 
            padding: 30px; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            margin-bottom: 30px; 
            border: 1px solid #e2e8f0;
        }

        .student-header { 
            color: var(--portal-dark); 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .doc-row { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 18px 20px; 
            background: #f8fafc; 
            border-radius: 14px; 
            margin-bottom: 15px; 
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .doc-status-badge { 
            padding: 5px 12px; 
            border-radius: 8px; 
            font-size: 0.78rem; 
            font-weight: 800; 
            display: inline-block;
            margin-top: 4px;
        }
        .status-missing, .status-rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        
        .inline-btn { 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none; 
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-view-doc { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .btn-view-doc:hover { background: #15803d; color: #ffffff; }

        .btn-upload-submit { background: var(--portal-blue); color: #ffffff; }
        .btn-upload-submit:hover { background: #1d4ed8; }

        @media (max-width: 640px) {
            .doc-card { padding: 20px 15px; border-radius: 18px; }
            .student-header { flex-direction: column; align-items: flex-start; gap: 6px; }
            .doc-row { flex-direction: column; align-items: stretch; gap: 12px; padding: 14px; }
            .doc-row form { flex-direction: column; align-items: stretch; width: 100%; gap: 10px; }
            .doc-row form input[type="file"] { width: 100%; box-sizing: border-box; }
            .inline-btn { width: 100%; justify-content: center; padding: 10px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 30px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Required Documents</h1>
            <p style="margin: 0;">Upload required verification documents for your enrolled children.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#fee2e2; color:#dc2626; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <!-- Overall Status Alert Box -->
        <?php if (!$all_complete): ?>
            <div style="background:#fef2f2; border-left: 5px solid #dc2626; color:#dc2626; padding:20px; border-radius:var(--radius-md); margin-bottom:30px; font-weight:700; border:1px solid #fecaca;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:1.3rem;"></i>
                    <strong style="font-size:1.05rem;">Action Required</strong>
                </div>
                You have <?php echo $missing_count; ?> required document(s) missing or rejected. Please upload them below for document verification.
            </div>
        <?php else: ?>
            <div style="background:#f0fdf4; border-left: 5px solid #166534; color:#166534; padding:20px; border-radius:var(--radius-md); margin-bottom:30px; font-weight:700; border:1px solid #bbf7d0;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <i class="fas fa-check-circle" style="font-size:1.3rem;"></i>
                    <strong style="font-size:1.05rem;">All Documents Verified</strong>
                </div>
                All required documents have been uploaded and approved. Thank you!
            </div>
        <?php endif; ?>

        <!-- Student Document Cards -->
        <?php foreach ($children as $child): ?>
            <div class="doc-card">
                <div class="student-header">
                    <h3 style="margin:0; font-size:1.3rem; color:var(--portal-dark); display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-user-graduate" style="color:var(--portal-blue);"></i> 
                        <?php echo htmlspecialchars($child['name']); ?>
                    </h3>
                    <span style="font-size:0.85rem; font-weight:800; color:#2563eb; background:#eff6ff; padding:4px 12px; border-radius:50px;">
                        Class: <?php echo htmlspecialchars($child['class_admitted'] ? $child['class_admitted'] : 'N/A'); ?>
                    </span>
                </div>

                <!-- Official School Admission Records Section -->
                <div style="margin-bottom: 22px;">
                    <h4 style="font-size: 0.88rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #6b21a8; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-file-signature"></i> Official Admission Records & Entrance Test
                    </h4>

                    <!-- Admission Test Paper Row -->
                    <div class="doc-row" style="background: #faf5ff; border-color: #e9d5ff;">
                        <div style="flex: 1; min-width: 220px;">
                            <h4 style="margin: 0 0 4px 0; color: #581c87; font-weight: 800; font-size: 0.96rem;">
                                <i class="fas fa-file-signature" style="color: #9333ea; margin-right: 6px;"></i> Admission Test Paper (प्रवेश परीक्षा प्रश्न-उत्तर पत्र)
                            </h4>
                            <small style="color: #7e22ce; font-weight: 600; display: block; margin-bottom: 6px;">Evaluated Entrance Exam Answer Sheet / Question Paper</small>
                            <?php if(!empty($child['admission_test_paper'])): ?>
                                <span class="doc-status-badge status-approved" style="background: #f3e8ff; color: #7e22ce; border-color: #d8b4fe;">
                                    ✓ AVAILABLE (स्कैन उपलब्ध)
                                </span>
                            <?php else: ?>
                                <span class="doc-status-badge" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                    ℹ️ NOT UPLOADED YET
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if(!empty($child['admission_test_paper'])): ?>
                                <a href="../<?php echo htmlspecialchars($child['admission_test_paper']); ?>" target="_blank" class="inline-btn" style="background: #9333ea; color: #ffffff; box-shadow: 0 4px 12px rgba(147, 51, 234, 0.25);">
                                    <i class="fas fa-eye"></i> View / Download Test Paper
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.82rem; color: #94a3b8; font-weight: 600;">School has not uploaded test paper yet.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Offline Admission Form Scan Row (if available) -->
                    <?php if(!empty($child['photo'])): ?>
                        <div class="doc-row" style="background: #f0f9ff; border-color: #bae6fd;">
                            <div style="flex: 1; min-width: 220px;">
                                <h4 style="margin: 0 0 4px 0; color: #0369a1; font-weight: 800; font-size: 0.96rem;">
                                    <i class="fas fa-file-pdf" style="color: #0284c7; margin-right: 6px;"></i> Admission Registration Form Scan
                                </h4>
                                <span class="doc-status-badge status-approved">✓ VERIFIED ARCHIVE</span>
                            </div>
                            <div>
                                <a href="../<?php echo htmlspecialchars($child['photo']); ?>" target="_blank" class="inline-btn" style="background: #0284c7; color: #ffffff;">
                                    <i class="fas fa-file-download"></i> View Admission Form
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="border-top: 1px solid #f1f5f9; padding-top: 18px; margin-top: 10px;">
                    <h4 style="font-size: 0.88rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--portal-blue); margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-folder-open"></i> Verification Documents
                    </h4>

                    <?php if(empty($child['docs'])): ?>
                        <p style="color:#94a3b8; font-size:0.9rem;">No required documents configured.</p>
                    <?php else: ?>
                        <?php foreach ($child['docs'] as $doc): ?>
                            <div class="doc-row">
                                <div style="flex:1; min-width:200px;">
                                    <h4 style="margin:0 0 6px 0; color:var(--portal-dark); font-weight:800; font-size:0.95rem;"><?php echo htmlspecialchars($doc['type']['name']); ?></h4>
                                    <span class="doc-status-badge status-<?php echo $doc['status']; ?>">
                                        <?php 
                                        if ($doc['status'] === 'approved') echo '✓ APPROVED';
                                        elseif ($doc['status'] === 'pending') echo '⏳ UNDER REVIEW';
                                        elseif ($doc['status'] === 'rejected') echo '✖ REJECTED';
                                        else echo '⚠️ MISSING';
                                        ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <?php if($doc['status'] == 'missing' || $doc['status'] == 'rejected'): ?>
                                        <form action="" method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                            <input type="hidden" name="student_id" value="<?php echo $child['id']; ?>">
                                            <input type="hidden" name="doc_type_id" value="<?php echo $doc['type']['id']; ?>">
                                            <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png" style="font-size:0.82rem; padding:8px 10px; border:1px solid #cbd5e1; border-radius:10px; background:#ffffff; max-width:220px;">
                                            <button type="submit" name="upload_doc" class="inline-btn btn-upload-submit">
                                                <i class="fas fa-cloud-upload-alt"></i> Upload
                                            </button>
                                        </form>
                                    <?php elseif($doc['status'] == 'pending'): ?>
                                        <span style="color:#b45309; font-weight:700; font-size:0.88rem; display:inline-flex; align-items:center; gap:6px;">
                                            <i class="fas fa-clock"></i> Pending School Approval
                                        </span>
                                    <?php else: ?>
                                        <a href="../<?php echo $doc['file']; ?>" target="_blank" class="inline-btn btn-view-doc">
                                            <i class="fas fa-eye"></i> View File
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </main>
</body>
</html>
