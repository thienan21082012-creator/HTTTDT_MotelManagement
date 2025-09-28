        </div>
    </main>
    
    <footer style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 2rem 0; margin-top: 3rem; text-align: center; color: white;">
        <div class="container">
            <p>&copy; 2025 Hệ thống quản lý CHDV. Tất cả quyền được bảo lưu.</p>
            <p>Được phát triển với <i class="fas fa-heart" style="color: #ff6b6b;"></i> bởi Nhóm 1 - Hệ thống thanh toán điện tử</p>
        </div>
    </footer>
    
    <script>
        // Hiển thị thông báo tự động ẩn sau 5 giây
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Xác nhận trước khi xóa
        function confirmDelete(message = 'Bạn có chắc chắn muốn xóa?') {
            return confirm(message);
        }
        
        // Hiển thị loading khi submit form
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<span class="loading"></span> Đang xử lý...';
                        submitBtn.disabled = true;
                    }
                });
            });
        });
    </script>
</body>
</html>
