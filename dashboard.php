<?php
/**
 * Main Central Dashboard
 * CodeVesper Hotel & Restaurant Management Suite
 */
$pageTitle = 'Executive & Operational Dashboard';
$pageSubtitle = 'Real-time property operations, room occupancy and revenue analytics';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// 1. Dynamic Room Occupancy Metrics
$roomCounts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM rooms 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalRooms = (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupiedRooms = $roomCounts['occupied'] ?? 0;
$vacantRooms = $roomCounts['vacant'] ?? 0;
$reservedRooms = $roomCounts['reserved'] ?? 0;
$dirtyRooms = $roomCounts['dirty'] ?? 0;
$maintenanceRooms = $roomCounts['maintenance'] ?? 0;
$occupancyRate = ($totalRooms > 0) ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

// 2. Today's Revenue (Rooms + Restaurant)
$todayRestaurantRevenue = $db->query("
    SELECT COALESCE(SUM(total_amount), 0) 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() AND payment_status != 'cancelled'
")->fetchColumn();

$todayRoomRevenue = $db->query("
    SELECT COALESCE(SUM(deposit_paid), 0) 
    FROM check_ins 
    WHERE DATE(check_in_time) = CURDATE()
")->fetchColumn();

$totalTodayRevenue = (float)$todayRestaurantRevenue + (float)$todayRoomRevenue;

// 3. Active Tables & Pending KOTs
$activeOrdersCount = $db->query("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status IN ('pending', 'in_kitchen', 'ready')
")->fetchColumn();

$occupiedTablesCount = $db->query("
    SELECT COUNT(*) 
    FROM dining_tables 
    WHERE status = 'occupied'
")->fetchColumn();

// 4. Low stock alerts
$lowStockCount = $db->query("
    SELECT COUNT(*) 
    FROM stock_items 
    WHERE current_stock <= minimum_stock
")->fetchColumn();

// 5. Vacant & Clean Rooms (Ready for Immediate Check-In)
$vacantRoomsList = $db->query("
    SELECT r.*, rt.name as type_name, rt.code as type_code, rt.capacity, rt.amenities
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.status = 'vacant'
    ORDER BY r.floor ASC, r.room_number ASC
")->fetchAll();

// 6. Confirmed Advance Bookings & Upcoming Arrivals
$reservedArrivalsList = $db->query("
    SELECT res.*, r.room_number, r.floor, rt.name as type_name, g.full_name as guest_name, g.phone as guest_phone, g.email as guest_email
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN guests g ON res.guest_id = g.id
    WHERE res.status = 'confirmed'
    ORDER BY res.check_in_date ASC, res.id ASC
")->fetchAll();

// 7. Recent Restaurant & Room Service Orders
$recentOrders = $db->query("
    SELECT o.*, u.full_name as waiter_name, t.table_number, r.room_number
    FROM orders o
    LEFT JOIN users u ON o.waiter_id = u.id
    LEFT JOIN dining_tables t ON o.table_id = t.id
    LEFT JOIN rooms r ON o.room_id = r.id
    ORDER BY o.id DESC
    LIMIT 6
")->fetchAll();
?>

<!-- Quick Action Shortcuts Bar -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card-custom p-3 bg-white d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-5">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0">Quick Operations Hub</h6>
                    <small class="text-muted">Direct shortcuts for standard daily operations</small>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="pos.php" class="btn btn-primary btn-sm px-3 rounded-pill">
                    <i class="fa-solid fa-tablet-screen-button me-1"></i> Waiter Tablet POS
                </a>
                <a href="rooms.php" class="btn btn-outline-dark btn-sm px-3 rounded-pill">
                    <i class="fa-solid fa-door-open me-1"></i> Rooms & Floor Rack
                </a>
                <a href="bookings.php" class="btn btn-outline-info text-dark btn-sm px-3 rounded-pill">
                    <i class="fa-solid fa-calendar-plus text-info me-1"></i> New Reservation
                </a>
                <a href="privileges.php" class="btn btn-outline-warning text-dark btn-sm px-3 rounded-pill">
                    <i class="fa-solid fa-user-shield text-warning me-1"></i> Privileges Management
                </a>
                <a href="kitchen.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                    <i class="fa-solid fa-fire me-1"></i> Kitchen KOT Display
                </a>
                <a href="billing.php" class="btn btn-outline-success btn-sm px-3 rounded-pill">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Folio & Billing
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Primary Metric KPI Cards -->
<div class="row g-4 mb-4">
    <!-- Occupancy KPI -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <div class="stat-label">Room Occupancy Rate</div>
                <div class="stat-number text-primary"><?= $occupancyRate ?>%</div>
                <div class="stat-subtext">
                    <span class="text-danger fw-bold"><?= $occupiedRooms ?> Occupied</span> / <?= $totalRooms ?> Total Keys
                </div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-hotel"></i>
            </div>
        </div>
    </div>

    <!-- Today's Estimated Revenue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-success">
            <div class="stat-content">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-number text-success"><?= formatMoney($totalTodayRevenue) ?></div>
                <div class="stat-subtext">
                    Dining: <?= formatMoney($todayRestaurantRevenue) ?> &bull; Rooms: <?= formatMoney($todayRoomRevenue) ?>
                </div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-cash-register"></i>
            </div>
        </div>
    </div>

    <!-- Active Restaurant KOTs -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-warning">
            <div class="stat-content">
                <div class="stat-label">Active Kitchen KOTs</div>
                <div class="stat-number text-warning"><?= $activeOrdersCount ?> Orders</div>
                <div class="stat-subtext">
                    <?= $occupiedTablesCount ?> Tables Active &bull; In Kitchen Queue
                </div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-fire-burner"></i>
            </div>
        </div>
    </div>

    <!-- Stock & Supplies Status -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card <?= $lowStockCount > 0 ? 'stat-danger' : 'stat-success' ?>">
            <div class="stat-content">
                <div class="stat-label">Low Stock Items</div>
                <div class="stat-number <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $lowStockCount ?> Alerts</div>
                <div class="stat-subtext">
                    <?= $lowStockCount > 0 ? 'Reorder needed immediately' : 'Inventory levels optimal' ?>
                </div>
            </div>
            <div class="stat-icon <?= $lowStockCount > 0 ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?>">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Property Room Status Visual Rack -->
<div class="card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-bed text-primary me-2"></i>Live Rooms Inventory</h5>
            <small class="text-muted">Real-time status overview of all registered property keys across floors</small>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.45rem; vertical-align: middle;"></i><?= $vacantRooms ?> Vacant
            </span>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                <i class="fa-solid fa-circle text-warning me-1" style="font-size: 0.45rem; vertical-align: middle;"></i><?= $reservedRooms ?> Reserved
            </span>
            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                <i class="fa-solid fa-circle text-danger me-1" style="font-size: 0.45rem; vertical-align: middle;"></i><?= $occupiedRooms ?> Occupied
            </span>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1">
                <i class="fa-solid fa-circle text-secondary me-1" style="font-size: 0.45rem; vertical-align: middle;"></i><?= $dirtyRooms ?> Dirty
            </span>
            <?php if ($maintenanceRooms > 0): ?>
            <span class="badge bg-dark-subtle text-dark border border-dark px-2 py-1">
                <i class="fa-solid fa-circle text-dark me-1" style="font-size: 0.45rem; vertical-align: middle;"></i><?= $maintenanceRooms ?> Maint.
            </span>
            <?php endif; ?>
            <a href="rooms.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 ms-1">
                Manage Rack <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Uniform Equal-Sized Room Tiles Grid -->
    <div class="live-room-grid">
        <?php
        $allRoomsQuery = $db->query("SELECT id, room_number, status, floor, daily_rate FROM rooms ORDER BY floor ASC, room_number ASC")->fetchAll();
        foreach ($allRoomsQuery as $r):
            $status = $r['status'];
            $icon = 'circle-check';
            $label = 'Vacant';
            if ($status === 'occupied') { $icon = 'user-check'; $label = 'Occupied'; }
            elseif ($status === 'reserved') { $icon = 'clock'; $label = 'Reserved'; }
            elseif ($status === 'dirty') { $icon = 'broom'; $label = 'Dirty'; }
            elseif ($status === 'maintenance') { $icon = 'wrench'; $label = 'Maint.'; }
        ?>
        <a href="rooms.php" class="live-room-pill status-<?= $status ?>" title="<?= htmlspecialchars($r['room_number']) ?> - <?= ucfirst($status) ?> (Floor <?= $r['floor'] ?>)">
            <div class="live-room-pill-num"><?= htmlspecialchars($r['room_number']) ?></div>
            <div class="live-room-pill-status">
                <i class="fa-solid fa-<?= $icon ?>"></i>
                <span><?= $label ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Main Operational Section: Vacant & Reserved Tabs + Live Restaurant Orders -->
<div class="row g-4">
    <!-- Left Column: Vacant Rooms & Reserved Bookings Tabs -->
    <div class="col-12 col-xl-8">
        <div class="card-custom h-100">
            <div class="card-header-clean bg-light bg-opacity-50">
                <ul class="nav nav-pills dashboard-nav-pills gap-2" id="operationalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2" id="vacant-tab" data-bs-toggle="pill" data-bs-target="#vacant-pane" type="button" role="tab">
                            <i class="fa-solid fa-door-open text-success"></i>
                            <span>Vacant Rooms Available</span>
                            <span class="badge bg-success rounded-pill"><?= count($vacantRoomsList) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2" id="reserved-tab" data-bs-toggle="pill" data-bs-target="#reserved-pane" type="button" role="tab">
                            <i class="fa-solid fa-calendar-check text-warning"></i>
                            <span>Upcoming Reserved Stays</span>
                            <span class="badge bg-warning text-dark rounded-pill"><?= count($reservedArrivalsList) ?></span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="p-3">
                <div class="tab-content" id="operationalTabsContent">
                    <!-- Tab 1: Available Vacant Rooms -->
                    <div class="tab-pane fade show active" id="vacant-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="small text-muted">
                                <i class="fa-solid fa-circle-info me-1 text-primary"></i>Clean rooms ready for walk-in arrivals or new guest allocations
                            </div>
                            <a href="rooms.php" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                <i class="fa-solid fa-sliders me-1"></i> Room Rack
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Tier & Capacity</th>
                                        <th>Floor</th>
                                        <th>Daily Rate</th>
                                        <th>Key Features</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vacantRoomsList)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-bed fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                                All rooms are currently occupied, reserved, or undergoing cleaning.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vacantRoomsList as $vr): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1">
                                                        <?= htmlspecialchars($vr['room_number']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($vr['type_name']) ?></div>
                                                    <small class="text-muted"><i class="fa-solid fa-user-group me-1"></i>Max <?= $vr['capacity'] ?> Guests</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">Floor <?= $vr['floor'] ?></span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-primary small"><?= formatMoney($vr['daily_rate']) ?></div>
                                                    <small class="text-muted" style="font-size: 0.7rem;">/ night</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($vr['amenities'] ?: 'Standard Hotel Amenities') ?></small>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-success px-3 py-1 rounded-pill" onclick="openWalkinCheckin(<?= $vr['id'] ?>, '<?= htmlspecialchars(addslashes($vr['room_number'])) ?>', <?= $vr['daily_rate'] ?>)">
                                                        <i class="fa-solid fa-key me-1"></i> Walk-In Check-In
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Upcoming Reserved Stays & Confirmed Arrivals -->
                    <div class="tab-pane fade" id="reserved-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="small text-muted">
                                <i class="fa-solid fa-circle-info me-1 text-primary"></i>Confirmed advance reservations scheduled for arrival
                            </div>
                            <a href="bookings.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fa-solid fa-calendar-plus me-1"></i> Bookings Ledger
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Guest Details</th>
                                        <th>Assigned Room</th>
                                        <th>Check-In Date</th>
                                        <th>Advance Deposit</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reservedArrivalsList)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-calendar-xmark fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                                No pending advance reservations scheduled right now.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reservedArrivalsList as $res): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($res['booking_ref']) ?></span>
                                                    <div class="text-muted" style="font-size: 0.7rem;"><?= formatDate($res['created_at']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($res['guest_name']) ?></div>
                                                    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($res['guest_phone']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold">
                                                        <?= htmlspecialchars($res['room_number']) ?>
                                                    </span>
                                                    <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($res['type_name']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="small fw-semibold text-dark"><?= formatDate($res['check_in_date']) ?></div>
                                                    <small class="text-muted">Out: <?= formatDate($res['check_out_date']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success"><?= formatMoney($res['advance_deposit']) ?></span>
                                                    <div class="text-muted" style="font-size: 0.7rem;">of <?= formatMoney($res['total_room_cost']) ?></div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-primary px-3 py-1 rounded-pill" onclick="checkinReservation(<?= $res['id'] ?>, '<?= htmlspecialchars(addslashes($res['booking_ref'])) ?>', '<?= htmlspecialchars(addslashes($res['room_number'])) ?>')">
                                                        <i class="fa-solid fa-door-open me-1"></i> Check-In Guest
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Recent Restaurant Orders & Front Desk Summary -->
    <div class="col-12 col-xl-4">
        <div class="card-custom h-100">
            <div class="card-header-clean d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-utensils text-success me-2"></i>Live Restaurant Orders
                </h5>
                <a href="pos.php" class="btn btn-sm btn-outline-success rounded-pill px-2">Take Order</a>
            </div>
            <div class="p-3">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-bowl-food fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            No restaurant orders logged today yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): 
                            $loc = ($ro['order_type'] === 'room_service') ? htmlspecialchars($ro['room_number'] ?? 'Room') : htmlspecialchars($ro['table_number'] ?? 'Dine-In');
                        ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-dark small">
                                        <?= htmlspecialchars($ro['order_number']) ?>
                                        <span class="badge bg-light text-dark border ms-1"><?= $loc ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        Waiter: <?= htmlspecialchars($ro['waiter_name'] ?? 'Staff') ?> &bull; <?= date('h:i A', strtotime($ro['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary small"><?= formatMoney($ro['total_amount']) ?></div>
                                    <div><?= getOrderStatusBadge($ro['status']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold small mb-2"><i class="fa-solid fa-chart-pie me-1 text-primary"></i>Rooms Inventory Snapshot</h6>
                    <div class="d-flex justify-content-between text-muted small py-1">
                        <span><i class="fa-solid fa-circle text-success me-1" style="font-size: 0.6rem;"></i>Vacant & Clean</span>
                        <strong class="text-success"><?= $vacantRooms ?> Keys</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small py-1">
                        <span><i class="fa-solid fa-circle text-warning me-1" style="font-size: 0.6rem;"></i>Reserved Stays</span>
                        <strong class="text-warning-emphasis"><?= $reservedRooms ?> Keys</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small py-1">
                        <span><i class="fa-solid fa-circle text-danger me-1" style="font-size: 0.6rem;"></i>Occupied Rooms</span>
                        <strong class="text-danger"><?= $occupiedRooms ?> Keys</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small py-1">
                        <span><i class="fa-solid fa-circle text-secondary me-1" style="font-size: 0.6rem;"></i>Housekeeping (Dirty)</span>
                        <strong class="text-secondary"><?= $dirtyRooms ?> Keys</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Quick Walk-In Check-In -->
<div class="modal fade" id="dashboardWalkinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-key text-success me-2"></i>Walk-In Guest Check-In: <span id="modalRoomTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="checkinRoomId">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Guest Full Name *</label>
                    <input type="text" id="checkinGuestName" class="form-control" placeholder="e.g. Richard Hendricks" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Phone Number *</label>
                        <input type="text" id="checkinPhone" class="form-control" placeholder="+94 77 123 4567" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">National ID / Passport</label>
                        <input type="text" id="checkinIdNumber" class="form-control" placeholder="NIC or Passport No.">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Stay Duration (Nights)</label>
                        <input type="number" id="checkinStayDays" class="form-control" value="1" min="1" max="365">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Advance Deposit (<?= APP_CURRENCY ?>)</label>
                        <input type="number" id="checkinDeposit" class="form-control" value="0.00" step="100">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="submitWalkinCheckin()">
                    <i class="fa-solid fa-check me-1"></i> Issue Key & Check-In
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let walkinModalInstance = null;

function openWalkinCheckin(roomId, roomNumber, rate) {
    document.getElementById('checkinRoomId').value = roomId;
    document.getElementById('modalRoomTitle').innerText = roomNumber + ' (' + '<?= APP_CURRENCY ?>' + rate + '/night)';
    document.getElementById('checkinGuestName').value = '';
    document.getElementById('checkinPhone').value = '';
    document.getElementById('checkinIdNumber').value = '';
    document.getElementById('checkinStayDays').value = '1';
    document.getElementById('checkinDeposit').value = '0.00';

    const modalEl = document.getElementById('dashboardWalkinModal');
    walkinModalInstance = new bootstrap.Modal(modalEl);
    walkinModalInstance.show();
}

function submitWalkinCheckin() {
    const roomId = document.getElementById('checkinRoomId').value;
    const name = document.getElementById('checkinGuestName').value.trim();
    const phone = document.getElementById('checkinPhone').value.trim();
    const idNum = document.getElementById('checkinIdNumber').value.trim();
    const days = document.getElementById('checkinStayDays').value;
    const deposit = document.getElementById('checkinDeposit').value;

    if (!name || !phone) {
        alert('Please provide Guest Full Name and Phone Number.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'quick_checkin');
    formData.append('room_id', roomId);
    formData.append('guest_name', name);
    formData.append('phone', phone);
    formData.append('id_number', idNum);
    formData.append('stay_days', days);
    formData.append('deposit', deposit);

    fetch('api/rooms.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Guest successfully checked in!');
            location.reload();
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => {
        alert('Request failed: ' + err);
    });
}

function checkinReservation(reservationId, bookingRef, roomNumber) {
    if (!confirm('Confirm check-in for ' + bookingRef + ' into ' + roomNumber + '?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'checkin_reservation');
    formData.append('reservation_id', reservationId);

    fetch('api/rooms.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => {
        alert('Request failed: ' + err);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
