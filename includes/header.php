<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'db';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Hệ thống quản lý Motel'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="<?php 
                    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
                        echo 'dashboard.php';
                    } elseif (isset($_SESSION['user_id'])) {
                        echo 'index.php';
                    } else {
                        echo 'home.php';
                    }
                ?>" class="logo">
                    <i class="fas fa-building"></i> Motel Manager
                </a>
                
                <ul class="nav-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="view_rooms.php"><i class="fas fa-bed"></i> Quản lý phòng</a></li>
                            <li><a href="generate_bills.php"><i class="fas fa-file-invoice"></i> Tạo hóa đơn</a></li>
                        <?php else: ?>
                            <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                            <li><a href="view_rooms.php"><i class="fas fa-bed"></i> Xem phòng</a></li>
                            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="view_rooms.php"><i class="fas fa-bed"></i> Xem phòng</a></li>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="user-info">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                        <a href="logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <?php if (isset($_SESSION['error_message']) && $_SESSION['error_message']): ?>
                <div class="alert alert-error" role="alert" style="margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
