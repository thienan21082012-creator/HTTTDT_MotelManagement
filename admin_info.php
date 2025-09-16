<?php
// File hiển thị thông tin đăng nhập admin
$page_title = "Thông tin Admin";
require_once 'includes/header.php';
?>

<div class="card">
    <h2><i class="fas fa-user-shield"></i> Thông tin đăng nhập Admin</h2>
    <p>Dưới đây là thông tin đăng nhập admin mặc định:</p>
</div>

<div class="card">
    <h3><i class="fas fa-key"></i> Tài khoản Admin có sẵn</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;">
        <div style="background: rgba(40, 167, 69, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #28a745;">
            <h4><i class="fas fa-user"></i> Tài khoản 1</h4>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p><strong>Role:</strong> admin</p>
        </div>
        
        <div style="background: rgba(40, 167, 69, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #28a745;">
            <h4><i class="fas fa-user"></i> Tài khoản 2</h4>
            <p><strong>Username:</strong> admin_user</p>
            <p><strong>Password:</strong> admin123</p>
            <p><strong>Role:</strong> admin</p>
        </div>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-info-circle"></i> Tài khoản khách hàng test</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;">
        <div style="background: rgba(102, 126, 234, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #667eea;">
            <h4><i class="fas fa-user"></i> Khách hàng 1</h4>
            <p><strong>Username:</strong> customer1</p>
            <p><strong>Password:</strong> customer1</p>
            <p><strong>Role:</strong> guest</p>
        </div>
        
        <div style="background: rgba(102, 126, 234, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #667eea;">
            <h4><i class="fas fa-user"></i> Khách hàng 2</h4>
            <p><strong>Username:</strong> customer2</p>
            <p><strong>Password:</strong> customer2</p>
            <p><strong>Role:</strong> guest</p>
        </div>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-link"></i> Links hữu ích</h3>
    <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
        <a href="login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Đăng nhập
        </a>
        <a href="create_admin.php" class="btn btn-warning">
            <i class="fas fa-user-plus"></i> Tạo Admin
        </a>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="test_momo_integration.php" class="btn btn-info">
            <i class="fas fa-vial"></i> Test MoMo
        </a>
        <a href="view_rooms.php" class="btn btn-success">
            <i class="fas fa-bed"></i> Xem phòng
        </a>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng</h3>
    <div style="background: rgba(255, 193, 7, 0.1); padding: 1rem; border-radius: 10px; border-left: 4px solid #ffc107;">
        <ul>
            <li>Tài khoản admin có quyền truy cập vào dashboard và quản lý hệ thống</li>
            <li>Tài khoản guest chỉ có thể xem phòng và đặt phòng</li>
            <li>Mật khẩu được mã hóa bằng bcrypt trong database</li>
            <li>File <code>create_admin.php</code> chỉ nên sử dụng trong môi trường development</li>
        </ul>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
