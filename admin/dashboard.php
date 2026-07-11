<?php
// Sigma Panels & Paint - Admin Dashboard
// Phase 5 base + dashboard content fill.

$adminPageKey = 'dashboard';
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = db();

// --- Stat counts (existing) ---
$servicesCount  = (int) $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$galleryCount   = (int) $pdo->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
$pendingQuotes  = (int) $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'pending'")->fetchColumn();
$unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

// --- Recent activity ---
$recentQuotes = $pdo->query("SELECT customer_name, service_interest, status, created_at FROM quote_requests ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentMessages = $pdo->query("SELECT name, subject, status, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// --- Setup checklist ---
$settings = get_business_settings();
$seoCount = (int) $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();

$faviconSet = false;
try {
    $fp = $pdo->query("SELECT favicon_path FROM business_settings WHERE id = 1")->fetchColumn();
    $faviconSet = !empty($fp);
} catch (Exception $e) {
    $faviconSet = false; // column may not exist before the SEO migration
}

$checklist = [
    ['label' => 'Business info added', 'done' => !empty($settings['business_name'])],
    ['label' => 'Services published',  'done' => $servicesCount > 0],
    ['label' => 'Gallery items added', 'done' => $galleryCount > 0],
    ['label' => 'SEO configured',      'done' => $seoCount > 0],
    ['label' => 'Favicon uploaded',    'done' => $faviconSet],
];

// Quick action links: [label, path, symbol]
$quickActions = [
    ['Manage Services',    'admin/services.php',        '&#9776;'],
    ['Manage Gallery',     'admin/gallery.php',         '&#9635;'],
    ['View Quote Requests','admin/quote-requests.php',  '&#9993;'],
    ['View Messages',      'admin/messages.php',        '&#9990;'],
    ['SEO & Site Icons',   'admin/seo-basic.php',       '&#9781;'],
    ['Business Info',      'admin/business-info.php',   '&#9873;'],
];
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Overview of your website activity.</p>
</div>

<!-- Stat cards -->
<div class="dashboard-grid">
    <div class="dashboard-card stat-card">
        <div class="card-title">Total Services</div>
        <div class="card-value"><?= e($servicesCount) ?></div>
    </div>
    <div class="dashboard-card stat-card">
        <div class="card-title">Gallery Items</div>
        <div class="card-value"><?= e($galleryCount) ?></div>
    </div>
    <div class="dashboard-card stat-card">
        <div class="card-title">Pending Quotes</div>
        <div class="card-value highlight-coral"><?= e($pendingQuotes) ?></div>
    </div>
    <div class="dashboard-card stat-card">
        <div class="card-title">Unread Messages</div>
        <div class="card-value"><?= e($unreadMessages) ?></div>
    </div>
</div>

<!-- Quick actions -->
<div class="dashboard-card" style="margin-top:20px;">
    <h2>Quick Actions</h2>
    <div class="quick-actions">
        <?php foreach ($quickActions as $qa): ?>
            <a class="quick-action" href="<?= e(url($qa[1])) ?>">
                <span class="qa-symbol" aria-hidden="true"><?= $qa[2] ?></span>
                <span><?= e($qa[0]) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent activity + checklist -->
<div class="dash-2col" style="margin-top:20px;">
    <div class="dashboard-card">
        <div class="dash-panel-head">
            <h2>Recent Quote Requests</h2>
            <a href="<?= e(url('admin/quote-requests.php')) ?>" class="btn btn-secondary btn-sm">View all</a>
        </div>
        <?php if (empty($recentQuotes)): ?>
            <div class="empty-state">No quote requests yet.</div>
        <?php else: ?>
            <div class="recent-list">
                <?php foreach ($recentQuotes as $q): ?>
                    <div class="recent-row">
                        <div>
                            <strong><?= e($q['customer_name']) ?></strong>
                            <div class="recent-sub"><?= e($q['service_interest']) ?> &middot; <?= e(format_date($q['created_at'], 'd M Y')) ?></div>
                        </div>
                        <span class="badge <?= $q['status'] === 'pending' ? 'badge-success' : 'badge-secondary' ?>"><?= e($q['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-card">
        <div class="dash-panel-head">
            <h2>Recent Messages</h2>
            <a href="<?= e(url('admin/messages.php')) ?>" class="btn btn-secondary btn-sm">View all</a>
        </div>
        <?php if (empty($recentMessages)): ?>
            <div class="empty-state">No messages yet.</div>
        <?php else: ?>
            <div class="recent-list">
                <?php foreach ($recentMessages as $m): ?>
                    <div class="recent-row">
                        <div>
                            <strong><?= e($m['name']) ?></strong>
                            <div class="recent-sub"><?= e($m['subject']) ?> &middot; <?= e(format_date($m['created_at'], 'd M Y')) ?></div>
                        </div>
                        <span class="badge <?= $m['status'] === 'unread' ? 'badge-success' : 'badge-secondary' ?>"><?= e($m['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Setup checklist -->
<div class="dashboard-card" style="margin-top:20px;">
    <h2>Website Setup</h2>
    <div class="checklist">
        <?php foreach ($checklist as $c): ?>
            <div class="checklist-row">
                <span class="check-icon <?= $c['done'] ? 'is-done' : 'is-pending' ?>" aria-hidden="true"><?= $c['done'] ? '&#10003;' : '&#9675;' ?></span>
                <span><?= e($c['label']) ?></span>
                <span class="badge <?= $c['done'] ? 'badge-success' : 'badge-secondary' ?>"><?= $c['done'] ? 'Done' : 'Pending' ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
