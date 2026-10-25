<?php
/**
 * Logout script
 */
require_once __DIR__ . '/includes/auth.php';

logAudit('Auth', 'User Logout', 'User signed out from terminal.');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
clearAuthCookie();
session_destroy();

header('Location: login.php?msg=logged_out');
exit;
