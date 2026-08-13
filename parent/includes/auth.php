<?php
// parent/includes/auth.php - Parent Session Authorization Gatekeeper

require_once __DIR__ . '/../../config/db.php';
$conn = getDB();

// Auto-Login Middleware: Restore parent session from persistent 1-year remember cookie
if (!isset($_SESSION['parent_id']) && isset($_COOKIE['abss_parent_remember'])) {
    $cookie_data = explode(':', $_COOKIE['abss_parent_remember'], 2);
    if (count($cookie_data) === 2) {
        $pid = (int)$cookie_data[0];
        $token_hash = $cookie_data[1];
        
        $stmt = $conn->prepare("SELECT id, parent_name, email FROM parents WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($parent = $res->fetch_assoc()) {
            $secret_key = defined('DB_PASS') ? DB_PASS . '_ABSS_AUTH_SECRET' : 'ABSS_AUTH_SECRET';
            $expected_hash = hash_hmac('sha256', $parent['id'] . '|' . $parent['email'], $secret_key);
            
            if (hash_equals($expected_hash, $token_hash)) {
                // Persistent token is valid -> Auto-Authenticate Parent!
                $_SESSION['parent_id'] = $parent['id'];
                $_SESSION['parent_name'] = $parent['parent_name'];
                $_SESSION['parent_email'] = $parent['email'];
            }
        }
    }
}

if (!isset($_SESSION['parent_id'])) {
    header("Location: login.php");
    exit();
}

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
                if (!isset($_SESSION['missing_docs_notified'])) {
                    $_SESSION['show_missing_docs_popup'] = true;
                    $_SESSION['missing_docs_notified'] = true;
                }
            }
        }
    }
}
?>
