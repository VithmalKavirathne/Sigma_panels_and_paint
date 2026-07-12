<?php
// Sigma Panels & Paint - Database Connection
// Phase 3 include component.

require_once __DIR__ . '/config.php';

/**
 * Returns a singleton PDO database connection instance.
 * @return PDO
 */
function db() {
    static $pdo = null;

    if ($pdo === null) {
        $portPart = (defined('DB_PORT') && DB_PORT) ? ';port=' . DB_PORT : '';
        $dsn = 'mysql:host=' . DB_HOST . $portPart . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements for security
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In a production environment, log the error to a file instead of displaying it.
            // error_log('Database connection failed: ' . $e->getMessage());
            die('A database error occurred. Please try again later.');
        }
    }

    return $pdo;
}
