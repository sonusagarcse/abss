<?php
// parent/includes/auth.php - Parent Session Authorization Gatekeeper

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
$conn = getDB();

// Auto-Login Middleware: Restore parent session from persistent 1-year remember cookie and refresh expiry
verify_and_restore_parent_session();

if (!isset($_SESSION['parent_id']) || (int)$_SESSION['parent_id'] <= 0) {
    header("Location: login.php");
    exit();
}

$pid = (int)$_SESSION['parent_id'];

// Active Student Gatekeeper: Real-time verification that parent has at least one active student
$active_students_check = $conn->query("SELECT COUNT(*) AS active_count FROM students WHERE parent_id = $pid AND status = 'active'");
$active_count = ($active_students_check && $active_students_check->num_rows > 0) ? (int)$active_students_check->fetch_assoc()['active_count'] : 0;

if ($active_count === 0) {
    // Auto-logout parent and clear persistent remember cookie
    unset($_SESSION['parent_id']);
    unset($_SESSION['parent_name']);
    unset($_SESSION['parent_email']);
    unset($_SESSION['show_missing_docs_popup']);
    unset($_SESSION['missing_docs_notified']);

    clear_parent_remember_cookie();

    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])) {
        session_destroy();
    }

    header("Location: login.php?error=inactive");
    exit();
}

// Middleware: Enforce Required Documents Upload
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'documents.php' && $current_page !== 'logout.php') {
    
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
                if (!isset($_SESSION['missing_docs_notified'])) {
                    $_SESSION['show_missing_docs_popup'] = true;
                    $_SESSION['missing_docs_notified'] = true;
                }
            }
        }
    }
}
?>
