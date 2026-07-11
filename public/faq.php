<?php
// Sigma Panels & Paint - FAQs
// Phase 9 public page - Stitch "FAQ" page.

$pageKey = 'faq';
require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$faqs = $pdo->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Good To Know</span>
        <h1>Frequently asked <span class="text-coral">questions.</span></h1>
        <p>Answers to the questions we hear most. Can't find what you need? Reach out any time.</p>
    </div>
</section>

<!-- ===================== FAQ ACCORDION ===================== -->
<section class="section-sm bg-white">
    <div class="container">
        <?php if (!empty($faqs)): ?>
            <div class="faq-list" data-reveal="fade">
                <?php foreach ($faqs as $faq): ?>
                    <div class="faq-item">
                        <button class="faq-q" type="button">
                            <span><?= e($faq['question']) ?></span>
                            <span class="material-symbols-outlined">add</span>
                        </button>
                        <div class="faq-a">
                            <div class="faq-a-inner">
                                <?php foreach (preg_split('/\n+/', $faq['answer']) as $para): ?>
                                    <?php $para = trim($para); if ($para === '') continue; ?>
                                    <p style="margin-bottom:12px;"><?= e($para) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="center" data-reveal="fade">
                <p class="muted">We're putting our FAQs together. In the meantime, please get in touch with any questions.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================== CTA BAND ===================== -->
<section class="cta-band bg-dark">
    <div class="mist-bg" style="opacity:0.2;"></div>
    <div class="container" style="position:relative;z-index:1;" data-reveal="fade">
        <h2>Still have a question?</h2>
        <p>Our team is happy to help. Request a quote or send us a message and we'll get back to you.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral btn-lg shine-effect">Request a Quote</a>
            <a href="<?= e(url('public/contact.php')) ?>" class="btn btn-pill btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,0.3);">Contact Us</a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
