<?php
$page_title = "Trang chủ - Hệ thống quản lý Motel";
require_once 'includes/header.php';
?>

<div class="card" style="text-align: center; padding: 3rem;">
    <h1 style="font-size: 3rem; margin-bottom: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        <i class="fas fa-building"></i> Hệ thống quản lý Motel
    </h1>
    <p style="font-size: 1.2rem; color: #666; margin-bottom: 2rem;">
        Quản lý phòng trọ hiện đại, tiện lợi và dễ sử dụng
    </p>
    
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem;">
        <a href="view_rooms.php" class="btn btn-success" style="padding: 1rem 2rem; font-size: 1.1rem;">
            <i class="fas fa-bed"></i> Xem phòng trống
        </a>
        <a href="login.php" class="btn" style="padding: 1rem 2rem; font-size: 1.1rem;">
            <i class="fas fa-sign-in-alt"></i> Đăng nhập
        </a>
        <a href="register.php" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1.1rem;">
            <i class="fas fa-user-plus"></i> Đăng ký
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><i class="fas fa-bed"></i></div>
        <div class="stat-label">Phòng trọ chất lượng</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><i class="fas fa-shield-alt"></i></div>
        <div class="stat-label">Bảo mật cao</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><i class="fas fa-clock"></i></div>
        <div class="stat-label">24/7 hỗ trợ</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><i class="fas fa-mobile-alt"></i></div>
        <div class="stat-label">Giao diện thân thiện</div>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-info-circle"></i> Về hệ thống</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
        <div style="text-align: center; padding: 1.5rem;">
            <i class="fas fa-search" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
            <h4>Tìm phòng dễ dàng</h4>
            <p>Xem danh sách phòng trống với thông tin chi tiết, giá cả minh bạch.</p>
        </div>
        <div style="text-align: center; padding: 1.5rem;">
            <i class="fas fa-credit-card" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
            <h4>Thanh toán nhanh chóng</h4>
            <p>Hệ thống thanh toán trực tuyến an toàn, tiện lợi.</p>
        </div>
        <div style="text-align: center; padding: 1.5rem;">
            <i class="fas fa-file-invoice" style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
            <h4>Quản lý hóa đơn</h4>
            <p>Theo dõi hóa đơn hàng tháng, thanh toán tiện lợi.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
