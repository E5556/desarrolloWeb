/* In-site Notifications Bell — Personal Shopper */
(function(){
    if (!document.getElementById('ps-notif-bell')) return; // only if logged in

    var _open = false;
    var _items = [];

    function psNotifLoad() {
        fetch('ajax-notifications.php?action=list')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.ok) return;
                _items = d.items;
                psNotifRender(d.unread);
            }).catch(function(){});
    }

    function psNotifRender(unread) {
        var badge = document.getElementById('ps-notif-badge');
        if (badge) {
            badge.textContent = unread > 9 ? '9+' : (unread || '');
            badge.style.display = unread > 0 ? 'flex' : 'none';
        }
        var list = document.getElementById('ps-notif-list');
        if (!list) return;
        if (!_items.length) {
            list.innerHTML = '<div style="padding:20px;text-align:center;color:#aaa;font-size:13px">Sin notificaciones</div>';
            return;
        }
        list.innerHTML = _items.map(function(n){
            var t = n.time ? n.time.substring(0,16).replace('T',' ') : '';
            return '<a href="' + (n.link || '#') + '" class="ps-notif-item' + (n.read ? '' : ' ps-notif-unread') + '" data-id="' + n.id + '">' +
                '<div class="ps-notif-msg">' + psHEsc(n.msg) + '</div>' +
                '<div class="ps-notif-time">' + t + '</div></a>';
        }).join('');
    }

    function psNotifMarkAll() {
        fetch('ajax-notifications.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_read'})
            .then(function(){ _items.forEach(function(n){ n.read=true; }); psNotifRender(0); });
    }

    function psHEsc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    document.addEventListener('DOMContentLoaded', function(){
        var style = document.createElement('style');
        style.textContent =
            '#ps-notif-wrap{position:relative;display:inline-block}' +
            '#ps-notif-bell{background:none;border:none;cursor:pointer;padding:4px 6px;position:relative;color:inherit;font-size:18px}' +
            '#ps-notif-badge{position:absolute;top:-2px;right:-2px;background:#e8233a;color:#fff;font-size:9px;font-weight:700;width:16px;height:16px;border-radius:50%;display:none;align-items:center;justify-content:center}' +
            '#ps-notif-panel{position:absolute;top:calc(100% + 8px);right:0;width:300px;background:#fff;border:1px solid #eee;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.13);z-index:9995;display:none}' +
            '#ps-notif-panel.open{display:block}' +
            '.ps-notif-head{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #f0f0f0;font-weight:700;font-size:13px}' +
            '#ps-notif-list{max-height:280px;overflow-y:auto}' +
            '.ps-notif-item{display:block;padding:10px 14px;text-decoration:none;color:#333;border-bottom:1px solid #f8f8f8;font-size:12px;transition:background .15s}' +
            '.ps-notif-item:hover{background:#fdf0f5;text-decoration:none}' +
            '.ps-notif-unread{background:#fffbf0}' +
            '.ps-notif-msg{font-size:12px;line-height:1.4;margin-bottom:2px}' +
            '.ps-notif-time{font-size:10px;color:#aaa}' +
            '.ps-notif-footer{padding:8px 14px;text-align:center;border-top:1px solid #f0f0f0}' +
            '.ps-notif-footer button{background:none;border:none;color:#e8233a;font-size:11px;cursor:pointer}';
        document.head.appendChild(style);

        var wrap = document.getElementById('ps-notif-wrap');
        var bell = document.getElementById('ps-notif-bell');
        var panel = document.createElement('div');
        panel.id = 'ps-notif-panel';
        panel.innerHTML =
            '<div class="ps-notif-head"><span>Notificaciones</span></div>' +
            '<div id="ps-notif-list"><div style="padding:20px;text-align:center;font-size:13px;color:#aaa">Cargando…</div></div>' +
            '<div class="ps-notif-footer"><button onclick="psNotifMarkAll()">Marcar todo como leído</button></div>';
        wrap.appendChild(panel);

        bell.addEventListener('click', function(e){
            e.stopPropagation();
            _open = !_open;
            panel.classList.toggle('open', _open);
            if (_open) psNotifLoad();
        });
        document.addEventListener('click', function(e){
            if (!wrap.contains(e.target)) { _open = false; panel.classList.remove('open'); }
        });
        panel.addEventListener('click', function(e){
            var item = e.target.closest('.ps-notif-item');
            if (item) {
                var nid = item.dataset.id;
                fetch('ajax-notifications.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_read&id='+nid});
                item.classList.remove('ps-notif-unread');
            }
        });

        // Initial load
        psNotifLoad();
        // Refresh every 60s
        setInterval(psNotifLoad, 60000);
    });

    window.psNotifMarkAll = psNotifMarkAll;
})();
