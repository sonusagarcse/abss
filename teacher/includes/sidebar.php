<?php
$current_page = basename($_SERVER['PHP_SELF']);
$faculty_name = $_SESSION['teacher_name'] ?? 'Faculty Member';
?>
<!-- Mobile Top Navigation Bar -->
<div class="mobile-header">
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="Logo">
        <div>
            <span>ABSS Faculty</span>
            <small style="display:block; font-size:0.65rem; color:var(--teacher-purple); font-weight:800; text-transform:uppercase;">Portal</small>
        </div>
    </div>
    <button type="button" class="hamburger-btn" onclick="toggleTeacherSidebar()" aria-label="Open Navigation">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav">
    <ul>
        <li>
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Overview</span>
            </a>
        </li>
        <li>
            <a href="attendance.php" class="<?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
        </li>
        <li>
            <a href="results.php" class="<?= $current_page == 'results.php' ? 'active' : '' ?>">
                <i class="fas fa-award"></i>
                <span>Marks</span>
            </a>
        </li>
        <li>
            <a href="expenses.php" class="<?= $current_page == 'expenses.php' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i>
                <span>Expenses</span>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" onclick="toggleTeacherSidebar()">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Mobile Dimming Backdrop Overlay -->
<div class="sidebar-overlay" id="teacherSidebarOverlay" onclick="toggleTeacherSidebar()"></div>

<!-- Glassmorphic Sidebar Drawer -->
<div class="sidebar" id="teacherSidebar">
    <!-- Close Mobile Button -->
    <button type="button" class="close-sidebar-btn" onclick="toggleTeacherSidebar()" aria-label="Close Navigation">
        <i class="fas fa-times"></i>
    </button>

    <div class="sidebar-brand">
        <img src="../assets/logo.png" alt="Logo">
        <div>
            <span>ABSS Faculty</span>
            <small>lok kala vikas manch</small>
        </div>
    </div>

    <!-- Active Faculty User Card Badge -->
    <div style="background: rgba(124, 58, 237, 0.08); padding: 12px 14px; border-radius: 16px; margin-bottom: 25px; border: 1px solid rgba(124, 58, 237, 0.15); display: flex; align-items: center; gap: 12px;">
        <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark)); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0;">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div style="overflow: hidden;">
            <div style="font-weight: 800; color: var(--teacher-dark); font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($faculty_name) ?></div>
            <div style="font-size: 0.72rem; color: #166534; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e; display: inline-block;"></span> Active Session
            </div>
        </div>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="attendance.php" class="nav-link <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i> Student Attendance
            </a>
        </li>
        <li class="nav-item">
            <a href="results.php" class="nav-link <?= $current_page == 'results.php' ? 'active' : '' ?>">
                <i class="fas fa-award"></i> Examination Marks
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
