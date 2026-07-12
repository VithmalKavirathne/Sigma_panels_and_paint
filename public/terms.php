<?php
// Sigma Panels & Paint - Terms & Conditions
// Phase 9 public page - Stitch "Terms & Conditions".

$pageKey = 'terms';
require_once __DIR__ . '/../includes/header.php';

$brand = $settings['business_name'] ?? SITE_NAME;
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero" style="padding-bottom:32px;">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Legal</span>
        <h1>Terms &amp; <span class="text-coral">Conditions</span></h1>
        <p>The terms that apply when you use our website and engage <?= e($brand) ?> for repair services.</p>
    </div>
</section>

<!-- ===================== CONTENT ===================== -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="prose" data-reveal="fade">
            <p><em>Last updated: <?= e(date('F Y')) ?>.</em></p>

            <p>These terms and conditions govern your use of the <?= e($brand) ?> website and the quotes, bookings and repair services we provide. By using our website or engaging our services, you agree to these terms.</p>

            <h2>Quotes &amp; Estimates</h2>
            <p>Quotes provided online or in person are estimates based on the information and images supplied. Final pricing may change once the vehicle has been physically inspected and any hidden or additional damage is identified. We will always discuss changes with you before proceeding.</p>

            <h2>Bookings &amp; Work</h2>
            <ul>
                <li>Repair timeframes are estimates and may vary with parts availability and workload.</li>
                <li>We will seek your approval before carrying out work beyond the agreed scope.</li>
                <li>Vehicles must be collected promptly once repairs are complete unless otherwise arranged.</li>
            </ul>

            <h2>Payment</h2>
            <p>Payment is due on completion of work unless a separate arrangement (such as a direct insurance settlement) has been agreed in writing. Vehicles may be held until payment is finalised.</p>

            <h2>Warranty</h2>
            <p>We stand behind our workmanship. Any workmanship warranty we offer covers the repair work we perform and does not extend to unrelated pre-existing conditions, general wear and tear, or damage caused after the vehicle leaves our care.</p>

            <h2>Insurance Repairs</h2>
            <p>Where we assist with an insurance claim, the terms of your policy and your insurer's approval apply to the covered work. You remain responsible for any excess or amounts not covered by your insurer.</p>

            <h2>Website Use</h2>
            <p>The content on this website is provided for general information only and may be updated at any time without notice. Images may include representative examples of our work.</p>

            <h2>Contact Us</h2>
            <p>If you have any questions about these terms, please contact us<?php if (!empty($settings['email'])): ?> at <a class="text-coral" href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a><?php endif; ?><?php if (!empty($settings['phone'])): ?> or on <?= e($settings['phone']) ?><?php endif; ?>.</p>

            <p style="margin-top:32px;">
                <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral">Request a Quote</a>
            </p>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
