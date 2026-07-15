/*
 * Sigma Panels & Paint - Homepage Animations
 * Final animation set:
 *   1. Hero car - one complete static photograph with a single optional
 *      entrance (fade + slide, once). No filters, gloss, or ScrollTrigger.
 *   2. Admin-managed paint booth video (#paint-process) playback via one
 *      lightweight IntersectionObserver (no ScrollTrigger / pin / Lenis).
 *
 * Everything respects prefers-reduced-motion and works without JS.
 * Removed: the filter/mask-based hero paint reveal and stage pills, the
 * fake scroll-driven paint-booth SVG sequence, and older demo code.
 */

/* ------------------------------------------------------------------
 * 1. Hero car - ONE complete static photograph.
 *    No colour/filter animation, no gloss, no stage pills, no
 *    ScrollTrigger. A single, optional one-off entrance (fade + 15px
 *    slide, 0.7s, plays once) runs only when GSAP is present and the
 *    user has not requested reduced motion. Without JS, or with reduced
 *    motion, the complete car is simply shown immediately.
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var car = document.querySelector('.hero-real-car[data-hero-car]');
        if (!car) { return; }

        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) { return; } // CSS already shows the complete car; no entrance.

        if (typeof window.gsap !== 'undefined') {
            // Single entrance tween - no ScrollTrigger, no filters, plays once.
            window.gsap.from(car, { opacity: 0, x: 15, duration: 0.7, ease: 'power2.out' });
        } else {
            // No GSAP: fall back to the CSS keyframe entrance (also one-off).
            car.classList.add('hero-car-enter');
        }
    });
})();

/* ------------------------------------------------------------------
 * 2. Admin-managed paint booth video (#paint-process .paint-booth-video).
 *    One lightweight IntersectionObserver drives playback: play when
 *    comfortably in view, pause when off-screen or the tab is hidden.
 *    No ScrollTrigger, no pinning, no Lenis, no requestAnimationFrame.
 *    Respects prefers-reduced-motion (no autoplay; play button offered).
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var section = document.getElementById('paint-process');
        if (!section || !section.classList.contains('paint-video-section')) { return; }

        var video = section.querySelector('.paint-booth-video');
        var playBtn = section.querySelector('.paint-video-play');
        if (!video) { return; }

        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var wantsAutoplay = video.hasAttribute('autoplay') && !reduce;
        var isVisible = false;

        function showPlayButton() {
            if (playBtn) { playBtn.hidden = false; }
        }
        function hidePlayButton() {
            if (playBtn) { playBtn.hidden = true; }
        }

        // autoplay only works muted; guard the play() promise so a rejected
        // autoplay (browser policy) simply reveals the manual play button.
        function attemptPlay() {
            var p = video.play();
            if (p && typeof p.then === 'function') {
                p.then(hidePlayButton).catch(showPlayButton);
            }
        }

        // Manual play never fails silently.
        if (playBtn) {
            playBtn.addEventListener('click', function () {
                video.muted = true;
                var p = video.play();
                if (p && typeof p.then === 'function') {
                    p.then(hidePlayButton).catch(function () {});
                } else {
                    hidePlayButton();
                }
            });
        }

        // Reduced motion / no autoplay: show poster (or first frame) + play button.
        if (!wantsAutoplay) {
            // Prevent the browser's own autoplay attribute from firing, and stop
            // any playback it may have already started before this script ran.
            video.removeAttribute('autoplay');
            video.autoplay = false;
            if (!video.paused) { video.pause(); }
            showPlayButton();
        }

        if (!('IntersectionObserver' in window)) {
            // No observer support: if autoplay was requested, just try once.
            if (wantsAutoplay) { attemptPlay(); }
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            for (var i = 0; i < entries.length; i++) {
                var entry = entries[i];
                isVisible = entry.isIntersecting && entry.intersectionRatio >= 0.35;
                if (isVisible) {
                    if (wantsAutoplay && !document.hidden) { attemptPlay(); }
                } else if (!video.paused) {
                    video.pause();
                }
            }
        }, { threshold: [0, 0.35, 0.6] });

        observer.observe(video);

        // Pause when the tab is hidden; resume when it returns (if visible).
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                if (!video.paused) { video.pause(); }
            } else if (wantsAutoplay && isVisible) {
                attemptPlay();
            }
        });
    });
})();
