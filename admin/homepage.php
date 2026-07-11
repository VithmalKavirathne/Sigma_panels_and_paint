<?php
// Sigma Panels & Paint - Homepage Manager
// Phase 7 implementation.

$adminPageKey = 'homepage';
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = db();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

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
