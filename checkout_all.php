<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Lấy các phòng trong giỏ của user và đang available
$sql = "SELECT r.id, r.room_number, r.rent_price FROM carts c JOIN rooms r ON c.room_id = r.id WHERE c.user_id = ? AND r.status = 'available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $_SESSION['error_message'] = 'Giỏ hàng không có phòng trống để giữ chỗ.';
    header('Location: cart.php');
    exit();
}

$rooms = [];
$total_amount = 0;
while ($row = $res->fetch_assoc()) {
    $fee = (int) round(max($row['rent_price'] * 0.10, 500000));
    $rooms[] = [
        'room_id' => (int)$row['id'],
        'room_number' => $row['room_number'],
        'fee_amount' => $fee,
    ];
    $total_amount += $fee;
}

// Tạo reservations pending cho từng phòng (trong transaction và khóa để tránh race)
$conn->begin_transaction();
try {
    foreach ($rooms as $r) {
        $check_room_sql = "SELECT id, status FROM rooms WHERE id = ? FOR UPDATE";
        $check_stmt = $conn->prepare($check_room_sql);
        $check_stmt->bind_param('i', $r['room_id']);
        $check_stmt->execute();
        $room_row = $check_stmt->get_result()->fetch_assoc();
        if (!$room_row || $room_row['status'] !== 'available') {
            throw new Exception('Phòng không còn trống: ' . $r['room_number']);
        }

        $check_existing_sql = "SELECT id FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'pending'";
        $check_existing_stmt = $conn->prepare($check_existing_sql);
        $check_existing_stmt->bind_param('ii', $user_id, $r['room_id']);
        $check_existing_stmt->execute();
        if ($check_existing_stmt->get_result()->num_rows > 0) {
            continue; // đã có pending, bỏ qua tạo mới
        }

        $ins_sql = "INSERT INTO reservations (user_id, room_id, fee_amount, status) VALUES (?, ?, ?, 'pending')";
        $ins_stmt = $conn->prepare($ins_sql);
        $ins_stmt->bind_param('iid', $user_id, $r['room_id'], $r['fee_amount']);
        $ins_stmt->execute();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Không thể tạo giữ chỗ: ' . $e->getMessage();
    header('Location: cart.php');
    exit();
}

// Tạo orderId chứa danh sách roomIds để fallback khi thiếu extraData/IPN
$roomIdList = implode('-', array_map('intval', array_column($rooms, 'room_id')));
$orderId = 'reservation_multi_' . $user_id . '_' . $roomIdList . '_' . time();
$orderInfo = 'Thanh toán giữ chỗ cho ' . count($rooms) . ' phòng';

// Lưu ngữ cảnh thanh toán: embed danh sách room_id trong extra
$_SESSION['momo_payment'] = [
    'orderId' => $orderId,
    'amount' => $total_amount,
    'orderInfo' => $orderInfo,
    'paymentType' => 'reservation_multi',
    'roomIds' => array_column($rooms, 'room_id'),
    'userId' => $user_id
];

header('Location: momo_payment_form.php');
exit();
?>


