/*
 * Sigma Panels & Paint - Homepage Animations
 * Phase 12 - paint-booth hero: ambient particles, cursor-reactive mist,
 * layered parallax, and a custom spray-gun cursor that emits nozzle spray.
 * CSS/JS only (no WebGL/Three.js/libraries). All motion respects
 * prefers-reduced-motion and is disabled on touch / <769px. Effect layers
 * are pointer-events:none so links/buttons are never blocked.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var finePointer = window.matchMedia('(min-width: 769px)').matches;
        var hero = document.getElementById('hero');

        // Reveal safety-net for the hero.
        setTimeout(function () {
            document.querySelectorAll('.hero [data-reveal]').forEach(function (el) {
                el.classList.add('revealed');
            });
        }, 400);

        if (reduce || !hero) { return; }

        /* ---- Ambient drifting particles ---- */
        var pc = document.getElementById('paint-particles');
        if (pc) {
            var count = 18;
            for (var i = 0; i < count; i++) {
                var s = document.createElement('span');
                var scale = 0.5 + Math.random() * 1.2;
                s.style.left = (Math.random() * 100) + '%';
                s.style.width = (7 * scale).toFixed(1) + 'px';
                s.style.height = (7 * scale).toFixed(1) + 'px';
                s.style.setProperty('--dur', (12 + Math.random() * 10).toFixed(1) + 's');
                s.style.setProperty('--delay', (Math.random() * 12).toFixed(1) + 's');
                s.style.setProperty('--drift', (Math.random() * 80 - 40).toFixed(0) + 'px');
                pc.appendChild(s);
            }
        }

        if (!finePointer) { return; }

        /* ---- Cursor-reactive mist + layered parallax ---- */
        var mist = document.getElementById('paint-mist');
        var parallaxEls = hero.querySelectorAll('[data-parallax]');
        var ticking = false, lastX = 0, lastY = 0;

        hero.addEventListener('mousemove', function (e) {
            lastX = e.clientX;
            lastY = e.clientY;
            if (ticking) { return; }
            ticking = true;
            requestAnimationFrame(function () {
                ticking = false;
                var rect = hero.getBoundingClientRect();
                var relX = ((lastX - rect.left) / rect.width) - 0.5;
                var relY = ((lastY - rect.top) / rect.height) - 0.5;
                if (mist) {
                    var vr = mist.getBoundingClientRect();
                    var vx = ((lastX - vr.left) / vr.width) * 100;
                    var vy = ((lastY - vr.top) / vr.height) * 100;
                    mist.style.setProperty('--mx', Math.max(-20, Math.min(120, vx)) + '%');
                    mist.style.setProperty('--my', Math.max(-20, Math.min(120, vy)) + '%');
                }
                for (var j = 0; j < parallaxEls.length; j++) {
                    var f = parseFloat(parallaxEls[j].getAttribute('data-parallax')) || 0;
                    parallaxEls[j].style.translate = (relX * f * 100).toFixed(1) + 'px ' + (relY * f * 100).toFixed(1) + 'px';
                }
            });
        });

        hero.addEventListener('mouseleave', function () {
            for (var k = 0; k < parallaxEls.length; k++) {
                parallaxEls[k].style.translate = '0px 0px';
            }
        });

        /* ---- Custom spray-gun cursor + nozzle spray ---- */
        var gun = document.getElementById('paintGun');
        var sprayLayer = document.getElementById('sprayLayer');
        if (gun) {
            var ANGLE = -8;                       // gun tilt: points right, slightly up toward the car
            var RAD = ANGLE * Math.PI / 180;
            var NOZZLE = 28;                      // nozzle-tip distance from gun centre
            var gx = 0, gy = 0, tgx = 0, tgy = 0; // current + target positions (relative to hero)
            var gunRaf = null, gunVisible = false, havePos = false, lastSpray = 0;

            function overInteractive(t) {
                return !!(t && t.closest && t.closest('a, button, input, select, textarea'));
            }

            function follow() {
                gx += (tgx - gx) * 0.22;
                gy += (tgy - gy) * 0.22;
                gun.style.transform = 'translate(' + gx.toFixed(1) + 'px,' + gy.toFixed(1) + 'px) translate(-50%,-50%) rotate(' + ANGLE + 'deg)';
                if (Math.abs(tgx - gx) > 0.4 || Math.abs(tgy - gy) > 0.4) {
                    gunRaf = requestAnimationFrame(follow);
                } else {
                    gunRaf = null;
                }
            }

            function emit() {
                if (!sprayLayer || sprayLayer.childElementCount > 50) { return; }
                var nx = gx + Math.cos(RAD) * NOZZLE;
                var ny = gy + Math.sin(RAD) * NOZZLE;
                for (var n = 0; n < 2; n++) {
                    var p = document.createElement('span');
                    var dir = RAD + (Math.random() - 0.5) * 0.65;   // cone spread
                    var dist = 50 + Math.random() * 95;
                    var sz = (3 + Math.random() * 5).toFixed(1);
                    p.style.left = nx.toFixed(1) + 'px';
                    p.style.top = ny.toFixed(1) + 'px';
                    p.style.width = sz + 'px';
                    p.style.height = sz + 'px';
                    p.style.setProperty('--tx', (Math.cos(dir) * dist).toFixed(1) + 'px');
                    p.style.setProperty('--ty', (Math.sin(dir) * dist).toFixed(1) + 'px');
                    p.style.animationDuration = (0.6 + Math.random() * 0.3).toFixed(2) + 's';
                    p.addEventListener('animationend', function () { this.remove(); });
                    sprayLayer.appendChild(p);
                }
            }

            hero.addEventListener('mouseenter', function () {
                hero.classList.add('paint-cursor-active');
            });

            hero.addEventListener('mouseleave', function () {
                hero.classList.remove('paint-cursor-active');
                gun.style.opacity = '0';
                gunVisible = false;
            });

            hero.addEventListener('mousemove', function (e) {
                var rect = hero.getBoundingClientRect();
                tgx = e.clientX - rect.left;
                tgy = e.clientY - rect.top;
                if (!havePos) { gx = tgx; gy = tgy; havePos = true; }

                if (overInteractive(e.target)) {
                    // Over a link/button: hide the gun and let the normal pointer show.
                    if (gunVisible) { gun.style.opacity = '0'; gunVisible = false; }
                } else {
                    if (!gunVisible) { gun.style.opacity = '1'; gunVisible = true; }
                    var now = performance.now();
                    if (now - lastSpray > 26) { lastSpray = now; emit(); }
                }
                if (!gunRaf) { follow(); }
            });
        }
    });
})();

/*
 * Phase 17 - Playable paint-booth demo controller (self-contained).
 * Runs on all screen sizes; static "finished" fallback under reduced motion.
 */
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var demo = document.getElementById('paintDemo');
        if (!demo) { return; }

        var playBtn    = document.getElementById('demoPlay');
        var bar        = document.getElementById('demoBar');
        var gun        = document.getElementById('demoGun');
        var emit       = document.getElementById('demoSpray');
        var revealRect = document.getElementById('revealRect');
        var stages     = demo.querySelectorAll('.demo-stages span');
        var CAR_W      = 400; // SVG viewBox width
        var reduce     = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function setStage(name) {
            for (var i = 0; i < stages.length; i++) {
                stages[i].classList.toggle('active', stages[i].getAttribute('data-stage') === name);
            }
        }
        function setReveal(pct) {
            if (revealRect) { revealRect.setAttribute('width', (pct / 100 * CAR_W).toFixed(1)); }
        }

        // Reduced motion: show the finished painted car, no animation, no button.
        if (reduce) {
            setReveal(100);
            setStage('finish');
            if (bar) { bar.style.width = '100%'; }
            if (playBtn) { playBtn.style.display = 'none'; }
            return;
        }

        var playing = false, raf = null, lastSpray = 0;
        var T_PREP = 700, T_SPRAY = 2500, T_CLEAR = 1200, T_TOTAL = T_PREP + T_SPRAY + T_CLEAR;

        function spawn(pct) {
            if (!emit || emit.childElementCount > 40) { return; }
            for (var i = 0; i < 2; i++) {
                var p = document.createElement('span');
                p.style.left = pct + '%';
                p.style.top = '15%';
                p.style.setProperty('--fx', (Math.random() * 28 - 14).toFixed(0) + 'px');
                p.style.setProperty('--fy', (30 + Math.random() * 45).toFixed(0) + 'px');
                p.addEventListener('animationend', function () { this.remove(); });
                emit.appendChild(p);
            }
        }

        function frame(now, start) {
            var t = now - start;
            if (bar) { bar.style.width = Math.min(100, t / T_TOTAL * 100).toFixed(1) + '%'; }

            if (t < T_PREP) {
                setStage('prep');
            } else if (t < T_PREP + T_SPRAY) {
                demo.classList.add('spraying');
                setStage('spray');
                var pct = Math.min(100, (t - T_PREP) / T_SPRAY * 100);
                setReveal(pct);
                if (gun) { gun.style.left = pct + '%'; }
                if (now - lastSpray > 36) { lastSpray = now; spawn(pct); }
            } else if (t < T_TOTAL) {
                demo.classList.remove('spraying');
                demo.classList.add('clearcoating');
                setStage('clear');
                setReveal(100);
            } else {
                setReveal(100);
                setStage('finish');
                demo.classList.remove('spraying', 'clearcoating', 'playing');
                demo.classList.add('finished');
                if (bar) { bar.style.width = '100%'; }
                if (playBtn) {
                    playBtn.innerHTML = '<span class="material-symbols-outlined">replay</span> Replay';
                    playBtn.setAttribute('aria-label', 'Replay paint demo');
                }
                playing = false; raf = null;
                return;
            }
            raf = requestAnimationFrame(function (n) { frame(n, start); });
        }

        function play() {
            if (playing) { return; }
            playing = true;
            if (emit) { emit.innerHTML = ''; }
            demo.classList.remove('finished', 'clearcoating', 'spraying');
            demo.classList.add('playing');
            setReveal(0);
            if (bar) { bar.style.width = '0'; }
            var start = performance.now();
            raf = requestAnimationFrame(function (n) { frame(n, start); });
        }

        if (playBtn) { playBtn.addEventListener('click', play); }
    });
})();
