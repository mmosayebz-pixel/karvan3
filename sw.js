// سرویس ورکر برای PWA - شماره 001
const CACHE_NAME = 'karvan-cache-v1';
const urlsToCache = [
    '/',
    '/index.html',
    '/manager.html',
    '/leader.html',
    '/user.html',
    '/manifest.json'
];

// نصب سرویس ورکر - شماره 002
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});
// پایان تابع install

// فعال‌سازی و حذف کش قدیمی - شماره 003
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
// پایان تابع activate

// دریافت و پاسخ به درخواست‌ها - شماره 004
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});
// پایان تابع fetch