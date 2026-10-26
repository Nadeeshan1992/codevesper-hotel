<?php
/**
 * Master Header Template
 */
if (!ob_get_level()) {
    ob_start();
}
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

requireAuth();
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?><?= APP_NAME ?></title>
    
    <!-- CodeVesper Brand Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="apple-touch-icon" href="assets/images/logo.jpg">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pos.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="app-wrapper">
    <!-- Include Master Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none btn-sm" id="sidebarToggle" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-page-title">
                    <h4><?= $pageTitle ?? 'Dashboard' ?></h4>
                    <p><?= $pageSubtitle ?? 'Hotel & Restaurant Management Suite' ?></p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Quick POS Shortcut for Waiter/Staff -->
                <a href="pos.php" class="btn btn-sm btn-outline-primary d-none d-md-inline-flex align-items-center gap-2 rounded-pill px-3">
                    <i class="fa-solid fa-tablet-screen-button text-primary"></i>
                    <span>Tablet POS</span>
                </a>

                <!-- Quick Rooms Status -->
                <?php $totalHeaderRooms = (int)getDbConnection()->query("SELECT COUNT(*) FROM rooms")->fetchColumn(); ?>
                <a href="rooms.php" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-2 rounded-pill px-3">
                    <i class="fa-solid fa-hotel text-warning"></i>
                    <span><?= $totalHeaderRooms ?> Rooms</span>
                </a>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar-circle" style="width: 28px; height: 28px; font-size: 0.8rem;">
                            <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                        </div>
                        <span class="small fw-bold d-none d-sm-inline"><?= htmlspecialchars($currentUser['username']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                            <div class="text-muted small"><?= getRoleBadge($currentUser['role_slug']) ?></div>
                        </li>
                        <li><a class="dropdown-item py-2" href="privileges.php"><i class="fa-solid fa-user-shield me-2 text-warning"></i> Privileges Management</a></li>
                        <li><a class="dropdown-item py-2" href="dashboard.php"><i class="fa-solid fa-sliders me-2 text-primary"></i> System Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Body Page Container -->
        <main class="page-container">
