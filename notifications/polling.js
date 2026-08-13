/**
 * ABSS Perfected Real-Time Notification & Polling Engine
 * Compatible with Web Browsers, Mobile Web PWAs & Android Webview APKs.
 */
(function () {
    'use strict';

    const POLLING_INTERVAL = 25000; // 25 seconds for sub-minute real-time delivery

    // Calculate Absolute API Endpoint URL
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

    // Web Audio API Synthetic Chime Generator (No external audio file needed!)
    function playNotificationChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc1.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
            
            gain1.gain.setValueAtTime(0.15, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
            
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.5);
        } catch (e) {
            // Audio context gesture restrictions fallback silently
        }
    }

    // In-App Toast Container Setup
    function createToastContainer() {
        let container = document.getElementById('abss-notification-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'abss-notification-toast-container';
            container.setAttribute('style', `
                position: fixed;
                top: 25px;
                right: 25px;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-width: 400px;
                width: calc(100% - 50px);
                pointer-events: none;
                font-family: 'Outfit', 'Segoe UI', sans-serif;
            `);
            document.body.appendChild(container);
        }
        return container;
    }

    // Category Icon Resolver
    function getCategoryBadge(category) {
        switch(category) {
            case 'fee':
                return { bg: '#fef3c7', color: '#d97706', icon: '💳', border: '#f59e0b' };
            case 'academic':
                return { bg: '#eff6ff', color: '#2563eb', icon: '🎓', border: '#2563eb' };
            case 'admission':
                return { bg: '#f0fdf4', color: '#16a34a', icon: '🌟', border: '#22c55e' };
            default:
                return { bg: '#eff6ff', color: '#2563eb', icon: '🔔', border: '#3b82f6' };
        }
    }

    // Display Modern Glassmorphic In-App Toast
    function showInAppToast(data) {
        const container = createToastContainer();
        const badge = getCategoryBadge(data.category);

        const toast = document.createElement('div');
        toast.setAttribute('style', `
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #0f172a;
            border-left: 5px solid ${badge.border};
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2), 0 8px 16px -6px rgba(0, 0, 0, 0.08);
            pointer-events: auto;
            transform: translateX(120%);
            transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
            border-right: 1px solid rgba(226, 232, 240, 0.8);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        `);

        let html = `
            <div style="display: flex; align-items: flex-start; gap: 14px;">
                <div style="background: ${badge.bg}; color: ${badge.color}; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem; font-weight: bold;">
                    ${badge.icon}
                </div>
                <div style="flex: 1; padding-right: 15px;">
                    <div style="font-weight: 800; font-size: 0.95rem; color: #0f172a; margin-bottom: 3px; line-height: 1.2;">${escapeHtml(data.title)}</div>
                    <div style="font-size: 0.84rem; color: #475569; line-height: 1.45; font-weight: 500;">${escapeHtml(data.message)}</div>
                    ${data.url ? `<a href="${escapeHtml(data.url)}" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; font-size: 0.8rem; font-weight: 800; color: #2563eb; text-decoration: none;">View Notice Details &rarr;</a>` : ''}
                </div>
                <button type="button" class="toast-close-btn" style="position: absolute; top: 12px; right: 12px; background: transparent; border: none; font-size: 1.2rem; line-height: 1; color: #94a3b8; cursor: pointer; padding: 2px;" aria-label="Close">&times;</button>
            </div>
            <div class="toast-progress-bar" style="position: absolute; bottom: 0; left: 0; height: 3px; background: ${badge.border}; width: 100%; transition: width 9s linear;"></div>
        `;

        toast.innerHTML = html;
        container.appendChild(toast);

        // Play chime sound
        playNotificationChime();

        requestAnimationFrame(function () {
            toast.style.transform = 'translateX(0)';
            setTimeout(function() {
                const bar = toast.querySelector('.toast-progress-bar');
                if (bar) bar.style.width = '0%';
            }, 100);
        });

        const closeBtn = toast.querySelector('.toast-close-btn');
        closeBtn.addEventListener('click', function () {
            toast.style.transform = 'translateX(120%)';
            setTimeout(function () { toast.remove(); }, 450);
        });

        setTimeout(function () {
            if (toast.parentNode) {
                toast.style.transform = 'translateX(120%)';
                setTimeout(function () { toast.remove(); }, 450);
            }
        }, 9000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Request Browser Notification Permission on User Gesture
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            const handleGesture = function() {
                Notification.requestPermission().then(function (permission) {
                    console.log('[ABSS App Notification] Permission granted:', permission);
                });
                window.removeEventListener('click', handleGesture);
                window.removeEventListener('touchstart', handleGesture);
            };
            window.addEventListener('click', handleGesture, { once: true });
            window.addEventListener('touchstart', handleGesture, { once: true });
        }
    }

    // Dual Persistence Helpers (LocalStorage + Cookie fallback)
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

    // Main Notification Polling Routine
    function checkNotifications() {
        const lastId = getStoredLastId();

        fetch(`${API_URL}?last_id=${lastId}`)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.status === true && data.id) {
                    
                    // If is_baseline is true (initial page visit with last_id=0), set baseline silently
                    if (data.is_baseline && lastId === 0) {
                        setStoredLastId(data.id);
                        return;
                    }

                    // Otherwise, a NEW notification has arrived!
                    showInAppToast(data);

                    // Trigger System / Device Notification
                    if ('Notification' in window && Notification.permission === 'granted') {
                        try {
                            const options = {
                                body: data.message,
                                icon: data.icon || 'assets/logo.png',
                                badge: data.icon || 'assets/logo.png',
                                tag: 'abss-notif-' + data.id,
                                renotify: true
                            };

                            const notification = new Notification(data.title, options);

                            if (data.url) {
                                notification.onclick = function (e) {
                                    e.preventDefault();
                                    window.open(data.url, '_blank');
                                };
                            }
                        } catch (e) {
                            console.warn('[ABSS Notification] Device Notification Notice:', e);
                        }
                    }

                    // Persist new last_id immediately
                    setStoredLastId(data.id);

                    // Dispatch Custom DOM Event for custom page handlers
                    window.dispatchEvent(new CustomEvent('abssNotificationReceived', { detail: data }));
                }
            })
            .catch(function (err) {
                console.error('[ABSS Notification] Polling error:', err);
            });
    }

    // Initialize notification engine
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

    // Global Controller Object
    window.ABSSNotificationApp = {
        checkNow: checkNotifications,
        resetLastId: function () {
            localStorage.setItem('notification_last_id', 0);
            document.cookie = "notification_last_id=0; path=/; max-age=0";
            console.log('[ABSS Notification] Baseline last_id reset to 0');
        }
    };
})();
