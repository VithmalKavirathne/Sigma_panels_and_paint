<?php
// Sigma Panels & Paint - Admin Login
// Phase 5 implementation.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect to dashboard if already logged in
if (is_admin_logged_in()) {
    redirect('admin/dashboard.php');
}

$error = '';

if (is_post()) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf)) {
        $error = "Invalid form submission. Please try again.";
    } elseif (login_admin($email, $password)) {
        redirect('admin/dashboard.php');
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(SITE_NAME) ?> Admin</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><?= e(SITE_NAME) ?></h2>
                <p>Admin Portal Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
