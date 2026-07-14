<?php
// Sigma Panels & Paint - router for the PHP built-in server.
// Maps clean URLs to the same PHP files the .htaccess uses in production,
// and serves real static files directly. Start with:
//   php -S 127.0.0.1:8088 -t . router.php

$root = __DIR__;
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// 1) Serve real existing files (assets, uploads, and legacy /public/*.php,
//    /admin/*.php URLs) directly.
if ($uri !== '/') {
    $candidate = realpath($root . $uri);
    if ($candidate !== false && is_file($candidate) && strpos($candidate, $root) === 0) {
        return false; // let the built-in server handle it
    }
}

$routes = [
    '/'                     => 'public/index.php',
    '/home'                 => 'public/index.php',
    '/about'                => 'public/about.php',
    '/services'             => 'public/services.php',
    '/gallery'              => 'public/gallery.php',
    '/quote'                => 'public/quote.php',
    '/contact'              => 'public/contact.php',
    '/faq'                  => 'public/faq.php',
    '/privacy-policy'       => 'public/privacy-policy.php',
    '/terms'                => 'public/terms.php',
    '/sitemap.xml'          => 'public/sitemap.php',
    '/robots.txt'           => 'public/robots.php',
    '/admin'                => 'admin/dashboard.php',
    '/admin/login'          => 'admin/login.php',
    '/admin/logout'         => 'admin/logout.php',
    '/admin/dashboard'      => 'admin/dashboard.php',
    '/admin/business-info'  => 'admin/business-info.php',
    '/admin/homepage'       => 'admin/homepage.php',
    '/admin/about-basic'    => 'admin/about-basic.php',
    '/admin/services'       => 'admin/services.php',
    '/admin/gallery'        => 'admin/gallery.php',
    '/admin/quote-requests' => 'admin/quote-requests.php',
    '/admin/messages'       => 'admin/messages.php',
    '/admin/seo-basic'      => 'admin/seo-basic.php',
    '/admin/settings-basic' => 'admin/settings-basic.php',
    '/admin/faqs'           => 'admin/faqs.php',
];

$path = rtrim($uri, '/');
if ($path === '') { $path = '/'; }

// 2) Pretty service detail: /service/{slug}
if (preg_match('#^/service/([A-Za-z0-9_-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    $_REQUEST['slug'] = $m[1];
    require $root . '/public/service.php';
    return true;
}

// 3) Mapped clean routes (query string is already in $_GET via the built-in server)
if (isset($routes[$path])) {
    require $root . '/' . $routes[$path];
    return true;
}

// 4) Graceful fallback -> homepage
http_response_code(404);
require $root . '/public/index.php';
return true;
