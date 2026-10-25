<?php
/**
 * Financial Reports & System Audit Logs
 */
$pageTitle = 'Financial & Audit Reports';
$pageSubtitle = 'Daily Revenue, RevPAR, Room Occupancy Trends & Activity Audit Trail';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Date range filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Revenue stats
$totalInvoiced = $db->query("
    SELECT COALESCE(SUM(total_amount), 0) FROM invoices
")->fetchColumn();

$totalRestaurantSales = $db->query("
    SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status != 'cancelled'
")->fetchColumn();

$totalPaidDeposits = $db->query("
    SELECT COALESCE(SUM(deposit_paid), 0) FROM check_ins
")->fetchColumn();

// Fetch Audit Logs
$auditLogs = $db->query("
    SELECT al.*, u.username, u.full_name, r.name as role_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY al.id DESC
    LIMIT 50
")->fetchAll();
?>

<!-- Date Range Picker & Print -->
<div class="card-custom p-3 mb-4 bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
    <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
        <span class="small fw-bold text-muted">Reporting Period:</span>
        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
        <span class="text-muted">to</span>
        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
        <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
    </form>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Print Executive Report
    </button>
</div>

<!-- Revenue Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Total Invoiced (Rooms & F&B)</div>
                <div class="stat-number text-success"><?= formatMoney($totalInvoiced) ?></div>
                <div class="small text-muted">Master folios settled</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Restaurant Gross Sales</div>
                <div class="stat-number text-primary"><?= formatMoney($totalRestaurantSales) ?></div>
                <div class="small text-muted">Dine-in & Room orders</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-utensils"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Advance Deposits Collected</div>
                <div class="stat-number text-warning"><?= formatMoney($totalPaidDeposits) ?></div>
                <div class="small text-muted">Reservation guarantees</div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>
    </div>

    <?php $totalReportRooms = (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn(); ?>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Total Room Inventory</div>
                <div class="stat-number text-dark"><?= $totalReportRooms ?> Keys</div>
                <div class="small text-muted">Scalable property keys</div>
            </div>
            <div class="stat-icon bg-dark bg-opacity-10 text-dark">
                <i class="fa-solid fa-key"></i>
            </div>
        </div>
    </div>
</div>

<!-- System Audit & Security Trail -->
<div class="card-custom">
    <div class="card-header-clean d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-shield-halved text-warning me-2"></i>System Security & Operational Audit Trail
            </h5>
            <small class="text-muted">Track who performed logins, changed room statuses, edited privileges, or created bills</small>
        </div>
        <span class="badge bg-light text-dark border">Immutable Ledger</span>
    </div>

    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 datatable-init">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Timestamp</th>
                        <th>User Account</th>
                        <th>Role</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Activity Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                    <tr>
                        <td>
                            <small class="text-muted font-monospace"><?= formatDateTime($log['created_at']) ?></small>
                        </td>
                        <td>
                            <span class="fw-bold text-dark small"><?= htmlspecialchars($log['username'] ?? 'System') ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small" style="font-size: 0.68rem;"><?= htmlspecialchars($log['role_name'] ?? 'System') ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($log['module']) ?></span>
                        </td>
                        <td>
                            <span class="fw-semibold small"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars($log['details'] ?? '-') ?></small>
                        </td>
                        <td>
                            <code class="small text-muted"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
