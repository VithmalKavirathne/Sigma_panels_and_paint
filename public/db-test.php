<?php
// =========================================================================
// !!! DELETE THIS FILE BEFORE DEPLOYMENT !!!
// =========================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Attempt to connect to the database
    $pdo = db();
    
    // Fetch the first row from business_settings
    $stmt = $pdo->query("SELECT * FROM business_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    if ($settings) {
        echo "<h1>Database Connection Successful!</h1>";
        echo "<p>Connected to database and retrieved business settings for: <strong>" . e($settings['business_name']) . "</strong></p>";
    } else {
        echo "<h1>Database Connection Successful!</h1>";
        echo "<p>Connected to database, but the business_settings table is empty. Did you import seed.sql?</p>";
    }
} catch (Exception $e) {
    // Show a simple safe error message
    echo "<h1>Database Connection Failed</h1>";
    echo "<p>Could not connect to the database. Please check your credentials in includes/config.php and ensure your MySQL server is running.</p>";
}
?>
