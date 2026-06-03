<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_products');

// Exportar CSV
if (isset($_GET['export']) && isset($_GET['pid'])) {
    $pid = intval($_GET['pid']);
    $r = mysqli_query($con,
        "SELECT o.id as orden_id, u.name as cliente, u.email,
                o.quantity as cantidad, p.productPrice as precio_unit,
                (o.quantity * p.productPrice) as subtotal,
                o.paymentMethod as metodo_pago, o.orderStatus as estado,
                o.orderDate as fecha,
                COALESCE(a.username,'Cliente web') as creado_por,
                COALESCE(s.name,'—') as proveedor
         FROM orders o
         JOIN users u ON u.id = o.userId
         JOIN products p ON p.id = o.productId
         LEFT JOIN admin a ON a.id = o.created_by
         LEFT JOIN order_items oi ON oi.order_id = o.id
         LEFT JOIN suppliers s ON s.id = oi.supplier_id
         WHERE o.productId = $pid
         ORDER BY o.orderDate DESC");
    $pname = mysqli_fetch_assoc(mysqli_query($con,"SELECT productName FROM products WHERE id=$pid"));
    $fname = 'ventas_' . preg_replace('/[^a-z0-9]/i','_', $pname['productName'] ?? $pid) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output','w');
    fputcsv($out, ['Orden ID','Cliente','Email','Cantidad','Precio unit.','Subtotal','Método pago','Estado','Fecha','Creado por','Proveedor']);
    while ($row = mysqli_fetch_assoc($r)) fputcsv($out, array_values($row));
    fclose($out); exit();
}

$pid = intval($_GET['pid'] ?? 0);

// Lista de productos para el selector
$products_q = mysqli_query($con, "SELECT id, productName FROM products ORDER BY productName");
$products_list = [];
while ($p = mysqli_fetch_assoc($products_q)) $products_list[] = $p;

// Datos del producto seleccionado
$product = null;
$sales   = [];
$stats   = [];
if ($pid > 0) {
    $pq = mysqli_query($con, "SELECT p.*, COALESCE(s.name,'—') as supplier_name
                               FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id
                               WHERE p.id=$pid LIMIT 1");
    $product = $pq ? mysqli_fetch_assoc($pq) : null;

    // Ventas con filtros opcionales
    $f_estado  = mysqli_real_escape_string($con, $_GET['estado'] ?? '');
    $f_from    = $_GET['from'] ?? '';
    $f_to      = $_GET['to']   ?? '';
    $where_extra = "WHERE o.productId = $pid";
    if ($f_estado !== '') $where_extra .= " AND o.orderStatus='$f_estado'";
    if ($f_from !== '')   $where_extra .= " AND DATE(o.orderDate) >= '" . date('Y-m-d', strtotime($f_from)) . "'";
    if ($f_to !== '')     $where_extra .= " AND DATE(o.orderDate) <= '" . date('Y-m-d', strtotime($f_to)) . "'";

    $sales_q = mysqli_query($con,
        "SELECT o.id as orden_id, u.name as cliente, u.email,
                o.quantity, p.productPrice,
                (o.quantity * p.productPrice) as subtotal,
                o.paymentMethod, o.orderStatus, o.orderDate,
                COALESCE(a.username,'Cliente web') as creado_por,
                COALESCE(sup.name,'—') as proveedor
         FROM orders o
         JOIN users u ON u.id = o.userId
         JOIN products p ON p.id = o.productId
         LEFT JOIN admin a ON a.id = o.created_by
         LEFT JOIN order_items oi ON oi.order_id = o.id
         LEFT JOIN suppliers sup ON sup.id = oi.supplier_id
         $where_extra
         ORDER BY o.orderDate DESC");
    while ($r = mysqli_fetch_assoc($sales_q)) $sales[] = $r;

    // Estadísticas
    $stats_q = mysqli_query($con,
        "SELECT COUNT(*) as total_ordenes,
                SUM(o.quantity) as unidades_vendidas,
                SUM(o.quantity * p.productPrice) as ingresos,
                MIN(o.orderDate) as primera_venta,
                MAX(o.orderDate) as ultima_venta
         FROM orders o JOIN products p ON p.id=o.productId
         WHERE o.productId=$pid");
    $stats = mysqli_fetch_assoc($stats_q) ?: [];
}

$status_colors = ['Borrador'=>'#f39c12','Confirmada'=>'#337ab7','En gestión'=>'#8e44ad','Despachada'=>'#27ae60','Entregada'=>'#2c3e50','in Process'=>'#e67e22','Delivered'=>'#27ae60'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Ventas por Referencia</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
        .stat-card { background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:14px 16px; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,.05); }
        .stat-num  { font-size:1.7em; font-weight:700; }
        .stat-lbl  { font-size:.75em; color:#888; margin-top:2px; }
        .filter-bar { background:#f8f9fa; border:1px solid #e0e0e0; border-radius:8px; padding:14px 18px; margin-bottom:16px; }
        .prod-card  { background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:14px 18px; margin-bottom:16px; display:flex; gap:16px; align-items:center; }
        .prod-card img { width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #eee; }
        .badge-status { display:inline-block; padding:2px 8px; border-radius:10px; font-size:.75em; font-weight:700; color:#fff; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content"><div class="module">

<div class="module-head" style="background:linear-gradient(135deg,#2c3e50,#34495e);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em">
        <i class="icon-bar-chart"></i> Ventas por Referencia
        <small style="color:rgba(255,255,255,.6);font-size:.75em;margin-left:8px">¿Quién compró cada producto?</small>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<!-- Selector de producto -->
<form method="get" class="filter-bar">
    <div class="row-fluid">
        <div class="span4">
            <label style="font-size:.82em;color:#555">Seleccionar producto</label>
            <select name="pid" class="input-block-level" onchange="this.form.submit()">
                <option value="">— Elige un producto —</option>
                <?php foreach ($products_list as $pl): ?>
                <option value="<?php echo $pl['id']; ?>" <?php echo ($pl['id']==$pid?'selected':''); ?>>
                    <?php echo htmlspecialchars($pl['productName']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($pid > 0): ?>
        <div class="span2">
            <label style="font-size:.82em;color:#555">Estado</label>
            <select name="estado" class="input-block-level">
                <option value="">Todos</option>
                <?php foreach (['Borrador','Confirmada','En gestión','Despachada','Entregada','in Process','Delivered'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo (($f_estado??'')===$st?'selected':''); ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="span2">
            <label style="font-size:.82em;color:#555">Desde</label>
            <input type="date" name="from" class="input-block-level" value="<?php echo htmlspecialchars($f_from??''); ?>">
        </div>
        <div class="span2">
            <label style="font-size:.82em;color:#555">Hasta</label>
            <input type="date" name="to" class="input-block-level" value="<?php echo htmlspecialchars($f_to??''); ?>">
        </div>
        <div class="span2" style="padding-top:18px">
            <button type="submit" class="btn btn-primary btn-small">Filtrar</button>
            <a href="product-sales.php?pid=<?php echo $pid; ?>" class="btn btn-default btn-small">Limpiar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($product): ?>

<!-- Info del producto -->
<div class="prod-card">
    <img src="../admin/productimages/<?php echo $pid; ?>/<?php echo htmlspecialchars($product['productImage1']??''); ?>" onerror="this.src='images/no-image.jpg'">
    <div style="flex:1">
        <strong style="font-size:1em"><?php echo htmlspecialchars($product['productName']); ?></strong><br>
        <span style="color:#888;font-size:.85em"><?php echo htmlspecialchars($product['productCompany']??''); ?></span>
        <span style="margin-left:12px;color:#337ab7;font-weight:700">$<?php echo number_format($product['productPrice'],0,'.','.');?></span>
    </div>
    <div style="text-align:right">
        <div style="font-size:.82em;color:#555">Stock actual</div>
        <div style="font-size:1.4em;font-weight:700;color:<?php echo ($product['stock_qty']??0)<=5?'#e8233a':'#27ae60'; ?>">
            <?php echo $product['stock_qty'] ?? '—'; ?>
        </div>
        <div style="font-size:.78em;color:#888"><?php echo htmlspecialchars($product['productAvailability']); ?></div>
    </div>
    <div>
        <a href="product-sales.php?pid=<?php echo $pid; ?>&export=1<?php echo $f_estado?"&estado=$f_estado":''; ?><?php echo $f_from?"&from=$f_from":''; ?><?php echo $f_to?"&to=$f_to":''; ?>"
           class="btn btn-success btn-small"><i class="icon-download"></i> Exportar CSV</a>
    </div>
</div>

<!-- Stats -->
<div style="display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap">
    <div class="stat-card" style="flex:1;min-width:100px">
        <div class="stat-num" style="color:#337ab7"><?php echo $stats['total_ordenes']??0; ?></div>
        <div class="stat-lbl">Órdenes</div>
    </div>
    <div class="stat-card" style="flex:1;min-width:100px">
        <div class="stat-num" style="color:#27ae60"><?php echo $stats['unidades_vendidas']??0; ?></div>
        <div class="stat-lbl">Unidades vendidas</div>
    </div>
    <div class="stat-card" style="flex:1;min-width:100px">
        <div class="stat-num" style="color:#8e44ad">$<?php echo number_format($stats['ingresos']??0,0,'.','.');?></div>
        <div class="stat-lbl">Ingresos totales</div>
    </div>
    <div class="stat-card" style="flex:1;min-width:140px">
        <div class="stat-num" style="font-size:1em;color:#555"><?php echo $stats['primera_venta'] ? substr($stats['primera_venta'],0,10) : '—'; ?></div>
        <div class="stat-lbl">Primera venta</div>
    </div>
    <div class="stat-card" style="flex:1;min-width:140px">
        <div class="stat-num" style="font-size:1em;color:#555"><?php echo $stats['ultima_venta'] ? substr($stats['ultima_venta'],0,10) : '—'; ?></div>
        <div class="stat-lbl">Última venta</div>
    </div>
</div>

<!-- Tabla de compradores -->
<?php if (empty($sales)): ?>
<div style="text-align:center;padding:30px;color:#aaa;font-style:italic">No hay ventas con los filtros aplicados.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="table table-bordered table-striped" style="font-size:.85em">
    <thead style="background:#2c3e50;color:#fff">
        <tr>
            <th>Orden</th><th>Cliente</th><th>Email</th><th>Qty</th>
            <th>Subtotal</th><th>Método pago</th><th>Estado</th>
            <th>Proveedor</th><th>Creado por</th><th>Fecha</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($sales as $sale): ?>
    <tr>
        <td><?php echo $sale['orden_id']; ?></td>
        <td><strong><?php echo htmlspecialchars($sale['cliente']); ?></strong></td>
        <td style="color:#888"><?php echo htmlspecialchars($sale['email']); ?></td>
        <td><strong><?php echo $sale['quantity']; ?></strong></td>
        <td style="color:#337ab7;font-weight:700">$<?php echo number_format($sale['subtotal'],0,'.','.');?></td>
        <td><?php echo htmlspecialchars($sale['paymentMethod']); ?></td>
        <td>
            <?php $sc = $status_colors[$sale['orderStatus']] ?? '#aaa'; ?>
            <span class="badge-status" style="background:<?php echo $sc;?>"><?php echo htmlspecialchars($sale['orderStatus']??'—'); ?></span>
        </td>
        <td style="color:#27ae60;font-size:.82em"><?php echo htmlspecialchars($sale['proveedor']); ?></td>
        <td style="color:#337ab7;font-size:.82em"><?php echo htmlspecialchars($sale['creado_por']); ?></td>
        <td style="color:#888;white-space:nowrap"><?php echo substr($sale['orderDate'],0,16); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php elseif ($pid === 0): ?>
<div style="text-align:center;padding:40px;color:#aaa">
    <i class="icon-bar-chart" style="font-size:3em;display:block;margin-bottom:10px"></i>
    Selecciona un producto para ver quién lo ha comprado.
</div>
<?php endif; ?>

</div></div></div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
