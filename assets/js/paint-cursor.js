/*
 * Sigma Panels & Paint - Global paint-gun cursor (public pages only).
 *
 * One cursor element, one pointermove handler, one controller. No particles,
 * no spray clouds, no trails, no extra Lenis instance. Uses GSAP quickTo when
 * available (GSAP already ships on public pages), otherwise a single lightweight
 * requestAnimationFrame loop. Only transform + opacity are animated.
 *
 * Enabled only when ALL are true: (pointer: fine), (hover: hover),
 * viewport >= 1024px, and prefers-reduced-motion is not 'reduce'. When those
 * conditions are not met, no listeners are attached, the native cursor is left
 * alone, and the paint-gun element stays hidden (display:none via CSS).
 */
(function () {
    'use strict';

    var enableMq = window.matchMedia('(pointer: fine) and (hover: hover) and (min-width: 1024px)');
    var motionMq = window.matchMedia('(prefers-reduced-motion: reduce)');

    function canEnable() { return enableMq.matches && !motionMq.matches; }

    var el = null;
    var active = false;
    var visible = false;
    var useGsap = false;
    var xTo = null, yTo = null;

    // Nozzle-tip offset inside the 36px artwork, so the tip sits under the pointer.
    var OFFX = 4, OFFY = 12;

    // rAF fallback state.
    var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;

    var HOVER_SEL = 'a,button,[role="button"],.chip,.faq-q,summary,label,' +
        'input[type="submit"],input[type="button"],.gallery-item,.lux-service-tile,.paint-video-play';
    var TEXT_SEL = 'input:not([type="submit"]):not([type="button"]):not([type="reset"])' +
        ':not([type="checkbox"]):not([type="radio"]):not([type="file"]),textarea,[contenteditable="true"]';

    function loop() {
        cx += (tx - cx) * 0.35;
        cy += (ty - cy) * 0.35;
        el.style.transform = 'translate3d(' + cx + 'px,' + cy + 'px,0)';
        if (Math.abs(tx - cx) > 0.1 || Math.abs(ty - cy) > 0.1) {
            raf = requestAnimationFrame(loop);
        } else {
            el.style.transform = 'translate3d(' + tx + 'px,' + ty + 'px,0)';
            raf = null;
        }
    }

    function show() { if (!visible) { visible = true; el.style.opacity = '1'; } }
    function hide() { if (visible) { visible = false; el.style.opacity = '0'; } }

    function onMove(e) {
        var x = e.clientX - OFFX;
        var y = e.clientY - OFFY;
        if (useGsap) { xTo(x); yTo(y); }
        else { tx = x; ty = y; if (!raf) { raf = requestAnimationFrame(loop); } }
        show();
    }

    function onOver(e) {
        var t = e.target;
        if (t && t.closest) {
            if (t.closest(HOVER_SEL)) { el.classList.add('is-hover'); }
            if (t.closest(TEXT_SEL)) { el.classList.add('is-text'); }
        }
    }

    function onOut(e) {
        var to = e.relatedTarget;
        // No relatedTarget => pointer left the window or entered an iframe/plugin
        // (where tracking is unreliable): hide until it returns.
        if (!to) { hide(); }
        if (el.classList.contains('is-hover') && !(to && to.closest && to.closest(HOVER_SEL))) {
            el.classList.remove('is-hover');
        }
        if (el.classList.contains('is-text') && !(to && to.closest && to.closest(TEXT_SEL))) {
            el.classList.remove('is-text');
        }
    }

    function onDown() { el.classList.add('is-down'); }
    function onUp() { el.classList.remove('is-down'); }
    function onDocLeave() { hide(); }
    function onBlur() { hide(); }
    function onVis() { if (document.hidden) { hide(); } }

    function setup() {
        if (active) { return; }
        el = document.querySelector('.paint-cursor');
        if (!el) { return; }
        active = true;

        useGsap = (typeof window.gsap !== 'undefined') && (typeof window.gsap.quickTo === 'function');
        if (useGsap) {
            xTo = window.gsap.quickTo(el, 'x', { duration: 0.12, ease: 'power3' });
            yTo = window.gsap.quickTo(el, 'y', { duration: 0.12, ease: 'power3' });
        }

        // Only now do we hide the native cursor (scoped to this class in CSS).
        document.documentElement.classList.add('paint-cursor-enabled');

        window.addEventListener('pointermove', onMove, { passive: true });
        window.addEventListener('pointerover', onOver, { passive: true });
        window.addEventListener('pointerout', onOut, { passive: true });
        window.addEventListener('pointerdown', onDown, { passive: true });
        window.addEventListener('pointerup', onUp, { passive: true });
        document.documentElement.addEventListener('mouseleave', onDocLeave);
        window.addEventListener('blur', onBlur);
        document.addEventListener('visibilitychange', onVis);
    }

    function teardown() {
        if (!active) { return; }
        active = false;

        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerover', onOver);
        window.removeEventListener('pointerout', onOut);
        window.removeEventListener('pointerdown', onDown);
        window.removeEventListener('pointerup', onUp);
        document.documentElement.removeEventListener('mouseleave', onDocLeave);
        window.removeEventListener('blur', onBlur);
        document.removeEventListener('visibilitychange', onVis);

        if (raf) { cancelAnimationFrame(raf); raf = null; }
        visible = false;
        if (el) {
            el.classList.remove('is-hover', 'is-down', 'is-text');
            el.style.opacity = '0';
            if (useGsap && window.gsap) { window.gsap.set(el, { clearProps: 'transform' }); }
        }
        // Restore the native cursor.
        document.documentElement.classList.remove('paint-cursor-enabled');
    }

    function evaluate() { if (canEnable()) { setup(); } else { teardown(); } }

    function start() {
        evaluate();
        // Re-evaluate if the viewport, pointer capability, or motion preference
        // changes (e.g. resizing across the 1024px threshold, docking a tablet).
        var onChange = function () { evaluate(); };
        if (enableMq.addEventListener) {
            enableMq.addEventListener('change', onChange);
            motionMq.addEventListener('change', onChange);
        } else if (enableMq.addListener) {
            enableMq.addListener(onChange);
            motionMq.addListener(onChange);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
