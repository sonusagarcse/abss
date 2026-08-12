/**
 * Polling Notification Module - JavaScript Client
 */
(function () {
    const POLLING_INTERVAL = 30000; // 30 seconds
    const API_URL = 'notification.php';

    // 1. Request Browser Notification Permission on initialization
    function requestNotificationPermission() {
        if ('Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(function (permission) {
                    console.log('[Notification Module] Permission result:', permission);
                });
            }
        } else {
            console.warn('[Notification Module] Web Notifications are not supported in this browser.');
        }
    }

    // 2. Main Polling Function
    function checkNotifications() {
        // Retrieve last_id from localStorage or default to 0
        const lastId = localStorage.getItem('notification_last_id') || 0;

        fetch(`${API_URL}?last_id=${lastId}`)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function (data) {
                if (data && data.status === true) {
                    console.log('[Notification Module] New notification received:', data);

                    // Trigger Native Desktop Browser Notification
                    if ('Notification' in window && Notification.permission === 'granted') {
                        const options = {
                            body: data.message,
                            icon: 'https://cdn-icons-png.flaticon.com/512/3602/3602145.png' // Default generic notification icon
                        };

                        const notification = new Notification(data.title, options);

                        // If optional URL is provided, open link on click
                        if (data.url) {
                            notification.onclick = function (event) {
                                event.preventDefault();
                                window.open(data.url, '_blank');
                            };
                        }
                    } else {
                        // Fallback in-page alert if permission not granted
                        console.log(`[Notification Fallback] ${data.title}: ${data.message}`);
                    }

                    // Automatically update last_id in localStorage
                    localStorage.setItem('notification_last_id', data.id);

                    // Dispatch custom DOM event for potential custom app handlers
                    window.dispatchEvent(new CustomEvent('newNotificationReceived', { detail: data }));
                }
            })
            .catch(function (error) {
                console.error('[Notification Module] Polling error:', error);
            });
    }

    // Initialize module
    document.addEventListener('DOMContentLoaded', function () {
        requestNotificationPermission();
        // Initial check immediately
        checkNotifications();
        // Schedule recurring poll every 30 seconds
        setInterval(checkNotifications, POLLING_INTERVAL);
    });

    // Expose utility globally
    window.NotificationPoller = {
        check: checkNotifications,
        getLastId: function () {
            return localStorage.getItem('notification_last_id') || 0;
        },
        resetLastId: function () {
            localStorage.setItem('notification_last_id', 0);
            console.log('[Notification Module] last_id reset to 0');
        }
    };
})();
