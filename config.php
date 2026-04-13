<?php
/**
 * App configurations
 */

//Dev or Prod
define('IS_DEV', true);

//Database Config
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'clinic');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');
define('DB_PATH', '');

/**
 * Get database connection (legacy fallback)
 */
function getDB() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        return new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
