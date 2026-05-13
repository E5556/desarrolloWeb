<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_stats');


// ── PERIOD FILTER ──────────────────────────────────────────────────────────
$period = intval($_GET['period'] ?? 30);
if (!in_array($period, [7, 30, 90, 365])) $period = 30;
$from = date('Y-m-d H:i:s', strtotime("-{$period} days"));

// ── DOWNLOAD CSV ───────────────────────────────────────────────────────────
if (isset($_GET['download'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_estadisticas_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

    fputcsv($out, ['REPORTE DE ESTADÍSTICAS - '.date('d/m/Y').' - Últimos '.$period.' días'], ';');
    fputcsv($out, [], ';');

    // Productos más vistos
    fputcsv($out, ['TOP PRODUCTOS MÁS VISTOS'], ';');
    fputcsv($out, ['#', 'Producto', 'Categoría', 'Vistas'], ';');
    $r = mysqli_query($con, "SELECT p.productName, c.categoryName, COUNT(pv.id) AS total
        FROM product_views pv JOIN products p ON pv.product_id=p.id
        LEFT JOIN category c ON p.category=c.id
        WHERE pv.viewed_at >= '$from' GROUP BY pv.product_id ORDER BY total DESC LIMIT 20");
    $i=1; while($row=mysqli_fetch_assoc($r)) fputcsv($out,[$i++,$row['productName'],$row['catName'],$row['total']],';');

    fputcsv($out, [], ';');
    fputcsv($out, ['TOP PRODUCTOS MÁS AGREGADOS AL CARRITO'], ';');
    fputcsv($out, ['#', 'Producto', 'Categoría', 'Veces agregado'], ';');
    $r = mysqli_query($con, "SELECT p.productName, c.categoryName, COUNT(ce.id) AS total
        FROM cart_events ce JOIN products p ON ce.product_id=p.id
        LEFT JOIN category c ON p.category=c.id
        WHERE ce.added_at >= '$from' GROUP BY ce.product_id ORDER BY total DESC LIMIT 20");
    $i=1; while($row=mysqli_fetch_assoc($r)) fputcsv($out,[$i++,$row['productName'],$row['catName'],$row['total']],';');

    fputcsv($out, [], ';');
    fputcsv($out, ['TOP PRODUCTOS MÁS VENDIDOS'], ';');
    fputcsv($out, ['#', 'Producto', 'Unidades vendidas', 'Ingresos'], ';');
    $r = mysqli_query($con, "SELECT p.productName, SUM(o.quantity) AS units, SUM(o.quantity * p.productPrice) AS revenue
        FROM orders o JOIN products p ON o.productId=p.id
        WHERE o.orderDate >= '$from' GROUP BY o.productId ORDER BY units DESC LIMIT 20");
    $i=1; while($row=mysqli_fetch_assoc($r)) fputcsv($out,[$i++,$row['productName'],$row['units'],'$'.number_format($row['revenue'],0,'.',',')],';');

    fputcsv($out, [], ';');
    fputcsv($out, ['MÉTRICAS GENERALES'], ';');

    $total_orders = (($__r = mysqli_query($con,"SELECT COUNT(*) AS n FROM orders WHERE orderDate>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n'];
    $total_sessions = (($__r = mysqli_query($con,"SELECT COUNT(DISTINCT session_id) AS n FROM product_views WHERE viewed_at>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n'];
    $total_cart_sessions = (($__r = mysqli_query($con,"SELECT COUNT(DISTINCT session_id) AS n FROM cart_events WHERE added_at>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n'];
    $order_sessions = (($__r = mysqli_query($con,"SELECT COUNT(DISTINCT userId) AS n FROM orders WHERE orderDate>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n'];
    $revenue = (($__r = mysqli_query($con,"SELECT SUM(o.quantity * p.productPrice) AS total FROM orders o JOIN products p ON o.productId=p.id WHERE o.orderDate>='$from'")) ? mysqli_fetch_assoc($__r) : [])['total'] ?? 0;
    $aov = $order_sessions > 0 ? $revenue / $order_sessions : 0;
    $cr  = $total_sessions > 0 ? round(($order_sessions/$total_sessions)*100,2) : 0;
    $abandonment = $total_cart_sessions > 0 ? round((($total_cart_sessions - $order_sessions) / $total_cart_sessions)*100,2) : 0;
    $new_users = (($__r = mysqli_query($con,"SELECT COUNT(*) AS n FROM users WHERE regDate>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n'];
    $total_users = (($__r = mysqli_query($con,"SELECT COUNT(*) AS n FROM users")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n'];

    fputcsv($out, ['Métrica', 'Valor'], ';');
    fputcsv($out, ['Tasa de conversión', $cr.'%'], ';');
    fputcsv($out, ['Tasa de abandono de carrito', $abandonment.'%'], ';');
    fputcsv($out, ['Ticket promedio (AOV)', '$'.number_format($aov,0,'.',',')], ';');
    fputcsv($out, ['Ingresos totales', '$'.number_format($revenue,0,'.',',')], ';');
    fputcsv($out, ['Total de pedidos', $total_orders], ';');
    fputcsv($out, ['Sesiones únicas', $total_sessions], ';');
    fputcsv($out, ['Usuarios nuevos', $new_users], ';');
    fputcsv($out, ['Total usuarios registrados', $total_users], ';');

    fclose($out);
    exit();
}

// ── QUERIES ─────────────────────────────────────────────────────────────────

// Top productos más vistos
$top_views = mysqli_query($con, "SELECT p.id, p.productName, p.productImage1, c.categoryName,
    COUNT(pv.id) AS total
    FROM product_views pv JOIN products p ON pv.product_id=p.id
    LEFT JOIN category c ON p.category=c.id
    WHERE pv.viewed_at >= '$from'
    GROUP BY pv.product_id ORDER BY total DESC LIMIT 10");

// Top más agregados al carrito
$top_cart = mysqli_query($con, "SELECT p.id, p.productName, p.productImage1, c.categoryName,
    COUNT(ce.id) AS total
    FROM cart_events ce JOIN products p ON ce.product_id=p.id
    LEFT JOIN category c ON p.category=c.id
    WHERE ce.added_at >= '$from'
    GROUP BY ce.product_id ORDER BY total DESC LIMIT 10");

// Top más vendidos
$top_sold = mysqli_query($con, "SELECT p.id, p.productName, p.productImage1,
    SUM(o.quantity) AS units, SUM(o.quantity * p.productPrice) AS revenue
    FROM orders o JOIN products p ON o.productId=p.id
    WHERE o.orderDate >= '$from'
    GROUP BY o.productId ORDER BY units DESC LIMIT 10");

// Métricas generales
$total_orders   = intval((($__r = mysqli_query($con,"SELECT COUNT(*) AS n FROM orders WHERE orderDate>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$total_sessions = intval((($__r = mysqli_query($con,"SELECT COUNT(DISTINCT session_id) AS n FROM product_views WHERE viewed_at>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$total_cart_ses = intval((($__r = mysqli_query($con,"SELECT COUNT(DISTINCT session_id) AS n FROM cart_events WHERE added_at>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$order_sessions = intval((($__r = mysqli_query($con,"SELECT COUNT(DISTINCT userId) AS n FROM orders WHERE orderDate>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$revenue_row    = ($__r = mysqli_query($con,"SELECT SUM(o.quantity * p.productPrice) AS total FROM orders o JOIN products p ON o.productId=p.id WHERE o.orderDate>='$from'")) ? mysqli_fetch_assoc($__r) : [];
$revenue        = floatval($revenue_row['total'] ?? 0);
$aov            = $order_sessions > 0 ? $revenue / $order_sessions : 0;
$cr             = $total_sessions > 0 ? round(($order_sessions / $total_sessions) * 100, 2) : 0;
$abandonment    = $total_cart_ses > 0 ? round((($total_cart_ses - $order_sessions) / $total_cart_ses) * 100, 2) : 0;
if ($abandonment < 0) $abandonment = 0;

// Usuarios nuevos vs recurrentes
$new_users      = intval((($__r = mysqli_query($con,"SELECT COUNT(*) AS n FROM users WHERE regDate>='$from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$total_users    = intval((($__r = mysqli_query($con,"SELECT COUNT(*) AS n FROM users")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$returning_users = max(0, $total_users - $new_users);

// Ingresos por día (últimos N días, para gráfica)
$revenue_chart = mysqli_query($con, "SELECT DATE(o.orderDate) AS day,
    SUM(o.quantity * p.productPrice) AS total
    FROM orders o JOIN products p ON o.productId=p.id
    WHERE o.orderDate >= '$from'
    GROUP BY DATE(o.orderDate) ORDER BY day ASC");
$chart_labels = []; $chart_data = [];
while ($r = mysqli_fetch_assoc($revenue_chart)) {
    $chart_labels[] = $r['day'];
    $chart_data[]   = floatval($r['total']);
}

// Pedidos por estado
$orders_status = mysqli_query($con,"SELECT orderStatus, COUNT(*) AS n FROM orders WHERE orderDate>='$from' GROUP BY orderStatus");
$status_labels = []; $status_data = [];
while ($r = mysqli_fetch_assoc($orders_status)) {
    $status_labels[] = $r['orderStatus'] ?: 'Sin estado';
    $status_data[]   = intval($r['n']);
}

// Productos por disponibilidad
$avail_data = ($__r = mysqli_query($con,"SELECT productAvailability, COUNT(*) AS n FROM products GROUP BY productAvailability")) ? mysqli_fetch_all($__r, MYSQLI_ASSOC) : [];
$avail_map  = ['In Stock'=>'En Stock','Out of Stock'=>'Sin Stock','On Order'=>'Bajo Pedido'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Estadísticas</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stat-card { background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px; margin-bottom:20px; text-align:center; }
        .stat-card .stat-value { font-size:2.2em; font-weight:700; color:#337ab7; line-height:1.1; }
        .stat-card .stat-label { font-size:0.85em; color:#777; margin-top:4px; }
        .stat-card .stat-sub   { font-size:0.78em; color:#aaa; margin-top:2px; }
        .stat-card.danger  .stat-value { color:#d9534f; }
        .stat-card.success .stat-value { color:#5cb85c; }
        .stat-card.warning .stat-value { color:#f0ad4e; }
        .stat-card.purple  .stat-value { color:#9b59b6; }
        .section-title { border-left:4px solid #337ab7; padding-left:10px; margin:24px 0 14px; font-size:1.1em; font-weight:600; color:#333; }
        .chart-box { background:#fff; border:1px solid #ddd; border-radius:6px; padding:16px; margin-bottom:20px; }
        .rank-badge { display:inline-block; width:22px; height:22px; line-height:22px; border-radius:50%; background:#337ab7; color:#fff; font-size:11px; font-weight:700; text-align:center; margin-right:6px; }
        .rank-badge.gold   { background:#f0ad4e; }
        .rank-badge.silver { background:#aaa; }
        .rank-badge.bronze { background:#cd7f32; }
        .top-table td { vertical-align:middle; }
        .top-table td img { width:36px; height:36px; object-fit:cover; border-radius:4px; }
        .bar-fill { height:8px; background:#337ab7; border-radius:4px; display:inline-block; }
        .period-selector a { margin:0 2px; }
        .tip-box { background:#f9f9f9; border:1px solid #e0e0e0; border-radius:5px; padding:10px 14px; font-size:0.82em; color:#555; margin-top:8px; }
        .tip-box i { color:#337ab7; margin-right:4px; }
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
<div class="module-head">
    <h3><i class="icon-bar-chart" style="margin-right:6px"></i>Estadísticas del Negocio</h3>
</div>
<div class="module-body">

    <!-- PERIOD + DOWNLOAD -->
    <div class="row-fluid" style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
        <div class="period-selector">
            <strong>Período:</strong>
            <?php foreach([7=>'7 días',30=>'30 días',90=>'90 días',365=>'1 año'] as $d=>$lbl): ?>
            <a href="?period=<?php echo $d; ?>" class="btn btn-<?php echo $period==$d?'primary':'default'; ?> btn-mini"><?php echo $lbl; ?></a>
            <?php endforeach; ?>
        </div>
        <a href="?period=<?php echo $period; ?>&download=1" class="btn btn-success btn-small">
            <i class="icon-download"></i> Descargar informe CSV
        </a>
    </div>

    <!-- ── MÉTRICAS PRINCIPALES ─────────────────────────────── -->
    <div class="section-title">Métricas de conversión</div>
    <div class="row-fluid">
        <div class="span3">
            <div class="stat-card success">
                <div class="stat-value"><?php echo $cr; ?>%</div>
                <div class="stat-label">Tasa de Conversión</div>
                <div class="stat-sub">Visitantes que compraron</div>
                <div class="tip-box"><i class="icon-info-sign"></i>Objetivo ideal: ≥ 2%</div>
            </div>
        </div>
        <div class="span3">
            <div class="stat-card <?php echo $abandonment>70?'danger':($abandonment>50?'warning':'success'); ?>">
                <div class="stat-value"><?php echo $abandonment; ?>%</div>
                <div class="stat-label">Abandono de Carrito</div>
                <div class="stat-sub">Agregaron pero no pagaron</div>
                <div class="tip-box"><i class="icon-info-sign"></i>Promedio industria: ~70%</div>
            </div>
        </div>
        <div class="span3">
            <div class="stat-card purple">
                <div class="stat-value">$<?php echo number_format($aov,0,'.',','); ?></div>
                <div class="stat-label">Ticket Promedio (AOV)</div>
                <div class="stat-sub">Gasto promedio por cliente</div>
                <div class="tip-box"><i class="icon-info-sign"></i>Aumenta con upselling</div>
            </div>
        </div>
        <div class="span3">
            <div class="stat-card success">
                <div class="stat-value">$<?php echo number_format($revenue,0,'.',','); ?></div>
                <div class="stat-label">Ingresos Totales</div>
                <div class="stat-sub">Últimos <?php echo $period; ?> días</div>
                <div class="tip-box"><i class="icon-info-sign"></i><?php echo $total_orders; ?> pedidos registrados</div>
            </div>
        </div>
    </div>

    <!-- ── MÉTRICAS DE TRÁFICO ──────────────────────────────── -->
    <div class="section-title">Tráfico y comportamiento</div>
    <div class="row-fluid">
        <div class="span3">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_sessions); ?></div>
                <div class="stat-label">Sesiones únicas</div>
                <div class="stat-sub">Visitantes a fichas de producto</div>
            </div>
        </div>
        <div class="span3">
            <div class="stat-card warning">
                <div class="stat-value"><?php echo number_format($total_cart_ses); ?></div>
                <div class="stat-label">Sesiones con carrito</div>
                <div class="stat-sub">Mostraron intención de compra</div>
            </div>
        </div>
        <div class="span3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $new_users; ?></div>
                <div class="stat-label">Usuarios nuevos</div>
                <div class="stat-sub">Registrados en este período</div>
            </div>
        </div>
        <div class="span3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total de clientes</div>
                <div class="stat-sub"><?php echo $returning_users; ?> previos al período</div>
            </div>
        </div>
    </div>

    <!-- ── GRÁFICAS ─────────────────────────────────────────── -->
    <div class="row-fluid">
        <div class="span8">
            <div class="chart-box">
                <strong>Ingresos por día</strong>
                <canvas id="chartRevenue" height="100"></canvas>
            </div>
        </div>
        <div class="span4">
            <div class="chart-box">
                <strong>Estado de pedidos</strong>
                <canvas id="chartStatus" height="160"></canvas>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span6">
            <div class="chart-box">
                <strong>Disponibilidad del catálogo</strong>
                <canvas id="chartAvail" height="130"></canvas>
            </div>
        </div>
        <div class="span6">
            <div class="chart-box">
                <strong>Embudo de ventas</strong>
                <canvas id="chartFunnel" height="130"></canvas>
            </div>
        </div>
    </div>

    <!-- ── TOP PRODUCTOS MÁS VISTOS ─────────────────────────── -->
    <div class="section-title">Productos más vistos</div>
    <?php
    $max_views=0; $rows_views=[];
    while($r=mysqli_fetch_assoc($top_views)){$rows_views[]=$r; if($r['total']>$max_views)$max_views=$r['total'];}
    if(empty($rows_views)): ?>
    <p class="text-muted" style="font-style:italic">Sin datos aún. Los datos se recopilarán a partir de ahora.</p>
    <?php else: ?>
    <table class="table table-striped table-condensed top-table">
        <thead><tr><th>#</th><th>Producto</th><th>Categoría</th><th>Vistas</th><th style="width:30%">Popularidad</th></tr></thead>
        <tbody>
        <?php foreach($rows_views as $i=>$r): $medal=['gold','silver','bronze'][$i]??''; ?>
        <tr>
            <td><span class="rank-badge <?php echo $medal; ?>"><?php echo $i+1; ?></span></td>
            <td>
                <?php if($r['productImage1']): ?><img src="../admin/productimages/<?php echo $r['id'].'/'.htmlentities($r['productImage1']); ?>" style="width:32px;height:32px;object-fit:cover;border-radius:3px;margin-right:6px;"><?php endif; ?>
                <a href="../product-details.php?pid=<?php echo $r['id']; ?>" target="_blank"><?php echo htmlentities($r['productName']); ?></a>
            </td>
            <td><?php echo htmlentities($r['categoryName']??'—'); ?></td>
            <td><strong><?php echo $r['total']; ?></strong></td>
            <td>
                <?php $pct=$max_views>0?round(($r['total']/$max_views)*100):0; ?>
                <span class="bar-fill" style="width:<?php echo $pct; ?>%"></span>
                <span style="font-size:0.8em;color:#999;margin-left:4px"><?php echo $pct; ?>%</span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- ── TOP AGREGADOS AL CARRITO ─────────────────────────── -->
    <div class="section-title">Productos más agregados al carrito</div>
    <?php
    $max_cart=0; $rows_cart=[];
    while($r=mysqli_fetch_assoc($top_cart)){$rows_cart[]=$r; if($r['total']>$max_cart)$max_cart=$r['total'];}
    if(empty($rows_cart)): ?>
    <p class="text-muted" style="font-style:italic">Sin datos aún.</p>
    <?php else: ?>
    <table class="table table-striped table-condensed top-table">
        <thead><tr><th>#</th><th>Producto</th><th>Categoría</th><th>Veces agregado</th><th style="width:30%">Popularidad</th></tr></thead>
        <tbody>
        <?php foreach($rows_cart as $i=>$r): $medal=['gold','silver','bronze'][$i]??''; ?>
        <tr>
            <td><span class="rank-badge <?php echo $medal; ?>"><?php echo $i+1; ?></span></td>
            <td>
                <?php if($r['productImage1']): ?><img src="../admin/productimages/<?php echo $r['id'].'/'.htmlentities($r['productImage1']); ?>" style="width:32px;height:32px;object-fit:cover;border-radius:3px;margin-right:6px;"><?php endif; ?>
                <a href="../product-details.php?pid=<?php echo $r['id']; ?>" target="_blank"><?php echo htmlentities($r['productName']); ?></a>
            </td>
            <td><?php echo htmlentities($r['categoryName']??'—'); ?></td>
            <td><strong><?php echo $r['total']; ?></strong></td>
            <td>
                <?php $pct=$max_cart>0?round(($r['total']/$max_cart)*100):0; ?>
                <span class="bar-fill" style="width:<?php echo $pct; ?>%; background:#f0ad4e;"></span>
                <span style="font-size:0.8em;color:#999;margin-left:4px"><?php echo $pct; ?>%</span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- ── TOP MÁS VENDIDOS ─────────────────────────────────── -->
    <div class="section-title">Productos más vendidos</div>
    <?php
    $max_sold=0; $rows_sold=[];
    while($r=mysqli_fetch_assoc($top_sold)){$rows_sold[]=$r; if($r['units']>$max_sold)$max_sold=$r['units'];}
    if(empty($rows_sold)): ?>
    <p class="text-muted" style="font-style:italic">Sin pedidos en este período.</p>
    <?php else: ?>
    <table class="table table-striped table-condensed top-table">
        <thead><tr><th>#</th><th>Producto</th><th>Unidades</th><th>Ingresos</th><th style="width:25%">Rendimiento</th></tr></thead>
        <tbody>
        <?php foreach($rows_sold as $i=>$r): $medal=['gold','silver','bronze'][$i]??''; ?>
        <tr>
            <td><span class="rank-badge <?php echo $medal; ?>"><?php echo $i+1; ?></span></td>
            <td>
                <?php if($r['productImage1']): ?><img src="../admin/productimages/<?php echo $r['id'].'/'.htmlentities($r['productImage1']); ?>" style="width:32px;height:32px;object-fit:cover;border-radius:3px;margin-right:6px;"><?php endif; ?>
                <a href="../product-details.php?pid=<?php echo $r['id']; ?>" target="_blank"><?php echo htmlentities($r['productName']); ?></a>
            </td>
            <td><strong><?php echo $r['units']; ?></strong> uds</td>
            <td><strong>$<?php echo number_format($r['revenue'],0,'.',','); ?></strong></td>
            <td>
                <?php $pct=$max_sold>0?round(($r['units']/$max_sold)*100):0; ?>
                <span class="bar-fill" style="width:<?php echo $pct; ?>%; background:#5cb85c;"></span>
                <span style="font-size:0.8em;color:#999;margin-left:4px"><?php echo $pct; ?>%</span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- ── INTERPRETACIÓN ───────────────────────────────────── -->
    <div class="section-title">Guía de interpretación</div>
    <table class="table table-bordered" style="font-size:0.88em">
        <thead style="background:#f5f5f5"><tr><th>Métrica</th><th>Valor actual</th><th>Objetivo</th><th>Interpretación</th></tr></thead>
        <tbody>
            <tr>
                <td><strong>Tasa de Conversión</strong></td>
                <td><?php echo $cr; ?>%</td>
                <td>≥ 2%</td>
                <td><?php echo $cr>=2?'<span class="label label-success">Bien — el embudo funciona</span>':($cr>=1?'<span class="label label-warning">Mejorable — revisa el checkout</span>':'<span class="label label-important">Bajo — optimiza la experiencia</span>'); ?></td>
            </tr>
            <tr>
                <td><strong>Abandono de Carrito</strong></td>
                <td><?php echo $abandonment; ?>%</td>
                <td>≤ 70%</td>
                <td><?php echo $abandonment<=70?'<span class="label label-success">Normal</span>':'<span class="label label-important">Alto — revisa costos de envío y proceso de pago</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Ticket Promedio (AOV)</strong></td>
                <td>$<?php echo number_format($aov,0,'.',','); ?></td>
                <td>Creciente</td>
                <td>Aplica bundles y upselling para incrementarlo</td>
            </tr>
            <tr>
                <td><strong>Usuarios nuevos</strong></td>
                <td><?php echo $new_users; ?></td>
                <td>Creciente</td>
                <td><?php echo $new_users>0?'<span class="label label-success">Hay adquisición de clientes</span>':'<span class="label label-warning">Sin registros nuevos en el período</span>'; ?></td>
            </tr>
        </tbody>
    </table>

</div><!-- /.module-body -->
</div><!-- /.module -->
</div><!-- /.content -->
</div><!-- /.span9 -->
</div><!-- /.row -->
</div><!-- /.container -->
</div><!-- /.wrapper -->

<?php include('include/footer.php'); ?>

<script>
// ── Ingresos por día ───────────────────────────────────────────
new Chart(document.getElementById('chartRevenue'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Ingresos ($)',
            data: <?php echo json_encode($chart_data); ?>,
            borderColor: '#337ab7',
            backgroundColor: 'rgba(51,122,183,0.12)',
            tension: 0.3,
            fill: true,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '$'+v.toLocaleString() } } }
    }
});

// ── Estado de pedidos ──────────────────────────────────────────
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{ data: <?php echo json_encode($status_data); ?>,
            backgroundColor: ['#5cb85c','#f0ad4e','#d9534f','#337ab7','#9b59b6','#aaa'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size:11 } } } } }
});

// ── Disponibilidad catálogo ────────────────────────────────────
const availMap = <?php echo json_encode($avail_map); ?>;
const availRaw = <?php echo json_encode($avail_data); ?>;
new Chart(document.getElementById('chartAvail'), {
    type: 'pie',
    data: {
        labels: availRaw.map(r => availMap[r.productAvailability] || r.productAvailability),
        datasets: [{ data: availRaw.map(r => r.n),
            backgroundColor: ['#5cb85c','#d9534f','#f0ad4e'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size:11 } } } } }
});

// ── Embudo de ventas ───────────────────────────────────────────
new Chart(document.getElementById('chartFunnel'), {
    type: 'bar',
    data: {
        labels: ['Sesiones', 'Con carrito', 'Compraron'],
        datasets: [{
            label: 'Usuarios',
            data: [<?php echo $total_sessions; ?>, <?php echo $total_cart_ses; ?>, <?php echo $order_sessions; ?>],
            backgroundColor: ['#337ab7','#f0ad4e','#5cb85c'],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
