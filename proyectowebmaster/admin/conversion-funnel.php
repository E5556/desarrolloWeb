<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$range = intval($_GET['days'] ?? 30);
$range = in_array($range, [7,14,30,60,90]) ? $range : 30;
$from = date('Y-m-d', strtotime("-$range days"));

// 1. Product views
$q_views = mysqli_query($con, "SELECT COUNT(*) n FROM product_views WHERE view_date >= '$from'");
$views = intval(mysqli_fetch_assoc($q_views)['n'] ?? 0);

// 2. Cart additions (orders created, even unpaid)
$q_carts = mysqli_query($con, "SELECT COUNT(DISTINCT userId) n FROM orders WHERE DATE(orderDate) >= '$from'");
$carts = intval(mysqli_fetch_assoc($q_carts)['n'] ?? 0);

// 3. Checkout (payment-method reached = paymentMethod IS NOT NULL)
$q_checkout = mysqli_query($con, "SELECT COUNT(DISTINCT userId) n FROM orders WHERE paymentMethod IS NOT NULL AND DATE(orderDate) >= '$from'");
$checkout = intval(mysqli_fetch_assoc($q_checkout)['n'] ?? 0);

// 4. Purchases completed (distinct users with paid orders)
$q_purchase = mysqli_query($con, "SELECT COUNT(DISTINCT userId) n FROM orders WHERE paymentMethod IS NOT NULL AND orderStatus IS NOT NULL AND DATE(orderDate) >= '$from'");
$purchases = intval(mysqli_fetch_assoc($q_purchase)['n'] ?? 0);

// Rates
$r_cart     = $views     > 0 ? round($carts    / $views    * 100, 1) : 0;
$r_checkout = $carts     > 0 ? round($checkout / $carts    * 100, 1) : 0;
$r_purchase = $checkout  > 0 ? round($purchases/ $checkout * 100, 1) : 0;
$r_overall  = $views     > 0 ? round($purchases/ $views    * 100, 2) : 0;

// Daily trend (last N days)
$daily_q = mysqli_query($con, "SELECT DATE(orderDate) d, COUNT(DISTINCT userId) n FROM orders WHERE paymentMethod IS NOT NULL AND DATE(orderDate) >= '$from' GROUP BY DATE(orderDate) ORDER BY d");
$daily_labels = [];
$daily_data   = [];
while ($daily_q && $dr = mysqli_fetch_assoc($daily_q)) {
    $daily_labels[] = date('d/m', strtotime($dr['d']));
    $daily_data[]   = intval($dr['n']);
}

// Top drop-off: products with most views but fewest orders
$top_dropoff = mysqli_query($con, "SELECT p.productName, COUNT(pv.id) views,
    COALESCE(SUM(o.quantity),0) sold
    FROM product_views pv
    JOIN products p ON p.id=pv.product_id
    LEFT JOIN orders o ON o.productId=pv.product_id AND o.paymentMethod IS NOT NULL
    WHERE pv.view_date >= '$from'
    GROUP BY p.id, p.productName
    HAVING views > 5
    ORDER BY views DESC, sold ASC
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Funnel de conversion | Admin</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content-area">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
<h3><i class="icon-filter"></i> Funnel de conversion</h3>
<form method="get" class="form-inline">
<label>Periodo:</label>
<select name="days" class="form-control input-sm" onchange="this.form.submit()" style="margin-left:8px">
<?php foreach ([7=>'7 dias',14=>'14 dias',30=>'30 dias',60=>'60 dias',90=>'90 dias'] as $d=>$l): ?>
<option value="<?php echo $d; ?>" <?php echo $range==$d?'selected':''; ?>><?php echo $l; ?></option>
<?php endforeach; ?>
</select>
</form>
</div>

<!-- Funnel visual -->
<div style="display:flex;flex-direction:column;align-items:center;gap:0;margin-bottom:30px">
<?php
$steps = [
    ['label'=>'Vistas de producto','value'=>$views,'icon'=>'icon-eye-open','color'=>'#3498db','rate'=>null],
    ['label'=>'Usuarios con carrito','value'=>$carts,'icon'=>'icon-shopping-cart','color'=>'#9b59b6','rate'=>$r_cart.'% de vistas'],
    ['label'=>'Llegaron a pago','value'=>$checkout,'icon'=>'icon-credit-card','color'=>'#e67e22','rate'=>$r_checkout.'% del carrito'],
    ['label'=>'Compraron','value'=>$purchases,'icon'=>'icon-ok','color'=>'#27ae60','rate'=>$r_purchase.'% del checkout'],
];
$max_val = max(array_column($steps,'value')) ?: 1;
foreach ($steps as $i => $step):
    $width = max(20, round($step['value'] / $max_val * 100));
    $clip_left  = (100 - $width) / 2;
?>
<div style="width:100%;display:flex;flex-direction:column;align-items:center">
<div style="width:<?php echo $width; ?>%;background:<?php echo $step['color']; ?>;color:#fff;border-radius:<?php echo $i===0?'8px 8px':''; ?><?php echo $i===count($steps)-1?'0 0 8px 8px':''; ?>;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;transition:width .4s">
<span><i class="<?php echo $step['icon']; ?>"></i> <strong><?php echo $step['label']; ?></strong></span>
<strong style="font-size:22px"><?php echo number_format($step['value']); ?></strong>
</div>
<?php if ($step['rate'] !== null): ?>
<div style="font-size:12px;color:#888;margin:2px 0 2px">
<i class="icon-arrow-down"></i> Conversion: <strong><?php echo $step['rate']; ?></strong>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- Tasa global -->
<div class="row" style="margin-bottom:30px">
<div class="col-md-3">
<div style="background:#f8f9fa;border-radius:10px;padding:16px;text-align:center">
<div style="font-size:28px;font-weight:700;color:#27ae60"><?php echo $r_overall; ?>%</div>
<div style="font-size:13px;color:#666">Tasa de conversion global<br><small>vistas → compras</small></div>
</div>
</div>
<div class="col-md-3">
<div style="background:#f8f9fa;border-radius:10px;padding:16px;text-align:center">
<div style="font-size:28px;font-weight:700;color:#e74c3c"><?php echo 100-$r_cart; ?>%</div>
<div style="font-size:13px;color:#666">Abandonan sin carrito<br><small>de vistas</small></div>
</div>
</div>
<div class="col-md-3">
<div style="background:#f8f9fa;border-radius:10px;padding:16px;text-align:center">
<div style="font-size:28px;font-weight:700;color:#e67e22"><?php echo 100-$r_checkout; ?>%</div>
<div style="font-size:13px;color:#666">Abandono de carrito<br><small>antes del pago</small></div>
</div>
</div>
<div class="col-md-3">
<div style="background:#f8f9fa;border-radius:10px;padding:16px;text-align:center">
<div style="font-size:28px;font-weight:700;color:#9b59b6"><?php echo 100-$r_purchase; ?>%</div>
<div style="font-size:13px;color:#666">Abandono en checkout<br><small>sin completar pago</small></div>
</div>
</div>
</div>

<!-- Daily trend chart -->
<?php if (!empty($daily_labels)): ?>
<div class="panel panel-default">
<div class="panel-heading"><strong>Compras por dia</strong></div>
<div class="panel-body">
<canvas id="daily-chart" height="80"></canvas>
</div>
</div>
<?php endif; ?>

<!-- Top drop-off products -->
<div class="panel panel-default">
<div class="panel-heading"><strong>Productos con mayor abandono <small class="text-muted">(muchas vistas, pocas ventas)</small></strong></div>
<div class="panel-body" style="padding:0">
<table class="table table-hover" style="margin:0;font-size:13px">
<thead><tr><th>Producto</th><th>Vistas</th><th>Unidades vendidas</th><th>Conversion estimada</th></tr></thead>
<tbody>
<?php if (!$top_dropoff || mysqli_num_rows($top_dropoff) === 0): ?>
<tr><td colspan="4" class="text-center text-muted">Sin datos suficientes.</td></tr>
<?php else: while ($td = mysqli_fetch_assoc($top_dropoff)):
    $td_rate = $td['views'] > 0 ? round($td['sold'] / $td['views'] * 100, 1) : 0;
?>
<tr>
<td><?php echo htmlspecialchars($td['productName']); ?></td>
<td><?php echo number_format($td['views']); ?></td>
<td><?php echo number_format($td['sold']); ?></td>
<td>
<div style="display:flex;align-items:center;gap:8px">
<div style="flex:1;background:#eee;border-radius:4px;height:8px;overflow:hidden">
<div style="background:<?php echo $td_rate<1?'#e74c3c':($td_rate<5?'#f39c12':'#27ae60'); ?>;height:100%;width:<?php echo min(100,$td_rate*10); ?>%"></div>
</div>
<span style="font-size:12px;min-width:36px"><?php echo $td_rate; ?>%</span>
</div>
</td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
</div>
</div>

</div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<?php if (!empty($daily_labels)): ?>
<script>
new Chart(document.getElementById('daily-chart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: 'Compras',
            data: <?php echo json_encode($daily_data); ?>,
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39,174,96,.1)',
            fill: true,
            tension: .3,
            pointRadius: 4,
            pointBackgroundColor: '#27ae60'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
<?php endif; ?>
</body>
</html>
