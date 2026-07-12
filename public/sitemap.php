<?php
// Sigma Panels & Paint - XML Sitemap
// Phase 16. Outputs valid sitemap XML for public pages + active service details.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$origin = current_origin();

$paths = [
    url('public/index.php'),
    url('public/about.php'),
    url('public/services.php'),
    url('public/gallery.php'),
    url('public/faq.php'),
    url('public/quote.php'),
    url('public/contact.php'),
    url('public/privacy-policy.php'),
    url('public/terms.php'),
];

// Active service detail pages from the database
try {
    $rows = db()->query("SELECT slug FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
    foreach ($rows as $r) {
        if (!empty($r['slug'])) {
            $paths[] = url('public/service.php?slug=' . rawurlencode($r['slug']));
        }
    }
} catch (Exception $e) {
    // If the DB is unavailable, still output the static pages.
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $p) {
    $loc = htmlspecialchars($origin . $p, ENT_QUOTES | ENT_XML1, 'UTF-8');
    echo '  <url><loc>' . $loc . '</loc><changefreq>weekly</changefreq></url>' . "\n";
}
echo '</urlset>' . "\n";
