<?php
$page_title = "Giỏ hàng";
require_once 'includes/header.php';

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

<div class="card">
    <h2><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h2>
    <p>Xem lại các phòng bạn đã chọn và tiến hành thanh toán.</p>
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

<?php if ($result->num_rows > 0): ?>
    <div class="card">
        <h3><i class="fas fa-list"></i> Phòng đã chọn</h3>
        <div class="room-grid">
            <?php while($row = $result->fetch_assoc()): ?>
                <?php $reservation_fee = (int) round(max($row['rent_price'] * 0.10,500000)); ?>
                <div class="room-card">
                    <div class="room-number">Phòng <?php echo htmlspecialchars($row['room_number']); ?></div>
                    <div class="room-price"><?php echo number_format($row['rent_price']); ?> VND/tháng</div>
                    <div class="room-description"><?php echo htmlspecialchars($row['description']); ?></div>
                    <div style="display: flex; gap: .5rem; margin-top: .75rem;">
                        <form action="checkout.php" method="POST" style="flex: 1;">
                            <input type="hidden" name="room_id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="fee_amount" value="<?php echo $reservation_fee; ?>">
                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-credit-card"></i> Giữ chỗ (<?= number_format($reservation_fee) ?> VND)
                            </button>
                        </form>
                        <form action="remove_from_cart.php" method="POST" onsubmit="return confirm('Xóa phòng này khỏi giỏ?');">
                            <input type="hidden" name="room_id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <div class="card">
        <div style="display: flex; gap: 1rem;">
            <a href="view_rooms.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-plus"></i> Thêm phòng khác
            </a>
            <form action="checkout_all.php" method="POST" style="flex: 1;">
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-layer-group"></i> Giữ chỗ tất cả
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <h4>Giỏ hàng trống</h4>
            <p>Bạn chưa chọn phòng nào. Hãy xem danh sách phòng trống và chọn phòng phù hợp.</p>
            <a href="view_rooms.php" class="btn btn-success" style="margin-top: 1rem;">
                <i class="fas fa-bed"></i> Xem phòng trống
            </a>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>