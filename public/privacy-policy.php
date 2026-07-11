<?php
// Sigma Panels & Paint - Privacy Policy
// Phase 9 public page - Stitch "Privacy Policy".

$pageKey = 'privacy';
require_once __DIR__ . '/../includes/header.php';

$brand = $settings['business_name'] ?? SITE_NAME;
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero" style="padding-bottom:32px;">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Legal</span>
        <h1>Privacy <span class="text-coral">Policy</span></h1>
        <p>How <?= e($brand) ?> collects, uses and protects your personal information.</p>
    </div>
</section>

<!-- ===================== CONTENT ===================== -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="prose" data-reveal="fade">
            <p><em>Last updated: <?= e(date('F Y')) ?>.</em></p>

            <p><?= e($brand) ?> ("we", "us", or "our") is committed to protecting your privacy. This policy explains what information we collect when you use our website or engage our services, and how we handle it.</p>

            <h2>Information We Collect</h2>
            <p>When you submit a quote request or contact form, we collect the details you provide, which may include your name, phone number, email address, vehicle or project location, and a description of the work you need. We only collect information that is necessary to respond to your enquiry and provide our services.</p>

            <h2>How We Use Your Information</h2>
            <ul>
                <li>To prepare and provide repair quotes and respond to your enquiries.</li>
                <li>To schedule, carry out and follow up on repair work.</li>
                <li>To coordinate with insurers where you have asked us to assist with a claim.</li>
                <li>To keep records required for warranty and quality assurance.</li>
            </ul>

            <h2>How We Protect Your Information</h2>
            <p>We take reasonable steps to keep your information secure and to prevent unauthorised access, use or disclosure. Access to enquiry records is limited to authorised staff who need it to serve you.</p>

            <h2>Sharing Your Information</h2>
            <p>We do not sell your personal information. We may share relevant details with your nominated insurer or a parts supplier where this is necessary to complete your repair, and only with your knowledge.</p>

            <h2>Your Choices</h2>
            <p>You may request access to, or correction of, the personal information we hold about you at any time. If you no longer wish to be contacted, let us know and we will update our records.</p>

            <h2>Contact Us</h2>
            <p>If you have any questions about this policy or your personal information, please contact us<?php if (!empty($settings['email'])): ?> at <a class="text-coral" href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a><?php endif; ?><?php if (!empty($settings['phone'])): ?> or on <?= e($settings['phone']) ?><?php endif; ?>.</p>

            <p style="margin-top:32px;">
                <a href="<?= e(url('public/contact.php')) ?>" class="btn btn-pill btn-coral">Contact Us</a>
            </p>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
