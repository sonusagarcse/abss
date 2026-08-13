/**
 * ABSS FCM Token Register & Device Connection Engine
 * Configured with Firebase Project: abss-notification
 * Optimized specifically for Shiaho WebToApp v2.4.3, Android WebViews, and Web Push.
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
        if (!fcmToken || fcmToken.length < 10) return;

        const payload = {
            token: fcmToken,
            device_type: deviceType || 'android_app',
            app_version: appVersion || '2.4.3'
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
                console.log('[ABSS FCM] Android App Device connected (ID: ' + data.token_id + ')');
            }
        })
        .catch(function(err) {
            console.warn('[ABSS FCM] Registration notice:', err);
        });
    };

    // 1. Capture FCM Token from URL Parameters (Shiaho WebToApp URL Injection)
    function captureTokenFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        let token = urlParams.get('fcm_token') || urlParams.get('fcmToken') || urlParams.get('device_token') || urlParams.get('push_token');
        
        if (!token && window.location.hash) {
            const hashMatch = window.location.hash.match(/fcm_token=([^&]+)/);
            if (hashMatch) token = hashMatch[1];
        }

        if (token) {
            console.log('[ABSS FCM] Captured Android token from URL parameters.');
            window.registerFcmDeviceToken(token, 'android_app', '2.4.3');
        }
    }

    // 2. Shiaho WebToApp v2.4.3 Native Callback Hooks
    window.onFcmTokenReceived = function(token) {
        if (token) {
            console.log('[ABSS FCM] Received token via onFcmTokenReceived');
            window.registerFcmDeviceToken(token, 'android_app', '2.4.3');
        }
    };

    window.setFcmToken = function(token) {
        if (token) {
            console.log('[ABSS FCM] Received token via setFcmToken');
            window.registerFcmDeviceToken(token, 'android_app', '2.4.3');
        }
    };

    window.onTokenReceived = function(token) {
        if (token) {
            console.log('[ABSS FCM] Received token via onTokenReceived');
            window.registerFcmDeviceToken(token, 'android_app', '2.4.3');
        }
    };

    // 3. Shiaho WebToApp & Android JavascriptInterface Detection
    function checkAndroidBridge() {
        try {
            if (window.Android && typeof window.Android.getFcmToken === 'function') {
                const t = window.Android.getFcmToken();
                if (t) window.registerFcmDeviceToken(t, 'android_app', '2.4.3');
            }
            if (window.WebToApp && typeof window.WebToApp.getFcmToken === 'function') {
                const t = window.WebToApp.getFcmToken();
                if (t) window.registerFcmDeviceToken(t, 'android_app', '2.4.3');
            }
            if (window.ShiahoApp && typeof window.ShiahoApp.getFcmToken === 'function') {
                const t = window.ShiahoApp.getFcmToken();
                if (t) window.registerFcmDeviceToken(t, 'android_app', '2.4.3');
            }
        } catch(e) {}
    }

    // 4. Web Browser Fallback Registration
    function ensureWebDeviceConnection() {
        const isAndroidApp = window.navigator.userAgent.indexOf('WebToApp') !== -1 || 
                             window.navigator.userAgent.indexOf('Shiaho') !== -1 ||
                             window.Android !== undefined ||
                             window.WebToApp !== undefined;

        if (isAndroidApp) {
            return; // Skip fallback for native Android APK
        }

        let webId = localStorage.getItem('abss_web_device_id');
        if (!webId) {
            webId = 'web_device_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now().toString(36);
            localStorage.setItem('abss_web_device_id', webId);
        }
        window.registerFcmDeviceToken(webId, 'web_browser', '1.0.0');
    }

    // Initialize FCM Detection Engine
    function initFcmClient() {
        captureTokenFromUrl();
        checkAndroidBridge();
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
                .catch(function(e) {});
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
                    }).catch(function(err) {});
                }
            }).catch(function(swErr) {});

            messaging.onMessage(function(payload) {
                if (window.ABSSNotificationApp && typeof window.ABSSNotificationApp.checkNow === 'function') {
                    window.ABSSNotificationApp.checkNow();
                }
            });

        } catch (err) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFcmClient);
    } else {
        initFcmClient();
    }
})();
