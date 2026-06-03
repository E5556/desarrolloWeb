<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
$my_role = $_SESSION['arole'] ?? 'super';
if (!in_array($my_role, ['super','editor','asesor'])) { header('location:index.php'); exit(); }

$admin_id = intval($_SESSION['aid'] ?? 0);
// Asesores ven solo los suyos; super/editor ven todos con filtro opcional
$f_asesor = ($my_role === 'asesor') ? $admin_id : intval($_GET['asesor'] ?? 0);
$f_status = mysqli_real_escape_string($con, $_GET['status'] ?? '');
$f_from   = $_GET['from'] ?? '';
$f_to     = $_GET['to']   ?? '';

$where = ['o.created_by IS NOT NULL'];
if ($f_asesor > 0) $where[] = "o.created_by = $f_asesor";
if ($f_status !== '') $where[] = "o.orderStatus = '$f_status'";
if ($f_from !== '')   $where[] = "DATE(o.orderDate) >= '".date('Y-m-d',strtotime($f_from))."'";
if ($f_to !== '')     $where[] = "DATE(o.orderDate) <= '".date('Y-m-d',strtotime($f_to))."'";
$where_sql = 'WHERE '.implode(' AND ',$where);

$orders_q = mysqli_query($con,
    "SELECT o.group_ref,
            MIN(o.orderDate) as orderDate,
            o.orderStatus,
            u.name as client_name, u.email as client_email,
            a.username as asesor_name,
            COUNT(DISTINCT o.id) as num_items,
            SUM(o.quantity * p.productPrice) as total,
            o.paymentMethod,
            MIN(o.id) as first_oid
     FROM orders o
     JOIN users u ON u.id = o.userId
     JOIN products p ON p.id = o.productId
     JOIN admin a ON a.id = o.created_by
     $where_sql
     GROUP BY o.group_ref
     ORDER BY MIN(o.orderDate) DESC");

$orders = [];
while ($r = mysqli_fetch_assoc($orders_q)) $orders[] = $r;

// Stats del asesor
$stats_q = mysqli_query($con,
    "SELECT COUNT(DISTINCT o.group_ref) as total_pedidos,
            SUM(o.quantity * p.productPrice) as total_ventas,
            COUNT(DISTINCT CASE WHEN o.orderStatus='Entregada' THEN o.group_ref END) as entregados
     FROM orders o JOIN products p ON p.id=o.productId
     WHERE o.created_by = $admin_id AND o.created_by IS NOT NULL");
$stats = mysqli_fetch_assoc($stats_q) ?: [];

// Lista de asesores para filtro (solo super/editor)
$asesores_q = mysqli_query($con,"SELECT id,username FROM admin WHERE role='asesor' ORDER BY username");
$asesores=[]; while($r=mysqli_fetch_assoc($asesores_q)) $asesores[]=$r;

$status_colors=['Borrador'=>'#f39c12','Confirmada'=>'#337ab7','En gestión'=>'#8e44ad','Despachada'=>'#27ae60','Entregada'=>'#2c3e50','in Process'=>'#e67e22','Delivered'=>'#27ae60'];
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $_ADMIN_SITE_NAME;?> | Mis Pedidos</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
<link rel="stylesheet" href="css/theme.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
<style>
.stat-card{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:14px;text-align:center;flex:1;min-width:110px}
.stat-num{font-size:1.7em;font-weight:700}.stat-lbl{font-size:.75em;color:#888;margin-top:2px}
.badge-st{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.75em;font-weight:700;color:#fff}
.filter-bar{background:#f8f9fa;border:1px solid #e0e0e0;border-radius:8px;padding:12px 16px;margin-bottom:14px}
</style>
</head><body>
<?php include('include/header.php');?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php');?>
<div class="span9"><div class="content"><div class="module">
<div class="module-head" style="background:linear-gradient(135deg,#27ae60,#1e8449);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em">
        <i class="icon-shopping-cart"></i>
        <?php echo $my_role==='asesor'?'Mis Pedidos':'Pedidos por Asesor';?>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<!-- Stats -->
<div style="display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap">
    <div class="stat-card"><div class="stat-num" style="color:#337ab7"><?php echo $stats['total_pedidos']??0;?></div><div class="stat-lbl">Mis pedidos</div></div>
    <div class="stat-card"><div class="stat-num" style="color:#8e44ad">$<?php echo number_format($stats['total_ventas']??0,0,'.',',');?></div><div class="stat-lbl">Valor total</div></div>
    <div class="stat-card"><div class="stat-num" style="color:#27ae60"><?php echo $stats['entregados']??0;?></div><div class="stat-lbl">Entregados</div></div>
</div>

<!-- Filtros -->
<form method="get" class="filter-bar">
<div class="row-fluid" style="margin:0">
    <?php if($my_role!=='asesor' && !empty($asesores)):?>
    <div class="span3">
        <label style="font-size:.8em">Asesor</label>
        <select name="asesor" class="input-block-level">
            <option value="0">Todos</option>
            <?php foreach($asesores as $as):?>
            <option value="<?php echo $as['id'];?>" <?php echo $f_asesor==$as['id']?'selected':'';?>><?php echo htmlspecialchars($as['username']);?></option>
            <?php endforeach;?>
        </select>
    </div>
    <?php endif;?>
    <div class="span3">
        <label style="font-size:.8em">Estado</label>
        <select name="status" class="input-block-level">
            <option value="">Todos</option>
            <?php foreach(['Borrador','Confirmada','En gestión','Despachada','Entregada'] as $st):?>
            <option value="<?php echo $st;?>" <?php echo $f_status===$st?'selected':'';?>><?php echo $st;?></option>
            <?php endforeach;?>
        </select>
    </div>
    <div class="span2"><label style="font-size:.8em">Desde</label><input type="date" name="from" class="input-block-level" value="<?php echo htmlspecialchars($f_from);?>"></div>
    <div class="span2"><label style="font-size:.8em">Hasta</label><input type="date" name="to" class="input-block-level" value="<?php echo htmlspecialchars($f_to);?>"></div>
    <div class="span2" style="padding-top:18px">
        <button type="submit" class="btn btn-primary btn-small">Filtrar</button>
        <a href="my-orders.php" class="btn btn-default btn-small">Limpiar</a>
    </div>
</div>
</form>

<?php if(empty($orders)):?>
<div style="text-align:center;padding:30px;color:#aaa;font-style:italic">No hay pedidos con los filtros aplicados.</div>
<?php else:?>
<div style="overflow-x:auto">
<table class="table table-bordered table-striped" style="font-size:.84em">
<thead style="background:#2c3e50;color:#fff"><tr>
    <th>Referencia</th><th>Cliente</th><?php if($my_role!=='asesor'):?><th>Asesor</th><?php endif;?>
    <th>Items</th><th>Total</th><th>Pago</th><th>Estado</th><th>Fecha</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php foreach($orders as $o):
    $sc = $status_colors[$o['orderStatus']] ?? '#aaa';
?>
<tr>
    <td><strong style="font-size:.82em;color:#8e44ad"><?php echo htmlspecialchars($o['group_ref']);?></strong></td>
    <td><strong><?php echo htmlspecialchars($o['client_name']);?></strong><br><small style="color:#888"><?php echo htmlspecialchars($o['client_email']);?></small></td>
    <?php if($my_role!=='asesor'):?><td style="color:#27ae60"><?php echo htmlspecialchars($o['asesor_name']);?></td><?php endif;?>
    <td><?php echo $o['num_items'];?> art.</td>
    <td style="font-weight:700;color:#337ab7">$<?php echo number_format($o['total']??0,0,'.',',');?></td>
    <td><?php echo htmlspecialchars($o['paymentMethod']);?></td>
    <td><span class="badge-st" style="background:<?php echo $sc;?>"><?php echo $o['orderStatus'];?></span></td>
    <td style="color:#888;white-space:nowrap"><?php echo substr($o['orderDate'],0,10);?></td>
    <td style="white-space:nowrap">
        <?php if($o['orderStatus']==='Borrador' && !empty($o['group_ref'])):?>
        <a href="edit-order.php?ref=<?php echo urlencode($o['group_ref']);?>" class="btn btn-xs btn-warning"><i class="icon-pencil"></i> Editar</a>
        <?php else:?>
        <a href="edit-order.php?ref=<?php echo urlencode($o['group_ref']);?>" class="btn btn-xs btn-default"><i class="icon-eye-open"></i> Ver</a>
        <?php endif;?>
    </td>
</tr>
<?php endforeach;?>
</tbody></table></div>
<?php endif;?>
</div></div></div></div></div></div>
<?php include('include/footer.php');?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body></html>
