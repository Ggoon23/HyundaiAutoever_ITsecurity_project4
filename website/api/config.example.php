<?php
/**
 * Database Configuration Example
 * Copy this file to config.php and update with your actual credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');  // or your RDS endpoint
define('DB_NAME', 'ota_db');
define('DB_USER', 'admin');
define('DB_PASS', 'password');  // Update with your actual password

// API Configuration
define('API_ENABLED', true);
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// CORS Configuration
define('CORS_ALLOWED_ORIGINS', '*'); // Update with your domain for production
?>
