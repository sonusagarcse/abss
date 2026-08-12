/**
 * ABSS Web & Mobile APK Polling Notification Client Module
 * Optimized for standard Web Notifications (new Notification()) & Android Webview APKs.
 */
(function () {
    const POLLING_INTERVAL = 30000; // 30 seconds

    // Calculate Absolute API URL dynamically based on script location or domain
    function getApiUrl() {
        let scriptSrc = '';
        if (document.currentScript && document.currentScript.src) {
            scriptSrc = document.currentScript.src;
        } else {
            const scripts = document.getElementsByTagName('script');
            for (let i = scripts.length - 1; i >= 0; i--) {
                if (scripts[i].src && scripts[i].src.indexOf('polling.js') !== -1) {
                    scriptSrc = scripts[i].src;
                    break;
                }
            }
        }

        if (scriptSrc) {
            return scriptSrc.replace('polling.js', 'notification.php');
        }
        const pathSegments = window.location.pathname.split('/');
        if (pathSegments.includes('abss')) {
            return window.location.origin + '/abss/notifications/notification.php';
        }
        return window.location.origin + '/notifications/notification.php';
    }

    const API_URL = getApiUrl();

    // In-App Toast Container Setup
    function createToastContainer() {
        let container = document.getElementById('abss-notification-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'abss-notification-toast-container';
            container.setAttribute('style', `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-width: 380px;
                width: calc(100% - 40px);
                pointer-events: none;
            `);
            document.body.appendChild(container);
        }
        return container;
    }

    // Display In-App Popup Banner
    function showInAppToast(data) {
        const container = createToastContainer();
        const toast = document.createElement('div');
        toast.setAttribute('style', `
            background: #ffffff;
            color: #0f172a;
            border-left: 5px solid #2563eb;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            font-family: 'Outfit', 'Inter', -apple-system, sans-serif;
            pointer-events: auto;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        `);

        let html = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="background: #eff6ff; color: #2563eb; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: bold;">
                    🔔
                </div>
                <div style="flex: 1; padding-right: 18px;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: #1e293b; margin-bottom: 4px;">${escapeHtml(data.title)}</div>
                    <div style="font-size: 0.85rem; color: #475569; line-height: 1.4;">${escapeHtml(data.message)}</div>
                    ${data.url ? `<a href="${escapeHtml(data.url)}" target="_blank" style="display: inline-block; margin-top: 10px; font-size: 0.8rem; font-weight: 700; color: #2563eb; text-decoration: none;">View Detail &rarr;</a>` : ''}
                </div>
                <button type="button" class="toast-close-btn" style="position: absolute; top: 12px; right: 12px; background: transparent; border: none; font-size: 1.2rem; line-height: 1; color: #94a3b8; cursor: pointer; padding: 2px;">&times;</button>
            </div>
        `;

        toast.innerHTML = html;
        container.appendChild(toast);

        requestAnimationFrame(function () {
            toast.style.transform = 'translateX(0)';
        });

        const closeBtn = toast.querySelector('.toast-close-btn');
        closeBtn.addEventListener('click', function () {
            toast.style.transform = 'translateX(120%)';
            setTimeout(function () { toast.remove(); }, 400);
        });

        setTimeout(function () {
            if (toast.parentNode) {
                toast.style.transform = 'translateX(120%)';
                setTimeout(function () { toast.remove(); }, 400);
            }
        }, 10000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Native Browser / Android APK Notification Permission Request
    function requestNotificationPermission() {
        if ('Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(function (permission) {
                    console.log('[ABSS App Notification] Permission:', permission);
                });
            }
        }
    }

    // Dual Persistence Helpers (LocalStorage + Cookie) to prevent WebView data loss
    function setStoredLastId(id) {
        if (!id) return;
        try { localStorage.setItem('notification_last_id', id); } catch (e) {}
        try { document.cookie = "notification_last_id=" + id + "; path=/; max-age=31536000; SameSite=Lax"; } catch (e) {}
    }

    function getStoredLastId() {
        let id = 0;
        try {
            const local = localStorage.getItem('notification_last_id');
            if (local && !isNaN(parseInt(local, 10))) {
                id = parseInt(local, 10);
            }
        } catch (e) {}
        if (id <= 0) {
            try {
                const match = document.cookie.match(/(?:^|; )notification_last_id=([^;]*)/);
                if (match) {
                    const cookieVal = parseInt(decodeURIComponent(match[1]), 10);
                    if (!isNaN(cookieVal) && cookieVal > 0) id = cookieVal;
                }
            } catch (e) {}
        }
        return id;
    }

    // Main Polling Engine
    function checkNotifications() {
        const lastId = getStoredLastId();

        fetch(`${API_URL}?last_id=${lastId}`)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.status === true && data.id) {
                    const isFirstSync = (lastId === 0);

                    // 1. Show In-App Toast Alert
                    showInAppToast(data);

                    // 2. Trigger Native Device / Web Notification (Mapped by Android WebView in APK)
                    if ('Notification' in window) {
                        try {
                            const options = {
                                body: data.message,
                                icon: data.icon || 'https://cdn-icons-png.flaticon.com/512/3602/3602145.png',
                                badge: data.icon || 'https://cdn-icons-png.flaticon.com/512/3602/3602145.png',
                                tag: 'notification-' + data.id,
                                renotify: false
                            };

                            const notification = new Notification(data.title, options);

                            if (data.url) {
                                notification.onclick = function (e) {
                                    e.preventDefault();
                                    window.open(data.url, '_blank');
                                };
                            }
                        } catch (e) {
                            console.warn('[ABSS App Notification] Native Web Notification error:', e);
                        }
                    }

                    // Store last_id in both LocalStorage & 1-Year Cookie immediately
                    setStoredLastId(data.id);

                    // Fire Custom DOM Event
                    window.dispatchEvent(new CustomEvent('abssNotificationReceived', { detail: data }));
                }
            })
            .catch(function (err) {
                console.error('[ABSS App Notification] Polling error:', err);
            });
    }

    // Initialize module
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            requestNotificationPermission();
            checkNotifications();
            setInterval(checkNotifications, POLLING_INTERVAL);
        });
    } else {
        requestNotificationPermission();
        checkNotifications();
        setInterval(checkNotifications, POLLING_INTERVAL);
    }

    // Global Access
    window.ABSSNotificationApp = {
        checkNow: checkNotifications,
        resetLastId: function () {
            localStorage.setItem('notification_last_id', 0);
            console.log('[ABSS App Notification] last_id reset to 0');
        }
    };
})();
