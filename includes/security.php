<?php
// Wired copy of the security bootstrap.
require_once __DIR__ . '/db.php';
/**
 * Sigma Panels & Paint - Security bootstrap (REVIEW BEFORE WIRING IN).
 *
 * Intended location once approved: includes/security.php
 * Include it FIRST (before any output and before session_start) from:
 *   - includes/auth.php, includes/csrf.php  (replace their bare session_start)
 *   - public entry points that start sessions
 *
 * Requires: includes/db.php (db()), includes/config.php loaded first.
 *
 * Everything here is additive and defensive. Nothing echoes secrets.
 * Depends on the phase-security-hardening.sql migration for:
 *   admins.auth_version, login_attempts, security_log.
 */

// ---------------------------------------------------------------------------
// 0. Environment + production error handling
// ---------------------------------------------------------------------------
if (!defined('APP_ENV')) {
    // Default to the safest posture. Local dev can define APP_ENV='development'
    // in its own config to see errors on screen.
    define('APP_ENV', 'production');
}
$sec_is_prod = (APP_ENV !== 'development' && APP_ENV !== 'local');

if ($sec_is_prod) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

// ---------------------------------------------------------------------------
// 1. Hardened session start
//    Call sec_session_start() everywhere instead of bare session_start().
// ---------------------------------------------------------------------------
function sec_is_https() {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return true;
    if (($_SERVER['SERVER_PORT'] ?? '') == 443) return true;
    // Hostinger proxy hint (only used to decide the Secure cookie flag).
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    return false;
}

function sec_session_start() {
    if (session_status() !== PHP_SESSION_NONE) {
        return; // already started
    }
    // Cookies only; no session id in URLs; reject uninitialised ids.
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');

    $params = [
        'lifetime' => 0,                 // session cookie (until browser close) + our own timeouts
        'path'     => '/',
        'httponly' => true,
        'secure'   => sec_is_https(),    // Secure flag only over HTTPS (won't break local http dev)
        'samesite' => 'Lax',
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($params);
    } else {
        session_set_cookie_params($params['lifetime'], $params['path'] . '; samesite=Lax', '', $params['secure'], $params['httponly']);
    }
    // Neutral cookie name (does not reveal PHP/framework).
    session_name('sigma_sess');
    session_start();
}

// ---------------------------------------------------------------------------
// 2. Security response headers (PHP fallback; .htaccess also sets these)
// ---------------------------------------------------------------------------
function sec_headers($isAdmin = false) {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    if ($isAdmin) {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

// ---------------------------------------------------------------------------
// 3. Client IP (never trust arbitrary X-Forwarded-For for security decisions)
// ---------------------------------------------------------------------------
function sec_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $packed = @inet_pton($ip);
    return $packed !== false ? $packed : inet_pton('0.0.0.0');
}

// ---------------------------------------------------------------------------
// 4. Private security event logging (DB table + error_log fallback)
//    NEVER pass passwords, hashes, tokens, cookies, or session ids here.
// ---------------------------------------------------------------------------
function sec_log($event, $adminId = null, $meta = null) {
    $route = substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 255);
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            "INSERT INTO security_log (event, admin_id, ip, route, meta)
             VALUES (:e, :a, :ip, :r, :m)"
        );
        $stmt->bindValue(':e', substr((string)$event, 0, 64));
        $stmt->bindValue(':a', $adminId, $adminId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':ip', sec_client_ip());
        $stmt->bindValue(':r', $route);
        $stmt->bindValue(':m', $meta === null ? null : substr((string)$meta, 0, 255));
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('[sigma-sec] ' . $event . ' route=' . $route);
    }
}

// ---------------------------------------------------------------------------
// 5. Login rate limiting  (per normalized-account-hash AND per IP)
//    ~5 failures within 15 minutes -> temporary soft block. No permanent lock.
// ---------------------------------------------------------------------------
function sec_email_hash($email) {
    return hash('sha256', strtolower(trim((string)$email)));
}

function sec_login_blocked($email) {
    try {
        $pdo = db();
        $ident = sec_email_hash($email);
        $ip = sec_client_ip();
        $stmt = $pdo->prepare(
            "SELECT
                SUM(identifier_hash = :ident) AS by_acct,
                SUM(ip = :ip)                 AS by_ip
             FROM login_attempts
             WHERE success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)"
        );
        $stmt->bindValue(':ident', $ident);
        $stmt->bindValue(':ip', $ip);
        $stmt->execute();
        $row = $stmt->fetch();
        return ((int)($row['by_acct'] ?? 0) >= 5) || ((int)($row['by_ip'] ?? 0) >= 15);
    } catch (Throwable $e) {
        return false; // fail open on infra error so admins are never permanently locked out
    }
}

function sec_record_attempt($email, $success) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            "INSERT INTO login_attempts (identifier_hash, ip, success) VALUES (:ident, :ip, :s)"
        );
        $stmt->bindValue(':ident', sec_email_hash($email));
        $stmt->bindValue(':ip', sec_client_ip());
        $stmt->bindValue(':s', $success ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        if ($success) {
            // Clear this account's failures after a successful login.
            $del = $pdo->prepare("DELETE FROM login_attempts WHERE identifier_hash = :ident AND success = 0");
            $del->bindValue(':ident', sec_email_hash($email));
            $del->execute();
        }
    } catch (Throwable $e) {
        // best-effort only
    }
}

// ---------------------------------------------------------------------------
// 6. Session lifecycle checks (inactivity 30m, absolute 8h, auth_version)
//    Call sec_enforce_admin_session() at the top of every protected request
//    (e.g. inside require_admin()).
// ---------------------------------------------------------------------------
define('SEC_IDLE_TIMEOUT', 30 * 60);       // 30 minutes
define('SEC_ABSOLUTE_TIMEOUT', 8 * 3600);  // 8 hours

function sec_enforce_admin_session() {
    if (empty($_SESSION['admin_id'])) return false;

    $now = time();
    $issued = $_SESSION['issued_at'] ?? 0;
    $seen   = $_SESSION['last_activity'] ?? 0;

    if (($now - $issued) > SEC_ABSOLUTE_TIMEOUT || ($now - $seen) > SEC_IDLE_TIMEOUT) {
        sec_log('session_expired', $_SESSION['admin_id'] ?? null);
        sec_logout();
        return false;
    }

    // auth_version must still match the DB (bumped on password change / logout-all).
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT auth_version FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $_SESSION['admin_id']]);
        $current = $stmt->fetchColumn();
        if ($current === false || (int)$current !== (int)($_SESSION['auth_version'] ?? -1)) {
            sec_log('session_invalidated', $_SESSION['admin_id'] ?? null);
            sec_logout();
            return false;
        }
    } catch (Throwable $e) {
        // On DB error, do not silently trust the session.
        sec_logout();
        return false;
    }

    $_SESSION['last_activity'] = $now;
    // Periodically rotate the id to limit fixation windows.
    if (($now - ($_SESSION['regenerated_at'] ?? 0)) > 900) { // every 15 min
        session_regenerate_id(true);
        $_SESSION['regenerated_at'] = $now;
    }
    return true;
}

function sec_establish_admin_session($adminId, $authVersion) {
    session_regenerate_id(true);
    $_SESSION['admin_id']       = (int)$adminId;
    $_SESSION['auth_version']   = (int)$authVersion;
    $_SESSION['issued_at']      = time();
    $_SESSION['last_activity']  = time();
    $_SESSION['regenerated_at'] = time();
}

function sec_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', !empty($p['secure']), !empty($p['httponly']));
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

// ---------------------------------------------------------------------------
// 7. Reusable input validators (server-side)
// ---------------------------------------------------------------------------
function sec_str($v, $max = 255) {
    if (is_array($v)) return null;                 // reject arrays where scalar expected
    $v = (string)$v;
    if (!mb_check_encoding($v, 'UTF-8')) return null;
    $v = str_replace(["\r\n", "\r"], "\n", $v);    // normalize line endings
    $v = trim($v);
    return mb_substr($v, 0, $max);
}
function sec_email($v) {
    $v = sec_str($v, 191);
    if ($v === null) return null;
    $v = strtolower($v);
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
}
function sec_int($v, $min = 1, $max = PHP_INT_MAX) {
    if (is_array($v)) return null;
    if (!preg_match('/^\d{1,18}$/', (string)$v)) return null;
    $n = (int)$v;
    return ($n >= $min && $n <= $max) ? $n : null;
}
function sec_enum($v, array $allowed) {
    $v = is_array($v) ? null : (string)$v;
    return in_array($v, $allowed, true) ? $v : null;
}
function sec_slug($v, $max = 191) {
    $v = sec_str($v, $max);
    return ($v !== null && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $v)) ? $v : null;
}
// Only allow redirects to internal paths (prevents open redirect).
function sec_safe_redirect_target($path, $fallback = '/') {
    $path = (string)$path;
    if ($path === '' || $path[0] !== '/' || strncmp($path, '//', 2) === 0 || strpos($path, "\\") !== false) {
        return $fallback;
    }
    return $path;
}
