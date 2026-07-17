<?php
// Sigma Panels & Paint - Admin Logout (POST + CSRF only).

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// State-changing action: never via GET, always CSRF-checked.
if (!is_post() || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect('admin/dashboard.php');
}

logout_admin();
redirect('admin/login.php');
