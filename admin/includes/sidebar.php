<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Sticky Blur Header -->
<div class="mobile-header">
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="Logo">
        <span>ABSS Admin</span>
    </div>
    <button class="hamburger-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Mobile Dimming Background Backdrop Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="sidebar">
    <!-- Mobile Close Circular Button -->
    <button class="close-sidebar-btn" onclick="toggleSidebar()">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="sidebar-brand">
        <img src="../assets/logo.png" alt="Logo">
        <span>ABSS Portal</span>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
        </li>
        <li class="nav-item">
            <a href="students.php" class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students
            </a>
        </li>
        <li class="nav-item dropdown">
            <a href="#" class="nav-link <?php echo in_array($current_page, ['teachers.php', 'teacher_expenses.php', 'teacher_invoices.php']) ? 'active' : ''; ?>" onclick="toggleTeacherMenu(event)">
                <i class="fas fa-chalkboard-teacher"></i> Teacher Management 
                <i class="fas fa-chevron-down" style="float:right; margin-top:5px; font-size: 12px; transition: transform 0.3s;" id="teacherMenuIcon"></i>
            </a>
            <ul class="submenu" id="teacherMenu" style="display: <?php echo in_array($current_page, ['teachers.php', 'teacher_expenses.php', 'teacher_invoices.php']) ? 'block' : 'none'; ?>; padding-left: 20px; list-style: none; margin-top: 5px;">
                <li style="margin-bottom: 5px;">
                    <a href="teachers.php" class="nav-link <?php echo $current_page == 'teachers.php' ? 'active' : ''; ?>" style="padding: 8px 15px; font-size: 0.9em;">
                        <i class="fas fa-user-tie"></i> Teachers
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="teacher_expenses.php" class="nav-link <?php echo $current_page == 'teacher_expenses.php' ? 'active' : ''; ?>" style="padding: 8px 15px; font-size: 0.9em;">
                        <i class="fas fa-receipt"></i> Expense Management
                    </a>
                </li>
                <li>
                    <a href="teacher_invoices.php" class="nav-link <?php echo $current_page == 'teacher_invoices.php' ? 'active' : ''; ?>" style="padding: 8px 15px; font-size: 0.9em;">
                        <i class="fas fa-file-invoice-dollar"></i> Invoice Generation
                    </a>
                </li>
            </ul>
        </li>
        <li class="nav-item">
            <a href="documents.php" class="nav-link <?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Required Documents
            </a>
        </li>
        <li class="nav-item">
            <a href="document_approvals.php" class="nav-link <?php echo $current_page == 'document_approvals.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-double"></i> Review Documents
            </a>
        </li>
        <li class="nav-item">
            <a href="parents.php" class="nav-link <?php echo $current_page == 'parents.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i> Parent Registry
            </a>
        </li>
        <li class="nav-item">
            <a href="fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Fee Ledger
            </a>
        </li>
        <li class="nav-item">
            <a href="expenses.php" class="nav-link <?php echo $current_page == 'expenses.php' ? 'active' : ''; ?>">
                <i class="fas fa-cart-plus"></i> Daily Expenses
            </a>
        </li>
        <li class="nav-item">
            <a href="results.php" class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                <i class="fas fa-award"></i> Test Results
            </a>
        </li>
        <li class="nav-item">
            <a href="inquiries.php" class="nav-link <?php echo $current_page == 'inquiries.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope-open-text"></i> Inquiries
            </a>
        </li>
        <li class="nav-item">
            <a href="tickets.php" class="nav-link <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Helpdesk Tickets
            </a>
        </li>
        <li class="nav-item">
            <a href="admissions.php" class="nav-link <?php echo $current_page == 'admissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Online Admissions
            </a>
        </li>
        <li class="nav-item">
            <a href="schools.php" class="nav-link <?php echo $current_page == 'schools.php' ? 'active' : ''; ?>">
                <i class="fas fa-school"></i> Target Schools
            </a>
        </li>
        <li class="nav-item">
            <a href="notices.php" class="nav-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Notice Board
            </a>
        </li>
        <li class="nav-item">
            <a href="achievers.php" class="nav-link <?php echo $current_page == 'achievers.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i> Hall of Excellence
            </a>
        </li>
        <li class="nav-item">
            <a href="gallery.php" class="nav-link <?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Gallery
            </a>
        </li>
        <li class="nav-item">
            <a href="visitors.php" class="nav-link <?php echo $current_page == 'visitors.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Visitor Analytics
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Web Settings
            </a>
        </li>
    </ul>
    <a href="logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout System
    </a>
</div>

<script>
    // Smooth Mobile Sliding Sidebar Drawer Toggler
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
    
    // Set initial icon rotation based on current page
    document.addEventListener('DOMContentLoaded', function() {
        const menu = document.getElementById('teacherMenu');
        const icon = document.getElementById('teacherMenuIcon');
        if (menu && icon && menu.style.display === 'block') {
            icon.style.transform = 'rotate(180deg)';
        }
    });
</script>
