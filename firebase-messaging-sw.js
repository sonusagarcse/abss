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
    const notificationTitle = payload.notification ? payload.notification.title : (payload.data ? payload.data.title : 'ABSS Notification');
    const notificationOptions = {
        body: payload.notification ? payload.notification.body : (payload.data ? payload.data.message : ''),
        icon: (payload.data && payload.data.image_url) ? payload.data.image_url : '/abss/assets/logo.png',
        badge: '/abss/assets/logo.png',
        data: {
            url: (payload.data && payload.data.click_url) ? payload.data.click_url : (payload.webpush && payload.webpush.fcm_options ? payload.webpush.fcm_options.link : '/abss/')
        }
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/abss/';
    event.waitUntil(
        clients.openWindow(targetUrl)
    );
});