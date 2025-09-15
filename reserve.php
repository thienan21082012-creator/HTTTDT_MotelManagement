<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['room_id'])) {
    header('Location: view_rooms.php');
    exit();
}

$room_id = $_GET['room_id'];
$sql = "SELECT room_number, description, rent_price, status FROM rooms WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room || $room['status'] != 'available') {
    echo "Phòng này không thể đặt được.";
    exit();
}

$reservation_fee = 500000;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đặt phòng - <?php echo htmlspecialchars($room['room_number']); ?></title>
</head>
<body>
    <h2>Xác nhận đặt phòng</h2>
    <p>Bạn đang đặt phòng: **<?php echo htmlspecialchars($room['room_number']); ?>**</p>
    <p>Giá thuê hàng tháng: **<?php echo number_format($room['rent_price']); ?>** VND</p>
    <p>Phí giữ chỗ cần thanh toán: **<?php echo number_format($reservation_fee); ?>** VND</p>
    
    <form action="process_reservation.php" method="POST">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        <input type="hidden" name="fee_amount" value="<?php echo $reservation_fee; ?>">
        <p>Vui lòng thanh toán phí giữ chỗ để xác nhận đặt phòng. Phí này sẽ được khấu trừ vào tiền cọc sau này.</p>
        <button type="submit">Thanh toán và giữ phòng</button>
    </form>
    <p><a href="view_rooms.php">Hủy bỏ</a></p>
</body>
</html>