<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Solo super, editor y asesor pueden crear pedidos
$my_role = $_SESSION['arole'] ?? 'super';
if (!in_array($my_role, ['super','editor','asesor'])) {
    header('location:index.php'); exit();
}

// ── Crear tablas/columnas si no existen ──────────────────────────────────────
mysqli_query($con, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL");
mysqli_query($con, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS group_ref VARCHAR(36) DEFAULT NULL");
mysqli_query($con, "CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    supplier_id INT DEFAULT NULL,
    unit_price  DECIMAL(10,2) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Buscar clientes (AJAX) ───────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clients') {
    header('Content-Type: application/json');
    $q  = '%' . mysqli_real_escape_string($con, $_GET['q'] ?? '') . '%';
    $rs = mysqli_query($con, "SELECT id, name, email FROM users WHERE name LIKE '$q' OR email LIKE '$q' ORDER BY name LIMIT 20");
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    echo json_encode($out); exit();
}

// ── Buscar productos (AJAX) ──────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'products') {
    header('Content-Type: application/json');
    $q  = '%' . mysqli_real_escape_string($con, $_GET['q'] ?? '') . '%';
    $rs = mysqli_query($con, "SELECT p.id, p.productName, p.productPrice, COALESCE(s.name,'—') as supplier_name, p.supplier_id
                               FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id
                               WHERE p.productName LIKE '$q' AND p.productAvailability='In Stock'
                               ORDER BY p.productName LIMIT 30");
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    echo json_encode($out); exit();
}

// ── Guardar pedido ───────────────────────────────────────────────────────────
$msg  = '';
$mtyp = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    $client_id  = intval($_POST['client_id']      ?? 0);
    $paymethod  = mysqli_real_escape_string($con, $_POST['payment_method'] ?? 'COD');
    $created_by = intval($_SESSION['aid']          ?? 0);
    $group_ref  = bin2hex(random_bytes(8));

    $pids = $_POST['product_id']  ?? [];
    $qtys = $_POST['quantity']    ?? [];
    $sups = $_POST['supplier_id'] ?? [];

    if ($client_id <= 0 || empty($pids)) {
        $msg = 'Selecciona un cliente y al menos un producto.'; $mtyp = 'danger';
    } else {
        $first_order_id = null;
        $stmt = mysqli_prepare($con, "INSERT INTO orders(userId,productId,quantity,paymentMethod,orderStatus,created_by,group_ref) VALUES(?,?,?,'$paymethod','Pending',?,?)");
        foreach ($pids as $i => $pid) {
            $pid_i = intval($pid);
            $qty_i = max(1, intval($qtys[$i] ?? 1));
            $sup_i = intval($sups[$i] ?? 0) ?: null;
            if ($pid_i <= 0) continue;
            mysqli_stmt_bind_param($stmt, 'isisi', $client_id, $pid_i, $qty_i, $created_by, $group_ref);
            mysqli_stmt_execute($stmt);
            $oid = mysqli_insert_id($con);
            if (!$first_order_id) $first_order_id = $oid;
            // Precio unitario
            $pr = mysqli_query($con, "SELECT productPrice FROM products WHERE id=$pid_i");
            $price = $pr && ($rp=mysqli_fetch_assoc($pr)) ? floatval($rp['productPrice']) : 0;
            $stmt2 = mysqli_prepare($con, "INSERT INTO order_items(order_id,product_id,quantity,supplier_id,unit_price) VALUES(?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt2, 'iiiid', $oid, $pid_i, $qty_i, $sup_i, $price);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }
        mysqli_stmt_close($stmt);
        $msg  = '✅ Pedido creado correctamente. <a href="orders.php" style="color:#155724">Ver pedidos</a>';
        $mtyp = 'success';
    }
}

// ── Datos para formulario ────────────────────────────────────────────────────
$suppliers = mysqli_query($con, "SELECT id, name FROM suppliers ORDER BY name");
$sup_arr   = [];
while ($s = mysqli_fetch_assoc($suppliers)) $sup_arr[] = $s;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Crear Pedido</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
        .co-card { background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:20px 24px; margin-bottom:18px; box-shadow:0 2px 6px rgba(0,0,0,.04); }
        .co-card h4 { margin:0 0 14px; font-size:.95em; font-weight:700; color:#333; border-bottom:1px solid #f0f0f0; padding-bottom:8px; }
        .client-result { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:.88em; }
        .client-result:hover { background:#f0f7ff; }
        .client-selected { background:#e8f8f0; border:1px solid #a9dfbf; border-radius:6px; padding:8px 12px; display:none; font-size:.88em; }
        #search-results { border:1px solid #ddd; border-radius:0 0 6px 6px; max-height:220px; overflow-y:auto; background:#fff; display:none; position:absolute; z-index:100; width:100%; }
        .search-wrap { position:relative; }
        .product-row { background:#f8f9fa; border:1px solid #e0e0e0; border-radius:6px; padding:12px; margin-bottom:10px; }
        .product-row .prod-name { font-weight:600; font-size:.88em; color:#333; }
        .product-row .prod-price { color:#337ab7; font-size:.85em; font-weight:700; }
        .btn-remove-row { background:#e8233a; color:#fff; border:none; border-radius:4px; padding:3px 9px; font-size:.8em; cursor:pointer; float:right; }
        #prod-search-wrap { position:relative; }
        #prod-results { border:1px solid #ddd; border-radius:0 0 6px 6px; max-height:240px; overflow-y:auto; background:#fff; display:none; position:absolute; z-index:100; width:100%; }
        .prod-result { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f5f5f5; font-size:.85em; }
        .prod-result:hover { background:#f0f7ff; }
        .total-box { background:#f0f7ff; border:1px solid #bcd4f0; border-radius:6px; padding:14px; font-size:.95em; }
        .total-box .total-num { font-size:1.4em; font-weight:700; color:#337ab7; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper">
<div class="container">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content">
<div class="module">
<div class="module-head" style="background:linear-gradient(135deg,#27ae60,#1e8449);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em">
        <i class="icon-shopping-cart" style="margin-right:8px"></i> Crear Pedido — Asesor
        <small style="color:rgba(255,255,255,.7);font-size:.75em;margin-left:10px">Genera un pedido a nombre de un cliente</small>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<?php if ($msg): ?>
<div class="alert alert-<?php echo $mtyp; ?>" style="border-radius:6px;margin-bottom:16px"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post" id="order-form">
<input type="hidden" name="save_order" value="1">

<!-- 1. Seleccionar cliente -->
<div class="co-card">
    <h4><i class="icon-user" style="color:#27ae60"></i> 1. Seleccionar cliente</h4>
    <input type="hidden" name="client_id" id="client_id" value="">
    <div class="search-wrap">
        <input type="text" id="client-search" class="input-block-level" placeholder="Buscar por nombre o email del cliente...">
        <div id="search-results"></div>
    </div>
    <div class="client-selected" id="client-selected">
        <i class="icon-ok" style="color:#27ae60"></i> <strong id="client-name-show"></strong> — <span id="client-email-show" style="color:#888"></span>
        <a href="#" id="change-client" style="margin-left:12px;font-size:.85em;color:#e8233a">Cambiar</a>
    </div>
</div>

<!-- 2. Artículos del pedido -->
<div class="co-card">
    <h4><i class="icon-list" style="color:#337ab7"></i> 2. Artículos del pedido</h4>

    <div id="prod-search-wrap" style="margin-bottom:12px">
        <input type="text" id="prod-search" class="input-block-level" placeholder="Buscar producto por nombre...">
        <div id="prod-results"></div>
    </div>

    <div id="items-container">
        <p class="muted" id="no-items-msg" style="font-style:italic;font-size:.88em">Aún no has agregado artículos. Usa el buscador de arriba.</p>
    </div>

    <div class="total-box" style="margin-top:14px">
        Total estimado: <span class="total-num" id="grand-total">$0</span>
    </div>
</div>

<!-- 3. Método de pago -->
<div class="co-card">
    <h4><i class="icon-credit-card" style="color:#f39c12"></i> 3. Método de pago</h4>
    <select name="payment_method" class="input-block-level" style="max-width:280px">
        <option value="COD">Pago contra entrega</option>
        <option value="BankTransfer">Transferencia bancaria</option>
        <option value="MercadoPago">MercadoPago</option>
        <option value="Efectivo">Efectivo en tienda</option>
    </select>
</div>

<!-- Guardar -->
<div style="display:flex;gap:10px;margin-top:4px">
    <button type="submit" class="btn btn-success btn-large"><i class="icon-ok"></i> Confirmar y crear pedido</button>
    <a href="orders.php" class="btn btn-default btn-large">Cancelar</a>
</div>

</form>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
var SUPPLIERS = <?php echo json_encode($sup_arr); ?>;
var itemCount = 0;

// ── Buscar clientes ──────────────────────────────────────────────────────────
var clientTimer;
$('#client-search').on('input', function() {
    clearTimeout(clientTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#search-results').hide(); return; }
    clientTimer = setTimeout(function() {
        $.getJSON('create-order.php?ajax=clients&q=' + encodeURIComponent(q), function(data) {
            var html = '';
            if (data.length === 0) html = '<div class="client-result" style="color:#aaa">Sin resultados</div>';
            data.forEach(function(c) {
                html += '<div class="client-result" data-id="'+c.id+'" data-name="'+escHtml(c.name)+'" data-email="'+escHtml(c.email)+'">'
                      + '<strong>'+escHtml(c.name)+'</strong> <span style="color:#888">'+escHtml(c.email)+'</span></div>';
            });
            $('#search-results').html(html).show();
        });
    }, 300);
});

$(document).on('click', '.client-result', function() {
    var id    = $(this).data('id');
    var name  = $(this).data('name');
    var email = $(this).data('email');
    $('#client_id').val(id);
    $('#client-name-show').text(name);
    $('#client-email-show').text(email);
    $('#client-selected').show();
    $('#client-search').hide();
    $('#search-results').hide();
});

$('#change-client').on('click', function(e) {
    e.preventDefault();
    $('#client_id').val('');
    $('#client-selected').hide();
    $('#client-search').val('').show().focus();
});

// ── Buscar productos ─────────────────────────────────────────────────────────
var prodTimer;
$('#prod-search').on('input', function() {
    clearTimeout(prodTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#prod-results').hide(); return; }
    prodTimer = setTimeout(function() {
        $.getJSON('create-order.php?ajax=products&q=' + encodeURIComponent(q), function(data) {
            var html = '';
            if (data.length === 0) html = '<div class="prod-result" style="color:#aaa">Sin resultados</div>';
            data.forEach(function(p) {
                html += '<div class="prod-result" data-id="'+p.id+'" data-name="'+escHtml(p.productName)+'" data-price="'+p.productPrice+'" data-sup="'+p.supplier_id+'">'
                      + '<strong>'+escHtml(p.productName)+'</strong> '
                      + '<span style="color:#337ab7;font-weight:700">$'+numberFmt(p.productPrice)+'</span> '
                      + '<span style="color:#888;font-size:.85em">Prov: '+escHtml(p.supplier_name)+'</span></div>';
            });
            $('#prod-results').html(html).show();
        });
    }, 300);
});

$(document).on('click', '.prod-result[data-id]', function() {
    addItem($(this).data('id'), $(this).data('name'), parseFloat($(this).data('price')), $(this).data('sup'));
    $('#prod-search').val('');
    $('#prod-results').hide();
});

function addItem(pid, name, price, defaultSup) {
    itemCount++;
    var idx = itemCount;
    $('#no-items-msg').hide();

    var supOptions = '<option value="">— Sin proveedor —</option>';
    SUPPLIERS.forEach(function(s) {
        var sel = (parseInt(s.id) === parseInt(defaultSup)) ? ' selected' : '';
        supOptions += '<option value="'+s.id+'"'+sel+'>'+escHtml(s.name)+'</option>';
    });

    var row = '<div class="product-row" id="row-'+idx+'">'
        + '<input type="hidden" name="product_id[]" value="'+pid+'">'
        + '<button type="button" class="btn-remove-row" onclick="removeItem('+idx+', '+price+')">✕ Quitar</button>'
        + '<div class="prod-name">'+escHtml(name)+'</div>'
        + '<div class="prod-price">$'+numberFmt(price)+'</div>'
        + '<div style="margin-top:8px;display:flex;gap:12px;flex-wrap:wrap">'
        +   '<div style="flex:1;min-width:100px">'
        +     '<label style="font-size:.8em;color:#888">Cantidad</label>'
        +     '<input type="number" name="quantity[]" value="1" min="1" class="item-qty" data-price="'+price+'" style="width:80px" onchange="recalcTotal()">'
        +   '</div>'
        +   '<div style="flex:2;min-width:160px">'
        +     '<label style="font-size:.8em;color:#888">Proveedor para este artículo</label>'
        +     '<select name="supplier_id[]" class="input-block-level">'+supOptions+'</select>'
        +   '</div>'
        + '</div>'
        + '</div>';
    $('#items-container').append(row);
    recalcTotal();
}

function removeItem(idx, price) {
    $('#row-'+idx).remove();
    if ($('.product-row').length === 0) $('#no-items-msg').show();
    recalcTotal();
}

function recalcTotal() {
    var total = 0;
    $('.item-qty').each(function() {
        total += parseFloat($(this).data('price')) * parseInt($(this).val() || 1);
    });
    $('#grand-total').text('$' + numberFmt(total));
}

function numberFmt(n) {
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function escHtml(str) {
    var d = document.createElement('div'); d.textContent = str; return d.innerHTML;
}

// Cerrar dropdowns al hacer clic fuera
$(document).on('click', function(e) {
    if (!$(e.target).closest('#prod-search-wrap').length) $('#prod-results').hide();
    if (!$(e.target).closest('.search-wrap').length) $('#search-results').hide();
});
</script>
</body>
</html>
