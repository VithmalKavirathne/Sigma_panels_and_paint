<?php
// Sigma Panels & Paint - robots.txt
// Phase 16. Outputs the admin-configured robots text, or a safe default.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$settings = get_business_settings();
$custom = trim((string)($settings['robots_txt'] ?? ''));

if ($custom !== '') {
    echo $custom . "\n";
} else {
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Sitemap: " . current_origin() . url('public/sitemap.php') . "\n";
}
