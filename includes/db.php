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

        // Enable SSL when connecting to remote cloud databases (TiDB, Aiven, PlanetScale, Railway)
        $bundledCa = __DIR__ . '/../config/ca-bundle.crt';
        $sslCaKey = defined('\Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? @constant('PDO::MYSQL_ATTR_SSL_CA') : 1002);
        
        if (defined('DB_SSL_CA') && !empty(DB_SSL_CA) && file_exists(DB_SSL_CA)) {
            $options[$sslCaKey] = DB_SSL_CA;
        } elseif (file_exists($bundledCa)) {
            $options[$sslCaKey] = $bundledCa;
        } elseif (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            if (file_exists('/etc/pki/tls/certs/ca-bundle.crt')) {
                $options[$sslCaKey] = '/etc/pki/tls/certs/ca-bundle.crt';
            } elseif (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
                $options[$sslCaKey] = '/etc/ssl/certs/ca-certificates.crt';
            }
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            $isVercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']) || (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Vercel') !== false);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Connection Required | <?= APP_NAME ?></title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
                <style>
                    body { background: linear-gradient(135deg, #090e17 0%, #0d1527 100%); min-height: 100vh; color: #f1f5f9; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px; }
                    .setup-card { background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); max-width: 620px; width: 100%; padding: 35px; }
                    .badge-pill { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
                    .code-box { background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 16px; font-family: monospace; font-size: 0.85rem; color: #38bdf8; overflow-x: auto; margin: 15px 0; }
                    .step-box { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 14px 18px; margin-bottom: 12px; }
                </style>
            </head>
            <body>
            <div class="setup-card text-center">
                <div class="mb-3">
                    <span class="badge-pill">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Cloud Database Setup Required</span>
                    </span>
                </div>
                <h3 class="fw-bold mb-2 text-white"><?= APP_NAME ?> Management Suite</h3>
                <p class="text-secondary small mb-4">Your application code is deployed and working on Vercel, but needs a Cloud MySQL connection to store hotel data.</p>
                
                <div class="code-box text-start">
                    <div class="text-muted small mb-1">// Attempted connection target:</div>
                    <div>Host: <strong><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></strong></div>
                    <div>Database: <strong><?= htmlspecialchars(DB_NAME) ?></strong></div>
                    <div>Error: <span class="text-danger"><?= htmlspecialchars($e->getMessage()) ?></span></div>
                </div>

                <div class="text-start mt-3">
                    <h6 class="text-white fw-bold mb-2"><i class="fa-solid fa-bolt text-warning me-2"></i>How to connect your Cloud MySQL in 1 minute:</h6>
                    <div class="step-box">
                        <strong>1. Go to Vercel Project Dashboard:</strong>
                        <p class="text-muted small mb-0">Open your project &rarr; <strong>Settings</strong> &rarr; <strong>Environment Variables</strong>.</p>
                    </div>
                    <div class="step-box">
                        <strong>2. Add Connection Variables:</strong>
                        <p class="text-muted small mb-0">Add <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code>, <code>DB_PORT</code> (or <code>DATABASE_URL</code>).</p>
                    </div>
                    <div class="step-box">
                        <strong>3. Import Database Tables:</strong>
                        <p class="text-muted small mb-0">Import <code>sql/hotel_db_export.sql</code> into your cloud MySQL database console.</p>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-center gap-3">
                    <a href="" class="btn btn-primary rounded-pill px-4"><i class="fa-solid fa-rotate-right me-1"></i> Retry Connection</a>
                </div>
            </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
    return $pdo;
}
