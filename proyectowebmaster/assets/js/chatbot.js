/* Chatbot de soporte — Personal Shopper */
(function(){
    var _open = false;

    var _faq = [
        { k: ['envio','envío','shipping','cuando llega','cuándo llega','tiempo entrega'], r: 'Los envíos normalmente tardan 3-5 días hábiles. Puedes rastrear tu pedido en <a href="track-order.php">Rastrear pedido</a>.' },
        { k: ['devolucion','devolución','devolver','reembolso','cambio'], r: 'Aceptamos devoluciones hasta 30 días después de la compra. Consulta nuestra <a href="devoluciones.php">Política de devoluciones</a>.' },
        { k: ['pago','pagar','tarjeta','efectivo','metodo pago','método de pago'], r: 'Aceptamos tarjetas de crédito/débito, transferencia bancaria y pago contra entrega. Selecciona tu método al hacer checkout.' },
        { k: ['carrito','agregar','comprar'], r: 'Puedes agregar productos al carrito desde la ficha de cada producto o usando el botón "Agregar" en el catálogo.' },
        { k: ['cuenta','registro','registrar','login','iniciar sesion','iniciar sesión'], r: 'Crea tu cuenta en <a href="sign-up.php">Registrarse</a> o inicia sesión en <a href="login.php">Iniciar sesión</a>.' },
        { k: ['descuento','cupon','cupón','promo','oferta'], r: 'Puedes ingresar tu código de descuento en el carrito de compras antes de confirmar el pedido.' },
        { k: ['contacto','contactar','hablar','asesor','humano','telefono','teléfono','whatsapp'], r: 'Puedes contactarnos en la <a href="contact.php">página de contacto</a> o directamente por WhatsApp.' },
        { k: ['orden','pedido','estado'], r: 'Para consultar el estado de tu pedido visita <a href="track-order.php">Rastrear pedido</a> o revisa <a href="my-account.php">Mi cuenta</a>.' },
        { k: ['wishlist','lista deseos','favorito'], r: 'Guarda productos en tu <a href="my-wishlist.php">Lista de deseos</a> haciendo clic en el ícono de corazón en cada producto.' },
        { k: ['hola','buenas','buen dia','buen día','buenos dias','buenos días'], r: '¡Hola! Soy el asistente virtual de la tienda. ¿En qué puedo ayudarte hoy?' },
        { k: ['gracias','ok','perfecto','excelente','genial'], r: '¡De nada! Si tienes más preguntas, aquí estoy 😊' },
        { k: ['adios','adiós','bye','hasta luego','chao'], r: '¡Hasta luego! Fue un placer ayudarte.' },
    ];

    function _match(msg) {
        var m = msg.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');
        for (var i = 0; i < _faq.length; i++) {
            for (var j = 0; j < _faq[i].k.length; j++) {
                if (m.indexOf(_faq[i].k[j].normalize('NFD').replace(/[̀-ͯ]/g,'')) > -1) return _faq[i].r;
            }
        }
        return 'No estoy seguro de cómo ayudarte con eso. Puedes contactarnos directamente en <a href="contact.php">Contacto</a> o por WhatsApp para atención personalizada.';
    }

    function _addMsg(who, text) {
        var list = document.getElementById('ps-chat-msgs');
        var div = document.createElement('div');
        div.className = 'ps-cm-' + who;
        div.innerHTML = text;
        list.appendChild(div);
        list.scrollTop = list.scrollHeight;
    }

    function _send() {
        var inp = document.getElementById('ps-chat-input');
        var msg = inp.value.trim();
        if (!msg) return;
        inp.value = '';
        _addMsg('user', _esc(msg));
        setTimeout(function(){ _addMsg('bot', _match(msg)); }, 400);
    }

    function _esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    document.addEventListener('DOMContentLoaded', function(){
        var style = document.createElement('style');
        style.textContent =
            '#ps-chat-fab{width:46px;height:46px;border-radius:50%;background:#c0396b;color:#fff;border:none;font-size:20px;cursor:pointer;box-shadow:0 2px 10px rgba(192,57,107,.4);display:flex;align-items:center;justify-content:center;transition:transform .2s;position:relative;flex-shrink:0}' +
            '#ps-chat-fab:hover{transform:scale(1.08)}' +
            '#ps-chat-box{position:fixed;bottom:138px;right:20px;width:300px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.15);z-index:9980;display:none;flex-direction:column;overflow:hidden;border:1px solid #eee}' +
            '#ps-chat-box.open{display:flex}' +
            '#ps-chat-head{background:#e8233a;color:#fff;padding:12px 14px;font-weight:700;font-size:13px;display:flex;align-items:center;gap:8px}' +
            '#ps-chat-head .ps-ch-dot{width:8px;height:8px;border-radius:50%;background:#7fff7f;display:inline-block}' +
            '#ps-chat-msgs{flex:1;padding:12px;overflow-y:auto;max-height:220px;display:flex;flex-direction:column;gap:8px;font-size:12px}' +
            '.ps-cm-bot{background:#f5f5f5;padding:8px 10px;border-radius:8px 8px 8px 0;align-self:flex-start;max-width:90%;line-height:1.5}' +
            '.ps-cm-bot a{color:#e8233a}' +
            '.ps-cm-user{background:#e8233a;color:#fff;padding:8px 10px;border-radius:8px 8px 0 8px;align-self:flex-end;max-width:90%;line-height:1.5}' +
            '#ps-chat-footer{display:flex;border-top:1px solid #eee;padding:6px 8px;gap:6px}' +
            '#ps-chat-input{flex:1;border:1px solid #eee;border-radius:6px;padding:6px 8px;font-size:12px;outline:none}' +
            '#ps-chat-send{background:#e8233a;color:#fff;border:none;border-radius:6px;padding:6px 12px;font-size:12px;cursor:pointer;font-weight:700}';
        document.head.appendChild(style);

        var fab = document.createElement('button');
        fab.id = 'ps-chat-fab';
        fab.title = 'Chat de soporte';
        fab.innerHTML = '<i class="fa fa-comments"></i>';

        var box = document.createElement('div');
        box.id = 'ps-chat-box';
        box.innerHTML =
            '<div id="ps-chat-head"><span class="ps-ch-dot"></span> Soporte en línea</div>' +
            '<div id="ps-chat-msgs"></div>' +
            '<div id="ps-chat-footer">' +
            '<input id="ps-chat-input" type="text" placeholder="Escribe tu pregunta…">' +
            '<button id="ps-chat-send">→</button>' +
            '</div>';

        (document.getElementById('ps-fab-stack') || document.body).appendChild(fab);
        document.body.appendChild(box);

        // Greeting
        _addMsg('bot', '¡Hola! Soy el asistente virtual. ¿En qué puedo ayudarte?<br><small style="color:#aaa">Pregunta sobre envíos, pagos, devoluciones…</small>');

        fab.addEventListener('click', function(){
            _open = !_open;
            box.classList.toggle('open', _open);
            if (_open) document.getElementById('ps-chat-input').focus();
        });

        document.getElementById('ps-chat-send').addEventListener('click', _send);
        document.getElementById('ps-chat-input').addEventListener('keypress', function(e){ if (e.key === 'Enter') _send(); });
    });
})();
