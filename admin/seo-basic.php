<?php
// Sigma Panels & Paint - SEO & Site Icons Manager
// Phase 16 - global site icons/verification/robots + full per-page SEO.

$adminPageKey = 'seo';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/upload.php';

$pdo = db();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Friendly note when the Phase 16 columns are not yet migrated.
function seo_migration_error(PDOException $e) {
    if (stripos($e->getMessage(), 'Unknown column') !== false) {
        return 'The advanced SEO columns are missing. Please run database/migrations/phase16-seo.sql, then try again.';
    }
    return 'Database error saving your changes.';
}

// ---------- GLOBAL SEO / SITE ICONS SAVE ----------
if (is_post() && ($_POST['form_type'] ?? '') === 'global') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $current = get_business_settings();
        $iconFields = [
            'favicon'  => ['col' => 'favicon_path',          'exts' => ['ico', 'png'],               'max' => 1048576],
            'apple'    => ['col' => 'apple_touch_icon_path', 'exts' => ['png'],                       'max' => 1048576],
            'siteicon' => ['col' => 'site_icon_path',        'exts' => ['png', 'webp'],               'max' => 1048576],
            'ogimg'    => ['col' => 'default_og_image',      'exts' => ['jpg', 'jpeg', 'png', 'webp'], 'max' => 2097152],
            'twimg'    => ['col' => 'default_twitter_image', 'exts' => ['jpg', 'jpeg', 'png', 'webp'], 'max' => 2097152],
        ];
        $vals = [];
        foreach ($iconFields as $key => $spec) {
            $vals[$spec['col']] = $current[$spec['col']] ?? '';
            $res = save_upload('upload_' . $key, 'seo', $spec['exts'], $spec['max']);
            if ($res['error']) { $error = $res['error']; break; }
            if ($res['uploaded']) { $vals[$spec['col']] = $res['path']; }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE business_settings SET
                    favicon_path = :favicon_path,
                    apple_touch_icon_path = :apple_touch_icon_path,
                    site_icon_path = :site_icon_path,
                    default_og_image = :default_og_image,
                    default_twitter_image = :default_twitter_image,
                    google_site_verification = :gsv,
                    bing_site_verification = :bsv,
                    robots_txt = :robots_txt,
                    updated_at = NOW()
                    WHERE id = 1");
                $stmt->execute([
                    'favicon_path'          => $vals['favicon_path'],
                    'apple_touch_icon_path' => $vals['apple_touch_icon_path'],
                    'site_icon_path'        => $vals['site_icon_path'],
                    'default_og_image'      => $vals['default_og_image'],
                    'default_twitter_image' => $vals['default_twitter_image'],
                    'gsv'                   => trim($_POST['google_site_verification'] ?? ''),
                    'bsv'                   => trim($_POST['bing_site_verification'] ?? ''),
                    'robots_txt'            => trim($_POST['robots_txt'] ?? ''),
                ]);
                $_SESSION['admin_success'] = 'Global SEO & site icons saved.';
                redirect('admin/seo-basic.php');
            } catch (PDOException $e) {
                $error = seo_migration_error($e);
            }
        }
    }
}

// ---------- PER-PAGE SEO SAVE ----------
if (is_post() && $action === 'edit') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDesc  = trim($_POST['meta_description'] ?? '');
        $schema    = trim($_POST['schema_json'] ?? '');

        if ($metaTitle === '')      { $error = 'Meta title is required.'; }
        elseif ($metaDesc === '')   { $error = 'Meta description is required.'; }
        elseif ($schema !== '') {
            json_decode($schema);
            if (json_last_error() !== JSON_ERROR_NONE) { $error = 'Schema JSON is not valid JSON.'; }
        }

        // Image uploads (preserve existing if none)
        $ogImage = $_POST['existing_og_image'] ?? '';
        $twImage = $_POST['existing_twitter_image'] ?? '';
        if (!$error) {
            $r1 = save_upload('upload_og_image', 'seo', ['jpg', 'jpeg', 'png', 'webp'], 2097152);
            if ($r1['error']) { $error = $r1['error']; } elseif ($r1['uploaded']) { $ogImage = $r1['path']; }
        }
        if (!$error) {
            $r2 = save_upload('upload_twitter_image', 'seo', ['jpg', 'jpeg', 'png', 'webp'], 2097152);
            if ($r2['error']) { $error = $r2['error']; } elseif ($r2['uploaded']) { $twImage = $r2['path']; }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE seo_pages SET
                    meta_title = :mt, meta_description = :md, meta_keywords = :mk,
                    canonical_url = :can, robots_noindex = :rni, robots_nofollow = :rnf,
                    og_title = :ogt, og_description = :ogd, og_image = :ogi,
                    twitter_title = :twt, twitter_description = :twd, twitter_image = :twi,
                    schema_json = :sj, updated_at = NOW()
                    WHERE id = :id");
                $stmt->execute([
                    'mt' => $metaTitle,
                    'md' => $metaDesc,
                    'mk' => trim($_POST['meta_keywords'] ?? ''),
                    'can' => trim($_POST['canonical_url'] ?? ''),
                    'rni' => isset($_POST['robots_noindex']) ? 1 : 0,
                    'rnf' => isset($_POST['robots_nofollow']) ? 1 : 0,
                    'ogt' => trim($_POST['og_title'] ?? ''),
                    'ogd' => trim($_POST['og_description'] ?? ''),
                    'ogi' => $ogImage,
                    'twt' => trim($_POST['twitter_title'] ?? ''),
                    'twd' => trim($_POST['twitter_description'] ?? ''),
                    'twi' => $twImage,
                    'sj'  => $schema,
                    'id'  => $_GET['id'],
                ]);
                $_SESSION['admin_success'] = 'Page SEO saved.';
                redirect('admin/seo-basic.php');
            } catch (PDOException $e) {
                $error = seo_migration_error($e);
            }
        }
    }
}

if (isset($_SESSION['admin_success'])) { $success = $_SESSION['admin_success']; unset($_SESSION['admin_success']); }
if (isset($_SESSION['admin_error'])) { $error = $_SESSION['admin_error']; unset($_SESSION['admin_error']); }

// Preview helper for a stored image/icon path
function seo_preview($path, $label) {
    if (empty($path)) { return '<span class="thumb thumb-empty">' . e($label) . '</span>'; }
    return '<img src="' . e(asset($path)) . '" alt="' . e($label) . '" class="thumb">';
}
?>

<div class="page-header">
    <h1>SEO &amp; Site Icons Manager</h1>
    <p>Manage site icons, social sharing, verification, robots, and per-page SEO.</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if ($action === 'edit'): ?>
    <?php
    $page = false;
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM seo_pages WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $page = $stmt->fetch();
    }
    if ($page && is_post()) { $page = array_merge($page, $_POST); }
    $v = function ($k) use ($page) { return e(isset($page[$k]) ? $page[$k] : ''); };
    ?>
    <?php if (!$page): ?>
        <div class="alert alert-error">SEO page not found.</div>
        <a href="<?= e(url('admin/seo-basic.php')) ?>" class="btn btn-secondary">Back</a>
    <?php else: ?>
        <div class="dashboard-card">
            <h2>Editing: <?= e($page['page_key']) ?></h2>
            <form method="POST" action="<?= e(url('admin/seo-basic.php?action=edit&id=' . $page['id'])) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Page Key</label>
                    <input type="text" value="<?= e($page['page_key']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="meta_title">Meta Title *</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= $v('meta_title') ?>" required>
                </div>
                <div class="form-group">
                    <label for="meta_description">Meta Description *</label>
                    <textarea id="meta_description" name="meta_description" rows="3" required><?= $v('meta_description') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="meta_keywords">Meta Keywords</label>
                    <textarea id="meta_keywords" name="meta_keywords" rows="2"><?= $v('meta_keywords') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="canonical_url">Canonical URL (optional)</label>
                    <input type="text" id="canonical_url" name="canonical_url" value="<?= $v('canonical_url') ?>" placeholder="Leave blank to auto-use this page URL">
                </div>

                <div style="display:flex; gap:24px; margin-bottom:20px; flex-wrap:wrap;">
                    <label><input type="checkbox" name="robots_noindex" value="1" <?= !empty($page['robots_noindex']) ? 'checked' : '' ?>> noindex (hide from search)</label>
                    <label><input type="checkbox" name="robots_nofollow" value="1" <?= !empty($page['robots_nofollow']) ? 'checked' : '' ?>> nofollow</label>
                </div>

                <h2 style="margin-top:10px;">Open Graph (Facebook / LinkedIn)</h2>
                <div class="form-group">
                    <label for="og_title">OG Title</label>
                    <input type="text" id="og_title" name="og_title" value="<?= $v('og_title') ?>" placeholder="Defaults to meta title">
                </div>
                <div class="form-group">
                    <label for="og_description">OG Description</label>
                    <textarea id="og_description" name="og_description" rows="2"><?= $v('og_description') ?></textarea>
                </div>
                <div class="form-group">
                    <label>OG Image (1200x630 recommended)</label>
                    <div style="margin-bottom:8px;"><?= seo_preview($page['og_image'] ?? '', 'No OG image') ?></div>
                    <input type="hidden" name="existing_og_image" value="<?= e($page['og_image'] ?? '') ?>">
                    <input type="file" name="upload_og_image" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <h2 style="margin-top:10px;">Twitter / X Card</h2>
                <div class="form-group">
                    <label for="twitter_title">Twitter Title</label>
                    <input type="text" id="twitter_title" name="twitter_title" value="<?= $v('twitter_title') ?>" placeholder="Defaults to OG title">
                </div>
                <div class="form-group">
                    <label for="twitter_description">Twitter Description</label>
                    <textarea id="twitter_description" name="twitter_description" rows="2"><?= $v('twitter_description') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Twitter Image (1200x630 recommended)</label>
                    <div style="margin-bottom:8px;"><?= seo_preview($page['twitter_image'] ?? '', 'No Twitter image') ?></div>
                    <input type="hidden" name="existing_twitter_image" value="<?= e($page['twitter_image'] ?? '') ?>">
                    <input type="file" name="upload_twitter_image" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="form-group">
                    <label for="schema_json">Custom Schema JSON-LD (optional, must be valid JSON)</label>
                    <textarea id="schema_json" name="schema_json" rows="4" placeholder='e.g. {"@context":"https://schema.org","@type":"WebPage"}'><?= $v('schema_json') ?></textarea>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary">Save Page SEO</button>
                    <a href="<?= e(url('admin/seo-basic.php')) ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php $s = get_business_settings(); ?>

    <!-- GLOBAL SEO / SITE ICONS -->
    <div class="dashboard-card">
        <h2>Global SEO &amp; Site Icons</h2>
        <form method="POST" action="<?= e(url('admin/seo-basic.php')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="form_type" value="global">

            <div class="form-group">
                <label>Browser Favicon (.ico or .png, 512x512 recommended)</label>
                <div style="margin-bottom:8px;"><?= seo_preview($s['favicon_path'] ?? '', 'No favicon') ?></div>
                <input type="file" name="upload_favicon" accept=".ico,.png">
            </div>
            <div class="form-group">
                <label>Apple Touch Icon (.png, 180x180 recommended)</label>
                <div style="margin-bottom:8px;"><?= seo_preview($s['apple_touch_icon_path'] ?? '', 'No apple icon') ?></div>
                <input type="file" name="upload_apple" accept=".png">
            </div>
            <div class="form-group">
                <label>Site / Android Icon (.png or .webp, 512x512 recommended)</label>
                <div style="margin-bottom:8px;"><?= seo_preview($s['site_icon_path'] ?? '', 'No site icon') ?></div>
                <input type="file" name="upload_siteicon" accept=".png,.webp">
            </div>
            <div class="form-group">
                <label>Default Social Sharing Image / OG (1200x630 recommended)</label>
                <div style="margin-bottom:8px;"><?= seo_preview($s['default_og_image'] ?? '', 'No default OG image') ?></div>
                <input type="file" name="upload_ogimg" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="form-group">
                <label>Default Twitter Image (1200x630 recommended)</label>
                <div style="margin-bottom:8px;"><?= seo_preview($s['default_twitter_image'] ?? '', 'No default Twitter image') ?></div>
                <input type="file" name="upload_twimg" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="form-group">
                <label for="google_site_verification">Google Site Verification code</label>
                <input type="text" id="google_site_verification" name="google_site_verification" value="<?= e($s['google_site_verification'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="bing_site_verification">Bing Site Verification code</label>
                <input type="text" id="bing_site_verification" name="bing_site_verification" value="<?= e($s['bing_site_verification'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="robots_txt">Custom robots.txt (leave blank for safe default)</label>
                <textarea id="robots_txt" name="robots_txt" rows="4" placeholder="User-agent: *&#10;Allow: /"><?= e($s['robots_txt'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save Global SEO</button>
        </form>
    </div>

    <!-- PER-PAGE SEO -->
    <div class="dashboard-card">
        <h2>Per-Page SEO</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Page</th><th>Meta Title</th><th>Robots</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $pages = $pdo->query("SELECT * FROM seo_pages ORDER BY page_key ASC")->fetchAll();
                    if (empty($pages)): ?>
                        <tr><td colspan="4">No SEO pages found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td><strong><?= e($p['page_key']) ?></strong></td>
                            <td><?= e($p['meta_title']) ?></td>
                            <td>
                                <?php if (!empty($p['robots_noindex'])): ?>
                                    <span class="badge badge-secondary">noindex</span>
                                <?php else: ?>
                                    <span class="badge badge-success">index</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/seo-basic.php?action=edit&id=' . $p['id'])) ?>" class="btn btn-secondary btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
