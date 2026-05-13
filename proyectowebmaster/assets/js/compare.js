/* =============================================
   Comparador de productos — Personal Shopper
   ============================================= */
var PS_COMPARE_KEY = 'ps_compare';
var PS_COMPARE_MAX = 3;

function psCompareGet() {
    try { return JSON.parse(localStorage.getItem(PS_COMPARE_KEY) || '[]'); } catch(e) { return []; }
}
function psCompareSave(arr) {
    localStorage.setItem(PS_COMPARE_KEY, JSON.stringify(arr));
}
function psCompareCount() { return psCompareGet().length; }

function psCompareAdd(id, name, img, price) {
    id = String(id);
    var list = psCompareGet();
    if (list.find(function(p){ return p.id === id; })) {
        showToast('Este producto ya está en la comparación.', 'info');
        return;
    }
    if (list.length >= PS_COMPARE_MAX) {
        showToast('Máximo ' + PS_COMPARE_MAX + ' productos para comparar.', 'warning');
        return;
    }
    list.push({ id: id, name: name, img: img, price: price });
    psCompareSave(list);
    psCompareUpdateBar();
    showToast('Agregado a comparar: ' + name, 'success');
}

function psCompareRemove(id) {
    id = String(id);
    var list = psCompareGet().filter(function(p){ return p.id !== id; });
    psCompareSave(list);
    psCompareUpdateBar();
    // If on compare page, reload
    if (window.location.pathname.indexOf('compare.php') > -1) location.reload();
}

function psCompareClear() {
    psCompareSave([]);
    psCompareUpdateBar();
    if (window.location.pathname.indexOf('compare.php') > -1) location.href = 'index2.php';
}

function psCompareUpdateBar() {
    var list = psCompareGet();
    var $bar = $('#ps-compare-bar');
    if (list.length === 0) { $bar.slideUp(200); return; }

    var items = list.map(function(p){
        return '<span class="ps-cb-item" title="' + $('<div>').text(p.name).html() + '">' +
            '<img src="' + p.img + '" onerror="this.src=\'assets/images/blank.gif\'">' +
            '<button onclick="psCompareRemove(' + p.id + ')" title="Quitar">&times;</button></span>';
    }).join('');

    $bar.find('.ps-cb-items').html(items);
    $bar.find('.ps-cb-count').text(list.length + ' / ' + PS_COMPARE_MAX);
    $bar.find('.ps-cb-compare-btn').prop('disabled', list.length < 2);
    $bar.slideDown(200);
}

$(document).ready(function(){
    // Inject bar if not present
    if (!document.getElementById('ps-compare-bar')) {
        $('body').append(
            '<style>' +
            '#ps-compare-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:2px solid #e8233a;box-shadow:0 -2px 12px rgba(0,0,0,.1);z-index:9990;padding:10px 16px;display:none;}' +
            '.ps-cb-inner{max-width:960px;margin:0 auto;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}' +
            '.ps-cb-label{font-weight:700;font-size:13px;color:#333;white-space:nowrap;}' +
            '.ps-cb-items{display:flex;gap:8px;flex:1;}' +
            '.ps-cb-item{position:relative;width:48px;height:60px;border:1px solid #eee;border-radius:4px;overflow:hidden;}' +
            '.ps-cb-item img{width:100%;height:100%;object-fit:cover;}' +
            '.ps-cb-item button{position:absolute;top:0;right:0;background:#e8233a;color:#fff;border:none;width:16px;height:16px;font-size:10px;line-height:1;cursor:pointer;padding:0;}' +
            '.ps-cb-count{font-size:12px;color:#aaa;white-space:nowrap;}' +
            '.ps-cb-actions{display:flex;gap:8px;}' +
            '.ps-cb-compare-btn{background:#e8233a;color:#fff;border:none;border-radius:5px;padding:7px 16px;font-size:13px;cursor:pointer;font-weight:700;}' +
            '.ps-cb-compare-btn:disabled{opacity:.5;cursor:not-allowed;}' +
            '.ps-cb-clear-btn{background:#f5f5f5;color:#666;border:none;border-radius:5px;padding:7px 12px;font-size:12px;cursor:pointer;}' +
            '@media(max-width:576px){.ps-cb-items{display:none;}}' +
            '</style>' +
            '<div id="ps-compare-bar">' +
            '<div class="ps-cb-inner">' +
            '<span class="ps-cb-label"><i class="fa fa-balance-scale" style="margin-right:6px;color:#e8233a"></i>Comparar</span>' +
            '<div class="ps-cb-items"></div>' +
            '<span class="ps-cb-count"></span>' +
            '<div class="ps-cb-actions">' +
            '<button class="ps-cb-compare-btn" onclick="location.href=\'compare.php\'">Ver comparación</button>' +
            '<button class="ps-cb-clear-btn" onclick="psCompareClear()">Limpiar</button>' +
            '</div></div></div>'
        );
    }
    psCompareUpdateBar();

    // Delegate click on compare buttons added by PHP pages
    $(document).on('click', '.btn-compare', function(e){
        e.preventDefault();
        var $btn = $(this);
        psCompareAdd(
            $btn.data('id'),
            $btn.data('name'),
            $btn.data('img'),
            $btn.data('price')
        );
    });
});
