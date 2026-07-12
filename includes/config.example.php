<?php
// EXAMPLE ONLY - documents what sigma_private/config.php must define.
//
// Do NOT put real credentials here and do NOT rename this to config.php.
// includes/config.php is a loader that reads the real values from
// sigma_private/config.php located one level ABOVE public_html.
//
// Create sigma_private/config.php on the server with content like below:

define('DB_HOST', 'localhost');            // Hostinger MySQL host (usually 'localhost')
define('DB_PORT', '3306');                 // Hostinger MySQL port (usually 3306)
define('DB_NAME', 'REPLACE_DB_NAME');      // Hostinger database name
define('DB_USER', 'REPLACE_DB_USER');      // Hostinger database username
define('DB_PASS', 'REPLACE_DB_PASSWORD');  // Hostinger database password

// Final domain, https, no trailing slash.
define('BASE_URL', 'https://REPLACE-YOUR-DOMAIN');
