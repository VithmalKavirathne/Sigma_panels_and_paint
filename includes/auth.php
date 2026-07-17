<?php
// Sigma Panels & Paint - Authentication Guard (hardened).
// Single hardened session initialization path via security.php / sec_session_start().

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

sec_session_start();

/**
 * Attempts to log in an admin. Normalizes email, enforces DB-backed rate
 * limiting, verifies with password_verify, transparently rehashes, and
 * establishes a hardened session (id, auth_version, issued_at, last_activity).
 */
function login_admin($email, $password) {
    $emailNorm = sec_email($email);
    if ($emailNorm === null) {
        sec_record_attempt((string) $email, false);   // count the attempt; no enumeration
        sec_log('login_fail', null, 'invalid_email_format');
        return false;
    }
    if (sec_login_blocked($emailNorm)) {
        sec_log('rate_limited', null, 'login');
        return false;
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, password_hash, auth_version FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $emailNorm]);
    $admin = $stmt->fetch();

    $ok = $admin && password_verify($password, $admin['password_hash']);
    sec_record_attempt($emailNorm, (bool) $ok);
    if (!$ok) {
        sec_log('login_fail', $admin['id'] ?? null);
        return false;
    }

    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $u = $pdo->prepare("UPDATE admins SET password_hash = :h WHERE id = :id");
        $u->execute(['h' => $newHash, 'id' => $admin['id']]);
    }

    sec_establish_admin_session($admin['id'], $admin['auth_version']);
    sec_log('login_success', $admin['id']);
    return true;
}

/** Logs out the current admin and destroys the session securely. */
function logout_admin() {
    $id = $_SESSION['admin_id'] ?? null;
    sec_logout();
    sec_log('logout', $id);
}

/** Cheap check used by the login page redirect (does not enforce version). */
function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']);
}

/** Current admin record (never includes password_hash). */
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
 * Middleware: every protected request verifies authenticated id + current
 * admins.auth_version + inactivity timeout + absolute timeout (sec_enforce_admin_session).
 */
function require_admin() {
    if (!sec_enforce_admin_session()) {
        $target = function_exists('url') ? url('admin/login.php') : '/admin/login.php';
        header('Location: ' . $target);
        exit;
    }
}
