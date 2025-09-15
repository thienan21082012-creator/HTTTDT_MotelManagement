<?php
session_start();
require_once 'includes/db.php';

// Kiểm tra nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Truy vấn để tìm phòng đã được đặt chỗ (trạng thái 'reserved')
$sql_res = "SELECT r.* FROM reservations res JOIN rooms r ON res.room_id = r.id WHERE res.user_id = ? AND r.status = 'reserved'";
$stmt_res = $conn->prepare($sql_res);
$stmt_res->bind_param("i", $user_id);
$stmt_res->execute();
$reserved_room = $stmt_res->get_result()->fetch_assoc();

// Cập nhật truy vấn để lấy chi tiết hóa đơn từ bảng bills và electricity_readings
$sql_bills = "SELECT 
                b.*, 
                r.room_number,
                er.old_reading,
                er.new_reading
              FROM bills b 
              JOIN rooms r ON b.room_id = r.id 
              LEFT JOIN electricity_readings er ON b.room_id = er.room_id AND b.billing_month = er.month AND b.billing_year = er.year
              WHERE b.user_id = ? 
              ORDER BY b.billing_year DESC, b.billing_month DESC";
$stmt_bills = $conn->prepare($sql_bills);
$stmt_bills->bind_param("i", $user_id);
$stmt_bills->execute();
$bills_result = $stmt_bills->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Trang cá nhân của bạn</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h2>Chào mừng, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p><a href="view_rooms.php">Xem phòng trống</a> | <a href="logout.php">Đăng xuất</a></p>
    <hr>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>
    
    <?php if ($reserved_room): ?>
        <h3>Bạn đã đặt phòng: **<?php echo htmlspecialchars($reserved_room['room_number']); ?>**</h3>
        <p>Vui lòng thanh toán tiền cọc để hoàn tất hợp đồng.</p>
        <a href="checkout_deposit.php?room_id=<?php echo $reserved_room['id']; ?>"><button>Thanh toán tiền cọc</button></a>
        <hr>
    <?php endif; ?>

    <h3>Hóa đơn hàng tháng của bạn</h3>
    <?php if ($bills_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Phòng</th>
                    <th>Tháng</th>
                    <th>Tiền thuê</th>
                    <th>Số điện (cũ/mới)</th>
                    <th>Tiền điện</th>
                    <th>Tiền nước</th>
                    <th>Phí dịch vụ</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php while($bill = $bills_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bill['room_number']); ?></td>
                    <td><?php echo htmlspecialchars($bill['billing_month'] . '/' . $bill['billing_year']); ?></td>
                    <td><?php echo number_format($bill['rent_amount']); ?> VND</td>
                    <td>
                        <?php 
                        if ($bill['old_reading'] !== null && $bill['new_reading'] !== null) {
                            echo htmlspecialchars($bill['old_reading']) . ' / ' . htmlspecialchars($bill['new_reading']);
                        } else {
                            echo "Chưa cập nhật";
                        }
                        ?>
                    </td>
                    <td><?php echo number_format($bill['electricity_amount']); ?> VND</td>
                    <td><?php echo number_format($bill['water_amount']); ?> VND</td>
                    <td><?php echo number_format($bill['service_fee']); ?> VND</td>
                    <td><?php echo number_format($bill['total_amount']); ?> VND</td>
                    <td style="color: <?php echo ($bill['status'] == 'unpaid') ? 'red' : 'green'; ?>; font-weight: bold;"><?php echo ($bill['status'] == 'unpaid') ? 'Chưa thanh toán' : 'Đã thanh toán'; ?></td>
                    <td>
                        <?php if ($bill['status'] == 'unpaid'): ?>
                            <a href="pay_bill.php?bill_id=<?php echo $bill['id']; ?>"><button>Thanh toán</button></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Bạn chưa có hóa đơn nào.</p>
    <?php endif; ?>
</body>
</html>