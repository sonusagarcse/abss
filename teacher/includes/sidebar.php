<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Top Header -->
<div class="mobile-header">
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="Logo">
        <span>ABSS Faculty</span>
    </div>
    <button type="button" class="hamburger-btn" onclick="toggleTeacherSidebar()" aria-label="Open Navigation">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Mobile Dimming Background Backdrop Overlay -->
<div class="sidebar-overlay" id="teacherSidebarOverlay" onclick="toggleTeacherSidebar()"></div>

<div class="sidebar" id="teacherSidebar">
    <!-- Close Circular Button -->
    <button type="button" class="close-sidebar-btn" onclick="toggleTeacherSidebar()" aria-label="Close Navigation">
        <i class="fas fa-times"></i>
    </button>

    <div class="sidebar-brand">
        <img src="../assets/logo.png" alt="Logo">
        <span>ABSS Faculty</span>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="attendance.php" class="nav-link <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
        </li>
        <li class="nav-item">
            <a href="results.php" class="nav-link <?= $current_page == 'results.php' ? 'active' : '' ?>">
                <i class="fas fa-award"></i> Student Marks
            </a>
        </li>
        <li class="nav-item">
            <a href="expenses.php" class="nav-link <?= $current_page == 'expenses.php' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i> Expense Claims
            </a>
        </li>
        <li class="nav-item">
            <a href="invoices.php" class="nav-link <?= $current_page == 'invoices.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i> Salary Invoices
            </a>
        </li>
        <li class="nav-item">
            <a href="notices.php" class="nav-link <?= $current_page == 'notices.php' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i> Notice Board
            </a>
        </li>
    </ul>

    <a href="logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout Faculty
    </a>
</div>

<script>
function toggleTeacherSidebar() {
    var sidebar = document.getElementById('teacherSidebar');
    var overlay = document.getElementById('teacherSidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        if (sidebar.classList.contains('open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}
</script>
