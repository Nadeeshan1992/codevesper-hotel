<?php
/**
 * Database Connection via PDO
 */
require_once __DIR__ . '/../config/database.php';

function getDbConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Enable SSL when connecting to remote cloud databases (Aiven, TiDB, PlanetScale, Railway)
        if (defined('DB_SSL_CA') && !empty(DB_SSL_CA) && file_exists(DB_SSL_CA)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        } elseif (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
