<?php
/**
 * Database Configuration
 * Hotel & Restaurant Management System
 */

// Suppress deprecation and minor notices from breaking HTML layouts / sending headers prematurely
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// Helper to get environment variable with fallback
if (!function_exists('getEnvVar')) {
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
}

// Support all-in-one DATABASE_URL / MYSQL_URL (e.g. Railway, PlanetScale, TiDB, Aiven)
$databaseUrl = getEnvVar('DATABASE_URL', getEnvVar('MYSQL_URL', ''));
$parsedDbUrl = !empty($databaseUrl) ? parse_url($databaseUrl) : null;

$defaultHost = $parsedDbUrl['host'] ?? 'localhost';
$defaultUser = $parsedDbUrl['user'] ?? 'root';
$defaultPass = $parsedDbUrl['pass'] ?? '';
$defaultName = isset($parsedDbUrl['path']) ? ltrim($parsedDbUrl['path'], '/') : 'hotel_db';
$defaultPort = isset($parsedDbUrl['port']) ? (string)$parsedDbUrl['port'] : '3306';

if (!defined('DB_HOST')) define('DB_HOST', getEnvVar('DB_HOST', $defaultHost));
if (!defined('DB_USER')) define('DB_USER', getEnvVar('DB_USER', $defaultUser));
if (!defined('DB_PASS')) define('DB_PASS', getEnvVar('DB_PASS', $defaultPass));
if (!defined('DB_NAME')) define('DB_NAME', getEnvVar('DB_NAME', $defaultName));
if (!defined('DB_PORT')) define('DB_PORT', getEnvVar('DB_PORT', $defaultPort));
if (!defined('DB_CHARSET')) define('DB_CHARSET', getEnvVar('DB_CHARSET', 'utf8mb4'));
if (!defined('DB_SSL_CA')) define('DB_SSL_CA', getEnvVar('DB_SSL_CA', ''));

if (!defined('APP_KEY')) define('APP_KEY', getEnvVar('APP_KEY', 'codevesper_hotel_resort_suite_secret_2026'));
if (!defined('APP_NAME')) define('APP_NAME', 'CodeVesper');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Hotel & Restaurant Management Suite');
if (!defined('APP_LOGO')) define('APP_LOGO', 'assets/images/logo.jpg');
if (!defined('APP_URL')) define('APP_URL', getEnvVar('APP_URL', 'http://localhost:8080/Hotel'));
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', 'LKR ');
