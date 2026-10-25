<?php
/**
 * API: Privileges & Permission Toggle Handler
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only Administrator can modify privileges
if ($_SESSION['role_slug'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only Administrator can modify role privileges.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleId = (int)($_POST['role_id'] ?? 0);
    $permissionId = (int)($_POST['permission_id'] ?? 0);
    $isGranted = (int)($_POST['is_granted'] ?? 0);

    if ($roleId <= 0 || $permissionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    // Protect Administrator role from locking itself out
    if ($roleId === 1 && $isGranted === 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot revoke permissions from System Administrator.']);
        exit;
    }

    try {
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO role_permissions (role_id, permission_id, is_granted)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_granted = VALUES(is_granted)
        ");
        $stmt->execute([$roleId, $permissionId, $isGranted]);

        logAudit('Privileges', 'Permission Updated', "Updated permission ID $permissionId for Role ID $roleId to status $isGranted");

        echo json_encode(['success' => true, 'message' => 'Privilege updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed']);
