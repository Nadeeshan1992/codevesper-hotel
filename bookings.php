<?php
/**
 * Reservations & Bookings Management
 */
$pageTitle = 'Reservations & Bookings';
$pageSubtitle = 'Manage Advance Bookings, Guest Registrations & Arrival Calendar';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Handle new advance booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $guestName = sanitize($_POST['guest_name'] ?? '');
    $phone = formatPhoneNumber(sanitize($_POST['phone'] ?? ''));
    $email = sanitize($_POST['email'] ?? '');
    $roomId = (int)$_POST['room_id'];
    $checkIn = $_POST['check_in_date'];
    $checkOut = $_POST['check_out_date'];
    $adults = (int)($_POST['adults'] ?? 2);
    $deposit = (float)($_POST['advance_deposit'] ?? 0.00);

    try {
        $db->beginTransaction();

        // 1. Create or find Guest
        $gStmt = $db->prepare("INSERT INTO guests (full_name, phone, email, id_type, id_number) VALUES (?, ?, ?, 'National ID', ?)");
        $gStmt->execute([$guestName, $phone, $email, 'ID-' . rand(10000, 99999)]);
        $guestId = (int)$db->lastInsertId();

        // 2. Calculate Room Cost
        $room = $db->query("SELECT * FROM rooms WHERE id = $roomId")->fetch();
        $days = max(1, (strtotime($checkOut) - strtotime($checkIn)) / (24 * 3600));
        $totalCost = $days * (float)$room['daily_rate'];

        // 3. Create Reservation
        $ref = 'BK-' . rand(1000, 9999);
        $resStmt = $db->prepare("
            INSERT INTO reservations (booking_ref, guest_id, room_id, check_in_date, check_out_date, adults, total_room_cost, advance_deposit, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
        ");
        $resStmt->execute([$ref, $guestId, $roomId, $checkIn, $checkOut, $adults, $totalCost, $deposit, $_SESSION['user_id']]);

        // 4. Update room status to reserved if arrival is today
        if ($checkIn === date('Y-m-d')) {
            $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'vacant'")->execute([$roomId]);
        }

        logAudit('Reservations', 'Booking Created', "Created booking $ref for guest $guestName in {$room['room_number']}");

        $db->commit();
        $bookSuccess = "Reservation $ref successfully booked!";
    } catch (Exception $e) {
        $db->rollBack();
        $bookError = "Failed to create reservation: " . $e->getMessage();
    }
}

// Fetch all reservations
$reservations = $db->query("
    SELECT res.*, r.room_number, rt.name as type_name, g.full_name as guest_name, g.phone as guest_phone, u.username as staff_user
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN guests g ON res.guest_id = g.id
    LEFT JOIN users u ON res.created_by = u.id
    ORDER BY res.id DESC
")->fetchAll();

// Available rooms for booking
$availableRooms = $db->query("
    SELECT r.*, rt.name as type_name 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    ORDER BY r.floor ASC, r.room_number ASC
")->fetchAll();
?>

<?php if (!empty($bookSuccess)): ?>
    <div class="alert alert-success py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span><?= htmlspecialchars($bookSuccess) ?></span>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Hotel Reservation Records</h5>
        <span class="text-muted small">Manage upcoming arrivals across all hotel rooms</span>
    </div>
    <button class="btn btn-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#newBookingModal">
        <i class="fa-solid fa-plus me-1"></i> New Advance Reservation
    </button>
</div>

<div class="card-custom">
    <div class="card-header-clean">
        <h5 class="fw-bold mb-0">All Booking Entries</h5>
    </div>
    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-init">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Booking Ref</th>
                        <th>Guest Details</th>
                        <th>Room Allocated</th>
                        <th>Dates (In / Out)</th>
                        <th>Total Cost</th>
                        <th>Advance Paid</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): 
                        $badge = 'warning';
                        if ($res['status'] === 'checked_in') $badge = 'danger';
                        elseif ($res['status'] === 'checked_out') $badge = 'secondary';
                        elseif ($res['status'] === 'confirmed') $badge = 'primary';
                    ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($res['booking_ref']) ?></span>
                            <div class="text-muted" style="font-size: 0.7rem;"><?= formatDate($res['created_at']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($res['guest_name']) ?></div>
                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars(formatPhoneNumber($res['guest_phone'])) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($res['room_number']) ?></span>
                            <div class="text-muted small"><?= htmlspecialchars($res['type_name']) ?></div>
                        </td>
                        <td>
                            <div class="small"><strong>In:</strong> <?= formatDate($res['check_in_date']) ?></div>
                            <div class="small text-muted"><strong>Out:</strong> <?= formatDate($res['check_out_date']) ?></div>
                        </td>
                        <td class="fw-bold"><?= formatMoney($res['total_room_cost']) ?></td>
                        <td>
                            <span class="badge bg-success-subtle text-success"><?= formatMoney($res['advance_deposit']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> text-capitalize"><?= str_replace('_', ' ', $res['status']) ?></span>
                        </td>
                        <td class="text-end">
                            <?php if ($res['status'] === 'confirmed'): ?>
                                <a href="rooms.php" class="btn btn-sm btn-outline-success py-1 px-2" title="Check-In Guest">
                                    <i class="fa-solid fa-key"></i> Check-In
                                </a>
                            <?php elseif ($res['status'] === 'checked_in'): ?>
                                <a href="billing.php?room_id=<?= $res['room_id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Folio">
                                    <i class="fa-solid fa-receipt"></i> Folio
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Reservation Modal -->
<div class="modal fade" id="newBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Book Advance Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="bookings.php">
                <input type="hidden" name="create_booking" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Guest Full Name *</label>
                        <input type="text" name="guest_name" class="form-control" required placeholder="e.g. Richard Hendricks">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Phone Number *</label>
                            <input type="text" name="phone" class="form-control" required placeholder="+94 77 123 4567">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="guest@example.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Room *</label>
                        <select name="room_id" class="form-select" required>
                            <?php foreach ($availableRooms as $ar): ?>
                                <option value="<?= $ar['id'] ?>">
                                    <?= htmlspecialchars($ar['room_number']) ?> &bull; <?= htmlspecialchars($ar['type_name']) ?> ($<?= $ar['daily_rate'] ?>/night) [<?= ucfirst($ar['status']) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Check-In Date *</label>
                            <input type="date" name="check_in_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Check-Out Date *</label>
                            <input type="date" name="check_out_date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Adults</label>
                            <input type="number" name="adults" class="form-control" value="2" min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Advance Deposit (<?= APP_CURRENCY ?>)</label>
                            <input type="number" name="advance_deposit" class="form-control" value="5000.00" step="500">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Confirm Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
