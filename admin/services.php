<?php
// Sigma Panels & Paint - Services Manager
// Phase 6 implementation.

$adminPageKey = 'services';
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = db();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Service deleted successfully.";
            redirect('admin/services.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error deleting service.";
            redirect('admin/services.php');
        }
    }
}

// Handle Toggle Action
if ($action === 'toggle' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Service status updated.";
            redirect('admin/services.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error updating status.";
            redirect('admin/services.php');
        }
    }
}

// Process Create/Edit Form Submission
if (is_post() && ($action === 'create' || $action === 'edit')) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = "Invalid form submission.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug) && !empty($title)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }
        $short_description = trim($_POST['short_description'] ?? '');
        $full_description = trim($_POST['full_description'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
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
                    $upload_dir = UPLOAD_DIR . '/services/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $new_filename = 'srv_' . time() . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path = 'uploads/services/' . $new_filename;
                    } else {
                        $error = "Failed to move uploaded file.";
                    }
                } else {
                    $error = "Invalid image upload. Allowed: JPG, PNG, WEBP (Max 2MB).";
                }
            }

            if (!$error) {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO services (title, slug, short_description, full_description, image_path, icon, sort_order, is_featured, is_active, created_at, updated_at) VALUES (:title, :slug, :short_desc, :full_desc, :image_path, :icon, :sort_order, :is_featured, :is_active, NOW(), NOW())");
                    } else {
                        $stmt = $pdo->prepare("UPDATE services SET title = :title, slug = :slug, short_description = :short_desc, full_description = :full_desc, image_path = :image_path, icon = :icon, sort_order = :sort_order, is_featured = :is_featured, is_active = :is_active, updated_at = NOW() WHERE id = :id");
                        $stmt->bindValue(':id', $_GET['id']);
                    }
                    
                    $stmt->bindValue(':title', $title);
                    $stmt->bindValue(':slug', $slug);
                    $stmt->bindValue(':short_desc', $short_description);
                    $stmt->bindValue(':full_desc', $full_description);
                    $stmt->bindValue(':image_path', $image_path);
                    $stmt->bindValue(':icon', $icon);
                    $stmt->bindValue(':sort_order', $sort_order);
                    $stmt->bindValue(':is_featured', $is_featured);
                    $stmt->bindValue(':is_active', $is_active);
                    
                    $stmt->execute();
                    $_SESSION['admin_success'] = $action === 'create' ? "Service created successfully." : "Service updated successfully.";
                    redirect('admin/services.php');
                } catch (PDOException $e) {
                    $error = "Database error saving service.";
                }
            }
        }
    }
}

// Fetch session messages
if (isset($_SESSION['admin_success'])) { $success = $_SESSION['admin_success']; unset($_SESSION['admin_success']); }
if (isset($_SESSION['admin_error'])) { $error = $_SESSION['admin_error']; unset($_SESSION['admin_error']); }
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Services Manager</h1>
        <p>Manage the services offered by the business.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= e(url('admin/services.php?action=create')) ?>" class="btn btn-primary">Add New Service</a>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;padding:12px;margin-bottom:20px;border-radius:4px;"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <?php
    $services = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, created_at DESC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="5">No services found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($services as $srv): ?>
                        <tr>
                            <td><?= e($srv['sort_order']) ?></td>
                            <td>
                                <strong><?= e($srv['title']) ?></strong><br>
                                <small style="color:var(--text-muted)">/<?= e($srv['slug']) ?></small>
                            </td>
                            <td>
                                <?php if ($srv['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $srv['is_featured'] ? 'Yes' : 'No' ?></td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/services.php?action=edit&id=' . $srv['id'])) ?>" class="btn btn-secondary btn-sm">Edit</a>
                                
                                <form method="POST" action="<?= e(url('admin/services.php?action=toggle&id=' . $srv['id'])) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">Toggle</button>
                                </form>
                                
                                <form method="POST" action="<?= e(url('admin/services.php?action=delete&id=' . $srv['id'])) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <?php
    $srv = [
        'title' => '', 'slug' => '', 'short_description' => '', 'full_description' => '',
        'icon' => '', 'sort_order' => 0, 'is_featured' => 0, 'is_active' => 1, 'image_path' => ''
    ];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) $srv = $fetched;
    }
    // Retain form data on error
    if (is_post()) {
        $srv = array_merge($srv, $_POST);
        $srv['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
        $srv['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    }
    ?>
    <div class="dashboard-card">
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= e($srv['title']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug (Leave empty to auto-generate)</label>
                    <input type="text" id="slug" name="slug" value="<?= e($srv['slug']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="short_description">Short Description (for cards)</label>
                <textarea id="short_description" name="short_description" rows="2"><?= e($srv['short_description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="full_description">Full Description</label>
                <textarea id="full_description" name="full_description" rows="5"><?= e($srv['full_description']) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="icon">Icon Class (e.g. fa-car)</label>
                    <input type="text" id="icon" name="icon" value="<?= e($srv['icon']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="sort_order">Sort Order (Lowest first)</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?= e($srv['sort_order']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <?php if (!empty($srv['image_path'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?= e(asset($srv['image_path'])) ?>" alt="Service Image" style="max-height: 100px; border-radius:4px;">
                    </div>
                <?php endif; ?>
                <input type="hidden" name="existing_image_path" value="<?= e($srv['image_path']) ?>">
                <label for="image_upload">Upload New Image (JPG, PNG, WEBP - Max 2MB)</label>
                <input type="file" id="image_upload" name="image_upload" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="is_featured" value="1" <?= $srv['is_featured'] ? 'checked' : '' ?>>
                    Show on Homepage (Featured)
                </label>
                
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= $srv['is_active'] ? 'checked' : '' ?>>
                    Active (Visible to public)
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Service</button>
                <a href="<?= e(url('admin/services.php')) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
