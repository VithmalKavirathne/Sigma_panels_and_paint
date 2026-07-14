/*
 * Sigma Panels & Paint - Homepage Animations
 * Final animation set (cleanup pass):
 *   1. Hero primer -> painted reveal + one clear-coat gloss sweep (scrubbed).
 *   2. Scroll-driven paint-booth sequence (#paint-process).
 *
 * All GSAP/ScrollTrigger work rides the shared foundation (motion-init.js);
 * everything respects prefers-reduced-motion and stays light on mobile.
 * Removed in cleanup: dormant paint-gun/particle/parallax code, the playable
 * paint-booth demo, the desktop spray-gun cursor, and the inspection-scan.
 */

/* ------------------------------------------------------------------
 * 1. Hero primer -> painted reveal + one clear-coat gloss sweep.
 *    One scrubbed ScrollTrigger (desktop) / one play-once timeline (mobile).
 *    Fails safe to a finished car if the libraries are unavailable.
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var reveal = document.querySelector('.real-car-reveal[data-real-car]');
        if (!reveal) { return; }

        var stagesWrap = document.querySelector('.paint-stages');
        var gloss      = reveal.querySelector('.real-car-gloss');

        var hasGsap = typeof window.gsap !== 'undefined';
        var hasST   = hasGsap && typeof window.ScrollTrigger !== 'undefined';
        var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var mobile  = window.matchMedia('(max-width: 767px)').matches;

        function setPaint(v) { reveal.style.setProperty('--paint', v); }
        function setSweep(v) { if (gloss) { gloss.style.setProperty('--sweep', v); } }

        function setStage(name) {
            if (!stagesWrap) { return; }
            var spans = stagesWrap.querySelectorAll('span');
            for (var i = 0; i < spans.length; i++) {
                spans[i].classList.toggle('active', spans[i].getAttribute('data-stage') === name);
            }
        }

        // Deterministic stage mapping with hysteresis (no boundary flicker).
        var STAGE_BANDS = [
            { name: 'prep',   lo: 0.00, hi: 0.10 },
            { name: 'paint',  lo: 0.10, hi: 0.70 },
            { name: 'clear',  lo: 0.70, hi: 0.90 },
            { name: 'finish', lo: 0.90, hi: 1.01 }
        ];
        var STAGE_HYST = 0.02;
        var currentStage = null;
        function resolveStage(p) {
            if (currentStage) {
                for (var i = 0; i < STAGE_BANDS.length; i++) {
                    if (STAGE_BANDS[i].name === currentStage) {
                        if (p >= STAGE_BANDS[i].lo - STAGE_HYST && p < STAGE_BANDS[i].hi + STAGE_HYST) { return currentStage; }
                        break;
                    }
                }
            }
            for (var j = 0; j < STAGE_BANDS.length; j++) {
                if (p >= STAGE_BANDS[j].lo && p < STAGE_BANDS[j].hi) { currentStage = STAGE_BANDS[j].name; return currentStage; }
            }
            currentStage = p < 0.5 ? 'prep' : 'finish';
            return currentStage;
        }

        // Fallback (no libraries) or reduced motion: leave the CSS default -
        // the finished painted photo is shown (no .rc-anim, no mask, no ScrollTrigger).
        if (!hasGsap || !hasST || reduce) {
            setStage('finish');
            return;
        }

        // Engage the masked reveal: PREP starts on the primer photo.
        reveal.classList.add('rc-anim');
        setPaint(0);
        setSweep(-60);

        var state = { paint: 0 };
        var sweep = { v: -60 };

        if (mobile) {
            // Short auto-play on enter; no scrub, no pin.
            var mtl = window.gsap.timeline({
                paused: true,
                onUpdate: function () { setStage(resolveStage(mtl.progress())); },
                onComplete: function () { setStage('finish'); }
            });
            // PREP -> PAINT (primer photo reveals to the painted photo)
            mtl.to(state, { paint: 0, duration: 0.2, ease: 'none',
                    onUpdate: function () { setPaint(state.paint); } })
               .to(state, { paint: 1, duration: 1.2, ease: 'none',
                    onUpdate: function () { setPaint(state.paint); } });
            // CLEAR COAT - one gloss sweep clipped to the car silhouette
            if (gloss) {
                mtl.to(gloss, { opacity: 1, duration: 0.2, ease: 'power2.out' }, '>');
                mtl.to(sweep, { v: 260, duration: 0.8, ease: 'power1.inOut',
                        onUpdate: function () { setSweep(sweep.v); } }, '<');
                mtl.to(gloss, { opacity: 0, duration: 0.28, ease: 'power2.in' }, '>-0.1');
            } else {
                mtl.to({}, { duration: 0.8 });
            }
            // FINISH - painted car settles forward ~8px
            mtl.to(reveal, { x: -8, duration: 0.34, ease: 'power2.out' }, '>-0.1');

            window.ScrollTrigger.create({
                trigger: '#hero', start: 'top 80%', once: true,
                onEnter: function () { mtl.play(); }
            });
        } else {
            // Desktop scrubbed timeline; no pinning. One ScrollTrigger.
            var tl = window.gsap.timeline({
                scrollTrigger: {
                    trigger: '#hero',
                    start: 'top top',
                    end: '+=55%',
                    scrub: 0.8,
                    onUpdate: function (self) { setStage(resolveStage(self.progress())); }
                }
            });

            // PREP (0-1) - primer photo held
            tl.to(state, { paint: 0, duration: 1, ease: 'none',
                    onUpdate: function () { setPaint(state.paint); } });
            // PAINT (1-7) - painted photo reveals front -> rear through the mask
            tl.to(state, { paint: 1, duration: 6, ease: 'none',
                    onUpdate: function () { setPaint(state.paint); } });
            // CLEAR COAT (7-9) - one gloss sweep clipped to the car silhouette
            if (gloss) {
                tl.to(gloss, { opacity: 1, duration: 0.3, ease: 'power2.out' }, 7);
                tl.to(sweep, { v: 260, duration: 1.8, ease: 'power1.inOut',
                        onUpdate: function () { setSweep(sweep.v); } }, 7);
                tl.to(gloss, { opacity: 0, duration: 0.4, ease: 'power2.in' }, 8.6);
            } else {
                tl.to({}, { duration: 2 });
            }
            // FINISH (9-10) - painted car settles forward ~8px
            tl.to(reveal, { x: -8, duration: 1, ease: 'power2.out' }, 9);
        }
    });
})();

/* ------------------------------------------------------------------
 * 2. Scroll-driven paint-booth sequence (#paint-process).
 *    One GSAP timeline with one ScrollTrigger. Desktop pins briefly and
 *    scrubs; mobile plays a short auto sequence on enter. Reduced motion /
 *    no libraries: the finished booth state is shown with no ScrollTrigger.
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var section = document.getElementById('paint-process');
        if (!section) { return; }

        var stageEl    = section.querySelector('.pb-stage');
        var car        = section.querySelector('.pb-car');
        var floor      = section.querySelector('.pb-floor');
        var glossBar   = section.querySelector('.pb-gloss i');
        var stagesWrap = section.querySelector('.pb-stages');
        var lights     = section.querySelectorAll('.pb-lights i');
        var tapes      = section.querySelectorAll('.pb-tape i');
        var mist       = section.querySelectorAll('.pb-mist i');

        var hasGsap = typeof window.gsap !== 'undefined';
        var hasST   = hasGsap && typeof window.ScrollTrigger !== 'undefined';
        var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var mobile  = window.matchMedia('(max-width: 767px)').matches;

        function setPB(v) { if (car) { car.style.setProperty('--pb', v); } }

        var ORDER = ['prep', 'mask', 'spray', 'colour', 'clear', 'finish'];
        var BANDS = [
            { n: 'prep',   lo: 0.00, hi: 0.25 },
            { n: 'mask',   lo: 0.25, hi: 0.375 },
            { n: 'spray',  lo: 0.375, hi: 0.54 },
            { n: 'colour', lo: 0.54, hi: 0.75 },
            { n: 'clear',  lo: 0.75, hi: 0.875 },
            { n: 'finish', lo: 0.875, hi: 1.01 }
        ];
        var HY = 0.015, cur = null;
        function resolve(p) {
            if (cur) {
                for (var i = 0; i < BANDS.length; i++) {
                    if (BANDS[i].n === cur) {
                        if (p >= BANDS[i].lo - HY && p < BANDS[i].hi + HY) { return cur; }
                        break;
                    }
                }
            }
            for (var j = 0; j < BANDS.length; j++) {
                if (p >= BANDS[j].lo && p < BANDS[j].hi) { cur = BANDS[j].n; return cur; }
            }
            cur = p < 0.5 ? 'prep' : 'finish';
            return cur;
        }
        function setStage(name) {
            if (!stagesWrap) { return; }
            var idx = ORDER.indexOf(name);
            var spans = stagesWrap.querySelectorAll('span');
            for (var i = 0; i < spans.length; i++) {
                var st = spans[i].getAttribute('data-pbstage');
                var si = ORDER.indexOf(st);
                spans[i].classList.toggle('active', st === name);
                spans[i].classList.toggle('done', si > -1 && si < idx);
            }
        }

        // Fallback / reduced motion: finished booth state, no timeline.
        if (!hasGsap || !hasST || reduce) {
            setPB(1);
            setStage('finish');
            var k;
            for (k = 0; k < lights.length; k++) { lights[k].style.opacity = '0.85'; }
            for (k = 0; k < tapes.length; k++) { tapes[k].style.opacity = '0'; }
            if (floor) { floor.style.opacity = '0.5'; }
            return;
        }

        var gsap = window.gsap;
        var pb = { v: 0 };
        setPB(0);
        if (car) { gsap.set(car, { xPercent: -50, yPercent: -46 }); } // centre (matches CSS)

        function addTweens(tl) {
            // PREP - booth fades into view (heading stays readable throughout)
            if (stageEl) { tl.from(stageEl, { opacity: 0, y: 24, duration: 1, ease: 'power2.out' }, 0); }
            // BOOTH LIGHTS - power on left -> right, floor appears
            if (lights.length) { tl.to(lights, { opacity: 0.9, duration: 1.4, stagger: 0.18, ease: 'power1.out' }, 1); }
            if (floor) { tl.to(floor, { opacity: 0.55, duration: 1.2, ease: 'power1.out' }, 1.2); }
            // MASK - tape fades/slides into place
            if (tapes.length) { tl.fromTo(tapes, { opacity: 0, y: -6 }, { opacity: 1, y: 0, duration: 0.5, stagger: 0.12, ease: 'power2.out' }, 3); }
            // SPRAY - mist passes front -> rear
            if (mist.length) {
                tl.fromTo(mist, { opacity: 0, xPercent: 120 },
                    { opacity: 0.7, xPercent: -150, duration: 1.3, ease: 'none', stagger: { each: 0.05, from: 'random' } }, 4.5);
                tl.to(mist, { opacity: 0, duration: 0.5, ease: 'power1.in' }, 6.0);
            }
            // COLOUR - painted metallic builds front -> rear
            tl.to(pb, { v: 1, duration: 2.5, ease: 'none', onUpdate: function () { setPB(pb.v); } }, 6.5);
            // CLEAR COAT - tape peels away + one soft reflection sweep
            if (tapes.length) { tl.to(tapes, { opacity: 0, y: -8, duration: 0.6, stagger: 0.08, ease: 'power2.in' }, 9); }
            if (glossBar) {
                tl.fromTo(glossBar, { xPercent: 180, opacity: 0 }, { xPercent: -160, duration: 1.6, ease: 'power1.inOut' }, 9);
                tl.to(glossBar, { opacity: 0.6, duration: 0.45, ease: 'power2.out' }, 9);
                tl.to(glossBar, { opacity: 0, duration: 0.6, ease: 'power2.in' }, 10.2);
            }
            // FINISH - colour settles, car eases forward, floor strengthens
            if (car)   { tl.to(car, { x: 12, y: -6, duration: 1.3, ease: 'power2.out' }, 10.5); }
            if (floor) { tl.to(floor, { opacity: 0.72, duration: 1.0, ease: 'power1.out' }, 10.5); }
        }

        if (mobile) {
            // Short auto-play on enter; no pin, no scrub, native touch scroll kept.
            var mtl = gsap.timeline({
                paused: true,
                onUpdate: function () { setStage(resolve(mtl.progress())); },
                onComplete: function () { setStage('finish'); }
            });
            addTweens(mtl);
            mtl.timeScale(mtl.duration() / 3.4); // ~3.4s total
            window.ScrollTrigger.create({
                trigger: section, start: 'top 78%', once: true,
                onEnter: function () { mtl.play(); }
            });
        } else {
            // Desktop: scrub as the section travels through the viewport.
            // No pin - nothing is ever position:fixed, so the stage bar can
            // never stick to the top or overlap the following sections.
            var tl = gsap.timeline({
                scrollTrigger: {
                    trigger: section,
                    start: 'top 80%',
                    end: 'bottom 20%',
                    scrub: 0.8,
                    onUpdate: function (self) { setStage(resolve(self.progress())); }
                }
            });
            addTweens(tl);
        }
    });
})();
