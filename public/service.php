<?php
// Sigma Panels & Paint - Service Detail
// Phase 9 public page - Stitch "Spray Painting Service Page" template.

$pageKey = 'services';

// Load core helpers early so we can resolve the service (and set a 404
// status) before any HTML is emitted by the header include.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$service = null;
if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE slug = :slug AND is_active = 1 LIMIT 1");
    $stmt->execute(['slug' => $slug]);
    $fetched = $stmt->fetch();
    if ($fetched) { $service = $fetched; }
}

if (!$service) {
    http_response_code(404);
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$service): ?>
    <!-- ===================== 404 / NOT FOUND ===================== -->
    <section class="page-hero">
        <div class="hero-glow"></div>
        <div class="container center" data-reveal="up">
            <span class="label-caps">Service Not Found</span>
            <h1>We couldn't find that service.</h1>
            <p>The service you're looking for may have moved or is no longer listed.</p>
            <div class="hero-actions" style="justify-content:center;margin-top:32px;">
                <a href="<?= e(url('public/services.php')) ?>" class="btn btn-pill btn-coral">Browse All Services</a>
                <a href="<?= e(url('public/contact.php')) ?>" class="btn btn-pill btn-outline">Contact Us</a>
            </div>
        </div>
    </section>

<?php else: ?>
    <?php
    $related = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 AND id != :id ORDER BY sort_order ASC, id ASC LIMIT 3");
    $related->execute(['id' => $service['id']]);
    $relatedServices = $related->fetchAll();
    ?>

    <!-- ===================== DETAIL HERO ===================== -->
    <section class="page-hero" style="padding-bottom:0;">
        <div class="hero-glow"></div>
        <div class="container" style="text-align:left;" data-reveal="up">
            <a href="<?= e(url('public/services.php')) ?>" class="section-link" style="margin-bottom:24px;">
                <span class="material-symbols-outlined" style="transform:rotate(180deg);">arrow_right_alt</span> All Services
            </a>
        </div>
    </section>

    <section class="section-sm bg-white">
        <div class="container">
            <div class="detail-hero" data-reveal="fade">
                <div class="detail-media clear-coat">
                    <?php if (!empty($service['image_path'])): ?>
                        <img src="<?= e(asset($service['image_path'])) ?>" alt="<?= e($service['title']) ?>">
                    <?php endif; ?>
                </div>
                <div class="detail-body">
                    <span class="label-caps">Sigma Service</span>
                    <h1><?= e($service['title']) ?></h1>
                    <?php if (!empty($service['short_description'])): ?>
                        <p class="body-lg muted"><?= e($service['short_description']) ?></p>
                    <?php endif; ?>
                    <div class="hero-actions" style="margin-top:12px;">
                        <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral shine-effect">Request a Quote</a>
                        <?php $bset = get_business_settings(); if (!empty($bset['phone'])): ?>
                            <a href="tel:<?= e(preg_replace('/[^0-9\+]/', '', $bset['phone'])) ?>" class="btn btn-pill btn-outline">Call Now</a>
                        <?php endif; ?>
                        <a href="<?= e(url('public/contact.php')) ?>" class="btn btn-pill btn-outline">Ask a Question</a>
                    </div>
                </div>
            </div>

            <?php if (!empty($service['full_description'])): ?>
                <div class="prose" style="margin-top:72px;" data-reveal="fade">
                    <?php foreach (preg_split('/\n+/', $service['full_description']) as $para): ?>
                        <?php $para = trim($para); if ($para === '') continue; ?>
                        <p><?= e($para) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($relatedServices)): ?>
        <!-- ===================== RELATED SERVICES ===================== -->
        <section class="section bg-stone">
            <div class="container">
                <div class="section-head" data-reveal="fade">
                    <div class="titles">
                        <span class="label-caps">Keep Exploring</span>
                        <h2 class="headline-lg">Related services.</h2>
                    </div>
                    <a href="<?= e(url('public/services.php')) ?>" class="section-link">All Services <span class="material-symbols-outlined">arrow_right_alt</span></a>
                </div>
                <div class="card-grid" data-reveal="stagger">
                    <?php foreach ($relatedServices as $srv): ?>
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
            </div>
        </section>
    <?php endif; ?>

    <!-- ===================== CTA BAND ===================== -->
    <section class="cta-band bg-dark">
        <div class="mist-bg" style="opacity:0.2;"></div>
        <div class="container" style="position:relative;z-index:1;" data-reveal="fade">
            <h2>Ready to restore your vehicle?</h2>
            <p>Send us the details and we'll prepare a precise, no-obligation quote.</p>
            <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral btn-lg shine-effect">Request a Quote</a>
        </div>
    </section>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
