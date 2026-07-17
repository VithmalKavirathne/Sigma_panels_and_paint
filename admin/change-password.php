<?php
// Sigma Panels & Paint - Admin Change Password.
$adminPageKey = 'change-password';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce auth BEFORE any output (session id regeneration must precede output).
require_admin();

$pdo = db();
$error = '';
$success = '';

if (is_post()) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $adminId = (int) $_SESSION['admin_id'];

        $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $adminId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
            sec_log('password_change_fail', $adminId);
        } elseif (strlen($new) < 12) {
            $error = 'New password must be at least 12 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($new, PASSWORD_DEFAULT);
                // Increment auth_version to invalidate ALL other sessions.
                $u = $pdo->prepare("UPDATE admins SET password_hash = :h, password_changed_at = NOW(), auth_version = auth_version + 1 WHERE id = :id");
                $u->execute(['h' => $hash, 'id' => $adminId]);
                $pdo->commit();

                // Re-establish THIS session with the new version so it survives.
                $v = $pdo->prepare("SELECT auth_version FROM admins WHERE id = :id");
                $v->execute(['id' => $adminId]);
                sec_establish_admin_session($adminId, (int) $v->fetchColumn());
                sec_log('password_change', $adminId);
                $success = 'Password changed. All other sessions have been logged out.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = 'Could not change password. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="page-header">
    <h1>Change Password</h1>
    <p>Update your admin password. Changing it logs out all other sessions.</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="dashboard-card" style="max-width:520px;">
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password" maxlength="200">
        </div>
        <div class="form-group">
            <label for="new_password">New password (min 12 characters)</label>
            <input type="password" id="new_password" name="new_password" required minlength="12" maxlength="200" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="12" maxlength="200" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>
<?php
require_once __DIR__ . '/../includes/admin-footer.php';
