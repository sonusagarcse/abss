/**
 * ABSS Web & Mobile App Polling Notification Client Module
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
        // Fallback relative to host
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

        // Slide In
        requestAnimationFrame(function () {
            toast.style.transform = 'translateX(0)';
        });

        // Close Event
        const closeBtn = toast.querySelector('.toast-close-btn');
        closeBtn.addEventListener('click', function () {
            toast.style.transform = 'translateX(120%)';
            setTimeout(function () { toast.remove(); }, 400);
        });

        // Auto dismiss after 10 seconds
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

    // Native Browser Notification Permission Request
    function requestNotificationPermission() {
        if ('Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(function (permission) {
                    console.log('[ABSS App Notification] Permission:', permission);
                });
            }
        }
    }

    // Main Polling Engine
    function checkNotifications() {
        const lastId = localStorage.getItem('notification_last_id') || 0;

        fetch(`${API_URL}?last_id=${lastId}`)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.status === true) {
                    console.log('[ABSS App Notification] New notification:', data);

                    // 1. Show In-App Toast Alert
                    showInAppToast(data);

                    // 2. Trigger Native Device / Web Notification
                    if ('Notification' in window && Notification.permission === 'granted') {
                        try {
                            const n = new Notification(data.title, {
                                body: data.message,
                                icon: 'https://cdn-icons-png.flaticon.com/512/3602/3602145.png'
                            });
                            if (data.url) {
                                n.onclick = function (e) {
                                    e.preventDefault();
                                    window.open(data.url, '_blank');
                                };
                            }
                        } catch (e) {
                            console.warn('[ABSS App Notification] Native notification error:', e);
                        }
                    }

                    // Update last_id
                    localStorage.setItem('notification_last_id', data.id);

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
