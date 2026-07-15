/*
 * Sigma Panels & Paint - Motion Foundation
 * Phase: GSAP + ScrollTrigger + Lenis foundation (public pages only).
 *
 * Responsibilities:
 *   - Initialise Lenis smooth scrolling (gentle, no exaggerated lag).
 *   - Register GSAP ScrollTrigger and synchronise it with Lenis.
 *   - Keep native behaviour when prefers-reduced-motion: reduce is set.
 *   - Keep mobile light: native touch scrolling, no heavy effects < 768px.
 *   - Fail safe: if GSAP or Lenis are missing, the site works normally.
 *   - One test animation only: the homepage hero eyebrow fades in.
 *
 * This file intentionally does NOT touch forms, the mobile menu, routing,
 * or any existing CSS/IntersectionObserver animations.
 */
(function () {
    'use strict';

    // ---- Capability + preference checks -------------------------------------
    var hasGsap   = typeof window.gsap !== 'undefined';
    var hasST     = hasGsap && typeof window.ScrollTrigger !== 'undefined';
    var hasLenis  = typeof window.Lenis !== 'undefined';
    var reduce    = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isMobile  = window.matchMedia('(max-width: 767px)').matches;

    // Expose a tiny namespace so later phases can reuse the instances.
    window.SigmaMotion = window.SigmaMotion || { lenis: null, ready: false };

    // ---- Register ScrollTrigger (safe) --------------------------------------
    if (hasST) {
        try {
            window.gsap.registerPlugin(window.ScrollTrigger);
        } catch (err) {
            hasST = false;
            if (window.console) { console.warn('[SigmaMotion] ScrollTrigger registration failed:', err); }
        }
    }

    // ---- Initialise Lenis (skip under reduced motion) -----------------------
    var lenis = null;
    if (hasLenis && !reduce) {
        try {
            lenis = new window.Lenis({
                // Gentle, natural easing. lerp ~0.1 is calm with no exaggerated lag.
                lerp: isMobile ? 0.12 : 0.1,
                wheelMultiplier: 1,
                smoothWheel: true,
                // Leave touch to the browser: keeps mobile light and native.
                syncTouch: false,
                touchMultiplier: 1,
                autoRaf: false // we drive rAF ourselves (via GSAP ticker when present)
            });
            window.SigmaMotion.lenis = lenis;
        } catch (err) {
            lenis = null;
            if (window.console) { console.warn('[SigmaMotion] Lenis init failed:', err); }
        }
    }

    // ---- Synchronise Lenis <-> ScrollTrigger --------------------------------
    if (lenis) {
        if (hasST) {
            // Update ScrollTrigger whenever Lenis scrolls.
            lenis.on('scroll', window.ScrollTrigger.update);

            // Drive Lenis from GSAP's ticker for a single, consistent rAF loop.
            window.gsap.ticker.add(function (time) {
                lenis.raf(time * 1000); // GSAP time is seconds; Lenis wants ms.
            });
            window.gsap.ticker.lagSmoothing(0);
        } else {
            // No GSAP ticker available: drive Lenis with a plain rAF loop.
            var raf = function (time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            };
            requestAnimationFrame(raf);
        }
    }

    // ---- Anchor links must still work ---------------------------------------
    // Smoothly scroll same-page hash links through Lenis; otherwise let the
    // browser handle them natively. Never interferes with forms or the menu.
    if (lenis) {
        document.addEventListener('click', function (e) {
            var link = e.target && e.target.closest ? e.target.closest('a[href*="#"]') : null;
            if (!link) { return; }
            if (link.hasAttribute('data-lenis-external') || link.target === '_blank') { return; }

            var href = link.getAttribute('href') || '';
            var hashIndex = href.indexOf('#');
            if (hashIndex === -1) { return; }

            var hash = href.slice(hashIndex);
            if (hash === '#' || hash.length < 2) { return; }

            // Only intercept when the link points at the current page.
            var path = href.slice(0, hashIndex);
            var samePage = path === '' || path === window.location.pathname ||
                           path === (window.location.pathname + window.location.search);
            if (!samePage) { return; }

            var target = null;
            try { target = document.querySelector(hash); } catch (err) { return; }
            if (!target) { return; }

            e.preventDefault();
            lenis.scrollTo(target, { offset: 0 });
        }, false);
    }

    // ---- Test animation: homepage hero eyebrow fade-in ----------------------
    // Purely additive. If GSAP is missing or motion is reduced, the element is
    // already visible via CSS, so nothing breaks.
    function runHeroTest() {
        if (!hasGsap || reduce) { return; }
        var eyebrow = document.querySelector('#hero .lux-eyebrow');
        if (!eyebrow) { return; }
        try {
            window.gsap.from(eyebrow, {
                opacity: 0,
                y: 12,
                duration: 0.9,
                delay: 0.15,
                ease: 'power2.out'
            });
        } catch (err) {
            if (window.console) { console.warn('[SigmaMotion] hero test animation failed:', err); }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runHeroTest);
    } else {
        runHeroTest();
    }

    // Refresh ScrollTrigger once everything (fonts/images) has settled.
    if (hasST) {
        window.addEventListener('load', function () {
            try { window.ScrollTrigger.refresh(); } catch (err) {}
        });
    }

    window.SigmaMotion.ready = true;
})();
