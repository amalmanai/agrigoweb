let messaging = null;

try {
    importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
    importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

    firebase.initializeApp({
        apiKey: 'AIzaSyCgSsEUxbMznBirMAN5sVrBgsVdQES6GCg',
        authDomain: 'agrigo-a6f05.firebaseapp.com',
        projectId: 'agrigo-a6f05',
        storageBucket: 'agrigo-a6f05.firebasestorage.app',
        messagingSenderId: '819705578051',
        appId: '1:819705578051:web:32e154a1f9611a0fb4caf6',
        measurementId: 'G-2GFHJR7W0C'
    });

    messaging = firebase.messaging();

    messaging.onBackgroundMessage(function (payload) {
        const notification = payload && payload.notification ? payload.notification : {};
        const title = notification.title || 'AgriGo';
        const options = {
            body: notification.body || 'Nouvelle notification de vente.',
            icon: notification.icon || '/images/logo.png',
            data: payload && payload.data ? payload.data : {},
        };

        self.registration.showNotification(title, options);
    });
} catch (error) {
    console.error('Firebase service worker initialization failed:', error);
}

self.addEventListener('push', function (event) {
    if (messaging) {
        return;
    }

    const data = event.data ? (() => {
        try {
            return event.data.json();
        } catch (error) {
            return { notification: { body: event.data.text() } };
        }
    })() : {};

    const notification = data.notification ? data.notification : {};
    const title = notification.title || 'AgriGo';
    const options = {
        body: notification.body || 'Nouvelle notification de vente.',
        icon: notification.icon || '/images/logo.png',
        data: data.data ? data.data : {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const notificationData = event.notification && event.notification.data ? event.notification.data : {};
    const clickAction = notificationData.click_action || '/vente/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if ('focus' in client && client.url.includes('/vente')) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(clickAction);
            }

            return undefined;
        })
    );
});