<?php
/**
 * API: Stock & Inventory Management Operations
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDbConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'add_movement') {
    $itemId = (int)($_POST['stock_item_id'] ?? 0);
    $type = $_POST['movement_type'] ?? 'purchase_in';
    $qty = (float)($_POST['quantity'] ?? 0);
    $unitPrice = (float)($_POST['unit_price'] ?? 0);
    $note = sanitize($_POST['reference_note'] ?? '');

    if ($itemId <= 0 || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item or quantity.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Check item exists
        $item = $db->query("SELECT * FROM stock_items WHERE id = $itemId")->fetch();
        if (!$item) throw new Exception("Stock item not found.");

        $currentStock = (float)$item['current_stock'];
        $newStock = $currentStock;

        if ($type === 'purchase_in' || $type === 'adjustment') {
            $newStock = $currentStock + $qty;
        } else {
            // kitchen_out, room_out, wastage
            if ($qty > $currentStock) {
                throw new Exception("Cannot deduct more than available stock ($currentStock {$item['unit']}).");
            }
            $newStock = $currentStock - $qty;
        }

        // Update item stock
        $upStmt = $db->prepare("UPDATE stock_items SET current_stock = ?, cost_per_unit = IF(? > 0, ?, cost_per_unit) WHERE id = ?");
        $upStmt->execute([$newStock, $unitPrice, $unitPrice, $itemId]);

        // Insert movement record
        $movStmt = $db->prepare("
            INSERT INTO stock_movements (stock_item_id, movement_type, quantity, unit_price, reference_note, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $movStmt->execute([$itemId, $type, $qty, $unitPrice, $note, $_SESSION['user_id']]);

        logAudit('Stock', 'Movement Recorded', "Logged $type of $qty {$item['unit']} for {$item['name']}");

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Inventory updated successfully!']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'create_item') {
    $catId = (int)($_POST['category_id'] ?? 1);
    $name = sanitize($_POST['name'] ?? '');
    $unit = sanitize($_POST['unit'] ?? 'pcs');
    $stock = (float)($_POST['current_stock'] ?? 0);
    $minStock = (float)($_POST['minimum_stock'] ?? 5);
    $cost = (float)($_POST['cost_per_unit'] ?? 0);
    $supplier = sanitize($_POST['supplier'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required.']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO stock_items (category_id, name, unit, current_stock, minimum_stock, cost_per_unit, supplier)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$catId, $name, $unit, $stock, $minStock, $cost, $supplier]);

        logAudit('Stock', 'Item Created', "Created new stock item: $name");

        echo json_encode(['success' => true, 'message' => 'New stock item created successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
