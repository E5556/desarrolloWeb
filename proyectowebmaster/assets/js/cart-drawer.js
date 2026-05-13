/* ═══════════════════════════════════════
   CART DRAWER — Lógica completa
   ═══════════════════════════════════════ */
(function ($) {
  'use strict';

  var FREE_SHIPPING_THRESHOLD = 150000; // COP

  /* ─── Abrir / Cerrar ─────────────────── */
  function openDrawer()  {
    $('#cart-drawer').addClass('open');
    $('#cart-overlay').addClass('active');
    $('body').css('overflow', 'hidden');
  }
  function closeDrawer() {
    $('#cart-drawer').removeClass('open');
    $('#cart-overlay').removeClass('active');
    $('body').css('overflow', '');
  }

  /* ─── Render del carrito ─────────────── */
  function renderCart(data) {
    // Actualizar badge contador
    var count = data.count || 0;
    $('#cart-count-badge').text(count);
    // Actualizar header: contador y total
    $('#header-cart-count').text(count);
    $('.basket-item-count .count').text(count);
    $('#header-cart-total').text(data.total);

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
    $('#fs-message').text(freeShipMsg);
    $('#fs-bar').css('width', pct + '%');

    // Items
    var $items = $('#drawer-items-list');
    $items.empty();

    if (!data.items || data.items.length === 0) {
      $items.html(
        '<div class="drawer-empty">' +
          '<i class="fa fa-shopping-cart"></i>' +
          '<p>Tu carrito está vacío</p>' +
        '</div>'
      );
      $('#drawer-footer').hide();
      return;
    }

    $('#drawer-footer').show();

    data.items.forEach(function (item) {
      var html =
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
      $items.append(html);
    });

    // Total
    $('#drawer-subtotal').text('$' + data.total);
  }

  /* ─── Llamada AJAX ───────────────────── */
  function cartRequest(action, id, qty, callback) {
    $.post('ajax-cart.php', { action: action, id: id, qty: qty }, function (res) {
      if (res.success) {
        renderCart(res);
        if (callback) callback(res);
      }
    }, 'json');
  }

  /* ─── Utilidades ─────────────────────── */
  function numberFormat(n) {
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  function escapeHtml(str) {
    return $('<div>').text(str).html();
  }

  /* ─── Init ───────────────────────────── */
  $(document).ready(function () {

    // Cargar estado inicial del carrito (sin acción)
    cartRequest('status', 0, 0, null);

    /* Botones "Agregar al carrito" — interceptar todos */
    $(document).on('click', '.btn-add-to-cart', function (e) {
      e.preventDefault();
      var id = $(this).data('id');
      cartRequest('add', id, 1, function () {
        openDrawer();
      });
    });

    /* Cerrar drawer */
    $('#cart-overlay, #cart-drawer .drawer-close').on('click', closeDrawer);

    /* Cambiar cantidad + */
    $(document).on('click', '.btn-qty-plus', function () {
      var id = $(this).data('id');
      var currentQty = parseInt($(this).closest('.qty-control').find('.qty-value').text());
      cartRequest('update', id, currentQty + 1, null);
    });

    /* Cambiar cantidad - */
    $(document).on('click', '.btn-qty-minus', function () {
      var id = $(this).data('id');
      var currentQty = parseInt($(this).closest('.qty-control').find('.qty-value').text());
      cartRequest('update', id, currentQty - 1, null);
    });

    /* Eliminar item */
    $(document).on('click', '.item-remove', function () {
      var id = $(this).data('id');
      cartRequest('remove', id, 0, null);
    });

    /* Icono del carrito en header abre el drawer */
    $(document).on('click', '.lnk-cart', function (e) {
      e.preventDefault();
      cartRequest('status', 0, 0, function () {
        openDrawer();
      });
    });

    /* Seguir comprando cierra el drawer */
    $(document).on('click', '#btn-keep-shopping', function (e) {
      e.preventDefault();
      closeDrawer();
    });

    /* ESC cierra */
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
    });
  });

}(jQuery));
