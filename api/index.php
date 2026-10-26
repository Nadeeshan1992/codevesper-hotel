<?php
/**
 * CodeVesper Hotel & Restaurant Management Suite
 * Serverless Front-Controller & Gateway for Vercel
 *
 * Directs all incoming requests to their respective application scripts,
 * maintaining session state, relative paths, and clean URLs.
 */

// Suppress deprecations and warnings from breaking layouts or headers
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// Start output buffering to prevent headers already sent issues
if (!ob_get_level()) {
    ob_start();
}

// Establish project root as current working directory
$rootDir = dirname(__DIR__);
chdir($rootDir);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);

// Normalize path: trim leading/trailing slashes and remove optional /Hotel base prefix
$path = trim($parsedPath, '/');
if (stripos($path, 'Hotel/') === 0) {
    $path = substr($path, 6);
} elseif (strcasecmp($path, 'Hotel') === 0) {
    $path = '';
}

// Prevent recursion if this script itself was targeted
if ($path === 'api/index.php' || $path === 'api/index') {
    $path = '';
}

// 1. Root landing page -> route to root index.php
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require $rootDir . '/index.php';
    exit;
}

// 2. Direct script or file check (e.g. dashboard.php, pos.php, api/orders.php, api/rooms.php)
$targetFile = $rootDir . '/' . $path;

if (is_file($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $_SERVER['SCRIPT_NAME'] = '/' . $path;
        $_SERVER['PHP_SELF'] = '/' . $path;
        require $targetFile;
        exit;
    }

    // Serve static files if not already caught by Vercel edge routes
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf'
    ];
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . filesize($targetFile));
    readfile($targetFile);
    exit;
}

// 3. Extension-less routes (e.g. /dashboard -> dashboard.php, /pos -> pos.php)
if (is_file($targetFile . '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $path . '.php';
    $_SERVER['PHP_SELF'] = '/' . $path . '.php';
    require $targetFile . '.php';
    exit;
}

// 4. Extension-less API routes (e.g. /api/rooms -> api/rooms.php)
if (stripos($path, 'api/') === 0) {
    $apiFile = $rootDir . '/' . $path . '.php';
    if (is_file($apiFile)) {
        $_SERVER['SCRIPT_NAME'] = '/' . $path . '.php';
        $_SERVER['PHP_SELF'] = '/' . $path . '.php';
        require $apiFile;
        exit;
    }
}

// 5. Fallback 404
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 Not Found | CodeVesper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="text-center p-4">
        <h1 class="display-3 text-warning fw-bold">404</h1>
        <h4 class="mb-3">Page Not Found</h4>
        <p class="text-muted">The requested path <code>/<?= htmlspecialchars($path) ?></code> does not exist on this server.</p>
        <a href="dashboard.php" class="btn btn-primary rounded-pill px-4 mt-2">Return to Dashboard</a>
    </div>
</body>
</html>
