<?php
// Sigma Panels & Paint - Authentication Guard
// Phase 3 include component.

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Attempts to log in an admin using email and password.
 * Uses password_verify against the bcrypt hash in the database.
 */
function login_admin($email, $password) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        return true;
    }

    return false;
}

/**
 * Logs out the current admin and destroys the session securely.
 */
function logout_admin() {
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
}

/**
 * Checks if an admin is currently logged in.
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

/**
 * Retrieves the currently logged-in admin's record (excluding password_hash).
 */
function current_admin() {
    if (!is_admin_logged_in()) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, name, email, created_at, updated_at FROM admins WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    return $stmt->fetch();
}

/**
 * Middleware function to require an active admin session.
 * Redirects to login page if not authenticated.
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        // Root-relative so it stays on the current host/port (8088 or 8091).
        $target = function_exists('url') ? url('admin/login.php') : '/admin/login.php';
        header('Location: ' . $target);
        exit;
    }
}
