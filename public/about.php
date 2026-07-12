<?php
// Sigma Panels & Paint - About Us
// Phase 9 public page - Stitch "The Craft" about page.

$pageKey = 'about';
require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$aboutSections = $pdo->query("SELECT * FROM about_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">The Craft</span>
        <h1>Precision, patience <span class="text-coral">and a perfect finish.</span></h1>
        <p><?= e($settings['tagline'] ?: 'The people, standards and paint booth technology behind every Sigma restoration.') ?></p>
    </div>
</section>

<!-- ===================== DYNAMIC ABOUT SECTIONS ===================== -->
<?php if (!empty($aboutSections)): ?>
    <?php foreach ($aboutSections as $i => $block): ?>
        <section class="section <?= $i % 2 === 0 ? 'bg-white' : 'bg-stone' ?>">
            <div class="container">
                <div class="split <?= $i % 2 === 1 ? 'reverse' : '' ?>" data-reveal="fade">
                    <div class="split-media clear-coat">
                        <?php if (!empty($block['image_path'])): ?>
                            <img src="<?= e(asset($block['image_path'])) ?>" alt="<?= e($block['title']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="split-body">
                        <span class="label-caps">0<?= e($i + 1) ?></span>
                        <h2><?= e($block['title']) ?></h2>
                        <?php foreach (preg_split('/\n+/', $block['content']) as $para): ?>
                            <?php $para = trim($para); if ($para === '') continue; ?>
                            <p><?= e($para) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
<?php else: ?>
    <section class="section-sm bg-white">
        <div class="container center" data-reveal="fade">
            <p class="muted">Our story is being written. In the meantime, we'd love to hear from you.</p>
        </div>
    </section>
<?php endif; ?>

<!-- ===================== VALUES / TRUST ===================== -->
<section class="section bg-white">
    <div class="container center">
        <h2 class="headline-lg" style="margin-bottom:72px;" data-reveal="fade">Standards we never compromise on.</h2>
        <div class="trust-grid" data-reveal="stagger">
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">verified_user</span></div>
                <h4>Certified Technicians</h4>
                <p>Qualified panel beaters and refinishers trained on modern repair systems.</p>
            </div>
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">colorize</span></div>
                <h4>Computerised Colour</h4>
                <p>Spectrophotometer colour matching for an imperceptible blend, every time.</p>
            </div>
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">air</span></div>
                <h4>Downdraft Booths</h4>
                <p>Dust-free, temperature-controlled baking for a hard, mirror-like gloss.</p>
            </div>
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">handshake</span></div>
                <h4>Insurance Partners</h4>
                <p>Direct coordination with major insurers to keep your claim stress-free.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===================== CTA BAND ===================== -->
<section class="cta-band bg-dark">
    <div class="mist-bg" style="opacity:0.2;"></div>
    <div class="container" style="position:relative;z-index:1;" data-reveal="fade">
        <h2>Let's restore it <span class="text-coral">the right way.</span></h2>
        <p>Get a precise quote or talk through your repair with our team.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral btn-lg shine-effect">Request a Quote</a>
            <a href="<?= e(url('public/contact.php')) ?>" class="btn btn-pill btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,0.3);">Contact Us</a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
