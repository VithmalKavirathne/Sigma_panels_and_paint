<?php
// Sigma Panels & Paint - FAQ Manager
// Phase 8 implementation.

$adminPageKey = 'faqs';
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
            $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "FAQ deleted successfully.";
            redirect('admin/faqs.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error deleting FAQ.";
            redirect('admin/faqs.php');
        }
    }
}

// Handle Toggle Action
if ($action === 'toggle' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("UPDATE faqs SET is_active = NOT is_active WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "FAQ status updated.";
            redirect('admin/faqs.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error updating status.";
            redirect('admin/faqs.php');
        }
    }
}

// Process Create/Edit Form Submission
if (is_post() && ($action === 'create' || $action === 'edit')) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = "Invalid form submission.";
    } else {
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($question)) {
            $error = "Question is required.";
        } elseif (empty($answer)) {
            $error = "Answer is required.";
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, sort_order, is_active, created_at, updated_at) VALUES (:question, :answer, :sort_order, :is_active, NOW(), NOW())");
                } else {
                    $stmt = $pdo->prepare("UPDATE faqs SET question = :question, answer = :answer, sort_order = :sort_order, is_active = :is_active, updated_at = NOW() WHERE id = :id");
                    $stmt->bindValue(':id', $_GET['id']);
                }
                $stmt->bindValue(':question', $question);
                $stmt->bindValue(':answer', $answer);
                $stmt->bindValue(':sort_order', $sort_order);
                $stmt->bindValue(':is_active', $is_active);
                $stmt->execute();
                $_SESSION['admin_success'] = $action === 'create' ? "FAQ created successfully." : "FAQ updated successfully.";
                redirect('admin/faqs.php');
            } catch (PDOException $e) {
                $error = "Database error saving FAQ.";
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
        <h1>FAQ Manager</h1>
        <p>Manage the frequently asked questions shown on the public site.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= e(url('admin/faqs.php?action=create')) ?>" class="btn btn-primary">Add New FAQ</a>
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
    $faqs = $pdo->query("SELECT * FROM faqs ORDER BY sort_order ASC, id ASC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Question</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faqs)): ?>
                        <tr><td colspan="4">No FAQs found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($faqs as $faq): ?>
                        <tr>
                            <td><?= e($faq['sort_order']) ?></td>
                            <td><strong><?= e($faq['question']) ?></strong></td>
                            <td>
                                <?php if ($faq['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/faqs.php?action=edit&id=' . $faq['id'])) ?>" class="btn btn-secondary btn-sm">Edit</a>

                                <form method="POST" action="<?= e(url('admin/faqs.php?action=toggle&id=' . $faq['id'])) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">Toggle</button>
                                </form>

                                <form method="POST" action="<?= e(url('admin/faqs.php?action=delete&id=' . $faq['id'])) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
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
    $faq = ['question' => '', 'answer' => '', 'sort_order' => 0, 'is_active' => 1];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) $faq = $fetched;
    }
    // Retain form data on error
    if (is_post()) {
        $faq = array_merge($faq, $_POST);
        $faq['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    }
    ?>
    <div class="dashboard-card">
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="question">Question *</label>
                <input type="text" id="question" name="question" value="<?= e($faq['question']) ?>" required>
            </div>

            <div class="form-group">
                <label for="answer">Answer *</label>
                <textarea id="answer" name="answer" rows="5" required><?= e($faq['answer']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">Sort Order (Lowest first)</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= e($faq['sort_order']) ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= $faq['is_active'] ? 'checked' : '' ?>>
                    Active (Visible to public)
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save FAQ</button>
                <a href="<?= e(url('admin/faqs.php')) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
