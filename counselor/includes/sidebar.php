<?php
// counselor/includes/sidebar.php - Dynamic Module-Permitted Navigation Drawer
$current_page = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
$base_app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss';
$counselor_url = $base_app_url . '/counselor/';
$assets_url = $base_app_url . '/assets/';

$catalog = get_counselor_modules_catalog();
$user_perms = isset($counselor_permissions) && is_array($counselor_permissions) ? $counselor_permissions : [];
$counselor_name = $_SESSION['counselor_name'] ?? 'Counselor';
$counselor_desig = $_SESSION['counselor_designation'] ?? 'Admission Counselor';
?>

<!-- Mobile Sticky Glass Header -->
<div class="mobile-header">
    <div class="mobile-brand" onclick="window.location.href='<?php echo $counselor_url; ?>dashboard'" style="cursor:pointer;" title="Counselor Dashboard">
        <img src="<?php echo $assets_url; ?>logo.png" alt="Logo">
        <span>ABSS Counselor</span>
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
            <small>Counselor Console</small>
        </div>
    </div>

    <!-- Active Session Pill -->
    <div style="background: rgba(240, 253, 250, 0.9); border-radius: 14px; padding: 12px 14px; margin-bottom: 16px; border: 1px solid #ccfbf1;">
        <span style="font-size:0.62rem; color:#0d9488; font-weight:800; text-transform:uppercase; display:block; margin-bottom:3px; letter-spacing:0.04em;">COUNSELOR DESK</span>
        <span style="font-weight:800; color:#0f172a; font-size:0.86rem; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-user-tie" style="color:#0d9488; font-size:0.95rem;"></i>
            <?php echo htmlspecialchars($counselor_name); ?>
        </span>
        <small style="color: #64748b; font-size: 0.72rem; display: block; margin-top: 2px;">
            <?php echo htmlspecialchars($counselor_desig); ?>
        </small>
    </div>

    <ul class="nav-menu">
        <!-- 1. MAIN DASHBOARD (Always Visible) -->
        <li class="nav-item">
            <a href="<?php echo $counselor_url; ?>dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- 2. ADMISSION LEADS & CONSULTATIONS -->
        <?php 
        $has_lead_modules = in_array('inquiries', $user_perms) || in_array('admissions', $user_perms) || in_array('visitors', $user_perms);
        if ($has_lead_modules): 
        ?>
            <span class="nav-section-title">Leads &amp; Admissions</span>
            
            <?php if (in_array('inquiries', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>inquiries" class="nav-link <?php echo $current_page == 'inquiries' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope-open-text" style="color: #2563eb;"></i>
                        <span>Admission Inquiries</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('admissions', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>admissions" class="nav-link <?php echo $current_page == 'admissions' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus" style="color: #059669;"></i>
                        <span>Online Admissions</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('visitors', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>visitors" class="nav-link <?php echo $current_page == 'visitors' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line" style="color: #ea580c;"></i>
                        <span>Campus Visitors Log</span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 3. STUDENTS & ACADEMICS -->
        <?php 
        $has_academic_modules = in_array('students', $user_perms) || in_array('attendance', $user_perms) || in_array('results', $user_perms) || in_array('syllabus', $user_perms) || in_array('schools', $user_perms) || in_array('achievers', $user_perms);
        if ($has_academic_modules): 
        ?>
            <span class="nav-section-title">Students &amp; Academics</span>

            <?php if (in_array('students', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>students" class="nav-link <?php echo $current_page == 'students' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate" style="color: #7c3aed;"></i>
                        <span>Student Directory</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('attendance', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>attendance" class="nav-link <?php echo $current_page == 'attendance' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check" style="color: #0d9488;"></i>
                        <span>Student Attendance</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('results', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>results" class="nav-link <?php echo $current_page == 'results' ? 'active' : ''; ?>">
                        <i class="fas fa-award" style="color: #9333ea;"></i>
                        <span>Exam Results</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('syllabus', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>syllabus" class="nav-link <?php echo $current_page == 'syllabus' ? 'active' : ''; ?>">
                        <i class="fas fa-book-open" style="color: #2563eb;"></i>
                        <span>Academic Syllabus</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('schools', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>schools" class="nav-link <?php echo $current_page == 'schools' ? 'active' : ''; ?>">
                        <i class="fas fa-graduation-cap" style="color: #4338ca;"></i>
                        <span>Coaching Programs</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('achievers', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>achievers" class="nav-link <?php echo $current_page == 'achievers' ? 'active' : ''; ?>">
                        <i class="fas fa-trophy" style="color: #eab308;"></i>
                        <span>Hall of Excellence</span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 4. FINANCE & FEES -->
        <?php if (in_array('fees', $user_perms)): ?>
            <span class="nav-section-title">Finance</span>
            <li class="nav-item">
                <a href="<?php echo $counselor_url; ?>fees" class="nav-link <?php echo $current_page == 'fees' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar" style="color: #16a34a;"></i>
                    <span>Fees &amp; Dues Lookup</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- 5. COMMUNICATION & MEDIA -->
        <?php 
        $has_comm_modules = in_array('tickets', $user_perms) || in_array('notices', $user_perms) || in_array('whatsapp', $user_perms) || in_array('gallery', $user_perms);
        if ($has_comm_modules): 
        ?>
            <span class="nav-section-title">Communication</span>

            <?php if (in_array('tickets', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>tickets" class="nav-link <?php echo $current_page == 'tickets' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt" style="color: #0284c7;"></i>
                        <span>Support Tickets</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('notices', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>notices" class="nav-link <?php echo $current_page == 'notices' ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn" style="color: #d97706;"></i>
                        <span>Notice Board</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('whatsapp', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>whatsapp" class="nav-link <?php echo $current_page == 'whatsapp' ? 'active' : ''; ?>">
                        <i class="fab fa-whatsapp" style="color: #22c55e;"></i>
                        <span>WhatsApp Quick Hub</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('gallery', $user_perms)): ?>
                <li class="nav-item">
                    <a href="<?php echo $counselor_url; ?>gallery" class="nav-link <?php echo $current_page == 'gallery' ? 'active' : ''; ?>">
                        <i class="fas fa-images" style="color: #0891b2;"></i>
                        <span>Campus Gallery</span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>

    <!-- Logout Button -->
    <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: auto;">
        <a href="<?php echo $counselor_url; ?>logout.php" class="nav-link" style="color: #dc2626;" onclick="return confirm('Log out of Counselor Console?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log Out</span>
        </a>
    </div>
</div>
