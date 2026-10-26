<?php
/**
 * Waiter Tablet POS Terminal
 * Touch-First Interface Designed for Tablet PCs & Fast Table Service
 */
$pageTitle = 'Waiter Tablet POS';
$pageSubtitle = 'Touchscreen Order Taking for Dining Tables & Room Service';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch menu categories
$categories = $db->query("SELECT * FROM menu_categories WHERE status = 'active' ORDER BY sort_order ASC")->fetchAll();

// Fetch all available menu items
$menuItems = $db->query("
    SELECT m.*, c.name as category_name 
    FROM menu_items m
    JOIN menu_categories c ON m.category_id = c.id
    WHERE m.is_available = 1
    ORDER BY c.sort_order ASC, m.name ASC
")->fetchAll();

// Fetch all dining tables
$diningTables = $db->query("SELECT * FROM dining_tables ORDER BY id ASC")->fetchAll();

// Fetch rooms with current occupant details for Room Service
$hotelRooms = $db->query("
    SELECT r.id, r.room_number, r.status, g.full_name as guest_name
    FROM rooms r
    LEFT JOIN check_ins ci ON (ci.room_id = r.id AND ci.status = 'active')
    LEFT JOIN guests g ON ci.guest_id = g.id
    ORDER BY r.floor ASC, r.room_number ASC
")->fetchAll();
?>

<style>
/* Prevent any horizontal window scrolling on Tablet POS */
body, .app-wrapper, .app-main {
    overflow-x: hidden !important;
    max-width: 100vw !important;
}

.page-container {
    padding: 8px 12px !important;
    overflow-x: hidden !important;
    max-width: 100% !important;
}
</style>

<div class="pos-page-wrapper">
    <!-- Top Tablet POS Navigation Bar -->
    <div class="card-custom p-2 px-3 mb-2 bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success px-2 py-1 small">
                <i class="fa-solid fa-tablet-screen-button me-1"></i> Waiter Touch Mode
            </span>
            <span class="text-muted small d-none d-md-inline">
                Tap items to add &bull; Select Table or Room to submit order
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border small">
                <i class="fa-solid fa-user me-1 text-primary"></i> <?= htmlspecialchars($currentUser['full_name'] ?? 'Waiter') ?>
            </span>
            <a href="kitchen.php" class="btn btn-outline-danger btn-sm py-1 px-2" title="Live Kitchen Display">
                <i class="fa-solid fa-fire me-1"></i> Kitchen KOT
            </a>
            <button class="btn btn-outline-secondary btn-sm py-1 px-2" onclick="clearCart()" title="Clear Current Cart">
                <i class="fa-solid fa-rotate-left me-1"></i> Clear
            </button>
        </div>
    </div>

    <!-- Main Dual-Panel POS Container -->
    <div class="pos-container">
        <!-- Left Column: Menu Catalog Panel -->
        <div class="pos-catalog-panel">
            <!-- Category Filter Bar (Touch swipeable horizontal bar) -->
            <div class="pos-categories-bar">
                <button type="button" class="category-pill active" onclick="filterCategory('all', this)">
                    <i class="fa-solid fa-utensils"></i> All Items
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="category-pill" onclick="filterCategory('cat-<?= $cat['id'] ?>', this)">
                        <i class="fa-solid fa-<?= htmlspecialchars($cat['icon']) ?>"></i>
                        <?= htmlspecialchars($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Search Bar with Big Touch Target -->
            <div class="pos-search-box">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" id="menuSearchInput" class="form-control border-start-0 ps-0" placeholder="Search dish, beverage or dessert..." onkeyup="filterMenuSearch()">
                </div>
            </div>

            <!-- Menu Item Cards Grid with Food Images -->
            <div class="pos-menu-grid" id="menuGrid">
                <?php foreach ($menuItems as $item): 
                    $foodImg = !empty($item['image']) ? htmlspecialchars($item['image']) : 'default_food.jpg';
                ?>
                    <div class="menu-card-touch cat-<?= $item['category_id'] ?>" 
                         data-id="<?= $item['id'] ?>"
                         data-name="<?= htmlspecialchars($item['name']) ?>"
                         data-price="<?= $item['price'] ?>"
                         onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>, '<?= addslashes($foodImg) ?>')">
                        
                        <div class="menu-card-img-wrap">
                            <img src="assets/images/foods/<?= $foodImg ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="menu-card-img"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='assets/images/foods/default_food.jpg';">
                            <span class="menu-card-cat-tag"><?= htmlspecialchars($item['category_name']) ?></span>
                            <?php if (!empty($item['prep_time_minutes'])): ?>
                                <span class="menu-card-prep-tag">
                                    <i class="fa-regular fa-clock me-1"></i><?= (int)$item['prep_time_minutes'] ?>m
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="menu-card-body">
                            <div class="menu-card-name" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="menu-card-desc"><?= htmlspecialchars($item['description'] ?? '') ?></div>
                        </div>

                        <div class="menu-card-footer">
                            <span class="menu-card-price"><?= formatMoney($item['price']) ?></span>
                            <button class="menu-card-add-btn" type="button" title="Add to Order">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Side: Pinned Active Order Cart Panel -->
        <div class="pos-order-panel">
            <!-- Header: Order Type (Table vs Room) -->
            <div class="pos-order-header">
                <div class="order-type-tabs">
                    <button type="button" class="order-type-btn active" id="btnTypeDineIn" onclick="setOrderType('dine_in')">
                        <i class="fa-solid fa-chair me-1"></i> Dine-In Table
                    </button>
                    <button type="button" class="order-type-btn" id="btnTypeRoom" onclick="setOrderType('room_service')">
                        <i class="fa-solid fa-hotel me-1"></i> In-Room Dining
                    </button>
                </div>

                <!-- Table Selector Section with 1-Tap Pills -->
                <div id="sectionTableSelect">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-muted mb-0">Select Table:</label>
                        <span class="text-muted" style="font-size: 0.7rem;">Quick 1-Tap:</span>
                    </div>

                    <!-- Quick Table Pills Bar -->
                    <div class="quick-table-scroll">
                        <?php foreach ($diningTables as $t): 
                            $shortName = str_replace('Table ', '', $t['table_number']);
                        ?>
                            <button type="button" 
                                    class="quick-table-pill <?= $t['status'] === 'occupied' ? 'occupied' : '' ?>" 
                                    id="pill-table-<?= $t['id'] ?>"
                                    onclick="selectQuickTable(<?= $t['id'] ?>)"
                                    title="<?= htmlspecialchars($t['table_number']) ?> (<?= htmlspecialchars($t['section']) ?> - <?= ucfirst($t['status']) ?>)">
                                <?= htmlspecialchars($shortName) ?><?= $t['status'] === 'occupied' ? '*' : '' ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <select id="selectDiningTable" class="form-select form-select-sm" onchange="syncTablePill(this.value)">
                        <option value="">-- Choose Dining Table --</option>
                        <?php foreach ($diningTables as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['status'] === 'occupied' ? 'data-occupied="1"' : '' ?>>
                                <?= htmlspecialchars($t['table_number']) ?> (<?= htmlspecialchars($t['section']) ?> - <?= ucfirst($t['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Room Service Selector Section (Hidden by default) -->
                <div id="sectionRoomSelect" style="display: none;">
                    <label class="form-label small fw-bold text-muted mb-1">Select Hotel Room:</label>
                    <select id="selectHotelRoom" class="form-select form-select-sm" onchange="updateGuestInfo()">
                        <option value="">-- Choose Room --</option>
                        <?php foreach ($hotelRooms as $r): ?>
                            <option value="<?= $r['id'] ?>" data-guest="<?= htmlspecialchars($r['guest_name'] ?? 'Vacant / Walk-in') ?>" data-status="<?= $r['status'] ?>">
                                <?= htmlspecialchars($r['room_number']) ?> &bull; <?= ucfirst($r['status']) ?> <?= !empty($r['guest_name']) ? '('.htmlspecialchars($r['guest_name']).')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="roomGuestDisplay" class="small text-primary fw-semibold mt-1"></div>
                </div>
            </div>

            <!-- Active Order Cart Items -->
            <div class="pos-cart-items" id="cartItemsList">
                <!-- Empty state -->
                <div class="text-center py-5 text-muted" id="emptyCartMessage">
                    <i class="fa-solid fa-cart-shopping fa-3x text-secondary opacity-50 mb-2"></i>
                    <div class="fw-semibold">No Items in Order</div>
                    <small>Tap food cards to add to order</small>
                </div>
            </div>

            <!-- Totals & Big Touch Action Buttons -->
            <div class="pos-order-footer">
                <div class="total-line">
                    <span>Subtotal:</span>
                    <span id="txtSubtotal"><?= APP_CURRENCY ?>0.00</span>
                </div>
                <div class="total-line">
                    <span>Taxes & Service (10%):</span>
                    <span id="txtTax"><?= APP_CURRENCY ?>0.00</span>
                </div>
                <div class="total-line final">
                    <span>Total Amount:</span>
                    <span id="txtTotal" class="text-primary"><?= APP_CURRENCY ?>0.00</span>
                </div>

                <div class="d-flex flex-column gap-2 mt-2">
                    <!-- 1. Send Kitchen KOT -->
                    <button type="button" class="pos-action-btn btn-kot" onclick="submitOrder('kot')">
                        <i class="fa-solid fa-fire"></i>
                        <span>Send to Kitchen (KOT)</span>
                    </button>

                    <!-- 2. Room Folio Charge (Only if room service selected) -->
                    <button type="button" class="pos-action-btn btn-room-charge" id="btnRoomCharge" style="display: none;" onclick="submitOrder('room_folio')">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Charge to Room Folio</span>
                    </button>

                    <!-- 3. Immediate Payment Settlement -->
                    <button type="button" class="pos-action-btn btn-settle" onclick="openPaymentModal()">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Quick Settle / Direct Pay</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-credit-card me-2 text-success"></i>Settle Order Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <span class="text-muted small">Total Due:</span>
                    <h2 class="fw-bold text-success" id="modalPaymentTotal"><?= APP_CURRENCY ?>0.00</h2>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Payment Method</label>
                    <select id="paymentMethodSelect" class="form-select">
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="UPI / QR">UPI / QR Payment</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Order Notes / Guest Remarks</label>
                    <input type="text" id="orderNotesInput" class="form-control" placeholder="e.g. VIP guest, requested quick delivery">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="executeDirectPayment()">
                    <i class="fa-solid fa-check me-1"></i> Complete & Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Item Special Instruction Modal -->
<div class="modal fade" id="instructionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Special Kitchen Instructions</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="instructionItemId">
                <textarea id="instructionText" class="form-control" rows="3" placeholder="e.g. Extra spicy, no onions, dressing on side"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveInstruction()">Save Instruction</button>
            </div>
        </div>
    </div>
</div>

<script>
// Waiter POS Cart State
const CURRENCY_SYM = '<?= APP_CURRENCY ?>';
let cart = [];
let currentOrderType = 'dine_in';

// Safe notification helper (works even if SweetAlert CDN fails)
function showNotify(icon, title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: icon,
            title: title,
            html: message,
            confirmButtonColor: '#1e3a8a'
        });
    } else {
        alert(title + "\n" + (message ? message.replace(/<[^>]*>?/gm, '') : ''));
    }
}

// Quick Toast feedback when adding item
function showItemToast(name) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: false
        });
        Toast.fire({
            icon: 'success',
            title: `Added ${name}`
        });
    }
}

// Switch between Dine-In and Room Service
function setOrderType(type) {
    currentOrderType = type;
    const btnDineIn = document.getElementById('btnTypeDineIn');
    const btnRoom = document.getElementById('btnTypeRoom');
    const secTable = document.getElementById('sectionTableSelect');
    const secRoom = document.getElementById('sectionRoomSelect');
    const btnRoomCharge = document.getElementById('btnRoomCharge');

    if (type === 'dine_in') {
        btnDineIn.classList.add('active');
        btnRoom.classList.remove('active');
        secTable.style.display = 'block';
        secRoom.style.display = 'none';
        btnRoomCharge.style.display = 'none';
    } else {
        btnRoom.classList.add('active');
        btnDineIn.classList.remove('active');
        secTable.style.display = 'none';
        secRoom.style.display = 'block';
        btnRoomCharge.style.display = 'flex';
    }
}

// Quick 1-Tap Table Pill Selection
function selectQuickTable(tableId) {
    const sel = document.getElementById('selectDiningTable');
    sel.value = tableId;
    syncTablePill(tableId);
}

function syncTablePill(tableId) {
    document.querySelectorAll('.quick-table-pill').forEach(p => p.classList.remove('active'));
    if (tableId) {
        const pill = document.getElementById('pill-table-' + tableId);
        if (pill) pill.classList.add('active');
    }
}

function updateGuestInfo() {
    const sel = document.getElementById('selectHotelRoom');
    const opt = sel.options[sel.selectedIndex];
    const guest = opt.getAttribute('data-guest') || '';
    const status = opt.getAttribute('data-status') || '';
    const disp = document.getElementById('roomGuestDisplay');

    if (sel.value) {
        disp.innerHTML = `<i class="fa-solid fa-user me-1"></i> Guest: <strong>${guest}</strong> (${status})`;
    } else {
        disp.innerHTML = '';
    }
}

function addToCart(id, name, price, image = 'default_food.jpg') {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            qty: 1,
            notes: '',
            image: image
        });
    }
    renderCart();
    showItemToast(name);
}

function updateQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;

    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== id);
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

function openInstructionModal(id) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    document.getElementById('instructionItemId').value = id;
    document.getElementById('instructionText').value = item.notes || '';
    const modal = new bootstrap.Modal(document.getElementById('instructionModal'));
    modal.show();
}

function saveInstruction() {
    const id = parseInt(document.getElementById('instructionItemId').value);
    const text = document.getElementById('instructionText').value.trim();
    const item = cart.find(i => i.id === id);
    if (item) {
        item.notes = text;
    }
    bootstrap.Modal.getInstance(document.getElementById('instructionModal')).hide();
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Are you sure you want to clear current order items?')) {
        cart = [];
        renderCart();
    }
}

function renderCart() {
    const container = document.getElementById('cartItemsList');

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted" id="emptyCartMessage">
                <i class="fa-solid fa-cart-shopping fa-3x text-secondary opacity-50 mb-2"></i>
                <div class="fw-semibold">No Items in Order</div>
                <small>Tap food cards to add to order</small>
            </div>
        `;
        document.getElementById('txtSubtotal').innerText = CURRENCY_SYM + '0.00';
        document.getElementById('txtTax').innerText = CURRENCY_SYM + '0.00';
        document.getElementById('txtTotal').innerText = CURRENCY_SYM + '0.00';
        return;
    }

    let html = '';
    let subtotal = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;

        html += `
            <div class="cart-item-row">
                <div class="cart-item-info">
                    <img src="assets/images/foods/${item.image || 'default_food.jpg'}" 
                         alt="${item.name}" 
                         class="rounded-2 border flex-shrink-0" 
                         style="width: 38px; height: 38px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='assets/images/foods/default_food.jpg';">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="cart-item-name text-truncate" title="${item.name}">${item.name}</div>
                        ${item.notes ? `<small class="text-danger d-block text-truncate"><i class="fa-solid fa-comment-dots me-1"></i>${item.notes}</small>` : ''}
                        <div class="cart-item-price">${CURRENCY_SYM}${itemTotal.toFixed(2)}</div>
                    </div>
                </div>
                <div class="cart-item-controls">
                    <div class="qty-control-touch">
                        <button type="button" class="qty-touch-btn" onclick="updateQty(${item.id}, -1)">-</button>
                        <span class="qty-display">${item.qty}</span>
                        <button type="button" class="qty-touch-btn" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Special Note" onclick="openInstructionModal(${item.id})">
                            <i class="fa-regular fa-comment"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove" onclick="removeFromCart(${item.id})">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    const tax = subtotal * 0.10;
    const total = subtotal + tax;

    document.getElementById('txtSubtotal').innerText = CURRENCY_SYM + subtotal.toFixed(2);
    document.getElementById('txtTax').innerText = CURRENCY_SYM + tax.toFixed(2);
    document.getElementById('txtTotal').innerText = CURRENCY_SYM + total.toFixed(2);
}

// Category filter
function filterCategory(catClass, elem) {
    document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
    elem.classList.add('active');

    const cards = document.querySelectorAll('.menu-card-touch');
    cards.forEach(card => {
        if (catClass === 'all' || card.classList.contains(catClass)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

// Search filter
function filterMenuSearch() {
    const term = document.getElementById('menuSearchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.menu-card-touch');
    cards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        if (name.includes(term)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

// Submit Order (KOT / Room Charge)
function submitOrder(actionType) {
    if (cart.length === 0) {
        showNotify('warning', 'Empty Cart', 'Please tap items to add to the order first.');
        return;
    }

    let tableId = null;
    let roomId = null;

    if (currentOrderType === 'dine_in') {
        tableId = document.getElementById('selectDiningTable').value;
        if (!tableId) {
            showNotify('warning', 'Table Required', 'Please select a dining table before sending order.');
            return;
        }
    } else {
        roomId = document.getElementById('selectHotelRoom').value;
        if (!roomId) {
            showNotify('warning', 'Room Required', 'Please choose a hotel room for Room Service.');
            return;
        }
    }

    const formData = new FormData();
    formData.append('action', 'create_order');
    formData.append('order_type', currentOrderType);
    if (tableId) formData.append('table_id', tableId);
    if (roomId) formData.append('room_id', roomId);
    formData.append('items', JSON.stringify(cart));
    formData.append('payment_method', 'unpaid');

    if (actionType === 'room_folio') {
        formData.append('charge_to_room', '1');
    }

    fetch('api/orders.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotify('success', 'Order Transmitted!', `<strong>Order #${data.order_number}</strong><br>${data.message}`);
            cart = [];
            renderCart();
            // Highlight occupied table
            if (tableId) {
                const pill = document.getElementById('pill-table-' + tableId);
                if (pill) pill.classList.add('occupied');
            }
        } else {
            showNotify('error', 'Error', data.message);
        }
    })
    .catch(err => {
        showNotify('error', 'Network Error', 'Failed to communicate with server: ' + err);
    });
}

function openPaymentModal() {
    if (cart.length === 0) {
        showNotify('warning', 'Empty Cart', 'Add items before payment settlement.');
        return;
    }
    const totalTxt = document.getElementById('txtTotal').innerText;
    document.getElementById('modalPaymentTotal').innerText = totalTxt;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function executeDirectPayment() {
    let tableId = null;
    let roomId = null;

    if (currentOrderType === 'dine_in') {
        tableId = document.getElementById('selectDiningTable').value;
        if (!tableId) {
            showNotify('warning', 'Select Table', 'Please choose a dining table.');
            return;
        }
    } else {
        roomId = document.getElementById('selectHotelRoom').value;
        if (!roomId) {
            showNotify('warning', 'Select Room', 'Please pick a room.');
            return;
        }
    }

    const payMethod = document.getElementById('paymentMethodSelect').value;
    const notes = document.getElementById('orderNotesInput').value;

    const formData = new FormData();
    formData.append('action', 'create_order');
    formData.append('order_type', currentOrderType);
    if (tableId) formData.append('table_id', tableId);
    if (roomId) formData.append('room_id', roomId);
    formData.append('items', JSON.stringify(cart));
    formData.append('payment_method', payMethod);
    formData.append('notes', notes);

    fetch('api/orders.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        if (data.success) {
            showNotify('success', 'Payment Received!', `Order #${data.order_number} marked as Paid via <strong>${payMethod}</strong>.<br>Amount: ${CURRENCY_SYM}${parseFloat(data.total_amount).toFixed(2)}`);
            cart = [];
            renderCart();
        } else {
            showNotify('error', 'Payment Failed', data.message);
        }
    })
    .catch(err => {
        showNotify('error', 'Network Error', 'Payment processing error: ' + err);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
