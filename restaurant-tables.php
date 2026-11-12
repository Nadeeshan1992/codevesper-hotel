<?php
/**
 * Restaurant Dining Tables Management (Full CRUD)
 * CodeVesper Hotel & Restaurant Management Suite
 */
$pageTitle = 'Dining Tables Management';
$pageSubtitle = 'Table Layout, Occupancy Status & Floor Sections';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

// 1. Handle Add New Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    $tableNum = trim(sanitize($_POST['table_number'] ?? ''));
    $section = trim(sanitize($_POST['section'] ?? 'Main Dining'));
    $capacity = max(1, (int)($_POST['capacity'] ?? 4));
    $status = in_array($_POST['status'] ?? '', ['available', 'occupied', 'reserved', 'cleaning']) ? $_POST['status'] : 'available';

    if (empty($tableNum)) {
        $errorMsg = "Table Name or Identifier is required.";
    } else {
        $chk = $db->prepare("SELECT COUNT(*) FROM dining_tables WHERE table_number = ?");
        $chk->execute([$tableNum]);
        if ($chk->fetchColumn() > 0) {
            $errorMsg = "A table named '$tableNum' already exists! Please use a unique name.";
        } else {
            $stmt = $db->prepare("INSERT INTO dining_tables (table_number, section, capacity, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tableNum, $section, $capacity, $status]);
            logAudit('Restaurant', 'Table Added', "Added dining table $tableNum in $section ($capacity seats)");
            $successMsg = "Table '$tableNum' successfully added to dining inventory!";
        }
    }
}

// 2. Handle Edit Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_table'])) {
    $tableId = (int)$_POST['table_id'];
    $tableNum = trim(sanitize($_POST['table_number'] ?? ''));
    $section = trim(sanitize($_POST['section'] ?? 'Main Dining'));
    $capacity = max(1, (int)($_POST['capacity'] ?? 4));

    if (empty($tableNum)) {
        $errorMsg = "Table Name cannot be empty.";
    } else {
        $chk = $db->prepare("SELECT COUNT(*) FROM dining_tables WHERE table_number = ? AND id != ?");
        $chk->execute([$tableNum, $tableId]);
        if ($chk->fetchColumn() > 0) {
            $errorMsg = "Another table already has the name '$tableNum'!";
        } else {
            $stmt = $db->prepare("UPDATE dining_tables SET table_number = ?, section = ?, capacity = ? WHERE id = ?");
            $stmt->execute([$tableNum, $section, $capacity, $tableId]);
            logAudit('Restaurant', 'Table Updated', "Updated Table ID $tableId: $tableNum ($section, $capacity seats)");
            $successMsg = "Table '$tableNum' updated successfully!";
        }
    }
}

// 3. Handle Delete Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $tableId = (int)$_POST['table_id'];
    // Check if table has active orders
    $activeOrders = $db->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ? AND status IN ('pending', 'in_kitchen', 'ready')");
    $activeOrders->execute([$tableId]);
    if ($activeOrders->fetchColumn() > 0) {
        $errorMsg = "Cannot delete this table because it has active kitchen orders!";
    } else {
        $delStmt = $db->prepare("DELETE FROM dining_tables WHERE id = ?");
        $delStmt->execute([$tableId]);
        logAudit('Restaurant', 'Table Deleted', "Deleted Table ID $tableId");
        $successMsg = "Table removed successfully!";
    }
}

// 4. Handle table status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table_status'])) {
    $tableId = (int)$_POST['table_id'];
    $status = $_POST['status'];
    $db->prepare("UPDATE dining_tables SET status = ? WHERE id = ?")->execute([$status, $tableId]);
    logAudit('Restaurant', 'Table Status Changed', "Updated Table ID $tableId status to $status");
}

// Section filter
$selectedSection = $_GET['section'] ?? 'all';

// Fetch tables with active order aggregations
$query = "
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
";
$params = [];
if ($selectedSection !== 'all') {
    $query .= " WHERE t.section = ?";
    $params[] = $selectedSection;
}
$query .= " GROUP BY t.id ORDER BY t.section ASC, t.id ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct sections for filter pills
$sections = $db->query("SELECT DISTINCT section FROM dining_tables WHERE section IS NOT NULL AND section != '' ORDER BY section ASC")->fetchAll(PDO::FETCH_COLUMN);

// Table counts summary
$totalTablesCount = (int)$db->query("SELECT COUNT(*) FROM dining_tables")->fetchColumn();
$occupiedCount = (int)$db->query("SELECT COUNT(*) FROM dining_tables WHERE status = 'occupied'")->fetchColumn();
$availableCount = (int)$db->query("SELECT COUNT(*) FROM dining_tables WHERE status = 'available'")->fetchColumn();
?>

<!-- Alert Feedback -->
<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span><?= htmlspecialchars($successMsg) ?></span>
    </div>
<?php endif; ?>
<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
        <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
<?php endif; ?>

<!-- Top Operations Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h5 class="fw-bold mb-1"><i class="fa-solid fa-utensils text-success me-2"></i>Restaurant Dining Floor Plan</h5>
        <div class="d-flex align-items-center gap-2 small text-muted">
            <span><strong><?= $totalTablesCount ?></strong> Total Tables</span> &bull; 
            <span class="text-success fw-bold"><?= $availableCount ?> Available</span> &bull; 
            <span class="text-danger fw-bold"><?= $occupiedCount ?> Occupied</span>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <!-- Add Table Button -->
        <button type="button" class="btn btn-success btn-sm px-3 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addTableModal">
            <i class="fa-solid fa-plus me-1"></i> Add New Table
        </button>
        <!-- Print QR Stands Button -->
        <a href="table-qrs.php" class="btn btn-outline-warning text-dark btn-sm px-3 rounded-pill fw-bold">
            <i class="fa-solid fa-qrcode me-1 text-warning"></i> Print Table QR Stands
        </a>
        <!-- Waiter Tablet POS -->
        <a href="pos.php" class="btn btn-primary btn-sm px-3 rounded-pill">
            <i class="fa-solid fa-tablet-screen-button me-1"></i> Waiter Tablet POS
        </a>
    </div>
</div>

<!-- Section Filter Tabs -->
<div class="d-flex align-items-center gap-2 overflow-x-auto pb-2 mb-4">
    <a href="restaurant-tables.php?section=all" class="btn btn-sm <?= ($selectedSection === 'all') ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill px-3">
        All Sections (<?= $totalTablesCount ?>)
    </a>
    <?php foreach ($sections as $sec): 
        $secCount = (int)$db->query("SELECT COUNT(*) FROM dining_tables WHERE section = " . $db->quote($sec))->fetchColumn();
    ?>
        <a href="restaurant-tables.php?section=<?= urlencode($sec) ?>" class="btn btn-sm <?= ($selectedSection === $sec) ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill px-3 text-nowrap">
            <?= htmlspecialchars($sec) ?> (<?= $secCount ?>)
        </a>
    <?php endforeach; ?>
</div>

<!-- Tables Grid -->
<div class="row g-4">
    <?php if (empty($tables)): ?>
        <div class="col-12">
            <div class="card-custom p-5 text-center text-muted">
                <i class="fa-solid fa-chair fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <h5>No tables found in this section.</h5>
                <p class="small text-muted mb-3">Click the button below to add your first table to this section.</p>
                <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addTableModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Table Now
                </button>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tables as $t): 
            $color = 'success';
            if ($t['status'] === 'occupied') $color = 'danger';
            elseif ($t['status'] === 'reserved') $color = 'warning';
            elseif ($t['status'] === 'cleaning') $color = 'info';
        ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card-custom h-100 p-3 border-2 border-<?= $color ?> shadow-sm d-flex flex-column justify-content-between">
                <div>
                    <!-- Header: Table Number & Seats -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['table_number']) ?></h5>
                            <small class="text-muted"><?= htmlspecialchars($t['section']) ?></small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-light text-dark border">
                                <i class="fa-solid fa-users me-1"></i> <?= $t['capacity'] ?> Seats
                            </span>
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-sm btn-link text-secondary p-0 ms-1" title="Edit Table Details" onclick="openEditTableModal(<?= htmlspecialchars(json_encode($t)) ?>)">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="my-2">
                        <?= getTableStatusBadge($t['status']) ?>
                    </div>

                    <!-- Active Orders Summary (Aggregated) -->
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

                <!-- Footer Action Buttons -->
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

                    <!-- View Guest QR Menu -->
                    <a href="qr-menu.php?table=<?= $t['id'] ?>" target="_blank" class="btn btn-sm btn-outline-warning text-dark" title="View Guest QR Menu">
                        <i class="fa-solid fa-qrcode text-warning"></i>
                    </a>

                    <!-- Toggle Clean/Occupy -->
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
    <?php endif; ?>
</div>

<!-- Modal 1: Add New Table -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-success rounded-3 text-white">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Add New Dining Table</h5>
                        <small class="text-white-50">Configure a new table for restaurant floor plan &amp; QR ordering</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="restaurant-tables.php">
                <input type="hidden" name="add_table" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Table Name / Number *</label>
                        <input type="text" name="table_number" class="form-control rounded-3" placeholder="e.g. Table T9, VIP 1, Garden Table 10" required>
                        <small class="text-muted">Must be a unique identifier for this table.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Floor Section / Location *</label>
                        <input type="text" name="section" class="form-control rounded-3" list="sectionsList" placeholder="e.g. Indoor Hall, Terrace Garden, Bar Lounge..." required>
                        <datalist id="sectionsList">
                            <option value="Indoor Hall">
                            <option value="Terrace Garden">
                            <option value="Bar Lounge">
                            <option value="Poolside">
                            <option value="Balcony View">
                            <option value="VIP Lounge">
                            <option value="Rooftop">
                        </datalist>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Seating Capacity (Guests)</label>
                            <input type="number" name="capacity" class="form-control rounded-3" value="4" min="1" max="50" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Initial Status</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="available" selected>Available (Vacant)</option>
                                <option value="reserved">Reserved</option>
                                <option value="cleaning">Cleaning</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-plus me-1"></i> Save &amp; Add Table
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Table -->
<div class="modal fade" id="editTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary rounded-3 text-white">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Edit Table Configuration</h5>
                        <small class="text-white-50" id="editModalSubtitle">Update table settings</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="restaurant-tables.php">
                <input type="hidden" name="edit_table" value="1">
                <input type="hidden" name="table_id" id="editTableId" value="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Table Name / Number *</label>
                        <input type="text" name="table_number" id="editTableNumber" class="form-control rounded-3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Floor Section / Location *</label>
                        <input type="text" name="section" id="editTableSection" class="form-control rounded-3" list="sectionsList" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Seating Capacity</label>
                        <input type="number" name="capacity" id="editTableCapacity" class="form-control rounded-3" min="1" max="50" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 d-flex justify-content-between">
                    <!-- Delete Form Trigger -->
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3" onclick="confirmDeleteTable()">
                        <i class="fa-solid fa-trash me-1"></i> Delete Table
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form method="POST" action="restaurant-tables.php" id="deleteTableForm" style="display: none;">
    <input type="hidden" name="delete_table" value="1">
    <input type="hidden" name="table_id" id="deleteTableId" value="">
</form>

<script>
function openEditTableModal(table) {
    document.getElementById('editTableId').value = table.id;
    document.getElementById('editTableNumber').value = table.table_number;
    document.getElementById('editTableSection').value = table.section || 'Main Dining';
    document.getElementById('editTableCapacity').value = table.capacity || 4;
    document.getElementById('editModalSubtitle').innerText = 'Editing ' + table.table_number;

    const modal = new bootstrap.Modal(document.getElementById('editTableModal'));
    modal.show();
}

function confirmDeleteTable() {
    const tableId = document.getElementById('editTableId').value;
    const tableName = document.getElementById('editTableNumber').value;

    Swal.fire({
        title: 'Delete ' + tableName + '?',
        text: 'Are you sure you want to remove this table from the floor plan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete Table'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteTableId').value = tableId;
            document.getElementById('deleteTableForm').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
