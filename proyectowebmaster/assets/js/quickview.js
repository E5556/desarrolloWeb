/* Quick View Modal — Personal Shopper */
(function(){
    // Inject modal HTML once
    if (document.getElementById('ps-qv-modal')) return;
    var style = '<style>' +
        '#ps-qv-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;display:none;align-items:center;justify-content:center;padding:16px}' +
        '#ps-qv-overlay.open{display:flex}' +
        '#ps-qv-modal{background:#fff;border-radius:8px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;position:relative;animation:psQvIn .2s ease}' +
        '@keyframes psQvIn{from{transform:translateY(20px);opacity:0}to{transform:none;opacity:1}}' +
        '#ps-qv-close{position:absolute;top:12px;right:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#888;z-index:2}' +
        '#ps-qv-close:hover{color:#333}' +
        '.ps-qv-body{display:flex;gap:24px;padding:28px;flex-wrap:wrap}' +
        '.ps-qv-imgs{flex:0 0 200px;min-width:140px}' +
        '.ps-qv-imgs img{width:100%;border-radius:4px;object-fit:contain;max-height:220px;cursor:pointer;border:1px solid #eee}' +
        '.ps-qv-thumb{width:52px;height:52px;object-fit:contain;border:1px solid #eee;border-radius:4px;cursor:pointer;margin-top:8px}' +
        '.ps-qv-info{flex:1;min-width:200px}' +
        '.ps-qv-info h3{font-size:17px;font-weight:700;margin:0 0 6px}' +
        '.ps-qv-rating{font-size:12px;color:#888;margin-bottom:8px}' +
        '.ps-qv-price{font-size:22px;color:#e8233a;font-weight:700;margin-bottom:6px}' +
        '.ps-qv-ship{font-size:12px;color:#888;margin-bottom:10px}' +
        '.ps-qv-avail-ok{color:#27ae60;font-size:13px}' +
        '.ps-qv-avail-ord{color:#f39c12;font-size:13px}' +
        '.ps-qv-avail-no{color:#e8233a;font-size:13px}' +
        '.ps-qv-desc{font-size:13px;color:#666;margin:10px 0 14px;line-height:1.6}' +
        '.ps-qv-actions{display:flex;gap:10px;flex-wrap:wrap}' +
        '.ps-qv-actions .btn{font-size:13px}' +
        '@media(max-width:480px){.ps-qv-body{flex-direction:column}.ps-qv-imgs{flex:none;width:100%}}' +
        '</style>';

    var html = style +
        '<div id="ps-qv-overlay">' +
        '<div id="ps-qv-modal">' +
        '<button id="ps-qv-close" title="Cerrar">&times;</button>' +
        '<div class="ps-qv-body" id="ps-qv-body"><div style="padding:40px;text-align:center;width:100%">Cargando…</div></div>' +
        '</div></div>';

    document.addEventListener('DOMContentLoaded', function(){
        document.body.insertAdjacentHTML('beforeend', html);

        document.getElementById('ps-qv-close').addEventListener('click', psQvClose);
        document.getElementById('ps-qv-overlay').addEventListener('click', function(e){
            if (e.target === this) psQvClose();
        });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') psQvClose(); });

        // Delegate clicks on .btn-quickview
        $(document).on('click', '.btn-quickview', function(e){
            e.preventDefault();
            psQvOpen($(this).data('id'));
        });
    });
})();

function psQvClose() {
    var ov = document.getElementById('ps-qv-overlay');
    if (ov) ov.classList.remove('open');
}

function psQvOpen(pid) {
    var ov = document.getElementById('ps-qv-overlay');
    var body = document.getElementById('ps-qv-body');
    if (!ov) return;
    body.innerHTML = '<div style="padding:40px;text-align:center;width:100%"><i class="fa fa-spinner fa-spin" style="font-size:28px;color:#e8233a"></i></div>';
    ov.classList.add('open');

    fetch('ajax-quickview.php?pid=' + pid)
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (!d.ok) { body.innerHTML = '<div style="padding:40px;text-align:center">No se pudo cargar el producto.</div>'; return; }

            var stars = '';
            for (var i = 1; i <= 5; i++) {
                stars += '<i class="fa fa-star' + (i <= Math.round(d.rating) ? '' : '-o') + '" style="color:#f39c12;font-size:12px"></i>';
            }
            var availHtml = d.avail === 'In Stock'
                ? '<span class="ps-qv-avail-ok"><i class="fa fa-check-circle"></i> En stock</span>'
                : d.avail === 'On Order'
                ? '<span class="ps-qv-avail-ord"><i class="fa fa-clock-o"></i> Bajo pedido</span>'
                : '<span class="ps-qv-avail-no"><i class="fa fa-times-circle"></i> Agotado</span>';

            var thumb = d.img2 ? '<br><img class="ps-qv-thumb" src="' + d.img2 + '" onerror="this.style.display=\'none\'" onclick="document.getElementById(\'ps-qv-main-img\').src=this.src">' : '';

            body.innerHTML =
                '<div class="ps-qv-imgs">' +
                '<img id="ps-qv-main-img" src="' + d.img1 + '" onerror="this.src=\'assets/images/blank.gif\'">' +
                thumb +
                '</div>' +
                '<div class="ps-qv-info">' +
                '<h3>' + psHtmlEsc(d.name) + '</h3>' +
                '<div class="ps-qv-rating">' + stars + ' <span style="margin-left:4px">(' + d.reviews + ' reseñas)</span></div>' +
                '<div class="ps-qv-price">$' + d.price.toLocaleString('es-CO') + '</div>' +
                '<div class="ps-qv-ship">' + (d.ship > 0 ? 'Envío: $' + d.ship.toLocaleString('es-CO') : '<span style="color:#27ae60">Envío gratis</span>') + '</div>' +
                availHtml +
                (d.brand ? '<div style="font-size:12px;color:#aaa;margin-top:4px">Marca: ' + psHtmlEsc(d.brand) + '</div>' : '') +
                '<p class="ps-qv-desc">' + psHtmlEsc(d.desc) + (d.desc.length >= 300 ? '…' : '') + '</p>' +
                '<div class="ps-qv-actions">' +
                '<a href="product-details.php?pid=' + d.id + '" class="btn btn-default btn-sm">Ver detalle completo</a>' +
                (d.avail !== 'Out of Stock' ? '<button class="btn btn-primary btn-sm btn-add-to-cart" data-id="' + d.id + '"><i class="fa fa-shopping-cart"></i> Agregar al carrito</button>' : '') +
                '</div></div>';
        }).catch(function(){ body.innerHTML = '<div style="padding:40px;text-align:center">Error al cargar.</div>'; });
}

function psHtmlEsc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
