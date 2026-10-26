<?php
/**
 * Restaurant Food & Beverage Menu Catalog
 */
$pageTitle = 'Food & Beverage Menu';
$pageSubtitle = 'Manage Menu Categories, Pricing, Ingredients & Kitchen Availability';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Handle add menu item
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['add_menu_item'])) {
    $catId = (int)$_POST['category_id'];
    $name = sanitize($_POST['name'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');
    $price = (float)$_POST['price'];
    $cost = (float)($_POST['cost_price'] ?? 0.00);
    $prep = (int)($_POST['prep_time_minutes'] ?? 15);
    $image = sanitize($_POST['image'] ?? 'default_food.jpg');

    try {
        $stmt = $db->prepare("
            INSERT INTO menu_items (category_id, name, description, price, cost_price, prep_time_minutes, is_available, image)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([$catId, $name, $desc, $price, $cost, $prep, $image]);
        logAudit('Menu', 'Item Added', "Added $name to menu at $$price");
        $menuSuccess = "Item '$name' added to menu!";
    } catch (Exception $e) {
        $menuError = "Failed to add item: " . $e->getMessage();
    }
}

// Toggle availability
if (isset($_GET['toggle_id'])) {
    $toggleId = (int)$_GET['toggle_id'];
    $db->prepare("UPDATE menu_items SET is_available = 1 - is_available WHERE id = ?")->execute([$toggleId]);
    header('Location: restaurant-menu.php');
    exit;
}

$categories = $db->query("SELECT * FROM menu_categories ORDER BY sort_order ASC")->fetchAll();
$menuItems = $db->query("
    SELECT m.*, c.name as category_name
    FROM menu_items m
    JOIN menu_categories c ON m.category_id = c.id
    ORDER BY c.sort_order ASC, m.name ASC
")->fetchAll();
?>

<?php if (!empty($menuSuccess)): ?>
    <div class="alert alert-success py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span><?= htmlspecialchars($menuSuccess) ?></span>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-book-open text-primary me-2"></i>Menu Catalog (<?= count($menuItems) ?> Items)</h5>
        <span class="text-muted small">Items available for Waiter Tablet POS and In-Room dining</span>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#newMenuItemModal">
            <i class="fa-solid fa-plus me-1"></i> Add Menu Item
        </button>
        <a href="pos.php" class="btn btn-outline-success btn-sm px-3 rounded-pill">
            <i class="fa-solid fa-tablet-screen-button me-1"></i> Open Tablet POS
        </a>
    </div>
</div>

<div class="card-custom">
    <div class="p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable-init">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th>Dish / Drink Name</th>
                        <th>Category</th>
                        <th>Selling Price</th>
                        <th>Cost Price</th>
                        <th>Est. Prep Time</th>
                        <th>Availability</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $m): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="assets/images/foods/<?= htmlspecialchars(!empty($m['image']) ? $m['image'] : 'default_food.jpg') ?>" 
                                     alt="<?= htmlspecialchars($m['name']) ?>" 
                                     class="rounded-3 border flex-shrink-0" 
                                     style="width: 48px; height: 48px; object-fit: cover;"
                                     onerror="this.onerror=null; this.src='assets/images/foods/default_food.jpg';">
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['description'] ?? '') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['category_name']) ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-primary"><?= formatMoney($m['price']) ?></span>
                        </td>
                        <td>
                            <span class="text-muted small"><?= formatMoney($m['cost_price']) ?></span>
                        </td>
                        <td>
                            <span class="small text-muted"><i class="fa-regular fa-clock me-1"></i> <?= $m['prep_time_minutes'] ?> mins</span>
                        </td>
                        <td>
                            <?php if ($m['is_available']): ?>
                                <span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-check me-1"></i> In Stock</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-xmark me-1"></i> Sold Out</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="restaurant-menu.php?toggle_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Toggle Availability">
                                <i class="fa-solid fa-toggle-<?= $m['is_available'] ? 'on text-success' : 'off text-danger' ?> fs-5"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Menu Item Modal -->
<div class="modal fade" id="newMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Item to Restaurant Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="restaurant-menu.php">
                <input type="hidden" name="add_menu_item" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Menu Category *</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Dish / Item Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Grilled Tiger Prawns" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Short Culinary Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Garlic butter sauce, served with jasmine rice">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Selling Price (<?= APP_CURRENCY ?>) *</label>
                            <input type="number" name="price" class="form-control" step="10" required placeholder="2200.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Cost Price (<?= APP_CURRENCY ?>)</label>
                            <input type="number" name="cost_price" class="form-control" step="10" placeholder="950.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Est. Prep Time (Minutes)</label>
                        <input type="number" name="prep_time_minutes" class="form-control" value="15" min="2" max="90">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Food Image Artwork</label>
                        <select name="image" class="form-select">
                            <option value="default_food.jpg">Default Gourmet Dish (default_food.jpg)</option>
                            <option value="calamari.jpg">Appetizer / Seafood (calamari.jpg)</option>
                            <option value="bruschetta.jpg">Bruschetta / Toast (bruschetta.jpg)</option>
                            <option value="tomato_soup.jpg">Soup / Cream (tomato_soup.jpg)</option>
                            <option value="salmon.jpg">Grilled Fish / Salmon (salmon.jpg)</option>
                            <option value="roast_chicken.jpg">Poultry / Roast Chicken (roast_chicken.jpg)</option>
                            <option value="biryani.jpg">Rice / Biryani Special (biryani.jpg)</option>
                            <option value="ribeye_steak.jpg">Beef / Ribeye Steak (ribeye_steak.jpg)</option>
                            <option value="lamb_chops.jpg">Lamb / Cutlets (lamb_chops.jpg)</option>
                            <option value="alfredo_pasta.jpg">Pasta / White Sauce (alfredo_pasta.jpg)</option>
                            <option value="seafood_pasta.jpg">Pasta / Red Marinara (seafood_pasta.jpg)</option>
                            <option value="tiramisu.jpg">Dessert / Tiramisu (tiramisu.jpg)</option>
                            <option value="lava_cake.jpg">Dessert / Lava Cake (lava_cake.jpg)</option>
                            <option value="smoothie.jpg">Drink / Fruit Smoothie (smoothie.jpg)</option>
                            <option value="cappuccino.jpg">Coffee / Cappuccino (cappuccino.jpg)</option>
                            <option value="old_fashioned.jpg">Cocktail / Whiskey (old_fashioned.jpg)</option>
                            <option value="mojito.jpg">Cocktail / Mojito (mojito.jpg)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Menu Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
