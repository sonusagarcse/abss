<?php
// admin/includes/sidebar.php - Categorized Frosted Glass Admin Navigation Drawer
$current_page = basename($_SERVER['PHP_SELF']);
$base_app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
$admin_url = $base_app_url . '/admin/';
$assets_url = $base_app_url . '/assets/';
$is_notif_page = (strpos($_SERVER['PHP_SELF'], 'notifications') !== false || $current_page == 'notifications.php');

// Define Active Category Groups
$is_academics_active = in_array($current_page, ['students.php', 'attendance.php', 'assign_groups.php', 'results.php', 'syllabus.php']);
$is_finance_active = in_array($current_page, ['fees.php', 'student_dues.php', 'fine_system.php', 'view_bill.php', 'receipt.php']);
$is_teacher_active = in_array($current_page, ['teachers.php', 'teacher_expenses.php', 'teacher_invoices.php', 'print_teacher_invoice.php']);
$is_parents_active = in_array($current_page, ['parents.php', 'documents.php', 'document_approvals.php']);
$is_communication_active = in_array($current_page, ['whatsapp.php', 'notices.php', 'inquiries.php', 'tickets.php']) || $is_notif_page;
$is_portal_active = in_array($current_page, ['admissions.php', 'schools.php', 'achievers.php', 'gallery.php', 'visitors.php', 'settings.php']);
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
</style>

<!-- Mobile Sticky Glass Header -->
<div class="mobile-header">
    <div class="mobile-brand">
        <img src="<?php echo $assets_url; ?>logo.png" alt="Logo">
        <span>ABSS Command Center</span>
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
            <span>ABSS Admin</span>
            <small>Management Portal</small>
        </div>
    </div>

    <div style="background: rgba(248, 250, 252, 0.85); border-radius: 14px; padding: 12px 16px; margin-bottom: 16px; border: 1px solid #e2e8f0;">
        <span style="font-size:0.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase; display:block; margin-bottom:3px;">ACTIVE SESSION</span>
        <span style="font-weight:800; color:var(--portal-dark); font-size:0.88rem; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-shield-alt" style="color:var(--portal-blue); font-size:0.95rem;"></i>
            Administrator Desk
        </span>
    </div>

    <ul class="nav-menu">
        <!-- 1. MAIN OVERVIEW -->
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>

        <span class="nav-section-title">Academic &amp; Accounts</span>

        <!-- 2. STUDENTS & ACADEMICS (5 items) -->
        <li class="nav-item has-submenu <?php echo $is_academics_active ? 'open active-parent' : ''; ?>" id="menuAcademics">
            <div class="submenu-toggle" onclick="toggleCategory('menuAcademics', event)">
                <div class="toggle-content">
                    <i class="fas fa-user-graduate" style="color: #2563eb; width:20px; text-align:center;"></i>
                    <span>Students &amp; Study</span>
                </div>
                <div class="toggle-meta">
                    <span class="count-pill">5</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
            </div>
            <ul class="submenu-list">
                <li>
                    <a href="<?php echo $admin_url; ?>students.php" class="sub-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Students Directory
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>attendance.php" class="sub-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>assign_groups.php" class="sub-link <?php echo $current_page == 'assign_groups.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users-gear"></i> Student Batches / Groups
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>results.php" class="sub-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                        <i class="fas fa-award"></i> Examination Results
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>syllabus.php" class="sub-link <?php echo $current_page == 'syllabus.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book-open"></i> Academic Syllabus
                    </a>
                </li>
            </ul>
        </li>

        <!-- 3. FEE & FINANCE (3 items) -->
        <li class="nav-item has-submenu <?php echo $is_finance_active ? 'open active-parent' : ''; ?>" id="menuFinance">
            <div class="submenu-toggle" onclick="toggleCategory('menuFinance', event)">
                <div class="toggle-content">
                    <i class="fas fa-file-invoice-dollar" style="color: #059669; width:20px; text-align:center;"></i>
                    <span>Fee &amp; Finance</span>
                </div>
                <div class="toggle-meta">
                    <span class="count-pill">3</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
            </div>
            <ul class="submenu-list">
                <li>
                    <a href="<?php echo $admin_url; ?>fees.php" class="sub-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Fee Ledger &amp; Collections
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>student_dues.php" class="sub-link <?php echo $current_page == 'student_dues.php' ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd" style="color: #dc2626;"></i> Student Dues &amp; Fines
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>fine_system.php" class="sub-link <?php echo $current_page == 'fine_system.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sliders-h" style="color: #ea580c;"></i> Fine System (ON / OFF)
                    </a>
                </li>
            </ul>
        </li>

        <!-- 4. TEACHERS & FACULTY (3 items) -->
        <li class="nav-item has-submenu <?php echo $is_teacher_active ? 'open active-parent' : ''; ?>" id="menuTeachers">
            <div class="submenu-toggle" onclick="toggleCategory('menuTeachers', event)">
                <div class="toggle-content">
                    <i class="fas fa-chalkboard-teacher" style="color: #7c3aed; width:20px; text-align:center;"></i>
                    <span>Teachers &amp; Staff</span>
                </div>
                <div class="toggle-meta">
                    <span class="count-pill">3</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
            </div>
            <ul class="submenu-list">
                <li>
                    <a href="<?php echo $admin_url; ?>teachers.php" class="sub-link <?php echo $current_page == 'teachers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Teachers Directory
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>teacher_expenses.php" class="sub-link <?php echo $current_page == 'teacher_expenses.php' ? 'active' : ''; ?>">
                        <i class="fas fa-receipt"></i> Expense Approvals
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>teacher_invoices.php" class="sub-link <?php echo $current_page == 'teacher_invoices.php' ? 'active' : ''; ?>">
                        <i class="fas fa-money-check-alt"></i> Salary Invoices
                    </a>
                </li>
            </ul>
        </li>

        <span class="nav-section-title">Administration</span>

        <!-- 5. PARENTS & VERIFICATION (3 items) -->
        <li class="nav-item has-submenu <?php echo $is_parents_active ? 'open active-parent' : ''; ?>" id="menuParents">
            <div class="submenu-toggle" onclick="toggleCategory('menuParents', event)">
                <div class="toggle-content">
                    <i class="fas fa-user-friends" style="color: #0284c7; width:20px; text-align:center;"></i>
                    <span>Parents &amp; Docs</span>
                </div>
                <div class="toggle-meta">
                    <span class="count-pill">3</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
            </div>
            <ul class="submenu-list">
                <li>
                    <a href="<?php echo $admin_url; ?>parents.php" class="sub-link <?php echo $current_page == 'parents.php' ? 'active' : ''; ?>">
                        <i class="fas fa-address-book"></i> Parent Registry
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>documents.php" class="sub-link <?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i> Required Documents
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>document_approvals.php" class="sub-link <?php echo $current_page == 'document_approvals.php' ? 'active' : ''; ?>">
                        <i class="fas fa-check-double"></i> Document Verification
                    </a>
                </li>
            </ul>
        </li>

        <!-- 6. COMMUNICATION & ALERTS (5 items) -->
        <li class="nav-item has-submenu <?php echo $is_communication_active ? 'open active-parent' : ''; ?>" id="menuCommunication">
            <div class="submenu-toggle" onclick="toggleCategory('menuCommunication', event)">
                <div class="toggle-content">
                    <i class="fas fa-paper-plane" style="color: #ea580c; width:20px; text-align:center;"></i>
                    <span>Communication</span>
                </div>
                <div class="toggle-meta">
                    <span class="count-pill">5</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
            </div>
            <ul class="submenu-list">
                <li>
                    <a href="<?php echo $admin_url; ?>whatsapp.php" class="sub-link <?php echo $current_page == 'whatsapp.php' ? 'active' : ''; ?>">
                        <i class="fab fa-whatsapp" style="color: #22c55e;"></i> WhatsApp Hub
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>notifications/index.php" class="sub-link <?php echo $is_notif_page ? 'active' : ''; ?>">
                        <i class="fas fa-bell" style="color: #eab308;"></i> App Push Notifications
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>notices.php" class="sub-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn"></i> Notice Board
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>inquiries.php" class="sub-link <?php echo $current_page == 'inquiries.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope-open-text"></i> Inquiries
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>tickets.php" class="sub-link <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i> Helpdesk Tickets
                    </a>
                </li>
            </ul>
        </li>

        <!-- 7. PORTAL, CONTENT & SETTINGS (6 items) -->
        <li class="nav-item has-submenu <?php echo $is_portal_active ? 'open active-parent' : ''; ?>" id="menuPortal">
            <div class="submenu-toggle" onclick="toggleCategory('menuPortal', event)">
                <div class="toggle-content">
                    <i class="fas fa-globe" style="color: #64748b; width:20px; text-align:center;"></i>
                    <span>Portal &amp; System</span>
                </div>
                <div class="toggle-meta">
                    <span class="count-pill">6</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
            </div>
            <ul class="submenu-list">
                <li>
                    <a href="<?php echo $admin_url; ?>admissions.php" class="sub-link <?php echo $current_page == 'admissions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> Online Admissions
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>schools.php" class="sub-link <?php echo $current_page == 'schools.php' ? 'active' : ''; ?>">
                        <i class="fas fa-graduation-cap"></i> Coaching Programs
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>achievers.php" class="sub-link <?php echo $current_page == 'achievers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trophy" style="color:#eab308;"></i> Hall of Excellence
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>gallery.php" class="sub-link <?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Media Gallery
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>visitors.php" class="sub-link <?php echo $current_page == 'visitors.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Visitor Analytics
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>settings.php" class="sub-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sliders-h"></i> Web Settings
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <a href="<?php echo $admin_url; ?>logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout Admin
    </a>
</div>

<script>
    // Universal Mobile Sidebar Toggle
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
    }

    // Category Accordion Dropdown Toggle
    function toggleCategory(categoryId, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const item = document.getElementById(categoryId);
        if (!item) return;

        const isCurrentlyOpen = item.classList.contains('open');

        // Optional: Close other categories for true accordion effect (or keep open if desired)
        // We will toggle the clicked one smoothly
        if (isCurrentlyOpen) {
            item.classList.remove('open');
        } else {
            item.classList.add('open');
        }
    }

    // Backward compatibility for teacher menu if referenced elsewhere
    function toggleTeacherMenu(e) {
        toggleCategory('menuTeachers', e);
    }
</script>
