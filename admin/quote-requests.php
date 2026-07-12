<?php
// Sigma Panels & Paint - Quote Requests Manager
// Phase 8 implementation.

$adminPageKey = 'quote-requests';
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = db();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Allowed status values (VARCHAR column; seed uses pending/reviewed)
$allowed_statuses = ['pending', 'reviewed', 'quoted', 'closed'];

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM quote_requests WHERE id = :id");
            $stmt->execute(['id' => $_GET['id']]);
            $_SESSION['admin_success'] = "Quote request deleted successfully.";
            redirect('admin/quote-requests.php');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = "Error deleting request.";
            redirect('admin/quote-requests.php');
        }
    }
}

// Handle Status Update Action
if ($action === 'status' && isset($_GET['id']) && is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $new_status = $_POST['status'] ?? '';
        if (in_array($new_status, $allowed_statuses, true)) {
            try {
                $stmt = $pdo->prepare("UPDATE quote_requests SET status = :status, updated_at = NOW() WHERE id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $_GET['id']]);
                $_SESSION['admin_success'] = "Status updated successfully.";
                redirect('admin/quote-requests.php?action=view&id=' . urlencode($_GET['id']));
            } catch (Exception $e) {
                $_SESSION['admin_error'] = "Error updating status.";
                redirect('admin/quote-requests.php');
            }
        } else {
            $_SESSION['admin_error'] = "Invalid status value.";
            redirect('admin/quote-requests.php?action=view&id=' . urlencode($_GET['id']));
        }
    }
}

// Fetch session messages
if (isset($_SESSION['admin_success'])) { $success = $_SESSION['admin_success']; unset($_SESSION['admin_success']); }
if (isset($_SESSION['admin_error'])) { $error = $_SESSION['admin_error']; unset($_SESSION['admin_error']); }

// Helper for status badge class
function quote_status_badge($status) {
    return $status === 'closed' ? 'badge-secondary' : 'badge-success';
}
?>

<div class="page-header">
    <h1>Quote Requests</h1>
    <p>Review and manage customer quote requests.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($action === 'view'): ?>
    <?php
    $req = false;
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $req = $stmt->fetch();
    }
    ?>
    <?php if (!$req): ?>
        <div class="alert alert-error">Quote request not found.</div>
        <a href="<?= e(url('admin/quote-requests.php')) ?>" class="btn btn-secondary">Back to list</a>
    <?php else: ?>
        <div class="dashboard-card">
            <table>
                <tbody>
                    <tr><th style="width:200px;">Customer Name</th><td><?= e($req['customer_name']) ?></td></tr>
                    <tr><th>Phone</th><td><?= e($req['phone']) ?></td></tr>
                    <tr><th>Email</th><td><?= e($req['email']) ?></td></tr>
                    <tr><th>Service Interest</th><td><?= e($req['service_interest']) ?></td></tr>
                    <tr><th>Project Location</th><td><?= e($req['project_location']) ?></td></tr>
                    <tr><th>Message</th><td><?= nl2br(e($req['message'])) ?></td></tr>
                    <tr><th>Current Status</th><td><span class="badge <?= quote_status_badge($req['status']) ?>"><?= e($req['status']) ?></span></td></tr>
                    <tr><th>Submitted</th><td><?= e(format_date($req['created_at'], 'd M Y, g:i a')) ?></td></tr>
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                <form method="POST" action="<?= e(url('admin/quote-requests.php?action=status&id=' . $req['id'])) ?>" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                    <?= csrf_field() ?>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="status">Update Status</label>
                        <select id="status" name="status">
                            <?php foreach ($allowed_statuses as $st): ?>
                                <option value="<?= e($st) ?>" <?= $req['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Status</button>
                </form>
            </div>

            <div style="margin-top: 20px; display:flex; gap:10px;">
                <a href="<?= e(url('admin/quote-requests.php')) ?>" class="btn btn-secondary">Back to list</a>
                <form method="POST" action="<?= e(url('admin/quote-requests.php?action=delete&id=' . $req['id'])) ?>" onsubmit="return confirm('Are you sure you want to delete this request?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Delete Request</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
    $requests = $pdo->query("SELECT * FROM quote_requests ORDER BY created_at DESC")->fetchAll();
    ?>
    <div class="dashboard-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="6">No quote requests found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <strong><?= e($req['customer_name']) ?></strong><br>
                                <small style="color:var(--text-muted)"><?= e($req['email']) ?></small>
                            </td>
                            <td><?= e($req['service_interest']) ?></td>
                            <td><?= e($req['project_location']) ?></td>
                            <td><span class="badge <?= quote_status_badge($req['status']) ?>"><?= e($req['status']) ?></span></td>
                            <td><?= e(format_date($req['created_at'], 'd M Y')) ?></td>
                            <td class="action-buttons">
                                <a href="<?= e(url('admin/quote-requests.php?action=view&id=' . $req['id'])) ?>" class="btn btn-secondary btn-sm">View</a>
                                <form method="POST" action="<?= e(url('admin/quote-requests.php?action=delete&id=' . $req['id'])) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this request?');">
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
