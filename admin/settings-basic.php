<?php
// Sigma Panels & Paint - Settings Hub
// Phase 8 implementation.
// No dedicated settings table exists beyond business_settings, so this page
// acts as a read-only summary + navigation hub (no invented DB columns).

$adminPageKey = 'settings';
require_once __DIR__ . '/../includes/admin-header.php';

$settings = get_business_settings();

// Content counts for a quick overview (read-only)
$pdo = db();
$counts = [
    'services'       => (int) $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn(),
    'gallery_items'  => (int) $pdo->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn(),
    'faqs'           => (int) $pdo->query("SELECT COUNT(*) FROM faqs")->fetchColumn(),
    'quote_requests' => (int) $pdo->query("SELECT COUNT(*) FROM quote_requests")->fetchColumn(),
    'messages'       => (int) $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn(),
];
?>

<div class="page-header">
    <h1>Settings</h1>
    <p>Overview of your site configuration and quick links to each manager.</p>
</div>

<div class="dashboard-card" style="margin-bottom: 20px;">
    <h2 style="font-size:18px; margin-bottom:15px;">Business Summary</h2>
    <table>
        <tbody>
            <tr><th style="width:200px;">Business Name</th><td><?= e($settings['business_name']) ?></td></tr>
            <tr><th>Tagline</th><td><?= e($settings['tagline']) ?></td></tr>
            <tr><th>Phone</th><td><?= e($settings['phone']) ?></td></tr>
            <tr><th>WhatsApp</th><td><?= e($settings['whatsapp']) ?></td></tr>
            <tr><th>Email</th><td><?= e($settings['email']) ?></td></tr>
            <tr><th>Address</th><td><?= e($settings['address']) ?></td></tr>
        </tbody>
    </table>
    <div style="margin-top:15px;">
        <a href="<?= e(url('admin/business-info.php')) ?>" class="btn btn-primary">Edit Business Info</a>
    </div>
</div>

<div class="dashboard-card" style="margin-bottom: 20px;">
    <h2 style="font-size:18px; margin-bottom:15px;">Content Overview</h2>
    <table>
        <tbody>
            <tr><th style="width:200px;">Services</th><td><?= e($counts['services']) ?></td></tr>
            <tr><th>Gallery Items</th><td><?= e($counts['gallery_items']) ?></td></tr>
            <tr><th>FAQs</th><td><?= e($counts['faqs']) ?></td></tr>
            <tr><th>Quote Requests</th><td><?= e($counts['quote_requests']) ?></td></tr>
            <tr><th>Contact Messages</th><td><?= e($counts['messages']) ?></td></tr>
        </tbody>
    </table>
</div>

<div class="dashboard-card">
    <h2 style="font-size:18px; margin-bottom:15px;">Quick Links</h2>
    <div class="action-buttons">
        <a href="<?= e(url('admin/business-info.php')) ?>" class="btn btn-secondary">Business Info</a>
        <a href="<?= e(url('admin/seo-basic.php')) ?>" class="btn btn-secondary">SEO</a>
        <a href="<?= e(url('admin/homepage.php')) ?>" class="btn btn-secondary">Homepage</a>
        <a href="<?= e(url('admin/about-basic.php')) ?>" class="btn btn-secondary">About</a>
        <a href="<?= e(url('admin/services.php')) ?>" class="btn btn-secondary">Services</a>
        <a href="<?= e(url('admin/gallery.php')) ?>" class="btn btn-secondary">Gallery</a>
        <a href="<?= e(url('admin/faqs.php')) ?>" class="btn btn-secondary">FAQs</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
