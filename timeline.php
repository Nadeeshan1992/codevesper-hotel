<?php
/**
 * Room Reservation Matrix & Tape Chart
 * CodeVesper Hotel & Restaurant Management Suite
 */
$pageTitle = 'Room Tape Chart & Reservation Matrix';
$pageSubtitle = 'Visual timeline & reservation grid for large multi-floor room inventories';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// 1. Time Range Configuration
$rangeDays = isset($_GET['range']) && in_array((int)$_GET['range'], [7, 14, 30]) ? (int)$_GET['range'] : 14;
$startDateParam = isset($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$selectedFloor = isset($_GET['floor']) && $_GET['floor'] !== 'all' ? (int)$_GET['floor'] : 'all';

$startDate = new DateTime($startDateParam);
$endDate = clone $startDate;
$endDate->modify('+' . ($rangeDays - 1) . ' days');

// Build date list
$dates = [];
$current = clone $startDate;
for ($i = 0; $i < $rangeDays; $i++) {
    $dates[] = [
        'date'     => $current->format('Y-m-d'),
        'day_num'  => $current->format('d'),
        'day_name' => $current->format('D'),
        'month'    => $current->format('M'),
        'is_today' => ($current->format('Y-m-d') === date('Y-m-d')),
        'is_weekend' => in_array($current->format('N'), [6, 7])
    ];
    $current->modify('+1 day');
}

// 2. Fetch Rooms with Type information
$roomQuery = "
    SELECT r.id, r.room_number, r.floor, r.status, r.daily_rate,
           rt.name as type_name, rt.code as type_code, rt.capacity, rt.amenities
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
";
$params = [];
if ($selectedFloor !== 'all') {
    $roomQuery .= " WHERE r.floor = ?";
    $params[] = $selectedFloor;
}
$roomQuery .= " ORDER BY r.floor ASC, r.room_number ASC";

$stmt = $db->prepare($roomQuery);
$stmt->execute($params);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct floors for filter pills
$floors = $db->query("SELECT DISTINCT floor FROM rooms ORDER BY floor ASC")->fetchAll(PDO::FETCH_COLUMN);

// Distinct room types
$roomTypes = $db->query("SELECT id, name, code FROM room_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Reservations overlapping the current date range
$resQuery = "
    SELECT res.*, g.full_name as guest_name, g.phone as guest_phone, g.email as guest_email,
           r.room_number, rt.name as type_name
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN guests g ON res.guest_id = g.id
    WHERE res.status != 'cancelled'
      AND res.check_in_date <= ?
      AND res.check_out_date >= ?
    ORDER BY res.check_in_date ASC
";
$resStmt = $db->prepare($resQuery);
$resStmt->execute([$endDate->format('Y-m-d'), $startDate->format('Y-m-d')]);
$allReservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);

// Index reservations by room_id and date for quick matrix lookup
$matrixBookings = [];
$occupancyPerDate = [];

foreach ($dates as $d) {
    $occupancyPerDate[$d['date']] = 0;
}

foreach ($allReservations as $res) {
    $rId = $res['room_id'];
    $checkIn = new DateTime($res['check_in_date']);
    $checkOut = new DateTime($res['check_out_date']);
    
    // Each date from checkIn to day before checkOut is an occupied night
    $stayNight = clone $checkIn;
    while ($stayNight < $checkOut) {
        $nightStr = $stayNight->format('Y-m-d');
        if (isset($occupancyPerDate[$nightStr])) {
            $occupancyPerDate[$nightStr]++;
            $matrixBookings[$rId][$nightStr] = $res;
        }
        $stayNight->modify('+1 day');
    }
}

// Navigation links
$prevStart = clone $startDate;
$prevStart->modify('-' . $rangeDays . ' days');
$nextStart = clone $startDate;
$nextStart->modify('+' . $rangeDays . ' days');
?>

<style>
/* Modern Hotel Tape Chart & Matrix Styles */
.timeline-toolbar {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.timeline-container {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0,0,0,0.04);
}

.matrix-scroll-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    position: relative;
    max-width: 100%;
}

.matrix-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    min-width: <?= 260 + ($rangeDays * 110) ?>px;
}

/* Sticky Room Column */
.matrix-room-col {
    position: sticky;
    left: 0;
    z-index: 20;
    background: #ffffff;
    width: 250px;
    min-width: 250px;
    max-width: 250px;
    border-right: 2px solid #e2e8f0;
    padding: 12px 16px;
    box-shadow: 4px 0 10px rgba(0,0,0,0.03);
}

/* Sticky Header Row */
.matrix-header-cell {
    position: sticky;
    top: 0;
    z-index: 15;
    background: #f8fafc;
    border-bottom: 2px solid #cbd5e1;
    border-right: 1px solid #e2e8f0;
    text-align: center;
    padding: 10px 4px;
    font-size: 0.8rem;
    min-width: 105px;
    width: 105px;
}

.matrix-header-cell.corner-cell {
    position: sticky;
    top: 0;
    left: 0;
    z-index: 25;
    background: #0f172a;
    color: #ffffff;
    border-right: 2px solid #334155;
    border-bottom: 2px solid #334155;
    text-align: left;
    padding: 14px 18px;
}

.matrix-header-cell.today-col {
    background: #eff6ff;
    border-left: 2px solid #3b82f6;
    border-right: 2px solid #3b82f6;
}

.matrix-header-cell.weekend-col {
    background: #f1f5f9;
}

/* Day Occupancy Indicator Pill */
.occ-pill {
    font-size: 0.68rem;
    padding: 2px 6px;
    border-radius: 999px;
    font-weight: 700;
    display: inline-block;
    margin-top: 4px;
}
.occ-high { background: #fee2e2; color: #dc2626; }
.occ-med  { background: #fef3c7; color: #d97706; }
.occ-low  { background: #dcfce7; color: #16a34a; }

/* Grid Body Cells */
.matrix-cell {
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    height: 64px;
    min-height: 64px;
    padding: 4px;
    vertical-align: middle;
    position: relative;
    background: #ffffff;
    transition: background 0.15s ease;
}

.matrix-cell.today-cell {
    background: rgba(59, 130, 246, 0.03);
    border-left: 2px solid #3b82f6;
    border-right: 2px solid #3b82f6;
}

.matrix-cell.weekend-cell {
    background: #fafafa;
}

.matrix-cell:hover {
    background: #f8fafc;
}

/* Available Slot with Quick + Button */
.slot-available {
    width: 100%;
    height: 100%;
    border-radius: 8px;
    border: 1px dashed transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.slot-available:hover {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.08);
    color: #2563eb;
    font-weight: 600;
    font-size: 0.78rem;
}

/* Reservation Stay Pill / Capsule */
.stay-capsule {
    width: 100%;
    height: 52px;
    border-radius: 10px;
    padding: 6px 10px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    font-size: 0.75rem;
    line-height: 1.25;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    border: 1px solid transparent;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    user-select: none;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.stay-capsule:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0,0,0,0.12);
}

.stay-in-house {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
    color: #ffffff;
    border-color: #1d4ed8;
}

.stay-confirmed {
    background: linear-gradient(135deg, #047857 0%, #059669 100%);
    color: #ffffff;
    border-color: #065f46;
}

.stay-checked-out {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    color: #f1f5f9;
    border-color: #334155;
    opacity: 0.85;
}

.stay-guest-name {
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.stay-meta {
    font-size: 0.68rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Room Column Meta */
.room-badge-num {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
.room-badge-type {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}

/* Legend items */
.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}
</style>

<!-- Main Toolbar -->
<div class="timeline-toolbar">
    <div class="row g-3 align-items-center">
        <!-- Date Navigator -->
        <div class="col-12 col-lg-5">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group shadow-sm">
                    <a href="timeline.php?start_date=<?= $prevStart->format('Y-m-d') ?>&range=<?= $rangeDays ?>&floor=<?= $selectedFloor ?>" class="btn btn-outline-secondary btn-sm" title="Previous <?= $rangeDays ?> Days">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <a href="timeline.php?start_date=<?= date('Y-m-d') ?>&range=<?= $rangeDays ?>&floor=<?= $selectedFloor ?>" class="btn btn-primary btn-sm px-3 fw-bold">
                        <i class="fa-solid fa-calendar-day me-1"></i> Today
                    </a>
                    <a href="timeline.php?start_date=<?= $nextStart->format('Y-m-d') ?>&range=<?= $rangeDays ?>&floor=<?= $selectedFloor ?>" class="btn btn-outline-secondary btn-sm" title="Next <?= $rangeDays ?> Days">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                <!-- Custom Start Date Picker -->
                <form method="GET" action="timeline.php" class="d-inline-flex align-items-center gap-1">
                    <input type="hidden" name="range" value="<?= $rangeDays ?>">
                    <input type="hidden" name="floor" value="<?= $selectedFloor ?>">
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $startDate->format('Y-m-d') ?>" onchange="this.form.submit()" style="max-width: 140px;">
                </form>

                <span class="small fw-bold text-dark d-none d-md-inline ms-1">
                    <?= $startDate->format('M d') ?> &ndash; <?= $endDate->format('M d, Y') ?>
                </span>
            </div>
        </div>

        <!-- Range Switcher & Floor Filters -->
        <div class="col-12 col-lg-7">
            <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                <!-- Range Toggle (7, 14, 30 days) -->
                <div class="btn-group btn-group-sm shadow-sm" role="group">
                    <a href="timeline.php?start_date=<?= $startDateParam ?>&range=7&floor=<?= $selectedFloor ?>" class="btn <?= ($rangeDays == 7) ? 'btn-dark' : 'btn-outline-secondary' ?>">7 Days</a>
                    <a href="timeline.php?start_date=<?= $startDateParam ?>&range=14&floor=<?= $selectedFloor ?>" class="btn <?= ($rangeDays == 14) ? 'btn-dark' : 'btn-outline-secondary' ?>">14 Days</a>
                    <a href="timeline.php?start_date=<?= $startDateParam ?>&range=30&floor=<?= $selectedFloor ?>" class="btn <?= ($rangeDays == 30) ? 'btn-dark' : 'btn-outline-secondary' ?>">30 Days</a>
                </div>

                <!-- Floor Dropdown / Filter -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-layer-group text-primary me-1"></i>
                        <span><?= ($selectedFloor === 'all') ? 'All Floors' : 'Floor ' . $selectedFloor ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item <?= ($selectedFloor === 'all') ? 'active' : '' ?>" href="timeline.php?start_date=<?= $startDateParam ?>&range=<?= $rangeDays ?>&floor=all">All Floors (<?= count($rooms) ?> Rooms)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($floors as $fl): ?>
                            <li><a class="dropdown-item <?= ($selectedFloor == $fl) ? 'active' : '' ?>" href="timeline.php?start_date=<?= $startDateParam ?>&range=<?= $rangeDays ?>&floor=<?= $fl ?>">Floor <?= $fl ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Instant Booking Shortcut -->
                <a href="bookings.php" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-plus me-1"></i> New Booking
                </a>
            </div>
        </div>
    </div>

    <!-- Status Legend & Live Counters -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 mt-3 border-top border-light-subtle small">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="text-muted fw-bold">Status:</span>
            <span><span class="legend-dot" style="background: #2563eb;"></span> In-House (Checked In)</span>
            <span><span class="legend-dot" style="background: #059669;"></span> Confirmed Booking</span>
            <span><span class="legend-dot" style="background: #64748b;"></span> Checked Out</span>
            <span><span class="legend-dot" style="background: #f1f5f9; border: 1px dashed #cbd5e1;"></span> Available / Vacant</span>
        </div>
        <div class="text-muted">
            <i class="fa-solid fa-circle-info text-info me-1"></i> Tip: Click on any empty cell to make a booking, or click a stay bar to view reservation folio.
        </div>
    </div>
</div>

<!-- Tape Chart Grid Matrix -->
<div class="timeline-container">
    <div class="matrix-scroll-wrapper">
        <table class="matrix-table">
            <thead>
                <tr>
                    <!-- Top Left Corner Cell -->
                    <th class="matrix-header-cell corner-cell">
                        <div class="fw-bold fs-6">Rooms & Inventory</div>
                        <div class="small text-white-50" style="font-size: 0.7rem;"><?= count($rooms) ?> Active Room Units</div>
                    </th>

                    <!-- Date Columns Header -->
                    <?php foreach ($dates as $d): 
                        $occCount = $occupancyPerDate[$d['date']] ?? 0;
                        $totalR = count($rooms);
                        $occPercent = ($totalR > 0) ? round(($occCount / $totalR) * 100) : 0;
                        $pillClass = ($occPercent >= 80) ? 'occ-high' : (($occPercent >= 40) ? 'occ-med' : 'occ-low');
                    ?>
                        <th class="matrix-header-cell <?= $d['is_today'] ? 'today-col' : '' ?> <?= $d['is_weekend'] ? 'weekend-col' : '' ?>">
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 700;"><?= $d['day_name'] ?></div>
                            <div class="fw-bold <?= $d['is_today'] ? 'text-primary' : 'text-dark' ?>" style="font-size: 1.15rem; line-height: 1.1;">
                                <?= $d['day_num'] ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.65rem;"><?= $d['month'] ?></div>
                            <div class="occ-pill <?= $pillClass ?>" title="<?= $occCount ?> of <?= $totalR ?> rooms booked">
                                <?= $occCount ?>/<?= $totalR ?> (<?= $occPercent ?>%)
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="<?= count($dates) + 1 ?>" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-door-closed fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No rooms found for the selected filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $currentFloorGroup = null;
                    foreach ($rooms as $room): 
                        $rId = $room['id'];
                    ?>
                        <tr>
                            <!-- Room Header Column -->
                            <td class="matrix-room-col">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="room-badge-num">
                                            <?= htmlspecialchars($room['room_number']) ?>
                                        </div>
                                        <div class="room-badge-type">
                                            <?= htmlspecialchars($room['type_name']) ?> &bull; Floor <?= $room['floor'] ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">
                                            <i class="fa-solid fa-user me-1 text-muted"></i><?= $room['capacity'] ?>
                                        </span>
                                        <div class="text-muted" style="font-size: 0.68rem; font-weight: 600;">
                                            <?= formatMoney($room['daily_rate']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Date Grid Cells for this Room -->
                            <?php foreach ($dates as $d): 
                                $dateStr = $d['date'];
                                $hasBooking = isset($matrixBookings[$rId][$dateStr]);
                                $booking = $hasBooking ? $matrixBookings[$rId][$dateStr] : null;
                            ?>
                                <td class="matrix-cell <?= $d['is_today'] ? 'today-cell' : '' ?> <?= $d['is_weekend'] ? 'weekend-cell' : '' ?>">
                                    <?php if ($booking): 
                                        $statusClass = ($booking['status'] === 'checked_in') ? 'stay-in-house' : (($booking['status'] === 'checked_out') ? 'stay-checked-out' : 'stay-confirmed');
                                        $statusLabel = ($booking['status'] === 'checked_in') ? 'In-House' : (($booking['status'] === 'checked_out') ? 'Departed' : 'Confirmed');
                                    ?>
                                        <div class="stay-capsule <?= $statusClass ?>" 
                                             onclick="openReservationModal(<?= htmlspecialchars(json_encode($booking)) ?>)"
                                             title="<?= htmlspecialchars($booking['guest_name']) ?> (<?= $booking['booking_ref'] ?>)&#10;Dates: <?= $booking['check_in_date'] ?> to <?= $booking['check_out_date'] ?>&#10;Status: <?= $statusLabel ?>">
                                            <div class="stay-guest-name">
                                                <i class="fa-solid <?= ($booking['status'] === 'checked_in') ? 'fa-key' : 'fa-calendar-check' ?> me-1 small"></i>
                                                <?= htmlspecialchars($booking['guest_name']) ?>
                                            </div>
                                            <div class="stay-meta">
                                                <span><?= $booking['booking_ref'] ?></span> &bull; 
                                                <span><?= $statusLabel ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Empty Slot -> Quick Booking Link -->
                                        <a href="bookings.php?room_id=<?= $rId ?>&date=<?= $dateStr ?>" class="slot-available" title="Click to book <?= htmlspecialchars($room['room_number']) ?> on <?= $d['day_name'] ?>, <?= $d['day_num'] ?> <?= $d['month'] ?>">
                                            <i class="fa-solid fa-plus me-1"></i> Book
                                        </a>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: View Reservation Details -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary rounded-3 text-white">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="resModalRef">Booking Details</h5>
                        <small class="text-white-50" id="resModalSubtitle">Reservation Folio Information</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted small d-block">Guest Full Name:</span>
                            <strong class="text-dark fs-6" id="resGuestName">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Contact Phone:</span>
                            <strong class="text-dark" id="resGuestPhone">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Room Assigned:</span>
                            <strong class="text-primary" id="resRoomNumber">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Booking Status:</span>
                            <span class="badge" id="resStatusBadge">-</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 text-secondary small mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded">
                            <span class="d-block text-muted">Check-In Date:</span>
                            <strong class="text-dark fs-6" id="resCheckIn">-</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded">
                            <span class="d-block text-muted">Check-Out Date:</span>
                            <strong class="text-dark fs-6" id="resCheckOut">-</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small d-block">Advance Paid:</span>
                            <strong class="text-success fs-5" id="resAdvancePaid">LKR 0.00</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Total Estimated Cost:</span>
                            <strong class="text-dark fs-6" id="resTotalCost">LKR 0.00</strong>
                        </div>
                    </div>
                </div>

                <div id="checkinActionSection" class="mt-4">
                    <!-- Populated dynamically if status is confirmed -->
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Open Reservation Details Modal
function openReservationModal(res) {
    document.getElementById('resModalRef').innerText = res.booking_ref;
    document.getElementById('resModalSubtitle').innerText = 'Room ' + res.room_number + ' • ' + res.type_name;
    document.getElementById('resGuestName').innerText = res.guest_name;
    document.getElementById('resGuestPhone').innerText = res.guest_phone || 'N/A';
    document.getElementById('resRoomNumber').innerText = 'Room ' + res.room_number + ' (' + res.type_name + ')';
    document.getElementById('resCheckIn').innerText = res.check_in_date;
    document.getElementById('resCheckOut').innerText = res.check_out_date;
    document.getElementById('resAdvancePaid').innerText = 'LKR ' + parseFloat(res.advance_deposit || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('resTotalCost').innerText = 'LKR ' + parseFloat(res.total_room_cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2});

    const badge = document.getElementById('resStatusBadge');
    if (res.status === 'checked_in') {
        badge.className = 'badge bg-primary fs-6';
        badge.innerText = 'In-House (Checked In)';
    } else if (res.status === 'checked_out') {
        badge.className = 'badge bg-secondary fs-6';
        badge.innerText = 'Checked Out';
    } else {
        badge.className = 'badge bg-success fs-6';
        badge.innerText = 'Confirmed Arrival';
    }

    const actionSec = document.getElementById('checkinActionSection');
    if (res.status === 'confirmed') {
        actionSec.innerHTML = `
            <button type="button" class="btn btn-primary w-100 py-2 rounded-pill fw-bold" onclick="performCheckin(${res.id}, '${res.booking_ref}', '${res.room_number}')">
                <i class="fa-solid fa-door-open me-2"></i> 1-Click Check-In Guest Now
            </button>
        `;
    } else if (res.status === 'checked_in') {
        actionSec.innerHTML = `
            <a href="billing.php" class="btn btn-outline-primary w-100 py-2 rounded-pill fw-bold">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i> View Guest Folio & Settle Bill
            </a>
        `;
    } else {
        actionSec.innerHTML = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
    modal.show();
}

// 1-Click Check-In Handler
function performCheckin(resId, bookingRef, roomNumber) {
    if (!confirm('Confirm Check-In for ' + bookingRef + ' into Room ' + roomNumber + '?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'checkin_reservation');
    formData.append('reservation_id', resId);

    fetch('api/rooms.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Check-In Successful!',
                text: 'Guest checked in successfully into Room ' + roomNumber,
                confirmButtonColor: '#0284c7'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Could not process check-in', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Connection error: ' + err, 'error');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
