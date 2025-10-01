<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Standard checkout now creates a pending reservation and redirects to MoMo payment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = (int) $_POST['room_id'];
    $fee_amount = isset($_POST['fee_amount']) ? (float) $_POST['fee_amount'] : null;

    if (!is_numeric($room_id) || $room_id <= 0 || !is_numeric($fee_amount) || $fee_amount <= 0) {
        $_SESSION['error_message'] = 'Dữ liệu không hợp lệ.';
        header('Location: cart.php');
        exit();
    }

    $conn->begin_transaction();
    try {
        // Ensure room is available
        $check_room_sql = "SELECT id, status, rent_price FROM rooms WHERE id = ? FOR UPDATE";
        $check_stmt = $conn->prepare($check_room_sql);
        $check_stmt->bind_param('i', $room_id);
        $check_stmt->execute();
        $room_res = $check_stmt->get_result();
        $room = $room_res->fetch_assoc();

        if (!$room) {
            throw new Exception('Phòng không tồn tại.');
        }
        if ($room['status'] !== 'available') {
            throw new Exception('Phòng không còn trống để đặt.');
        }

        // Check no existing pending reservation by this user for this room
        $check_existing_sql = "SELECT id FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'pending'";
        $check_existing_stmt = $conn->prepare($check_existing_sql);
        $check_existing_stmt->bind_param('ii', $user_id, $room_id);
        $check_existing_stmt->execute();
        if ($check_existing_stmt->get_result()->num_rows > 0) {
            throw new Exception('Bạn đã có một đơn đặt phòng đang chờ thanh toán cho phòng này.');
        }

        // Create pending reservation
        $insert_res_sql = "INSERT INTO reservations (user_id, room_id, fee_amount, status) VALUES (?, ?, ?, 'pending')";
        $insert_res_stmt = $conn->prepare($insert_res_sql);
        $insert_res_stmt->bind_param('iid', $user_id, $room_id, $fee_amount);
        $insert_res_stmt->execute();

        $conn->commit();

        // Build MoMo order
        $orderId = 'reservation_' . $room_id . '_' . $user_id . '_' . time();
        $orderInfo = 'Thanh toán phí giữ chỗ phòng';

        // Save payment context to session and redirect to MoMo form
        $_SESSION['momo_payment'] = [
            'orderId' => $orderId,
            'amount' => $fee_amount,
            'orderInfo' => $orderInfo,
            'paymentType' => 'reservation',
            'roomId' => $room_id,
            'userId' => $user_id,
            'roomIds' => [$room_id]
        ];

        header('Location: momo_payment_form.php');
        exit();

    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Không thể tạo giao dịch: ' . $e->getMessage();
        header('Location: cart.php');
        exit();
    }
}

header('Location: cart.php');
exit();
?>