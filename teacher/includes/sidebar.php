<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
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
