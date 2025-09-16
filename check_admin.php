<?php
// File kiểm tra và tạo lại tài khoản admin
session_start();
require_once 'includes/db';

$page_title = "Kiểm tra Admin";
require_once 'includes/header.php';

echo '<div class="card">';
echo '<h2><i class="fas fa-user-shield"></i> Kiểm tra tài khoản Admin</h2>';
echo '</div>';

// Kiểm tra tài khoản admin hiện tại
echo '<div class="card">';
echo '<h3><i class="fas fa-list"></i> Tài khoản Admin hiện tại trong database</h3>';

$sql = "SELECT id, username, password, role FROM users WHERE role = 'admin'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo '<table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">';
    echo '<thead>';
    echo '<tr style="background: #f8f9fa;">';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">ID</th>';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">Username</th>';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">Password Hash</th>';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">Role</th>';
    echo '<th style="border: 1px solid #dee2e6; padding: 8px;">Test Login</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . $row['id'] . '</td>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . htmlspecialchars($row['username']) . '</td>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px; font-size: 12px;">' . substr($row['password'], 0, 20) . '...</td>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . htmlspecialchars($row['role']) . '</td>';
        
        // Test password
        $test_passwords = ['admin123', 'admin', 'password', '123456'];
        $password_works = false;
        $working_password = '';
        
        foreach ($test_passwords as $test_pass) {
            if (password_verify($test_pass, $row['password'])) {
                $password_works = true;
                $working_password = $test_pass;
                break;
            }
        }
        
        if ($password_works) {
            echo '<td style="border: 1px solid #dee2e6; padding: 8px; color: green;">✓ ' . $working_password . '</td>';
        } else {
            echo '<td style="border: 1px solid #dee2e6; padding: 8px; color: red;">✗ Không khớp</td>';
        }
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p>Không có tài khoản admin nào trong database.</p>';
}
echo '</div>';

// Tạo lại tài khoản admin với mật khẩu đúng
echo '<div class="card">';
echo '<h3><i class="fas fa-tools"></i> Tạo lại tài khoản Admin</h3>';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_admin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Xóa tài khoản admin cũ nếu tồn tại
        $delete_sql = "DELETE FROM users WHERE username = ? AND role = 'admin'";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("s", $username);
        $delete_stmt->execute();
        
        // Tạo tài khoản admin mới
        $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ss", $username, $hashed_password);
        
        if ($insert_stmt->execute()) {
            echo '<div class="alert alert-success">';
            echo '<i class="fas fa-check-circle"></i> Tạo tài khoản admin thành công!';
            echo '</div>';
        } else {
            echo '<div class="alert alert-error">';
            echo '<i class="fas fa-exclamation-circle"></i> Lỗi: ' . $insert_stmt->error;
            echo '</div>';
        }
    } else {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-circle"></i> Vui lòng nhập đầy đủ thông tin!';
        echo '</div>';
    }
}

echo '<form method="POST" style="margin-top: 1rem;">';
echo '<div class="form-group">';
echo '<label for="username"><i class="fas fa-user"></i> Username</label>';
echo '<input type="text" id="username" name="username" class="form-control" value="admin" required>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="password"><i class="fas fa-lock"></i> Password</label>';
echo '<input type="password" id="password" name="password" class="form-control" value="admin123" required>';
echo '</div>';

echo '<button type="submit" name="create_admin" class="btn btn-success">';
echo '<i class="fas fa-plus"></i> Tạo lại Admin';
echo '</button>';
echo '</form>';
echo '</div>';

// Test đăng nhập
echo '<div class="card">';
echo '<h3><i class="fas fa-sign-in-alt"></i> Test đăng nhập</h3>';

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

// Links hữu ích
echo '<div class="card">';
echo '<h3><i class="fas fa-link"></i> Links hữu ích</h3>';
echo '<div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">';
echo '<a href="login.php" class="btn btn-primary">';
echo '<i class="fas fa-sign-in-alt"></i> Đăng nhập';
echo '</a>';
echo '<a href="dashboard.php" class="btn btn-secondary">';
echo '<i class="fas fa-tachometer-alt"></i> Dashboard';
echo '</a>';
echo '<a href="admin_info.php" class="btn btn-info">';
echo '<i class="fas fa-info-circle"></i> Thông tin Admin';
echo '</a>';
echo '</div>';
echo '</div>';

require_once 'includes/footer.php';
?>
