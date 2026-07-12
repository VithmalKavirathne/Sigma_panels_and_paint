<?php
// Sigma Panels & Paint - CSRF Protection
// Phase 3 include component.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates and returns a CSRF token.
 * Creates a new one if it doesn't exist in the session.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback for older PHP versions if random_bytes fails, though unlikely on PHP 8+
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a given CSRF token against the session token.
 */
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generates a hidden HTML input field containing the CSRF token.
 * Use this inside forms to protect against Cross-Site Request Forgery.
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
