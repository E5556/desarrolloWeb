<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

date_default_timezone_set('America/Bogota');

// Tabla de vistas de producto (para tasa de conversión)
mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    view_date DATE NOT NULL,
    INDEX(product_id), INDEX(view_date)
)");

// ── KPIs ────────────────────────────────────────────────────────────────────
$today      = date('Y-m-d');
$week_from  = date('Y-m-d', strtotime('-7 days'));
$month_from = date('Y-m-d', strtotime('-30 days'));

// Pedidos hoy
$kpi_orders_today = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM orders WHERE DATE(orderDate)='$today'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Pedidos esta semana
$kpi_orders_week = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM orders WHERE orderDate>='$week_from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Ingresos del mes
$kpi_revenue_month = floatval((($__r = mysqli_query($con,
    "SELECT COALESCE(SUM(o.quantity*p.productPrice),0) n
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.orderDate>='$month_from'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Ingresos totales
$kpi_revenue_total = floatval((($__r = mysqli_query($con,
    "SELECT COALESCE(SUM(o.quantity*p.productPrice),0) n
     FROM orders o JOIN products p ON o.productId=p.id")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Total clientes
$kpi_users = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM users")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Usuarios nuevos hoy
$kpi_users_today = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM users WHERE DATE(regDate)='$today'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Pedidos pendientes
$kpi_pending = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM orders WHERE orderStatus IS NULL OR orderStatus NOT IN ('Delivered','Cancelado')")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Total productos
$kpi_products = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM products")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// ── ALERTAS INVENTARIO ───────────────────────────────────────────────────────
$out_of_stock = mysqli_query($con,
    "SELECT id, productName, productImage1 FROM products WHERE productAvailability='Out of Stock' ORDER BY productName LIMIT 20");
$out_count    = mysqli_num_rows($out_of_stock);

// Stock bajo (umbral configurable en settings)
$_low_threshold = intval((($__r = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='low_stock_threshold'")) && ($_rv = mysqli_fetch_assoc($__r))) ? $_rv['setting_value'] : 5);
if ($_low_threshold < 1) $_low_threshold = 5;
$low_stock = mysqli_query($con,
    "SELECT id, productName, stock_qty FROM products WHERE stock_qty IS NOT NULL AND stock_qty > 0 AND stock_qty <= $_low_threshold ORDER BY stock_qty ASC LIMIT 20");
$low_count = mysqli_num_rows($low_stock);

// ── VENTAS POR DÍA (últimos 14 días) ────────────────────────────────────────
$sales_chart = mysqli_query($con,
    "SELECT DATE(o.orderDate) d, COUNT(*) pedidos, COALESCE(SUM(o.quantity*p.productPrice),0) ingresos
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.orderDate >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
     GROUP BY DATE(o.orderDate) ORDER BY d ASC");
$chart_days=[]; $chart_orders=[]; $chart_revenue=[];
while($r=mysqli_fetch_assoc($sales_chart)){
    $chart_days[]    = $r['d'];
    $chart_orders[]  = intval($r['pedidos']);
    $chart_revenue[] = floatval($r['ingresos']);
}

// ── ÚLTIMOS 8 PEDIDOS ────────────────────────────────────────────────────────
$recent_orders = mysqli_query($con,
    "SELECT o.id, o.orderDate, o.orderStatus, o.quantity,
            p.productName, p.productPrice,
            u.name AS username, u.email
     FROM orders o
     JOIN products p ON o.productId=p.id
     JOIN users u ON o.userId=u.id
     ORDER BY o.orderDate DESC LIMIT 8");

// ── ÚLTIMOS 5 USUARIOS REGISTRADOS ──────────────────────────────────────────
$recent_users = mysqli_query($con,
    "SELECT id, name, email, regDate FROM users ORDER BY regDate DESC LIMIT 5");

// ── TOP 10 PRODUCTOS MÁS VENDIDOS (mes) ─────────────────────────────────────
$top_products = mysqli_query($con,
    "SELECT p.id, p.productName, p.productImage1,
            SUM(o.quantity) units, SUM(o.quantity*p.productPrice) revenue
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.orderDate>='$month_from'
     GROUP BY o.productId ORDER BY units DESC LIMIT 10");

// ── VENTAS POR CATEGORÍA (mes) ───────────────────────────────────────────────
$cat_sales = mysqli_query($con,
    "SELECT c.catName, COUNT(o.id) orders_n, SUM(o.quantity) units, SUM(o.quantity*p.productPrice) revenue
     FROM orders o JOIN products p ON o.productId=p.id JOIN categories c ON p.catId=c.id
     WHERE o.orderDate>='$month_from'
     GROUP BY c.id ORDER BY revenue DESC LIMIT 10");
$cat_rows = []; $cat_max = 1;
while ($cr = mysqli_fetch_assoc($cat_sales)) { $cat_rows[]=$cr; if($cr['revenue']>$cat_max)$cat_max=$cr['revenue']; }

// ── TASA DE CONVERSIÓN ───────────────────────────────────────────────────────
$_vis_q  = mysqli_query($con, "SELECT COUNT(*) n FROM product_views WHERE view_date>='$month_from'");
$_vis    = $_vis_q ? intval(mysqli_fetch_assoc($_vis_q)['n']) : 0;
$_buy_q  = mysqli_query($con, "SELECT COUNT(DISTINCT userId) n FROM orders WHERE orderDate>='$month_from' AND paymentMethod IS NOT NULL");
$_buyers = $_buy_q ? intval(mysqli_fetch_assoc($_buy_q)['n']) : 0;
$_conv   = $kpi_users > 0 ? round(($_buyers/$kpi_users)*100,1) : 0;

// ── VENTAS POR PERÍODO (semana y mes para toggle) ────────────────────────────
$sales_week_chart = mysqli_query($con,
    "SELECT DATE(o.orderDate) d, COUNT(*) pedidos, COALESCE(SUM(o.quantity*p.productPrice),0) ingresos
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.orderDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(o.orderDate) ORDER BY d ASC");
$week_days=[]; $week_orders=[]; $week_revenue=[];
while($r=mysqli_fetch_assoc($sales_week_chart)){$week_days[]=$r['d'];$week_orders[]=intval($r['pedidos']);$week_revenue[]=floatval($r['ingresos']);}

$sales_month_chart = mysqli_query($con,
    "SELECT DATE_FORMAT(o.orderDate,'%Y-%m') d, COUNT(*) pedidos, COALESCE(SUM(o.quantity*p.productPrice),0) ingresos
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.orderDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(o.orderDate,'%Y-%m') ORDER BY d ASC");
$month_labels=[]; $month_orders=[]; $month_revenue=[];
while($r=mysqli_fetch_assoc($sales_month_chart)){$month_labels[]=$r['d'];$month_orders[]=intval($r['pedidos']);$month_revenue[]=floatval($r['ingresos']);}

$status_colors = [
    'Delivered'  => 'label-success',
    'Pending'    => 'label-warning',
    'Shipped'    => 'label-info',
    'Cancelado'  => 'label-important',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Dashboard</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ── KPI Cards ── */
        .kpi-row { display:flex; flex-wrap:wrap; gap:14px; margin-bottom:22px; }
        .kpi-card { flex:1 1 140px; background:#fff; border:1px solid #e0e0e0; border-radius:7px;
                    padding:18px 16px; text-align:center; position:relative; overflow:hidden; }
        .kpi-card .kpi-icon { font-size:2em; margin-bottom:6px; opacity:.15; position:absolute;
                               right:12px; top:10px; }
        .kpi-card .kpi-val  { font-size:1.9em; font-weight:700; line-height:1.1; }
        .kpi-card .kpi-lbl  { font-size:.78em; color:#888; margin-top:3px; }
        .kpi-card .kpi-sub  { font-size:.72em; color:#aaa; margin-top:2px; }
        .kpi-blue   { border-top:3px solid #337ab7; } .kpi-blue .kpi-val   { color:#337ab7; }
        .kpi-green  { border-top:3px solid #5cb85c; } .kpi-green .kpi-val  { color:#5cb85c; }
        .kpi-orange { border-top:3px solid #f0ad4e; } .kpi-orange .kpi-val { color:#f0ad4e; }
        .kpi-red    { border-top:3px solid #d9534f; } .kpi-red .kpi-val    { color:#d9534f; }
        .kpi-purple { border-top:3px solid #9b59b6; } .kpi-purple .kpi-val { color:#9b59b6; }
        /* ── Alerts ── */
        .stock-alert { background:#fff3cd; border:1px solid #ffc107; border-radius:6px;
                       padding:12px 16px; margin-bottom:20px; }
        .stock-alert .alert-title { font-weight:700; color:#856404; margin-bottom:8px; font-size:.95em; }
        .stock-pill { display:inline-flex; align-items:center; gap:6px; background:#fff;
                      border:1px solid #ffc107; border-radius:20px; padding:4px 10px 4px 4px;
                      margin:3px; font-size:.8em; color:#555; text-decoration:none; }
        .stock-pill img { width:24px; height:24px; border-radius:50%; object-fit:cover; }
        .stock-pill:hover { border-color:#e8233a; color:#e8233a; }
        /* ── Section headers ── */
        .dash-section { font-size:.95em; font-weight:700; color:#444; border-left:3px solid #337ab7;
                         padding-left:8px; margin:22px 0 10px; }
        /* ── Charts ── */
        .chart-box { background:#fff; border:1px solid #e0e0e0; border-radius:6px;
                     padding:16px; margin-bottom:18px; }
        /* ── Tables ── */
        .dash-table td, .dash-table th { vertical-align:middle !important; font-size:.85em; }
        .dash-table td img { width:32px; height:32px; object-fit:cover; border-radius:3px; }
        .user-avatar { width:28px; height:28px; border-radius:50%; background:#337ab7;
                       color:#fff; font-size:.75em; font-weight:700; display:inline-flex;
                       align-items:center; justify-content:center; margin-right:5px; }
        /* ── Top products bar ── */
        .mini-bar { height:6px; border-radius:3px; background:#5cb85c; display:inline-block; }
        /* ── Greeting ── */
        .dash-greeting { background:linear-gradient(135deg,#337ab7,#23527c);
                         color:#fff; border-radius:7px; padding:18px 22px; margin-bottom:20px;
                         display:flex; justify-content:space-between; align-items:center; }
        .dash-greeting h2 { margin:0; font-size:1.3em; font-weight:600; }
        .dash-greeting .dash-date { font-size:.82em; opacity:.8; margin-top:3px; }
        .dash-greeting .dash-links a { color:rgba(255,255,255,.85); font-size:.82em; margin-left:12px;
                                        text-decoration:none; }
        .dash-greeting .dash-links a:hover { color:#fff; }
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

    <!-- GREETING -->
    <div class="dash-greeting">
        <div>
            <h2>Bienvenido, <?php echo htmlentities($_SESSION['alogin']); ?> 👋</h2>
            <div class="dash-date"><?php echo date('l, d \d\e F \d\e Y'); ?> &nbsp;·&nbsp; <?php echo date('H:i'); ?></div>
        </div>
        <div class="dash-links">
            <a href="statistics.php"><i class="icon-bar-chart"></i> Estadísticas</a>
            <a href="manage-products.php"><i class="icon-th-list"></i> Productos</a>
            <a href="pending-orders.php"><i class="icon-tasks"></i> Pedidos</a>
        </div>
    </div>

    <!-- ── KPI CARDS ─────────────────────────────────────────── -->
    <div class="kpi-row">
        <div class="kpi-card kpi-blue">
            <i class="icon-shopping-cart kpi-icon"></i>
            <div class="kpi-val"><?php echo $kpi_orders_today; ?></div>
            <div class="kpi-lbl">Pedidos hoy</div>
            <div class="kpi-sub"><?php echo $kpi_orders_week; ?> esta semana</div>
        </div>
        <div class="kpi-card kpi-orange">
            <i class="icon-time kpi-icon"></i>
            <div class="kpi-val"><?php echo $kpi_pending; ?></div>
            <div class="kpi-lbl">Pendientes</div>
            <div class="kpi-sub">Sin procesar</div>
        </div>
        <div class="kpi-card kpi-green">
            <i class="icon-money kpi-icon"></i>
            <div class="kpi-val">$<?php echo number_format($kpi_revenue_month,0,'.',','); ?></div>
            <div class="kpi-lbl">Ingresos 30 días</div>
            <div class="kpi-sub">Total: $<?php echo number_format($kpi_revenue_total,0,'.',','); ?></div>
        </div>
        <div class="kpi-card kpi-purple">
            <i class="icon-group kpi-icon"></i>
            <div class="kpi-val"><?php echo $kpi_users; ?></div>
            <div class="kpi-lbl">Clientes</div>
            <div class="kpi-sub">+<?php echo $kpi_users_today; ?> hoy</div>
        </div>
        <div class="kpi-card <?php echo $out_count>0?'kpi-red':'kpi-green'; ?>">
            <i class="icon-warning-sign kpi-icon"></i>
            <div class="kpi-val"><?php echo $out_count; ?></div>
            <div class="kpi-lbl">Sin stock</div>
            <div class="kpi-sub"><?php echo $kpi_products; ?> productos total</div>
        </div>
        <div class="kpi-card kpi-blue">
            <i class="icon-exchange kpi-icon"></i>
            <div class="kpi-val"><?php echo $_conv; ?>%</div>
            <div class="kpi-lbl">Tasa conversión</div>
            <div class="kpi-sub"><?php echo $_buyers; ?> compradores / <?php echo $kpi_users; ?> clientes</div>
        </div>
    </div>

    <!-- ── ALERTA INVENTARIO ─────────────────────────────────── -->
    <?php if ($out_count > 0): ?>
    <div class="stock-alert">
        <div class="alert-title"><i class="icon-warning-sign"></i> Alerta de inventario — <?php echo $out_count; ?> producto(s) sin stock</div>
        <?php
        mysqli_data_seek($out_of_stock, 0);
        while ($p = mysqli_fetch_assoc($out_of_stock)):
        ?>
        <a href="edit-products.php?pid=<?php echo $p['id']; ?>" class="stock-pill">
            <?php if($p['productImage1']): ?>
            <img src="productimages/<?php echo $p['id'].'/'.htmlentities($p['productImage1']); ?>" onerror="this.style.display='none'">
            <?php endif; ?>
            <?php echo htmlentities($p['productName']); ?>
        </a>
        <?php endwhile; ?>
        <div style="margin-top:6px;font-size:.75em;color:#aaa">Haz clic en un producto para editarlo.</div>
    </div>
    <?php endif; ?>

    <!-- ── ALERTA STOCK BAJO ──────────────────────────────────── -->
    <?php if ($low_count > 0): ?>
    <div class="stock-alert" style="background:#fff3cd;border-color:#ffc107;">
        <div class="alert-title" style="color:#856404"><i class="icon-exclamation-sign"></i> Stock bajo — <?php echo $low_count; ?> producto(s) con &le; <?php echo $_low_threshold; ?> unidades</div>
        <?php while ($p = mysqli_fetch_assoc($low_stock)): ?>
        <a href="edit-products.php?pid=<?php echo $p['id']; ?>" class="stock-pill" style="background:#ffeeba;border-color:#ffc107;color:#856404">
            <?php echo htmlentities($p['productName']); ?>
            <span style="font-weight:700;margin-left:4px">(<?php echo intval($p['stock_qty']); ?> uds)</span>
        </a>
        <?php endwhile; ?>
        <div style="margin-top:6px;font-size:.75em;color:#aaa">Umbral configurable en <a href="settings.php">Configuración</a>.</div>
    </div>
    <?php endif; ?>

    <!-- ── GRÁFICA VENTAS CON TOGGLE ──────────────────────────── -->
    <div class="dash-section" style="display:flex;align-items:center;justify-content:space-between">
        <span id="chart-title">Ventas — últimos 14 días</span>
        <div class="btn-group btn-group-xs">
            <button class="btn btn-primary" onclick="switchChart('14d')">14 días</button>
            <button class="btn btn-default" onclick="switchChart('7d')">7 días</button>
            <button class="btn btn-default" onclick="switchChart('6m')">6 meses</button>
        </div>
    </div>
    <div class="chart-box">
        <canvas id="chartSales" height="80"></canvas>
    </div>

    <!-- ── DOS COLUMNAS: pedidos + top productos ─────────────── -->
    <div class="row-fluid">
        <div class="span7">
            <div class="dash-section">Últimos pedidos</div>
            <table class="table table-striped table-condensed dash-table">
                <thead>
                    <tr>
                        <th>#</th><th>Cliente</th><th>Producto</th>
                        <th>Total</th><th>Estado</th><th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($ord = mysqli_fetch_assoc($recent_orders)):
                    $initials = strtoupper(substr($ord['username'],0,1));
                    $status   = $ord['orderStatus'] ?: 'Pendiente';
                    $lbl      = $status_colors[$ord['orderStatus']] ?? 'label-default';
                    $total    = intval($ord['quantity']) * intval($ord['productPrice']);
                ?>
                <tr>
                    <td><small>#<?php echo $ord['id']; ?></small></td>
                    <td>
                        <span class="user-avatar"><?php echo $initials; ?></span>
                        <small><?php echo htmlentities($ord['username']); ?></small>
                    </td>
                    <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <small><?php echo htmlentities($ord['productName']); ?></small>
                    </td>
                    <td><small><strong>$<?php echo number_format($total,0,'.',','); ?></strong></small></td>
                    <td><span class="label <?php echo $lbl; ?>"><?php echo htmlentities($status); ?></span></td>
                    <td><small><?php echo date('d/m H:i', strtotime($ord['orderDate'])); ?></small></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <a href="pending-orders.php" class="btn btn-mini btn-primary">Ver todos los pedidos</a>
        </div>

        <div class="span5">
            <div class="dash-section">Top 10 productos (30 días)</div>
            <?php
            $max_units=0; $top_rows=[];
            while($r=mysqli_fetch_assoc($top_products)){$top_rows[]=$r; if($r['units']>$max_units)$max_units=$r['units'];}
            if(empty($top_rows)): ?>
            <p class="text-muted" style="font-style:italic;font-size:.85em">Sin ventas en los últimos 30 días.</p>
            <?php else: ?>
            <table class="table table-condensed dash-table">
                <thead><tr><th>#</th><th>Producto</th><th>Uds</th><th>Ingresos</th></tr></thead>
                <tbody>
                <?php foreach($top_rows as $i=>$r): $pct=$max_units>0?round(($r['units']/$max_units)*100):0; ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td>
                        <?php if($r['productImage1']): ?>
                        <img src="productimages/<?php echo $r['id'].'/'.htmlentities($r['productImage1']); ?>" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <small><?php echo htmlentities(mb_substr($r['productName'],0,22)); ?><?php echo mb_strlen($r['productName'])>22?'…':''; ?></small>
                    </td>
                    <td><strong><?php echo $r['units']; ?></strong></td>
                    <td><small style="color:#888">$<?php echo number_format($r['revenue'],0,'.',','); ?></small></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- MAPA DE CALOR CATEGORÍAS -->
            <div class="dash-section" style="margin-top:18px">Categorías — ingresos (30 días)</div>
            <?php if (empty($cat_rows)): ?>
            <p class="text-muted" style="font-style:italic;font-size:.85em">Sin datos.</p>
            <?php else: foreach($cat_rows as $cr): $pct=round(($cr['revenue']/$cat_max)*100); ?>
            <div style="margin-bottom:7px">
                <div style="display:flex;justify-content:space-between;font-size:.8em;margin-bottom:2px">
                    <span><?php echo htmlspecialchars($cr['catName']); ?></span>
                    <span style="color:#888">$<?php echo number_format($cr['revenue'],0,'.',','); ?></span>
                </div>
                <div style="background:#eee;border-radius:3px;height:8px">
                    <div style="width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#337ab7,#5cb85c);height:8px;border-radius:3px"></div>
                </div>
            </div>
            <?php endforeach; endif; ?>

            <div class="dash-section" style="margin-top:18px">Clientes recientes</div>
            <table class="table table-condensed dash-table">
                <thead><tr><th>Cliente</th><th>Email</th><th>Registro</th></tr></thead>
                <tbody>
                <?php while($u=mysqli_fetch_assoc($recent_users)): ?>
                <tr>
                    <td>
                        <span class="user-avatar"><?php echo strtoupper(substr($u['name'],0,1)); ?></span>
                        <small><?php echo htmlentities($u['name']); ?></small>
                    </td>
                    <td><small style="color:#888"><?php echo htmlentities($u['email']); ?></small></td>
                    <td><small><?php echo date('d/m/y', strtotime($u['regDate'])); ?></small></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <a href="manage-users.php" class="btn btn-mini btn-default">Ver todos los clientes</a>
        </div>
    </div>

</div><!-- /.content -->
</div><!-- /.span9 -->
</div><!-- /.row -->
</div><!-- /.container -->
</div><!-- /.wrapper -->

<?php include('include/footer.php'); ?>

<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
var datasets14d = {
    labels:  <?php echo json_encode($chart_days); ?>,
    orders:  <?php echo json_encode($chart_orders); ?>,
    revenue: <?php echo json_encode($chart_revenue); ?>
};
var datasets7d = {
    labels:  <?php echo json_encode($week_days); ?>,
    orders:  <?php echo json_encode($week_orders); ?>,
    revenue: <?php echo json_encode($week_revenue); ?>
};
var datasets6m = {
    labels:  <?php echo json_encode($month_labels); ?>,
    orders:  <?php echo json_encode($month_orders); ?>,
    revenue: <?php echo json_encode($month_revenue); ?>
};
var titles = { '14d':'Ventas — últimos 14 días', '7d':'Ventas — última semana', '6m':'Ventas — últimos 6 meses' };

var salesChart = new Chart(document.getElementById('chartSales'), {
    type: 'bar',
    data: {
        labels: datasets14d.labels,
        datasets: [
            { label:'Pedidos', data:datasets14d.orders, backgroundColor:'rgba(51,122,183,0.7)', borderColor:'#337ab7', borderWidth:1, yAxisID:'y' },
            { label:'Ingresos ($)', data:datasets14d.revenue, type:'line', borderColor:'#5cb85c', backgroundColor:'rgba(92,184,92,0.1)', tension:0.3, fill:true, yAxisID:'y2', pointRadius:3 }
        ]
    },
    options: {
        responsive:true,
        interaction:{ mode:'index', intersect:false },
        plugins:{ legend:{ position:'top', labels:{ font:{ size:11 } } } },
        scales:{
            y: { beginAtZero:true, position:'left',  title:{ display:true, text:'Pedidos' }, ticks:{ stepSize:1 } },
            y2:{ beginAtZero:true, position:'right', title:{ display:true, text:'Ingresos ($)' }, grid:{ drawOnChartArea:false }, ticks:{ callback:v=>'$'+v.toLocaleString('es-CO') } }
        }
    }
});

function switchChart(period) {
    var d = period==='7d' ? datasets7d : (period==='6m' ? datasets6m : datasets14d);
    salesChart.data.labels = d.labels;
    salesChart.data.datasets[0].data = d.orders;
    salesChart.data.datasets[1].data = d.revenue;
    salesChart.update();
    document.getElementById('chart-title').textContent = titles[period];
    document.querySelectorAll('.btn-group .btn').forEach(function(b,i){
        b.className = (i===(['14d','7d','6m'].indexOf(period))) ? 'btn btn-primary' : 'btn btn-default';
    });
}
</script>
</body>
</html>
