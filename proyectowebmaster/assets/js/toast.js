/* =============================================
   Toast Notification System — Personal Shopper
   ============================================= */
var _pendingToasts = window._pendingToasts || [];

function showToast(msg, type) {
    type = type || 'info';
    if (document.getElementById('ps-toast-container')) {
        _doToast(msg, type);
    } else {
        _pendingToasts.push({ msg: msg, type: type });
    }
}

function _doToast(msg, type) {
    var icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
    var colors = { success: '#276c3a', error: '#c0392b', info: '#2471a3', warning: '#d68910' };
    var bgs    = { success: '#e9f7ef', error: '#fdecea', info: '#eaf4fb', warning: '#fef9e7' };
    var icon   = icons[type]  || icons.info;
    var color  = colors[type] || colors.info;
    var bg     = bgs[type]    || bgs.info;

    var $t = $('<div class="ps-toast"></div>').css({
        background: bg,
        borderLeft: '4px solid ' + color,
        color: '#333'
    }).html(
        '<i class="fa ' + icon + '" style="color:' + color + ';margin-right:8px"></i>' +
        '<span>' + msg + '</span>' +
        '<button class="ps-toast-close" onclick="$(this).parent().remove()">&times;</button>'
    );

    $('#ps-toast-container').append($t);
    setTimeout(function () { $t.addClass('ps-toast-show'); }, 10);
    setTimeout(function () { $t.removeClass('ps-toast-show'); setTimeout(function(){ $t.remove(); }, 400); }, 4000);
}

$(document).ready(function () {
    if (!document.getElementById('ps-toast-container')) {
        $('body').append(
            '<style>' +
            '#ps-toast-container{position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px;max-width:320px;}' +
            '.ps-toast{padding:12px 14px;border-radius:6px;box-shadow:0 4px 14px rgba(0,0,0,.13);display:flex;align-items:center;font-size:13.5px;opacity:0;transform:translateX(20px);transition:opacity .35s,transform .35s;position:relative;}' +
            '.ps-toast.ps-toast-show{opacity:1;transform:translateX(0);}' +
            '.ps-toast-close{margin-left:auto;background:none;border:none;font-size:16px;cursor:pointer;line-height:1;color:#999;padding:0 0 0 10px;}' +
            '.ps-toast-close:hover{color:#333}' +
            '@media(max-width:480px){#ps-toast-container{left:12px;right:12px;max-width:none;top:auto;bottom:16px;}}' +
            '</style>' +
            '<div id="ps-toast-container"></div>'
        );
    }
    _pendingToasts.forEach(function (t) { _doToast(t.msg, t.type); });
    _pendingToasts = [];
});
