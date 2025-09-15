<?php
session_start();
require_once 'includes/db.php';

$sql = "SELECT * FROM rooms WHERE status = 'available'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Danh sách phòng trống</title>
</head>
<body>
    <h2>Danh sách phòng trọ trống</h2>
    <p>Xin chào, <?php echo $_SESSION['username'] ?? 'khách'; ?>! 
    <?php if (isset($_SESSION['user_id'])): ?>
        | <a href="index.php">Trang cá nhân</a> | <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
        | <a href="login.php">Đăng nhập</a>
    <?php endif; ?>
    </p>
    <hr>
    
    <?php if ($result->num_rows > 0): ?>
        <ul>
            <?php while($row = $result->fetch_assoc()): ?>
                <li>
                    Phòng: **<?php echo htmlspecialchars($row['room_number']); ?>** - Giá: **<?php echo number_format($row['rent_price']); ?>** VND
                    <p>Mô tả: <?php echo htmlspecialchars($row['description']); ?></p>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="reserve.php?room_id=<?php echo $row['id']; ?>">Đặt phòng</a>
                    <?php else: ?>
                        <p><a href="login.php">Đăng nhập để đặt phòng</a></p>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Hiện không có phòng trống.</p>
    <?php endif; ?>
</body>
</html>