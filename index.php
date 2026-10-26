<?php
/**
 * Root Index Redirect
 */
ob_start();
require_once __DIR__ . '/includes/auth.php';

$target = isLoggedIn() ? 'dashboard.php' : 'login.php';

if (!headers_sent()) {
    header('Location: ' . $target);
}
echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $target . '"><script>window.location.href="' . $target . '";</script></head><body>Redirecting to <a href="' . $target . '">' . $target . '</a>...</body></html>';
exit;
