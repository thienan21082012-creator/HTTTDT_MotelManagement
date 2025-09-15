<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}
require_once 'includes/db.php';

// Xử lý thêm phòng
if (isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $description = $_POST['description'];
    $rent_price = $_POST['rent_price'];
    $status = 'available';

    $sql = "INSERT INTO rooms (room_number, description, rent_price, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssds", $room_number, $description, $rent_price, $status);
    $stmt->execute();
}

// Xử lý xóa phòng
if (isset($_GET['delete_room'])) {
    $id = $_GET['delete_room'];
    $sql = "DELETE FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Xử lý xóa người dùng
if (isset($_GET['delete_user'])) {
    $id = $_GET['delete_user'];
    $sql = "DELETE FROM users WHERE id = ? AND role != 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Lấy danh sách phòng và thông tin người thuê (nếu có)
$sql_rooms = "SELECT 
    r.*, 
    p.user_id, 
    p.contract_duration,
    p.start_date,
    er.old_reading,
    er.new_reading,
    er.reading_date,
    res.user_id as reserved_user_id
FROM rooms r 
LEFT JOIN payments p ON r.id = p.room_id
LEFT JOIN reservations res ON r.id = res.room_id
LEFT JOIN electricity_readings er ON r.id = er.room_id AND er.id = (
    SELECT MAX(id) FROM electricity_readings WHERE room_id = r.id
)
ORDER BY r.room_number ASC";
$rooms_result = $conn->query($sql_rooms);

// Lấy danh sách người dùng
$users_result = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trang quản trị</title>
    <script>
        function updateOldReading() {
            var roomId = document.getElementById('room_select').value;
            if (!roomId) {
                document.getElementById('old_reading_input').value = '';
                return;
            }
            
            // Sử dụng AJAX để lấy chỉ số cũ
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var oldReading = this.responseText.trim();
                    document.getElementById('old_reading_input').value = oldReading;
                    document.getElementById('new_reading_input').min = oldReading;
                }
            };
            xhr.open("GET", "get_last_reading.php?room_id=" + roomId, true);
            xhr.send();
        }
    </script>
</head>
<body>
    <h2>Chào mừng Admin, <?php echo $_SESSION['username']; ?>!</h2>
    <p><a href="logout.php">Đăng xuất</a></p>
    <hr>

    <h3>Quản lý phòng trọ</h3>
    <form action="dashboard.php" method="POST">
        <input type="hidden" name="add_room" value="1">
        Số phòng: <input type="text" name="room_number" required>
        Mô tả: <textarea name="description"></textarea>
        Giá thuê: <input type="number" step="0.01" name="rent_price" required>
        <button type="submit">Thêm phòng</button>
    </form>
        <hr>
    
    <h3>Tạo hóa đơn hàng tháng</h3>
    <form action="generate_bills.php" method="POST">
        <button type="submit">Tạo hóa đơn cho tháng hiện tại</button>
    </form>
    
    <hr>
    
    <h3>Danh sách phòng</h3>
    <ul>
        <?php while($row = $rooms_result->fetch_assoc()): ?>
            <li>
                Phòng **<?php echo htmlspecialchars($row['room_number']); ?>** (Trạng thái: **<?php echo htmlspecialchars($row['status']); ?>**)
                <?php if ($row['status'] == 'occupied'): ?>
                    <br>Thuê bởi User ID: **<?php echo htmlspecialchars($row['user_id']); ?>**
                    <br>Thời hạn hợp đồng: **<?php echo htmlspecialchars($row['contract_duration']); ?>** tháng
                    <br>Ngày nhận phòng: **<?php echo htmlspecialchars($row['start_date']); ?>**
                    <?php if ($row['reading_date']): ?>
                        <br>Số điện cũ: **<?php echo htmlspecialchars($row['old_reading']); ?>**
                        <br>Số điện mới: **<?php echo htmlspecialchars($row['new_reading']); ?>**
                        <br>Ngày ghi số điện: **<?php echo htmlspecialchars($row['reading_date']); ?>**
                    <?php else: ?>
                        <br>Chưa có thông tin điện.
                    <?php endif; ?>
                <?php elseif ($row['status'] == 'reserved'): ?>
                    <br>Đã cọc bởi User ID: **<?php echo htmlspecialchars($row['reserved_user_id']); ?>**
                <?php endif; ?>
                - <a href="edit_room.php?id=<?php echo $row['id']; ?>">Chỉnh sửa</a>
                - <a href="dashboard.php?delete_room=<?php echo $row['id']; ?>">Xóa</a>
            </li>
        <?php endwhile; ?>
    </ul>
    <hr>

    <h3>Cập nhật chỉ số điện</h3>
    <form action="update_electricity.php" method="POST">
        <label for="room_select">Chọn phòng:</label>
        <select name="room_id" id="room_select" onchange="updateOldReading()" required>
            <option value="">-- Chọn phòng --</option>
            <?php
            // Lấy lại danh sách phòng
            $rooms_for_select = $conn->query("SELECT id, room_number FROM rooms");
            while($room = $rooms_for_select->fetch_assoc()): ?>
                <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number']); ?></option>
            <?php endwhile; ?>
        </select><br>
        
        <label for="old_reading_input">Số điện cũ:</label>
        <input type="number" name="old_reading" id="old_reading_input" readonly required><br>
        
        <label for="new_reading_input">Số điện mới:</label>
        <input type="number" name="new_reading" id="new_reading_input" required><br>
        
        <label for="reading_date_input">Ngày ghi:</label>
        <input type="date" name="reading_date" id="reading_date_input" required><br>
        <button type="submit">Cập nhật chỉ số</button>
    </form>
    <hr>

    <h3>Quản lý người dùng</h3>
    <h4>Danh sách người dùng</h4>
    <ul>
        <?php while($row = $users_result->fetch_assoc()): ?>
            <li>
                <?php echo htmlspecialchars($row['username']); ?> (<?php echo htmlspecialchars($row['role']); ?>)
                <?php if ($row['role'] != 'admin'): ?>
                    - <a href="dashboard.php?delete_user=<?php echo $row['id']; ?>">Xóa</a>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>