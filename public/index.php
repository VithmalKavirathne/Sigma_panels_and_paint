<?php
// Sigma Panels & Paint - Homepage
// Phase 9 public page - Stitch "Fully Animated Homepage".

$pageKey = 'home';
require_once __DIR__ . '/../includes/header.php';

$pdo = db();

// --- Hero car (admin-managed via homepage settings) ---
$homepage    = get_homepage_video();
$heroCarPath = trim((string) ($homepage['hero_car_path'] ?? ''));
$showHeroCar = !empty($homepage['hero_car_enabled'])
            && $heroCarPath !== ''
            && hero_car_file_exists($heroCarPath);

// --- Homepage content sections ---
$sections = $pdo->query("SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
$hero = null;
$storyBlocks = [];
foreach ($sections as $s) {
    if ($hero === null && ($s['section_key'] === 'hero' || count($sections) === 1)) {
        $hero = $s;
    } else {
        $storyBlocks[] = $s;
    }
}
if ($hero === null && !empty($sections)) { $hero = array_shift($sections); $storyBlocks = $sections; }

// --- Featured services (fallback to any active) ---
$featured = $pdo->query("SELECT * FROM services WHERE is_active = 1 AND is_featured = 1 ORDER BY sort_order ASC LIMIT 6")->fetchAll();
if (empty($featured)) {
    $featured = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3")->fetchAll();
}

// --- Gallery preview ---
$galleryItems = $pdo->query("SELECT * FROM gallery_items WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 6")->fetchAll();

// --- FAQ preview ---
$faqs = $pdo->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 5")->fetchAll();

// Fallback copy
$heroTitle = $hero['title'] ?? 'Precision is our starting line.';
$heroSubtitle = $hero['subtitle'] ?? ($settings['tagline'] ?? '');
$heroBody = $hero['content'] ?? 'Panel beating, spray painting, insurance repairs and colour matching completed with care, clarity and finish-focused workmanship.';
?>

<!-- ===================== PREMIUM HERO ===================== -->
<?php
$luxTitle = $heroTitle ?: 'Precision is our starting line';
$luxBody  = $heroBody ?: 'Sigma Panels & Paint delivers flawless repairs with factory precision and a finish that exceeds expectations.';
?>
<section class="sigma-lux-hero<?= $showHeroCar ? '' : ' no-hero-car' ?>" id="hero">
    <div class="lux-split" aria-hidden="true"></div>

    <div class="lux-hero-shell">
        <div class="lux-copy" data-reveal="up">
            <span class="lux-eyebrow">True Craftsmanship</span>
            <h1 class="lux-headline">Precision is our<br>starting line</h1>
            <p class="lux-lead">Sigma Panels &amp; Paint delivers flawless repairs with factory precision and a finish that exceeds expectations.</p>
            <div class="lux-actions">
                <a class="btn btn-pill btn-coral btn-lg shine-effect" href="<?= e(url('public/quote.php')) ?>">Get a Quote <span class="material-symbols-outlined">arrow_forward</span></a>
                <a class="lux-watch" href="<?= e(url('public/gallery.php')) ?>"><span class="lux-watch-icon"><span class="material-symbols-outlined">play_arrow</span></span> Watch Our Story</a>
            </div>
            <div class="lux-count" aria-hidden="true"><span>01</span><i></i><span>04</span></div>
        </div>

        <div class="lux-hero-visual" data-reveal="fade">
            <div class="lux-ghost" aria-hidden="true"><span>CRAFTED</span></div>
            <div class="lux-car-stage">
                <?php if ($showHeroCar): ?>
                <div class="hero-top-car" data-hero-car>
                    <img
                        src="<?= e(hero_car_url($heroCarPath)) ?>"
                        alt="<?= e($homepage['hero_car_alt'] ?? 'Professionally refinished sports car') ?>">
                </div>
                <?php endif; ?>
            </div>

            <div class="lux-feature-card" data-reveal="fade">
                <div class="lux-feature-row">
                    <span class="lux-feature-icon"><span class="material-symbols-outlined">verified_user</span></span>
                    <div><p class="lf-title">Insurance Approved</p><p class="lf-sub">All major providers</p></div>
                </div>
                <div class="lux-feature-row">
                    <span class="lux-feature-icon"><span class="material-symbols-outlined">precision_manufacturing</span></span>
                    <div><p class="lf-title">OEM Repair Standards</p><p class="lf-sub">Factory level quality</p></div>
                </div>
                <div class="lux-feature-row">
                    <span class="lux-feature-icon"><span class="material-symbols-outlined">palette</span></span>
                    <div><p class="lf-title">Precision Colour Matching</p><p class="lf-sub">Flawless every time</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="scroll-rail" aria-hidden="true">
        <span>Scroll to explore</span>
        <i class="rail-line"></i>
        <i class="rail-dot"></i>
    </div>
</section>

<div class="lux-dock-wrap">
    <div class="lux-service-dock">
        <a class="lux-service-tile" href="<?= e(url('public/service.php?slug=panel-beating')) ?>">
            <div class="lst-body"><h3>Panel Beating</h3><p>Restoring strength and shape</p></div>
            <span class="lst-arrow material-symbols-outlined">arrow_outward</span>
        </a>
        <a class="lux-service-tile" href="<?= e(url('public/service.php?slug=spray-painting')) ?>">
            <div class="lst-body"><h3>Spray Painting</h3><p>Flawless finishes with premium paints</p></div>
            <span class="lst-arrow material-symbols-outlined">arrow_outward</span>
        </a>
        <a class="lux-service-tile" href="<?= e(url('public/service.php?slug=insurance-repairs')) ?>">
            <div class="lst-body"><h3>Insurance Repairs</h3><p>End to end claims and repairs</p></div>
            <span class="lst-arrow material-symbols-outlined">arrow_outward</span>
        </a>
        <a class="lux-service-tile" href="<?= e(url('public/service.php?slug=spray-painting')) ?>">
            <div class="lst-body"><h3>Colour Matching</h3><p>Advanced technology, perfect results</p></div>
            <span class="lst-arrow material-symbols-outlined">arrow_outward</span>
        </a>
        <a class="lux-dark-cta" href="<?= e(url('public/quote.php')) ?>">
            <div>
                <span class="ldc-eyebrow">Let's get your car</span>
                <h3>Back to its best</h3>
                <p>Book a free inspection and quote with our friendly team today.</p>
            </div>
            <span class="ldc-btn">Book Inspection <span class="material-symbols-outlined">arrow_forward</span></span>
        </a>
    </div>
</div>

<!-- ===================== PAINT BOOTH VIDEO (admin-managed) ===================== -->
<?php
// Admin-managed paint booth video. Renders ONLY when enabled, a path is stored,
// and the referenced file physically exists on disk.
$paintVideo = get_homepage_video();
$videoRel   = trim((string)($paintVideo['paint_video_path'] ?? ''));
$posterRel  = trim((string)($paintVideo['paint_video_poster'] ?? ''));
$videoAbs   = $videoRel !== '' ? dirname(__DIR__) . '/' . ltrim($videoRel, '/') : '';
$posterAbs  = $posterRel !== '' ? dirname(__DIR__) . '/' . ltrim($posterRel, '/') : '';
$showVideo  = !empty($paintVideo['paint_video_enabled'])
           && $videoRel !== ''
           && is_file($videoAbs);
if ($showVideo):
    $videoUrl  = asset($videoRel);
    $posterUrl = ($posterRel !== '' && is_file($posterAbs)) ? asset($posterRel) : '';
?>
<section class="paint-video-section" id="paint-process">
    <div class="container">
        <div class="paint-video-heading">
            <p class="eyebrow"><?= e($paintVideo['paint_video_eyebrow'] ?? 'THE SIGMA FINISH') ?></p>
            <h2><?= e($paintVideo['paint_video_heading'] ?? 'Precision in Every Layer') ?></h2>
            <?php if (!empty($paintVideo['paint_video_description'])): ?>
                <p><?= e($paintVideo['paint_video_description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="paint-video-frame">
            <video
                class="paint-booth-video"
                muted
                playsinline
                preload="metadata"
                <?= !empty($paintVideo['paint_video_autoplay']) ? 'autoplay' : '' ?>
                <?= !empty($paintVideo['paint_video_loop']) ? 'loop' : '' ?>
                <?php if ($posterUrl !== ''): ?>poster="<?= e($posterUrl) ?>"<?php endif; ?>
            >
                <source src="<?= e($videoUrl) ?>" type="video/mp4">
                Your browser does not support embedded video.
            </video>
            <button class="paint-video-play" type="button" aria-label="Play paint booth video" hidden>
                <span class="material-symbols-outlined">play_arrow</span>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($storyBlocks)): ?>
<!-- ===================== STORY / CRAFT SECTIONS ===================== -->
<?php foreach ($storyBlocks as $i => $block): ?>
<section class="section <?= $i % 2 === 0 ? 'bg-stone' : 'bg-white' ?>">
    <div class="container">
        <div class="split <?= $i % 2 === 1 ? 'reverse' : '' ?>" data-reveal="fade">
            <div class="split-media clear-coat">
                <?php if (!empty($block['image_path'])): ?>
                    <img src="<?= e(asset($block['image_path'])) ?>" alt="<?= e($block['title']) ?>">
                <?php endif; ?>
            </div>
            <div class="split-body">
                <?php if (!empty($block['subtitle'])): ?>
                    <span class="label-caps"><?= e($block['subtitle']) ?></span>
                <?php endif; ?>
                <h2><?= e($block['title']) ?></h2>
                <?php if (!empty($block['content'])): ?>
                    <p><?= e($block['content']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endforeach; ?>
<?php endif; ?>

<!-- ===================== THE SIGMA PROCESS (dark) ===================== -->
<section class="section bg-dark">
    <div class="mist-bg"></div>
    <div class="container" style="position:relative;z-index:1;">
        <div class="center" style="max-width:56rem;margin:0 auto 72px;" data-reveal="fade">
            <span class="label-caps">The Sigma Process</span>
            <h2 class="display-xl" style="font-size:56px;margin-top:12px;">From damaged panels to polished finish.</h2>
        </div>
        <div class="card-grid-4" data-reveal="stagger">
            <div class="process-card">
                <span class="num">01</span>
                <div class="p-icon"><span class="material-symbols-outlined">analytics</span></div>
                <h4>Assess</h4>
                <p>Full damage mapping and digital assessment for insurance approval.</p>
            </div>
            <div class="process-card">
                <span class="num">02</span>
                <div class="p-icon"><span class="material-symbols-outlined">layers</span></div>
                <h4>Prepare</h4>
                <p>Decontamination, panel alignment, and precision surface sanding.</p>
            </div>
            <div class="process-card">
                <span class="num">03</span>
                <div class="p-icon"><span class="material-symbols-outlined">brush</span></div>
                <h4>Paint</h4>
                <p>Multi-stage coating in a precision downdraft booth with OEM paint.</p>
            </div>
            <div class="process-card">
                <span class="num">04</span>
                <div class="p-icon"><span class="material-symbols-outlined">flare</span></div>
                <h4>Polish</h4>
                <p>Final detailing, mirror-buffing and rigorous quality certification.</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($galleryItems)): ?>
<!-- ===================== GALLERY PREVIEW ===================== -->
<section class="section bg-background">
    <div class="container">
        <div class="section-head" data-reveal="fade">
            <div class="titles">
                <span class="label-caps">Portfolio</span>
                <h2 class="headline-lg">Proof is in the finish.</h2>
            </div>
            <a href="<?= e(url('public/gallery.php')) ?>" class="section-link">View Full Gallery <span class="material-symbols-outlined">arrow_right_alt</span></a>
        </div>
        <div class="gallery-grid" data-reveal="stagger">
            <?php foreach ($galleryItems as $item): ?>
                <a class="gallery-item clear-coat" href="<?= e(url('public/gallery.php')) ?>">
                    <?php if (!empty($item['image_path'])): ?>
                        <img src="<?= e(asset($item['image_path'])) ?>" alt="<?= e($item['title']) ?>">
                    <?php endif; ?>
                    <div class="overlay"></div>
                    <div class="caption">
                        <span class="cat"><?= e($item['category']) ?></span>
                        <h4><?= e($item['title']) ?></h4>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===================== WHY SIGMA (trust) ===================== -->
<section class="section bg-stone">
    <div class="container center">
        <h2 class="headline-lg" style="margin-bottom:72px;" data-reveal="fade">Built around care, clarity and finish.</h2>
        <div class="trust-grid" data-reveal="stagger">
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">verified_user</span></div>
                <h4>Lifetime Warranty</h4>
                <p>Full guarantee on every spray stroke and panel alignment for your peace of mind.</p>
            </div>
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">chat</span></div>
                <h4>Clear Updates</h4>
                <p>Transparent communication through every stage of your vehicle's repair journey.</p>
            </div>
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">car_repair</span></div>
                <h4>OE Standards</h4>
                <p>Strict adherence to Original Equipment Manufacturer specs and genuine parts.</p>
            </div>
            <div class="trust-item">
                <div class="t-icon pulse-icon"><span class="material-symbols-outlined">handshake</span></div>
                <h4>Insurance Ready</h4>
                <p>Working seamlessly with all major Australian insurers to make your claim stress-free.</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($faqs)): ?>
<!-- ===================== FAQ PREVIEW ===================== -->
<section class="section bg-white">
    <div class="container">
        <div class="section-head" data-reveal="fade">
            <div class="titles">
                <span class="label-caps">Good To Know</span>
                <h2 class="headline-lg">Frequently asked questions.</h2>
            </div>
            <a href="<?= e(url('public/faq.php')) ?>" class="section-link">All FAQs <span class="material-symbols-outlined">arrow_right_alt</span></a>
        </div>
        <div class="faq-list" data-reveal="fade">
            <?php foreach ($faqs as $faq): ?>
                <div class="faq-item">
                    <button class="faq-q" type="button">
                        <span><?= e($faq['question']) ?></span>
                        <span class="material-symbols-outlined">add</span>
                    </button>
                    <div class="faq-a">
                        <div class="faq-a-inner"><?= e($faq['answer']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===================== CTA BAND ===================== -->
<section class="cta-band bg-dark">
    <div class="mist-bg" style="opacity:0.2;"></div>
    <div class="container" style="position:relative;z-index:1;" data-reveal="fade">
        <h2>Tell us what happened. <span class="text-coral">We'll guide the repair.</span></h2>
        <p>Quick online quotes and expert advice to get you back on the road in showroom condition.</p>
        <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral btn-lg shine-effect">Request a Quote</a>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
