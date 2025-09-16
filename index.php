<?php
$page_title = "Trang chủ";
require_once 'includes/header.php';

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
              LEFT JOIN (
                SELECT e1.* FROM electricity_readings e1
                JOIN (
                  SELECT room_id, `month`, `year`, MAX(id) AS max_id
                  FROM electricity_readings
                  GROUP BY room_id, `month`, `year`
                ) last ON last.max_id = e1.id
              ) er ON b.room_id = er.room_id AND b.billing_month = er.month AND b.billing_year = er.year
              WHERE b.user_id = ? 
              ORDER BY b.billing_year DESC, b.billing_month DESC, b.created_at DESC";
$stmt_bills = $conn->prepare($sql_bills);
$stmt_bills->bind_param("i", $user_id);
$stmt_bills->execute();
$bills_result = $stmt_bills->get_result();

?>

<div class="card">
    <h2><i class="fas fa-home"></i> Chào mừng, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Đây là trang quản lý cá nhân của bạn trong hệ thống motel.</p>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<?php if ($reserved_room): ?>
    <div class="card">
        <h3><i class="fas fa-clock"></i> Phòng đã đặt chỗ</h3>
        <div style="background: rgba(255, 193, 7, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #ffc107;">
            <h4><i class="fas fa-bed"></i> Phòng <?php echo htmlspecialchars($reserved_room['room_number']); ?></h4>
            <p><strong>Giá thuê:</strong> <?php echo number_format($reserved_room['rent_price']); ?> VND/tháng</p>
            <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($reserved_room['description']); ?></p>
            <p style="margin-top: 1rem;"><strong>Vui lòng thanh toán tiền cọc để hoàn tất hợp đồng.</strong></p>
            <a href="checkout_deposit.php?room_id=<?php echo $reserved_room['id']; ?>" class="btn btn-success">
                <i class="fas fa-credit-card"></i> Thanh toán tiền cọc
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Hóa đơn hàng tháng -->
<div class="card">
    <h3><i class="fas fa-file-invoice"></i> Hóa đơn hàng tháng của bạn</h3>
    <?php if ($bills_result->num_rows > 0): ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-bed"></i> Phòng</th>
                        <th><i class="fas fa-calendar"></i> Tháng</th>
                        <th><i class="fas fa-money-bill-wave"></i> Tiền thuê</th>
                        <th><i class="fas fa-bolt"></i> Số điện</th>
                        <th><i class="fas fa-bolt"></i> Tiền điện</th>
                        <th><i class="fas fa-tint"></i> Tiền nước</th>
                        <th><i class="fas fa-cogs"></i> Phí dịch vụ</th>
                        <th><i class="fas fa-calculator"></i> Tổng tiền</th>
                        <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                        <th><i class="fas fa-cogs"></i> Hành động</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($bill = $bills_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($bill['room_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($bill['billing_month'] . '/' . $bill['billing_year']); ?></td>
                        <td><?php echo number_format($bill['rent_amount']); ?> VND</td>
                        <td>
                            <?php 
                            if ($bill['old_reading'] !== null && $bill['new_reading'] !== null) {
                                echo htmlspecialchars($bill['old_reading']) . ' → ' . htmlspecialchars($bill['new_reading']);
                            } else {
                                echo "<span style='color: #666;'>Chưa cập nhật</span>";
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($bill['electricity_amount']); ?> VND</td>
                        <td><?php echo number_format($bill['water_amount']); ?> VND</td>
                        <td><?php echo number_format($bill['service_fee']); ?> VND</td>
                        <td><strong><?php echo number_format($bill['total_amount']); ?> VND</strong></td>
                        <td>
                            <span class="room-status <?php echo ($bill['status'] == 'unpaid') ? 'status-occupied' : 'status-available'; ?>">
                                <?php echo ($bill['status'] == 'unpaid') ? 'Chưa thanh toán' : 'Đã thanh toán'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($bill['status'] == 'unpaid'): ?>
                                <a href="pay_bill.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card"></i> Thanh toán
                                </a>
                            <?php else: ?>
                                <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Hoàn thành</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="fas fa-file-invoice" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <h4>Chưa có hóa đơn nào</h4>
            <p>Bạn chưa có hóa đơn hàng tháng nào. Hóa đơn sẽ được tạo tự động khi bạn thuê phòng.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>