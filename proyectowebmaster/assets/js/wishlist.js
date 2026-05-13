/* ── Wishlist AJAX + animación de corazón ─────────────────────────────────── */
(function () {

    // ── Toast notification ───────────────────────────────────────────────────
    function showToast(msg, type) {
        var t = document.createElement('div');
        t.className = 'wl-toast wl-toast-' + (type || 'ok');
        t.innerHTML = '<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>'
                   + '<span>' + msg + '</span>';
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('wl-toast-show'); });
        setTimeout(function () {
            t.classList.remove('wl-toast-show');
            setTimeout(function () { t.parentNode && t.parentNode.removeChild(t); }, 400);
        }, 2800);
    }

    // ── Floating heart animation ─────────────────────────────────────────────
    function floatHeart(btn) {
        var rect = btn.getBoundingClientRect();
        var h = document.createElement('div');
        h.className = 'wl-float-heart';
        h.innerHTML = '❤';
        h.style.left = (rect.left + rect.width  / 2) + 'px';
        h.style.top  = (rect.top  + rect.height / 2) + 'px';
        document.body.appendChild(h);
        setTimeout(function () { h.parentNode && h.parentNode.removeChild(h); }, 1000);
    }

    // ── Handle click on any wishlist button ──────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-wl-pid]');
        if (!btn) return;
        e.preventDefault();

        var pid = btn.getAttribute('data-wl-pid');

        floatHeart(btn);

        var fd = new FormData();
        fd.append('pid', pid);

        fetch('ajax-wishlist.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok === false && data.msg === 'login') {
                    showToast('Inicia sesión para guardar favoritos', 'warn');
                    return;
                }
                if (data.already) {
                    showToast(data.msg, 'warn');
                    return;
                }
                // mark button as active
                btn.classList.add('wl-added');
                var icon = btn.querySelector('i, svg');
                if (icon) icon.style.color = '#e0245e';
                showToast(data.msg, 'ok');
            })
            .catch(function () {
                showToast('Error de conexión', 'warn');
            });
    });

})();
