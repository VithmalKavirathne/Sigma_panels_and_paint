/*
 * Sigma Panels & Paint - Admin Javascript
 * Replaces broken table/preview thumbnails with a clean "No image" box
 * so the admin never shows the browser's broken-image icon.
 */
(function () {
    'use strict';

    function fallbackThumb(img) {
        if (!img || img.getAttribute('data-fb')) { return; }
        img.setAttribute('data-fb', '1');
        var span = document.createElement('span');
        span.className = 'thumb thumb-empty';
        span.textContent = 'No image';
        if (img.parentNode) { img.parentNode.replaceChild(span, img); }
    }

    // Catch load errors (capture phase) for any .thumb image.
    window.addEventListener('error', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG' && t.classList && t.classList.contains('thumb')) {
            fallbackThumb(t);
        }
    }, true);

    // Sweep thumbs that already failed before this script ran.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('img.thumb').forEach(function (img) {
            if (img.complete && img.naturalWidth === 0) { fallbackThumb(img); }
        });
    });
})();
