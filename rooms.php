<?php
/**
 * Dynamic Rooms & Suites Management
 * Scalable for Any Property Size & Floor Layout
 */
$pageTitle = 'Rooms & Suites Management';
$pageSubtitle = 'Visual Floor Rack, Real-time Occupancy, Housekeeping & Dynamic Room Inventory';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch all rooms with types and active guest stay
$rooms = $db->query("
    SELECT r.*, rt.name as type_name, rt.code as type_code, rt.capacity, rt.amenities,
           ci.id as check_in_id, ci.check_in_time, ci.deposit_paid,
           g.full_name as guest_name, g.phone as guest_phone,
           res.check_out_date, res.booking_ref
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN check_ins ci ON (ci.room_id = r.id AND ci.status = 'active')
    LEFT JOIN guests g ON ci.guest_id = g.id
    LEFT JOIN reservations res ON ci.reservation_id = res.id
    ORDER BY r.floor ASC, r.room_number ASC
")->fetchAll();

// Fetch all room types
$roomTypes = $db->query("SELECT * FROM room_types ORDER BY base_price ASC")->fetchAll();

// Count stats dynamically
$stats = [
    'total' => count($rooms),
    'vacant' => 0,
    'occupied' => 0,
    'reserved' => 0,
    'dirty' => 0,
    'maintenance' => 0
];
foreach ($rooms as $r) {
    if (isset($stats[$r['status']])) {
        $stats[$r['status']]++;
    }
}

// Group rooms dynamically by floor
$floors = [];
foreach ($rooms as $r) {
    $fNum = (int)$r['floor'];
    $floorLabel = ($fNum === 1) ? 'Ground Floor (Level 1)' : 'Floor Level ' . $fNum;
    $floors[$floorLabel][] = $r;
}
?>

<!-- Dynamic Status KPI Bar -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card-custom p-3 text-center border-start border-4 border-primary">
            <span class="text-muted small fw-bold">TOTAL ROOMS</span>
            <h3 class="fw-bold my-1 text-primary"><?= $stats['total'] ?></h3>
            <span class="small text-muted">Scalable Inventory</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card-custom p-3 text-center border-start border-4 border-success">
            <span class="text-muted small fw-bold text-success">VACANT & CLEAN</span>
            <h3 class="fw-bold my-1 text-success"><?= $stats['vacant'] ?></h3>
            <span class="small text-muted">Ready for Check-In</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card-custom p-3 text-center border-start border-4 border-danger">
            <span class="text-muted small fw-bold text-danger">OCCUPIED</span>
            <h3 class="fw-bold my-1 text-danger"><?= $stats['occupied'] ?></h3>
            <span class="small text-muted">In-House Guests</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card-custom p-3 text-center border-start border-4 border-warning">
            <span class="text-muted small fw-bold text-warning-emphasis">RESERVED</span>
            <h3 class="fw-bold my-1 text-warning"><?= $stats['reserved'] ?></h3>
            <span class="small text-muted">Upcoming Stays</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card-custom p-3 text-center border-start border-4 border-secondary">
            <span class="text-muted small fw-bold text-secondary">DIRTY / CLEANING</span>
            <h3 class="fw-bold my-1 text-secondary"><?= $stats['dirty'] ?></h3>
            <span class="small text-muted">Housekeeping Alert</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card-custom p-3 text-center border-start border-4 border-dark">
            <span class="text-muted small fw-bold text-dark">MAINTENANCE</span>
            <h3 class="fw-bold my-1 text-dark"><?= $stats['maintenance'] ?></h3>
            <span class="small text-muted">Out of Order</span>
        </div>
    </div>
</div>

<!-- Controls & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <!-- Filter Buttons -->
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-dark btn-sm active" onclick="filterRooms('all', this)">All (<?= $stats['total'] ?>)</button>
        <button type="button" class="btn btn-outline-success btn-sm" onclick="filterRooms('vacant', this)">Vacant (<?= $stats['vacant'] ?>)</button>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="filterRooms('occupied', this)">Occupied (<?= $stats['occupied'] ?>)</button>
        <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterRooms('reserved', this)">Reserved (<?= $stats['reserved'] ?>)</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="filterRooms('dirty', this)">Dirty (<?= $stats['dirty'] ?>)</button>
        <button type="button" class="btn btn-outline-dark btn-sm" onclick="filterRooms('maintenance', this)">Maintenance (<?= $stats['maintenance'] ?>)</button>
    </div>

    <!-- Management Action Buttons -->
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-success btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#newRoomModal">
            <i class="fa-solid fa-plus me-1"></i> Add New Room
        </button>
        <button class="btn btn-outline-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#roomTypesModal">
            <i class="fa-solid fa-sliders me-1"></i> Room Types & Tiers
        </button>
        <a href="bookings.php" class="btn btn-outline-dark btn-sm px-3 rounded-pill">
            <i class="fa-solid fa-calendar-check me-1"></i> Reservations
        </a>
    </div>
</div>

<!-- Dynamic Floor Display -->
<?php if (empty($rooms)): ?>
    <div class="card-custom p-5 text-center text-muted">
        <i class="fa-solid fa-door-open fa-4x text-secondary opacity-50 mb-3"></i>
        <h4 class="fw-bold text-dark">No Rooms Created Yet</h4>
        <p class="mb-3">Start setting up your hotel capacity by adding rooms and assigning them to floors.</p>
        <div>
            <button class="btn btn-primary px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#newRoomModal">
                <i class="fa-solid fa-plus me-1"></i> Add Your First Room
            </button>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($floors as $floorName => $floorRooms): ?>
        <div class="mb-5 floor-group">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <i class="fa-solid fa-layer-group text-primary"></i>
                <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($floorName) ?></h5>
                <span class="badge bg-secondary-subtle text-dark ms-2"><?= count($floorRooms) ?> Rooms on this floor</span>
            </div>

            <div class="row g-4">
                <?php foreach ($floorRooms as $room): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 room-col status-<?= $room['status'] ?>">
                        <div class="room-card status-<?= $room['status'] ?> h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark">
                                            <i class="fa-solid fa-door-closed text-secondary me-1"></i> <?= htmlspecialchars($room['room_number']) ?>
                                        </h5>
                                        <span class="text-muted small"><?= htmlspecialchars($room['type_name']) ?></span>
                                    </div>
                                    <div>
                                        <span class="fs-6 fw-bold text-primary"><?= formatMoney($room['daily_rate']) ?></span>
                                        <div class="small text-muted text-end">/ night</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <?= getRoomStatusBadge($room['status']) ?>
                                </div>

                                <!-- Occupant / Stay Details -->
                                <?php if ($room['status'] === 'occupied' && !empty($room['guest_name'])): ?>
                                    <div class="bg-danger bg-opacity-10 border border-danger-subtle rounded-2 p-2 small mb-3">
                                        <div class="fw-bold text-danger"><i class="fa-solid fa-user me-1"></i> <?= htmlspecialchars($room['guest_name']) ?></div>
                                        <div class="text-muted"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($room['guest_phone']) ?></div>
                                        <div class="text-muted"><i class="fa-regular fa-calendar-days me-1"></i> Check-out: <?= formatDate($room['check_out_date']) ?></div>
                                    </div>
                                <?php elseif ($room['status'] === 'reserved'): ?>
                                    <div class="bg-warning bg-opacity-10 border border-warning-subtle rounded-2 p-2 small mb-3">
                                        <div class="fw-bold text-warning-emphasis"><i class="fa-solid fa-calendar-check me-1"></i> Reserved Stay</div>
                                        <div class="text-muted">Deposit: <?= formatMoney($room['deposit_paid'] ?? 0) ?></div>
                                    </div>
                                <?php elseif ($room['status'] === 'dirty'): ?>
                                    <div class="bg-secondary bg-opacity-10 border border-secondary-subtle rounded-2 p-2 small mb-3">
                                        <div class="fw-bold text-secondary"><i class="fa-solid fa-broom me-1"></i> Housekeeping Turnaround</div>
                                        <div class="text-muted">Needs cleaning and fresh linen</div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small mb-3">
                                        <i class="fa-solid fa-users me-1"></i> Max <?= $room['capacity'] ?> Guests &bull; <?= htmlspecialchars($room['amenities'] ?? 'AC, Wi-Fi') ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Action Buttons -->
                            <div class="pt-2 border-top d-flex flex-wrap gap-1 align-items-center">
                                <?php if ($room['status'] === 'vacant'): ?>
                                    <button class="btn btn-sm btn-success flex-grow-1" onclick="openCheckinModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>', <?= $room['daily_rate'] ?>)">
                                        <i class="fa-solid fa-key me-1"></i> Check-In
                                    </button>
                                <?php elseif ($room['status'] === 'occupied'): ?>
                                    <a href="billing.php?room_id=<?= $room['id'] ?>" class="btn btn-sm btn-danger flex-grow-1">
                                        <i class="fa-solid fa-receipt me-1"></i> Folio & Check-Out
                                    </a>
                                    <a href="pos.php" class="btn btn-sm btn-outline-primary" title="Order Room Service">
                                        <i class="fa-solid fa-utensils"></i>
                                    </a>
                                <?php elseif ($room['status'] === 'dirty'): ?>
                                    <button class="btn btn-sm btn-secondary flex-grow-1" onclick="quickUpdateStatus(<?= $room['id'] ?>, 'vacant')">
                                        <i class="fa-solid fa-check-double me-1"></i> Mark Clean
                                    </button>
                                <?php elseif ($room['status'] === 'maintenance'): ?>
                                    <button class="btn btn-sm btn-dark flex-grow-1" onclick="quickUpdateStatus(<?= $room['id'] ?>, 'vacant')">
                                        <i class="fa-solid fa-wrench me-1"></i> Release Maint.
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="openCheckinModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>', <?= $room['daily_rate'] ?>)">
                                        <i class="fa-solid fa-key me-1"></i> Check-In
                                    </button>
                                <?php endif; ?>

                                <!-- Edit Room Modal Button -->
                                <button class="btn btn-sm btn-outline-primary" title="Edit Room" onclick="openEditRoomModal(<?= htmlspecialchars(json_encode($room)) ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <!-- Status switch dropdown button -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-gear"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item small" href="#" onclick="quickUpdateStatus(<?= $room['id'] ?>, 'vacant')"><i class="fa-solid fa-circle-check text-success me-2"></i> Set Vacant & Clean</a></li>
                                        <li><a class="dropdown-item small" href="#" onclick="quickUpdateStatus(<?= $room['id'] ?>, 'dirty')"><i class="fa-solid fa-broom text-secondary me-2"></i> Set Dirty / Cleaning</a></li>
                                        <li><a class="dropdown-item small" href="#" onclick="quickUpdateStatus(<?= $room['id'] ?>, 'maintenance')"><i class="fa-solid fa-screwdriver-wrench text-dark me-2"></i> Set Maintenance</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item small text-danger" href="#" onclick="deleteRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_number']) ?>')"><i class="fa-solid fa-trash me-2"></i> Delete Room</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- 1. Add New Room Modal -->
<div class="modal fade" id="newRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-door-open text-success me-2"></i>Add New Hotel Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Room Number / Name *</label>
                    <input type="text" id="newRoomNumber" class="form-control" placeholder="e.g. Room 401 or Villa 5" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Room Tier / Type *</label>
                        <select id="newRoomTypeId" class="form-select" onchange="autoFillRate(this)">
                            <?php foreach ($roomTypes as $rt): ?>
                                <option value="<?= $rt['id'] ?>" data-rate="<?= $rt['base_price'] ?>">
                                    <?= htmlspecialchars($rt['name']) ?> (<?= formatMoney($rt['base_price']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Floor Level *</label>
                        <input type="number" id="newRoomFloor" class="form-control" value="1" min="1" max="100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Daily Rate (<?= CURRENCY_SYMBOL ?> / Night) *</label>
                    <input type="number" id="newRoomRate" class="form-control" value="120.00" step="5" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Amenities / Room Notes</label>
                    <input type="text" id="newRoomNotes" class="form-control" placeholder="e.g. Balcony with sea view, mini-bar, jacuzzi">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="submitCreateRoom()">
                    <i class="fa-solid fa-check me-1"></i> Add Room to Inventory
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editRoomId">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Room Number / Name *</label>
                    <input type="text" id="editRoomNumber" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Room Tier / Type *</label>
                        <select id="editRoomTypeId" class="form-select">
                            <?php foreach ($roomTypes as $rt): ?>
                                <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Floor Level *</label>
                        <input type="number" id="editRoomFloor" class="form-control" min="1">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Daily Rate (<?= CURRENCY_SYMBOL ?> / Night) *</label>
                    <input type="number" id="editRoomRate" class="form-control" step="5" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Amenities / Notes</label>
                    <input type="text" id="editRoomNotes" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitUpdateRoom()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- 3. Room Types & Pricing Modal -->
<div class="modal fade" id="roomTypesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-sliders text-primary me-2"></i>Room Types & Pricing Tiers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Tier Name</th>
                                <th>Code</th>
                                <th>Base Rate</th>
                                <th>Capacity</th>
                                <th>Amenities</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roomTypes as $rt): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($rt['name']) ?></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($rt['code']) ?></span></td>
                                    <td class="text-primary fw-bold"><?= formatMoney($rt['base_price']) ?></td>
                                    <td><?= $rt['capacity'] ?> Guests</td>
                                    <td class="small text-muted"><?= htmlspecialchars($rt['amenities'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-plus me-1 text-success"></i>Create Custom Room Tier</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" id="newTypeName" class="form-control form-control-sm" placeholder="e.g. Honeymoon Villa">
                        </div>
                        <div class="col-3">
                            <input type="text" id="newTypeCode" class="form-control form-control-sm" placeholder="CODE (e.g. HNY-V)">
                        </div>
                        <div class="col-3">
                            <input type="number" id="newTypePrice" class="form-control form-control-sm" placeholder="Rate (LKR)">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <input type="number" id="newTypeCapacity" class="form-control form-control-sm" placeholder="Max Guests" value="2">
                        </div>
                        <div class="col-8">
                            <input type="text" id="newTypeAmenities" class="form-control form-control-sm" placeholder="Amenities: AC, Jacuzzi, Balcony">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="submitCreateRoomType()">
                        <i class="fa-solid fa-plus me-1"></i> Save New Room Type
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- 4. Quick Walk-In Check-In Modal -->
<div class="modal fade" id="checkinModal" tabindex="-1" aria-hidden="true">
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
                    <input type="text" id="checkinGuestName" class="form-control" placeholder="e.g. Jonathan Smith" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Phone Number *</label>
                        <input type="text" id="checkinPhone" class="form-control" placeholder="+1 555-0199" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">National ID / Passport</label>
                        <input type="text" id="checkinIdNumber" class="form-control" placeholder="P8812399">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Stay Duration (Nights)</label>
                        <input type="number" id="checkinStayDays" class="form-control" value="1" min="1" max="365">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Advance Deposit (<?= CURRENCY_SYMBOL ?>)</label>
                        <input type="number" id="checkinDeposit" class="form-control" value="0.00" step="10">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="submitQuickCheckin()">
                    <i class="fa-solid fa-check me-1"></i> Issue Key & Check-In
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function filterRooms(status, btn) {
    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.room-col');
    cards.forEach(c => {
        if (status === 'all' || c.classList.contains('status-' + status)) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}

function autoFillRate(selectElem) {
    const opt = selectElem.options[selectElem.selectedIndex];
    const rate = opt.getAttribute('data-rate');
    if (rate) {
        document.getElementById('newRoomRate').value = parseFloat(rate).toFixed(2);
    }
}

function submitCreateRoom() {
    const num = document.getElementById('newRoomNumber').value.trim();
    const typeId = document.getElementById('newRoomTypeId').value;
    const floor = document.getElementById('newRoomFloor').value;
    const rate = document.getElementById('newRoomRate').value;
    const notes = document.getElementById('newRoomNotes').value.trim();

    if (!num || !rate || parseFloat(rate) <= 0) {
        Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Room Number and positive Daily Rate are required.' });
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create_room');
    formData.append('room_number', num);
    formData.append('room_type_id', typeId);
    formData.append('floor', floor);
    formData.append('daily_rate', rate);
    formData.append('notes', notes);

    fetch('api/rooms.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Room Added!', text: data.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    });
}

function openEditRoomModal(room) {
    document.getElementById('editRoomId').value = room.id;
    document.getElementById('editRoomNumber').value = room.room_number;
    document.getElementById('editRoomTypeId').value = room.room_type_id;
    document.getElementById('editRoomFloor').value = room.floor;
    document.getElementById('editRoomRate').value = room.daily_rate;
    document.getElementById('editRoomNotes').value = room.notes || '';
    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

function submitUpdateRoom() {
    const id = document.getElementById('editRoomId').value;
    const num = document.getElementById('editRoomNumber').value.trim();
    const typeId = document.getElementById('editRoomTypeId').value;
    const floor = document.getElementById('editRoomFloor').value;
    const rate = document.getElementById('editRoomRate').value;
    const notes = document.getElementById('editRoomNotes').value.trim();

    const formData = new FormData();
    formData.append('action', 'update_room');
    formData.append('room_id', id);
    formData.append('room_number', num);
    formData.append('room_type_id', typeId);
    formData.append('floor', floor);
    formData.append('daily_rate', rate);
    formData.append('notes', notes);

    fetch('api/rooms.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Updated!', text: data.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: data.message });
        }
    });
}

function deleteRoom(roomId, roomNumber) {
    Swal.fire({
        title: 'Delete ' + roomNumber + '?',
        text: 'Are you sure you want to remove this room from inventory? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Delete Room'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete_room');
            formData.append('room_id', roomId);

            fetch('api/rooms.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Deleted', text: data.message }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Cannot Delete', text: data.message });
                }
            });
        }
    });
}

function submitCreateRoomType() {
    const name = document.getElementById('newTypeName').value.trim();
    const code = document.getElementById('newTypeCode').value.trim();
    const price = document.getElementById('newTypePrice').value;
    const cap = document.getElementById('newTypeCapacity').value;
    const amenities = document.getElementById('newTypeAmenities').value.trim();

    if (!name || !code || !price) {
        Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Name, Code and Base Price are required.' });
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create_room_type');
    formData.append('name', name);
    formData.append('code', code);
    formData.append('base_price', price);
    formData.append('capacity', cap);
    formData.append('amenities', amenities);

    fetch('api/rooms.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Room Type Created!', text: data.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    });
}

function openCheckinModal(roomId, roomNumber, rate) {
    document.getElementById('checkinRoomId').value = roomId;
    document.getElementById('modalRoomTitle').innerText = roomNumber + ' (' + '<?= CURRENCY_SYMBOL ?>' + rate + '/night)';
    new bootstrap.Modal(document.getElementById('checkinModal')).show();
}

function submitQuickCheckin() {
    const roomId = document.getElementById('checkinRoomId').value;
    const name = document.getElementById('checkinGuestName').value.trim();
    const phone = document.getElementById('checkinPhone').value.trim();
    const idNum = document.getElementById('checkinIdNumber').value.trim();
    const days = document.getElementById('checkinStayDays').value;
    const deposit = document.getElementById('checkinDeposit').value;

    if (!name || !phone) {
        Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Guest Name and Phone are required.' });
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

    fetch('api/rooms.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Check-In Complete!', text: data.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Check-In Failed', text: data.message });
        }
    });
}

function quickUpdateStatus(roomId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('room_id', roomId);
    formData.append('status', newStatus);

    fetch('api/rooms.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 1500 }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
