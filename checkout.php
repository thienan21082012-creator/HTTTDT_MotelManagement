<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id']) && isset($_POST['start_date'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = $_POST['room_id'];
    $start_date = $_POST['start_date'];
    $contract_duration = $_POST['contract_duration'];
    $payment_date = date('Y-m-d H:i:s');
    
    // Lấy giá thuê của phòng
    $room_sql = "SELECT rent_price FROM rooms WHERE id = ?";
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();
    $room = $room_result->fetch_assoc();
    $total_amount = $room['rent_price'];

    // 1. Ghi lại giao dịch thanh toán
    $payment_sql = "INSERT INTO payments (user_id, room_id, start_date, contract_duration, total_amount, payment_status, payment_date) VALUES (?, ?, ?, ?, ?, 'completed', ?)";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("iisdss", $user_id, $room_id, $start_date, $contract_duration, $total_amount, $payment_date);

    if ($payment_stmt->execute()) {
        // 2. Cập nhật trạng thái phòng thành 'occupied'
        $update_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
        $update_room_stmt = $conn->prepare($update_room_sql);
        $update_room_stmt->bind_param("i", $room_id);
        $update_room_stmt->execute();

        // 3. Xóa phòng khỏi giỏ hàng
        $delete_cart_sql = "DELETE FROM carts WHERE user_id = ? AND room_id = ?";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        $delete_cart_stmt->bind_param("ii", $user_id, $room_id);
        $delete_cart_stmt->execute();
        
        $_SESSION['success_message'] = "Thanh toán thành công! Phòng đã được thuê.";
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Thanh toán thất bại. Vui lòng thử lại.";
        header('Location: cart.php');
        exit();
    }
} else {
    header('Location: cart.php');
    exit();
}
?>