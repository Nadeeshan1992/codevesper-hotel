<?php
/**
 * Master Sidebar Navigation
 * Featuring "Privileges Management" and Full RBAC Navigation
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$roleSlug = $user['role_slug'] ?? 'guest';
$dynamicRoomCount = (int)getDbConnection()->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand Header -->
    <a href="dashboard.php" class="sidebar-brand">
        <img src="assets/images/logo.jpg" alt="CodeVesper" class="brand-logo-img">
        <div class="brand-info">
            <h5><?= APP_NAME ?></h5>
            <span><?= APP_TAGLINE ?></span>
        </div>
    </a>

    <!-- User Profile Strip -->
    <div class="px-3 py-3 border-bottom border-secondary border-opacity-25 bg-black bg-opacity-25 d-flex align-items-center gap-2">
        <div class="user-avatar-circle">
            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="flex-grow-1 overflow-hidden">
            <div class="text-white fw-bold text-truncate small"><?= htmlspecialchars($user['full_name']) ?></div>
            <div><?= getRoleBadge($roleSlug) ?></div>
        </div>
    </div>

    <!-- Navigation Links -->
    <div class="sidebar-menu">
        <div class="menu-category">Main Overview</div>
        <a href="dashboard.php" class="nav-item-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <!-- Highlighted Privileges Management in Left Bar -->
        <div class="menu-category text-warning"><i class="fa-solid fa-shield-halved me-1"></i> Security & Access</div>
        <a href="privileges.php" class="nav-item-link privileges-link <?= ($currentPage == 'privileges.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-shield text-warning"></i>
            <span class="fw-bold">Privileges Management</span>
            <?php if ($roleSlug === 'admin'): ?>
                <span class="badge bg-warning text-dark ms-auto font-monospace" style="font-size: 0.65rem;">ADMIN</span>
            <?php endif; ?>
        </a>

        <div class="menu-category">Hotel & Accommodations</div>
        <a href="rooms.php" class="nav-item-link <?= ($currentPage == 'rooms.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-door-open"></i>
            <span>Rooms & Floor Rack</span>
            <span class="badge bg-primary ms-auto" style="font-size: 0.7rem;"><?= $dynamicRoomCount ?></span>
        </a>

        <?php if (hasPermission('reservations.manage') || hasPermission('frontdesk.checkin_checkout') || $roleSlug === 'owner'): ?>
        <a href="bookings.php" class="nav-item-link <?= ($currentPage == 'bookings.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Reservations & Check-In</span>
        </a>
        <?php endif; ?>

        <div class="menu-category">Restaurant & POS</div>
        <a href="pos.php" class="nav-item-link <?= ($currentPage == 'pos.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-tablet-screen-button text-info"></i>
            <span class="fw-bold text-light">Waiter Tablet POS</span>
            <span class="badge bg-success ms-auto pulse-badge" style="font-size: 0.65rem;">TOUCH</span>
        </a>

        <a href="kitchen.php" class="nav-item-link <?= ($currentPage == 'kitchen.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-fire text-danger"></i>
            <span>Live Kitchen KOT</span>
        </a>

        <?php if ($roleSlug !== 'waiter'): ?>
        <a href="restaurant-tables.php" class="nav-item-link <?= ($currentPage == 'restaurant-tables.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-utensils"></i>
            <span>Dining Tables</span>
        </a>
        <a href="restaurant-menu.php" class="nav-item-link <?= ($currentPage == 'restaurant-menu.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Food & Drink Menu</span>
        </a>
        <?php endif; ?>

        <div class="menu-category">Billing & Back Office</div>
        <?php if (hasPermission('billing.invoices') || $roleSlug === 'owner'): ?>
        <a href="billing.php" class="nav-item-link <?= ($currentPage == 'billing.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Guest Folio & Billing</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('stock.view') || $roleSlug === 'owner'): ?>
        <a href="stock.php" class="nav-item-link <?= ($currentPage == 'stock.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Stock & Inventory</span>
            <span class="badge bg-danger ms-auto" style="font-size: 0.65rem;">Alerts</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('staff.view') || $roleSlug === 'owner'): ?>
        <a href="staff.php" class="nav-item-link <?= ($currentPage == 'staff.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>Staff & Attendance</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('reports.financial') || $roleSlug === 'owner'): ?>
        <div class="menu-category">Analytics & Logs</div>
        <a href="reports.php" class="nav-item-link <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Financial & Audit Reports</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Sidebar Bottom Footer -->
    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-dot text-success small"></i>
            <span class="small text-muted">System Online</span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-outline-danger border-0" title="Sign Out">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</aside>
