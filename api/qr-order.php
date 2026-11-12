<?php
/**
 * Public API: Guest Table QR Self-Ordering Endpoint
 * CodeVesper Hotel & Restaurant Management Suite
 */
header('Content-Type: application/json');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$tableId = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : 0;
$guestName = sanitize($_POST['guest_name'] ?? 'Table Guest');
$guestRoom = sanitize($_POST['guest_room'] ?? '');
$customerNotes = sanitize($_POST['notes'] ?? '');
$itemsJson = $_POST['items'] ?? '[]';
$items = json_decode($itemsJson, true);

if ($tableId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid table identifier. Please re-scan your table QR code.']);
    exit;
}

// 1. Verify Table exists
$tStmt = $db->prepare("SELECT * FROM dining_tables WHERE id = ?");
$tStmt->execute([$tableId]);
$table = $tStmt->fetch(PDO::FETCH_ASSOC);

if (!$table) {
    echo json_encode(['success' => false, 'message' => 'Dining table not found in hotel records.']);
    exit;
}

// 2. Validate Items
if (empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty. Please select at least one dish.']);
    exit;
}

try {
    $db->beginTransaction();

    // Collect Item IDs & quantities
    $itemIds = [];
    $itemQtys = [];
    $itemInstructions = [];

    foreach ($items as $it) {
        $mid = (int)($it['id'] ?? 0);
        $qty = max(1, (int)($it['qty'] ?? 1));
        $inst = sanitize($it['instructions'] ?? '');

        if ($mid > 0) {
            $itemIds[] = $mid;
            $itemQtys[$mid] = $qty;
            $itemInstructions[$mid] = $inst;
        }
    }

    if (empty($itemIds)) {
        throw new Exception("No valid dishes found in cart.");
    }

    // 3. Fetch current live prices from menu_items to prevent client-side tampering
    $inClause = implode(',', array_fill(0, count($itemIds), '?'));
    $mStmt = $db->prepare("SELECT id, name, price, is_available FROM menu_items WHERE id IN ($inClause)");
    $mStmt->execute($itemIds);
    $liveItems = $mStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($liveItems)) {
        throw new Exception("Selected dishes are currently not in our live menu.");
    }

    $subtotal = 0.00;
    $verifiedOrderItems = [];

    foreach ($liveItems as $m) {
        $mId = (int)$m['id'];
        $qty = $itemQtys[$mId] ?? 1;
        $price = (float)$m['price'];
        $lineSubtotal = $price * $qty;
        $subtotal += $lineSubtotal;

        $verifiedOrderItems[] = [
            'menu_item_id' => $mId,
            'name'         => $m['name'],
            'quantity'     => $qty,
            'unit_price'   => $price,
            'subtotal'     => $lineSubtotal,
            'instructions' => $itemInstructions[$mId] ?? ''
        ];
    }

    $taxAmount = round($subtotal * 0.10, 2); // 10% hospitality service charge & tax
    $totalAmount = $subtotal + $taxAmount;

    // 4. Check if Room Number provided links to an active in-house guest
    $guestId = null;
    $roomId = null;
    if (!empty($guestRoom)) {
        $rStmt = $db->prepare("
            SELECT r.id as room_id, c.guest_id 
            FROM rooms r 
            LEFT JOIN check_ins c ON r.id = c.room_id AND c.status = 'active'
            WHERE r.room_number = ? OR r.room_number = ?
            LIMIT 1
        ");
        $rStmt->execute([$guestRoom, 'Room ' . $guestRoom]);
        $roomRow = $rStmt->fetch(PDO::FETCH_ASSOC);
        if ($roomRow) {
            $roomId = $roomRow['room_id'];
            $guestId = $roomRow['guest_id'] ?? null;
        }
    }

    // 5. Generate Order Reference
    $orderNumber = 'QR-' . date('ymd') . '-' . rand(1000, 9999);
    $notes = "📱 [Table QR Self-Order] Guest: $guestName";
    if (!empty($guestRoom)) {
        $notes .= " | Room: $guestRoom";
    }
    if (!empty($customerNotes)) {
        $notes .= " | Notes: $customerNotes";
    }

    // 6. Insert Order
    $orderStmt = $db->prepare("
        INSERT INTO orders (
            order_number, order_type, table_id, room_id, guest_id, 
            waiter_id, status, subtotal, tax_amount, discount_amount, 
            total_amount, payment_status, payment_method, notes, created_at
        ) VALUES (
            ?, 'dine_in', ?, ?, ?, 
            NULL, 'in_kitchen', ?, ?, 0.00, 
            ?, 'unpaid', 'Cash / Card', ?, NOW()
        )
    ");
    $orderStmt->execute([
        $orderNumber,
        $tableId,
        $roomId,
        $guestId,
        $subtotal,
        $taxAmount,
        $totalAmount,
        $notes
    ]);
    $orderId = (int)$db->lastInsertId();

    // 7. Insert Order Items (KOT Items)
    $itemStmt = $db->prepare("
        INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal, special_instructions, kot_status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");

    foreach ($verifiedOrderItems as $voi) {
        $itemStmt->execute([
            $orderId,
            $voi['menu_item_id'],
            $voi['quantity'],
            $voi['unit_price'],
            $voi['subtotal'],
            $voi['instructions']
        ]);
    }

    // 8. Set Table Status to Occupied
    $db->prepare("UPDATE dining_tables SET status = 'occupied' WHERE id = ?")->execute([$tableId]);

    // 9. Log Audit Entry
    logAudit('Restaurant', 'QR Order Placed', "Guest placed QR order $orderNumber at {$table['table_number']} (Total: LKR " . number_format($totalAmount, 2) . ")");

    $db->commit();

    echo json_encode([
        'success'       => true,
        'order_id'      => $orderId,
        'order_number'  => $orderNumber,
        'table_number'  => $table['table_number'],
        'section'       => $table['section'] ?? 'Dining Area',
        'subtotal'      => $subtotal,
        'tax_amount'    => $taxAmount,
        'total_amount'  => $totalAmount,
        'items_count'   => count($verifiedOrderItems),
        'created_at'    => date('h:i A')
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
}
