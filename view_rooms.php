<?php
$page_title = "Danh sách phòng trống";
require_once 'includes/header.php';

$sql = "SELECT * FROM rooms WHERE status = 'available'";
$result = $conn->query($sql);
?>

<div class="card">
    <h2><i class="fas fa-bed"></i> Danh sách phòng trọ trống</h2>
    <p>Xin chào, <strong><?php echo $_SESSION['username'] ?? 'khách'; ?></strong>! Dưới đây là danh sách các phòng trống hiện có.</p>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="room-grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="room-card">
                <div class="room-number">Phòng <?php echo htmlspecialchars($row['room_number']); ?></div>
                <div class="room-price"><?php echo number_format($row['rent_price']); ?> VND/tháng</div>
                <div class="room-description"><?php echo htmlspecialchars($row['description']); ?></div>
                
                <div class="room-status status-available">
                    <i class="fas fa-check-circle"></i> Trống
                </div>

                <?php
                // Tìm tất cả ảnh trong thư mục: .../CĂN {room_id}
                $roomId = (int)$row['id'];
                $imageDirBase = __DIR__ . DIRECTORY_SEPARATOR . 'HÌNH CĂN HỘ CHUNG CƯ-20250923T112113Z-1-001' . DIRECTORY_SEPARATOR . 'HÌNH CĂN HỘ CHUNG CƯ' . DIRECTORY_SEPARATOR . 'CĂN ' . $roomId;
                $imageUrls = [];
                if (is_dir($imageDirBase)) {
                    $candidates = glob($imageDirBase . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
                    if (!empty($candidates)) {
                        natsort($candidates);
                        foreach ($candidates as $absPath) {
                            $relative = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $absPath);
                            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
                            $segments = array_map(function($seg) { return rawurlencode($seg); }, explode('/', $relative));
                            $imageUrls[] = implode('/', $segments);
                        }
                    }
                }
                ?>

                <?php if (!empty($imageUrls)): ?>
                    <div class="carousel" data-autoplay="5000">
                        <div class="carousel-viewport">
                            <div class="carousel-track">
                                <?php foreach ($imageUrls as $url): ?>
                                    <div class="carousel-slide">
                                        <img src="<?php echo htmlspecialchars($url); ?>" alt="Ảnh phòng <?php echo htmlspecialchars($row['room_number']); ?>" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button class="carousel-btn carousel-prev" aria-label="Ảnh trước"><i class="fas fa-chevron-left"></i></button>
                        <button class="carousel-btn carousel-next" aria-label="Ảnh sau"><i class="fas fa-chevron-right"></i></button>
                    </div>
                <?php else: ?>
                    <div class="room-image placeholder">
                        <i class="fas fa-image"></i>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="margin-top: 1.5rem; display: flex; gap: .75rem;">
                        <form action="add_to_cart.php" method="POST" style="flex: 1;">
                            <input type="hidden" name="room_id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                            </button>
                        </form>
                        <a href="reserve.php?room_id=<?php echo $row['id']; ?>" class="btn btn-success" style="flex: 1; text-align: center;">
                            <i class="fas fa-calendar-plus"></i> Đặt phòng
                        </a>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 1.5rem; text-align: center;">
                        <p style="color: #666; margin-bottom: 1rem;">
                            <i class="fas fa-info-circle"></i> Vui lòng đăng nhập để đặt phòng
                        </p>
                        <a href="login.php" class="btn" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var carousels = document.querySelectorAll('.carousel');
        carousels.forEach(function(carousel) {
            var track = carousel.querySelector('.carousel-track');
            var slides = Array.prototype.slice.call(carousel.querySelectorAll('.carousel-slide'));
            if (!track || slides.length === 0) return;

            var prevBtn = carousel.querySelector('.carousel-prev');
            var nextBtn = carousel.querySelector('.carousel-next');
            var index = 0;
            var autoplayMs = parseInt(carousel.getAttribute('data-autoplay') || '5000', 10);
            var isTouching = false;
            var startX = 0;
            var deltaX = 0;
            var threshold = 30;
            var intervalId = null;

            function update() {
                track.style.transform = 'translateX(' + (-index * 100) + '%)';
            }

            function next() {
                index = (index + 1) % slides.length;
                update();
            }

            function prev() {
                index = (index - 1 + slides.length) % slides.length;
                update();
            }

            function startAutoplay() {
                if (autoplayMs > 0) {
                    stopAutoplay();
                    intervalId = setInterval(next, autoplayMs);
                }
            }

            function stopAutoplay() {
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            }

            if (prevBtn) prevBtn.addEventListener('click', function() { prev(); startAutoplay(); });
            if (nextBtn) nextBtn.addEventListener('click', function() { next(); startAutoplay(); });

            // Touch swipe
            carousel.addEventListener('touchstart', function(e) {
                if (!e.touches || e.touches.length === 0) return;
                isTouching = true;
                startX = e.touches[0].clientX;
                deltaX = 0;
                stopAutoplay();
            }, { passive: true });

            carousel.addEventListener('touchmove', function(e) {
                if (!isTouching || !e.touches || e.touches.length === 0) return;
                deltaX = e.touches[0].clientX - startX;
            }, { passive: true });

            carousel.addEventListener('touchend', function() {
                if (!isTouching) return;
                if (Math.abs(deltaX) > threshold) {
                    if (deltaX < 0) { next(); } else { prev(); }
                } else {
                    update();
                }
                isTouching = false;
                startAutoplay();
            });

            // Pause on hover (desktop)
            carousel.addEventListener('mouseenter', stopAutoplay);
            carousel.addEventListener('mouseleave', startAutoplay);

            // Init
            track.style.transition = 'transform 0.4s ease';
            slides.forEach(function(slide) { slide.style.minWidth = '100%'; });
            update();
            startAutoplay();
        });
    });
    </script>
<?php else: ?>
    <div class="card">
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="fas fa-bed" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <h4>Không có phòng trống</h4>
            <p>Hiện tại tất cả phòng đều đã được thuê hoặc đặt chỗ. Vui lòng quay lại sau.</p>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>