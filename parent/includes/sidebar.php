<?php
// parent/includes/sidebar.php - Parent Navigation & Mobile Bottom Bar
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Top Header -->
<div class="mobile-header">
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="ABSS Logo">
        <span>ABSS Parent Portal</span>
    </div>
    <button class="hamburger-btn" id="hamburgerMenuBtn" aria-label="Open Drawer">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav">
    <ul>
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Overview</span>
            </a>
        </li>
        <li>
            <a href="fees.php" class="<?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i>
                <span>Fees</span>
            </a>
        </li>
        <li>
            <a href="results.php" class="<?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                <i class="fas fa-award"></i>
                <span>Results</span>
            </a>
        </li>
        <li>
            <a href="documents.php" class="<?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>Docs</span>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" id="bottomNavMenuBtn">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Sliding Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Desktop & Mobile Drawer Sidebar -->
<div class="sidebar">
    <button class="close-sidebar-btn" id="closeSidebarBtn" aria-label="Close Drawer">
        <i class="fas fa-times"></i>
    </button>

    <div class="sidebar-brand">
        <img src="../assets/logo.png" alt="Logo">
        <div>
            <span>ABSS Portal</span>
            <small>Parent Portal</small>
        </div>
    </div>
    
    <div style="background: #f8fafc; border-radius: 16px; padding: 14px 18px; margin-bottom: 25px; border: 1px solid #ede9fe;">
        <span style="font-size:0.7rem; color:#94a3b8; font-weight:800; text-transform:uppercase; display:block; margin-bottom:4px;">Logged in parent</span>
        <span style="font-weight:800; color:var(--portal-indigo); font-size:0.92rem; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-user-circle" style="color:var(--portal-purple); font-size:1.1rem;"></i>
            <?php echo htmlspecialchars($_SESSION['parent_name'] ?? 'Parent Profile'); ?>
        </span>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Overview Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="documents.php" class="nav-link <?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Required Documents
            </a>
        </li>
        <li class="nav-item">
            <a href="results.php" class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                <i class="fas fa-award"></i> Academic Performance
            </a>
        </li>
        <li class="nav-item">
            <a href="fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> Dues & Fees Ledger
            </a>
        </li>
        <li class="nav-item">
            <a href="notices.php" class="nav-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Notice Board
            </a>
        </li>
        <li class="nav-item">
            <a href="tickets.php" class="nav-link <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i> Helpdesk Support
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Portal Settings
            </a>
        </li>
    </ul>
    
    <a href="logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout Portal
    </a>
</div>

<!-- Drawer Toggle Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hamburger = document.getElementById("hamburgerMenuBtn");
        const bottomNavMenu = document.getElementById("bottomNavMenuBtn");
        const closeBtn = document.getElementById("closeSidebarBtn");
        const sidebar = document.querySelector(".sidebar");
        const overlay = document.getElementById("sidebarOverlay");
        
        function openDrawer() {
            if (sidebar && overlay) {
                sidebar.classList.add("open");
                overlay.classList.add("active");
                document.body.style.overflow = "hidden";
            }
        }

        function closeDrawer() {
            if (sidebar && overlay) {
                sidebar.classList.remove("open");
                overlay.classList.remove("active");
                document.body.style.overflow = "";
            }
        }
        
        if (hamburger) hamburger.addEventListener("click", openDrawer);
        if (bottomNavMenu) bottomNavMenu.addEventListener("click", openDrawer);
        if (closeBtn) closeBtn.addEventListener("click", closeDrawer);
        if (overlay) overlay.addEventListener("click", closeDrawer);
        
        window.addEventListener("resize", function() {
            if (window.innerWidth > 1024) closeDrawer();
        });
    });
</script>

<?php if (isset($_SESSION['show_missing_docs_popup']) && $_SESSION['show_missing_docs_popup'] === true): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: 'Action Required',
            text: 'Please upload missing required student document(s).',
            icon: 'warning',
            confirmButtonColor: '#7c3aed',
            confirmButtonText: 'OK'
        });
    });
</script>
<?php unset($_SESSION['show_missing_docs_popup']); ?>
<?php endif; ?>
