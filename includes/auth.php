<?php
/**
 * Authentication and Authorization Helper
 * RBAC with Dynamic Permissions Check
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Auto-restore session from signed HMAC cookie if serverless container recycled
if (empty($_SESSION['user_id']) && !empty($_COOKIE['cv_session'])) {
    restoreSessionFromCookie($_COOKIE['cv_session']);
}

function issueAuthCookie(array $user): void {
    $payload = [
        'id'        => $user['id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'role_id'   => $user['role_id'],
        'role_slug' => $user['role_slug'],
        'role_name' => $user['role_name'],
        'email'     => $user['email'] ?? '',
        'avatar'    => $user['avatar'] ?? 'default_avatar.png',
        'exp'       => time() + (86400 * 7) // 7 days
    ];
    $encoded = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $encoded, APP_KEY);
    $token = $encoded . '.' . $signature;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    setcookie('cv_session', $token, [
        'expires'  => time() + (86400 * 7),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps
    ]);
}

function restoreSessionFromCookie(string $token): bool {
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return false;
    }
    [$encoded, $signature] = $parts;
    $expected = hash_hmac('sha256', $encoded, APP_KEY);
    if (!hash_equals($expected, $signature)) {
        return false;
    }
    $data = json_decode(base64_decode($encoded), true);
    if (!is_array($data) || empty($data['id']) || empty($data['exp'])) {
        return false;
    }
    if (time() > $data['exp']) {
        return false; // Expired
    }

    $_SESSION['user_id']   = $data['id'];
    $_SESSION['username']  = $data['username'];
    $_SESSION['full_name'] = $data['full_name'];
    $_SESSION['role_id']   = $data['role_id'];
    $_SESSION['role_slug'] = $data['role_slug'];
    $_SESSION['role_name'] = $data['role_name'];
    $_SESSION['email']     = $data['email'] ?? '';
    $_SESSION['avatar']    = $data['avatar'] ?? 'default_avatar.png';
    return true;
}

function clearAuthCookie(): void {
    if (isset($_COOKIE['cv_session'])) {
        setcookie('cv_session', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['cv_session']);
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? 0,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'Staff',
        'role_id'   => $_SESSION['role_id'] ?? 1,
        'role_slug' => $_SESSION['role_slug'] ?? 'admin',
        'role_name' => $_SESSION['role_name'] ?? 'Staff',
        'email'     => $_SESSION['email'] ?? '',
        'avatar'    => $_SESSION['avatar'] ?? 'default_avatar.png'
    ];
}

function hasRole($roles): bool {
    if (!isLoggedIn()) return false;
    $currentRole = $_SESSION['role_slug'] ?? '';
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

function hasPermission(string $permissionSlug): bool {
    if (!isLoggedIn()) return false;
    
    // Administrator role always has all permissions
    if (isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === 'admin') {
        return true;
    }

    $roleId = (int)$_SESSION['role_id'];
    $db = getDbConnection();

    try {
        $stmt = $db->prepare("
            SELECT rp.is_granted 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
            LIMIT 1
        ");
        $stmt->execute([$roleId, $permissionSlug]);
        $row = $stmt->fetch();
        return $row && (int)$row['is_granted'] === 1;
    } catch (Exception $e) {
        return false;
    }
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requirePermission(string $permissionSlug): void {
    requireAuth();
    if (!hasPermission($permissionSlug)) {
        http_response_code(403);
        include __DIR__ . '/../includes/403.php';
        exit;
    }
}

function logAudit(string $module, string $action, ?string $details = null): void {
    if (!isLoggedIn()) return;
    try {
        $db = getDbConnection();
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, module, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $module, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently skip log failures to avoid halting critical operations
    }
}
