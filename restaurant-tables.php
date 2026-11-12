<?php
/**
 * Restaurant Dining Tables Management
 */
$pageTitle = 'Dining Tables Management';
$pageSubtitle = 'Table Layout, Occupancy Status & Floor Sections';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Handle table status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table_status'])) {
    $tableId = (int)$_POST['table_id'];
    $status = $_POST['status'];
    $db->prepare("UPDATE dining_tables SET status = ? WHERE id = ?")->execute([$status, $tableId]);
    logAudit('Restaurant', 'Table Status Changed', "Updated Table ID $tableId status to $status");
}

$tables = $db->query("
    SELECT 
        t.*,
        COUNT(o.id) as active_orders_count,
        COALESCE(SUM(o.total_amount), 0) as total_amount,
        GROUP_CONCAT(o.order_number SEPARATOR ', ') as order_numbers,
        SUBSTRING_INDEX(GROUP_CONCAT(o.order_number ORDER BY o.id DESC SEPARATOR ', '), ', ', 1) as latest_order_number,
        SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(u.full_name, 'Guest QR') ORDER BY o.id DESC SEPARATOR ', '), ', ', 1) as latest_waiter_name
    FROM dining_tables t
    LEFT JOIN orders o ON (o.table_id = t.id AND o.status IN ('pending', 'in_kitchen', 'ready'))
    LEFT JOIN users u ON o.waiter_id = u.id
    GROUP BY t.id
    ORDER BY t.section ASC, t.id ASC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-utensils text-success me-2"></i>Restaurant Dining Floor Plan</h5>
        <span class="text-muted small">Floor sections and live dining table states</span>
    </div>
    <div class="d-flex gap-2">
        <a href="table-qrs.php" class="btn btn-outline-warning text-dark btn-sm px-3 rounded-pill fw-bold">
            <i class="fa-solid fa-qrcode me-1 text-warning"></i> Print Table QR Stands
        </a>
        <a href="pos.php" class="btn btn-primary btn-sm px-3 rounded-pill">
            <i class="fa-solid fa-tablet-screen-button me-1"></i> Waiter Tablet POS
        </a>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($tables as $t): 
        $color = 'success';
        if ($t['status'] === 'occupied') $color = 'danger';
        elseif ($t['status'] === 'reserved') $color = 'warning';
        elseif ($t['status'] === 'cleaning') $color = 'info';
    ?>
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        <div class="card-custom h-100 p-3 border-2 border-<?= $color ?> shadow-sm d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['table_number']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($t['section']) ?></small>
                    </div>
                    <span class="badge bg-light text-dark border">
                        <i class="fa-solid fa-users me-1"></i> <?= $t['capacity'] ?> Seats
                    </span>
                </div>

                <div class="my-2">
                    <?= getTableStatusBadge($t['status']) ?>
                </div>

                <?php if ($t['active_orders_count'] > 0): ?>
                    <div class="p-2 bg-light rounded-2 small mt-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark font-monospace">
                                <i class="fa-solid fa-receipt me-1 text-primary"></i> <?= htmlspecialchars($t['latest_order_number']) ?>
                            </span>
                            <?php if ($t['active_orders_count'] > 1): ?>
                                <span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;"><?= $t['active_orders_count'] ?> Active Orders</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            Source: 
                            <?php if (strpos($t['latest_order_number'], 'QR-') === 0 || $t['latest_waiter_name'] === 'Guest QR'): ?>
                                <span class="badge bg-info-subtle text-info border border-info" style="font-size: 0.65rem;"><i class="fa-solid fa-qrcode me-1"></i> Guest QR</span>
                            <?php else: ?>
                                <?= htmlspecialchars($t['latest_waiter_name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-primary fw-bold mt-1">Total Bill: <?= formatMoney($t['total_amount']) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pt-3 border-top mt-3 d-flex gap-1">
                <?php if ($t['status'] === 'available'): ?>
                    <a href="pos.php" class="btn btn-sm btn-outline-success flex-grow-1">
                        <i class="fa-solid fa-plus me-1"></i> New Order
                    </a>
                <?php else: ?>
                    <form method="POST" class="flex-grow-1">
                        <input type="hidden" name="update_table_status" value="1">
                        <input type="hidden" name="table_id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="status" value="available">
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fa-solid fa-rotate-left me-1"></i> Free Table
                        </button>
                    </form>
                <?php endif; ?>

                <a href="qr-menu.php?table=<?= $t['id'] ?>" target="_blank" class="btn btn-sm btn-outline-warning text-dark" title="View Guest QR Menu">
                    <i class="fa-solid fa-qrcode text-warning"></i>
                </a>

                <form method="POST">
                    <input type="hidden" name="update_table_status" value="1">
                    <input type="hidden" name="table_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="status" value="<?= ($t['status'] === 'occupied') ? 'cleaning' : 'occupied' ?>">
                    <button type="submit" class="btn btn-sm btn-outline-dark" title="Toggle Clean/Occupy">
                        <i class="fa-solid fa-brush"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
