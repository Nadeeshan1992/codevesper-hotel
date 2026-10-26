<?php
/**
 * Consolidated Guest Folio & Invoicing Module
 * Combines Room Stay Charges + Restaurant In-Room Dining + Amenities
 */
$pageTitle = 'Guest Folio & Billing';
$pageSubtitle = 'Consolidated Hotel Room & Restaurant Service Invoicing';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch all occupied rooms with active check-ins
$occupiedRooms = $db->query("
    SELECT r.id as room_id, r.room_number, r.daily_rate,
           ci.id as check_in_id, ci.check_in_time, ci.deposit_paid,
           g.id as guest_id, g.full_name as guest_name, g.phone as guest_phone, g.email as guest_email,
           res.booking_ref, res.check_in_date, res.check_out_date
    FROM rooms r
    JOIN check_ins ci ON (ci.room_id = r.id AND ci.status = 'active')
    JOIN guests g ON ci.guest_id = g.id
    LEFT JOIN reservations res ON ci.reservation_id = res.id
    ORDER BY r.room_number ASC
")->fetchAll();

// Check if specific room requested
$selectedRoomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : ($occupiedRooms[0]['room_id'] ?? 0);

$activeFolio = null;
$restaurantOrders = [];
$nights = 1;
$roomCost = 0.00;
$fnbTotal = 0.00;

if ($selectedRoomId > 0) {
    foreach ($occupiedRooms as $o) {
        if ((int)$o['room_id'] === $selectedRoomId) {
            $activeFolio = $o;
            break;
        }
    }

    if ($activeFolio) {
        // Calculate nights
        $checkinTime = strtotime($activeFolio['check_in_time']);
        $now = time();
        $diff = max(0, $now - $checkinTime);
        $nights = max(1, ceil($diff / (24 * 3600)));
        $roomCost = $nights * (float)$activeFolio['daily_rate'];

        // Get restaurant orders charged to this room
        $stmt = $db->prepare("
            SELECT o.*, u.full_name as waiter_name
            FROM orders o
            LEFT JOIN users u ON o.waiter_id = u.id
            WHERE o.room_id = ? AND o.payment_status = 'billed_to_room'
            ORDER BY o.id ASC
        ");
        $stmt->execute([$selectedRoomId]);
        $restaurantOrders = $stmt->fetchAll();

        foreach ($restaurantOrders as $ro) {
            $fnbTotal += (float)$ro['total_amount'];
        }
    }
}

// Invoices History
$invoicesHistory = $db->query("
    SELECT inv.*, g.full_name as guest_name, r.room_number, u.username as staff_user
    FROM invoices inv
    JOIN guests g ON inv.guest_id = g.id
    LEFT JOIN rooms r ON inv.room_id = r.id
    LEFT JOIN users u ON inv.generated_by = u.id
    ORDER BY inv.id DESC
    LIMIT 10
")->fetchAll();
?>

<div class="row g-4">
    <!-- Left Column: Room Selector & Active Folio Breakdown -->
    <div class="col-12 col-lg-7">
        <div class="card-custom mb-4">
            <div class="card-header-clean d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Active Guest Folio Settle
                </h5>
                <span class="badge bg-primary px-3 py-1"><?= count($occupiedRooms) ?> In-House Folios</span>
            </div>

            <div class="p-4">
                <!-- Select Room Dropdown -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Select In-House Room:</label>
                    <select class="form-select" onchange="location.href='billing.php?room_id=' + this.value">
                        <?php if (empty($occupiedRooms)): ?>
                            <option value="">No occupied rooms currently found</option>
                        <?php else: ?>
                            <?php foreach ($occupiedRooms as $or): ?>
                                <option value="<?= $or['room_id'] ?>" <?= $or['room_id'] == $selectedRoomId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($or['room_number']) ?> &mdash; <?= htmlspecialchars($or['guest_name']) ?> (Ref: <?= htmlspecialchars($or['booking_ref'] ?? 'Direct') ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($activeFolio): 
                    $subtotal = $roomCost + $fnbTotal;
                    $tax = round($subtotal * 0.10, 2);
                    $total = $subtotal + $tax;
                    $deposit = (float)$activeFolio['deposit_paid'];
                    $balanceDue = max(0, $total - $deposit);
                ?>
                    <!-- Guest & Stay Information Strip -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="row g-3">
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small">Guest Name:</span>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($activeFolio['guest_name']) ?></div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small">Phone:</span>
                                <div class="fw-bold text-dark"><?= htmlspecialchars(formatPhoneNumber($activeFolio['guest_phone'])) ?></div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small">Check-In Time:</span>
                                <div class="fw-bold text-dark"><?= formatDateTime($activeFolio['check_in_time']) ?></div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted small">Duration:</span>
                                <div class="fw-bold text-primary"><?= $nights ?> Night(s) Stayed</div>
                            </div>
                        </div>
                    </div>

                    <!-- Itemized Folio Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Service Description</th>
                                    <th>Units / Ref</th>
                                    <th>Unit Price</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Room charges -->
                                <tr>
                                    <td>
                                        <strong>Room Lodging Charges</strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($activeFolio['room_number']) ?> accommodation</small>
                                    </td>
                                    <td><?= $nights ?> Nights</td>
                                    <td><?= formatMoney($activeFolio['daily_rate']) ?></td>
                                    <td class="text-end fw-bold"><?= formatMoney($roomCost) ?></td>
                                </tr>

                                <!-- Restaurant charges -->
                                <?php if (!empty($restaurantOrders)): ?>
                                    <?php foreach ($restaurantOrders as $ro): ?>
                                        <tr>
                                            <td>
                                                <i class="fa-solid fa-utensils text-success me-1"></i> <strong>Restaurant Order: <?= htmlspecialchars($ro['order_number']) ?></strong><br>
                                                <small class="text-muted">In-Room Dining &bull; Waiter: <?= htmlspecialchars($ro['waiter_name'] ?? 'Staff') ?></small>
                                            </td>
                                            <td>Food & Drinks</td>
                                            <td><?= formatMoney($ro['subtotal']) ?></td>
                                            <td class="text-end fw-bold"><?= formatMoney($ro['total_amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-muted small py-2 bg-light">
                                            <i class="fa-solid fa-circle-info me-1"></i> No restaurant orders billed to this room.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Summary & Settlement Button -->
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="row g-2 justify-content-end text-end">
                            <div class="col-6 col-sm-4 text-muted small">Lodging Subtotal:</div>
                            <div class="col-6 col-sm-3 fw-bold"><?= formatMoney($roomCost) ?></div>
                        </div>
                        <div class="row g-2 justify-content-end text-end mt-1">
                            <div class="col-6 col-sm-4 text-muted small">Restaurant Dining:</div>
                            <div class="col-6 col-sm-3 fw-bold"><?= formatMoney($fnbTotal) ?></div>
                        </div>
                        <div class="row g-2 justify-content-end text-end mt-1">
                            <div class="col-6 col-sm-4 text-muted small">Taxes & Service (10%):</div>
                            <div class="col-6 col-sm-3 fw-bold"><?= formatMoney($tax) ?></div>
                        </div>
                        <div class="row g-2 justify-content-end text-end mt-1">
                            <div class="col-6 col-sm-4 text-muted small">Advance Deposit Paid:</div>
                            <div class="col-6 col-sm-3 fw-bold text-success">- <?= formatMoney($deposit) ?></div>
                        </div>
                        <hr class="my-2">
                        <div class="row g-2 justify-content-end text-end align-items-center">
                            <div class="col-6 col-sm-4 fs-5 fw-bold text-dark">Net Balance Due:</div>
                            <div class="col-6 col-sm-3 fs-4 fw-bold text-danger"><?= formatMoney($balanceDue) ?></div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <a href="pos.php" class="btn btn-outline-primary btn-sm">
                                <i class="fa-solid fa-plus me-1"></i> Add In-Room Dining (POS)
                            </a>
                            <button class="btn btn-danger px-4 py-2 fw-bold" onclick="openCheckoutModal(<?= $activeFolio['room_id'] ?>, '<?= htmlspecialchars($activeFolio['room_number']) ?>', '<?= formatMoney($balanceDue) ?>')">
                                <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Complete Checkout & Print Invoice
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-hotel fa-3x text-secondary opacity-50 mb-2"></i>
                        <p>No active stay currently selected. Please choose an occupied room above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Recent Tax Invoices Generated -->
    <div class="col-12 col-lg-5">
        <div class="card-custom h-100">
            <div class="card-header-clean d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-receipt text-success me-2"></i>Recent Master Invoices
                </h5>
                <span class="badge bg-light text-dark border">Audited Invoices</span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th>Invoice #</th>
                                <th>Guest / Room</th>
                                <th>Total</th>
                                <th class="text-end">Print</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoicesHistory)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No completed checkout invoices yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoicesHistory as $inv): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark small"><?= htmlspecialchars($inv['invoice_number']) ?></span>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?= formatDate($inv['created_at']) ?></div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold"><?= htmlspecialchars($inv['guest_name']) ?></div>
                                            <span class="badge bg-light text-dark border" style="font-size: 0.65rem;"><?= htmlspecialchars($inv['room_number'] ?? 'Walk-in') ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success small"><?= formatMoney($inv['total_amount']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="printInvoicePrompt('<?= htmlspecialchars($inv['invoice_number']) ?>')">
                                                <i class="fa-solid fa-print"></i>
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

<!-- Checkout Settlement Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt text-danger me-2"></i>Checkout & Settlement: <span id="modalRoomLabel"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="settleRoomId">
                <div class="text-center p-3 bg-light rounded-3 border mb-3">
                    <span class="text-muted small">Final Balance Due to Settle:</span>
                    <h2 class="fw-bold text-danger my-1" id="modalDueAmount"><?= APP_CURRENCY ?>0.00</h2>
                    <small class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Room will auto-switch to <strong>Dirty</strong> for Housekeeping</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Final Settlement Payment Method *</label>
                    <select id="settlePaymentMethod" class="form-select">
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="UPI / QR">UPI / QR Code</option>
                        <option value="Bank Transfer">Bank Wire Transfer</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Checkout Notes / Remarks</label>
                    <input type="text" id="settleNotes" class="form-control" placeholder="e.g. Guest praised dining service">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="btnConfirmSettle" onclick="executeCheckoutSettlement()">
                    <i class="fa-solid fa-check-double me-1"></i> Confirm Settle & Release Room
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openCheckoutModal(roomId, roomNumber, dueAmount) {
    document.getElementById('settleRoomId').value = roomId;
    document.getElementById('modalRoomLabel').innerText = roomNumber;
    document.getElementById('modalDueAmount').innerText = dueAmount;
    
    // Reset button state
    const btn = document.getElementById('btnConfirmSettle');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Confirm Settle & Release Room';
    }
    
    new bootstrap.Modal(document.getElementById('checkoutModal')).show();
}

function executeCheckoutSettlement() {
    const btn = document.getElementById('btnConfirmSettle');
    if (btn) {
        if (btn.disabled) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing Settlement...';
    }

    const roomId = document.getElementById('settleRoomId').value;
    const method = document.getElementById('settlePaymentMethod').value;
    const notes = document.getElementById('settleNotes').value;

    const formData = new FormData();
    formData.append('action', 'settle_checkout');
    formData.append('room_id', roomId);
    formData.append('payment_method', method);
    formData.append('notes', notes);

    fetch('api/billing.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const modalEl = document.getElementById('checkoutModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Checkout Succeeded!',
                html: `<strong>Invoice #${data.invoice_number}</strong><br>${data.message}`,
                confirmButtonText: 'Print Invoice / Done'
            }).then(() => {
                location.href = 'rooms.php';
            });
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Confirm Settle & Release Room';
            }
            Swal.fire({ icon: 'error', title: 'Settlement Failed', text: data.message });
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Confirm Settle & Release Room';
        }
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'An error occurred while connecting to server.' });
    });
}

function printInvoicePrompt(invoiceNumber) {
    Swal.fire({
        title: 'Invoice #' + invoiceNumber,
        text: 'Ready to send to physical thermal printer (80mm) or standard A4 document PDF.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-print me-1"></i> Print Receipt',
        confirmButtonColor: '#1e3a8a'
    }).then(res => {
        if (res.isConfirmed) {
            window.print();
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
