<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = (int) $_POST['room_id'];

    if (!is_numeric($room_id) || $room_id <= 0) {
        $_SESSION['error_message'] = 'Dữ liệu không hợp lệ.';
        header('Location: cart.php');
        exit();
    }

    $sql = "DELETE FROM carts WHERE user_id = ? AND room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $room_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Đã xóa phòng khỏi giỏ hàng.';
    } else {
        $_SESSION['error_message'] = 'Không thể xóa phòng khỏi giỏ.';
    }
}

header('Location: cart.php');
exit();
?>


