(function(){
'use strict';

// Solo mostrar el chat si el usuario está logueado en el frontend
if (!window._psUserId || window._psUserId <= 0) return;

var CHAT_URL = 'ajax-chat.php';
var lastId = 0;
var pollInterval = null;
var open = false;

// chat_sid: identifica la conversación, separado de PHPSESSID
// Se genera por usuario — se limpia al cerrar sesión (ver logout)
var _chatSidKey = 'ps_chat_sid_u' + window._psUserId;
var _chatSid = localStorage.getItem(_chatSidKey);
if (!_chatSid || !/^[a-zA-Z0-9_-]{20,80}$/.test(_chatSid)) {
    var _arr = new Uint8Array(24);
    (window.crypto || window.msCrypto).getRandomValues(_arr);
    _chatSid = Array.from(_arr).map(function(b){ return ('0'+b.toString(36)).slice(-2); }).join('').slice(0,32);
    localStorage.setItem(_chatSidKey, _chatSid);
}

// Inyectar estilos
var _style = document.createElement('style');
_style.textContent =
'#lc-btn{width:46px;height:46px;background:#e8233a;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.3);transition:.2s;position:relative;flex-shrink:0}' +
'#lc-btn:hover{background:#c0392b}' +
'#lc-btn i{color:#fff;font-size:22px}' +
'#lc-badge{position:absolute;top:-4px;right:-4px;background:#f39c12;color:#fff;border-radius:10px;font-size:10px;padding:1px 5px;display:none}' +
'#lc-window{position:fixed;bottom:20px;right:76px;width:300px;background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.2);z-index:1050;display:none;flex-direction:column;overflow:hidden;font-family:Arial,sans-serif}' +
'#lc-head{background:#e8233a;color:#fff;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:700}' +
'#lc-msgs{flex:1;max-height:260px;overflow-y:auto;padding:10px;display:flex;flex-direction:column;gap:6px}' +
'.lc-msg{padding:6px 10px;border-radius:8px;max-width:85%;font-size:12px;line-height:1.4;word-break:break-word}' +
'.lc-msg.user{background:#e8f4fd;align-self:flex-end;border-bottom-right-radius:2px}' +
'.lc-msg.admin{background:#f5f5f5;align-self:flex-start;border-bottom-left-radius:2px}' +
'.lc-msg .lc-time{display:block;font-size:9px;color:#aaa;margin-top:2px}' +
'#lc-input-row{display:flex;border-top:1px solid #eee;padding:8px}' +
'#lc-input{flex:1;border:1px solid #ddd;border-radius:15px;padding:5px 12px;font-size:12px;outline:none}' +
'#lc-send{background:#e8233a;color:#fff;border:none;border-radius:50%;width:30px;height:30px;margin-left:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}';
document.head.appendChild(_style);

// Crear #lc-btn e insertarlo en el stack FAB
var btn = document.createElement('div');
btn.id = 'lc-btn';
btn.innerHTML = '<i class="fa fa-comment"></i><span id="lc-badge"></span>';
var _stack = document.getElementById('ps-fab-stack');
(_stack || document.body).appendChild(btn);

// Crear #lc-window e insertarlo en body (position:fixed)
var win = document.createElement('div');
win.id = 'lc-window';
win.innerHTML =
'<div id="lc-head"><span>💬 Chat de soporte</span><span id="lc-close" style="cursor:pointer;font-size:16px">×</span></div>' +
'<div id="lc-msgs"><div style="color:#aaa;font-size:11px;text-align:center;padding:20px 0">Escríbenos, estamos aquí para ayudarte.</div></div>' +
'<div id="lc-input-row"><input id="lc-input" type="text" placeholder="Escribe tu mensaje…" maxlength="500"><button id="lc-send"><i class="fa fa-paper-plane"></i></button></div>';
document.body.appendChild(win);
var msgs = document.getElementById('lc-msgs');
var inp = document.getElementById('lc-input');
var badge = document.getElementById('lc-badge');

function fmtTime(dt){
    if(!dt) return '';
    var d=new Date(dt.replace(' ','T'));
    return d.toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'});
}

function addMsg(m){
    var div=document.createElement('div');
    div.className='lc-msg '+m.sender;
    div.innerHTML=escHtml(m.message)+'<span class="lc-time">'+fmtTime(m.created_at)+'</span>';
    msgs.appendChild(div);
    msgs.scrollTop=msgs.scrollHeight;
}

function escHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

function handleAuthError(){
    // Sesión expirada en el servidor — detener polling y ocultar botón
    clearInterval(pollInterval);
    btn.style.display='none';
    win.style.display='none';
}

function poll(){
    var xhr=new XMLHttpRequest();
    xhr.open('POST', CHAT_URL, true);
    xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    xhr.onload=function(){
        try{
            var r=JSON.parse(xhr.responseText);
            if(r.error === 'auth_required' || r.error === 'forbidden'){ handleAuthError(); return; }
            if(r.msgs && r.msgs.length){
                r.msgs.forEach(function(m){
                    addMsg(m);
                    lastId=Math.max(lastId, parseInt(m.id));
                });
                if(!open && r.msgs.some(function(m){ return m.sender==='admin'; })){
                    badge.textContent='!'; badge.style.display='block';
                }
            }
        }catch(e){}
    };
    xhr.send('action=poll&last_id='+lastId+'&chat_sid='+encodeURIComponent(_chatSid));
}

function sendMsg(){
    var msg=inp.value.trim();
    if(!msg) return;
    inp.value='';
    addMsg({sender:'user',message:msg,created_at:null});
    var xhr=new XMLHttpRequest();
    xhr.open('POST', CHAT_URL, true);
    xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    xhr.onload=function(){
        try{
            var r=JSON.parse(xhr.responseText);
            if(r.error === 'auth_required' || r.error === 'forbidden'){ handleAuthError(); return; }
            if(r.ok && r.id) lastId=Math.max(lastId, parseInt(r.id));
        }catch(e){}
    };
    xhr.send('action=send&msg='+encodeURIComponent(msg)+'&chat_sid='+encodeURIComponent(_chatSid));
}

btn.addEventListener('click',function(){
    open=!open;
    win.style.display=open?'flex':'none';
    if(open){ badge.style.display='none'; inp.focus(); }
});
document.getElementById('lc-close').addEventListener('click',function(){ open=false; win.style.display='none'; });
document.getElementById('lc-send').addEventListener('click',sendMsg);
inp.addEventListener('keypress',function(e){ if(e.key==='Enter') sendMsg(); });

setTimeout(function(){
    poll();
    pollInterval=setInterval(poll, 2000);
}, 1000);
})();
