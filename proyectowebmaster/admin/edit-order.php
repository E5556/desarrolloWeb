<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$my_role = $_SESSION['arole'] ?? 'super';
if (!in_array($my_role, ['super','editor','asesor'])) { header('location:index.php'); exit(); }

$group_ref = trim($_GET['ref'] ?? '');
if ($group_ref === '') { header('location:pending-orders.php'); exit(); }

// ── Cargar líneas del pedido (solo Borrador) ─────────────────────────────────
$ref_e = mysqli_real_escape_string($con, $group_ref);
$lines_q = mysqli_query($con, "
    SELECT o.id, o.productId, o.quantity, o.orderStatus, o.userId, o.paymentMethod,
           p.productName, p.productPrice, p.shippingCharge,
           u.name as client_name, u.email as client_email,
           oi.id as item_id, oi.supplier_id,
           s.name as supplier_name
    FROM orders o
    JOIN products p ON p.id = o.productId
    JOIN users u ON u.id = o.userId
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN suppliers s ON s.id = oi.supplier_id
    WHERE o.group_ref = '$ref_e'
    ORDER BY o.id ASC
");

$lines = [];
while ($r = mysqli_fetch_assoc($lines_q)) $lines[] = $r;

if (empty($lines)) { header('location:pending-orders.php'); exit(); }

$order_status = $lines[0]['orderStatus'];
$client_name  = $lines[0]['client_name'];
$client_email = $lines[0]['client_email'];
$pay_method   = $lines[0]['paymentMethod'];
$client_id    = $lines[0]['userId'];
$is_editable  = ($order_status === 'Borrador');

// ── Proveedores ──────────────────────────────────────────────────────────────
$sup_q  = mysqli_query($con, "SELECT id, name FROM suppliers WHERE active=1 ORDER BY name");
$sup_arr = [];
while ($s = mysqli_fetch_assoc($sup_q)) $sup_arr[] = $s;

// ── Buscar productos (AJAX) ──────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'products') {
    header('Content-Type: application/json');
    $q  = '%' . mysqli_real_escape_string($con, $_GET['q'] ?? '') . '%';
    $rs = mysqli_query($con, "SELECT p.id, p.productName, p.productPrice, COALESCE(s.name,'—') as supplier_name, p.supplier_id
                               FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id
                               WHERE p.productName LIKE '$q'
                               ORDER BY p.productName LIMIT 30");
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    echo json_encode($out); exit();
}

$msg = ''; $mtyp = '';

// ── Eliminar línea ───────────────────────────────────────────────────────────
if ($is_editable && isset($_GET['delete_line'])) {
    $del_id = intval($_GET['delete_line']);
    // Verificar que la línea pertenece a este group_ref
    $chk = mysqli_query($con, "SELECT o.id FROM orders o WHERE o.id=$del_id AND o.group_ref='$ref_e'");
    if (mysqli_num_rows($chk) > 0) {
        mysqli_query($con, "DELETE FROM order_items WHERE order_id=$del_id");
        mysqli_query($con, "DELETE FROM orders WHERE id=$del_id AND group_ref='$ref_e'");
        header("location:edit-order.php?ref=$group_ref&msg=deleted"); exit();
    }
}

if (isset($_GET['msg'])) {
    $msgs = ['deleted'=>['Artículo eliminado del pedido.','success'], 'saved'=>['Cambios guardados.','success']];
    [$msg,$mtyp] = $msgs[$_GET['msg']] ?? ['',''];
}

// ── Guardar cambios de líneas (qty + proveedor) ──────────────────────────────
if ($is_editable && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_lines'])) {
    $order_ids = $_POST['order_id']   ?? [];
    $qtys      = $_POST['quantity']   ?? [];
    $sups      = $_POST['supplier_id']?? [];

    foreach ($order_ids as $i => $oid_i) {
        $oid_i = intval($oid_i);
        $qty_i = max(1, intval($qtys[$i] ?? 1));
        $sup_i = intval($sups[$i] ?? 0) ?: null;
        mysqli_query($con, "UPDATE orders SET quantity=$qty_i WHERE id=$oid_i AND group_ref='$ref_e'");
        // Actualizar o insertar order_items
        $ex = mysqli_query($con, "SELECT id FROM order_items WHERE order_id=$oid_i");
        if (mysqli_num_rows($ex) > 0) {
            $sup_sql = $sup_i ? $sup_i : 'NULL';
            mysqli_query($con, "UPDATE order_items SET quantity=$qty_i, supplier_id=$sup_sql WHERE order_id=$oid_i");
        } else {
            $pr = mysqli_query($con, "SELECT productPrice FROM products WHERE id=(SELECT productId FROM orders WHERE id=$oid_i)");
            $price = $pr && ($rp=mysqli_fetch_assoc($pr)) ? floatval($rp['productPrice']) : 0;
            $sup_sql = $sup_i ? $sup_i : 'NULL';
            mysqli_query($con, "INSERT INTO order_items(order_id,product_id,quantity,supplier_id,unit_price)
                SELECT $oid_i, productId, $qty_i, $sup_sql, $price FROM orders WHERE id=$oid_i");
        }
    }
    header("location:edit-order.php?ref=$group_ref&msg=saved"); exit();
}

// ── Agregar artículo ─────────────────────────────────────────────────────────
if ($is_editable && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_item'])) {
    $new_pid = intval($_POST['new_product_id'] ?? 0);
    $new_qty = max(1, intval($_POST['new_qty'] ?? 1));
    $new_sup = intval($_POST['new_supplier_id'] ?? 0) ?: null;

    if ($new_pid > 0) {
        $pr = mysqli_query($con, "SELECT productPrice FROM products WHERE id=$new_pid");
        $price = $pr && ($rp=mysqli_fetch_assoc($pr)) ? floatval($rp['productPrice']) : 0;
        $sup_sql = $new_sup ?? 'NULL';
        $admin_id = intval($_SESSION['aid'] ?? 0);

        $stmt = mysqli_prepare($con, "INSERT INTO orders(userId,productId,quantity,paymentMethod,orderStatus,created_by,group_ref) VALUES(?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'iisissi', $client_id, $new_pid, $new_qty, $pay_method, $order_status, $admin_id, $group_ref);
        mysqli_stmt_execute($stmt);
        $new_oid = mysqli_insert_id($con);
        mysqli_stmt_close($stmt);

        $stmt2 = mysqli_prepare($con, "INSERT INTO order_items(order_id,product_id,quantity,supplier_id,unit_price) VALUES(?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt2, 'iiiid', $new_oid, $new_pid, $new_qty, $new_sup, $price);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
    header("location:edit-order.php?ref=$group_ref&msg=saved"); exit();
}

// ── Confirmar pedido ─────────────────────────────────────────────────────────
if ($is_editable && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirm_order'])) {
    mysqli_query($con, "UPDATE orders SET orderStatus='Confirmada' WHERE group_ref='$ref_e'");

    // Descontar stock por cada línea del pedido
    $lines_stock = mysqli_query($con, "SELECT o.productId, o.quantity FROM orders o WHERE o.group_ref='$ref_e'");
    $admin_user  = mysqli_real_escape_string($con, $_SESSION['alogin'] ?? 'asesor');
    while ($ls = mysqli_fetch_assoc($lines_stock)) {
        $spid = intval($ls['productId']);
        $sqty = intval($ls['quantity']);
        mysqli_query($con, "UPDATE products SET stock_qty = GREATEST(0, COALESCE(stock_qty,0) - $sqty) WHERE id=$spid AND stock_qty IS NOT NULL");
        mysqli_query($con, "UPDATE products SET productAvailability='Out of Stock' WHERE id=$spid AND stock_qty IS NOT NULL AND stock_qty=0");
        $qa = mysqli_fetch_assoc(mysqli_query($con, "SELECT stock_qty FROM products WHERE id=$spid LIMIT 1"));
        $qty_after = intval($qa['stock_qty'] ?? 0);
        mysqli_query($con, "INSERT INTO stock_movements(product_id,type,qty_change,qty_after,reason,admin_user)
            VALUES($spid,'out',-$sqty,$qty_after,'Venta confirmada por asesor — grupo $ref_e','$admin_user')");
    }
    header("location:edit-order.php?ref=$group_ref"); exit();
}

// Recargar líneas tras POST
$lines_q2 = mysqli_query($con, "
    SELECT o.id, o.productId, o.quantity, o.orderStatus, o.paymentMethod,
           p.productName, p.productPrice, p.shippingCharge,
           oi.id as item_id, oi.supplier_id
    FROM orders o
    JOIN products p ON p.id = o.productId
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.group_ref = '$ref_e'
    ORDER BY o.id ASC
");
$lines = [];
while ($r = mysqli_fetch_assoc($lines_q2)) $lines[] = $r;
$order_status = $lines[0]['orderStatus'] ?? $order_status;
$is_editable  = ($order_status === 'Borrador');

$grand_total = array_sum(array_map(fn($l) => ($l['productPrice'] + $l['shippingCharge']) * $l['quantity'], $lines));

// Mapa colores de estado
$status_colors = [
    'Borrador'    => ['#f39c12','#fff8e6','⏳'],
    'Confirmada'  => ['#337ab7','#e8f0fe','✅'],
    'En gestión'  => ['#8e44ad','#f5eef8','🔄'],
    'Despachada'  => ['#27ae60','#e8f8f0','🚚'],
    'Entregada'   => ['#2c3e50','#f0f0f0','📦'],
];
[$sc, $sbg, $sicon] = $status_colors[$order_status] ?? ['#aaa','#f9f9f9','❓'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Editar Pedido</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
        .eo-card { background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:18px 22px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,.04); }
        .eo-card h4 { margin:0 0 12px; font-size:.93em; font-weight:700; color:#333; border-bottom:1px solid #f0f0f0; padding-bottom:8px; }
        .line-row { background:#f8f9fa; border:1px solid #e8e8e8; border-radius:6px; padding:12px 14px; margin-bottom:10px; }
        .line-row .prod-name { font-weight:700; font-size:.9em; color:#222; }
        .line-row .prod-price { color:#337ab7; font-size:.85em; font-weight:600; }
        .status-badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:.82em; font-weight:700; letter-spacing:.3px; }
        .btn-del { background:#e8233a; color:#fff; border:none; border-radius:4px; padding:3px 9px; font-size:.78em; cursor:pointer; }
        .btn-del:hover { background:#c0001e; }
        .total-bar { background:#f0f7ff; border:1px solid #bcd4f0; border-radius:6px; padding:12px 16px; font-size:1em; }
        .total-bar .tnum { font-size:1.4em; font-weight:700; color:#337ab7; }
        #prod-results { border:1px solid #ddd; border-radius:0 0 6px 6px; max-height:220px; overflow-y:auto; background:#fff; display:none; position:absolute; z-index:100; width:100%; }
        .prod-result { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f5f5f5; font-size:.85em; }
        .prod-result:hover { background:#f0f7ff; }
        .add-wrap { position:relative; }
        .locked-msg { background:#fff8e6; border:1px solid #f5c842; border-radius:6px; padding:10px 14px; font-size:.88em; color:#856404; margin-bottom:14px; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content"><div class="module">

<div class="module-head" style="background:linear-gradient(135deg,<?php echo $sc; ?>,<?php echo $sc; ?>cc);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em">
        <i class="icon-shopping-cart"></i> Pedido — Grupo <code style="background:rgba(255,255,255,.2);padding:1px 6px;border-radius:4px;font-size:.85em"><?php echo htmlspecialchars($group_ref); ?></code>
        <span class="status-badge" style="background:rgba(255,255,255,.25);color:#fff;margin-left:10px"><?php echo $sicon.' '.$order_status; ?></span>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<?php if ($msg): ?>
<div class="alert alert-<?php echo $mtyp; ?>" style="border-radius:6px;margin-bottom:14px"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<!-- Info cliente -->
<div class="eo-card">
    <h4><i class="icon-user" style="color:#337ab7"></i> Cliente</h4>
    <strong><?php echo htmlspecialchars($client_name); ?></strong>
    <span style="color:#888;margin-left:8px"><?php echo htmlspecialchars($client_email); ?></span>
    <span style="margin-left:14px;font-size:.85em;color:#555"><i class="icon-credit-card"></i> <?php echo htmlspecialchars($pay_method); ?></span>
</div>

<?php if (!$is_editable): ?>
<div class="locked-msg">
    <i class="icon-lock"></i> Este pedido está en estado <strong><?php echo htmlspecialchars($order_status); ?></strong> — ya no es editable por el asesor.
    <?php if (in_array($my_role, ['super','editor'])): ?>
    Puedes cambiar el estado desde <a href="updateorder.php?oid=<?php echo $lines[0]['id']; ?>">Actualizar orden</a>.
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Líneas del pedido -->
<div class="eo-card">
    <h4><i class="icon-list" style="color:#27ae60"></i> Artículos del pedido</h4>

    <?php if ($is_editable): ?>
    <form method="post" id="lines-form">
    <input type="hidden" name="save_lines" value="1">
    <?php endif; ?>

    <?php if (empty($lines)): ?>
    <p class="muted" style="font-style:italic">No hay artículos en este pedido.</p>
    <?php endif; ?>

    <?php foreach ($lines as $line): ?>
    <div class="line-row">
        <?php if ($is_editable): ?>
        <a href="edit-order.php?ref=<?php echo urlencode($group_ref); ?>&delete_line=<?php echo $line['id']; ?>"
           class="btn-del" style="float:right"
           onclick="return confirm('¿Quitar este artículo del pedido?')">✕ Quitar</a>
        <input type="hidden" name="order_id[]" value="<?php echo $line['id']; ?>">
        <?php endif; ?>

        <div class="prod-name"><?php echo htmlspecialchars($line['productName']); ?></div>
        <div class="prod-price">$<?php echo number_format($line['productPrice'], 0, '.', '.'); ?></div>

        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:8px">
            <div>
                <label style="font-size:.78em;color:#888">Cantidad</label><br>
                <?php if ($is_editable): ?>
                <input type="number" name="quantity[]" value="<?php echo $line['quantity']; ?>" min="1" style="width:70px" class="line-qty" data-price="<?php echo $line['productPrice'] + $line['shippingCharge']; ?>">
                <?php else: ?>
                <strong><?php echo $line['quantity']; ?></strong>
                <?php endif; ?>
            </div>
            <div style="flex:2;min-width:180px">
                <label style="font-size:.78em;color:#888">Proveedor</label><br>
                <?php if ($is_editable): ?>
                <select name="supplier_id[]" class="input-block-level" style="max-width:260px">
                    <option value="">— Sin proveedor —</option>
                    <?php foreach ($sup_arr as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($s['id']==$line['supplier_id']?'selected':''); ?>><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <span style="color:#27ae60"><?php echo $line['supplier_id'] ? htmlspecialchars(array_column($sup_arr,'name','id')[$line['supplier_id']] ?? '—') : '—'; ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label style="font-size:.78em;color:#888">Subtotal</label><br>
                <strong style="color:#337ab7">$<?php echo number_format(($line['productPrice']+$line['shippingCharge'])*$line['quantity'],0,'.','.');?></strong>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($is_editable): ?>
    <div style="margin-top:10px">
        <button type="submit" class="btn btn-primary btn-small"><i class="icon-save"></i> Guardar cambios</button>
    </div>
    </form>
    <?php endif; ?>

    <!-- Total -->
    <div class="total-bar" style="margin-top:14px">
        Total estimado: <span class="tnum" id="grand-total">$<?php echo number_format($grand_total,0,'.','.');?></span>
    </div>
</div>

<?php if ($is_editable): ?>
<!-- Agregar artículo -->
<div class="eo-card">
    <h4><i class="icon-plus" style="color:#27ae60"></i> Agregar artículo</h4>
    <form method="post">
    <input type="hidden" name="add_item" value="1">
    <input type="hidden" name="new_product_id" id="new_product_id" value="">
    <div class="add-wrap" style="margin-bottom:10px">
        <input type="text" id="prod-search" class="input-block-level" placeholder="Buscar producto (stock y fuera de stock)...">
        <div id="prod-results"></div>
        <div id="prod-selected" style="display:none;margin-top:6px;background:#e8f8f0;border:1px solid #a9dfbf;border-radius:5px;padding:7px 10px;font-size:.85em">
            <strong id="prod-selected-name"></strong> — <span id="prod-selected-price" style="color:#337ab7"></span>
            <a href="#" id="prod-clear" style="margin-left:10px;color:#e8233a;font-size:.85em">Cambiar</a>
        </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div>
            <label style="font-size:.82em;color:#555">Cantidad</label>
            <input type="number" name="new_qty" value="1" min="1" style="width:70px">
        </div>
        <div style="flex:2;min-width:180px">
            <label style="font-size:.82em;color:#555">Proveedor</label>
            <select name="new_supplier_id" class="input-block-level" style="max-width:260px">
                <option value="">— Sin proveedor —</option>
                <?php foreach ($sup_arr as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-success btn-small"><i class="icon-plus"></i> Agregar</button>
        </div>
    </div>
    </form>
</div>

<!-- Confirmar pedido -->
<div class="eo-card" style="border-color:#27ae60;background:#f0fdf4">
    <h4 style="color:#27ae60"><i class="icon-ok-circle"></i> Confirmar pedido</h4>
    <p style="font-size:.88em;color:#555;margin-bottom:12px">
        Una vez confirmado, el pedido pasa a estado <strong>Confirmada</strong> y no podrá ser modificado por el asesor. El equipo administrativo tomará el proceso desde ahí.
    </p>
    <form method="post" onsubmit="return confirm('¿Confirmar este pedido? Ya no podrás editarlo.')">
        <input type="hidden" name="confirm_order" value="1">
        <button type="submit" class="btn btn-success"><i class="icon-ok"></i> Confirmar pedido</button>
        <a href="pending-orders.php" class="btn btn-default" style="margin-left:8px">Dejar como borrador</a>
    </form>
</div>
<?php else: ?>
<div style="margin-top:8px">
    <a href="pending-orders.php" class="btn btn-default"><i class="icon-arrow-left"></i> Volver a pedidos</a>
    <?php if (in_array($my_role,['super','editor'])): ?>
    <a href="updateorder.php?oid=<?php echo $lines[0]['id']; ?>" class="btn btn-primary" style="margin-left:8px"><i class="icon-edit"></i> Cambiar estado</a>
    <?php endif; ?>
</div>
<?php endif; ?>

</div></div></div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
// Buscador de productos para agregar
var prodTimer;
$('#prod-search').on('input', function() {
    clearTimeout(prodTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#prod-results').hide(); return; }
    prodTimer = setTimeout(function() {
        $.getJSON('edit-order.php?ref=<?php echo urlencode($group_ref); ?>&ajax=products&q=' + encodeURIComponent(q), function(data) {
            var html = '';
            if (!data.length) html = '<div class="prod-result" style="color:#aaa">Sin resultados</div>';
            data.forEach(function(p) {
                html += '<div class="prod-result" data-id="'+p.id+'" data-name="'+escH(p.productName)+'" data-price="'+p.productPrice+'">'
                      + '<strong>'+escH(p.productName)+'</strong> <span style="color:#337ab7">$'+numFmt(p.productPrice)+'</span></div>';
            });
            $('#prod-results').html(html).show();
        });
    }, 300);
});

$(document).on('click', '.prod-result[data-id]', function() {
    $('#new_product_id').val($(this).data('id'));
    $('#prod-selected-name').text($(this).data('name'));
    $('#prod-selected-price').text('$' + numFmt($(this).data('price')));
    $('#prod-selected').show();
    $('#prod-search').hide();
    $('#prod-results').hide();
});

$('#prod-clear').on('click', function(e) {
    e.preventDefault();
    $('#new_product_id').val('');
    $('#prod-selected').hide();
    $('#prod-search').val('').show().focus();
});

// Recalcular total al cambiar cantidades
$(document).on('change', '.line-qty', function() {
    var total = 0;
    $('.line-qty').each(function() {
        total += parseFloat($(this).data('price')) * parseInt($(this).val() || 1);
    });
    $('#grand-total').text('$' + numFmt(total));
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('.add-wrap').length) $('#prod-results').hide();
});

function numFmt(n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
function escH(s) { var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
</body>
</html>
