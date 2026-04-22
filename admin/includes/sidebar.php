<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
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
        <li class="nav-item">
            <a href="fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Fee Ledger
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
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Web Settings
            </a>
        </li>
    </ul>
    <a href="logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout System
    </a>
</div>
