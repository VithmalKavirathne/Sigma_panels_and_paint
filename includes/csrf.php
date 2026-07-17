<?php
// Sigma Panels & Paint - CSRF Protection.
// Uses the single hardened session path (sec_session_start via security.php).

require_once __DIR__ . '/security.php';
sec_session_start();

/** Generates (once per session) and returns a CSRF token. */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/** Validates a token with hash_equals; logs genuine rejections (never the token). */
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token) || !is_string($token)) {
        return false;
    }
    $ok = hash_equals($_SESSION['csrf_token'], $token);
    if (!$ok && function_exists('sec_log')) {
        sec_log('csrf_reject');
    }
    return $ok;
}

/** Hidden CSRF input for forms. */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
