<?php
// Sigma Panels & Paint - Contact Messages Manager
// Phase 8 implementation.

$adminPageKey = 'messages';
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = db();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Allowed status values (VARCHAR column; seed uses unread/read)
$allowed_statuses = ['unread', 'read'];

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Message deleted successfully.";
            redirect('admin/messages.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error deleting message.";
            redirect('admin/messages.php');
        }
    }
}

// Handle Status Update Action (toggle or explicit set)
if ($action === 'status' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $new_status = $_POST['status'] ?? '';
        if (in_array($new_status, $allowed_statuses, true)) {
            try {
                $stmt = $pdo->prepare("UPDATE contact_messages SET status = :status, updated_at = NOW() WHERE id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $_GET['id']]);
                $_SESSION['admin_success'] = "Message marked as " . $new_status . ".";
            } catch (Exception $e) {
                $_SESSION['admin_error'] = "Error updating status.";
            }
        } else {
            $_SESSION['admin_error'] = "Invalid status value.";
        }
        $back = $_POST['return'] ?? 'list';
        if ($back === 'view') {
            redirect('admin/messages.php?action=view&id=' . urlencode($_GET['id']));
        }
        redirect('admin/messages.php');
    }
}

// Fetch session messages
if (isset($_SESSION['admin_success'])) { $success = $_SESSION['admin_success']; unset($_SESSION['admin_success']); }
if (isset($_SESSION['admin_error'])) { $error = $_SESSION['admin_error']; unset($_SESSION['admin_error']); }
?>

<div class="page-header">
    <h1>Contact Messages</h1>
    <p>Review and manage messages submitted through the contact form.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($action === 'view'): ?>
    <?php
    $msg = false;
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $msg = $stmt->fetch();
    }
    ?>
    <?php if (!$msg): ?>
        <div class="alert alert-error">Message not found.</div>
        <a href="<?= e(url('admin/messages.php')) ?>" class="btn btn-secondary">Back to list</a>
    <?php else: ?>
        <div class="dashboard-card">
            <table>
                <tbody>
                    <tr><th style="width:200px;">Name</th><td><?= e($msg['name']) ?></td></tr>
                    <tr><th>Phone</th><td><?= e($msg['phone']) ?></td></tr>
                    <tr><th>Email</th><td><?= e($msg['email']) ?></td></tr>
                    <tr><th>Subject</th><td><?= e($msg['subject']) ?></td></tr>
                    <tr><th>Message</th><td><?= nl2br(e($msg['message'])) ?></td></tr>
                    <tr><th>Status</th><td><span class="badge <?= $msg['status'] === 'unread' ? 'badge-success' : 'badge-secondary' ?>"><?= e($msg['status']) ?></span></td></tr>
                    <tr><th>Received</th><td><?= e(format_date($msg['created_at'], 'd M Y, g:i a')) ?></td></tr>
                </tbody>
            </table>

            <div style="margin-top: 20px; display:flex; gap:10px; flex-wrap:wrap;">
                <a href="<?= e(url('admin/messages.php')) ?>" class="btn btn-secondary">Back to list</a>

                <?php $toggle_to = $msg['status'] === 'unread' ? 'read' : 'unread'; ?>
                <form method="POST" action="<?= e(url('admin/messages.php?action=status&id=' . $msg['id'])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= e($toggle_to) ?>">
                    <input type="hidden" name="return" value="view">
                    <button type="submit" class="btn btn-secondary">Mark as <?= e(ucfirst($toggle_to)) ?></button>
                </form>

                <form method="POST" action="<?= e(url('admin/messages.php?action=delete&id=' . $msg['id'])) ?>" onsubmit="return confirm('Are you sure you want to delete this message?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Delete Message</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
    $messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="5">No messages found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td>
                                <strong><?= e($msg['name']) ?></strong><br>
                                <small style="color:var(--text-muted)"><?= e($msg['email']) ?></small>
                            </td>
                            <td><?= e($msg['subject']) ?></td>
                            <td><span class="badge <?= $msg['status'] === 'unread' ? 'badge-success' : 'badge-secondary' ?>"><?= e($msg['status']) ?></span></td>
                            <td><?= e(format_date($msg['created_at'], 'd M Y')) ?></td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/messages.php?action=view&id=' . $msg['id'])) ?>" class="btn btn-secondary btn-sm">View</a>

                                <?php $toggle_to = $msg['status'] === 'unread' ? 'read' : 'unread'; ?>
                                <form method="POST" action="<?= e(url('admin/messages.php?action=status&id=' . $msg['id'])) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= e($toggle_to) ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">Mark <?= e(ucfirst($toggle_to)) ?></button>
                                </form>

                                <form method="POST" action="<?= e(url('admin/messages.php?action=delete&id=' . $msg['id'])) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
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
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
