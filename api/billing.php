<?php
/**
 * API: Guest Folio & Checkout Settlement
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

if ($action === 'settle_checkout') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';
    $notes = sanitize($_POST['notes'] ?? '');

    try {
        $db->beginTransaction();

        // Find active check-in for room
        $ciStmt = $db->prepare("
            SELECT ci.*, r.room_number, r.daily_rate, g.id as guest_id, g.full_name as guest_name, res.id as res_id
            FROM check_ins ci
            JOIN rooms r ON ci.room_id = r.id
            JOIN guests g ON ci.guest_id = g.id
            LEFT JOIN reservations res ON ci.reservation_id = res.id
            WHERE ci.room_id = ? AND ci.status = 'active'
            LIMIT 1
        ");
        $ciStmt->execute([$roomId]);
        $checkin = $ciStmt->fetch();

        if (!$checkin) {
            throw new Exception("No active check-in found for this room.");
        }

        // Calculate nights stayed (at least 1 night)
        $checkinTime = strtotime($checkin['check_in_time']);
        $now = time();
        $diffSeconds = max(0, $now - $checkinTime);
        $nights = max(1, ceil($diffSeconds / (24 * 3600)));
        $roomCharges = $nights * (float)$checkin['daily_rate'];

        // Calculate restaurant charges charged to room
        $orderStmt = $db->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total_fnb
            FROM orders
            WHERE room_id = ? AND payment_status = 'billed_to_room'
        ");
        $orderStmt->execute([$roomId]);
        $fnbCharges = (float)$orderStmt->fetchColumn();

        $subtotal = $roomCharges + $fnbCharges;
        $taxAmount = round($subtotal * 0.10, 2);
        $totalAmount = $subtotal + $taxAmount;
        $depositPaid = (float)$checkin['deposit_paid'];
        $netPayable = max(0, $totalAmount - $depositPaid);

        // Generate Master Invoice
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
        $invStmt = $db->prepare("
            INSERT INTO invoices (
                invoice_number, guest_id, room_id, reservation_id, room_charges, 
                restaurant_charges, tax_amount, total_amount, paid_amount, balance_due, 
                payment_status, generated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 'paid', ?)
        ");
        $invStmt->execute([
            $invoiceNumber, $checkin['guest_id'], $roomId, $checkin['res_id'],
            $roomCharges, $fnbCharges, $taxAmount, $totalAmount,
            $totalAmount, $_SESSION['user_id']
        ]);
        $invoiceId = (int)$db->lastInsertId();

        // Record final payment transaction
        if ($netPayable > 0) {
            $payStmt = $db->prepare("
                INSERT INTO payments (invoice_id, amount, payment_method, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $payStmt->execute([$invoiceId, $netPayable, $paymentMethod, $notes, $_SESSION['user_id']]);
        }

        // Mark check-in completed
        $db->prepare("UPDATE check_ins SET status = 'completed', actual_check_out = NOW() WHERE id = ?")->execute([$checkin['id']]);

        // Mark associated reservation checked out
        if ($checkin['res_id']) {
            $db->prepare("UPDATE reservations SET status = 'checked_out' WHERE id = ?")->execute([$checkin['res_id']]);
        }

        // Mark restaurant orders billed
        $db->prepare("UPDATE orders SET payment_status = 'paid', status = 'billed' WHERE room_id = ? AND payment_status = 'billed_to_room'")->execute([$roomId]);

        // AUTOMATIC TRIGGER: Room becomes DIRTY for Housekeeping
        $db->prepare("UPDATE rooms SET status = 'dirty' WHERE id = ?")->execute([$roomId]);

        logAudit('Billing', 'Folio Checkout', "Checked out guest {$checkin['guest_name']} from {$checkin['room_number']} with invoice $invoiceNumber. Room set to Dirty.");

        $db->commit();

        echo json_encode([
            'success' => true,
            'invoice_number' => $invoiceNumber,
            'invoice_id' => $invoiceId,
            'message' => "Checkout settled! Room set to Dirty for housekeeping."
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
