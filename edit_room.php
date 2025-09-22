<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'includes/db';

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
    $_SESSION['error_message'] = "Phòng không tồn tại.";
    header('Location: dashboard.php');
    exit();
}

// Xử lý khi form được gửi để cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = trim($_POST['room_number']);
    $description = trim($_POST['description']);
    $rent_price = floatval(str_replace(['.', ','], '', $_POST['rent_price']));
    $status = $_POST['status'];
    $contract_duration = !empty($_POST['contract_duration']) ? intval($_POST['contract_duration']) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $num_people = !empty($_POST['num_people']) ? intval($_POST['num_people']) : null;
    
    $errors = [];
    if (empty($room_number)) {
        $errors[] = "Số phòng không được để trống.";
    }
    if ($rent_price <= 0) {
        $errors[] = "Giá thuê phải lớn hơn 0.";
    }
    if ($num_people && $num_people <= 0) {
        $errors[] = "Số người phải lớn hơn 0.";
    }
    
    if (empty($errors)) {
        // Cập nhật thông tin cơ bản của phòng trước
        $update_room_sql = "UPDATE rooms SET room_number = ?, description = ?, rent_price = ?, num_people = ?, status = ? WHERE id = ?";
        $update_room_stmt = $conn->prepare($update_room_sql);
        $update_room_stmt->bind_param("sdsisi", $room_number, $description, $rent_price, $num_people, $status, $room_id);
        $update_room_stmt->execute();
        
        if ($status === 'occupied' && ($room['status'] === 'available' || $room['status'] === 'reserved')) {
            $user_id_from_res = null;
            if($room['status'] === 'reserved'){
                $sql_get_res_user = "SELECT user_id FROM reservations WHERE room_id = ?";
                $stmt_get_res_user = $conn->prepare($sql_get_res_user);
                $stmt_get_res_user->bind_param('i', $room_id);
                $stmt_get_res_user->execute();
                $user_id_from_res = $stmt_get_res_user->get_result()->fetch_assoc()['user_id'] ?? null;
            }
    
            $insert_payment_sql = "INSERT INTO payments (user_id, room_id, start_date, contract_duration, total_amount, payment_status, payment_date, payment_type) VALUES (?, ?, ?, ?, ?, 'completed', NOW(), 'deposit')";
            $insert_payment_stmt = $conn->prepare($insert_payment_sql);
            $insert_payment_stmt->bind_param("isid", $user_id_from_res ?? 0, $room_id, $start_date, $contract_duration, $rent_price);
            $insert_payment_stmt->execute();
    
            if($user_id_from_res){
                $delete_res_sql = "DELETE FROM reservations WHERE room_id = ? AND user_id = ?";
                $delete_res_stmt = $conn->prepare($delete_res_sql);
                $delete_res_stmt->bind_param("ii", $room_id, $user_id_from_res);
                $delete_res_stmt->execute();
            }
    
        } elseif ($status === 'available' && ($room['status'] === 'occupied' || $room['status'] === 'reserved')) {
            $delete_payments_sql = "DELETE FROM payments WHERE room_id = ?";
            $delete_payments_stmt = $conn->prepare($delete_payments_sql);
            $delete_payments_stmt->bind_param("i", $room_id);
            $delete_payments_stmt->execute();
    
            $delete_reservations_sql = "DELETE FROM reservations WHERE room_id = ?";
            $delete_reservations_stmt = $conn->prepare($delete_reservations_sql);
            $delete_reservations_stmt->bind_param("i", $room_id);
            $delete_reservations_stmt->execute();
        }
        
        $_SESSION['success_message'] = "Cập nhật phòng thành công.";
        header('Location: dashboard.php');
        exit();
    } else {
        $errors[] = "Có lỗi xảy ra khi cập nhật phòng.";
    }
}

$page_title = "Chỉnh sửa phòng";
include 'includes/header.php';
?>

<div class="card">
    <div class="d-flex justify-between align-center mb-3">
        <h2><i class="fas fa-edit"></i> Chỉnh sửa phòng: <?php echo htmlspecialchars($room['room_number']); ?></h2>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="edit_room.php?id=<?php echo $room['id']; ?>" method="POST" id="editRoomForm">
        <div class="form-row">
            <div class="form-group">
                <label for="room_number">
                    <i class="fas fa-door-open"></i> Số phòng *
                </label>
                <input type="text" 
                       id="room_number" 
                       name="room_number" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($room['room_number']); ?>" 
                       required
                       placeholder="Nhập số phòng">
            </div>
            
            <div class="form-group">
                <label for="rent_price">
                    <i class="fas fa-money-bill-wave"></i> Giá thuê (VNĐ) *
                </label>
                <input type="text" 
                       id="rent_price" 
                       name="rent_price" 
                       class="form-control" 
                       value="<?php echo number_format($room['rent_price'], 0, ',', '.'); ?>" 
                       min="0" 
                       required
                       placeholder="Nhập giá thuê (VD: 2.500.000)">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">
                <i class="fas fa-info-circle"></i> Mô tả phòng
            </label>
            <textarea id="description" 
                      name="description" 
                      class="form-control" 
                      rows="4" 
                      placeholder="Mô tả chi tiết về phòng, tiện ích, vị trí..."><?php echo htmlspecialchars($room['description']); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="status">
                    <i class="fas fa-bed"></i> Trạng thái phòng *
                </label>
                <select id="status" name="status" class="form-control" required>
                    <option value="available" <?php echo ($room['status'] == 'available') ? 'selected' : ''; ?>>
                        Trống
                    </option>
                    <option value="reserved" <?php echo ($room['status'] == 'reserved') ? 'selected' : ''; ?>>
                        Đã cọc
                    </option>
                    <option value="occupied" <?php echo ($room['status'] == 'occupied') ? 'selected' : ''; ?>>
                        Đã thuê
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="num_people">
                    <i class="fas fa-users"></i> Số người *
                </label>
                <input type="number" 
                       id="num_people" 
                       name="num_people" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($room['num_people']); ?>" 
                       min="1"
                       required
                       placeholder="Nhập số người thuê">
            </div>
        </div>
        
        <div class="form-group" id="contract_duration_group" style="<?php echo ($room['status'] == 'occupied') ? '' : 'display: none;'; ?>">
            <label for="contract_duration">
                <i class="fas fa-calendar-alt"></i> Thời hạn hợp đồng
            </label>
            <select id="contract_duration" name="contract_duration" class="form-control">
                <option value="">-- Chọn thời hạn --</option>
                <option value="3" <?php echo ($room['contract_duration'] == 3) ? 'selected' : ''; ?>>3 tháng</option>
                <option value="6" <?php echo ($room['contract_duration'] == 6) ? 'selected' : ''; ?>>6 tháng</option>
                <option value="12" <?php echo ($room['contract_duration'] == 12) ? 'selected' : ''; ?>>12 tháng</option>
            </select>
        </div>
        
        <div class="form-group" id="start_date_group" style="<?php echo ($room['status'] == 'occupied') ? '' : 'display: none;'; ?>">
            <label for="start_date">
                <i class="fas fa-calendar-check"></i> Ngày nhận phòng
            </label>
            <input type="date" 
                   id="start_date" 
                   name="start_date" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($room['start_date']); ?>">
        </div>
        
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Cập nhật phòng
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Hủy bỏ
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const contractDurationGroup = document.getElementById('contract_duration_group');
    const startDateGroup = document.getElementById('start_date_group');
    const contractDurationSelect = document.getElementById('contract_duration');
    const startDateInput = document.getElementById('start_date');
    const numPeopleInput = document.getElementById('num_people');
    
    function toggleContractFields() {
        if (statusSelect.value === 'occupied') {
            contractDurationGroup.style.display = 'block';
            startDateGroup.style.display = 'block';
            contractDurationSelect.required = true;
            startDateInput.required = true;
            numPeopleInput.required = true;
        } else {
            contractDurationGroup.style.display = 'none';
            startDateGroup.style.display = 'none';
            contractDurationSelect.required = false;
            startDateInput.required = false;
            numPeopleInput.required = false;
        }
    }
    
    toggleContractFields();
    statusSelect.addEventListener('change', toggleContractFields);
    
    const rentPriceInput = document.getElementById('rent_price');
    rentPriceInput.addEventListener('input', function() {
        let value = this.value.replace(/[^\d]/g, '');
        if (value) {
            this.value = parseInt(value).toLocaleString('vi-VN');
        }
    });
    
    document.getElementById('editRoomForm').addEventListener('submit', function() {
        const priceValue = rentPriceInput.value.replace(/[^\d]/g, '');
        rentPriceInput.value = priceValue;
    });
    
    rentPriceInput.addEventListener('keypress', function(e) {
        if ([46, 8, 9, 27, 13, 110].indexOf(e.keyCode) !== -1 ||
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>