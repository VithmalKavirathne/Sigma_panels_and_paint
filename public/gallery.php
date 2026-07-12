<?php
// Sigma Panels & Paint - Gallery / Our Work
// Phase 9 public page - Stitch "Our Work Gallery".

$pageKey = 'gallery';
require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$items = $pdo->query("SELECT * FROM gallery_items WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll();

// Build unique category list for the filter chips
$categories = [];
foreach ($items as $it) {
    $cat = trim($it['category']);
    if ($cat !== '' && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
// Slug helper for the data-category matching
function gallery_cat_slug($value) {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($value)));
}
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Portfolio</span>
        <h1>Proof is in <span class="text-coral">the finish.</span></h1>
        <p>A selection of restorations, refinishes and repairs completed in our Acacia Ridge workshop.</p>
    </div>
</section>

<!-- ===================== GALLERY ===================== -->
<section class="section-sm bg-white">
    <div class="container">
        <?php if (!empty($items)): ?>
            <?php if (!empty($categories)): ?>
                <div class="filter-chips" style="margin-bottom:48px;" data-reveal="fade">
                    <button class="chip active" data-filter="all">All</button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="chip" data-filter="<?= e(gallery_cat_slug($cat)) ?>"><?= e($cat) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="gallery-grid" data-reveal="stagger">
                <?php foreach ($items as $item): ?>
                    <div class="gallery-item clear-coat" data-category="<?= e(gallery_cat_slug($item['category'])) ?>">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= e(asset($item['image_path'])) ?>" alt="<?= e($item['title']) ?>">
                        <?php endif; ?>
                        <div class="overlay"></div>
                        <div class="caption">
                            <span class="cat"><?= e($item['category']) ?></span>
                            <h4><?= e($item['title']) ?></h4>
                            <?php if (!empty($item['description'])): ?>
                                <p style="color:rgba(255,255,255,0.7);font-size:14px;margin-top:8px;"><?= e($item['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="center" data-reveal="fade">
                <p class="muted" style="margin-bottom:24px;">Our gallery is being updated with our latest work. Please check back soon.</p>
                <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral">Request a Quote</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================== CTA BAND ===================== -->
<section class="cta-band bg-dark">
    <div class="mist-bg" style="opacity:0.2;"></div>
    <div class="container" style="position:relative;z-index:1;" data-reveal="fade">
        <h2>Want your car to look like this?</h2>
        <p>Tell us what happened and we'll guide the repair from first assessment to final polish.</p>
        <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral btn-lg shine-effect">Request a Quote</a>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
