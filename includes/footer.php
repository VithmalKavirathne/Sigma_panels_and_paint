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

<script src="<?= e(asset('assets/js/public.js')) ?>"></script>
<script src="<?= e(asset('assets/js/home-animations.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-validation.js')) ?>"></script>

<!-- Motion foundation (public pages only): GSAP + ScrollTrigger + Lenis.
     Loaded after core scripts; motion-init.js fails safe if any are missing. -->
<script src="<?= e(asset('assets/vendor/gsap/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/vendor/gsap/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/vendor/lenis/lenis.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/motion-init.js')) ?>" defer></script>
</body>
</html>
