<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT r.id, r.room_number, r.description, r.rent_price FROM carts c JOIN rooms r ON c.room_id = r.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Giỏ hàng của bạn</title>
</head>
<body>
    <h2>Giỏ hàng của bạn</h2>
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <form action="checkout.php" method="POST">
            <ul>
                <?php while($row = $result->fetch_assoc()): ?>
                    <li>
                        Phòng: <?php echo htmlspecialchars($row['room_number']); ?> - Giá: <?php echo htmlspecialchars($row['rent_price']); ?> VND
                        <p>Mô tả: <?php echo htmlspecialchars($row['description']); ?></p>
                        <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                    </li>
                <?php endwhile; ?>
            </ul>
            
            <p>Chọn ngày nhận phòng và thanh toán:</p>
            Ngày nhận phòng: <input type="date" name="start_date" required><br>
            
            <label for="contract_duration">Thời hạn hợp đồng:</label>
            <select name="contract_duration" id="contract_duration" required>
                <option value="3">3 tháng</option>
                <option value="6">6 tháng</option>
                <option value="12">12 tháng</option>
            </select><br><br>
            
            <button type="submit">Tiến hành thanh toán</button>
        </form>
    <?php else: ?>
        <p>Giỏ hàng của bạn trống.</p>
    <?php endif; ?>
    <p><a href="index.php">Tiếp tục chọn phòng</a></p>
</body>
</html>