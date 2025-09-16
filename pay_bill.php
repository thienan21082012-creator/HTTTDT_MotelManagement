<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id']) || !isset($_GET['bill_id'])) {
    header('Location: index.php');
    exit();
}

$bill_id = (int) $_GET['bill_id'];
$user_id = (int) $_SESSION['user_id'];

// Lấy thông tin hóa đơn để thanh toán qua MoMo
$sql = "SELECT b.*, r.room_number FROM bills b JOIN rooms r ON b.room_id = r.id WHERE b.id = ? AND b.user_id = ? AND b.status = 'unpaid'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bill_id, $user_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) {
    $_SESSION['error_message'] = "Không tìm thấy hóa đơn hợp lệ để thanh toán.";
    header('Location: index.php');
    exit();
}

// Tạo đơn hàng MoMo cho hóa đơn
$orderId = 'bill_' . $bill_id . '_' . $user_id . '_' . time();
$amount = (float) $bill['total_amount'];
$orderInfo = 'Thanh toán hóa đơn tháng ' . $bill['billing_month'] . '/' . $bill['billing_year'] . ' - Phòng ' . $bill['room_number'];

// Lưu ngữ cảnh thanh toán vào session và chuyển đến form MoMo
$_SESSION['momo_payment'] = [
    'orderId' => $orderId,
    'amount' => $amount,
    'orderInfo' => $orderInfo,
    'paymentType' => 'bill',
    'billId' => $bill_id,
    'userId' => $user_id
];

header('Location: momo_payment_form.php');
exit();
?>