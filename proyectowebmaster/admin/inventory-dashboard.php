<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_products');

// ── Datos para gráficas ──────────────────────────────────────────────────────
// Valor en inventario por categoría
$cat_val_q = mysqli_query($con,
    "SELECT c.categoryName, SUM(COALESCE(p.stock_qty,0)*p.productPrice) as valor, COUNT(p.id) as productos
     FROM products p JOIN category c ON c.id=p.category
     GROUP BY p.category ORDER BY valor DESC LIMIT 10");
$cat_labels=[]; $cat_values=[]; $cat_counts=[];
while($r=mysqli_fetch_assoc($cat_val_q)){$cat_labels[]=$r['categoryName'];$cat_values[]=(float)$r['valor'];$cat_counts[]=(int)$r['productos'];}

// Rotación — productos más vendidos últimos 30 días
$rot_q = mysqli_query($con,
    "SELECT p.productName, SUM(o.quantity) as vendidos, p.stock_qty
     FROM orders o JOIN products p ON p.id=o.productId
     WHERE o.orderDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY o.productId ORDER BY vendidos DESC LIMIT 10");
$rot_names=[]; $rot_sales=[]; $rot_stock=[];
while($r=mysqli_fetch_assoc($rot_q)){$rot_names[]=$r['productName'];$rot_sales[]=(int)$r['vendidos'];$rot_stock[]=(int)($r['stock_qty']??0);}

// Movimientos últimos 30 días
$mov_q = mysqli_query($con,
    "SELECT DATE(created_at) as dia,
            SUM(CASE WHEN type='in' THEN qty_change ELSE 0 END) as entradas,
            SUM(CASE WHEN type='out' THEN ABS(qty_change) ELSE 0 END) as salidas
     FROM stock_movements
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY dia ORDER BY dia ASC");
$mov_dias=[]; $mov_entradas=[]; $mov_salidas=[];
while($r=mysqli_fetch_assoc($mov_q)){$mov_dias[]=$r['dia'];$mov_entradas[]=(int)$r['entradas'];$mov_salidas[]=(int)$r['salidas'];}

// Totales
$totals = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as total_prods,
            SUM(COALESCE(stock_qty,0)*productPrice) as valor_total,
            SUM(CASE WHEN productAvailability='In Stock' THEN 1 ELSE 0 END) as in_stock,
            SUM(CASE WHEN productAvailability='Out of Stock' THEN 1 ELSE 0 END) as out_stock,
            SUM(CASE WHEN stock_qty IS NOT NULL AND stock_qty<=5 AND stock_qty>0 THEN 1 ELSE 0 END) as criticos
     FROM products")) ?: [];

// Productos sin movimiento en 60 días (posible obsoleto)
$sin_mov_q = mysqli_query($con,
    "SELECT p.id, p.productName, p.stock_qty, p.productPrice, MAX(sm.created_at) as ultimo_mov
     FROM products p LEFT JOIN stock_movements sm ON sm.product_id=p.id
     WHERE p.stock_qty > 0 OR p.stock_qty IS NULL
     GROUP BY p.id HAVING ultimo_mov IS NULL OR ultimo_mov < DATE_SUB(NOW(), INTERVAL 60 DAY)
     ORDER BY p.stock_qty DESC LIMIT 10");
$sin_mov=[];
while($r=mysqli_fetch_assoc($sin_mov_q)) $sin_mov[]=$r;
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $_ADMIN_SITE_NAME;?> | Dashboard Inventario</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
<link rel="stylesheet" href="css/theme.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<style>
.inv-card{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px 20px;margin-bottom:16px;box-shadow:0 2px 6px rgba(0,0,0,.04)}
.inv-card h4{margin:0 0 14px;font-size:.92em;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:8px}
.stat-kpi{text-align:center;flex:1;min-width:100px;padding:14px;background:#fff;border:1px solid #e0e0e0;border-radius:8px}
.stat-kpi .n{font-size:1.6em;font-weight:700}.stat-kpi .l{font-size:.73em;color:#888;margin-top:2px}
</style>
</head><body>
<?php include('include/header.php');?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php');?>
<div class="span9"><div class="content"><div class="module">
<div class="module-head" style="background:linear-gradient(135deg,#1a5276,#2980b9);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em"><i class="icon-bar-chart"></i> Dashboard de Inventario
        <span style="float:right;display:flex;gap:6px">
            <a href="export.php?type=inventory" class="btn btn-xs btn-default"><i class="icon-download"></i> Inventario CSV</a>
            <a href="inventory-adjust.php" class="btn btn-xs btn-success"><i class="icon-inbox"></i> Ajustar</a>
        </span>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<!-- KPIs -->
<div style="display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap">
    <div class="stat-kpi"><div class="n" style="color:#337ab7"><?php echo $totals['total_prods']??0;?></div><div class="l">Productos</div></div>
    <div class="stat-kpi"><div class="n" style="color:#27ae60">$<?php echo number_format($totals['valor_total']??0,0,'.',',');?></div><div class="l">Valor inventario</div></div>
    <div class="stat-kpi"><div class="n" style="color:#27ae60"><?php echo $totals['in_stock']??0;?></div><div class="l">En stock</div></div>
    <div class="stat-kpi"><div class="n" style="color:#e8233a"><?php echo $totals['out_stock']??0;?></div><div class="l">Agotados</div></div>
    <div class="stat-kpi"><div class="n" style="color:#f39c12"><?php echo $totals['criticos']??0;?></div><div class="l">Stock crítico (≤5)</div></div>
</div>

<div class="row-fluid">
<div class="span6">
<!-- Gráfica valor por categoría -->
<div class="inv-card">
    <h4><i class="icon-pie-chart" style="color:#8e44ad"></i> Valor en inventario por categoría</h4>
    <canvas id="catChart" height="220"></canvas>
</div>
</div>
<div class="span6">
<!-- Rotación top 10 -->
<div class="inv-card">
    <h4><i class="icon-fire" style="color:#e8233a"></i> Top 10 más vendidos (30 días)</h4>
    <canvas id="rotChart" height="220"></canvas>
</div>
</div>
</div>

<!-- Movimientos de stock -->
<div class="inv-card">
    <h4><i class="icon-exchange" style="color:#337ab7"></i> Entradas vs Salidas de stock (30 días)</h4>
    <canvas id="movChart" height="100"></canvas>
</div>

<!-- Productos sin movimiento -->
<?php if(!empty($sin_mov)):?>
<div class="inv-card" style="border-color:#f39c12">
    <h4 style="color:#f39c12"><i class="icon-warning-sign"></i> Posibles obsoletos — sin movimiento +60 días</h4>
    <table class="table table-bordered" style="font-size:.82em;margin-bottom:0">
    <thead><tr style="background:#fff8e6"><th>Producto</th><th>Stock</th><th>Valor</th><th>Último mov.</th></tr></thead>
    <tbody>
    <?php foreach($sin_mov as $sm):?>
    <tr>
        <td><?php echo htmlspecialchars($sm['productName']);?></td>
        <td><?php echo $sm['stock_qty']??'—';?></td>
        <td>$<?php echo number_format(($sm['stock_qty']??0)*$sm['productPrice'],0,'.',',');?></td>
        <td style="color:#e8233a"><?php echo $sm['ultimo_mov']?substr($sm['ultimo_mov'],0,10):'Nunca';?></td>
    </tr>
    <?php endforeach;?>
    </tbody></table>
</div>
<?php endif;?>

</div></div></div></div></div></div>
<?php include('include/footer.php');?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
var catLabels = <?php echo json_encode($cat_labels);?>;
var catValues = <?php echo json_encode($cat_values);?>;
var rotNames  = <?php echo json_encode($rot_names);?>;
var rotSales  = <?php echo json_encode($rot_sales);?>;
var rotStock  = <?php echo json_encode($rot_stock);?>;
var movDias   = <?php echo json_encode($mov_dias);?>;
var movEnt    = <?php echo json_encode($mov_entradas);?>;
var movSal    = <?php echo json_encode($mov_salidas);?>;

var COLORS=['#8e44ad','#337ab7','#27ae60','#f39c12','#e8233a','#2980b9','#16a085','#d35400','#c0392b','#7f8c8d'];

new Chart(document.getElementById('catChart'),{type:'doughnut',data:{labels:catLabels,datasets:[{data:catValues,backgroundColor:COLORS,borderWidth:2}]},options:{plugins:{legend:{position:'right',labels:{font:{size:10},boxWidth:12}}},responsive:true}});

new Chart(document.getElementById('rotChart'),{type:'bar',data:{labels:rotNames,datasets:[{label:'Vendidos',data:rotSales,backgroundColor:'#e8233a'},{label:'Stock actual',data:rotStock,backgroundColor:'#337ab7'}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{labels:{font:{size:10}}}},scales:{x:{ticks:{font:{size:9}}}}}});

if(movDias.length){
new Chart(document.getElementById('movChart'),{type:'line',data:{labels:movDias,datasets:[{label:'Entradas',data:movEnt,borderColor:'#27ae60',backgroundColor:'rgba(39,174,96,.1)',fill:true,tension:.3},{label:'Salidas',data:movSal,borderColor:'#e8233a',backgroundColor:'rgba(232,35,58,.1)',fill:true,tension:.3}]},options:{responsive:true,plugins:{legend:{labels:{font:{size:11}}}},scales:{y:{beginAtZero:true}}}});
}
</script>
</body></html>
