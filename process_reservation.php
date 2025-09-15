<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: view_rooms.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = $_POST['room_id'];
$fee_amount = $_POST['fee_amount'];

// Mô phỏng quá trình thanh toán thành công
$payment_status = 'paid';

if ($payment_status == 'paid') {
    $conn->begin_transaction();
    try {
        // Cập nhật trạng thái phòng thành 'reserved'
        $update_room_sql = "UPDATE rooms SET status = 'reserved' WHERE id = ?";
        $update_room_stmt = $conn->prepare($update_room_sql);
        $update_room_stmt->bind_param("i", $room_id);
        $update_room_stmt->execute();

        // Tạo bản ghi đặt phòng trong bảng reservations
        $insert_res_sql = "INSERT INTO reservations (user_id, room_id, fee_amount, status) VALUES (?, ?, ?, ?)";
        $insert_res_stmt = $conn->prepare($insert_res_sql);
        $insert_res_stmt->bind_param("iids", $user_id, $room_id, $fee_amount, $payment_status);
        $insert_res_stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Đặt phòng thành công! Vui lòng thanh toán tiền cọc để ký hợp đồng.";
        header('Location: index.php');
        exit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
        header('Location: view_rooms.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "Thanh toán thất bại. Vui lòng thử lại.";
    header('Location: reserve.php?room_id=' . $room_id);
    exit();
}