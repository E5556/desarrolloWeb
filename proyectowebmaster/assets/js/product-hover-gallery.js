/**
 * Product Hover Gallery
 * Mueve el cursor izquierda/derecha sobre la imagen para ver más fotos del producto.
 */
(function () {
    'use strict';

    var CSS = [
        '.ps-img-wrap { position:relative; overflow:hidden; cursor:ew-resize; }',
        '.ps-img-wrap img { transition: opacity .18s ease; }',
        '.ps-img-dots { position:absolute; bottom:7px; left:50%; transform:translateX(-50%);',
        '  display:flex; gap:5px; z-index:10; pointer-events:none; }',
        '.ps-img-dot { width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,.5);',
        '  transition:background .2s, transform .2s; }',
        '.ps-img-dot.active { background:#fff; transform:scale(1.3); }',
        '.ps-img-zones { position:absolute; inset:0; display:flex; z-index:9; }',
        '.ps-img-zone { flex:1; }'
    ].join('\n');

    var style = document.createElement('style');
    style.textContent = CSS;
    document.head.appendChild(style);

    function init() {
        document.querySelectorAll('.image').forEach(function (container) {
            if (container.dataset.hoverGallery) return;
            container.dataset.hoverGallery = '1';

            var img = container.querySelector('img');
            if (!img) return;

            // Collect images from data-img0, data-img1, data-img2, ... (any number)
            var srcs = [];
            var i = 0;
            while (true) {
                var val = img.dataset['img' + i];
                if (i === 0) val = val || img.dataset.echo || img.src;
                if (!val || val.indexOf('blank.gif') !== -1) { if (i > 0) break; }
                else srcs.push(val);
                i++;
                if (i > 20) break; // límite de seguridad
            }

            if (srcs.length === 0) return;

            // Wrap image
            container.classList.add('ps-img-wrap');

            if (srcs.length <= 1) return; // Solo una imagen, sin efecto

            var current = 0;

            // Dots indicator
            var dotsEl = document.createElement('div');
            dotsEl.className = 'ps-img-dots';
            srcs.forEach(function (_, i) {
                var d = document.createElement('span');
                d.className = 'ps-img-dot' + (i === 0 ? ' active' : '');
                dotsEl.appendChild(d);
            });
            container.appendChild(dotsEl);

            function setImage(idx) {
                if (idx === current) return;
                current = idx;
                img.style.opacity = '0';
                setTimeout(function () {
                    img.src = srcs[idx];
                    img.style.opacity = '1';
                }, 80);
                dotsEl.querySelectorAll('.ps-img-dot').forEach(function (d, i) {
                    d.classList.toggle('active', i === idx);
                });
            }

            container.addEventListener('mousemove', function (e) {
                var rect = container.getBoundingClientRect();
                var x = e.clientX - rect.left;
                // Sensibilidad: el cambio ocurre en los primeros 2/3 del ancho
                // dejando el resto del espacio como zona de la última imagen
                var pct = Math.min(x / (rect.width * 0.65), 1);
                var idx = Math.min(Math.floor(pct * srcs.length), srcs.length - 1);
                setImage(idx);
            });

            container.addEventListener('mouseleave', function () {
                setImage(0);
            });
        });
    }

    // Run after page load and after echo.js renders images
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(init, 400);
        });
    } else {
        setTimeout(init, 400);
    }

    // Re-run when echo.js loads images (it fires no event, so poll briefly)
    var _tries = 0;
    var _interval = setInterval(function () {
        init();
        if (++_tries > 10) clearInterval(_interval);
    }, 600);

})();
