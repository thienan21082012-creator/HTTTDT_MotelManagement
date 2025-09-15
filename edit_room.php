<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'includes/db.php';

// Kiểm tra nếu không có ID phòng được truyền
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$room_id = $_GET['id'];

// Lấy thông tin phòng và thông tin hợp đồng
$sql = "SELECT r.*, p.contract_duration, p.start_date
        FROM rooms r
        LEFT JOIN payments p ON r.id = p.room_id AND p.payment_type = 'deposit'
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo "Phòng không tồn tại.";
    exit();
}

// Xử lý khi form được gửi để cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = $_POST['room_number'];
    $description = $_POST['description'];
    $rent_price = $_POST['rent_price'];
    $status = $_POST['status'];
    $contract_duration = $_POST['contract_duration'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    
    // Cập nhật thông tin cơ bản của phòng
    $update_sql = "UPDATE rooms SET room_number = ?, description = ?, rent_price = ?, status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssdsi", $room_number, $description, $rent_price, $status, $room_id);
    $update_stmt->execute();

    // Cập nhật thông tin hợp đồng (nếu có)
    if ($status == 'occupied') {
        $update_payment_sql = "UPDATE payments SET contract_duration = ?, start_date = ? WHERE room_id = ? AND payment_type = 'deposit'";
        $update_payment_stmt = $conn->prepare($update_payment_sql);
        $update_payment_stmt->bind_param("isi", $contract_duration, $start_date, $room_id);
        $update_payment_stmt->execute();
    }

    $_SESSION['success_message'] = "Cập nhật phòng thành công.";
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chỉnh sửa phòng</title>
</head>
<body>
    <h2>Chỉnh sửa phòng: <?php echo htmlspecialchars($room['room_number']); ?></h2>
    <form action="edit_room.php?id=<?php echo $room['id']; ?>" method="POST">
        Số phòng: <input type="text" name="room_number" value="<?php echo htmlspecialchars($room['room_number']); ?>" required><br>
        Mô tả: <textarea name="description"><?php echo htmlspecialchars($room['description']); ?></textarea><br>
        Giá thuê: <input type="number" step="0.01" name="rent_price" value="<?php echo htmlspecialchars($room['rent_price']); ?>" required><br>
        Trạng thái: 
        <select name="status">
            <option value="available" <?php echo ($room['status'] == 'available') ? 'selected' : ''; ?>>Trống</option>
            <option value="reserved" <?php echo ($room['status'] == 'reserved') ? 'selected' : ''; ?>>Đã cọc</option>
            <option value="occupied" <?php echo ($room['status'] == 'occupied') ? 'selected' : ''; ?>>Đã thuê</option>
        </select><br>

        <label for="start_date">Ngày nhận phòng:</label>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($room['start_date']); ?>"><br>
        
        <label for="contract_duration">Thời hạn hợp đồng:</label>
        <select name="contract_duration">
            <option value="">-- Chọn --</option>
            <option value="3" <?php echo ($room['contract_duration'] == 3) ? 'selected' : ''; ?>>3 tháng</option>
            <option value="6" <?php echo ($room['contract_duration'] == 6) ? 'selected' : ''; ?>>6 tháng</option>
            <option value="12" <?php echo ($room['contract_duration'] == 12) ? 'selected' : ''; ?>>12 tháng</option>
        </select><br>
        
        <button type="submit">Cập nhật</button>
        <a href="dashboard.php">Hủy bỏ</a>
    </form>
</body>
</html>