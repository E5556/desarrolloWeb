/* ═══════════════════════════════════════
   CART DRAWER — Lógica completa
   Usa fetch + event delegation nativa (sin depender de $(document).ready)
   ═══════════════════════════════════════ */
(function () {
  'use strict';

  var FREE_SHIPPING_THRESHOLD = 150000; // COP

  /* ─── Abrir / Cerrar ─────────────────── */
  function openDrawer() {
    var d = document.getElementById('cart-drawer');
    var o = document.getElementById('cart-overlay');
    if (d) d.classList.add('open');
    if (o) o.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer() {
    var d = document.getElementById('cart-drawer');
    var o = document.getElementById('cart-overlay');
    if (d) d.classList.remove('open');
    if (o) o.classList.remove('active');
    document.body.style.overflow = '';
  }

  /* ─── Render del carrito ─────────────── */
  function renderCart(data) {
    var count = data.count || 0;

    // Actualizar badges de cantidad
    var badges = document.querySelectorAll('#cart-count-badge, #header-cart-count, .basket-item-count .count');
    badges.forEach(function(el) {
      el.textContent = count;
      el.style.display = count > 0 ? '' : 'none';
    });

    var totalEl = document.getElementById('header-cart-total');
    if (totalEl) {
      totalEl.textContent = data.total ? '$' + data.total : '';
      totalEl.style.display = data.total ? '' : 'none';
    }

    // Barra de envío gratis
    var totalNum = 0;
    if (data.items) {
      data.items.forEach(function(it) { totalNum += it.subtotal; });
    }
    var pct = Math.min(100, Math.round((totalNum / FREE_SHIPPING_THRESHOLD) * 100));
    var remaining = Math.max(0, FREE_SHIPPING_THRESHOLD - totalNum);
    var freeShipMsg = pct >= 100
      ? '🎉 ¡Envío gratis aplicado!'
      : '¡Te faltan $' + numberFormat(remaining) + ' para envío gratis!';
    var fsMsg = document.getElementById('fs-message');
    var fsBar = document.getElementById('fs-bar');
    if (fsMsg) fsMsg.textContent = freeShipMsg;
    if (fsBar) fsBar.style.width = pct + '%';

    // Items
    var $items = document.getElementById('drawer-items-list');
    var footer  = document.getElementById('drawer-footer');
    if (!$items) return;

    if (!data.items || data.items.length === 0) {
      $items.innerHTML =
        '<div class="drawer-empty">' +
          '<i class="fa fa-shopping-cart"></i>' +
          '<p>Tu carrito está vacío</p>' +
        '</div>';
      if (footer) footer.style.display = 'none';
      return;
    }

    if (footer) footer.style.display = '';

    var html = '';
    data.items.forEach(function (item) {
      html +=
        '<div class="cart-item-row" data-id="' + item.id + '">' +
          '<img class="item-img" src="' + item.image + '" alt="">' +
          '<div class="item-info">' +
            '<div class="item-name">' + escapeHtml(item.name) + '</div>' +
            '<div class="item-price">$' + numberFormat(item.price) + '</div>' +
            '<div class="qty-control">' +
              '<button class="qty-btn btn-qty-minus" data-id="' + item.id + '">−</button>' +
              '<span class="qty-value">' + item.qty + '</span>' +
              '<button class="qty-btn btn-qty-plus" data-id="' + item.id + '">+</button>' +
            '</div>' +
            '<button class="item-remove" data-id="' + item.id + '">Eliminar</button>' +
          '</div>' +
        '</div>';
    });
    $items.innerHTML = html;

    var subEl = document.getElementById('drawer-subtotal');
    if (subEl) subEl.textContent = '$' + data.total;
  }

  /* ─── AJAX con fetch ─────────────────── */
  function cartRequest(action, id, qty, callback, vid) {
    var body = 'action='     + encodeURIComponent(action) +
               '&id='        + encodeURIComponent(id) +
               '&qty='       + encodeURIComponent(qty) +
               '&variant_id='+ encodeURIComponent(vid || 0);
    fetch('ajax-cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        renderCart(res);
        if (callback) callback(res);
      }
    })
    .catch(function() {});
  }

  /* ─── Utilidades ─────────────────────── */
  function numberFormat(n) {
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  function escapeHtml(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  /* ─── Init con delegación nativa ─────── */
  function init() {
    // Carga estado inicial
    cartRequest('status', 0, 0, null);

    // Delegación sobre document (funciona para elementos dinámicos)
    document.addEventListener('click', function(e) {
      var t = e.target;

      // Subir al botón si el clic fue en un hijo (ej. el ícono <i>)
      while (t && t !== document) {
        // Agregar al carrito
        if (t.classList && t.classList.contains('btn-add-to-cart')) {
          e.preventDefault();
          var id  = t.getAttribute('data-id');
          var vid = t.getAttribute('data-variant-id') || '0';
          cartRequest('add', id, 1, function() { openDrawer(); }, vid);
          return;
        }
        // Icono carrito header
        if (t.classList && t.classList.contains('lnk-cart')) {
          e.preventDefault();
          cartRequest('status', 0, 0, function() { openDrawer(); });
          return;
        }
        // Aumentar cantidad
        if (t.classList && t.classList.contains('btn-qty-plus')) {
          var row = t.closest('.cart-item-row');
          var qEl = row ? row.querySelector('.qty-value') : null;
          var qty = qEl ? parseInt(qEl.textContent) : 1;
          cartRequest('update', t.getAttribute('data-id'), qty + 1, null);
          return;
        }
        // Reducir cantidad
        if (t.classList && t.classList.contains('btn-qty-minus')) {
          var row2 = t.closest('.cart-item-row');
          var qEl2 = row2 ? row2.querySelector('.qty-value') : null;
          var qty2 = qEl2 ? parseInt(qEl2.textContent) : 1;
          cartRequest('update', t.getAttribute('data-id'), qty2 - 1, null);
          return;
        }
        // Eliminar item
        if (t.classList && t.classList.contains('item-remove')) {
          cartRequest('remove', t.getAttribute('data-id'), 0, null);
          return;
        }
        // Cerrar drawer (botón X o overlay)
        if (t.classList && (t.classList.contains('drawer-close') || t.id === 'cart-overlay')) {
          closeDrawer();
          return;
        }
        // Seguir comprando
        if (t.id === 'btn-keep-shopping') {
          e.preventDefault();
          closeDrawer();
          return;
        }
        t = t.parentNode;
      }
    });

    // ESC cierra
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeDrawer();
    });
  }

  // Ejecutar init cuando el DOM esté listo (funciona con o sin jQuery)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init(); // DOM ya está listo
  }

})();
