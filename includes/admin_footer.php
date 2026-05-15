<?php // includes/admin_footer.php ?>
    </div><!-- end admin-content -->
</div><!-- end admin-main -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle for mobile
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    document.getElementById('adminSidebar').classList.toggle('open');
});
// Confirm delete
document.querySelectorAll('.confirm-delete').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        if (!confirm('Bạn chắc chắn muốn xóa? Hành động này không thể hoàn tác.')) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
