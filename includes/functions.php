<?php
// Sigma Panels & Paint - Global Helper Functions
// Phase 3 include component.

require_once __DIR__ . '/db.php';

/**
 * Escapes HTML output to prevent XSS (Cross-Site Scripting) attacks.
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects to a specified path within the application and exits.
 */
function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

/**
 * Checks if the current request is a POST request.
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Returns the base sub-path from BASE_URL (without host/port), so links
 * resolve against the CURRENT host and port. This keeps assets and navigation
 * working regardless of which local port serves the app (e.g. 8088 for public
 * and 8091 for admin), while still supporting a sub-folder deployment
 * (e.g. https://domain/SIGMA_WEB_LATEST) in production.
 */
function base_path() {
    $path = parse_url(BASE_URL, PHP_URL_PATH);
    return $path ? '/' . trim($path, '/') : '';
}

/**
 * Generates a root-relative URL for an asset (css, js, images).
 */
function asset($path) {
    return base_path() . '/' . ltrim($path, '/');
}

/**
 * Generates a clean, root-relative URL for a given internal path.
 * Known page files are mapped to pretty URLs (e.g. public/about.php -> /about,
 * public/service.php?slug=x -> /service/x). Anything unmapped falls back to a
 * plain root-relative path so old links keep working.
 */
function url($path) {
    static $map = [
        'public/index.php'          => '/',
        'public/about.php'          => '/about',
        'public/services.php'       => '/services',
        'public/gallery.php'        => '/gallery',
        'public/quote.php'          => '/quote',
        'public/contact.php'        => '/contact',
        'public/faq.php'            => '/faq',
        'public/privacy-policy.php' => '/privacy-policy',
        'public/terms.php'          => '/terms',
        'public/sitemap.php'        => '/sitemap.xml',
        'public/robots.php'         => '/robots.txt',
        'admin/login.php'           => '/admin/login',
        'admin/logout.php'          => '/admin/logout',
        'admin/dashboard.php'       => '/admin/dashboard',
        'admin/business-info.php'   => '/admin/business-info',
        'admin/homepage.php'        => '/admin/homepage',
        'admin/about-basic.php'     => '/admin/about-basic',
        'admin/services.php'        => '/admin/services',
        'admin/gallery.php'         => '/admin/gallery',
        'admin/quote-requests.php'  => '/admin/quote-requests',
        'admin/messages.php'        => '/admin/messages',
        'admin/seo-basic.php'       => '/admin/seo-basic',
        'admin/settings-basic.php'  => '/admin/settings-basic',
        'admin/faqs.php'            => '/admin/faqs',
    ];
    $clean = ltrim((string) $path, '/');

    // Pretty service detail: public/service.php?slug=xyz -> /service/xyz
    if (preg_match('#^public/service\.php\?slug=([^&]+)(.*)$#', $clean, $m)) {
        return base_path() . '/service/' . $m[1] . $m[2];
    }

    $qpos  = strpos($clean, '?');
    $base  = $qpos === false ? $clean : substr($clean, 0, $qpos);
    $query = $qpos === false ? '' : substr($clean, $qpos);

    if (isset($map[$base])) {
        $target = $map[$base];
        return $target === '/' ? base_path() . '/' . $query : base_path() . $target . $query;
    }
    return base_path() . '/' . $clean;
}

/**
 * Returns the scheme://host[:port] of the CURRENT request (never hardcoded),
 * so absolute URLs (Open Graph images, sitemap entries) work on any local port
 * or the live domain.
 */
function current_origin() {
    $https  = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
           || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? parse_url(BASE_URL, PHP_URL_HOST) ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Absolute URL for a given path, built from the current request host.
 */
function abs_url($path) {
    return current_origin() . url($path);
}

/**
 * Maps a file extension to a favicon MIME type.
 */
function favicon_type($path) {
    switch (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION))) {
        case 'ico':  return 'image/x-icon';
        case 'svg':  return 'image/svg+xml';
        case 'webp': return 'image/webp';
        case 'jpg':
        case 'jpeg': return 'image/jpeg';
        default:     return 'image/png';
    }
}

/**
 * Renders the GLOBAL favicon / apple-touch-icon <link> tags, shared by every
 * public page (via seo.php) and every admin page (via admin-header.php).
 * Uses the uploaded icons from business_settings, falling back to the logo
 * and finally the bundled logo.svg so a valid icon is always present.
 */
function render_favicon_links($settings = null) {
    if ($settings === null) { $settings = get_business_settings(); }

    $icon = trim((string)($settings['favicon_path'] ?? ''));
    if ($icon === '') { $icon = trim((string)($settings['logo_path'] ?? '')); }
    if ($icon === '') { $icon = 'assets/images/logo/logo.svg'; }

    $apple = trim((string)($settings['apple_touch_icon_path'] ?? ''));
    if ($apple === '') { $apple = $icon; }

    $out  = '<link rel="icon" type="' . e(favicon_type($icon)) . '" href="' . e(asset($icon)) . '">' . "\n";
    $out .= '    <link rel="shortcut icon" href="' . e(asset($icon)) . '">' . "\n";
    $out .= '    <link rel="apple-touch-icon" href="' . e(asset($apple)) . '">' . "\n";
    return $out;
}

/**
 * Retrieves the business settings from the database.
 * Returns default values if no settings are found.
 */
function get_business_settings() {
    $pdo = db();
    $stmt = $pdo->query("SELECT * FROM business_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    if (!$settings) {
        return [
            'business_name' => SITE_NAME,
            'tagline' => '',
            'phone' => '',
            'whatsapp' => '',
            'email' => '',
            'address' => '',
            'google_map_embed' => '',
            'logo_path' => '',
            'primary_color' => '#F6F4F1',
            'secondary_color' => '#F95C4B'
        ];
    }
    return $settings;
}

/**
 * Retrieves the single-row paint booth video configuration.
 *
 * Returns an associative array of the stored settings, or sensible defaults if
 * the row (or the table) is not present yet. Wrapped in a try/catch so a site
 * that has not run the phase-video-section migration still renders normally
 * (the public section simply stays hidden because no path exists).
 */
function get_homepage_video() {
    $defaults = [
        'paint_video_path'        => null,
        'paint_video_poster'      => null,
        'paint_video_enabled'     => 1,
        'paint_video_autoplay'    => 1,
        'paint_video_loop'        => 1,
        'paint_video_eyebrow'     => 'THE SIGMA FINISH',
        'paint_video_heading'     => 'Precision in Every Layer',
        'paint_video_description' => 'From preparation and colour matching to clear coat and final polish, every stage is controlled for a clean, durable finish.',
    ];
    try {
        $pdo = db();
        $row = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
        if ($row) {
            return array_merge($defaults, $row);
        }
    } catch (Exception $e) {
        // Table missing / not migrated yet - fall through to defaults.
    }
    return $defaults;
}

/**
 * Formats a date string into a more readable format.
 */
function format_date($date, $format = 'd M Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Returns an 'active' class string if the given pageKey matches the currentPage.
 * Useful for highlighting active links in navigation menus.
 */
function active_class($pageKey, $currentPage, $className = 'active') {
    return $pageKey === $currentPage ? $className : '';
}
