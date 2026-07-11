<?php
// Sigma Panels & Paint - SEO Helper
// Phase 16 - full technical SEO: meta, canonical, robots, Open Graph,
// Twitter cards, site verification, favicons, and LocalBusiness JSON-LD.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Retrieves all SEO data for a specific page key (returns row or false).
 * Uses SELECT * so it keeps working before/after the Phase 16 migration.
 */
function get_seo_page($pageKey) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM seo_pages WHERE page_key = :page_key LIMIT 1");
    $stmt->execute(['page_key' => $pageKey]);
    return $stmt->fetch();
}

/**
 * Turns a stored media path into an absolute URL for social tags.
 * Leaves already-absolute URLs untouched; blank stays blank.
 */
function seo_abs_media($path) {
    $path = trim((string) $path);
    if ($path === '') { return ''; }
    if (preg_match('#^https?://#i', $path)) { return $path; }
    return current_origin() . asset($path);
}

/**
 * Renders the full <head> SEO block for a page.
 *
 * @param string     $pageKey
 * @param array|null $settings  business_settings row (fetched if null)
 */
function render_meta_tags($pageKey, $settings = null) {
    if ($settings === null) { $settings = get_business_settings(); }
    $seo = get_seo_page($pageKey);
    if (!is_array($seo)) { $seo = []; }
    $g = function ($row, $key) { return isset($row[$key]) ? trim((string) $row[$key]) : ''; };

    // --- Core meta with safe fallbacks ---
    $title = SITE_NAME;
    $description = "Panel beating, spray painting and smash repairs in Acacia Ridge. Sigma Panels & Paint - precision automotive paint repair in Brisbane.";
    $keywords = "panel beating acacia ridge, smash repairs acacia ridge, spray painting acacia ridge, accident repair acacia ridge, automotive paint repair brisbane, sigma panels & paint";

    if (!empty($seo)) {
        if ($g($seo, 'meta_title') !== '')       { $title = $g($seo, 'meta_title'); }
        if ($g($seo, 'meta_description') !== '')  { $description = $g($seo, 'meta_description'); }
        if ($g($seo, 'meta_keywords') !== '')     { $keywords = $g($seo, 'meta_keywords'); }
    }

    // --- Canonical ---
    $canonical = $g($seo ?: [], 'canonical_url');
    if ($canonical === '') {
        $canonical = current_origin() . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    } elseif (!preg_match('#^https?://#i', $canonical)) {
        $canonical = seo_abs_media($canonical);
    }

    // --- Robots ---
    $robots = ($g($seo ?: [], 'robots_noindex') === '1' || (int)($seo['robots_noindex'] ?? 0) === 1) ? 'noindex' : 'index';
    $robots .= (($seo['robots_nofollow'] ?? 0) == 1) ? ', nofollow' : ', follow';

    // --- Open Graph ---
    $ogTitle = $g($seo ?: [], 'og_title') ?: $title;
    $ogDesc  = $g($seo ?: [], 'og_description') ?: $description;
    $ogImage = seo_abs_media($g($seo ?: [], 'og_image')
        ?: ($settings['default_og_image'] ?? '')
        ?: ($settings['logo_path'] ?? ''));

    // --- Twitter ---
    $twTitle = $g($seo ?: [], 'twitter_title') ?: $ogTitle;
    $twDesc  = $g($seo ?: [], 'twitter_description') ?: $ogDesc;
    $twImage = seo_abs_media($g($seo ?: [], 'twitter_image')
        ?: ($settings['default_twitter_image'] ?? '')) ?: $ogImage;

    $out  = '<title>' . e($title) . '</title>' . "\n";
    $out .= '    <meta name="description" content="' . e($description) . '">' . "\n";
    if ($keywords !== '') {
        $out .= '    <meta name="keywords" content="' . e($keywords) . '">' . "\n";
    }
    $out .= '    <link rel="canonical" href="' . e($canonical) . '">' . "\n";
    $out .= '    <meta name="robots" content="' . e($robots) . '">' . "\n";

    // Site verification
    if (!empty($settings['google_site_verification'])) {
        $out .= '    <meta name="google-site-verification" content="' . e($settings['google_site_verification']) . '">' . "\n";
    }
    if (!empty($settings['bing_site_verification'])) {
        $out .= '    <meta name="msvalidate.01" content="' . e($settings['bing_site_verification']) . '">' . "\n";
    }

    // Open Graph
    $out .= '    <meta property="og:type" content="website">' . "\n";
    $out .= '    <meta property="og:site_name" content="' . e(SITE_NAME) . '">' . "\n";
    $out .= '    <meta property="og:title" content="' . e($ogTitle) . '">' . "\n";
    $out .= '    <meta property="og:description" content="' . e($ogDesc) . '">' . "\n";
    $out .= '    <meta property="og:url" content="' . e($canonical) . '">' . "\n";
    if ($ogImage !== '') {
        $out .= '    <meta property="og:image" content="' . e($ogImage) . '">' . "\n";
    }

    // Twitter
    $out .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $out .= '    <meta name="twitter:title" content="' . e($twTitle) . '">' . "\n";
    $out .= '    <meta name="twitter:description" content="' . e($twDesc) . '">' . "\n";
    if ($twImage !== '') {
        $out .= '    <meta name="twitter:image" content="' . e($twImage) . '">' . "\n";
    }

    // Favicons (global site icons, shared with the admin via render_favicon_links)
    $out .= '    ' . render_favicon_links($settings);

    // Optional per-page custom JSON-LD
    if (!empty($seo) && trim((string)($seo['schema_json'] ?? '')) !== '') {
        $decoded = json_decode($seo['schema_json'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $out .= '    <script type="application/ld+json">' . json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    return $out;
}

/**
 * LocalBusiness (AutoRepair) JSON-LD for Sigma Panels & Paint.
 * Real business info only - no ratings/reviews/awards invented.
 */
function render_local_business_jsonld($settings = null) {
    if ($settings === null) { $settings = get_business_settings(); }

    $logo = $settings['logo_path'] ?? ($settings['default_og_image'] ?? '');

    $data = [
        '@context'  => 'https://schema.org',
        '@type'     => 'AutoRepair',
        'name'      => $settings['business_name'] ?? 'Sigma Panels & Paint',
        'url'       => current_origin() . url('public/index.php'),
        'telephone' => $settings['phone'] ?? '+61 478 453 598',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => '8 Lombank St',
            'addressLocality' => 'Acacia Ridge',
            'addressRegion'   => 'QLD',
            'postalCode'      => '4110',
            'addressCountry'  => 'AU',
        ],
        'openingHoursSpecification' => [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        ],
    ];
    if (!empty($settings['email'])) { $data['email'] = $settings['email']; }
    if ($logo !== '') { $data['image'] = seo_abs_media($logo); $data['logo'] = seo_abs_media($logo); }

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}
