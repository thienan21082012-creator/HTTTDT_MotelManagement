<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['room_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = $_GET['room_id'];

$sql = "SELECT rent_price, room_number FROM rooms WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    header('Location: index.php');
    exit();
}

$deposit_amount = $room['rent_price'] * 1; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id']) && isset($_POST['deposit_amount'])) {
    $conn->begin_transaction();
    try {
        $insert_payment_sql = "INSERT INTO payments (user_id, room_id, total_amount, payment_type, payment_status, payment_date) VALUES (?, ?, ?, 'deposit', 'completed', NOW())";
        $insert_payment_stmt = $conn->prepare($insert_payment_sql);
        $insert_payment_stmt->bind_param("iid", $user_id, $room_id, $deposit_amount);
        $insert_payment_stmt->execute();

        $update_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
        $update_room_stmt = $conn->prepare($update_room_sql);
        $update_room_stmt->bind_param("i", $room_id);
        $update_room_stmt->execute();

        $delete_res_sql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ?";
        $delete_res_stmt = $conn->prepare($delete_res_sql);
        $delete_res_stmt->bind_param("ii", $user_id, $room_id);
        $delete_res_stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Thanh toán tiền cọc thành công! Hợp đồng của bạn đã được ký kết.";
        header('Location: index.php');
        exit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Thanh toán thất bại: " . $e->getMessage();
        header('Location: index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Thanh toán tiền cọc</title>
</head>
<body>
    <h2>Thanh toán tiền cọc cho phòng **<?php echo htmlspecialchars($room['room_number']); ?>**</h2>
    <p>Tiền cọc (1 tháng thuê): **<?php echo number_format($deposit_amount); ?>** VND</p>
    
    <form action="checkout_deposit.php?room_id=<?php echo $room_id; ?>" method="POST">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        <input type="hidden" name="deposit_amount" value="<?php echo $deposit_amount; ?>">
        <button type="submit">Xác nhận thanh toán</button>
    </form>
    <p><a href="index.php">Hủy bỏ</a></p>
</body>
</html>