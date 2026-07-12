/*
 * Sigma Panels & Paint - Public Javascript
 * Phase 9 shared behaviours + Phase 13 branded image fallback.
 */
(function () {
    'use strict';

    /* ---- Image fallback: swap a failed image to a local branded placeholder,
       never show a broken-image icon, keep layout height stable. ---- */
    function applyImageFallback(img) {
        if (!img || img.getAttribute('data-fb')) { return; }
        img.setAttribute('data-fb', '1');
        var src = img.getAttribute('src') || '';
        if (src.indexOf('/logo/') !== -1) {
            img.src = src.replace(/[^\/]+$/, 'logo.svg');
        } else if (src.indexOf('/placeholders/') !== -1) {
            img.src = src.replace(/[^\/]+$/, 'placeholder.svg');
        } else if (src.indexOf('placeholder.svg') === -1 && src.indexOf('logo.svg') === -1) {
            img.style.display = 'none';
        }
    }

    // Catch errors that fire before/while this script runs (capture phase).
    window.addEventListener('error', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG') { applyImageFallback(t); }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {

        // Sweep any images that already failed before the listener attached.
        document.querySelectorAll('img').forEach(function (img) {
            if (img.complete && img.naturalWidth === 0) { applyImageFallback(img); }
        });

        /* ---- 1. Glassy sticky header on scroll ---- */
        var header = document.getElementById('main-header');
        if (header) {
            var onScroll = function () {
                if (window.scrollY > 50) { header.classList.add('scrolled'); }
                else { header.classList.remove('scrolled'); }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        /* ---- 2. Mobile menu toggle ---- */
        var toggle = document.getElementById('nav-toggle');
        var menu = document.getElementById('mobile-menu');
        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                var open = menu.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                menu.setAttribute('aria-hidden', open ? 'false' : 'true');
                var icon = toggle.querySelector('.material-symbols-outlined');
                if (icon) { icon.textContent = open ? 'close' : 'menu'; }
            });
        }

        /* ---- 3. Scroll reveal via IntersectionObserver ---- */
        var revealEls = document.querySelectorAll('[data-reveal]');
        if ('IntersectionObserver' in window && revealEls.length) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
            revealEls.forEach(function (el) { observer.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('revealed'); });
        }

        /* ---- 4. FAQ accordion ---- */
        var faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(function (item) {
            var q = item.querySelector('.faq-q');
            var a = item.querySelector('.faq-a');
            if (!q || !a) { return; }
            q.addEventListener('click', function () {
                var isOpen = item.classList.contains('open');
                faqItems.forEach(function (other) {
                    other.classList.remove('open');
                    var oa = other.querySelector('.faq-a');
                    if (oa) { oa.style.maxHeight = null; }
                });
                if (!isOpen) {
                    item.classList.add('open');
                    a.style.maxHeight = a.scrollHeight + 'px';
                }
            });
        });

        /* ---- 5. Gallery category filter ---- */
        var chips = document.querySelectorAll('.chip[data-filter]');
        var galleryItems = document.querySelectorAll('.gallery-item[data-category]');
        if (chips.length && galleryItems.length) {
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var filter = chip.getAttribute('data-filter');
                    chips.forEach(function (c) { c.classList.remove('active'); });
                    chip.classList.add('active');
                    galleryItems.forEach(function (item) {
                        var cat = item.getAttribute('data-category');
                        item.style.display = (filter === 'all' || filter === cat) ? '' : 'none';
                    });
                });
            });
        }

    });
})();
