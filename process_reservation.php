<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: view_rooms.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = $_POST['room_id'];
$fee_amount = $_POST['fee_amount'];

// Validate input
if (!is_numeric($room_id) || !is_numeric($fee_amount) || $fee_amount <= 0) {
    $_SESSION['error_message'] = "Dữ liệu không hợp lệ.";
    header('Location: view_rooms.php');
    exit();
}

// Create reservation record with pending status first
$conn->begin_transaction();
try {
    // First, check if room is available and lock it
    $check_room_sql = "SELECT id, status FROM rooms WHERE id = ? FOR UPDATE";
    $check_stmt = $conn->prepare($check_room_sql);
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $room_result = $check_stmt->get_result();
    $room = $room_result->fetch_assoc();
    
    if (!$room) {
        throw new Exception("Phòng không tồn tại.");
    }
    
    if ($room['status'] !== 'available') {
        throw new Exception("Phòng không còn trống để đặt.");
    }
    
    // Check if user already has a pending reservation for this room
    $check_existing_sql = "SELECT id FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'pending'";
    $check_existing_stmt = $conn->prepare($check_existing_sql);
    $check_existing_stmt->bind_param("ii", $user_id, $room_id);
    $check_existing_stmt->execute();
    $existing_result = $check_existing_stmt->get_result();
    
    if ($existing_result->num_rows > 0) {
        throw new Exception("Bạn đã có một đơn đặt phòng đang chờ thanh toán cho phòng này.");
    }
    
    // Tạo bản ghi đặt phòng trong bảng reservations với status 'pending'
    $insert_res_sql = "INSERT INTO reservations (user_id, room_id, fee_amount, status) VALUES (?, ?, ?, 'pending')";
    $insert_res_stmt = $conn->prepare($insert_res_sql);
    $insert_res_stmt->bind_param("iid", $user_id, $room_id, $fee_amount);
    $insert_res_stmt->execute();
    
    $conn->commit();
    
    // Redirect to MoMo payment
    $orderId = "reservation_" . $room_id . "_" . $user_id . "_" . time();
    $orderInfo = "Thanh toán phí giữ chỗ phòng";
    
    // Store payment info in session for MoMo
    $_SESSION['momo_payment'] = [
        'orderId' => $orderId,
        'amount' => $fee_amount,
        'orderInfo' => $orderInfo,
        'paymentType' => 'reservation',
        'roomId' => $room_id,
        'userId' => $user_id,
        'roomIds' => [$room_id]
    ];
    
    // Redirect to MoMo payment page
    header('Location: momo_payment_form.php');
    exit();
    
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
    header('Location: view_rooms.php');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: view_rooms.php');
    exit();
}