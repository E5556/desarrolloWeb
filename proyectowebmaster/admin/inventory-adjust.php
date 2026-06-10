<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_products');

// Agregar columnas faltantes si no existen
mysqli_report(MYSQLI_REPORT_OFF);
mysqli_query($con, "ALTER TABLE stock_movements ADD COLUMN type ENUM('in','out','adjustment') NOT NULL DEFAULT 'adjustment' AFTER product_id");
mysqli_query($con, "ALTER TABLE stock_movements ADD COLUMN qty_after INT DEFAULT NULL AFTER change_qty");
mysqli_query($con, "ALTER TABLE stock_movements ADD COLUMN admin_user VARCHAR(80) DEFAULT '' AFTER qty_after");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$msg = ''; $mtyp = '';

// ── Procesar ajuste / entrada ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_adjust'])) {
    $pids    = array_values($_POST['product_id']  ?? []);
    $qtys    = array_values($_POST['qty_change']  ?? []);
    $reasons = array_values($_POST['reason']      ?? []);
    $types   = array_values($_POST['mov_type']    ?? []);
    $admin_user = mysqli_real_escape_string($con, $_SESSION['alogin'] ?? '');
    $count = 0;

    $skipped_on_order = 0;
    for ($i = 0; $i < count($pids); $i++) {
        $pid_i  = intval($pids[$i]);
        $qty_i  = intval($qtys[$i] ?? 0);
        $type_i = in_array($types[$i] ?? '', ['in','out','adjustment']) ? $types[$i] : 'in';
        $reason = mysqli_real_escape_string($con, substr($reasons[$i] ?? '', 0, 200));
        if ($pid_i <= 0 || $qty_i <= 0) continue;

        // Bloquear productos Bajo Pedido
        $prod_info = mysqli_fetch_assoc(mysqli_query($con, "SELECT COALESCE(stock_qty,0) stock_qty, productAvailability FROM products WHERE id=$pid_i LIMIT 1"));
        if (($prod_info['productAvailability'] ?? '') === 'On Order') { $skipped_on_order++; continue; }
        $stock_before = intval($prod_info['stock_qty'] ?? 0);

        if ($type_i === 'adjustment') {
            // Ajuste manual: reemplaza el stock al valor exacto ingresado
            $delta = $qty_i - $stock_before;
            mysqli_query($con, "UPDATE products SET stock_qty = $qty_i WHERE id=$pid_i");
        } else {
            $delta = ($type_i === 'out') ? -$qty_i : $qty_i;
            mysqli_query($con, "UPDATE products SET stock_qty = GREATEST(0, $stock_before + $delta) WHERE id=$pid_i");
        }

        // Actualizar disponibilidad (nunca tocar On Order)
        $qa = mysqli_fetch_assoc(mysqli_query($con, "SELECT stock_qty FROM products WHERE id=$pid_i LIMIT 1"));
        $qty_after = intval($qa['stock_qty'] ?? 0);
        if ($qty_after > 0) mysqli_query($con, "UPDATE products SET productAvailability='In Stock' WHERE id=$pid_i");
        else mysqli_query($con, "UPDATE products SET productAvailability='Out of Stock' WHERE id=$pid_i");

        $aid = intval($_SESSION['aid'] ?? 0);
        mysqli_query($con, "INSERT INTO stock_movements(product_id,type,change_qty,qty_after,reason,admin_user,admin_id)
            VALUES($pid_i,'$type_i',$delta,$qty_after,'$reason','$admin_user',$aid)");
        $count++;
    }
    $msg  = $count > 0 ? "✅ $count movimiento(s) registrado(s) correctamente." : 'No se procesó ningún movimiento (verifica los datos).';
    if ($skipped_on_order > 0) $msg .= " ⚠️ $skipped_on_order referencia(s) omitida(s) por ser Bajo Pedido (sin control de stock).";
    $mtyp = $count > 0 ? 'success' : 'warning';
}

// ── Búsqueda AJAX de productos ───────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'products') {
    header('Content-Type: application/json');
    $q = '%' . mysqli_real_escape_string($con, $_GET['q'] ?? '') . '%';
    $rs = mysqli_query($con, "SELECT p.id, p.productName, p.productPrice, p.stock_qty, p.productAvailability,
                                      COALESCE(s.name,'—') as supplier_name
                               FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id
                               WHERE p.productName LIKE '$q' ORDER BY p.productName LIMIT 30");
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    echo json_encode($out); exit();
}

// Producto precargado desde edit-products
$preload_product = null;
if (isset($_GET['load_pid']) && intval($_GET['load_pid']) > 0) {
    $lp = intval($_GET['load_pid']);
    $lpr = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT p.id, p.productName, p.productPrice, p.stock_qty, p.productAvailability,
                COALESCE(s.name,'—') as supplier_name
         FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id
         WHERE p.id=$lp LIMIT 1"));
    if ($lpr) $preload_product = $lpr;
}

// Proveedores para referencia
$suppliers_q = mysqli_query($con, "SELECT id, name FROM suppliers WHERE active=1 ORDER BY name");
$sup_arr = [];
while ($s = mysqli_fetch_assoc($suppliers_q)) $sup_arr[] = $s;

// Historial reciente (últimos 20)
$hist_q = mysqli_query($con, "SELECT sm.*, p.productName FROM stock_movements sm
    JOIN products p ON p.id=sm.product_id
    ORDER BY sm.created_at DESC LIMIT 20");
$hist = [];
while ($h = mysqli_fetch_assoc($hist_q)) $hist[] = $h;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Ajuste de Inventario</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
        .ia-card  { background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:18px 22px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,.04); }
        .ia-card h4 { margin:0 0 14px; font-size:.93em; font-weight:700; color:#333; border-bottom:1px solid #f0f0f0; padding-bottom:8px; }
        .item-row { background:#f8f9fa; border:1px solid #e0e0e0; border-radius:6px; padding:12px 14px; margin-bottom:10px; position:relative; }
        .item-row .prod-name { font-weight:700; font-size:.9em; }
        .item-row .prod-stock { font-size:.82em; color:#888; }
        .btn-del-row { position:absolute; top:10px; right:10px; background:#e8233a; color:#fff; border:none; border-radius:4px; padding:2px 8px; font-size:.78em; cursor:pointer; }
        #prod-results { border:1px solid #ddd; border-radius:0 0 6px 6px; max-height:220px; overflow-y:auto; background:#fff; display:none; position:absolute; z-index:100; width:100%; }
        .prod-result { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f5f5f5; font-size:.85em; }
        .prod-result:hover { background:#f0f7ff; }
        .search-wrap { position:relative; }
        .mov-in   { color:#27ae60; font-weight:700; }
        .mov-out  { color:#e8233a; font-weight:700; }
        .mov-adj  { color:#f39c12; font-weight:700; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content"><div class="module">

<div class="module-head" style="background:linear-gradient(135deg,#27ae60,#1e8449);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em">
        <i class="icon-inbox"></i> Ajuste de Inventario
        <small style="color:rgba(255,255,255,.6);font-size:.75em;margin-left:8px">Entradas, salidas y ajustes manuales</small>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<?php if ($msg): ?>
<div class="alert alert-<?php echo $mtyp; ?>" style="border-radius:6px;margin-bottom:14px"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post" id="adjust-form">
<input type="hidden" name="do_adjust" value="1">

<div class="ia-card">
    <h4><i class="icon-search" style="color:#337ab7"></i> Agregar productos al movimiento</h4>
    <div class="search-wrap" style="margin-bottom:12px">
        <input type="text" id="prod-search" class="input-block-level" placeholder="Buscar producto por nombre...">
        <div id="prod-results"></div>
    </div>
    <div id="items-container">
        <p class="muted" id="no-items-msg" style="font-style:italic;font-size:.88em">Busca y agrega productos para registrar movimientos.</p>
    </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:18px">
    <button type="submit" class="btn btn-success btn-large" id="btn-save" disabled>
        <i class="icon-ok"></i> Registrar movimientos
    </button>
    <a href="stock-history.php" class="btn btn-default btn-large">Ver historial completo</a>
    <a href="product-sales.php" class="btn btn-default btn-large">Ver ventas por referencia</a>
</div>
</form>

<!-- Historial reciente -->
<div class="ia-card">
    <h4><i class="icon-time" style="color:#8e44ad"></i> Últimos 20 movimientos</h4>
    <?php if (empty($hist)): ?>
    <p class="muted" style="font-style:italic">Sin movimientos registrados aún.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="table table-bordered table-striped" style="font-size:.83em;margin-bottom:0">
        <thead><tr style="background:#f0f0f0">
            <th>Producto</th><th>Tipo</th><th>Cambio</th><th>Stock resultante</th><th>Razón</th><th>Usuario</th><th>Fecha</th>
        </tr></thead>
        <tbody>
        <?php foreach ($hist as $h): ?>
        <tr>
            <td><?php echo htmlspecialchars($h['productName']); ?></td>
            <td>
                <?php if ($h['type']==='in'): ?><span class="mov-in">↑ Entrada</span>
                <?php elseif ($h['type']==='out'): ?><span class="mov-out">↓ Salida</span>
                <?php else: ?><span class="mov-adj">⟳ Ajuste</span><?php endif; ?>
            </td>
            <td class="<?php echo $h['change_qty']>0?'mov-in':($h['change_qty']<0?'mov-out':'mov-adj'); ?>">
                <?php echo ($h['change_qty']>0?'+':'').$h['change_qty']; ?>
            </td>
            <td><strong><?php echo $h['qty_after']??'—'; ?></strong></td>
            <td style="color:#555"><?php echo htmlspecialchars($h['reason']); ?></td>
            <td style="color:#337ab7"><?php echo htmlspecialchars($h['admin_user']); ?></td>
            <td style="color:#888;white-space:nowrap"><?php echo substr($h['created_at'],0,16); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div></div></div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
var itemCount = 0;

var prodTimer;
$('#prod-search').on('input', function() {
    clearTimeout(prodTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#prod-results').hide(); return; }
    prodTimer = setTimeout(function() {
        $.getJSON('inventory-adjust.php?ajax=products&q=' + encodeURIComponent(q), function(data) {
            var html = '';
            if (!data.length) html = '<div class="prod-result" style="color:#aaa">Sin resultados</div>';
            data.forEach(function(p) {
                html += '<div class="prod-result" data-id="'+p.id+'" data-name="'+escH(p.productName)+'" data-stock="'+(p.stock_qty||0)+'" data-avail="'+escH(p.productAvailability)+'">'
                      + '<strong>'+escH(p.productName)+'</strong> '
                      + '<span style="color:#888">Stock: '+(p.stock_qty||'—')+'</span> '
                      + '<span style="color:#337ab7">'+escH(p.productAvailability)+'</span></div>';
            });
            $('#prod-results').html(html).show();
        });
    }, 300);
});

$(document).on('click', '.prod-result[data-id]', function() {
    addItem($(this).data('id'), $(this).data('name'), $(this).data('stock'), $(this).data('avail'));
    $('#prod-search').val('');
    $('#prod-results').hide();
});

function addItem(pid, name, stock, avail) {
    itemCount++;
    var idx = itemCount;
    $('#no-items-msg').hide();

    var isOnOrder = (avail === 'On Order');
    var row;

    if (isOnOrder) {
        // Producto Bajo Pedido: mostrar aviso, sin controles de ajuste
        row = '<div class="item-row" id="irow-'+idx+'" style="border-color:#f0ad4e;background:#fffbf0">'
            + '<button type="button" class="btn-del-row" onclick="removeItem('+idx+')">✕</button>'
            + '<div class="prod-name">'+escH(name)+'</div>'
            + '<div style="margin-top:8px;padding:8px 12px;background:#fff3cd;border:1px solid #ffc107;border-radius:5px;font-size:.85em;color:#856404">'
            + '<i class="icon-warning-sign"></i> <strong>Bajo Pedido</strong> — Esta referencia no maneja stock físico. Los ajustes de inventario no aplican.'
            + '</div>'
            + '</div>';
    } else {
        $('#btn-save').prop('disabled', false);
        row = '<div class="item-row" id="irow-'+idx+'">'
            + '<button type="button" class="btn-del-row" onclick="removeItem('+idx+')">✕</button>'
            + '<input type="hidden" name="product_id[]" value="'+pid+'">'
            + '<div class="prod-name">'+escH(name)+'</div>'
            + '<div class="prod-stock">Stock actual: <strong>'+stock+'</strong></div>'
            + '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px">'
            +   '<div>'
            +     '<label style="font-size:.8em;color:#555">Tipo de movimiento</label>'
            +     '<select name="mov_type[]" class="input-block-level" style="max-width:180px">'
            +       '<option value="in">↑ Entrada (restock)</option>'
            +       '<option value="out">↓ Salida (ajuste)</option>'
            +       '<option value="adjustment">⟳ Ajuste manual</option>'
            +     '</select>'
            +   '</div>'
            +   '<div>'
            +     '<label style="font-size:.8em;color:#555" class="qty-label">Cantidad</label>'
            +     '<input type="number" name="qty_change[]" value="1" min="0" class="qty-input" style="width:80px">'
            +     '<div class="qty-hint" style="font-size:.75em;color:#888;margin-top:2px">Unidades a sumar</div>'
            +   '</div>'
            +   '<div style="flex:2;min-width:200px">'
            +     '<label style="font-size:.8em;color:#555">Razón / Referencia</label>'
            +     '<input type="text" name="reason[]" class="input-block-level" placeholder="ej: Factura #123, Proveedor Alpha..." style="max-width:300px">'
            +   '</div>'
            + '</div>'
            + '</div>';
    }
    $('#items-container').append(row);
}

function removeItem(idx) {
    $('#irow-'+idx).remove();
    if ($('.item-row').length === 0) { $('#no-items-msg').show(); $('#btn-save').prop('disabled',true); }
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('.search-wrap').length) $('#prod-results').hide();
});

function escH(s) { var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

// Actualizar hint de cantidad según tipo de movimiento
$(document).on('change', 'select[name="mov_type[]"]', function() {
    var row = $(this).closest('.item-row');
    var hint = row.find('.qty-hint');
    var label = row.find('.qty-label');
    var t = $(this).val();
    if (t === 'adjustment') {
        hint.text('Stock TOTAL final (reemplaza el actual)').css('color','#e67e22');
        label.text('Stock final');
    } else if (t === 'out') {
        hint.text('Unidades a restar').css('color','#e8233a');
        label.text('Cantidad');
    } else {
        hint.text('Unidades a sumar').css('color','#27ae60');
        label.text('Cantidad');
    }
});

// Autocargar producto si viene desde edit-products
<?php if ($preload_product): ?>
$(function(){
    addItem(
        <?php echo intval($preload_product['id']); ?>,
        <?php echo json_encode($preload_product['productName']); ?>,
        <?php echo intval($preload_product['stock_qty'] ?? 0); ?>,
        <?php echo json_encode($preload_product['productAvailability'] ?? ''); ?>
    );
});
<?php endif; ?>
</script>
</body>
</html>
