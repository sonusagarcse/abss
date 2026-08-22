importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyCWHzgexBb-ogRJ6ypTTMjbGUT0768wmE8",
    authDomain: "abss-notification.firebaseapp.com",
    projectId: "abss-notification",
    storageBucket: "abss-notification.firebasestorage.app",
    messagingSenderId: "343001874555",
    appId: "1:343001874555:web:7d97e7f76603009b0962de",
    measurementId: "G-20VSNX1DSL"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    console.log('[ABSS FCM ServiceWorker] Background notification received:', payload);
    
    // If the payload already contains a notification object, the browser SDK renders it automatically.
    // Only manually trigger showNotification for data-only messages to avoid duplicate notifications.
    if (payload.notification) {
        console.log('[ABSS FCM ServiceWorker] Notification payload already rendered by browser SDK.');
        return;
    }

    const notificationTitle = (payload.data && payload.data.title) ? payload.data.title : 'ABSS Notification';
    const tagKey = (payload.data && payload.data.tag) ? payload.data.tag : ('abss_tag_' + Date.now());

    const notificationOptions = {
        body: (payload.data && (payload.data.body || payload.data.message)) ? (payload.data.body || payload.data.message) : '',
        icon: (payload.data && payload.data.image_url) ? payload.data.image_url : '/abss/assets/logo.png',
        badge: '/abss/assets/logo.png',
        tag: tagKey,
        renotify: false,
        data: {
            url: (payload.data && (payload.data.click_url || payload.data.url)) ? (payload.data.click_url || payload.data.url) : '/abss/'
        }
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    let targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/abss/';
    
    // Normalize relative paths
    if (!targetUrl.startsWith('http')) {
        targetUrl = self.location.origin + (targetUrl.startsWith('/') ? targetUrl : '/' + targetUrl);
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if ('focus' in client) {
                    client.focus();
                    if ('navigate' in client && client.url !== targetUrl) {
                        return client.navigate(targetUrl);
                    }
                    return;
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});