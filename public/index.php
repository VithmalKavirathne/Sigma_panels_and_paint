<?php
// Sigma Panels & Paint - Homepage
// Phase 9 public page - Stitch "Fully Animated Homepage".

$pageKey = 'home';
require_once __DIR__ . '/../includes/header.php';

$pdo = db();

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
$heroCarFile = 'assets/images/home/hero-painted-car.webp';
$hasHeroCar  = is_file(dirname(__DIR__) . '/' . $heroCarFile);
$luxTitle = $heroTitle ?: 'Precision is our starting line';
$luxBody  = $heroBody ?: 'Sigma Panels & Paint delivers flawless repairs with factory precision and a finish that exceeds expectations.';
?>
<section class="sigma-lux-hero" id="hero">
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
                <?php if ($hasHeroCar): ?>
                    <img class="ref-car-image" src="<?= e(asset($heroCarFile)) ?>" alt="Premium vehicle refinished by Sigma Panels &amp; Paint">
                <?php else: ?>
                    <svg class="ref-car-svg" viewBox="0 0 760 340" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Premium sports car finished by Sigma Panels &amp; Paint">
                        <defs>
                            <linearGradient id="refBody" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#eeeae3"/><stop offset="0.45" stop-color="#d3cfc7"/><stop offset="1" stop-color="#a7a39b"/>
                            </linearGradient>
                            <linearGradient id="refGlass" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#3c3e42"/><stop offset="1" stop-color="#17181a"/>
                            </linearGradient>
                            <radialGradient id="refShadow" cx="0.5" cy="0.5" r="0.5">
                                <stop offset="0" stop-color="rgba(0,0,0,0.30)"/><stop offset="1" stop-color="rgba(0,0,0,0)"/>
                            </radialGradient>
                        </defs>
                        <ellipse cx="380" cy="300" rx="330" ry="30" fill="url(#refShadow)"/>
                        <path d="M60,250 C74,214 140,204 205,202 L280,200 C330,166 415,152 505,160 C580,166 646,186 700,220 C726,234 726,250 700,256 L120,256 C92,256 66,258 60,250 Z" fill="url(#refBody)"/>
                        <path d="M280,200 C330,166 415,152 505,160 C556,165 598,178 628,198 L600,204 C556,184 505,176 452,176 C388,176 322,186 296,204 Z" fill="#c4c0b8"/>
                        <path d="M300,200 C338,172 404,166 476,172 C516,175 546,186 568,200 Z" fill="url(#refGlass)"/>
                        <path d="M120,232 C280,224 520,224 690,232" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                        <path d="M690,214 C704,218 716,226 710,236 L690,236 Z" fill="#2a2a2a"/>
                        <circle cx="222" cy="252" r="50" fill="#141414"/><circle cx="222" cy="252" r="27" fill="#2c2c2c"/><circle cx="222" cy="252" r="9" fill="#F95C4B"/>
                        <circle cx="576" cy="252" r="50" fill="#141414"/><circle cx="576" cy="252" r="27" fill="#2c2c2c"/><circle cx="576" cy="252" r="9" fill="#F95C4B"/>
                    </svg>
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
