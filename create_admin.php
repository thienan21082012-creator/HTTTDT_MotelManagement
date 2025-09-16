<?php
// File tạo tài khoản admin - CHỈ SỬ DỤNG TRONG DEVELOPMENT
session_start();
require_once 'includes/db';

$page_title = "Tạo tài khoản Admin";
require_once 'includes/header.php';

// Chỉ cho phép chạy trong môi trường development
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    die('File này chỉ có thể chạy trong môi trường development!');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        try {
            if ($action === 'create') {
                // Tạo tài khoản admin mới
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $username, $hashed_password);
                
                if ($stmt->execute()) {
                    $message = "Tạo tài khoản admin thành công!";
                } else {
                    $error = "Lỗi khi tạo tài khoản: " . $stmt->error;
                }
                
            } elseif ($action === 'reset') {
                // Reset mật khẩu admin
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "UPDATE users SET password = ? WHERE username = ? AND role = 'admin'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $hashed_password, $username);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = "Reset mật khẩu admin thành công!";
                    } else {
                        $error = "Không tìm thấy tài khoản admin với username: " . $username;
                    }
                } else {
                    $error = "Lỗi khi reset mật khẩu: " . $stmt->error;
                }
            }
        } catch (Exception $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy danh sách admin hiện tại
$admin_sql = "SELECT id, username, role FROM users WHERE role = 'admin'";
$admin_result = $conn->query($admin_sql);
$admins = [];
if ($admin_result) {
    while ($row = $admin_result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>

<div class="card">
    <h2><i class="fas fa-user-shield"></i> Quản lý tài khoản Admin</h2>
    <p><strong>⚠️ Cảnh báo:</strong> File này chỉ dành cho môi trường development!</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3><i class="fas fa-list"></i> Danh sách Admin hiện tại</h3>
    <?php if (!empty($admins)): ?>
        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="border: 1px solid #dee2e6; padding: 8px;">ID</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px;">Username</th>
                    <th style="border: 1px solid #dee2e6; padding: 8px;">Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td style="border: 1px solid #dee2e6; padding: 8px;"><?php echo $admin['id']; ?></td>
                        <td style="border: 1px solid #dee2e6; padding: 8px;"><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td style="border: 1px solid #dee2e6; padding: 8px;"><?php echo htmlspecialchars($admin['role']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Không có tài khoản admin nào.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3><i class="fas fa-plus"></i> Tạo tài khoản Admin mới</h3>
    <form method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="action" value="create">
        
        <div class="form-group">
            <label for="create_username"><i class="fas fa-user"></i> Username</label>
            <input type="text" id="create_username" name="username" class="form-control" required placeholder="Nhập username">
        </div>
        
        <div class="form-group">
            <label for="create_password"><i class="fas fa-lock"></i> Password</label>
            <input type="password" id="create_password" name="password" class="form-control" required placeholder="Nhập password">
        </div>
        
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus"></i> Tạo Admin
        </button>
    </form>
</div>

<div class="card">
    <h3><i class="fas fa-key"></i> Reset mật khẩu Admin</h3>
    <form method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="action" value="reset">
        
        <div class="form-group">
            <label for="reset_username"><i class="fas fa-user"></i> Username</label>
            <input type="text" id="reset_username" name="username" class="form-control" required placeholder="Nhập username cần reset">
        </div>
        
        <div class="form-group">
            <label for="reset_password"><i class="fas fa-lock"></i> Password mới</label>
            <input type="password" id="reset_password" name="password" class="form-control" required placeholder="Nhập password mới">
        </div>
        
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-key"></i> Reset Password
        </button>
    </form>
</div>

<div class="card">
    <h3><i class="fas fa-info-circle"></i> Thông tin đăng nhập mặc định</h3>
    <div style="background: rgba(40, 167, 69, 0.1); padding: 1rem; border-radius: 10px; border-left: 4px solid #28a745;">
        <p><strong>Username:</strong> admin</p>
        <p><strong>Password:</strong> admin123</p>
        <p><strong>Hoặc:</strong></p>
        <p><strong>Username:</strong> admin_user</p>
        <p><strong>Password:</strong> admin123</p>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-link"></i> Links hữu ích</h3>
    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
        <a href="login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Đăng nhập
        </a>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="test_momo_integration.php" class="btn btn-info">
            <i class="fas fa-vial"></i> Test MoMo
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
