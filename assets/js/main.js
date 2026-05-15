// assets/js/main.js — Frontend JS

// ── Auto-dismiss alerts ──────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Auto dismiss alerts after 4s
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 4000);
    });

    // ── Smooth scroll top button ─────────────────────
    const scrollBtn = document.createElement('button');
    scrollBtn.innerHTML = '&#8679;';
    scrollBtn.className = 'scroll-top-btn';
    scrollBtn.setAttribute('aria-label', 'Lên đầu trang');
    scrollBtn.style.cssText = `
        position:fixed; bottom:24px; right:24px;
        width:44px; height:44px; border-radius:50%;
        background:var(--primary,#f97316); color:#fff;
        border:none; font-size:1.4rem; cursor:pointer;
        box-shadow:0 4px 16px rgba(249,115,22,.4);
        z-index:9999; opacity:0; transition:all .3s ease;
        display:flex; align-items:center; justify-content:center;
    `;
    document.body.appendChild(scrollBtn);

    window.addEventListener('scroll', function () {
        scrollBtn.style.opacity = window.scrollY > 400 ? '1' : '0';
        scrollBtn.style.pointerEvents = window.scrollY > 400 ? 'auto' : 'none';
    });
    scrollBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ── Format price inputs ──────────────────────────
    document.querySelectorAll('input[name="gia_min"], input[name="gia_max"]').forEach(function (inp) {
        inp.addEventListener('input', function () {
            if (this.value) {
                // Basic validation: positive only
                if (parseInt(this.value) < 0) this.value = 0;
            }
        });
    });

    // ── Image lazy load fallback ─────────────────────
    document.querySelectorAll('img[loading="lazy"]').forEach(function (img) {
        img.addEventListener('error', function () {
            this.src = (window.BASE_URL || '') + '/assets/images/no-image.png';
        });
    });
});
