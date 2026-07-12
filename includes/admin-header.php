<?php
// Sigma Panels & Paint - Admin Header
// Phase 4 include component.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

// Require admin authentication to access any page including this header
require_admin();

$adminUser = current_admin();
$adminPageKey = isset($adminPageKey) ? $adminPageKey : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> - Admin Portal</title>
    <?= render_favicon_links() ?>
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="<?= e(url('admin/dashboard.php')) ?>" class="<?= active_class('dashboard', $adminPageKey) ?>">Dashboard</a></li>
                    <li><a href="<?= e(url('admin/business-info.php')) ?>" class="<?= active_class('business-info', $adminPageKey) ?>">Business Info</a></li>
                    <li><a href="<?= e(url('admin/homepage.php')) ?>" class="<?= active_class('homepage', $adminPageKey) ?>">Homepage</a></li>
                    <li><a href="<?= e(url('admin/about-basic.php')) ?>" class="<?= active_class('about', $adminPageKey) ?>">About</a></li>
                    <li><a href="<?= e(url('admin/services.php')) ?>" class="<?= active_class('services', $adminPageKey) ?>">Services</a></li>
                    <li><a href="<?= e(url('admin/gallery.php')) ?>" class="<?= active_class('gallery', $adminPageKey) ?>">Gallery</a></li>
                    <li><a href="<?= e(url('admin/faqs.php')) ?>" class="<?= active_class('faqs', $adminPageKey) ?>">FAQs</a></li>
                    <li><a href="<?= e(url('admin/quote-requests.php')) ?>" class="<?= active_class('quote-requests', $adminPageKey) ?>">Quote Requests</a></li>
                    <li><a href="<?= e(url('admin/messages.php')) ?>" class="<?= active_class('messages', $adminPageKey) ?>">Messages</a></li>
                    <li><a href="<?= e(url('admin/seo-basic.php')) ?>" class="<?= active_class('seo', $adminPageKey) ?>">SEO &amp; Site Icons</a></li>
                    <li><a href="<?= e(url('admin/settings-basic.php')) ?>" class="<?= active_class('settings', $adminPageKey) ?>">Settings</a></li>
                    <li><a href="<?= e(url('admin/logout.php')) ?>" class="logout-btn">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-main-content">
            <header class="admin-topbar">
                <div class="topbar-user">
                    <span>Welcome, <?= e($adminUser['name'] ?? 'Admin') ?></span>
                </div>
            </header>
            <div class="admin-content-area">
