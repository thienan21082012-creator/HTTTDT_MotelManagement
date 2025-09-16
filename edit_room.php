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
$sql = "SELECT r.*, COALESCE(r.contract_duration, p.contract_duration) AS contract_duration, COALESCE(r.start_date, p.start_date) AS start_date
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
    // Remove dots and commas from price, then convert to float
    $rent_price = floatval(str_replace(['.', ','], '', $_POST['rent_price']));
    $status = $_POST['status'];
    $contract_duration = !empty($_POST['contract_duration']) ? intval($_POST['contract_duration']) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    
    // Validation
    $errors = [];
    if (empty($room_number)) {
        $errors[] = "Số phòng không được để trống.";
    }
    if ($rent_price <= 0) {
        $errors[] = "Giá thuê phải lớn hơn 0.";
    }
    // Validation will be handled in the status-specific logic below
    
    if (empty($errors)) {
        // Cập nhật thông tin cơ bản của phòng
        // Update base room info first
        $update_sql = "UPDATE rooms SET room_number = ?, description = ?, rent_price = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssdsi", $room_number, $description, $rent_price, $status, $room_id);
        
        if ($update_stmt->execute()) {
            // Xử lý logic dựa trên trạng thái mới
            if ($status == 'available') {
                // Clear contract info on room when returning to available
                $clear_contract_sql = "UPDATE rooms SET contract_duration = NULL, start_date = NULL WHERE id = ?";
                $clear_contract_stmt = $conn->prepare($clear_contract_sql);
                $clear_contract_stmt->bind_param("i", $room_id);
                $clear_contract_stmt->execute();
                // Nếu phòng trở về trạng thái trống, xóa tất cả reservations và payments liên quan
                $delete_reservations_sql = "DELETE FROM reservations WHERE room_id = ?";
                $delete_reservations_stmt = $conn->prepare($delete_reservations_sql);
                $delete_reservations_stmt->bind_param("i", $room_id);
                $delete_reservations_stmt->execute();
                
                $delete_payments_sql = "DELETE FROM payments WHERE room_id = ? AND payment_type = 'deposit'";
                $delete_payments_stmt = $conn->prepare($delete_payments_sql);
                $delete_payments_stmt->bind_param("i", $room_id);
                $delete_payments_stmt->execute();
                
            } elseif ($status == 'reserved') {
                // Clear contract info on room when only reserved
                $clear_contract_sql = "UPDATE rooms SET contract_duration = NULL, start_date = NULL WHERE id = ?";
                $clear_contract_stmt = $conn->prepare($clear_contract_sql);
                $clear_contract_stmt->bind_param("i", $room_id);
                $clear_contract_stmt->execute();
                // Nếu phòng được đặt thành "đã cọc" bởi admin (không có user cụ thể)
                // Xóa các reservations cũ và không tạo mới (vì admin không chọn user)
                $delete_reservations_sql = "DELETE FROM reservations WHERE room_id = ?";
                $delete_reservations_stmt = $conn->prepare($delete_reservations_sql);
                $delete_reservations_stmt->bind_param("i", $room_id);
                $delete_reservations_stmt->execute();
                
            } elseif ($status == 'occupied') {
                // Validate required fields for occupied status
                if (empty($contract_duration) || empty($start_date)) {
                    $errors[] = "Khi đặt phòng thành 'đã thuê', thời hạn hợp đồng và ngày nhận phòng là bắt buộc.";
                } else {
                    // Persist contract info directly on rooms
                    $save_contract_sql = "UPDATE rooms SET contract_duration = ?, start_date = ? WHERE id = ?";
                    $save_contract_stmt = $conn->prepare($save_contract_sql);
                    $save_contract_stmt->bind_param("isi", $contract_duration, $start_date, $room_id);
                    $save_contract_stmt->execute();

                    // Check if there's an existing payment record for this room
                    $check_payment_sql = "SELECT id, user_id FROM payments WHERE room_id = ? AND payment_type = 'deposit' LIMIT 1";
                    $check_payment_stmt = $conn->prepare($check_payment_sql);
                    $check_payment_stmt->bind_param("i", $room_id);
                    $check_payment_stmt->execute();
                    $payment_result = $check_payment_stmt->get_result();
                    $existing_payment = $payment_result->fetch_assoc();
                    
                    if ($existing_payment) {
                        // Update existing payment record with contract details
                        $update_payment_sql = "UPDATE payments SET contract_duration = ?, start_date = ?, payment_status = 'completed' WHERE id = ?";
                        $update_payment_stmt = $conn->prepare($update_payment_sql);
                        $update_payment_stmt->bind_param("isi", $contract_duration, $start_date, $existing_payment['id']);
                        $update_payment_stmt->execute();
                        
                        // If this was a user payment, delete the reservation
                        if ($existing_payment['user_id'] > 0) {
                            $delete_res_sql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ?";
                            $delete_res_stmt = $conn->prepare($delete_res_sql);
                            $delete_res_stmt->bind_param("ii", $existing_payment['user_id'], $room_id);
                            $delete_res_stmt->execute();
                        }
                    } else {
                        // Create new payment record for admin-managed room
                        // Use user_id = 0 to indicate this is admin-managed
                        $insert_payment_sql = "INSERT INTO payments (user_id, room_id, start_date, contract_duration, total_amount, payment_status, payment_date, payment_type) VALUES (0, ?, ?, ?, ?, 'completed', NOW(), 'deposit')";
                        $insert_payment_stmt = $conn->prepare($insert_payment_sql);
                        $insert_payment_stmt->bind_param("isid", $room_id, $start_date, $contract_duration, $rent_price);
                        $insert_payment_stmt->execute();
                        
                        // Clean up any existing reservations for this room
                        $delete_res_sql = "DELETE FROM reservations WHERE room_id = ?";
                        $delete_res_stmt = $conn->prepare($delete_res_sql);
                        $delete_res_stmt->bind_param("i", $room_id);
                        $delete_res_stmt->execute();
                    }
                }
            }
            
            $_SESSION['success_message'] = "Cập nhật phòng thành công.";
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = "Có lỗi xảy ra khi cập nhật phòng.";
        }
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
    
    // Toggle contract fields based on status
    statusSelect.addEventListener('change', function() {
        if (this.value === 'occupied') {
            contractDurationGroup.style.display = 'block';
            startDateGroup.style.display = 'block';
            contractDurationSelect.required = true;
            startDateInput.required = true;
        } else {
            contractDurationGroup.style.display = 'none';
            startDateGroup.style.display = 'none';
            contractDurationSelect.required = false;
            startDateInput.required = false;
            contractDurationSelect.value = '';
            startDateInput.value = '';
        }
    });
    
    // Format price input
    const rentPriceInput = document.getElementById('rent_price');
    rentPriceInput.addEventListener('input', function() {
        let value = this.value.replace(/[^\d]/g, '');
        if (value) {
            // Format with Vietnamese number format (dots as thousands separators)
            this.value = parseInt(value).toLocaleString('vi-VN');
        }
    });
    
    // Convert formatted price back to number before submit
    document.getElementById('editRoomForm').addEventListener('submit', function() {
        const priceValue = rentPriceInput.value.replace(/[^\d]/g, '');
        rentPriceInput.value = priceValue;
    });
    
    // Allow only numbers and dots for price input
    rentPriceInput.addEventListener('keypress', function(e) {
        // Allow: backspace, delete, tab, escape, enter, decimal point
        if ([46, 8, 9, 27, 13, 110].indexOf(e.keyCode) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    startDateInput.min = today;
});
</script>

<?php include 'includes/footer.php'; ?>