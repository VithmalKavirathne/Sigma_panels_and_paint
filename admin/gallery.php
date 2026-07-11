<?php
// Sigma Panels & Paint - Gallery Manager
// Phase 7 implementation.

$adminPageKey = 'gallery';
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
            $stmt = $pdo->prepare("DELETE FROM gallery_items WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Gallery item deleted successfully.";
            redirect('admin/gallery.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error deleting item.";
            redirect('admin/gallery.php');
        }
    }
}

// Handle Toggle Action
if ($action === 'toggle' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("UPDATE gallery_items SET is_active = NOT is_active WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Item status updated.";
            redirect('admin/gallery.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error updating status.";
            redirect('admin/gallery.php');
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
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_path = $_POST['existing_image_path'] ?? '';

        if (empty($title)) {
            $error = "Title is required.";
        } elseif (empty($category)) {
            $error = "Category is required.";
        } else {
            // Handle File Upload
            if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed) && $_FILES['image_upload']['size'] <= 2097152) { // 2MB
                    $upload_dir = UPLOAD_DIR . '/gallery/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $new_filename = 'gal_' . time() . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path = 'uploads/gallery/' . $new_filename;
                    } else {
                        $error = "Failed to move uploaded file.";
                    }
                } else {
                    $error = "Invalid image upload. Allowed: JPG, PNG, WEBP (Max 2MB).";
                }
            }

            // image_path is required (NOT NULL in schema)
            if (!$error && empty($image_path)) {
                $error = "An image is required for a gallery item.";
            }

            if (!$error) {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO gallery_items (title, category, image_path, description, sort_order, is_active, created_at, updated_at) VALUES (:title, :category, :image_path, :description, :sort_order, :is_active, NOW(), NOW())");
                    } else {
                        $stmt = $pdo->prepare("UPDATE gallery_items SET title = :title, category = :category, image_path = :image_path, description = :description, sort_order = :sort_order, is_active = :is_active, updated_at = NOW() WHERE id = :id");
                        $stmt->bindValue(':id', $_GET['id']);
                    }
                    $stmt->bindValue(':title', $title);
                    $stmt->bindValue(':category', $category);
                    $stmt->bindValue(':image_path', $image_path);
                    $stmt->bindValue(':description', $description);
                    $stmt->bindValue(':sort_order', $sort_order);
                    $stmt->bindValue(':is_active', $is_active);
                    $stmt->execute();
                    $_SESSION['admin_success'] = $action === 'create' ? "Gallery item created successfully." : "Gallery item updated successfully.";
                    redirect('admin/gallery.php');
                } catch (PDOException $e) {
                    $error = "Database error saving item.";
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
        <h1>Gallery Manager</h1>
        <p>Manage the project gallery images shown to the public.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= e(url('admin/gallery.php?action=create')) ?>" class="btn btn-primary">Add New Item</a>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <?php
    $items = $pdo->query("SELECT * FROM gallery_items ORDER BY sort_order ASC, created_at DESC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6">No gallery items found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['sort_order']) ?></td>
                            <td>
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?= e(asset($item['image_path'])) ?>" alt="" class="thumb">
                                <?php else: ?>
                                    <span class="thumb thumb-empty">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= e($item['title']) ?></strong></td>
                            <td><span class="badge badge-secondary"><?= e($item['category']) ?></span></td>
                            <td>
                                <?php if ($item['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/gallery.php?action=edit&id=' . $item['id'])) ?>" class="btn btn-secondary btn-sm">Edit</a>

                                <form method="POST" action="<?= e(url('admin/gallery.php?action=toggle&id=' . $item['id'])) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">Toggle</button>
                                </form>

                                <form method="POST" action="<?= e(url('admin/gallery.php?action=delete&id=' . $item['id'])) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
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
    $item = [
        'title' => '', 'category' => '', 'description' => '',
        'sort_order' => 0, 'is_active' => 1, 'image_path' => ''
    ];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM gallery_items WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) $item = $fetched;
    }
    // Retain form data on error
    if (is_post()) {
        $item = array_merge($item, $_POST);
        $item['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    }
    ?>
    <div class="dashboard-card">
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= e($item['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" id="category" name="category" value="<?= e($item['category']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= e($item['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">Sort Order (Lowest first)</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= e($item['sort_order']) ?>">
            </div>

            <div class="form-group">
                <label>Current Image<?= $action === 'create' ? ' *' : '' ?></label>
                <?php if (!empty($item['image_path'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?= e(asset($item['image_path'])) ?>" alt="Gallery Image" style="max-height: 100px; border-radius:4px;">
                    </div>
                <?php endif; ?>
                <input type="hidden" name="existing_image_path" value="<?= e($item['image_path']) ?>">
                <label for="image_upload">Upload <?= $action === 'edit' ? 'New ' : '' ?>Image (JPG, PNG, WEBP - Max 2MB)</label>
                <input type="file" id="image_upload" name="image_upload" accept=".jpg,.jpeg,.png,.webp" <?= ($action === 'create') ? 'required' : '' ?>>
            </div>

            <div style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                    Active (Visible to public)
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Item</button>
                <a href="<?= e(url('admin/gallery.php')) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
