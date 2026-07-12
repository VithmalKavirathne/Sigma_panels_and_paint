<?php
// Sigma Panels & Paint - Services Overview
// Phase 9 public page - Stitch "Precision Services Overview".

$pageKey = 'services';
require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$services = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Our Expertise</span>
        <h1>Precision services, <span class="text-coral">finished to perfection.</span></h1>
        <p>From structural panel beating to flawless colour-matched refinishing, every job is handled with care, clarity and a finish-focused standard.</p>
    </div>
</section>

<!-- ===================== SERVICES GRID ===================== -->
<section class="section-sm bg-white">
    <div class="container">
        <?php if (!empty($services)): ?>
            <div class="card-grid" data-reveal="stagger">
                <?php foreach ($services as $srv): ?>
                    <a class="service-card" href="<?= e(url('public/service.php?slug=' . urlencode($srv['slug']))) ?>">
                        <div class="media clear-coat">
                            <?php if (!empty($srv['image_path'])): ?>
                                <img src="<?= e(asset($srv['image_path'])) ?>" alt="<?= e($srv['title']) ?>">
                            <?php endif; ?>
                            <div class="icon-badge"><span class="material-symbols-outlined">build</span></div>
                        </div>
                        <h3><?= e($srv['title']) ?></h3>
                        <p><?= e($srv['short_description']) ?></p>
                        <div class="underline"></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="center" data-reveal="fade">
                <p class="muted" style="margin-bottom:24px;">Our service list is being updated. Please check back soon or get in touch for details.</p>
                <a href="<?= e(url('public/contact.php')) ?>" class="btn btn-pill btn-coral">Contact Us</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================== CTA BAND ===================== -->
<section class="cta-band bg-dark">
    <div class="mist-bg" style="opacity:0.2;"></div>
    <div class="container" style="position:relative;z-index:1;" data-reveal="fade">
        <h2>Not sure which service you need?</h2>
        <p>Send us the details and photos of your vehicle &mdash; we'll guide you to the right repair.</p>
        <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral btn-lg shine-effect">Request a Quote</a>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
