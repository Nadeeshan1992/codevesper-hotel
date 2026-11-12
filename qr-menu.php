<?php
/**
 * Contactless Digital Food & Beverage Menu for Restaurant Tables
 * CodeVesper Hotel & Restaurant Management Suite
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDbConnection();

// Read Table identifier from URL (?table=1 or ?t=1)
$tableParam = $_GET['table'] ?? ($_GET['t'] ?? '1');
$tableId = is_numeric($tableParam) ? (int)$tableParam : 1;

// Fetch Table Details
$tStmt = $db->prepare("SELECT * FROM dining_tables WHERE id = ?");
$tStmt->execute([$tableId]);
$table = $tStmt->fetch(PDO::FETCH_ASSOC);

// If not found by ID, try matching table_number (e.g. "T1" or "Table T1")
if (!$table) {
    $tStmt2 = $db->prepare("SELECT * FROM dining_tables WHERE table_number LIKE ? LIMIT 1");
    $tStmt2->execute(['%' . $tableParam . '%']);
    $table = $tStmt2->fetch(PDO::FETCH_ASSOC);
    if ($table) {
        $tableId = (int)$table['id'];
    }
}

// Fallback to first table if still not found
if (!$table) {
    $table = $db->query("SELECT * FROM dining_tables ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $tableId = $table ? (int)$table['id'] : 1;
}

// Fetch all categories and their available menu items
$categories = $db->query("
    SELECT c.*, COUNT(m.id) as item_count 
    FROM menu_categories c 
    JOIN menu_items m ON c.id = m.category_id 
    WHERE m.is_available = 1 
    GROUP BY c.id 
    ORDER BY c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$menuItems = $db->query("
    SELECT m.*, c.name as category_name
    FROM menu_items m
    JOIN menu_categories c ON m.category_id = c.id
    WHERE m.is_available = 1
    ORDER BY c.id ASC, m.price ASC
")->fetchAll(PDO::FETCH_ASSOC);

$itemsByCategory = [];
foreach ($menuItems as $item) {
    $itemsByCategory[$item['category_id']][] = $item;
}

// Category Icons mapping
$catIcons = [
    'Appetizers & Starters'  => 'fa-bowl-rice',
    'Main Course'            => 'fa-plate-wheat',
    'Grill & Steaks'         => 'fa-drumstick-bite',
    'Pasta & Italian'        => 'fa-wheat-awn',
    'Desserts'               => 'fa-ice-cream',
    'Beverages & Mocktails'  => 'fa-glass-water',
    'Bar & Cocktails'        => 'fa-martini-glass-citrus'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Digital Menu | <?= htmlspecialchars($table['table_number']) ?> &bull; <?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="apple-touch-icon" href="assets/images/logo.jpg">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --brand-primary: #0284c7;
            --brand-dark: #090e17;
            --brand-gold: #f59e0b;
            --bg-card: #ffffff;
            --text-dark: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #f8fafc;
            color: var(--text-dark);
            margin: 0;
            padding-bottom: 120px;
            -webkit-tap-highlight-color: transparent;
        }

        /* Top Luxury Hero Bar */
        .qr-hero-header {
            background: linear-gradient(135deg, #090e17 0%, #0f172a 100%);
            color: #ffffff;
            padding: 24px 20px 20px;
            border-bottom: 2px solid rgba(245, 158, 11, 0.4);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        }

        .hotel-brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 2px;
            letter-spacing: -0.3px;
        }

        .hotel-brand-title span {
            color: #38bdf8;
            font-style: italic;
        }

        .table-pill-badge {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.5);
            color: #fbbf24;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Search input */
        .search-container {
            margin-top: 14px;
        }
        .search-input-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-radius: 50px;
            padding: 10px 18px 10px 42px;
            font-size: 0.9rem;
            width: 100%;
            transition: all 0.2s ease;
        }
        .search-input-box::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .search-input-box:focus {
            background: rgba(255, 255, 255, 0.14);
            border-color: #38bdf8;
            outline: none;
            color: #ffffff;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.3);
        }

        /* Category Scroll Strip */
        .category-nav-strip {
            background: #ffffff;
            padding: 10px 16px;
            overflow-x: auto;
            white-space: nowrap;
            position: sticky;
            top: 148px;
            z-index: 90;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
        }
        .category-nav-strip::-webkit-scrollbar { display: none; }

        .cat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 50px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        .cat-chip:hover, .cat-chip.active {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
        }

        /* Dish Cards */
        .category-section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin: 28px 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .food-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
            display: flex;
            position: relative;
            transition: all 0.2s ease;
        }
        .food-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }

        .food-img-wrapper {
            width: 120px;
            min-width: 120px;
            height: 125px;
            position: relative;
            overflow: hidden;
            background: #0f172a;
        }
        .food-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .food-card:hover .food-img-wrapper img {
            transform: scale(1.05);
        }

        .food-details {
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
        }

        .food-title {
            font-weight: 700;
            font-size: 0.96rem;
            color: #0f172a;
            margin-bottom: 2px;
            line-height: 1.25;
        }

        .food-desc {
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .food-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }

        .food-price {
            font-weight: 800;
            color: #0284c7;
            font-size: 0.98rem;
        }

        /* Add / Quantity Stepper */
        .btn-add-dish {
            background: #0284c7;
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .btn-add-dish:hover {
            background: #0369a1;
            transform: translateY(-1px);
        }

        .qty-stepper {
            display: none;
            align-items: center;
            background: #f1f5f9;
            border: 1.5px solid #0284c7;
            border-radius: 50px;
            overflow: hidden;
        }
        .qty-btn {
            background: transparent;
            border: none;
            color: #0284c7;
            width: 28px;
            height: 28px;
            font-size: 0.9rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .qty-val {
            font-weight: 800;
            font-size: 0.85rem;
            min-width: 20px;
            text-align: center;
            color: #0f172a;
        }

        /* Floating Bottom Cart Bar */
        .floating-cart-bar {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            max-width: 520px;
            margin: 0 auto;
            background: linear-gradient(135deg, #090e17 0%, #0f172a 100%);
            border: 1.5px solid rgba(245, 158, 11, 0.6);
            border-radius: 50px;
            padding: 12px 20px;
            color: #ffffff;
            display: none;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            z-index: 1000;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .floating-cart-bar:active {
            transform: scale(0.98);
        }

        .cart-badge-count {
            background: #f59e0b;
            color: #090e17;
            font-weight: 800;
            font-size: 0.8rem;
            padding: 3px 10px;
            border-radius: 50px;
        }

        .btn-checkout-glow {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #090e17;
            border: none;
            border-radius: 50px;
            padding: 8px 18px;
            font-weight: 800;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }

        /* Order Placed Success Screen */
        .order-success-screen {
            display: none;
            position: fixed;
            inset: 0;
            background: #ffffff;
            z-index: 2000;
            overflow-y: auto;
            padding: 40px 20px;
            text-align: center;
        }
        .success-check-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 20px;
            box-shadow: 0 0 0 10px rgba(22, 163, 74, 0.1);
        }
    </style>
</head>
<body>

<!-- Top Luxury Hero Bar -->
<header class="qr-hero-header">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <img src="assets/images/logo.jpg" alt="Logo" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 1.5px solid #f59e0b;">
            <div>
                <h1 class="hotel-brand-title"><?= APP_NAME ?> <span>Restaurant</span></h1>
                <small class="text-white-50" style="font-size: 0.72rem;">Ella Hide View Hotel &bull; Contactless Dining</small>
            </div>
        </div>
        <div class="table-pill-badge">
            <i class="fa-solid fa-chair"></i>
            <span><?= htmlspecialchars($table['table_number']) ?></span>
        </div>
    </div>

    <!-- Search Input -->
    <div class="search-container position-relative">
        <i class="fa-solid fa-magnifying-glass position-absolute text-white-50" style="left: 16px; top: 13px;"></i>
        <input type="text" id="menuSearchInput" class="search-input-box" placeholder="Search dishes, drinks, desserts..." onkeyup="filterMenuSearch()">
    </div>
</header>

<!-- Horizontal Category Nav Strip -->
<nav class="category-nav-strip">
    <a href="#all" class="cat-chip active" onclick="scrollToCategory('all', event)">
        <i class="fa-solid fa-utensils"></i> All Dishes
    </a>
    <?php foreach ($categories as $cat): 
        $icon = $catIcons[$cat['name']] ?? 'fa-bowl-food';
    ?>
        <a href="#cat-<?= $cat['id'] ?>" class="cat-chip" onclick="scrollToCategory('cat-<?= $cat['id'] ?>', event)">
            <i class="fa-solid <?= $icon ?>"></i> <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<!-- Main Menu Content Container -->
<main class="container py-2" style="max-width: 680px;">

    <!-- Table Info Banner -->
    <div class="p-3 bg-white rounded-4 border my-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-5">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <strong class="text-dark d-block">Ordering for <?= htmlspecialchars($table['table_number']) ?></strong>
                <small class="text-muted"><?= htmlspecialchars($table['section'] ?? 'Main Dining Area') ?> &bull; Capacity: <?= $table['capacity'] ?> Guests</small>
            </div>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
            <i class="fa-solid fa-circle-check me-1"></i> Connected
        </span>
    </div>

    <!-- Menu Items Grouped By Categories -->
    <?php foreach ($categories as $cat): 
        $items = $itemsByCategory[$cat['id']] ?? [];
        if (empty($items)) continue;
        $icon = $catIcons[$cat['name']] ?? 'fa-bowl-food';
    ?>
        <div class="category-block" id="cat-<?= $cat['id'] ?>">
            <div class="category-section-title">
                <i class="fa-solid <?= $icon ?> text-primary small"></i>
                <span><?= htmlspecialchars($cat['name']) ?></span>
                <span class="badge bg-light text-muted border fs-6 fw-normal ms-2" style="font-size: 0.7rem !important;"><?= count($items) ?> items</span>
            </div>

            <?php foreach ($items as $item): 
                $imgName = $item['image'] ?: 'default_food.jpg';
                $imgPath = file_exists(__DIR__ . '/assets/images/foods/' . $imgName) 
                           ? 'assets/images/foods/' . $imgName 
                           : 'assets/images/foods/default_food.jpg';
            ?>
                <div class="food-card" data-dish-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>" data-desc="<?= htmlspecialchars(strtolower($item['description'] ?? '')) ?>">
                    <div class="food-img-wrapper">
                        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                    </div>
                    <div class="food-details">
                        <div>
                            <div class="food-title"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="food-desc"><?= htmlspecialchars($item['description'] ?: 'Prepared fresh by Ella Hide View culinary chefs.') ?></div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">
                                    <i class="fa-regular fa-clock me-1"></i><?= $item['prep_time_minutes'] ?? 15 ?>m
                                </span>
                            </div>
                        </div>

                        <div class="food-meta-row">
                            <div class="food-price"><?= formatMoney($item['price']) ?></div>
                            <div>
                                <!-- Initial Add Button -->
                                <button type="button" class="btn-add-dish" id="addBtn-<?= $item['id'] ?>" onclick="addToCart(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>

                                <!-- Stepper when item > 0 -->
                                <div class="qty-stepper" id="stepper-<?= $item['id'] ?>">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $item['id'] ?>, -1)">&minus;</button>
                                    <span class="qty-val" id="qtyVal-<?= $item['id'] ?>">1</span>
                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $item['id'] ?>, 1)">&plus;</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</main>

<!-- Floating Bottom Cart Bar -->
<div class="floating-cart-bar" id="floatingCartBar" onclick="openCartModal()">
    <div class="d-flex align-items-center gap-3">
        <span class="cart-badge-count" id="cartBadgeCount">0 items</span>
        <div class="fw-bold fs-6" id="cartTotalDisplay">LKR 0.00</div>
    </div>
    <button type="button" class="btn-checkout-glow">
        <span>View Order</span>
        <i class="fa-solid fa-arrow-right"></i>
    </button>
</div>

<!-- Modal: Cart & Checkout Drawer -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary rounded-3 text-white">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Your Table Order</h5>
                        <small class="text-white-50"><?= htmlspecialchars($table['table_number']) ?> &bull; Ella Hide View Restaurant</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Cart Items List -->
                <div id="cartItemsContainer" class="mb-3">
                    <!-- Populated dynamically by JS -->
                </div>

                <!-- Totals Calculation -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Items Subtotal:</span>
                        <strong class="text-dark" id="modalSubtotal">LKR 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Hospitality Service & Tax (10%):</span>
                        <strong class="text-dark" id="modalTax">LKR 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-primary pt-2 border-top">
                        <span>Grand Total:</span>
                        <span id="modalGrandTotal">LKR 0.00</span>
                    </div>
                </div>

                <!-- Guest & Special Requests Info -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Guest Name (Optional)</label>
                    <input type="text" id="guestNameInput" class="form-control rounded-3" placeholder="e.g. John / Sarah">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Room Number (If staying in hotel)</label>
                    <input type="text" id="guestRoomInput" class="form-control rounded-3" placeholder="e.g. 101 or 205 (Optional)">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Special Chef Instructions</label>
                    <textarea id="orderNotesInput" class="form-control rounded-3" rows="2" placeholder="e.g. Less spicy, dressing on the side, no ice in drinks..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Add More Food</button>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" id="btnSubmitOrder" onclick="submitOrderToKitchen()">
                    <i class="fa-solid fa-paper-plane me-1"></i> Send Order to Kitchen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Order Placed Success Screen -->
<div class="order-success-screen" id="orderSuccessScreen">
    <div style="max-width: 480px; margin: 40px auto 0;">
        <div class="success-check-circle">
            <i class="fa-solid fa-check"></i>
        </div>
        <h2 class="fw-bold text-dark mb-1">Order Sent to Kitchen!</h2>
        <p class="text-muted small mb-4">Our chefs have received your ticket and are preparing your meal fresh.</p>

        <div class="p-4 bg-light rounded-4 border text-start mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Order Reference:</span>
                <strong class="text-primary font-monospace fs-5" id="confirmedOrderNum">QR-0000</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Dining Table:</span>
                <strong class="text-dark" id="confirmedTableNum"><?= htmlspecialchars($table['table_number']) ?></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Order Status:</span>
                <span class="badge bg-warning text-dark"><i class="fa-solid fa-fire me-1"></i> Cooking in Kitchen</span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="text-muted small">Total Bill:</span>
                <strong class="text-success fs-5" id="confirmedTotal">LKR 0.00</strong>
            </div>
        </div>

        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 small mb-4">
            <i class="fa-solid fa-bell me-1"></i> Your food will be served directly to <strong><?= htmlspecialchars($table['table_number']) ?></strong>. You can settle the bill via Cash, Card, or Room Charge upon departure.
        </div>

        <div class="d-grid gap-2">
            <button type="button" class="btn btn-primary rounded-pill py-2 fw-bold" onclick="resetForMoreOrders()">
                <i class="fa-solid fa-plus me-1"></i> Order Additional Drinks or Desserts
            </button>
        </div>
    </div>
</div>

<!-- Core Cart & Interactivity Logic -->
<script>
const TABLE_ID = <?= $tableId ?>;
const TABLE_NAME = "<?= htmlspecialchars(addslashes($table['table_number'])) ?>";

// Cart state: { dishId: { id, name, price, qty, instructions } }
let cart = {};

function addToCart(id, name, price) {
    if (!cart[id]) {
        cart[id] = { id: id, name: name, price: price, qty: 1, instructions: '' };
    } else {
        cart[id].qty++;
    }
    updateUI();
}

function updateQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) {
        delete cart[id];
    }
    updateUI();
}

function updateItemNote(id, note) {
    if (cart[id]) {
        cart[id].instructions = note;
    }
}

function updateUI() {
    let totalItems = 0;
    let subtotal = 0;

    // Reset all item cards
    document.querySelectorAll('.food-card').forEach(card => {
        const dishId = card.getAttribute('data-dish-id');
        const addBtn = document.getElementById('addBtn-' + dishId);
        const stepper = document.getElementById('stepper-' + dishId);
        const qtyVal = document.getElementById('qtyVal-' + dishId);

        if (cart[dishId]) {
            addBtn.style.display = 'none';
            stepper.style.display = 'inline-flex';
            qtyVal.innerText = cart[dishId].qty;
        } else {
            addBtn.style.display = 'inline-flex';
            stepper.style.display = 'none';
        }
    });

    // Calculate totals
    for (let id in cart) {
        totalItems += cart[id].qty;
        subtotal += (cart[id].price * cart[id].qty);
    }

    const cartBar = document.getElementById('floatingCartBar');
    const badge = document.getElementById('cartBadgeCount');
    const totalDisplay = document.getElementById('cartTotalDisplay');

    if (totalItems > 0) {
        cartBar.style.display = 'flex';
        badge.innerText = totalItems + (totalItems === 1 ? ' dish' : ' dishes');
        totalDisplay.innerText = 'LKR ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2});
    } else {
        cartBar.style.display = 'none';
    }
}

function openCartModal() {
    const container = document.getElementById('cartItemsContainer');
    container.innerHTML = '';

    let subtotal = 0;
    let hasItems = false;

    for (let id in cart) {
        hasItems = true;
        const item = cart[id];
        const lineTotal = item.price * item.qty;
        subtotal += lineTotal;

        const row = document.createElement('div');
        row.className = 'p-3 bg-light rounded-3 border mb-2';
        row.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong class="text-dark fs-6 d-block">${item.name}</strong>
                    <small class="text-muted">LKR ${item.price.toLocaleString('en-US', {minimumFractionDigits: 2})} each</small>
                </div>
                <div class="text-end">
                    <strong class="text-primary fs-6">LKR ${lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                <input type="text" class="form-control form-control-sm me-2 rounded-pill" placeholder="Item note (e.g. less spicy)..." value="${item.instructions || ''}" onchange="updateItemNote(${item.id}, this.value)">
                <div class="qty-stepper" style="display: inline-flex;">
                    <button type="button" class="qty-btn" onclick="updateQty(${item.id}, -1); openCartModal();">&minus;</button>
                    <span class="qty-val">${item.qty}</span>
                    <button type="button" class="qty-btn" onclick="updateQty(${item.id}, 1); openCartModal();">&plus;</button>
                </div>
            </div>
        `;
        container.appendChild(row);
    }

    if (!hasItems) {
        container.innerHTML = '<p class="text-center text-muted py-4">Your cart is currently empty.</p>';
    }

    const tax = Math.round(subtotal * 0.10 * 100) / 100;
    const grand = subtotal + tax;

    document.getElementById('modalSubtotal').innerText = 'LKR ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('modalTax').innerText = 'LKR ' + tax.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('modalGrandTotal').innerText = 'LKR ' + grand.toLocaleString('en-US', {minimumFractionDigits: 2});

    const modal = new bootstrap.Modal(document.getElementById('cartModal'));
    modal.show();
}

function submitOrderToKitchen() {
    const itemsList = Object.values(cart);
    if (itemsList.length === 0) {
        Swal.fire('Empty Cart', 'Please add dishes to your order first.', 'warning');
        return;
    }

    const submitBtn = document.getElementById('btnSubmitOrder');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending to Chef...';

    const formData = new FormData();
    formData.append('table_id', TABLE_ID);
    formData.append('guest_name', document.getElementById('guestNameInput').value.trim() || 'Guest (' + TABLE_NAME + ')');
    formData.append('guest_room', document.getElementById('guestRoomInput').value.trim());
    formData.append('notes', document.getElementById('orderNotesInput').value.trim());
    formData.append('items', JSON.stringify(itemsList));

    fetch('api/qr-order.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Order to Kitchen';

        if (data.success) {
            // Close modal
            const modalEl = document.getElementById('cartModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Populate confirmed screen
            document.getElementById('confirmedOrderNum').innerText = data.order_number;
            document.getElementById('confirmedTableNum').innerText = data.table_number;
            document.getElementById('confirmedTotal').innerText = 'LKR ' + parseFloat(data.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2});

            // Show success screen
            document.getElementById('orderSuccessScreen').style.display = 'block';

            // Clear cart
            cart = {};
            updateUI();
        } else {
            Swal.fire('Error', data.message || 'Could not place order', 'error');
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Order to Kitchen';
        Swal.fire('Connection Error', 'Please check your internet connection and try again.', 'error');
    });
}

function resetForMoreOrders() {
    document.getElementById('orderSuccessScreen').style.display = 'none';
}

function filterMenuSearch() {
    const query = document.getElementById('menuSearchInput').value.toLowerCase().trim();
    document.querySelectorAll('.food-card').forEach(card => {
        const name = card.getAttribute('data-name');
        const desc = card.getAttribute('data-desc');
        if (name.includes(query) || desc.includes(query)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function scrollToCategory(catId, e) {
    if (e) e.preventDefault();
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    if (e && e.currentTarget) e.currentTarget.classList.add('active');

    if (catId === 'all') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        const target = document.getElementById(catId);
        if (target) {
            const offset = 190;
            const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
    }
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
