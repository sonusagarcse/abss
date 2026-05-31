<?php
// parent/includes/auth.php - Parent Session Authorization Gatekeeper

require_once __DIR__ . '/../../includes/security.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: ../admin/login.php?role=parent");
    exit();
}

require_once __DIR__ . '/../../config/db.php';
$conn = getDB();

// Middleware: Enforce Required Documents Upload
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'documents.php' && $current_page !== 'logout.php') {
    $pid = (int)$_SESSION['parent_id'];
    
    // Check if there are any required documents globally
    $req_check = $conn->query("SELECT id FROM document_types WHERE is_required = 1");
    if ($req_check && $req_check->num_rows > 0) {
        $required_docs = [];
        while($r = $req_check->fetch_assoc()) { $required_docs[] = $r['id']; }
        
        // Fetch active children for this parent
        $children_q = $conn->query("SELECT id FROM students WHERE parent_id = $pid AND status = 'active'");
        if ($children_q && $children_q->num_rows > 0) {
            $missing = false;
            while($child = $children_q->fetch_assoc()) {
                $cid = $child['id'];
                foreach ($required_docs as $doc_id) {
                    $doc_q = $conn->query("SELECT status FROM student_documents WHERE student_id = $cid AND document_type_id = $doc_id");
                    if ($doc_q->num_rows == 0) {
                        $missing = true;
                        break 2;
                    } else {
                        $d_status = $doc_q->fetch_assoc()['status'];
                        if ($d_status == 'rejected') {
                            $missing = true;
                            break 2;
                        }
                    }
                }
            }
            if ($missing) {
                header("Location: documents.php");
                exit();
            }
        }
    }
}
?>
