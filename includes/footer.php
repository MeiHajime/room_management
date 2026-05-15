<?php // includes/footer.php ?>
<!-- FOOTER -->
<footer class="footer-main">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand mb-2">Vinh<span>Rooms</span></div>
                <p style="font-size:.88rem;color:rgba(255,255,255,.6);line-height:1.7">
                    Nền tảng tìm kiếm và đăng phòng trọ uy tín hàng đầu. Kết nối người thuê và chủ trọ một cách nhanh chóng, tiện lợi.
                </p>
                <div class="d-flex gap-2 mt-2">
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle p-1" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle p-1" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle p-1" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div style="font-size:.85rem;font-weight:700;color:#fff;margin-bottom:.75rem;">Tìm phòng</div>
                <a href="<?= BASE_URL ?>/search.php?tinh_thanh=TP.HCM" class="footer-link">TP. Hồ Chí Minh</a>
                <a href="<?= BASE_URL ?>/search.php?tinh_thanh=Hà Nội" class="footer-link">Hà Nội</a>
                <a href="<?= BASE_URL ?>/search.php?tinh_thanh=Đà Nẵng" class="footer-link">Đà Nẵng</a>
                <a href="<?= BASE_URL ?>/search.php?tinh_thanh=Vinh" class="footer-link">Vinh</a>
                <a href="<?= BASE_URL ?>/search.php" class="footer-link">Xem tất cả →</a>
            </div>
            <div class="col-6 col-lg-2">
                <div style="font-size:.85rem;font-weight:700;color:#fff;margin-bottom:.75rem;">Hỗ trợ</div>
                <a href="#" class="footer-link">Hướng dẫn</a>
                <a href="#" class="footer-link">Liên hệ</a>
                <a href="#" class="footer-link">Báo cáo lỗi</a>
            </div>
            <div class="col-lg-4">
                <div style="font-size:.85rem;font-weight:700;color:#fff;margin-bottom:.75rem;">Thông tin liên hệ</div>
                <div class="footer-link"><i class="bi bi-telephone me-2 text-warning"></i>0900 000 000</div>
                <div class="footer-link"><i class="bi bi-envelope me-2 text-warning"></i>support@phongtro.vn</div>
                <div class="footer-link"><i class="bi bi-geo-alt me-2 text-warning"></i>TP. Hồ Chí Minh, Việt Nam</div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> VinhRooms — Bản quyền thuộc về nhóm phát triển.
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
