<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['bill_id'])) {
    header('Location: index.php');
    exit();
}

$bill_id = $_GET['bill_id'];
$user_id = $_SESSION['user_id'];

// Cập nhật trạng thái hóa đơn thành 'paid'
$sql = "UPDATE bills SET status = 'paid' WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bill_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Thanh toán hóa đơn thành công!";
} else {
    $_SESSION['error_message'] = "Có lỗi xảy ra, không thể thanh toán hóa đơn.";
}

header('Location: index.php');
exit();
?>