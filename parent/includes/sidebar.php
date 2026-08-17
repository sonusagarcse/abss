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
    <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" class="notification-bell-btn" id="mobileNotificationBellBtn" onclick="toggleNotificationDrawer()" aria-label="Notifications" style="position: relative; background: #ede9fe; color: var(--portal-purple); border: none; width: 40px; height: 40px; border-radius: 12px; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="mobileNotificationBadge" style="display:none; position: absolute; top: -4px; right: -4px; background: #dc2626; color: #ffffff; font-size: 0.68rem; font-weight: 800; border-radius: 50px; padding: 2px 6px; border: 2px solid #ffffff; min-width: 18px; text-align: center;">0</span>
        </button>
        <button type="button" class="hamburger-btn" id="hamburgerMenuBtn" aria-label="Open Navigation">
            <i class="fas fa-bars"></i>
        </button>
    </div>
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
            <a href="gallery.php" class="<?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-photo-video"></i>
                <span>Gallery</span>
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
            <a href="javascript:void(0);" onclick="toggleNotificationDrawer()" class="nav-link" id="desktopNotificationNavBtn">
                <i class="fas fa-bell"></i> Alerts Center
                <span id="desktopNotificationBadge" style="display:none; margin-left:auto; background:#dc2626; color:#ffffff; font-size:0.72rem; border-radius:50px; padding:2px 8px; font-weight:800;">0</span>
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
            <a href="gallery.php" class="nav-link <?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-photo-video"></i> Gallery & Videos
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

<!-- In-Built Notification Center Modal Overlay & Drawer -->
<div id="portalNotificationOverlay" class="notification-overlay" style="display:none;" onclick="toggleNotificationDrawer()"></div>

<div id="portalNotificationDrawer" class="notification-center-drawer" style="display:none;">
    <div class="notification-center-header">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:10px; background:#ede9fe; color:var(--portal-purple); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:#0f172a;">Portal Alerts</h3>
                <span style="font-size:0.75rem; color:#64748b; font-weight:600;" id="drawerSubtitle">Recent activity notifications</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="btn-drawer-action" onclick="markAllNotificationsRead()" title="Mark all as read">
                <i class="fas fa-check-double"></i> Mark all read
            </button>
            <button type="button" class="btn-drawer-close" onclick="toggleNotificationDrawer()" title="Close">&times;</button>
        </div>
    </div>

    <div class="notification-center-body" id="portalNotificationList">
        <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
            <i class="fas fa-spinner fa-spin" style="font-size:1.8rem; margin-bottom:12px;"></i>
            <p style="margin:0; font-size:0.9rem; font-weight:600;">Loading alerts...</p>
        </div>
    </div>
</div>

<!-- Floating In-App Web Notification Toast Alert Popup -->
<div id="portalInAppToast" class="portal-inapp-toast" style="display:none;">
    <button type="button" class="toast-close-btn" onclick="dismissInAppToast()" title="Dismiss">&times;</button>
    <div class="toast-content-row">
        <div class="toast-icon-wrap" id="toastIconWrap" style="background: #ede9fe; color: var(--portal-purple);">
            <i id="toastIcon" class="fas fa-bell"></i>
        </div>
        <div class="toast-text-col">
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                <span class="toast-badge-tag" id="toastTag" style="background:#ede9fe; color:var(--portal-purple);">Notification</span>
                <span style="font-size:0.7rem; color:#94a3b8; font-weight:700;" id="toastTime">Just now</span>
            </div>
            <h4 id="toastTitle" style="margin:0 0 4px 0; font-size:0.96rem; font-weight:800; color:#0f172a; line-height:1.3;"></h4>
            <p id="toastMessage" style="margin:0 0 10px 0; font-size:0.82rem; color:#475569; line-height:1.4;"></p>
            <div>
                <a id="toastActionBtn" href="javascript:void(0);" class="btn-toast-action">
                    <span>View Now</span> <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Notification Drawer Styles */
    .notification-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 10000;
        animation: fadeInOverlay 0.2s ease-out;
    }
    @keyframes fadeInOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .notification-center-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 420px;
        max-width: 92vw;
        height: 100vh;
        background: #ffffff;
        z-index: 10001;
        box-shadow: -10px 0 35px rgba(0,0,0,0.18);
        display: flex;
        flex-direction: column;
        animation: slideInDrawer 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes slideInDrawer {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }

    .notification-center-header {
        padding: 18px 20px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .btn-drawer-action {
        background: #f1f5f9;
        color: #475569;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.76rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background 0.2s, color 0.2s;
    }
    .btn-drawer-action:hover {
        background: #ede9fe;
        color: var(--portal-purple);
    }

    .btn-drawer-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #94a3b8;
        cursor: pointer;
        line-height: 1;
        padding: 0 4px;
        transition: color 0.2s;
    }
    .btn-drawer-close:hover { color: #dc2626; }

    .notification-center-body {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
    }

    .notif-item-card {
        padding: 14px 16px;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        margin-bottom: 10px;
        display: flex;
        gap: 14px;
        align-items: flex-start;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s, background 0.2s;
        position: relative;
        cursor: pointer;
    }
    .notif-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .notif-item-card.unread {
        background: #faf5ff;
        border-color: #e9d5ff;
    }
    .notif-item-card.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 14px;
        bottom: 14px;
        width: 4px;
        background: var(--portal-purple);
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }

    .notif-icon-circle {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .notif-text-wrap {
        flex: 1;
        min-width: 0;
    }
    .notif-title-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    .notif-title-text {
        font-size: 0.9rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .notif-time-ago {
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 700;
        white-space: nowrap;
    }
    .notif-msg-text {
        font-size: 0.8rem;
        color: #475569;
        margin: 0 0 8px 0;
        line-height: 1.4;
    }
    .notif-link-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--portal-purple);
    }

    /* Floating In-App Toast Popup Styles */
    .portal-inapp-toast {
        position: fixed;
        bottom: 25px;
        right: 25px;
        max-width: 390px;
        width: calc(100vw - 40px);
        background: #ffffff;
        border-radius: 20px;
        padding: 16px 18px;
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(124, 58, 237, 0.15);
        z-index: 99999;
        animation: slideUpToast 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @media (max-width: 500px) {
        .portal-inapp-toast {
            bottom: 75px;
            right: 15px;
            left: 15px;
            width: auto;
        }
    }
    @keyframes slideUpToast {
        from { transform: translateY(60px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .toast-close-btn {
        position: absolute;
        top: 10px;
        right: 12px;
        background: none;
        border: none;
        font-size: 1.3rem;
        color: #94a3b8;
        cursor: pointer;
        line-height: 1;
        transition: color 0.2s;
    }
    .toast-close-btn:hover { color: #dc2626; }

    .toast-content-row {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }
    .toast-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .toast-text-col {
        flex: 1;
        min-width: 0;
        padding-right: 12px;
    }
    .toast-badge-tag {
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-block;
    }
    .btn-toast-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, var(--portal-purple), var(--portal-purple-dark));
        color: #ffffff !important;
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-toast-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.45);
    }
</style>

<!-- In-Built Notification System Client Logic -->
<script>
    let portalNotifications = [];
    let unreadCount = 0;
    let toastTimeout = null;

    function fetchPortalNotifications() {
        fetch('api/notifications.php?action=fetch')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    portalNotifications = data.notifications || [];
                    unreadCount = data.unread_count || 0;
                    updateNotificationBadges();
                    renderNotificationList();
                    checkAndTriggerInAppToast();
                }
            })
            .catch(err => console.error('Notification fetch error:', err));
    }

    function updateNotificationBadges() {
        const mobileBadge = document.getElementById('mobileNotificationBadge');
        const desktopBadge = document.getElementById('desktopNotificationBadge');
        const bottomBadge = document.getElementById('bottomNavNotificationBadge');

        const displayStyle = unreadCount > 0 ? 'inline-flex' : 'none';
        const displayBlock = unreadCount > 0 ? 'inline-block' : 'none';
        const countTxt = unreadCount > 99 ? '99+' : unreadCount;

        if (mobileBadge) {
            mobileBadge.style.display = displayStyle;
            mobileBadge.textContent = countTxt;
        }
        if (desktopBadge) {
            desktopBadge.style.display = displayBlock;
            desktopBadge.textContent = countTxt;
        }
        if (bottomBadge) {
            bottomBadge.style.display = displayBlock;
            bottomBadge.textContent = countTxt;
        }
    }

    function renderNotificationList() {
        const listContainer = document.getElementById('portalNotificationList');
        const subtitle = document.getElementById('drawerSubtitle');
        if (!listContainer) return;

        if (subtitle) {
            subtitle.textContent = unreadCount > 0 ? `${unreadCount} unread alert(s)` : 'All caught up!';
        }

        if (!portalNotifications || portalNotifications.length === 0) {
            listContainer.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                    <i class="far fa-bell-slash" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.6;"></i>
                    <h4 style="margin: 0 0 4px 0; color: #64748b; font-size: 1rem;">No Alerts Yet</h4>
                    <p style="margin: 0; font-size: 0.82rem;">New bills, results, and gallery updates will appear here.</p>
                </div>
            `;
            return;
        }

        let html = '';
        portalNotifications.forEach(n => {
            const isUnread = (n.is_read == 0);
            const iconClass = n.icon || 'fa-bell';
            const badgeColor = n.badge_color || '#7c3aed';
            const targetUrl = n.target_url || 'dashboard.php';

            html += `
                <div class="notif-item-card ${isUnread ? 'unread' : ''}" onclick="onNotificationClick(${n.id}, '${escapeHtmlAttr(targetUrl)}')">
                    <div class="notif-icon-circle" style="background: ${badgeColor}15; color: ${badgeColor};">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="notif-text-wrap">
                        <div class="notif-title-row">
                            <h5 class="notif-title-text">${escapeHtml(n.title)}</h5>
                            <span class="notif-time-ago">${escapeHtml(n.time_ago)}</span>
                        </div>
                        <p class="notif-msg-text">${escapeHtml(n.message)}</p>
                        <span class="notif-link-btn" style="color: ${badgeColor};">
                            <span>Open Link</span> <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
                        </span>
                    </div>
                </div>
            `;
        });

        listContainer.innerHTML = html;
    }

    function checkAndTriggerInAppToast() {
        if (!portalNotifications || portalNotifications.length === 0) return;

        // Find the latest unread notification
        const latestUnread = portalNotifications.find(n => n.is_read == 0);
        if (!latestUnread) return;

        // Check if already displayed in this browser session
        const lastSeenId = sessionStorage.getItem('abss_last_toast_notif_id');
        if (lastSeenId && parseInt(lastSeenId) === parseInt(latestUnread.id)) {
            return;
        }

        // Show Toast Popup
        showInAppToast(latestUnread);
    }

    function showInAppToast(notif) {
        const toast = document.getElementById('portalInAppToast');
        const iconWrap = document.getElementById('toastIconWrap');
        const icon = document.getElementById('toastIcon');
        const tag = document.getElementById('toastTag');
        const time = document.getElementById('toastTime');
        const title = document.getElementById('toastTitle');
        const msg = document.getElementById('toastMessage');
        const actionBtn = document.getElementById('toastActionBtn');

        if (!toast) return;

        const badgeColor = notif.badge_color || '#7c3aed';
        const iconClass = notif.icon || 'fa-bell';
        const typeLabel = (notif.type || 'Alert').toUpperCase();

        if (iconWrap) {
            iconWrap.style.background = badgeColor + '18';
            iconWrap.style.color = badgeColor;
        }
        if (icon) {
            icon.className = 'fas ' + iconClass;
        }
        if (tag) {
            tag.style.background = badgeColor + '18';
            tag.style.color = badgeColor;
            tag.textContent = typeLabel;
        }
        if (time) time.textContent = notif.time_ago || 'Just now';
        if (title) title.textContent = notif.title;
        if (msg) msg.textContent = notif.message;
        if (actionBtn) {
            actionBtn.onclick = function(e) {
                e.preventDefault();
                onNotificationClick(notif.id, notif.target_url);
            };
        }

        toast.style.display = 'block';
        sessionStorage.setItem('abss_last_toast_notif_id', notif.id);

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            dismissInAppToast();
        }, 12000); // 12 seconds display
    }

    function dismissInAppToast() {
        const toast = document.getElementById('portalInAppToast');
        if (toast) toast.style.display = 'none';
        if (toastTimeout) clearTimeout(toastTimeout);
    }

    function onNotificationClick(notifId, targetUrl) {
        dismissInAppToast();
        
        // Mark as read in background
        fetch(`api/notifications.php?action=mark_read&id=${notifId}`, { method: 'POST' })
            .then(() => {
                window.location.href = targetUrl;
            })
            .catch(() => {
                window.location.href = targetUrl;
            });
    }

    function markAllNotificationsRead() {
        fetch('api/notifications.php?action=mark_all_read', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    unreadCount = 0;
                    portalNotifications.forEach(n => n.is_read = 1);
                    updateNotificationBadges();
                    renderNotificationList();
                    dismissInAppToast();
                }
            });
    }

    function toggleNotificationDrawer() {
        const drawer = document.getElementById('portalNotificationDrawer');
        const overlay = document.getElementById('portalNotificationOverlay');
        if (!drawer || !overlay) return;

        const isVisible = drawer.style.display === 'flex';
        if (isVisible) {
            drawer.style.display = 'none';
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        } else {
            drawer.style.display = 'flex';
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
            renderNotificationList();
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeHtmlAttr(str) {
        if (!str) return '';
        return String(str).replace(/'/g, "\\'");
    }

    // Initialize notification fetch on load and every 25 seconds
    document.addEventListener("DOMContentLoaded", function() {
        fetchPortalNotifications();
        setInterval(fetchPortalNotifications, 25000);
    });
</script>

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
