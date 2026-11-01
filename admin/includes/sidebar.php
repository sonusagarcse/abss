<?php
// admin/includes/sidebar.php - Dynamic Multi-Role Navigation Drawer
$current_page = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
$base_app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
$admin_url = $base_app_url . '/admin/';
$assets_url = $base_app_url . '/assets/';
$is_notif_page = (strpos($_SERVER['PHP_SELF'], 'notifications') !== false || $current_page == 'notifications');

// Role and Permission Gatekeeper Setup
$is_counselor_session = !isset($_SESSION['admin_id']) && isset($_SESSION['counselor_id']);
$c_perms = (isset($_SESSION['counselor_permissions']) && is_array($_SESSION['counselor_permissions'])) ? $_SESSION['counselor_permissions'] : [];

$can_view = function($module_key) use ($is_counselor_session, $c_perms) {
    if (!$is_counselor_session) return true; // Super admin sees everything
    return in_array($module_key, $c_perms, true);
};

// 1. Academics Group Items
$show_students = $can_view('students');
$show_attendance = $can_view('attendance');
$show_assign_groups = $can_view('students');
$show_results = $can_view('results');
$show_syllabus = $can_view('syllabus');
$has_academics = $show_students || $show_attendance || $show_assign_groups || $show_results || $show_syllabus;
$academics_count = ($show_students?1:0) + ($show_attendance?1:0) + ($show_assign_groups?1:0) + ($show_results?1:0) + ($show_syllabus?1:0);

// 2. Finance Group Items
$show_fees = $can_view('fees');
$show_dues = $can_view('fees');
$show_fine_sys = !$is_counselor_session; // Only super admin manages fine system switches
$has_finance = $show_fees || $show_dues || $show_fine_sys;
$finance_count = ($show_fees?1:0) + ($show_dues?1:0) + ($show_fine_sys?1:0);

// 3. Teachers & Staff Group Items
$show_teachers = $can_view('teachers');
$show_t_attendance = $can_view('attendance');
$show_t_expenses = !$is_counselor_session;
$show_t_invoices = !$is_counselor_session;
$show_counselors_mgr = !$is_counselor_session; // Only super admin manages counselor accounts
$has_teachers = $show_teachers || $show_t_attendance || $show_t_expenses || $show_t_invoices || $show_counselors_mgr;
$teachers_count = ($show_teachers?1:0) + ($show_t_attendance?1:0) + ($show_t_expenses?1:0) + ($show_t_invoices?1:0) + ($show_counselors_mgr?1:0);

// 4. Parents & Docs Group Items
$show_parents = $can_view('students') || $can_view('admissions');
$show_documents = $can_view('admissions');
$show_approvals = $can_view('admissions');
$has_parents = $show_parents || $show_documents || $show_approvals;
$parents_count = ($show_parents?1:0) + ($show_documents?1:0) + ($show_approvals?1:0);

// 5. Communication Group Items
$show_whatsapp = $can_view('whatsapp');
$show_notifications = $can_view('notices');
$show_notices = $can_view('notices');
$show_inquiries = $can_view('inquiries');
$show_tickets = $can_view('tickets');
$has_comm = $show_whatsapp || $show_notifications || $show_notices || $show_inquiries || $show_tickets;
$comm_count = ($show_whatsapp?1:0) + ($show_notifications?1:0) + ($show_notices?1:0) + ($show_inquiries?1:0) + ($show_tickets?1:0);

// 6. Portal & System Group Items
$show_admissions = $can_view('admissions');
$show_schools = $can_view('schools');
$show_achievers = $can_view('achievers');
$show_gallery = $can_view('gallery');
$show_visitors = $can_view('visitors');
$show_settings = !$is_counselor_session; // Only super admin can access site/db settings
$has_portal = $show_admissions || $show_schools || $show_achievers || $show_gallery || $show_visitors || $show_settings;
$portal_count = ($show_admissions?1:0) + ($show_schools?1:0) + ($show_achievers?1:0) + ($show_gallery?1:0) + ($show_visitors?1:0) + ($show_settings?1:0);

// Define Active Category Highlights
$is_academics_active = in_array($current_page, ['students', 'attendance', 'assign_groups', 'results', 'syllabus']);
$is_finance_active = in_array($current_page, ['fees', 'student_dues', 'fine_system', 'view_bill', 'receipt']);
$is_teacher_active = in_array($current_page, ['teachers', 'teacher_attendance', 'teacher_expenses', 'teacher_invoices', 'print_teacher_invoice', 'counselors']);
$is_parents_active = in_array($current_page, ['parents', 'documents', 'document_approvals']);
$is_communication_active = in_array($current_page, ['whatsapp', 'notices', 'inquiries', 'tickets']) || $is_notif_page;
$is_portal_active = in_array($current_page, ['admissions', 'schools', 'achievers', 'gallery', 'visitors', 'settings']);
?>

<style>
    /* Scoped Sidebar Accordion Styling */
    .nav-section-title {
        font-size: 0.65rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 10px 14px 4px;
        margin-top: 4px;
        display: block;
    }
    .has-submenu {
        margin-bottom: 4px;
    }
    .submenu-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 16px;
        border-radius: var(--radius-md, 12px);
        color: #475569;
        font-weight: 700;
        text-decoration: none;
        font-size: 0.90rem;
        cursor: pointer;
        user-select: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .submenu-toggle:hover {
        background: rgba(239, 246, 255, 0.85);
        color: var(--portal-blue, #2563eb);
        transform: translateX(3px);
    }
    .has-submenu.active-parent > .submenu-toggle {
        background: rgba(37, 99, 235, 0.09);
        color: var(--portal-blue, #2563eb);
        font-weight: 800;
    }
    .toggle-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .toggle-meta {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .count-pill {
        font-size: 0.68rem;
        font-weight: 800;
        background: #f1f5f9;
        color: #64748b;
        padding: 2px 7px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    .has-submenu.active-parent .count-pill {
        background: rgba(37, 99, 235, 0.15);
        color: var(--portal-blue, #2563eb);
    }
    .submenu-arrow {
        font-size: 0.72rem;
        color: #94a3b8;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .has-submenu.open .submenu-arrow {
        transform: rotate(180deg);
        color: var(--portal-blue, #2563eb);
    }
    .submenu-list {
        list-style: none;
        padding: 4px 0 6px 12px;
        margin: 2px 0 6px 18px;
        border-left: 2px solid #e2e8f0;
        display: none;
    }
    .has-submenu.open .submenu-list {
        display: block;
    }
    .sub-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        color: #475569;
        text-decoration: none;
        font-size: 0.84rem;
        font-weight: 600;
        transition: all 0.2s ease;
        margin-bottom: 2px;
    }
    .sub-link:hover {
        background: rgba(239, 246, 255, 0.9);
        color: var(--portal-blue, #2563eb);
        transform: translateX(3px);
    }
    .sub-link.active {
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 800;
    }
    .sub-link i {
        font-size: 0.82rem;
        width: 16px;
        text-align: center;
        opacity: 0.85;
    }
    .sub-link.active i {
        color: #0284c7;
        opacity: 1;
    }
    @media print {
        .mobile-header,
        .mobile-bottom-nav,
        #adminBottomNav,
        .sidebar,
        .sidebar-overlay,
        .close-sidebar-btn,
        .hamburger-btn {
            display: none !important;
            visibility: hidden !important;
        }
    }
</style>

<!-- Mobile Sticky Glass Header -->
<div class="mobile-header">
    <div class="mobile-brand" onclick="window.location.href='<?php echo $admin_url; ?>dashboard'" style="cursor:pointer;" title="Dashboard">
        <img src="<?php echo $assets_url; ?>logo.png" alt="Logo">
        <span><?php echo $is_counselor_session ? 'ABSS Counselor Desk' : 'ABSS Command Center'; ?></span>
    </div>
    <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Open Navigation">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Mobile Dimming Background Backdrop Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="sidebar">
    <!-- Mobile Close Circular Button -->
    <button class="close-sidebar-btn" onclick="toggleSidebar()" aria-label="Close Navigation">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="sidebar-brand">
        <img src="<?php echo $assets_url; ?>logo.png" alt="Logo">
        <div>
            <span>ABSS Portal</span>
            <small><?php echo $is_counselor_session ? 'Counselor Console' : 'Management Portal'; ?></small>
        </div>
    </div>

    <!-- Active Session Card -->
    <?php if ($is_counselor_session): ?>
        <div style="background: rgba(240, 253, 250, 0.95); border-radius: 14px; padding: 12px 14px; margin-bottom: 16px; border: 1px solid #ccfbf1;">
            <span style="font-size:0.62rem; color:#0d9488; font-weight:800; text-transform:uppercase; display:block; margin-bottom:3px; letter-spacing:0.04em;">COUNSELOR DESK</span>
            <span style="font-weight:800; color:#0f172a; font-size:0.86rem; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-user-tie" style="color:#0d9488; font-size:0.95rem;"></i>
                <?php echo htmlspecialchars($_SESSION['counselor_name'] ?? 'Counselor'); ?>
            </span>
            <small style="color: #64748b; font-size: 0.72rem; display: block; margin-top: 2px;">
                <?php echo htmlspecialchars($_SESSION['counselor_designation'] ?? 'Admission Counselor'); ?>
            </small>
        </div>
    <?php else: ?>
        <div style="background: rgba(248, 250, 252, 0.85); border-radius: 14px; padding: 12px 16px; margin-bottom: 16px; border: 1px solid #e2e8f0;">
            <span style="font-size:0.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase; display:block; margin-bottom:3px;">ACTIVE SESSION</span>
            <span style="font-weight:800; color:var(--portal-dark); font-size:0.88rem; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-shield-alt" style="color:var(--portal-blue); font-size:0.95rem;"></i>
                Administrator Desk
            </span>
        </div>
    <?php endif; ?>

    <ul class="nav-menu">
        <!-- 1. MAIN OVERVIEW (Always Available) -->
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>

        <!-- 2. STUDENTS & ACADEMICS -->
        <?php if ($has_academics): ?>
            <span class="nav-section-title">Academic &amp; Accounts</span>
            <li class="nav-item has-submenu <?php echo $is_academics_active ? 'open active-parent' : ''; ?>" id="menuAcademics">
                <div class="submenu-toggle" onclick="toggleCategory('menuAcademics', event)">
                    <div class="toggle-content">
                        <i class="fas fa-user-graduate" style="color: #2563eb; width:20px; text-align:center;"></i>
                        <span>Students &amp; Study</span>
                    </div>
                    <div class="toggle-meta">
                        <span class="count-pill"><?php echo $academics_count; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </div>
                </div>
                <ul class="submenu-list">
                    <?php if ($show_students): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>students" class="sub-link <?php echo $current_page == 'students' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i> Students Directory
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_attendance): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>attendance" class="sub-link <?php echo $current_page == 'attendance' ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-check"></i> Attendance
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_assign_groups): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>assign_groups" class="sub-link <?php echo $current_page == 'assign_groups' ? 'active' : ''; ?>">
                                <i class="fas fa-users-gear"></i> Student Batches / Groups
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_results): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>results" class="sub-link <?php echo $current_page == 'results' ? 'active' : ''; ?>">
                                <i class="fas fa-award"></i> Examination Results
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_syllabus): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>syllabus" class="sub-link <?php echo $current_page == 'syllabus' ? 'active' : ''; ?>">
                                <i class="fas fa-book-open"></i> Academic Syllabus
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 3. FEE & FINANCE -->
        <?php if ($has_finance): ?>
            <li class="nav-item has-submenu <?php echo $is_finance_active ? 'open active-parent' : ''; ?>" id="menuFinance">
                <div class="submenu-toggle" onclick="toggleCategory('menuFinance', event)">
                    <div class="toggle-content">
                        <i class="fas fa-file-invoice-dollar" style="color: #059669; width:20px; text-align:center;"></i>
                        <span>Fee &amp; Finance</span>
                    </div>
                    <div class="toggle-meta">
                        <span class="count-pill"><?php echo $finance_count; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </div>
                </div>
                <ul class="submenu-list">
                    <?php if ($show_fees): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>fees" class="sub-link <?php echo $current_page == 'fees' ? 'active' : ''; ?>">
                                <i class="fas fa-history"></i> Fee Ledger &amp; Collections
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_dues): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>student_dues" class="sub-link <?php echo $current_page == 'student_dues' ? 'active' : ''; ?>">
                                <i class="fas fa-hand-holding-usd" style="color: #dc2626;"></i> Student Dues &amp; Fines
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_fine_sys): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>fine_system" class="sub-link <?php echo $current_page == 'fine_system' ? 'active' : ''; ?>">
                                <i class="fas fa-sliders-h" style="color: #ea580c;"></i> Fine System (ON / OFF)
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 4. TEACHERS & FACULTY -->
        <?php if ($has_teachers): ?>
            <li class="nav-item has-submenu <?php echo $is_teacher_active ? 'open active-parent' : ''; ?>" id="menuTeachers">
                <div class="submenu-toggle" onclick="toggleCategory('menuTeachers', event)">
                    <div class="toggle-content">
                        <i class="fas fa-chalkboard-teacher" style="color: #7c3aed; width:20px; text-align:center;"></i>
                        <span>Teachers &amp; Staff</span>
                    </div>
                    <div class="toggle-meta">
                        <span class="count-pill"><?php echo $teachers_count; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </div>
                </div>
                <ul class="submenu-list">
                    <?php if ($show_teachers): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>teachers" class="sub-link <?php echo $current_page == 'teachers' ? 'active' : ''; ?>">
                                <i class="fas fa-user-tie"></i> Teachers Directory
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_t_attendance): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>teacher_attendance" class="sub-link <?php echo $current_page == 'teacher_attendance' ? 'active' : ''; ?>">
                                <i class="fas fa-user-check"></i> Staff Attendance
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_t_expenses): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>teacher_expenses" class="sub-link <?php echo $current_page == 'teacher_expenses' ? 'active' : ''; ?>">
                                <i class="fas fa-receipt"></i> Expense Approvals
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_t_invoices): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>teacher_invoices" class="sub-link <?php echo $current_page == 'teacher_invoices' ? 'active' : ''; ?>">
                                <i class="fas fa-money-check-alt"></i> Salary Invoices
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_counselors_mgr): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>counselors" class="sub-link <?php echo $current_page == 'counselors' ? 'active' : ''; ?>">
                                <i class="fas fa-user-shield" style="color: #0d9488;"></i> Counselors &amp; Roles
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($has_parents || $has_comm || $has_portal): ?>
            <span class="nav-section-title">Administration</span>
        <?php endif; ?>

        <!-- 5. PARENTS & VERIFICATION -->
        <?php if ($has_parents): ?>
            <li class="nav-item has-submenu <?php echo $is_parents_active ? 'open active-parent' : ''; ?>" id="menuParents">
                <div class="submenu-toggle" onclick="toggleCategory('menuParents', event)">
                    <div class="toggle-content">
                        <i class="fas fa-user-friends" style="color: #0284c7; width:20px; text-align:center;"></i>
                        <span>Parents &amp; Docs</span>
                    </div>
                    <div class="toggle-meta">
                        <span class="count-pill"><?php echo $parents_count; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </div>
                </div>
                <ul class="submenu-list">
                    <?php if ($show_parents): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>parents" class="sub-link <?php echo $current_page == 'parents' ? 'active' : ''; ?>">
                                <i class="fas fa-address-book"></i> Parent Registry
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_documents): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>documents" class="sub-link <?php echo $current_page == 'documents' ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt"></i> Required Documents
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_approvals): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>document_approvals" class="sub-link <?php echo $current_page == 'document_approvals' ? 'active' : ''; ?>">
                                <i class="fas fa-check-double"></i> Document Verification
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 6. COMMUNICATION & ALERTS -->
        <?php if ($has_comm): ?>
            <li class="nav-item has-submenu <?php echo $is_communication_active ? 'open active-parent' : ''; ?>" id="menuCommunication">
                <div class="submenu-toggle" onclick="toggleCategory('menuCommunication', event)">
                    <div class="toggle-content">
                        <i class="fas fa-paper-plane" style="color: #ea580c; width:20px; text-align:center;"></i>
                        <span>Communication</span>
                    </div>
                    <div class="toggle-meta">
                        <span class="count-pill"><?php echo $comm_count; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </div>
                </div>
                <ul class="submenu-list">
                    <?php if ($show_whatsapp): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>whatsapp" class="sub-link <?php echo $current_page == 'whatsapp' ? 'active' : ''; ?>">
                                <i class="fab fa-whatsapp" style="color: #22c55e;"></i> WhatsApp Hub
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_notifications): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>notifications" class="sub-link <?php echo $is_notif_page ? 'active' : ''; ?>">
                                <i class="fas fa-bell" style="color: #eab308;"></i> App Push Notifications
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_notices): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>notices" class="sub-link <?php echo $current_page == 'notices' ? 'active' : ''; ?>">
                                <i class="fas fa-bullhorn"></i> Notice Board
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_inquiries): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>inquiries" class="sub-link <?php echo $current_page == 'inquiries' ? 'active' : ''; ?>">
                                <i class="fas fa-envelope-open-text"></i> Inquiries
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_tickets): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>tickets" class="sub-link <?php echo $current_page == 'tickets' ? 'active' : ''; ?>">
                                <i class="fas fa-ticket-alt"></i> Helpdesk Tickets
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 7. PORTAL, CONTENT & SETTINGS -->
        <?php if ($has_portal): ?>
            <li class="nav-item has-submenu <?php echo $is_portal_active ? 'open active-parent' : ''; ?>" id="menuPortal">
                <div class="submenu-toggle" onclick="toggleCategory('menuPortal', event)">
                    <div class="toggle-content">
                        <i class="fas fa-globe" style="color: #64748b; width:20px; text-align:center;"></i>
                        <span>Portal &amp; System</span>
                    </div>
                    <div class="toggle-meta">
                        <span class="count-pill"><?php echo $portal_count; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </div>
                </div>
                <ul class="submenu-list">
                    <?php if ($show_admissions): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>admissions" class="sub-link <?php echo $current_page == 'admissions' ? 'active' : ''; ?>">
                                <i class="fas fa-user-plus"></i> Online Admissions
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_schools): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>schools" class="sub-link <?php echo $current_page == 'schools' ? 'active' : ''; ?>">
                                <i class="fas fa-graduation-cap"></i> Coaching Programs
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_achievers): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>achievers" class="sub-link <?php echo $current_page == 'achievers' ? 'active' : ''; ?>">
                                <i class="fas fa-trophy" style="color:#eab308;"></i> Hall of Excellence
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_gallery): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>gallery" class="sub-link <?php echo $current_page == 'gallery' ? 'active' : ''; ?>">
                                <i class="fas fa-images"></i> Media Gallery
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_visitors): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>visitors" class="sub-link <?php echo $current_page == 'visitors' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line"></i> Visitor Analytics
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($show_settings): ?>
                        <li>
                            <a href="<?php echo $admin_url; ?>settings" class="sub-link <?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                                <i class="fas fa-sliders-h"></i> Web Settings
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- Logout Link -->
        <li class="nav-item" style="margin-top: 15px;">
            <?php if ($is_counselor_session): ?>
                <a href="<?php echo $base_app_url; ?>/counselor/logout.php" class="nav-link logout-link" onclick="return confirm('Log out of Counselor Desk?')">
                    <i class="fas fa-sign-out-alt"></i> Logout Counselor
                </a>
            <?php else: ?>
                <a href="<?php echo $admin_url; ?>logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to securely end your administrative session?')">
                    <i class="fas fa-sign-out-alt"></i> Secure Logout
                </a>
            <?php endif; ?>
        </li>
    </ul>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar) {
            sidebar.classList.toggle('open');
            if (overlay) {
                overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
            }
        }
    }

    function toggleCategory(categoryId, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const item = document.getElementById(categoryId);
        if (item) {
            item.classList.toggle('open');
        }
    }
</script>
