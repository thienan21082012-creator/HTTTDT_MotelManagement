<?php
$page_title = "Đăng ký";
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (strlen($username) < 3) {
        $error = "Tên đăng nhập phải có ít nhất 3 ký tự.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Đăng ký thành công! Vui lòng đăng nhập.";
            header('Location: login.php');
            exit();
        } else {
            $error = "Đăng ký thất bại. Tên đăng nhập có thể đã tồn tại.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-user-plus"></i> Đăng ký</h1>
            <p>Tạo tài khoản mới để sử dụng hệ thống</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Tên đăng nhập</label>
                <input type="text" id="username" name="username" class="form-control" required 
                       placeholder="Nhập tên đăng nhập (ít nhất 3 ký tự)" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-control" required 
                       placeholder="Nhập mật khẩu (ít nhất 6 ký tự)">
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                       placeholder="Nhập lại mật khẩu">
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">
                <i class="fas fa-user-plus"></i> Đăng ký
            </button>
        </form>
        
        <div class="auth-links">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>