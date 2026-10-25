<?php
/**
 * API: Restaurant Orders & Tablet POS Handler
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

// 1. Create or Submit New Order
if ($action === 'create_order') {
    $orderType = $_POST['order_type'] ?? 'dine_in';
    $tableId = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : null;
    $roomId = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;
    $notes = sanitize($_POST['notes'] ?? '');
    $itemsJson = $_POST['items'] ?? '[]';
    $items = json_decode($itemsJson, true);
    $waiterId = (int)$_SESSION['user_id'];
    $paymentMethod = $_POST['payment_method'] ?? 'unpaid';
    $isRoomCharge = !empty($_POST['charge_to_room']);

    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'Please add at least one item to the order.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // If Room Service, find active guest
        $guestId = null;
        if ($roomId) {
            $guestStmt = $db->prepare("
                SELECT c.guest_id 
                FROM check_ins c 
                WHERE c.room_id = ? AND c.status = 'active' 
                ORDER BY c.id DESC LIMIT 1
            ");
            $guestStmt->execute([$roomId]);
            $guest = $guestStmt->fetch();
            if ($guest) {
                $guestId = $guest['guest_id'];
            }
        }

        // Calculate totals
        $subtotal = 0.00;
        foreach ($items as $item) {
            $subtotal += ((float)$item['price'] * (int)$item['qty']);
        }
        $taxAmount = round($subtotal * 0.10, 2); // 10% tax
        $totalAmount = $subtotal + $taxAmount;

        // Generate Order Number
        $orderNumber = 'ORD-' . date('ymd') . '-' . rand(1000, 9999);
        $orderStatus = 'in_kitchen';
        $paymentStatus = $isRoomCharge ? 'billed_to_room' : ($paymentMethod !== 'unpaid' ? 'paid' : 'unpaid');

        // Insert Master Order
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, order_type, table_id, room_id, guest_id, 
                waiter_id, status, subtotal, tax_amount, total_amount, 
                payment_status, payment_method, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderNumber, $orderType, $tableId, $roomId, $guestId,
            $waiterId, $orderStatus, $subtotal, $taxAmount, $totalAmount,
            $paymentStatus, $paymentMethod, $notes
        ]);
        $orderId = (int)$db->lastInsertId();

        // Insert Order Items
        $itemStmt = $db->prepare("
            INSERT INTO order_items (
                order_id, menu_item_id, quantity, unit_price, subtotal, special_instructions, kot_status
            ) VALUES (?, ?, ?, ?, ?, ?, 'cooking')
        ");

        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $qty = (int)$item['qty'];
            $price = (float)$item['price'];
            $itemTotal = $price * $qty;
            $instruction = sanitize($item['notes'] ?? '');

            $itemStmt->execute([$orderId, $itemId, $qty, $price, $itemTotal, $instruction]);
        }

        // If table occupied, update table status
        if ($tableId) {
            $db->prepare("UPDATE dining_tables SET status = 'occupied' WHERE id = ?")->execute([$tableId]);
        }

        // If direct payment recorded
        if ($paymentStatus === 'paid') {
            $payStmt = $db->prepare("
                INSERT INTO payments (order_id, amount, payment_method, recorded_by)
                VALUES (?, ?, ?, ?)
            ");
            $payStmt->execute([$orderId, $totalAmount, $paymentMethod, $waiterId]);
        }

        // If billed to room, create/update folio note
        if ($isRoomCharge && $roomId) {
            logAudit('POS', 'Room Dining Charged', "Order $orderNumber of $" . number_format($totalAmount, 2) . " charged to Room ID $roomId");
        } else {
            logAudit('POS', 'Order Placed', "Order $orderNumber created by Waiter " . $_SESSION['username']);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'message' => 'Order successfully sent to kitchen! KOT Generated.'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to process order: ' . $e->getMessage()]);
    }
    exit;
}

// 2. Update KOT Status (Kitchen screen interaction)
if ($action === 'update_kot_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowedStatuses = ['in_kitchen', 'ready', 'served', 'billed', 'cancelled'];
    if (!in_array($status, $allowedStatuses, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);

        // If served or billed and has table, optionally free table
        if ($status === 'billed') {
            $order = $db->query("SELECT table_id FROM orders WHERE id = $orderId")->fetch();
            if ($order && $order['table_id']) {
                $db->prepare("UPDATE dining_tables SET status = 'available' WHERE id = ?")->execute([$order['table_id']]);
            }
        }

        logAudit('KOT', 'Status Changed', "Order #$orderId changed status to $status");

        echo json_encode(['success' => true, 'message' => "Order updated to " . ucfirst($status)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
