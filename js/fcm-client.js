/**
 * ABSS FCM Token Register & Device Connection Engine
 * Configured with Firebase Project: abss-notification
 * Compatible with Web Push, Shiaho WebToApp v2.4.3, and Android WebViews.
 */
(function() {
    'use strict';

    const FIREBASE_CONFIG = {
        apiKey: "AIzaSyCWHzgexBb-ogRJ6ypTTMjbGUT0768wmE8",
        authDomain: "abss-notification.firebaseapp.com",
        projectId: "abss-notification",
        storageBucket: "abss-notification.firebasestorage.app",
        messagingSenderId: "343001874555",
        appId: "1:343001874555:web:7d97e7f76603009b0962de",
        measurementId: "G-20VSNX1DSL"
    };

    const VAPID_KEY = "BLBC9JquNYYaHFTiJuzrH50jyTBweuMdgSDkNZpHlyf_JhPgiPUa1l1bokgWdho1xo4YPpnk33-adM7qX1KcM3M";

    function getBasePath() {
        const baseEl = document.querySelector('base');
        let basePath = '/abss/';
        if (baseEl && baseEl.getAttribute('href')) {
            basePath = baseEl.getAttribute('href');
        }
        return basePath.replace(/\/$/, '') + '/';
    }

    function getApiUrl() {
        return getBasePath() + 'api/register-token.php';
    }

    // Register FCM Token to MySQL Database (`fcm_tokens`)
    window.registerFcmDeviceToken = function(fcmToken, deviceType, appVersion) {
        if (!fcmToken) return;

        const payload = {
            token: fcmToken,
            device_type: deviceType || 'android',
            app_version: appVersion || '1.0.0'
        };

        fetch(getApiUrl(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.status) {
                console.log('[ABSS FCM] Device connected successfully (ID: ' + data.token_id + ')');
            }
        })
        .catch(function(err) {
            console.warn('[ABSS FCM] Registration notice:', err);
        });
    };

    // Shiaho WebToApp v2.4.3 & Native Android App Callback Listener
    window.onFcmTokenReceived = function(token) {
        if (token) {
            window.registerFcmDeviceToken(token, 'android_app', '2.4.3');
        }
    };

    if (window.Android && typeof window.Android.getFcmToken === 'function') {
        try {
            const token = window.Android.getFcmToken();
            if (token) {
                window.registerFcmDeviceToken(token, 'android_app', '2.4.3');
            }
        } catch(e) {}
    }

    // Ensure Unique Browser Device ID is connected even before FCM permission granted
    function ensureWebDeviceConnection() {
        let webId = localStorage.getItem('abss_web_device_id');
        if (!webId) {
            webId = 'web_device_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now().toString(36);
            localStorage.setItem('abss_web_device_id', webId);
        }
        window.registerFcmDeviceToken(webId, 'web_browser', '1.0.0');
    }

    // Initialize Web Push FCM with Service Worker Scope
    function initWebFcm() {
        // Register Web Device ID
        ensureWebDeviceConnection();

        if (!('serviceWorker' in navigator) || !('Notification' in window)) {
            return;
        }

        function loadScript(src) {
            return new Promise(function(resolve, reject) {
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        if (typeof firebase === 'undefined') {
            loadScript('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js')
                .then(function() {
                    return loadScript('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');
                })
                .then(function() {
                    setupFirebaseMessaging();
                })
                .catch(function(e) {
                    console.warn('[ABSS FCM] Firebase SDK load notice:', e);
                });
        } else {
            setupFirebaseMessaging();
        }
    }

    function setupFirebaseMessaging() {
        try {
            if (!firebase.apps.length) {
                firebase.initializeApp(FIREBASE_CONFIG);
            }
            const messaging = firebase.messaging();
            const swPath = getBasePath() + 'firebase-messaging-sw.js';

            navigator.serviceWorker.register(swPath).then(function(registration) {
                if (Notification.permission === 'granted') {
                    messaging.getToken({ 
                        vapidKey: VAPID_KEY,
                        serviceWorkerRegistration: registration 
                    }).then(function(currentToken) {
                        if (currentToken) {
                            window.registerFcmDeviceToken(currentToken, 'web_browser', '1.0.0');
                        }
                    }).catch(function(err) {
                        console.warn('[ABSS FCM] Token retrieval notice:', err);
                    });
                }
            }).catch(function(swErr) {
                console.warn('[ABSS FCM] Service Worker registration notice:', swErr);
            });

            messaging.onMessage(function(payload) {
                console.log('[ABSS FCM] Foreground push message received:', payload);
                if (window.ABSSNotificationApp && typeof window.ABSSNotificationApp.checkNow === 'function') {
                    window.ABSSNotificationApp.checkNow();
                }
            });

        } catch (err) {
            console.warn('[ABSS FCM] Setup notice:', err);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWebFcm);
    } else {
        initWebFcm();
    }
})();
