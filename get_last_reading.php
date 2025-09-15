<?php
require_once 'includes/db.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];
    
    $sql = "SELECT new_reading FROM electricity_readings WHERE room_id = ? ORDER BY reading_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row['new_reading'];
    } else {
        // Nếu không có chỉ số cũ, mặc định là 0
        echo '0';
    }
} else {
    echo '0';
}
?>