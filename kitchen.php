<?php
/**
 * Live Kitchen Order Ticket (KOT) Screen
 * Displays incoming orders for chefs and kitchen staff
 */
$pageTitle = 'Live Kitchen Display (KOT)';
$pageSubtitle = 'Real-time Kitchen Order Tickets and Preparation Queue';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch active orders (pending, in_kitchen, ready)
$activeOrders = $db->query("
    SELECT o.*, 
           u.full_name as waiter_name,
           t.table_number,
           r.room_number,
           g.full_name as guest_name
    FROM orders o
    LEFT JOIN users u ON o.waiter_id = u.id
    LEFT JOIN dining_tables t ON o.table_id = t.id
    LEFT JOIN rooms r ON o.room_id = r.id
    LEFT JOIN guests g ON o.guest_id = g.id
    WHERE o.status IN ('pending', 'in_kitchen', 'ready')
    ORDER BY o.id ASC
")->fetchAll();

// Fetch items for each active order
$orderIds = array_column($activeOrders, 'id');
$itemsByOrder = [];

if (!empty($orderIds)) {
    $inClause = implode(',', $orderIds);
    $itemsStmt = $db->query("
        SELECT oi.*, m.name as item_name
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE oi.order_id IN ($inClause)
    ");
    while ($row = $itemsStmt->fetch()) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">
            <i class="fa-solid fa-fire text-danger me-2"></i>Active Kitchen Queue
        </h5>
        <span class="text-muted small">Real-time orders sent by waiters from Tablet POS</span>
    </div>
    <div class="d-flex gap-2">
        <a href="pos.php" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Order (POS)
        </a>
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh Queue
        </button>
    </div>
</div>

<?php if (empty($activeOrders)): ?>
    <div class="card-custom p-5 text-center text-muted">
        <i class="fa-solid fa-bell-concierge fa-4x text-secondary opacity-50 mb-3"></i>
        <h4 class="fw-bold text-dark">Kitchen Queue is Empty</h4>
        <p class="mb-3">No pending orders at this moment. New orders sent by waiters on Tablet POS will appear here instantly.</p>
        <div>
            <a href="pos.php" class="btn btn-primary px-4 rounded-pill">Open Waiter Tablet POS</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($activeOrders as $order): 
            $orderItems = $itemsByOrder[$order['id']] ?? [];
            $badgeColor = ($order['status'] === 'ready') ? 'info' : 'warning';
            $badgeText = ($order['status'] === 'ready') ? 'Ready to Serve' : 'Cooking in Kitchen';
            $dest = ($order['order_type'] === 'room_service') 
                    ? '<i class="fa-solid fa-hotel text-primary me-1"></i> ' . htmlspecialchars($order['room_number']) . ' (' . htmlspecialchars($order['guest_name'] ?? 'Room Guest') . ')'
                    : '<i class="fa-solid fa-chair text-success me-1"></i> ' . htmlspecialchars($order['table_number'] ?? 'Dine-In');
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-custom h-100 border-2 border-<?= $badgeColor ?> shadow-sm">
                <div class="card-header-clean bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($order['order_number']) ?></span>
                        <div class="small text-muted"><?= date('h:i A', strtotime($order['created_at'])) ?> &bull; Waiter: <?= htmlspecialchars($order['waiter_name'] ?? 'Staff') ?></div>
                    </div>
                    <span class="badge bg-<?= $badgeColor ?> text-dark px-2 py-1 small"><?= $badgeText ?></span>
                </div>

                <div class="p-3">
                    <div class="mb-3 p-2 bg-light rounded-2 fw-bold text-dark small border">
                        Destination: <?= $dest ?>
                    </div>

                    <div class="list-group list-group-flush mb-3">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold text-dark">
                                        <span class="badge bg-primary me-2"><?= $item['quantity'] ?>x</span>
                                        <?= htmlspecialchars($item['item_name']) ?>
                                    </div>
                                    <?php if (!empty($item['special_instructions'])): ?>
                                        <div class="small text-danger ms-4 ps-2 border-start border-danger">
                                            <i class="fa-solid fa-pencil me-1"></i> <?= htmlspecialchars($item['special_instructions']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div class="alert alert-warning py-1 px-2 small mb-3">
                            <i class="fa-solid fa-note-sticky me-1"></i> <?= htmlspecialchars($order['notes']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-3 border-top bg-light mt-auto d-flex gap-2">
                    <?php if ($order['status'] === 'in_kitchen'): ?>
                        <button class="btn btn-info text-dark w-100 fw-bold btn-sm py-2" onclick="updateOrderStatus(<?= $order['id'] ?>, 'ready')">
                            <i class="fa-solid fa-bell-concierge me-1"></i> Mark Ready to Serve
                        </button>
                    <?php else: ?>
                        <button class="btn btn-success w-100 fw-bold btn-sm py-2" onclick="updateOrderStatus(<?= $order['id'] ?>, 'served')">
                            <i class="fa-solid fa-check-double me-1"></i> Mark Served
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-outline-danger btn-sm px-3" onclick="updateOrderStatus(<?= $order['id'] ?>, 'cancelled')" title="Cancel Order">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function updateOrderStatus(orderId, status) {
    const formData = new FormData();
    formData.append('action', 'update_kot_status');
    formData.append('order_id', orderId);
    formData.append('status', status);

    fetch('api/orders.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: data.message });
        }
    });
}

// Auto-refresh KOT screen every 20 seconds
setTimeout(() => {
    location.reload();
}, 20000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
