<?php
/**
 * Database Configuration
 * Hotel & Restaurant Management System
 */

// Helper to get environment variable with fallback
function getEnvVar(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    return $default;
}

// Support all-in-one DATABASE_URL / MYSQL_URL (e.g. Railway, PlanetScale, TiDB, Aiven)
$databaseUrl = getEnvVar('DATABASE_URL', getEnvVar('MYSQL_URL', ''));
$parsedDbUrl = !empty($databaseUrl) ? parse_url($databaseUrl) : null;

$defaultHost = $parsedDbUrl['host'] ?? 'localhost';
$defaultUser = $parsedDbUrl['user'] ?? 'root';
$defaultPass = $parsedDbUrl['pass'] ?? '';
$defaultName = isset($parsedDbUrl['path']) ? ltrim($parsedDbUrl['path'], '/') : 'hotel_db';
$defaultPort = isset($parsedDbUrl['port']) ? (string)$parsedDbUrl['port'] : '3306';

define('DB_HOST', getEnvVar('DB_HOST', $defaultHost));
define('DB_USER', getEnvVar('DB_USER', $defaultUser));
define('DB_PASS', getEnvVar('DB_PASS', $defaultPass));
define('DB_NAME', getEnvVar('DB_NAME', $defaultName));
define('DB_PORT', getEnvVar('DB_PORT', $defaultPort));
define('DB_CHARSET', getEnvVar('DB_CHARSET', 'utf8mb4'));
define('DB_SSL_CA', getEnvVar('DB_SSL_CA', ''));

define('APP_KEY', getEnvVar('APP_KEY', 'codevesper_hotel_resort_suite_secret_2026'));
define('APP_NAME', 'CodeVesper');
define('APP_TAGLINE', 'Hotel & Restaurant Management Suite');
define('APP_LOGO', 'assets/images/logo.jpg');
define('APP_URL', getEnvVar('APP_URL', 'http://localhost:8080/Hotel'));
define('CURRENCY_SYMBOL', 'LKR ');
