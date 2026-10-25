<?php
/**
 * API: Room Operations & Housekeeping Updates
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

if ($action === 'update_status') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['vacant', 'reserved', 'occupied', 'dirty', 'maintenance'];
    if (!in_array($status, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid room status']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt->execute([$status, $roomId]);

        logAudit('Rooms', 'Status Changed', "Room ID $roomId status changed to $status by " . $_SESSION['username']);

        echo json_encode(['success' => true, 'message' => 'Room status updated successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'quick_checkin') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $guestName = sanitize($_POST['guest_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $idNumber = sanitize($_POST['id_number'] ?? '');
    $days = max(1, (int)($_POST['stay_days'] ?? 1));
    $deposit = (float)($_POST['deposit'] ?? 0.00);

    if (empty($guestName) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Guest Name and Phone are required.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Get room rate
        $room = $db->query("SELECT * FROM rooms WHERE id = $roomId")->fetch();
        if (!$room) throw new Exception("Room not found");
        $rate = (float)$room['daily_rate'];
        $totalRoomCost = $rate * $days;

        // 2. Insert or find Guest
        $gStmt = $db->prepare("INSERT INTO guests (full_name, phone, id_type, id_number) VALUES (?, ?, 'National ID', ?)");
        $gStmt->execute([$guestName, $phone, $idNumber ?: 'ID-' . rand(10000, 99999)]);
        $guestId = (int)$db->lastInsertId();

        // 3. Insert Reservation
        $ref = 'BK-' . rand(1000, 9999);
        $resStmt = $db->prepare("
            INSERT INTO reservations (booking_ref, guest_id, room_id, check_in_date, check_out_date, total_room_cost, advance_deposit, status, created_by)
            VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, 'checked_in', ?)
        ");
        $resStmt->execute([$ref, $guestId, $roomId, $days, $totalRoomCost, $deposit, $_SESSION['user_id']]);
        $reservationId = (int)$db->lastInsertId();

        // 4. Insert Active Check-In
        $ciStmt = $db->prepare("
            INSERT INTO check_ins (reservation_id, room_id, guest_id, check_in_time, check_out_time, daily_rate, deposit_paid, status, created_by)
            VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), ?, ?, 'active', ?)
        ");
        $ciStmt->execute([$reservationId, $roomId, $guestId, $days, $rate, $deposit, $_SESSION['user_id']]);

        // 5. Update Room to Occupied
        $db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$roomId]);

        logAudit('FrontDesk', 'Walk-in Check-in', "Checked in guest $guestName into " . $room['room_number']);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Guest checked in successfully!']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 2b. Check-in Confirmed Advance Reservation
if ($action === 'checkin_reservation') {
    $resId = (int)($_POST['reservation_id'] ?? 0);
    if ($resId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
        exit;
    }

    try {
        $db->beginTransaction();

        $resStmt = $db->prepare("SELECT * FROM reservations WHERE id = ?");
        $resStmt->execute([$resId]);
        $res = $resStmt->fetch();

        if (!$res) throw new Exception("Reservation not found");
        if ($res['status'] !== 'confirmed') throw new Exception("Reservation is already " . $res['status']);

        $roomId = (int)$res['room_id'];
        $roomStmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $roomStmt->execute([$roomId]);
        $room = $roomStmt->fetch();
        if (!$room) throw new Exception("Allocated room not found");

        $dailyRate = (float)$room['daily_rate'];
        $deposit = (float)$res['advance_deposit'];

        // Insert into check_ins
        $ciStmt = $db->prepare("
            INSERT INTO check_ins (reservation_id, room_id, guest_id, check_in_time, check_out_time, daily_rate, deposit_paid, status, created_by)
            VALUES (?, ?, ?, NOW(), ?, ?, ?, 'active', ?)
        ");
        $ciStmt->execute([
            $resId,
            $roomId,
            $res['guest_id'],
            $res['check_out_date'] . ' 12:00:00',
            $dailyRate,
            $deposit,
            $_SESSION['user_id']
        ]);

        // Mark reservation checked_in
        $db->prepare("UPDATE reservations SET status = 'checked_in' WHERE id = ?")->execute([$resId]);

        // Mark room occupied
        $db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$roomId]);

        logAudit('FrontDesk', 'Reservation Check-In', "Guest check-in processed for booking {$res['booking_ref']} into room {$room['room_number']}");

        $db->commit();
        echo json_encode(['success' => true, 'message' => "Guest checked into room {$room['room_number']} successfully!"]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 3. Add New Room Dynamically (Support Any Number of Rooms)
if ($action === 'create_room') {
    $roomNumber = sanitize($_POST['room_number'] ?? '');
    $roomTypeId = (int)($_POST['room_type_id'] ?? 1);
    $floor = max(1, (int)($_POST['floor'] ?? 1));
    $dailyRate = (float)($_POST['daily_rate'] ?? 0.00);
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($roomNumber) || $dailyRate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid Room Number and Daily Rate are required.']);
        exit;
    }

    try {
        // Check uniqueness
        $dupStmt = $db->prepare("SELECT id FROM rooms WHERE room_number = ? LIMIT 1");
        $dupStmt->execute([$roomNumber]);
        if ($dupStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => "Room '$roomNumber' already exists."]);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO rooms (room_number, room_type_id, floor, status, daily_rate, notes)
            VALUES (?, ?, ?, 'vacant', ?, ?)
        ");
        $stmt->execute([$roomNumber, $roomTypeId, $floor, $dailyRate, $notes]);
        $newRoomId = (int)$db->lastInsertId();

        logAudit('Rooms', 'Room Created', "Added new $roomNumber on floor $floor at $$dailyRate/night");

        echo json_encode(['success' => true, 'room_id' => $newRoomId, 'message' => "Room '$roomNumber' added successfully!"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 4. Update Existing Room Details
if ($action === 'update_room') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $roomNumber = sanitize($_POST['room_number'] ?? '');
    $roomTypeId = (int)($_POST['room_type_id'] ?? 1);
    $floor = max(1, (int)($_POST['floor'] ?? 1));
    $dailyRate = (float)($_POST['daily_rate'] ?? 0.00);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($roomId <= 0 || empty($roomNumber) || $dailyRate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid Room Number and Daily Rate are required.']);
        exit;
    }

    try {
        // Check duplicate room number
        $dupStmt = $db->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ? LIMIT 1");
        $dupStmt->execute([$roomNumber, $roomId]);
        if ($dupStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => "Another room with number '$roomNumber' already exists."]);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE rooms 
            SET room_number = ?, room_type_id = ?, floor = ?, daily_rate = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$roomNumber, $roomTypeId, $floor, $dailyRate, $notes, $roomId]);

        logAudit('Rooms', 'Room Updated', "Updated details for $roomNumber");

        echo json_encode(['success' => true, 'message' => "Room '$roomNumber' updated successfully!"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 5. Delete Room
if ($action === 'delete_room') {
    $roomId = (int)($_POST['room_id'] ?? 0);

    if ($roomId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Room ID.']);
        exit;
    }

    try {
        // Verify no active check-in
        $activeCheck = $db->prepare("SELECT id FROM check_ins WHERE room_id = ? AND status = 'active' LIMIT 1");
        $activeCheck->execute([$roomId]);
        if ($activeCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete room with active in-house guests. Please checkout guest first.']);
            exit;
        }

        $room = $db->query("SELECT room_number FROM rooms WHERE id = $roomId")->fetch();
        $roomNumber = $room ? $room['room_number'] : "ID #$roomId";

        // Remove room
        $del = $db->prepare("DELETE FROM rooms WHERE id = ?");
        $del->execute([$roomId]);

        logAudit('Rooms', 'Room Deleted', "Deleted $roomNumber from property inventory");

        echo json_encode(['success' => true, 'message' => "Room '$roomNumber' deleted from system."]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete room with existing billing or history records: ' . $e->getMessage()]);
    }
    exit;
}

// 6. Create New Custom Room Type / Tier
if ($action === 'create_room_type') {
    $name = sanitize($_POST['name'] ?? '');
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $basePrice = (float)($_POST['base_price'] ?? 0.00);
    $capacity = max(1, (int)($_POST['capacity'] ?? 2));
    $amenities = sanitize($_POST['amenities'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name) || empty($code) || $basePrice <= 0) {
        echo json_encode(['success' => false, 'message' => 'Name, unique code, and base price are required.']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO room_types (name, code, base_price, capacity, amenities, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $code, $basePrice, $capacity, $amenities, $description]);
        $typeId = (int)$db->lastInsertId();

        logAudit('Rooms', 'Room Type Created', "Created room tier $name ($code) at $$basePrice");

        echo json_encode(['success' => true, 'room_type_id' => $typeId, 'message' => "Room Type '$name' created successfully!"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
