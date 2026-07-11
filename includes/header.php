<?php
// Sigma Panels & Paint - Public Header
// Phase 9 implementation - Stitch "Digital Showroom" shell.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/seo.php';

$pageKey  = isset($pageKey) ? $pageKey : 'home';
$settings = get_business_settings();

// Public navigation links: [key, label, path]
$navLinks = [
    ['home',      'Home',      'public/index.php'],
    ['services',  'Services',  'public/services.php'],
    ['gallery',   'Our Work',  'public/gallery.php'],
    ['about',     'About',     'public/about.php'],
    ['insurance', 'Insurance', 'public/service.php?slug=insurance-repairs'],
    ['contact',   'Contact',   'public/contact.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= render_meta_tags($pageKey, $settings) ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/public.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/animations.css')) ?>">
    <?php if (in_array($pageKey, ['home', 'contact'], true)) { echo render_local_business_jsonld($settings); } ?>
</head>
<body class="site-body">

<header class="site-header" id="main-header">
    <div class="nav-container">
        <?php
        $logoPath = $settings['logo_path'] ?? '';
        $logoOk = $logoPath && is_file(dirname(__DIR__) . '/' . ltrim($logoPath, '/'));
        ?>
        <a class="brand ref-logo" href="<?= e(url('public/index.php')) ?>">
            <?php if ($logoOk): ?>
                <img src="<?= e(asset($logoPath)) ?>" alt="<?= e($settings['business_name']) ?>">
            <?php else: ?>
                <span class="ref-logo-word">SIGM<span class="accent">A</span></span>
                <span class="ref-logo-sub">Panels &amp; Paint</span>
            <?php endif; ?>
        </a>

        <nav class="main-nav" aria-label="Primary">
            <?php foreach ($navLinks as $link): ?>
                <a href="<?= e(url($link[2])) ?>" class="nav-link <?= active_class($link[0], $pageKey) ?>">
                    <?= e(strtoupper($link[1])) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-actions">
            <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral shine-effect">GET A QUOTE</a>
            <button class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>

    <!-- Mobile drawer -->
    <div class="mobile-menu" id="mobile-menu" aria-hidden="true">
        <?php foreach ($navLinks as $link): ?>
            <a href="<?= e(url($link[2])) ?>" class="mobile-link <?= active_class($link[0], $pageKey) ?>">
                <?= e($link[1]) ?>
            </a>
        <?php endforeach; ?>
        <a href="<?= e(url('public/quote.php')) ?>" class="btn btn-pill btn-coral">GET A QUOTE</a>
    </div>
</header>

<main class="site-main">
