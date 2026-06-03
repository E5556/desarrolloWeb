<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_stats');

$f_from = $_GET['from'] ?? date('Y-m-01');
$f_to   = $_GET['to']   ?? date('Y-m-d');
$from_e = date('Y-m-d', strtotime($f_from));
$to_e   = date('Y-m-d', strtotime($f_to));

// Rendimiento por asesor
$perf_q = mysqli_query($con,
    "SELECT a.id, a.username, a.role,
            COUNT(DISTINCT o.group_ref) as pedidos_creados,
            COUNT(DISTINCT CASE WHEN o.orderStatus='Confirmada' THEN o.group_ref END) as confirmados,
            COUNT(DISTINCT CASE WHEN o.orderStatus='Entregada' THEN o.group_ref END) as entregados,
            COUNT(DISTINCT CASE WHEN o.orderStatus='Borrador' THEN o.group_ref END) as borradores,
            SUM(o.quantity * p.productPrice) as valor_total,
            SUM(CASE WHEN o.orderStatus='Entregada' THEN o.quantity*p.productPrice ELSE 0 END) as valor_entregado
     FROM admin a
     LEFT JOIN orders o ON o.created_by=a.id AND DATE(o.orderDate) BETWEEN '$from_e' AND '$to_e'
     LEFT JOIN products p ON p.id=o.productId
     WHERE a.role IN ('asesor','editor','super')
     GROUP BY a.id ORDER BY pedidos_creados DESC");
$perf=[]; while($r=mysqli_fetch_assoc($perf_q)) $perf[]=$r;

// Exportar CSV
if(isset($_GET['export'])){
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rendimiento_asesores_'.$from_e.'_al_'.$to_e.'.csv"');
    echo "\xEF\xBB\xBF";
    $out=fopen('php://output','w');
    fputcsv($out,['Asesor','Rol','Pedidos creados','Confirmados','Entregados','Borradores','Valor total','Valor entregado','% conversión']);
    foreach($perf as $p){
        $conv = $p['pedidos_creados']>0?round($p['entregados']/$p['pedidos_creados']*100,1):0;
        fputcsv($out,[$p['username'],$p['role'],$p['pedidos_creados'],$p['confirmados'],$p['entregados'],$p['borradores'],$p['valor_total']??0,$p['valor_entregado']??0,$conv.'%']);
    }
    fclose($out); exit();
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $_ADMIN_SITE_NAME;?> | Rendimiento Asesores</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
<link rel="stylesheet" href="css/theme.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
</head><body>
<?php include('include/header.php');?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php');?>
<div class="span9"><div class="content"><div class="module">
<div class="module-head" style="background:linear-gradient(135deg,#27ae60,#1a6b3c);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em"><i class="icon-user"></i> Rendimiento de Asesores</h3>
</div>
<div class="module-body" style="padding:18px">

<form method="get" style="background:#f8f9fa;border:1px solid #e0e0e0;border-radius:8px;padding:12px 16px;margin-bottom:16px">
<div class="row-fluid" style="margin:0">
    <div class="span3"><label style="font-size:.8em">Desde</label><input type="date" name="from" class="input-block-level" value="<?php echo $from_e;?>"></div>
    <div class="span3"><label style="font-size:.8em">Hasta</label><input type="date" name="to" class="input-block-level" value="<?php echo $to_e;?>"></div>
    <div class="span3" style="padding-top:18px">
        <button type="submit" class="btn btn-primary btn-small">Filtrar</button>
        <a href="asesor-report.php?from=<?php echo $from_e;?>&to=<?php echo $to_e;?>&export=1" class="btn btn-success btn-small"><i class="icon-download"></i> CSV</a>
    </div>
</div>
</form>

<div style="overflow-x:auto">
<table class="table table-bordered table-striped" style="font-size:.85em">
<thead style="background:#2c3e50;color:#fff"><tr>
    <th>Asesor</th><th>Rol</th><th>Creados</th><th>Confirmados</th><th>Entregados</th><th>Borradores</th><th>Valor total</th><th>Valor entregado</th><th>% Conversión</th>
</tr></thead>
<tbody>
<?php foreach($perf as $p):
    $conv = $p['pedidos_creados']>0?round($p['entregados']/$p['pedidos_creados']*100,1):0;
    $conv_color = $conv>=70?'#27ae60':($conv>=40?'#f39c12':'#e8233a');
?>
<tr>
    <td><strong><?php echo htmlspecialchars($p['username']);?></strong></td>
    <td><span style="background:#e8f0fe;color:#1a56db;padding:2px 7px;border-radius:8px;font-size:.78em;font-weight:700"><?php echo $p['role'];?></span></td>
    <td style="text-align:center"><strong><?php echo $p['pedidos_creados'];?></strong></td>
    <td style="text-align:center;color:#337ab7"><?php echo $p['confirmados'];?></td>
    <td style="text-align:center;color:#27ae60"><?php echo $p['entregados'];?></td>
    <td style="text-align:center;color:#f39c12"><?php echo $p['borradores'];?></td>
    <td style="color:#8e44ad;font-weight:700">$<?php echo number_format($p['valor_total']??0,0,'.',',');?></td>
    <td style="color:#27ae60;font-weight:700">$<?php echo number_format($p['valor_entregado']??0,0,'.',',');?></td>
    <td><strong style="color:<?php echo $conv_color;?>"><?php echo $conv;?>%</strong></td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</div></div></div></div></div></div>
<?php include('include/footer.php');?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body></html>
