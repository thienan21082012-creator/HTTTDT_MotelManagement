<?php
$page_title = "Dashboard Admin";
require_once 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

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

// Lấy thống kê
$stats = [];
$stats['total_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$stats['available_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")->fetch_assoc()['count'];
$stats['occupied_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied'")->fetch_assoc()['count'];
$stats['reserved_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'reserved'")->fetch_assoc()['count'];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'guest'")->fetch_assoc()['count'];
$stats['unpaid_bills'] = $conn->query("SELECT COUNT(*) as count FROM bills WHERE status = 'unpaid'")->fetch_assoc()['count'];

// Lấy danh sách phòng và thông tin người thuê (nếu có)
// Kiểm tra tương thích: nếu bảng rooms CHƯA có cột contract_duration/start_date thì fallback về payments
$has_contract_cols = false;
$check_col = $conn->query("SHOW COLUMNS FROM rooms LIKE 'contract_duration'");
if ($check_col && $check_col->num_rows > 0) { $has_contract_cols = true; }

if ($has_contract_cols) {
    $sql_rooms = "SELECT 
        r.*, 
        p.user_id, 
        COALESCE(r.contract_duration, p.contract_duration) AS contract_duration,
        COALESCE(r.start_date, p.start_date) AS start_date,
        er.old_reading,
        er.new_reading,
        er.reading_date,
        res.user_id as reserved_user_id
    FROM rooms r 
    LEFT JOIN (
        SELECT pp1.*
        FROM payments pp1
        JOIN (
            SELECT room_id, MAX(id) AS max_id
            FROM payments
            GROUP BY room_id
        ) latest ON pp1.id = latest.max_id
    ) p ON r.id = p.room_id
    LEFT JOIN reservations res ON r.id = res.room_id
    LEFT JOIN electricity_readings er ON r.id = er.room_id AND er.id = (
        SELECT MAX(id) FROM electricity_readings WHERE room_id = r.id
    )
    ORDER BY r.room_number ASC";
} else {
    $sql_rooms = "SELECT 
        r.*, 
        p.user_id, 
        p.contract_duration AS contract_duration,
        p.start_date AS start_date,
        er.old_reading,
        er.new_reading,
        er.reading_date,
        res.user_id as reserved_user_id
    FROM rooms r 
    LEFT JOIN (
        SELECT pp1.*
        FROM payments pp1
        JOIN (
            SELECT room_id, MAX(id) AS max_id
            FROM payments
            GROUP BY room_id
        ) latest ON pp1.id = latest.max_id
    ) p ON r.id = p.room_id
    LEFT JOIN reservations res ON r.id = res.room_id
    LEFT JOIN electricity_readings er ON r.id = er.room_id AND er.id = (
        SELECT MAX(id) FROM electricity_readings WHERE room_id = r.id
    )
    ORDER BY r.room_number ASC";
}
$rooms_result = $conn->query($sql_rooms);
if ($rooms_result === false) {
    $_SESSION['error_message'] = 'Lỗi truy vấn danh sách phòng: ' . $conn->error;
}

// Lấy danh sách người dùng
$users_result = $conn->query("SELECT * FROM users");

// Lọc hóa đơn theo tháng/năm cho admin
$filter_month = isset($_GET['bill_month']) && $_GET['bill_month'] !== '' ? (int)$_GET['bill_month'] : null;
$filter_year = isset($_GET['bill_year']) && $_GET['bill_year'] !== '' ? (int)$_GET['bill_year'] : null;

if ($filter_month && $filter_year) {
    $bills_sql = "SELECT b.*, r.room_number, u.username FROM bills b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id WHERE b.billing_month = ? AND b.billing_year = ? ORDER BY b.created_at DESC";
    $bills_stmt = $conn->prepare($bills_sql);
    $bills_stmt->bind_param('ii', $filter_month, $filter_year);
    $bills_stmt->execute();
    $bills_admin_result = $bills_stmt->get_result();
} elseif ($filter_month && !$filter_year) {
    $bills_sql = "SELECT b.*, r.room_number, u.username FROM bills b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id WHERE b.billing_month = ? ORDER BY b.created_at DESC";
    $bills_stmt = $conn->prepare($bills_sql);
    $bills_stmt->bind_param('i', $filter_month);
    $bills_stmt->execute();
    $bills_admin_result = $bills_stmt->get_result();
} elseif (!$filter_month && $filter_year) {
    $bills_sql = "SELECT b.*, r.room_number, u.username FROM bills b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id WHERE b.billing_year = ? ORDER BY b.created_at DESC";
    $bills_stmt = $conn->prepare($bills_sql);
    $bills_stmt->bind_param('i', $filter_year);
    $bills_stmt->execute();
    $bills_admin_result = $bills_stmt->get_result();
} else {
    $bills_sql = "SELECT b.*, r.room_number, u.username FROM bills b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC LIMIT 100";
    $bills_admin_result = $conn->query($bills_sql);
}
?>

<div class="card">
    <h2><i class="fas fa-tachometer-alt"></i> Dashboard Admin</h2>
    <p>Chào mừng, <strong><?php echo $_SESSION['username']; ?></strong>! Đây là trang quản trị hệ thống.</p>
</div>

<!-- Hóa đơn đã tạo -->
<div class="card">
    <h3><i class="fas fa-file-invoice-dollar"></i> Hóa đơn đã tạo</h3>
    <form method="GET" action="dashboard.php" style="margin: 1rem 0; display: flex; gap: 1rem; align-items: end;">
        <div class="form-group">
            <label for="bill_month"><i class="fas fa-calendar-alt"></i> Tháng</label>
            <select id="bill_month" name="bill_month" class="form-control">
                <option value="">Tất cả</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($filter_month === $m) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="bill_year"><i class="fas fa-calendar"></i> Năm</label>
            <input id="bill_year" type="number" name="bill_year" class="form-control" min="2000" max="2100" value="<?php echo $filter_year ? $filter_year : date('Y'); ?>">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="dashboard.php" class="btn" style="margin-left: 0.5rem;"><i class="fas fa-times"></i> Xóa lọc</a>
        </div>
    </form>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-user"></i> Khách hàng</th>
                    <th><i class="fas fa-bed"></i> Phòng</th>
                    <th><i class="fas fa-calendar"></i> Tháng/Năm</th>
                    <th><i class="fas fa-money-bill-wave"></i> Tiền thuê</th>
                    <th><i class="fas fa-bolt"></i> Tiền điện</th>
                    <th><i class="fas fa-tint"></i> Tiền nước</th>
                    <th><i class="fas fa-cogs"></i> Phí dịch vụ</th>
                    <th><i class="fas fa-calculator"></i> Tổng</th>
                    <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                    <th><i class="fas fa-clock"></i> Tạo lúc</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bills_admin_result && $bills_admin_result->num_rows > 0): ?>
                    <?php while($b = $bills_admin_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['username']); ?></td>
                            <td><?php echo htmlspecialchars($b['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($b['billing_month'] . '/' . $b['billing_year']); ?></td>
                            <td><?php echo number_format($b['rent_amount']); ?> VND</td>
                            <td><?php echo number_format($b['electricity_amount']); ?> VND</td>
                            <td><?php echo number_format($b['water_amount']); ?> VND</td>
                            <td><?php echo number_format($b['service_fee']); ?> VND</td>
                            <td><strong><?php echo number_format($b['total_amount']); ?> VND</strong></td>
                            <td>
                                <span class="room-status <?php echo ($b['status'] == 'unpaid') ? 'status-occupied' : 'status-available'; ?>">
                                    <?php echo ($b['status'] == 'unpaid') ? 'Chưa thanh toán' : 'Đã thanh toán'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($b['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align:center; color:#666;">Không có hóa đơn phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Thống kê -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_rooms']; ?></div>
        <div class="stat-label"><i class="fas fa-bed"></i> Tổng số phòng</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['available_rooms']; ?></div>
        <div class="stat-label"><i class="fas fa-check-circle"></i> Phòng trống</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['occupied_rooms']; ?></div>
        <div class="stat-label"><i class="fas fa-user"></i> Phòng đã thuê</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['reserved_rooms']; ?></div>
        <div class="stat-label"><i class="fas fa-clock"></i> Phòng đã cọc</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
        <div class="stat-label"><i class="fas fa-users"></i> Khách hàng</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['unpaid_bills']; ?></div>
        <div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Hóa đơn chưa thanh toán</div>
    </div>
</div>

<!-- Quản lý phòng trọ -->
<div class="card">
    <h3><i class="fas fa-plus-circle"></i> Thêm phòng mới</h3>
    <form action="dashboard.php" method="POST">
        <input type="hidden" name="add_room" value="1">
        <div class="form-row">
            <div class="form-group">
                <label for="room_number"><i class="fas fa-bed"></i> Số phòng</label>
                <input type="text" id="room_number" name="room_number" class="form-control" required placeholder="VD: 101, 102...">
            </div>
            <div class="form-group">
                <label for="rent_price"><i class="fas fa-money-bill-wave"></i> Giá thuê (VND)</label>
                <input type="number" id="rent_price" name="rent_price" class="form-control" step="1000" required placeholder="VD: 2000000">
            </div>
        </div>
        <div class="form-group">
            <label for="description"><i class="fas fa-info-circle"></i> Mô tả phòng</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Mô tả chi tiết về phòng..."></textarea>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus"></i> Thêm phòng
        </button>
    </form>
</div>

<!-- Tạo hóa đơn -->
<div class="card">
    <h3><i class="fas fa-file-invoice"></i> Tạo hóa đơn hàng tháng</h3>
    <p>Tạo hóa đơn cho tất cả phòng đang thuê trong tháng hiện tại.</p>
    <form action="generate_bills.php" method="POST">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-file-invoice"></i> Tạo hóa đơn cho tháng <?php echo date('m/Y'); ?>
        </button>
    </form>
</div>
    
<!-- Danh sách phòng -->
<div class="card">
    <h3><i class="fas fa-list"></i> Danh sách phòng</h3>
    <div class="room-grid">
        <?php if ($rooms_result && $rooms_result->num_rows > 0): ?>
        <?php while($row = $rooms_result->fetch_assoc()): ?>
            <div class="room-card">
                <div class="room-number">Phòng <?php echo htmlspecialchars($row['room_number']); ?></div>
                <div class="room-price"><?php echo number_format($row['rent_price']); ?> VND/tháng</div>
                <div class="room-description"><?php echo htmlspecialchars($row['description']); ?></div>
                
                <div class="room-status status-<?php echo $row['status']; ?>">
                    <?php 
                    $status_text = [
                        'available' => 'Trống',
                        'occupied' => 'Đã thuê',
                        'reserved' => 'Đã cọc'
                    ];
                    echo $status_text[$row['status']];
                    ?>
                </div>
                
                <?php if ($row['status'] == 'occupied'): ?>
                    <div style="margin: 1rem 0; padding: 1rem; background: rgba(40, 167, 69, 0.1); border-radius: 10px;">
                        <?php if ($row['user_id'] && $row['user_id'] != 0): ?>
                            <p><strong><i class="fas fa-user"></i> Thuê bởi:</strong> User ID <?php echo htmlspecialchars($row['user_id']); ?></p>
                        <?php else: ?>
                            <p><strong><i class="fas fa-user"></i> Thuê bởi:</strong> Admin (không có thông tin user)</p>
                        <?php endif; ?>
                        <?php if ($row['contract_duration']): ?>
                            <p><strong><i class="fas fa-calendar"></i> Thời hạn:</strong> <?php echo htmlspecialchars($row['contract_duration']); ?> tháng</p>
                        <?php endif; ?>
                        <?php if ($row['start_date']): ?>
                            <p><strong><i class="fas fa-calendar-check"></i> Ngày nhận:</strong> <?php echo htmlspecialchars($row['start_date']); ?></p>
                        <?php endif; ?>
                        <?php if ($row['reading_date']): ?>
                            <p><strong><i class="fas fa-bolt"></i> Điện:</strong> <?php echo htmlspecialchars($row['old_reading']); ?> → <?php echo htmlspecialchars($row['new_reading']); ?></p>
                            <p><strong><i class="fas fa-calendar"></i> Ngày ghi:</strong> <?php echo htmlspecialchars($row['reading_date']); ?></p>
                        <?php else: ?>
                            <p><i class="fas fa-exclamation-triangle"></i> Chưa có thông tin điện</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($row['status'] == 'reserved'): ?>
                    <div style="margin: 1rem 0; padding: 1rem; background: rgba(255, 193, 7, 0.1); border-radius: 10px;">
                        <?php if ($row['reserved_user_id']): ?>
                            <p><strong><i class="fas fa-user-clock"></i> Đã cọc bởi:</strong> User ID <?php echo htmlspecialchars($row['reserved_user_id']); ?></p>
                        <?php else: ?>
                            <p><strong><i class="fas fa-user-clock"></i> Trạng thái:</strong> Đã cọc (chưa có thông tin người cọc)</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <a href="edit_room.php?id=<?php echo $row['id']; ?>" class="btn" style="flex: 1; text-align: center; padding: 0.5rem;">
                        <i class="fas fa-edit"></i> Sửa
                    </a>
                    <a href="dashboard.php?delete_room=<?php echo $row['id']; ?>" 
                       class="btn btn-danger" 
                       style="flex: 1; text-align: center; padding: 0.5rem;"
                       onclick="return confirmDelete('Bạn có chắc chắn muốn xóa phòng này?')">
                        <i class="fas fa-trash"></i> Xóa
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
        <?php else: ?>
            <div class="card" style="grid-column: 1 / -1;">
                <div style="text-align:center; color:#666; padding: 1rem;">Không có phòng để hiển thị.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cập nhật chỉ số điện -->
<div class="card">
    <h3><i class="fas fa-bolt"></i> Cập nhật chỉ số điện</h3>
    <form action="update_electricity.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="room_select"><i class="fas fa-bed"></i> Chọn phòng</label>
                <select name="room_id" id="room_select" class="form-control" onchange="updateOldReading()" required>
                    <option value="">-- Chọn phòng --</option>
                    <?php
                    // Lấy lại danh sách phòng
                    $rooms_for_select = $conn->query("SELECT id, room_number FROM rooms");
                    while($room = $rooms_for_select->fetch_assoc()): ?>
                        <option value="<?php echo $room['id']; ?>">Phòng <?php echo htmlspecialchars($room['room_number']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reading_date_input"><i class="fas fa-calendar"></i> Ngày ghi</label>
                <input type="date" name="reading_date" id="reading_date_input" class="form-control" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="old_reading_input"><i class="fas fa-arrow-left"></i> Số điện cũ</label>
                <input type="number" name="old_reading" id="old_reading_input" class="form-control" readonly required placeholder="Sẽ tự động điền">
            </div>
            <div class="form-group">
                <label for="new_reading_input"><i class="fas fa-arrow-right"></i> Số điện mới</label>
                <input type="number" name="new_reading" id="new_reading_input" class="form-control" required placeholder="Nhập số điện mới">
            </div>
        </div>
        
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Cập nhật chỉ số
        </button>
    </form>
</div>

<!-- Quản lý người dùng -->
<div class="card">
    <h3><i class="fas fa-users"></i> Quản lý người dùng</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-user"></i> Tên đăng nhập</th>
                    <th><i class="fas fa-user-tag"></i> Vai trò</th>
                    <th><i class="fas fa-cogs"></i> Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>
                            <span class="room-status <?php echo $row['role'] == 'admin' ? 'status-occupied' : 'status-available'; ?>">
                                <?php echo $row['role'] == 'admin' ? 'Quản trị viên' : 'Khách hàng'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['role'] != 'admin'): ?>
                                <a href="dashboard.php?delete_user=<?php echo $row['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirmDelete('Bạn có chắc chắn muốn xóa người dùng này?')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            <?php else: ?>
                                <span style="color: #666;"><i class="fas fa-shield-alt"></i> Không thể xóa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

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

<?php require_once 'includes/footer.php'; ?>