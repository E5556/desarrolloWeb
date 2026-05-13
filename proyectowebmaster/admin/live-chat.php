<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chat en vivo | Admin</title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
<style>
.chat-layout { display:flex; gap:0; height: calc(100vh - 140px); min-height:400px; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.sessions-panel { width:260px; border-right:1px solid #ddd; background:#f9f9f9; overflow-y:auto; flex-shrink:0; }
.sessions-panel h5 { padding:12px 14px; margin:0; border-bottom:1px solid #ddd; font-weight:700; font-size:13px; background:#fff; }
.session-item { padding:10px 14px; border-bottom:1px solid #eee; cursor:pointer; transition:.15s; }
.session-item:hover, .session-item.active { background:#e8f4fd; }
.session-item .sid { font-size:11px; color:#888; font-family:monospace; }
.session-item .preview { font-size:11px; color:#555; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:3px; }
.session-item .unread-badge { background:#e8233a; color:#fff; border-radius:10px; font-size:10px; padding:1px 6px; float:right; }
.chat-area { flex:1; display:flex; flex-direction:column; }
.chat-msgs { flex:1; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:8px; }
.chat-empty { color:#aaa; text-align:center; padding:40px; font-size:13px; }
.msg-bubble { max-width:80%; padding:8px 12px; border-radius:10px; font-size:13px; line-height:1.4; word-break:break-word; }
.msg-bubble.user { background:#e8f4fd; align-self:flex-start; border-bottom-left-radius:2px; }
.msg-bubble.admin { background:#e8f8e8; align-self:flex-end; border-bottom-right-radius:2px; }
.msg-time { font-size:10px; color:#aaa; margin-top:2px; }
.chat-input-row { padding:10px 14px; border-top:1px solid #eee; display:flex; gap:8px; }
.chat-input-row input { flex:1; border:1px solid #ccc; border-radius:20px; padding:7px 14px; font-size:13px; }
.chat-input-row button { background:#e8233a; color:#fff; border:none; border-radius:20px; padding:7px 18px; cursor:pointer; }
.chat-header { background:#337ab7; color:#fff; padding:10px 14px; font-size:13px; font-weight:600; }
</style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content-area">
<h3><i class="icon-comment"></i> Chat en vivo</h3>
<p class="text-muted" style="font-size:12px">Chats activos de las últimas 24 horas. Las sesiones se actualizan cada 5 segundos.</p>

<div class="chat-layout">
    <div class="sessions-panel">
        <h5><i class="fa fa-users"></i> Sesiones activas</h5>
        <div id="sessions-list">
            <div style="padding:20px;color:#aaa;font-size:12px;text-align:center">Cargando…</div>
        </div>
    </div>
    <div class="chat-area">
        <div class="chat-header" id="chat-header">Selecciona una sesión para chatear</div>
        <div class="chat-msgs" id="chat-msgs">
            <div class="chat-empty"><i class="fa fa-comment-o" style="font-size:36px;margin-bottom:10px"></i><br>Selecciona una sesión del panel izquierdo.</div>
        </div>
        <div class="chat-input-row" id="chat-input-row" style="display:none">
            <input type="text" id="admin-msg-input" placeholder="Escribe tu respuesta…" maxlength="500">
            <button id="admin-send-btn"><i class="fa fa-paper-plane"></i> Enviar</button>
        </div>
    </div>
</div>

</div>
</div>
</div>
</div>

<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script>
var currentSid = null;
var lastId = 0;
var pollTimer = null;

function escHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function fmtTime(dt){ if(!dt) return ''; var d=new Date(dt.replace(' ','T')); return d.toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'}); }

function loadSessions(){
    $.post('../ajax-chat.php',{action:'sessions'},function(r){
        if(!r.sessions){ return; }
        var list=$('#sessions-list');
        if(r.sessions.length===0){ list.html('<div style="padding:16px;color:#aaa;font-size:12px;text-align:center">Sin chats activos</div>'); return; }
        var h='';
        r.sessions.forEach(function(s){
            var active = s.session_id===currentSid ? 'active':'';
            var badge = s.unread>0 ? '<span class="unread-badge">'+s.unread+'</span>' : '';
            h+='<div class="session-item '+active+'" data-sid="'+escHtml(s.session_id)+'">'+badge;
            h+='<div class="sid">'+s.session_id.substr(0,12)+'…</div>';
            h+='<div class="preview">'+(s.preview?escHtml(s.preview.substr(0,60)):'Sin mensajes')+'</div>';
            h+='<div style="font-size:10px;color:#aaa">'+s.last_msg+'</div>';
            h+='</div>';
        });
        list.html(h);
        list.find('.session-item').on('click',function(){ openSession($(this).data('sid')); });
    },'json');
}

function openSession(sid){
    currentSid=sid; lastId=0;
    $('#chat-header').text('Chat con sesión: '+sid.substr(0,20)+'…');
    $('#chat-msgs').html('');
    $('#chat-input-row').show();
    $('#sessions-list .session-item').removeClass('active');
    $('#sessions-list .session-item[data-sid="'+sid+'"]').addClass('active');
    pollAdmin();
}

function pollAdmin(){
    if(!currentSid) return;
    $.post('../ajax-chat.php',{action:'poll',target_sid:currentSid,last_id:lastId},function(r){
        if(r.msgs && r.msgs.length){
            r.msgs.forEach(function(m){
                var div=$('<div class="msg-bubble '+m.sender+'">'+escHtml(m.message)+'<div class="msg-time">'+fmtTime(m.created_at)+'</div></div>');
                $('#chat-msgs').append(div);
                lastId=Math.max(lastId,parseInt(m.id));
            });
            $('#chat-msgs').scrollTop($('#chat-msgs')[0].scrollHeight);
        }
    },'json');
}

function sendAdmin(){
    var msg=$('#admin-msg-input').val().trim();
    if(!msg||!currentSid) return;
    $('#admin-msg-input').val('');
    $.post('../ajax-chat.php',{action:'send',msg:msg,target_sid:currentSid},function(){
        pollAdmin();
    },'json');
}

$('#admin-send-btn').on('click',sendAdmin);
$('#admin-msg-input').on('keypress',function(e){ if(e.key==='Enter') sendAdmin(); });

// Auto-refresh
loadSessions();
setInterval(function(){ loadSessions(); if(currentSid) pollAdmin(); }, 5000);
</script>
</body>
</html>
