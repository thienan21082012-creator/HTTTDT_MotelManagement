<?php
// File reset tài khoản admin - CHỈ DÀNH CHO DEVELOPMENT
session_start();
require_once 'includes/db';

$page_title = "Reset Admin";
require_once 'includes/header.php';

// Chỉ cho phép chạy trong môi trường development
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    die('File này chỉ có thể chạy trong môi trường development!');
}

echo '<div class="card">';
echo '<h2><i class="fas fa-user-shield"></i> Reset tài khoản Admin</h2>';
echo '<p><strong>⚠️ Cảnh báo:</strong> File này sẽ xóa và tạo lại tài khoản admin!</p>';
echo '</div>';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_admin'])) {
    try {
        $conn->begin_transaction();
        
        // Xóa tất cả tài khoản admin cũ
        $delete_sql = "DELETE FROM users WHERE role = 'admin'";
        $delete_result = $conn->query($delete_sql);
        
        if (!$delete_result) {
            throw new Exception("Lỗi khi xóa admin cũ: " . $conn->error);
        }
        
        // Tạo tài khoản admin mới với mật khẩu admin123
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
        $insert_stmt = $conn->prepare($insert_sql);
        
        // Tạo 2 tài khoản admin
        $admins = [
            ['admin', $admin_password],
            ['admin_user', $admin_password]
        ];
        
        foreach ($admins as $admin) {
            $insert_stmt->bind_param("ss", $admin[0], $admin[1]);
            if (!$insert_stmt->execute()) {
                throw new Exception("Lỗi khi tạo admin: " . $insert_stmt->error);
            }
        }
        
        $conn->commit();
        
        echo '<div class="alert alert-success">';
        echo '<i class="fas fa-check-circle"></i> Reset tài khoản admin thành công!';
        echo '<br><strong>Username:</strong> admin';
        echo '<br><strong>Password:</strong> admin123';
        echo '<br><strong>Username 2:</strong> admin_user';
        echo '<br><strong>Password 2:</strong> admin123';
        echo '</div>';
        
    } catch (Exception $e) {
        $conn->rollback();
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-circle"></i> Lỗi: ' . $e->getMessage();
        echo '</div>';
    }
}

// Hiển thị tài khoản admin hiện tại
echo '<div class="card">';
echo '<h3><i class="fas fa-list"></i> Tài khoản Admin hiện tại</h3>';

$sql = "SELECT id, username, role FROM users WHERE role = 'admin'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo '<table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">';
    echo '<thead>';
    echo '<tr style="background: #f8f9fa;">';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">ID</th>';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">Username</th>';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">Role</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . $row['id'] . '</td>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . htmlspecialchars($row['username']) . '</td>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . htmlspecialchars($row['role']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p>Không có tài khoản admin nào.</p>';
}
echo '</div>';

// Form reset
echo '<div class="card">';
echo '<h3><i class="fas fa-tools"></i> Reset Admin</h3>';
echo '<p>Nhấn nút bên dưới để xóa tất cả tài khoản admin cũ và tạo lại với mật khẩu <strong>admin123</strong></p>';

echo '<form method="POST" style="margin-top: 1rem;">';
echo '<button type="submit" name="reset_admin" class="btn btn-warning" onclick="return confirm(\'Bạn có chắc chắn muốn reset tài khoản admin?\')">';
echo '<i class="fas fa-exclamation-triangle"></i> Reset Admin';
echo '</button>';
echo '</form>';
echo '</div>';

// Test đăng nhập
echo '<div class="card">';
echo '<h3><i class="fas fa-sign-in-alt"></i> Test đăng nhập ngay</h3>';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_login'])) {
    $test_username = $_POST['test_username'];
    $test_password = $_POST['test_password'];
    
    $test_sql = "SELECT * FROM users WHERE username = ?";
    $test_stmt = $conn->prepare($test_sql);
    $test_stmt->bind_param("s", $test_username);
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();
    $test_user = $test_result->fetch_assoc();
    
    if ($test_user && password_verify($test_password, $test_user['password'])) {
        echo '<div class="alert alert-success">';
        echo '<i class="fas fa-check-circle"></i> Đăng nhập thành công!';
        echo '<br>User ID: ' . $test_user['id'];
        echo '<br>Username: ' . $test_user['username'];
        echo '<br>Role: ' . $test_user['role'];
        echo '</div>';
    } else {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-circle"></i> Đăng nhập thất bại!';
        echo '</div>';
    }
}

echo '<form method="POST" style="margin-top: 1rem;">';
echo '<div class="form-group">';
echo '<label for="test_username"><i class="fas fa-user"></i> Username</label>';
echo '<input type="text" id="test_username" name="test_username" class="form-control" value="admin" required>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="test_password"><i class="fas fa-lock"></i> Password</label>';
echo '<input type="password" id="test_password" name="test_password" class="form-control" value="admin123" required>';
echo '</div>';

echo '<button type="submit" name="test_login" class="btn btn-primary">';
echo '<i class="fas fa-sign-in-alt"></i> Test Login';
echo '</button>';
echo '</form>';
echo '</div>';

// Links
echo '<div class="card">';
echo '<h3><i class="fas fa-link"></i> Links hữu ích</h3>';
echo '<div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">';
echo '<a href="login.php" class="btn btn-primary">';
echo '<i class="fas fa-sign-in-alt"></i> Đăng nhập';
echo '</a>';
echo '<a href="check_admin.php" class="btn btn-info">';
echo '<i class="fas fa-search"></i> Kiểm tra Admin';
echo '</a>';
echo '<a href="dashboard.php" class="btn btn-secondary">';
echo '<i class="fas fa-tachometer-alt"></i> Dashboard';
echo '</a>';
echo '</div>';
echo '</div>';

require_once 'includes/footer.php';
?>
