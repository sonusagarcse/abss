<?php
// admin/includes/auth.php - Multi-Role Session Authorization & Granular Module Gatekeeper
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../config/db.php';

$conn = getDB();

// 1. Auto-restore sessions from persistent cookies
verify_and_restore_admin_session();
verify_and_restore_counselor_session();

$is_admin = isset($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] > 0;
$is_counselor = !$is_admin && isset($_SESSION['counselor_id']) && (int)$_SESSION['counselor_id'] > 0;

if (!$is_admin && !$is_counselor) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $login_path = (strpos($host, 'localhost') !== false) ? '/abss/admin/login' : '/admin/login';
    header("Location: $login_path");
    exit();
}

// 2. If Counselor: verify account status & granular module permissions in real time
if ($is_counselor) {
    $cid = (int)$_SESSION['counselor_id'];
    $stmt = $conn->prepare("SELECT id, name, email, designation, permissions, status FROM counselors WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $c_row = $stmt->get_result()->fetch_assoc();

    if (!$c_row || $c_row['status'] !== 'active') {
        unset($_SESSION['counselor_id']);
        unset($_SESSION['counselor_name']);
        unset($_SESSION['counselor_email']);
        unset($_SESSION['counselor_permissions']);
        clear_counselor_remember_cookie();
        header("Location: ../counselor/login.php?error=inactive");
        exit();
    }

    $c_perms = !empty($c_row['permissions']) ? json_decode($c_row['permissions'], true) : [];
    if (!is_array($c_perms)) $c_perms = [];
    $_SESSION['counselor_permissions'] = $c_perms;
    $_SESSION['counselor_name'] = $c_row['name'];
    $_SESSION['counselor_email'] = $c_row['email'];
    $_SESSION['counselor_designation'] = $c_row['designation'] ?: 'Admission Counselor';

    // Current page being accessed
    $current_page = str_replace('.php', '', basename($_SERVER['PHP_SELF']));

    // Pages strictly restricted to Super Admin
    $super_admin_only = ['counselors', 'settings', 'fine_system', 'billing_dry_run'];
    if (in_array($current_page, $super_admin_only, true)) {
        show_counselor_denied_screen($current_page);
        exit();
    }

    // Map admin page names to catalog module keys
    $page_module_map = [
        'dashboard' => 'dashboard',
        'students' => 'students',
        'export_students_excel' => 'students',
        'print_students' => 'students',
        'bulk_print' => 'students',
        'assign_groups' => 'students',
        'attendance' => 'attendance',
        'results' => 'results',
        'syllabus' => 'syllabus',
        'fees' => 'fees',
        'view_bill' => 'fees',
        'receipt' => 'fees',
        'student_dues' => 'fees',
        'ajax_send_due_email' => 'fees',
        'student_addons' => 'fees',
        'teachers' => 'teachers',
        'teacher_attendance' => 'attendance',
        'teacher_expenses' => 'teachers',
        'teacher_invoices' => 'teachers',
        'print_teacher_invoice' => 'teachers',
        'parents' => 'students',
        'documents' => 'admissions',
        'document_approvals' => 'admissions',
        'whatsapp' => 'whatsapp',
        'notifications' => 'notices',
        'notices' => 'notices',
        'inquiries' => 'inquiries',
        'tickets' => 'tickets',
        'admissions' => 'admissions',
        'schools' => 'schools',
        'achievers' => 'achievers',
        'gallery' => 'gallery',
        'visitors' => 'visitors'
    ];

    $required_module = $page_module_map[$current_page] ?? $current_page;
    if ($required_module !== 'dashboard' && !in_array($required_module, $c_perms, true)) {
        show_counselor_denied_screen($current_page, $required_module);
        exit();
    }
}

/**
 * Render friendly Access Denied screen when a Counselor visits an unauthorized admin page
 */
function show_counselor_denied_screen($page_name, $module_key = null) {
    http_response_code(403);
    $catalog = function_exists('get_counselor_modules_catalog') ? get_counselor_modules_catalog() : [];
    $label = isset($catalog[$module_key]['label']) ? $catalog[$module_key]['label'] : ucfirst(str_replace('_', ' ', $page_name));
    $icon = isset($catalog[$module_key]['icon']) ? $catalog[$module_key]['icon'] : 'fas fa-lock';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Restricted | ABSS Counselor Desk</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
            body { background: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .denied-card { background: #ffffff; border-radius: 24px; border: 1px solid #e2e8f0; padding: 44px; max-width: 520px; width: 100%; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.04); }
            .denied-icon { width: 76px; height: 76px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 20px; }
            h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 10px; }
            p { color: #64748b; font-size: 0.92rem; line-height: 1.55; margin-bottom: 24px; }
            .module-badge { display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 8px 18px; border-radius: 50px; font-weight: 800; color: #334155; font-size: 0.88rem; margin-bottom: 20px; border: 1px solid #e2e8f0; }
            .btn { display: inline-flex; align-items: center; gap: 8px; background: #0d9488; color: #fff; padding: 12px 26px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.92rem; transition: background 0.2s; }
            .btn:hover { background: #0f766e; }
        </style>
    </head>
    <body>
        <div class="denied-card">
            <div class="denied-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Module Access Restricted</h1>
            <div class="module-badge">
                <i class="<?php echo $icon; ?>"></i> <?php echo htmlspecialchars($label); ?>
            </div>
            <p>
                The administrator has not granted access to this module for your counselor account.
                If you require access to this section, please contact the Institute Administrator.
            </p>
            <a href="dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>
    </body>
    </html>
    <?php
}
?>
