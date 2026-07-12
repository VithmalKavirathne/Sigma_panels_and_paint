<?php
// Sigma Panels & Paint - Git-safe configuration loader.
//
// IMPORTANT: This file contains NO real credentials and is safe to commit.
// The real database credentials live OUTSIDE the web root (public_html) in:
//
//     sigma_private/config.php      (one level ABOVE public_html)
//
// On Hostinger the layout looks like:
//     /home/uXXXXXXXX/domains/your-domain/
//         public_html/            <-- this Git repo is pulled in here
//             includes/config.php  (this file)
//         sigma_private/
//             config.php           <-- real DB credentials, never in Git
//
// dirname(__DIR__, 2) resolves to one level above public_html, so the
// private config sits next to public_html, i.e. "../sigma_private/config.php".

$sigmaPrivateConfig = dirname(__DIR__, 2) . '/sigma_private/config.php';

if (!is_file($sigmaPrivateConfig)) {
    http_response_code(500);
    exit('Configuration error: sigma_private/config.php was not found '
        . 'one level above public_html. Copy the template there and add '
        . 'your real database credentials.');
}

// Loads: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, BASE_URL
require $sigmaPrivateConfig;

// ---- Non-secret, path-derived settings (safe in Git) ----
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Sigma Panels & Paint');
}

// Uploads live inside public_html/uploads (dirname(__DIR__) == public_html).
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
