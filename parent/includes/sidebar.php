<?php
// parent/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Top Header -->
<div class="mobile-header">
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="ABSS Logo">
        <span>ABSS Portal</span>
    </div>
    <button class="hamburger-btn" id="hamburgerMenuBtn" aria-label="Open Navigation">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Sliding Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Left Navigation Drawer -->
<div class="sidebar">
    <!-- Close Drawer Button (Mobile Only) -->
    <button class="close-sidebar-btn" id="closeSidebarBtn" aria-label="Close Navigation">
        <i class="fas fa-times"></i>
    </button>

    <div class="sidebar-brand">
        <img src="../assets/logo.png" alt="Logo">
        <div>
            <span>ABSS Portal</span>
            <small>Parent Space</small>
        </div>
    </div>
    
    <div style="background: #f8faff; border-radius: 18px; padding: 15px 20px; margin-bottom: 35px; border: 1px solid #eef2ff;">
        <span style="font-size:0.75rem; color:#9aa5ce; font-weight:700; text-transform:uppercase; display:block; margin-bottom:5px;">LOGGED IN AS</span>
        <span style="font-weight:800; color:var(--portal-indigo); font-size:0.95rem; display:flex; align-items:center; gap:8px;">
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
            <a href="fees" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> Dues & Fees Ledger
            </a>
        </li>
        <li class="nav-item">
            <a href="notices" class="nav-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Notice Board
            </a>
        </li>
        <li class="nav-item">
            <a href="tickets" class="nav-link <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Helpdesk Support
            </a>
        </li>
        <li class="nav-item">
            <a href="settings" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Portal Settings
            </a>
        </li>
    </ul>
    
    <a href="../admin/logout.php" class="nav-link logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout Portal
    </a>
</div>

<!-- Lightweight Self-Contained Drawer Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hamburger = document.getElementById("hamburgerMenuBtn");
        const closeBtn = document.getElementById("closeSidebarBtn");
        const sidebar = document.querySelector(".sidebar");
        const overlay = document.getElementById("sidebarOverlay");
        
        if (hamburger && sidebar && overlay) {
            hamburger.addEventListener("click", function() {
                sidebar.classList.add("open");
                overlay.classList.add("active");
                document.body.style.overflow = "hidden"; // Prevent body scrolling when menu is open
            });
        }
        
        function closeDrawer() {
            if (sidebar && overlay) {
                sidebar.classList.remove("open");
                overlay.classList.remove("active");
                document.body.style.overflow = ""; // Restore body scrolling
            }
        }
        
        if (closeBtn) {
            closeBtn.addEventListener("click", closeDrawer);
        }
        if (overlay) {
            overlay.addEventListener("click", closeDrawer);
        }
        
        // Auto-close menu drawer on screen resize if user resizes back to desktop
        window.addEventListener("resize", function() {
            if (window.innerWidth > 1024) {
                closeDrawer();
            }
        });
    });
</script>

<?php if (isset($_SESSION['show_missing_docs_popup']) && $_SESSION['show_missing_docs_popup'] === true): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: 'Action Required',
            text: 'Upload required Document.',
            icon: 'warning',
            confirmButtonColor: '#3f51b5',
            confirmButtonText: 'OK'
        });
    });
</script>
<?php unset($_SESSION['show_missing_docs_popup']); ?>
<?php endif; ?>
