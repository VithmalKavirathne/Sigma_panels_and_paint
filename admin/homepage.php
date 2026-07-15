<?php
// Sigma Panels & Paint - Homepage Manager
// Phase 7 implementation.

$adminPageKey = 'homepage';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/upload.php';

$pdo = db();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Guard: a POST larger than the server's post_max_size arrives with empty
// $_POST/$_FILES. Surface a clear message instead of a confusing CSRF failure.
if (is_post() && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $_SESSION['admin_error'] = 'The upload exceeded the server size limit. Use a smaller MP4 (max 25MB), or raise upload_max_filesize / post_max_size.';
    redirect('admin/homepage.php');
}

// ---------------------------------------------------------------
// Paint Booth Video handlers (save / remove video / remove poster)
// ---------------------------------------------------------------
if (is_post() && in_array($action, ['video_save', 'video_remove_video', 'video_remove_poster'], true)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_error'] = 'Invalid form submission.';
        redirect('admin/homepage.php');
    }

    // Ensure the singleton config row exists (idempotent).
    try {
        $vid = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
        if (!$vid) {
            $pdo->exec("INSERT INTO homepage_video (paint_video_enabled, paint_video_autoplay, paint_video_loop, paint_video_eyebrow, paint_video_heading, paint_video_description) VALUES (1,1,1,'THE SIGMA FINISH','Precision in Every Layer','From preparation and colour matching to clear coat and final polish, every stage is controlled for a clean, durable finish.')");
            $vid = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
        }
    } catch (PDOException $e) {
        $_SESSION['admin_error'] = 'The homepage_video table is missing. Run database/migrations/phase-video-section.sql first.';
        redirect('admin/homepage.php');
    }
    $vidId = (int) $vid['id'];

    // Remove current video only (CSRF + POST already enforced).
    if ($action === 'video_remove_video') {
        try {
            $stmt = $pdo->prepare("UPDATE homepage_video SET paint_video_path = NULL, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $vidId]);
            if (!empty($vid['paint_video_path'])) { delete_upload_file($vid['paint_video_path']); }
            $_SESSION['admin_success'] = 'Paint booth video removed.';
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Could not remove the video.';
        }
        redirect('admin/homepage.php');
    }

    // Remove current poster only.
    if ($action === 'video_remove_poster') {
        try {
            $stmt = $pdo->prepare("UPDATE homepage_video SET paint_video_poster = NULL, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $vidId]);
            if (!empty($vid['paint_video_poster'])) { delete_upload_file($vid['paint_video_poster']); }
            $_SESSION['admin_success'] = 'Poster image removed.';
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Could not remove the poster.';
        }
        redirect('admin/homepage.php');
    }

    // ---- Save (text, toggles, and optional replacement uploads) ----
    $eyebrow     = trim($_POST['paint_video_eyebrow'] ?? '');
    $heading     = trim($_POST['paint_video_heading'] ?? '');
    $description = trim($_POST['paint_video_description'] ?? '');
    $enabled     = isset($_POST['paint_video_enabled']) ? 1 : 0;
    $autoplay    = isset($_POST['paint_video_autoplay']) ? 1 : 0;
    $loop        = isset($_POST['paint_video_loop']) ? 1 : 0;

    $oldVideoPath  = $vid['paint_video_path'];
    $oldPosterPath = $vid['paint_video_poster'];
    $newVideoPath  = $oldVideoPath;
    $newPosterPath = $oldPosterPath;
    $err = '';

    // 1) New video: upload + fully validate BEFORE any DB change or deletion.
    $vres = save_video_upload('paint_video_file', 'homepage/videos', 26214400); // 25 MB
    if ($vres['error']) {
        $err = $vres['error'];
    } elseif ($vres['uploaded']) {
        $newVideoPath = $vres['path'];
    }

    // 2) New poster (optional). If it fails, discard any just-uploaded video.
    $pres = ['uploaded' => false];
    if (!$err) {
        $pres = save_upload('paint_video_poster_file', 'homepage/video-posters', ['jpg', 'jpeg', 'png', 'webp'], 4194304); // 4 MB
        if ($pres['error']) {
            $err = $pres['error'];
            if ($vres['uploaded'] && $newVideoPath !== $oldVideoPath) { delete_upload_file($newVideoPath); }
        } elseif ($pres['uploaded']) {
            $newPosterPath = $pres['path'];
        }
    }

    if ($err) {
        $_SESSION['admin_error'] = $err;
        redirect('admin/homepage.php');
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE homepage_video SET
                paint_video_path = :path,
                paint_video_poster = :poster,
                paint_video_enabled = :enabled,
                paint_video_autoplay = :autoplay,
                paint_video_loop = :loop,
                paint_video_eyebrow = :eyebrow,
                paint_video_heading = :heading,
                paint_video_description = :description,
                updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->bindValue(':path', $newVideoPath !== '' ? $newVideoPath : null);
        $stmt->bindValue(':poster', $newPosterPath !== '' ? $newPosterPath : null);
        $stmt->bindValue(':enabled', $enabled, PDO::PARAM_INT);
        $stmt->bindValue(':autoplay', $autoplay, PDO::PARAM_INT);
        $stmt->bindValue(':loop', $loop, PDO::PARAM_INT);
        $stmt->bindValue(':eyebrow', $eyebrow !== '' ? $eyebrow : null);
        $stmt->bindValue(':heading', $heading !== '' ? $heading : null);
        $stmt->bindValue(':description', $description !== '' ? $description : null);
        $stmt->bindValue(':id', $vidId, PDO::PARAM_INT);
        $stmt->execute();

        // DB update succeeded: now it is safe to delete replaced old files.
        if ($vres['uploaded'] && $oldVideoPath && $oldVideoPath !== $newVideoPath) { delete_upload_file($oldVideoPath); }
        if ($pres['uploaded'] && $oldPosterPath && $oldPosterPath !== $newPosterPath) { delete_upload_file($oldPosterPath); }

        $_SESSION['admin_success'] = 'Paint booth video settings saved.';
    } catch (PDOException $e) {
        // Preserve prior state: remove any newly-uploaded files, keep the old ones.
        if ($vres['uploaded'] && $newVideoPath !== $oldVideoPath) { delete_upload_file($newVideoPath); }
        if ($pres['uploaded'] && $newPosterPath !== $oldPosterPath) { delete_upload_file($newPosterPath); }
        $_SESSION['admin_error'] = 'Database error saving video settings.';
    }
    redirect('admin/homepage.php');
}

// Handle Toggle Action
if ($action === 'toggle' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("UPDATE homepage_sections SET is_active = NOT is_active WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Section status updated.";
            redirect('admin/homepage.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error updating status.";
            redirect('admin/homepage.php');
        }
    }
}

// Process Edit Form Submission (homepage sections are edit-only, keyed by section_key)
if (is_post() && $action === 'edit') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = "Invalid form submission.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_path = $_POST['existing_image_path'] ?? '';

        if (empty($title)) {
            $error = "Title is required.";
        } else {
            // Handle File Upload
            if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed) && $_FILES['image_upload']['size'] <= 2097152) { // 2MB
                    $upload_dir = UPLOAD_DIR . '/homepage/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $new_filename = 'home_' . time() . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path = 'uploads/homepage/' . $new_filename;
                    } else {
                        $error = "Failed to move uploaded file.";
                    }
                } else {
                    $error = "Invalid image upload. Allowed: JPG, PNG, WEBP (Max 2MB).";
                }
            }

            if (!$error) {
                try {
                    $stmt = $pdo->prepare("UPDATE homepage_sections SET title = :title, subtitle = :subtitle, content = :content, image_path = :image_path, sort_order = :sort_order, is_active = :is_active, updated_at = NOW() WHERE id = :id");
                    $stmt->bindValue(':title', $title);
                    $stmt->bindValue(':subtitle', $subtitle);
                    $stmt->bindValue(':content', $content);
                    $stmt->bindValue(':image_path', $image_path);
                    $stmt->bindValue(':sort_order', $sort_order);
                    $stmt->bindValue(':is_active', $is_active);
                    $stmt->bindValue(':id', $_GET['id']);
                    $stmt->execute();
                    $_SESSION['admin_success'] = "Homepage section updated successfully.";
                    redirect('admin/homepage.php');
                } catch (PDOException $e) {
                    $error = "Database error saving section.";
                }
            }
        }
    }
}


// ---------------------------------------------------------------
// Hero Car Image handlers (save / remove)
// ---------------------------------------------------------------
if (is_post() && in_array($action, ['hero_save', 'hero_remove'], true)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_error'] = 'Invalid form submission.';
        redirect('admin/homepage.php');
    }

    try {
        $hv = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
        if (!$hv) {
            $pdo->exec("INSERT INTO homepage_video (hero_car_enabled) VALUES (1)");
            $hv = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
        }
    } catch (PDOException $e) {
        $_SESSION['admin_error'] = 'The homepage settings table is missing. Run database/migrations/phase-hero-car-admin.sql first.';
        redirect('admin/homepage.php');
    }
    $hvId = (int) $hv['id'];
    $bundledHero = 'assets/images/home/hero-car-transparent-clean.png';

    // ---- Remove hero car (clear DB; delete only an uploaded file, never the bundled asset) ----
    if ($action === 'hero_remove') {
        try {
            $stmt = $pdo->prepare("UPDATE homepage_video SET hero_car_path = NULL, hero_car_updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $hvId]);
            $old = (string) ($hv['hero_car_path'] ?? '');
            if ($old !== '' && $old !== $bundledHero && strpos($old, 'uploads/homepage/hero-car/') === 0) {
                delete_upload_file($old);
            }
            $_SESSION['admin_success'] = 'Hero car image removed.';
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Could not remove the hero car image.';
        }
        redirect('admin/homepage.php');
    }

    // ---- Save (alt, enable, optional replacement upload) ----
    $alt     = trim($_POST['hero_car_alt'] ?? '');
    $enabled = isset($_POST['hero_car_enabled']) ? 1 : 0;
    $oldPath = (string) ($hv['hero_car_path'] ?? '');
    $newPath = $oldPath;
    $err = '';

    // Optional new image: upload + fully validate BEFORE any DB change/deletion.
    if (!empty($_FILES['hero_car_file']) && ($_FILES['hero_car_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $res = save_hero_image('hero_car_file', 'homepage/hero-car', 8388608);
        if ($res['error']) {
            $err = $res['error'];
        } elseif ($res['uploaded']) {
            $newPath = $res['path'];
        }
    }

    if ($err) {
        $_SESSION['admin_error'] = $err;
        redirect('admin/homepage.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE homepage_video SET hero_car_path = :path, hero_car_alt = :alt, hero_car_enabled = :en, hero_car_updated_at = NOW() WHERE id = :id");
        $stmt->bindValue(':path', $newPath !== '' ? $newPath : null);
        $stmt->bindValue(':alt', $alt !== '' ? $alt : null);
        $stmt->bindValue(':en', $enabled, PDO::PARAM_INT);
        $stmt->bindValue(':id', $hvId, PDO::PARAM_INT);
        $stmt->execute();
        // DB updated: now safe to delete the replaced OLD uploaded file (never the bundled asset).
        if ($newPath !== $oldPath && $oldPath !== '' && $oldPath !== $bundledHero
            && strpos($oldPath, 'uploads/homepage/hero-car/') === 0) {
            delete_upload_file($oldPath);
        }
        $_SESSION['admin_success'] = 'Hero car image saved.';
    } catch (PDOException $e) {
        // Preserve prior state: discard any just-uploaded new file.
        if ($newPath !== $oldPath && strpos($newPath, 'uploads/homepage/hero-car/') === 0) {
            delete_upload_file($newPath);
        }
        $_SESSION['admin_error'] = 'Database error saving the hero car image.';
    }
    redirect('admin/homepage.php');
}

// Fetch session messages
if (isset($_SESSION['admin_success'])) { $success = $_SESSION['admin_success']; unset($_SESSION['admin_success']); }
if (isset($_SESSION['admin_error'])) { $error = $_SESSION['admin_error']; unset($_SESSION['admin_error']); }
?>

<div class="page-header">
    <h1>Homepage Manager</h1>
    <p>Edit the content sections shown on the public homepage.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <?php
    $sections = $pdo->query("SELECT * FROM homepage_sections ORDER BY sort_order ASC, id ASC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Image</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sections)): ?>
                        <tr><td colspan="5">No homepage sections found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($sections as $sec): ?>
                        <tr>
                            <td><?= e($sec['sort_order']) ?></td>
                            <td>
                                <?php if (!empty($sec['image_path'])): ?>
                                    <img src="<?= e(asset($sec['image_path'])) ?>" alt="" class="thumb">
                                <?php else: ?>
                                    <span class="thumb thumb-empty">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($sec['title']) ?></strong><br>
                                <small style="color:var(--text-muted)"><?= e($sec['section_key']) ?></small>
                            </td>
                            <td>
                                <?php if ($sec['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/homepage.php?action=edit&id=' . $sec['id'])) ?>" class="btn btn-secondary btn-sm">Edit</a>

                                <form method="POST" action="<?= e(url('admin/homepage.php?action=toggle&id=' . $sec['id'])) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">Toggle</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // ---- Paint Booth Video panel ----
    $videoTableReady = true;
    $video = null;
    try {
        $video = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
    } catch (PDOException $e) {
        $videoTableReady = false;
    }
    $v = array_merge([
        'paint_video_path' => null, 'paint_video_poster' => null,
        'paint_video_enabled' => 1, 'paint_video_autoplay' => 1, 'paint_video_loop' => 1,
        'paint_video_eyebrow' => 'THE SIGMA FINISH', 'paint_video_heading' => 'Precision in Every Layer',
        'paint_video_description' => 'From preparation and colour matching to clear coat and final polish, every stage is controlled for a clean, durable finish.',
    ], is_array($video) ? $video : []);

    $vPath   = trim((string)($v['paint_video_path'] ?? ''));
    $vAbs    = $vPath !== '' ? dirname(__DIR__) . '/' . ltrim($vPath, '/') : '';
    $vExists = $vPath !== '' && is_file($vAbs);
    $vName   = $vExists ? basename($vPath) : '';
    $vSize   = $vExists ? filesize($vAbs) : 0;
    $vSizeMB = $vSize > 0 ? number_format($vSize / 1048576, 2) . ' MB' : '';

    $pPath   = trim((string)($v['paint_video_poster'] ?? ''));
    $pAbs    = $pPath !== '' ? dirname(__DIR__) . '/' . ltrim($pPath, '/') : '';
    $pExists = $pPath !== '' && is_file($pAbs);
    ?>
    <div class="dashboard-card" style="margin-top:24px;">
        <h2 style="margin-top:0;">Paint Booth Video</h2>
        <p style="color:var(--text-muted);margin-top:-6px;">A real video shown on the homepage in place of the old animated booth. Leave the video empty to hide the section from the public site.</p>

        <?php if (!$videoTableReady): ?>
            <div class="alert alert-error">The <code>homepage_video</code> table was not found. Run <code>database/migrations/phase-video-section.sql</code> to enable this section.</div>
        <?php else: ?>

            <div style="margin-bottom:18px;">
                <?php if ($vExists): ?>
                    <p>
                        <strong>Status:</strong>
                        <?php if (!empty($v['paint_video_enabled'])): ?>
                            <span class="badge badge-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Disabled</span>
                        <?php endif; ?>
                    </p>
                    <p style="margin:6px 0;"><strong>Current video:</strong> <?= e($vName) ?><?= $vSizeMB ? ' (' . e($vSizeMB) . ')' : '' ?></p>
                    <video src="<?= e(asset($vPath)) ?>" controls muted playsinline preload="metadata"
                           <?php if ($pExists): ?>poster="<?= e(asset($pPath)) ?>"<?php endif; ?>
                           style="width:100%;max-width:520px;border-radius:10px;background:#0f1013;display:block;"></video>

                    <form method="POST" action="<?= e(url('admin/homepage.php?action=video_remove_video')) ?>"
                          onsubmit="return confirm('Remove the current paint booth video? This cannot be undone.');"
                          style="margin-top:10px;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-secondary btn-sm">Remove current video</button>
                    </form>
                <?php else: ?>
                    <p style="color:var(--text-muted);">No paint booth video uploaded.</p>
                <?php endif; ?>
            </div>

            <?php if ($pExists): ?>
                <div style="margin-bottom:18px;">
                    <p style="margin-bottom:6px;"><strong>Current poster:</strong></p>
                    <img src="<?= e(asset($pPath)) ?>" alt="Poster preview" style="max-height:110px;border-radius:8px;display:block;">
                    <form method="POST" action="<?= e(url('admin/homepage.php?action=video_remove_poster')) ?>"
                          onsubmit="return confirm('Remove the current poster image?');"
                          style="margin-top:10px;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-secondary btn-sm">Remove poster</button>
                    </form>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(url('admin/homepage.php?action=video_save')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="paint_video_eyebrow">Eyebrow text</label>
                    <input type="text" id="paint_video_eyebrow" name="paint_video_eyebrow" value="<?= e($v['paint_video_eyebrow']) ?>">
                </div>

                <div class="form-group">
                    <label for="paint_video_heading">Heading</label>
                    <input type="text" id="paint_video_heading" name="paint_video_heading" value="<?= e($v['paint_video_heading']) ?>">
                </div>

                <div class="form-group">
                    <label for="paint_video_description">Description</label>
                    <textarea id="paint_video_description" name="paint_video_description" rows="4"><?= e($v['paint_video_description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="paint_video_file"><?= $vExists ? 'Replace video' : 'Upload video' ?> (MP4 only, max 25MB)</label>
                    <input type="file" id="paint_video_file" name="paint_video_file" accept="video/mp4,.mp4">
                </div>

                <div class="form-group">
                    <label for="paint_video_poster_file">Poster image (optional - JPG, PNG, WEBP)</label>
                    <input type="file" id="paint_video_poster_file" name="paint_video_poster_file" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div style="margin-bottom:12px;">
                    <label><input type="checkbox" name="paint_video_enabled" value="1" <?= !empty($v['paint_video_enabled']) ? 'checked' : '' ?>> Enable section (visible to public)</label>
                </div>
                <div style="margin-bottom:12px;">
                    <label><input type="checkbox" name="paint_video_autoplay" value="1" <?= !empty($v['paint_video_autoplay']) ? 'checked' : '' ?>> Autoplay (muted, when in view)</label>
                </div>
                <div style="margin-bottom:20px;">
                    <label><input type="checkbox" name="paint_video_loop" value="1" <?= !empty($v['paint_video_loop']) ? 'checked' : '' ?>> Loop</label>
                </div>

                <button type="submit" class="btn btn-primary">Save Video Settings</button>
            </form>

        <?php endif; ?>
    </div>


    <?php
    // ---- Hero Car Image panel ----
    $heroReady = true; $hrow = null;
    try {
        $hrow = $pdo->query("SELECT * FROM homepage_video ORDER BY id ASC LIMIT 1")->fetch();
    } catch (PDOException $e) { $heroReady = false; }
    $hc = array_merge([
        'hero_car_path'    => 'assets/images/home/hero-car-transparent-clean.png',
        'hero_car_enabled' => 1,
        'hero_car_alt'     => 'Professionally refinished sports car',
    ], is_array($hrow) ? $hrow : []);
    $hcPath   = trim((string) ($hc['hero_car_path'] ?? ''));
    $hcAbs    = $hcPath !== '' ? dirname(__DIR__) . '/' . ltrim($hcPath, '/') : '';
    $hcExists = $hcPath !== '' && is_file($hcAbs);
    $hcName   = $hcExists ? basename($hcPath) : '';
    $hcSize   = $hcExists ? filesize($hcAbs) : 0;
    $hcSizeKB = $hcSize > 0 ? number_format($hcSize / 1024, 0) . ' KB' : '';
    $hcDim    = '';
    if ($hcExists) { $gi = @getimagesize($hcAbs); if ($gi) { $hcDim = $gi[0] . ' x ' . $gi[1] . ' px'; } }
    $hcUrl    = $hcExists ? asset($hcPath) . '?v=' . (@filemtime($hcAbs) ?: time()) : '';
    ?>
    <div class="dashboard-card" style="margin-top:24px;">
        <h2 style="margin-top:0;">Hero Car Image</h2>
        <p style="color:var(--text-muted);margin-top:-6px;">The transparent car shown on the homepage hero. Transparent PNG or WebP recommended. Disable or remove to hide it from the homepage.</p>

        <?php if (!$heroReady): ?>
            <div class="alert alert-error">The homepage settings table was not found. Run <code>database/migrations/phase-hero-car-admin.sql</code>.</div>
        <?php else: ?>

            <div style="margin-bottom:18px;">
                <?php if ($hcExists): ?>
                    <p>
                        <strong>Status:</strong>
                        <?php if (!empty($hc['hero_car_enabled'])): ?>
                            <span class="badge badge-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Disabled</span>
                        <?php endif; ?>
                    </p>
                    <p style="margin:6px 0;"><strong>Current file:</strong> <?= e($hcName) ?><?= $hcSizeKB ? ' (' . e($hcSizeKB) . ')' : '' ?><?= $hcDim ? ' - ' . e($hcDim) : '' ?></p>
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                        <div style="padding:10px; border-radius:10px; background:#ffffff; border:1px solid #e5e5e5; text-align:center;">
                            <img src="<?= e($hcUrl) ?>" alt="Hero car on light background" style="height:120px; display:block;">
                            <small style="color:#666;">Light</small>
                        </div>
                        <div style="padding:10px; border-radius:10px; background:#2a2d33; text-align:center;">
                            <img src="<?= e($hcUrl) ?>" alt="Hero car on dark background" style="height:120px; display:block;">
                            <small style="color:#cbd0d8;">Dark</small>
                        </div>
                    </div>
                    <form method="POST" action="<?= e(url('admin/homepage.php?action=hero_remove')) ?>"
                          onsubmit="return confirm('Remove the current hero car image? This clears it from the homepage.');"
                          style="margin-top:12px;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-secondary btn-sm">Remove current image</button>
                    </form>
                <?php else: ?>
                    <p style="color:var(--text-muted);">No hero car image uploaded.</p>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= e(url('admin/homepage.php?action=hero_save')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="hero_car_file"><?= $hcExists ? 'Replace image' : 'Upload image' ?> (PNG or WebP, max 8MB, min 800x400px)</label>
                    <input type="file" id="hero_car_file" name="hero_car_file" accept="image/png,image/webp,.png,.webp">
                </div>
                <div class="form-group">
                    <label for="hero_car_alt">Alt text</label>
                    <input type="text" id="hero_car_alt" name="hero_car_alt" value="<?= e($hc['hero_car_alt']) ?>">
                </div>
                <div style="margin-bottom:16px;">
                    <label><input type="checkbox" name="hero_car_enabled" value="1" <?= !empty($hc['hero_car_enabled']) ? 'checked' : '' ?>> Enabled (show on homepage)</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Hero Car</button>
            </form>

        <?php endif; ?>
    </div>

<?php elseif ($action === 'edit'): ?>
    <?php
    $sec = [
        'title' => '', 'subtitle' => '', 'content' => '', 'section_key' => '',
        'sort_order' => 0, 'is_active' => 1, 'image_path' => ''
    ];
    $found = false;
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM homepage_sections WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) { $sec = $fetched; $found = true; }
    }
    // Retain form data on error
    if (is_post()) {
        $sec = array_merge($sec, $_POST);
        $sec['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    }
    ?>
    <?php if (!$found && !is_post()): ?>
        <div class="alert alert-error">Section not found.</div>
        <a href="<?= e(url('admin/homepage.php')) ?>" class="btn btn-secondary">Back to list</a>
    <?php else: ?>
    <div class="dashboard-card">
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Section Key (read-only)</label>
                <input type="text" value="<?= e($sec['section_key']) ?>" disabled>
            </div>

            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?= e($sec['title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="subtitle">Subtitle</label>
                <input type="text" id="subtitle" name="subtitle" value="<?= e($sec['subtitle']) ?>">
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" rows="6"><?= e($sec['content']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">Sort Order (Lowest first)</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= e($sec['sort_order']) ?>">
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <?php if (!empty($sec['image_path'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?= e(asset($sec['image_path'])) ?>" alt="Section Image" style="max-height: 100px; border-radius:4px;">
                    </div>
                <?php endif; ?>
                <input type="hidden" name="existing_image_path" value="<?= e($sec['image_path']) ?>">
                <label for="image_upload">Upload New Image (JPG, PNG, WEBP - Max 2MB)</label>
                <input type="file" id="image_upload" name="image_upload" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= $sec['is_active'] ? 'checked' : '' ?>>
                    Active (Visible to public)
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Section</button>
                <a href="<?= e(url('admin/homepage.php')) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
