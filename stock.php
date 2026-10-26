<?php
/**
 * Stock & Inventory Management
 */
$pageTitle = 'Stock & Inventory Management';
$pageSubtitle = 'Kitchen Provisions, Bar Supplies, Room Amenities & Automated Reorder Alerts';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch categories
$stockCategories = $db->query("SELECT * FROM stock_categories ORDER BY id ASC")->fetchAll();

// Fetch all stock items with category names
$stockItems = $db->query("
    SELECT si.*, sc.name as category_name, sc.type as category_type
    FROM stock_items si
    JOIN stock_categories sc ON si.category_id = sc.id
    ORDER BY (si.current_stock <= si.minimum_stock) DESC, sc.id ASC, si.name ASC
")->fetchAll();

// Fetch recent movements
$recentMovements = $db->query("
    SELECT sm.*, si.name as item_name, si.unit, u.username as staff_user
    FROM stock_movements sm
    JOIN stock_items si ON sm.stock_item_id = si.id
    LEFT JOIN users u ON sm.created_by = u.id
    ORDER BY sm.id DESC
    LIMIT 10
")->fetchAll();

// Totals
$totalItems = count($stockItems);
$lowStockCount = 0;
$totalValuation = 0.00;

foreach ($stockItems as $item) {
    if ((float)$item['current_stock'] <= (float)$item['minimum_stock']) {
        $lowStockCount++;
    }
    $totalValuation += ((float)$item['current_stock'] * (float)$item['cost_per_unit']);
}
?>

<!-- KPI Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Total Stock Items</div>
                <div class="stat-number text-primary"><?= $totalItems ?> SKUs</div>
                <div class="small text-muted">Across 5 categories</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Low Stock Warnings</div>
                <div class="stat-number <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $lowStockCount ?> Alerts</div>
                <div class="small text-muted"><?= $lowStockCount > 0 ? 'At or below threshold' : 'Sufficient buffer' ?></div>
            </div>
            <div class="stat-icon <?= $lowStockCount > 0 ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?>">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Inventory Valuation</div>
                <div class="stat-number text-success"><?= formatMoney($totalValuation) ?></div>
                <div class="small text-muted">Current book value</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-vault"></i>
            </div>
        </div>
    </div>

    <?php $totalStockRooms = (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn(); ?>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Room Amenities Coverage</div>
                <div class="stat-number text-warning"><?= $totalStockRooms ?> Rooms</div>
                <div class="small text-muted">Linen & toiletry restocked</div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-pump-soap"></i>
            </div>
        </div>
    </div>
</div>

<!-- Controls & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm px-3 rounded-pill" onclick="openMovementModal('purchase_in')">
            <i class="fa-solid fa-arrow-down me-1"></i> Stock Inward / Purchase
        </button>
        <button class="btn btn-outline-danger btn-sm px-3 rounded-pill" onclick="openMovementModal('kitchen_out')">
            <i class="fa-solid fa-arrow-up me-1"></i> Kitchen / Room Dispensation
        </button>
        <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" onclick="openNewItemModal()">
            <i class="fa-solid fa-plus me-1"></i> Add New SKU
        </button>
    </div>
</div>

<!-- Main Inventory Table -->
<div class="card-custom mb-5">
    <div class="card-header-clean d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-clipboard-list text-primary me-2"></i>Inventory Master Registry
        </h5>
        <span class="badge bg-light text-dark border">Real-Time Balances</span>
    </div>

    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-init">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Item Description</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Current Stock</th>
                        <th>Min Par Level</th>
                        <th>Unit Cost</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stockItems as $item): 
                        $isLow = ((float)$item['current_stock'] <= (float)$item['minimum_stock']);
                        $stockPercent = ($item['minimum_stock'] > 0) ? min(100, round(($item['current_stock'] / ($item['minimum_stock'] * 2)) * 100)) : 100;
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                            <small class="text-muted"><i class="fa-solid fa-truck me-1"></i> <?= htmlspecialchars($item['supplier'] ?? 'Direct Market') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['category_name']) ?></span>
                        </td>
                        <td><span class="font-monospace small"><?= htmlspecialchars($item['unit']) ?></span></td>
                        <td>
                            <span class="fw-bold fs-6 <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                                <?= number_format($item['current_stock'], 2) ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-muted font-monospace small"><?= number_format($item['minimum_stock'], 2) ?></span>
                        </td>
                        <td><?= formatMoney($item['cost_per_unit']) ?></td>
                        <td>
                            <?php if ($isLow): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Low Stock
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success">
                                    <i class="fa-solid fa-check me-1"></i> Adequate
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary py-1 px-2" onclick="quickAddStock(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')">
                                <i class="fa-solid fa-plus"></i> Inward
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Movements Log -->
<div class="card-custom">
    <div class="card-header-clean">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Recent Stock Movements</h5>
    </div>
    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Date & Time</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference / Note</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMovements)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No inventory movements recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMovements as $m): 
                            $badge = 'success';
                            if ($m['movement_type'] === 'kitchen_out' || $m['movement_type'] === 'room_out') $badge = 'primary';
                            elseif ($m['movement_type'] === 'wastage') $badge = 'danger';
                        ?>
                        <tr>
                            <td><small class="text-muted"><?= formatDateTime($m['created_at']) ?></small></td>
                            <td class="fw-bold"><?= htmlspecialchars($m['item_name']) ?></td>
                            <td><span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> text-capitalize"><?= str_replace('_', ' ', $m['movement_type']) ?></span></td>
                            <td class="fw-bold font-monospace"><?= number_format($m['quantity'], 2) ?> <?= htmlspecialchars($m['unit']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($m['reference_note'] ?? '-') ?></small></td>
                            <td><small class="badge bg-light text-dark border"><?= htmlspecialchars($m['staff_user'] ?? 'System') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Movement Modal -->
<div class="modal fade" id="movementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="movementModalTitle">Record Stock Movement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalMovementType" value="purchase_in">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Select Item *</label>
                    <select id="modalStockItemId" class="form-select">
                        <?php foreach ($stockItems as $si): ?>
                            <option value="<?= $si['id'] ?>"><?= htmlspecialchars($si['name']) ?> (Available: <?= $si['current_stock'] ?> <?= $si['unit'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Quantity *</label>
                        <input type="number" id="modalMovementQty" class="form-control" step="0.1" min="0.1" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Unit Purchase Price (<?= APP_CURRENCY ?>)</label>
                        <input type="number" id="modalMovementPrice" class="form-control" step="1" value="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason / Reference Note</label>
                    <input type="text" id="modalMovementNote" class="form-control" placeholder="e.g. GRN-2023-88 or Kitchen evening preparation">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitStockMovement()">Confirm Movement</button>
            </div>
        </div>
    </div>
</div>

<!-- New SKU Modal -->
<div class="modal fade" id="newItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Create New Inventory SKU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Category *</label>
                    <select id="newCatId" class="form-select">
                        <?php foreach ($stockCategories as $sc): ?>
                            <option value="<?= $sc['id'] ?>"><?= htmlspecialchars($sc['name']) ?> (<?= ucfirst($sc['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Item Name *</label>
                    <input type="text" id="newItemName" class="form-control" placeholder="e.g. Premium Basmati Rice" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label small fw-bold">Unit *</label>
                        <input type="text" id="newItemUnit" class="form-control" placeholder="kg, L, pcs" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold">Opening Stock</label>
                        <input type="number" id="newItemStock" class="form-control" value="10" step="0.1">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold">Min Threshold</label>
                        <input type="number" id="newItemMin" class="form-control" value="5" step="0.1">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Unit Cost (<?= APP_CURRENCY ?>)</label>
                        <input type="number" id="newItemCost" class="form-control" value="0.00" step="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Supplier</label>
                        <input type="text" id="newItemSupplier" class="form-control" placeholder="e.g. Agro Supplies">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="submitNewItem()">Create SKU</button>
            </div>
        </div>
    </div>
</div>

<script>
function openMovementModal(type) {
    document.getElementById('modalMovementType').value = type;
    document.getElementById('movementModalTitle').innerText = (type === 'purchase_in') ? 'Record Stock Inward / Purchase' : 'Record Stock Dispensation / Out';
    new bootstrap.Modal(document.getElementById('movementModal')).show();
}

function quickAddStock(itemId, itemName) {
    document.getElementById('modalStockItemId').value = itemId;
    openMovementModal('purchase_in');
}

function submitStockMovement() {
    const itemId = document.getElementById('modalStockItemId').value;
    const type = document.getElementById('modalMovementType').value;
    const qty = document.getElementById('modalMovementQty').value;
    const price = document.getElementById('modalMovementPrice').value;
    const note = document.getElementById('modalMovementNote').value;

    if (!qty || parseFloat(qty) <= 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid positive quantity.' });
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_movement');
    formData.append('stock_item_id', itemId);
    formData.append('movement_type', type);
    formData.append('quantity', qty);
    formData.append('unit_price', price);
    formData.append('reference_note', note);

    fetch('api/stock.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('movementModal')).hide();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Inventory Updated', text: data.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    });
}

function openNewItemModal() {
    new bootstrap.Modal(document.getElementById('newItemModal')).show();
}

function submitNewItem() {
    const catId = document.getElementById('newCatId').value;
    const name = document.getElementById('newItemName').value.trim();
    const unit = document.getElementById('newItemUnit').value.trim();
    const stock = document.getElementById('newItemStock').value;
    const min = document.getElementById('newItemMin').value;
    const cost = document.getElementById('newItemCost').value;
    const supplier = document.getElementById('newItemSupplier').value.trim();

    if (!name || !unit) {
        Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Item Name and Unit are required.' });
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create_item');
    formData.append('category_id', catId);
    formData.append('name', name);
    formData.append('unit', unit);
    formData.append('current_stock', stock);
    formData.append('minimum_stock', min);
    formData.append('cost_per_unit', cost);
    formData.append('supplier', supplier);

    fetch('api/stock.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('newItemModal')).hide();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'SKU Added', text: data.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Failed', text: data.message });
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
