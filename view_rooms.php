<?php
$page_title = "Danh sách phòng trống";
require_once 'includes/header.php';

$sql = "SELECT * FROM rooms WHERE status = 'available'";
$result = $conn->query($sql);
?>

<div class="card">
    <h2><i class="fas fa-bed"></i> Danh sách phòng trọ trống</h2>
    <p>Xin chào, <strong><?php echo $_SESSION['username'] ?? 'khách'; ?></strong>! Dưới đây là danh sách các phòng trống hiện có.</p>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="room-grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="room-card">
                <div class="room-number">Phòng <?php echo htmlspecialchars($row['room_number']); ?></div>
                <div class="room-price"><?php echo number_format($row['rent_price']); ?> VND/tháng</div>
                <div class="room-description"><?php echo htmlspecialchars($row['description']); ?></div>
                
                <div class="room-status status-available">
                    <i class="fas fa-check-circle"></i> Trống
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="margin-top: 1.5rem; display: flex; gap: .75rem;">
                        <form action="add_to_cart.php" method="POST" style="flex: 1;">
                            <input type="hidden" name="room_id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                            </button>
                        </form>
                        <a href="reserve.php?room_id=<?php echo $row['id']; ?>" class="btn btn-success" style="flex: 1; text-align: center;">
                            <i class="fas fa-calendar-plus"></i> Đặt phòng
                        </a>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 1.5rem; text-align: center;">
                        <p style="color: #666; margin-bottom: 1rem;">
                            <i class="fas fa-info-circle"></i> Vui lòng đăng nhập để đặt phòng
                        </p>
                        <a href="login.php" class="btn" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="fas fa-bed" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <h4>Không có phòng trống</h4>
            <p>Hiện tại tất cả phòng đều đã được thuê hoặc đặt chỗ. Vui lòng quay lại sau.</p>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>