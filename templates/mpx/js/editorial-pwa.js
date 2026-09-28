(function () {
    'use strict';

    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/service-worker.js', {
            scope: '/',
            updateViaCache: 'none'
        }).catch(function () {
            // PWA support is progressive; registration failure must not affect the site.
        });
    });
}());
