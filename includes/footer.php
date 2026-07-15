<?php
// Sigma Panels & Paint - Public Footer
// Phase 9 implementation - Stitch "Digital Showroom" footer.

if (!isset($settings)) {
    $settings = get_business_settings();
}
$brandName = $settings['business_name'] ?? SITE_NAME;
?>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="footer-wordmark"><?= e(strtoupper(explode(' ', $brandName)[0])) ?></span>
                <p class="footer-blurb">
                    <?= e($settings['tagline'] ?: 'Precision automotive restoration and collision repair, built on technical excellence and a commitment to a flawless finish.') ?>
                </p>
                <?php if (!empty($settings['whatsapp'])): ?>
                    <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $settings['whatsapp'])) ?>" target="_blank" rel="noopener" class="footer-social" aria-label="WhatsApp">
                        <span class="material-symbols-outlined">chat</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="footer-col">
                <h5 class="footer-heading">Location</h5>
                <?php if (!empty($settings['address'])): ?>
                    <p><?= e($settings['address']) ?></p>
                <?php endif; ?>
                <?php if (!empty($settings['phone'])): ?>
                    <p class="footer-phone">
                        <a href="tel:<?= e(preg_replace('/[^0-9\+]/', '', $settings['phone'])) ?>">P: <?= e($settings['phone']) ?></a>
                    </p>
                <?php endif; ?>
                <?php if (!empty($settings['email'])): ?>
                    <p><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a></p>
                <?php endif; ?>
            </div>

            <div class="footer-col">
                <h5 class="footer-heading">Quick Links</h5>
                <nav class="footer-links">
                    <a href="<?= e(url('public/services.php')) ?>">Services</a>
                    <a href="<?= e(url('public/gallery.php')) ?>">Our Work</a>
                    <a href="<?= e(url('public/about.php')) ?>">About</a>
                    <a href="<?= e(url('public/quote.php')) ?>">Request a Quote</a>
                    <a href="<?= e(url('public/contact.php')) ?>">Contact</a>
                </nav>
            </div>

            <div class="footer-col">
                <h5 class="footer-heading">Legal</h5>
                <nav class="footer-links">
                    <a href="<?= e(url('public/faq.php')) ?>">FAQ</a>
                    <a href="<?= e(url('public/privacy-policy.php')) ?>">Privacy Policy</a>
                    <a href="<?= e(url('public/terms.php')) ?>">Terms &amp; Conditions</a>
                </nav>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= e(strtoupper($brandName)) ?>. Precision Restoration.</p>
            <p>Designed for excellence.</p>
        </div>
    </div>
</footer>

<!-- Global paint-gun cursor (public pages, desktop only). One element,
     hidden until paint-cursor.js verifies capabilities and adds
     html.paint-cursor-enabled. aria-hidden + pointer-events:none. -->
<div class="paint-cursor" aria-hidden="true">
    <span class="paint-cursor-inner">
        <svg viewBox="0 0 36 36" width="36" height="36" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <!-- handle -->
            <path d="M18 14.5 L14 29 q-0.6 2.2 1.9 2.2 l2.8 0 q2.2 0 2.4 -2 L22.5 15.5 Z" fill="#34373c"/>
            <!-- gun body -->
            <rect x="8" y="7.5" width="15.5" height="8.5" rx="3" fill="#4b5058"/>
            <rect x="8" y="7.5" width="15.5" height="3.4" rx="3" fill="#565c65"/>
            <!-- gravity-feed paint cup -->
            <path d="M12.5 7.5 L12.5 3 q0 -1.4 1.9 -1.4 L18.6 1.6 q1.9 0 1.9 1.4 L20.4 7.5 Z" fill="#5c626b"/>
            <ellipse cx="16.5" cy="1.9" rx="4.4" ry="1.2" fill="#6d737c"/>
            <!-- nozzle body -->
            <rect x="2.6" y="10" width="6.6" height="3.8" rx="1.4" fill="#2f3236"/>
            <!-- air cap / nozzle tip accent -->
            <circle cx="4" cy="11.9" r="1.9" fill="var(--pc-accent, #F95C4B)"/>
            <!-- trigger accent -->
            <path d="M12 16 q-1.4 3.4 1.7 3.6" fill="none" stroke="var(--pc-accent, #F95C4B)" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
    </span>
</div>

<script src="<?= e(asset('assets/js/public.js')) ?>"></script>
<script src="<?= e(asset('assets/js/home-animations.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-validation.js')) ?>"></script>

<!-- Motion foundation (public pages only): GSAP + ScrollTrigger + Lenis.
     Loaded after core scripts; motion-init.js fails safe if any are missing. -->
<script src="<?= e(asset('assets/vendor/gsap/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/vendor/gsap/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/vendor/lenis/lenis.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/motion-init.js')) ?>" defer></script>

<!-- Paint-gun cursor: loaded after GSAP so it can use gsap.quickTo. -->
<script src="<?= e(asset('assets/js/paint-cursor.js')) ?>" defer></script>
</body>
</html>
