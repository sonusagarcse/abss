<?php
// admin/includes/sidebar.php - Frosted Glass Admin Navigation Drawer
$current_page = basename($_SERVER['PHP_SELF']);
$base_app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
$admin_url = $base_app_url . '/admin/';
$assets_url = $base_app_url . '/assets/';
$is_notif_page = (strpos($_SERVER['PHP_SELF'], 'notifications') !== false || $current_page == 'notifications.php');
?>
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

    <div style="background: rgba(248, 250, 252, 0.8); border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
        <span style="font-size:0.68rem; color:#94a3b8; font-weight:800; text-transform:uppercase; display:block; margin-bottom:3px;">ACTIVE SESSION</span>
        <span style="font-weight:800; color:var(--portal-dark); font-size:0.9rem; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-shield-alt" style="color:var(--portal-blue); font-size:1rem;"></i>
            Administrator Desk
        </span>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>students.php" class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students
            </a>
        </li>
        <li class="nav-item dropdown">
            <a href="#" class="nav-link <?php echo in_array($current_page, ['teachers.php', 'teacher_expenses.php', 'teacher_invoices.php']) ? 'active' : ''; ?>" onclick="toggleTeacherMenu(event)">
                <i class="fas fa-chalkboard-teacher"></i> Teacher Module 
                <i class="fas fa-chevron-down" style="float:right; margin-top:5px; font-size: 11px; transition: transform 0.3s;" id="teacherMenuIcon"></i>
            </a>
            <ul class="submenu" id="teacherMenu" style="display: <?php echo in_array($current_page, ['teachers.php', 'teacher_expenses.php', 'teacher_invoices.php']) ? 'block' : 'none'; ?>; padding-left: 15px; list-style: none; margin-top: 4px;">
                <li style="margin-bottom: 4px;">
                    <a href="<?php echo $admin_url; ?>teachers.php" class="nav-link <?php echo $current_page == 'teachers.php' ? 'active' : ''; ?>" style="padding: 10px 14px; font-size: 0.88em;">
                        <i class="fas fa-user-tie"></i> Teachers Directory
                    </a>
                </li>
                <li style="margin-bottom: 4px;">
                    <a href="<?php echo $admin_url; ?>teacher_expenses.php" class="nav-link <?php echo $current_page == 'teacher_expenses.php' ? 'active' : ''; ?>" style="padding: 10px 14px; font-size: 0.88em;">
                        <i class="fas fa-receipt"></i> Expense Approvals
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_url; ?>teacher_invoices.php" class="nav-link <?php echo $current_page == 'teacher_invoices.php' ? 'active' : ''; ?>" style="padding: 10px 14px; font-size: 0.88em;">
                        <i class="fas fa-file-invoice-dollar"></i> Salary Invoices
                    </a>
                </li>
            </ul>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>documents.php" class="nav-link <?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Required Documents
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>document_approvals.php" class="nav-link <?php echo $current_page == 'document_approvals.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-double"></i> Document Verification
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>parents.php" class="nav-link <?php echo $current_page == 'parents.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i> Parent Registry
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Fee Ledger
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>student_dues.php" class="nav-link <?php echo $current_page == 'student_dues.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar" style="color: #dc2626;"></i> Student Dues
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>whatsapp.php" class="nav-link <?php echo $current_page == 'whatsapp.php' ? 'active' : ''; ?>">
                <i class="fab fa-whatsapp" style="color: #22c55e;"></i> WhatsApp Hub
            </a>
        </li>

        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>results.php" class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                <i class="fas fa-award"></i> Test Results
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>inquiries.php" class="nav-link <?php echo $current_page == 'inquiries.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope-open-text"></i> Inquiries
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>tickets.php" class="nav-link <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Helpdesk Tickets
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>admissions.php" class="nav-link <?php echo $current_page == 'admissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Online Admissions
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>schools.php" class="nav-link <?php echo $current_page == 'schools.php' ? 'active' : ''; ?>">
                <i class="fas fa-graduation-cap"></i> Coaching Programs
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>notices.php" class="nav-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Notice Board
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>notifications/index.php" class="nav-link <?php echo $is_notif_page ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Push Notifications (FCM)
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>syllabus.php" class="nav-link <?php echo $current_page == 'syllabus.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i> Academic Syllabus
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>assign_groups.php" class="nav-link <?php echo $current_page == 'assign_groups.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-gear"></i> Assign Student Groups
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>achievers.php" class="nav-link <?php echo $current_page == 'achievers.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i> Hall of Excellence
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>gallery.php" class="nav-link <?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Gallery
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>visitors.php" class="nav-link <?php echo $current_page == 'visitors.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Visitor Analytics
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $admin_url; ?>settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Web Settings
            </a>
        </li>
    </ul>

    <a href="<?php echo $admin_url; ?>logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout Admin
    </a>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
    }

    function toggleTeacherMenu(e) {
        e.preventDefault();
        const menu = document.getElementById('teacherMenu');
        const icon = document.getElementById('teacherMenuIcon');
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        } else {
            menu.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const menu = document.getElementById('teacherMenu');
        const icon = document.getElementById('teacherMenuIcon');
        if (menu && icon && menu.style.display === 'block') {
            icon.style.transform = 'rotate(180deg)';
        }
    });
</script>
