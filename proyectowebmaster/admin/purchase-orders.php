<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_orders');

mysqli_query($con,"CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_ref VARCHAR(25) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    status ENUM('Pendiente','Enviada al proveedor','Recibida parcial','Recibida completa','Cancelada') DEFAULT 'Pendiente',
    notes TEXT DEFAULT '',
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con,"CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_ordered INT NOT NULL DEFAULT 1,
    quantity_received INT NOT NULL DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    order_ref VARCHAR(40) DEFAULT '',
    INDEX(po_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg=''; $mtyp='';

// Generar OC automática desde pedidos Confirmada con artículos de proveedor
if (isset($_GET['generate'])) {
    $group_ref = mysqli_real_escape_string($con, $_GET['ref']??'');
    // Agrupar artículos del pedido por proveedor
    $items_q = mysqli_query($con,
        "SELECT oi.supplier_id, oi.product_id, SUM(oi.quantity) as qty, oi.unit_price, s.name as sup_name, o.group_ref
         FROM order_items oi
         JOIN orders o ON o.id=oi.order_id
         JOIN suppliers s ON s.id=oi.supplier_id
         WHERE o.group_ref='$group_ref' AND oi.supplier_id IS NOT NULL
         GROUP BY oi.supplier_id, oi.product_id");
    $by_sup = [];
    while($r=mysqli_fetch_assoc($items_q)) $by_sup[$r['supplier_id']][]=$r;

    $created=0;
    foreach($by_sup as $sup_id=>$items){
        $po_ref='OC-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-4));
        $admin_id=intval($_SESSION['aid']??0);
        mysqli_query($con,"INSERT INTO purchase_orders(po_ref,supplier_id,created_by) VALUES('$po_ref',$sup_id,$admin_id)");
        $po_id=mysqli_insert_id($con);
        $s=mysqli_prepare($con,"INSERT INTO purchase_order_items(po_id,product_id,quantity_ordered,unit_cost,order_ref) VALUES(?,?,?,?,?)");
        foreach($items as $item){
            $pid=intval($item['product_id']); $qty=intval($item['qty']); $cost=floatval($item['unit_price']);
            $oref=mysqli_real_escape_string($con,$group_ref);
            mysqli_stmt_bind_param($s,'iidss',$po_id,$pid,$qty,$cost,$oref);
            mysqli_stmt_execute($s);
        }
        mysqli_stmt_close($s); $created++;
    }
    $msg=$created>0?"✅ $created orden(es) de compra generadas desde pedido $group_ref":"⚠ No se encontraron artículos con proveedor asignado en ese pedido.";
    $mtyp=$created>0?'success':'warning';
}

// Cambiar estado OC
if(isset($_GET['st_change'])){
    $poid=intval($_GET['poid']); $new_st=$_GET['st_change'];
    $allowed=['Pendiente','Enviada al proveedor','Recibida parcial','Recibida completa','Cancelada'];
    if(in_array($new_st,$allowed)){
        mysqli_query($con,"UPDATE purchase_orders SET status='".mysqli_real_escape_string($con,$new_st)."' WHERE id=$poid");
        // Si recibida completa → aumentar stock
        if($new_st==='Recibida completa'){
            $pitems=mysqli_query($con,"SELECT * FROM purchase_order_items WHERE po_id=$poid");
            $admin_user=mysqli_real_escape_string($con,$_SESSION['alogin']??'');
            while($pi=mysqli_fetch_assoc($pitems)){
                $ppid=intval($pi['product_id']); $pqty=intval($pi['quantity_ordered']);
                mysqli_query($con,"UPDATE products SET stock_qty=COALESCE(stock_qty,0)+$pqty, productAvailability='In Stock' WHERE id=$ppid");
                $qa=mysqli_fetch_assoc(mysqli_query($con,"SELECT stock_qty FROM products WHERE id=$ppid LIMIT 1"));
                $qa_val=intval($qa['stock_qty']??0);
                mysqli_query($con,"INSERT INTO stock_movements(product_id,type,qty_change,qty_after,reason,admin_user) VALUES($ppid,'in',$pqty,$qa_val,'OC recibida — po_id=$poid','$admin_user')");
            }
        }
    }
    header('location:purchase-orders.php'); exit();
}

// Listado de OC
$pos_q=mysqli_query($con,
    "SELECT po.*, s.name as supplier_name, a.username as created_by_name,
            COUNT(poi.id) as items, SUM(poi.quantity_ordered*poi.unit_cost) as total
     FROM purchase_orders po
     JOIN suppliers s ON s.id=po.supplier_id
     LEFT JOIN admin a ON a.id=po.created_by
     LEFT JOIN purchase_order_items poi ON poi.po_id=po.id
     GROUP BY po.id ORDER BY po.created_at DESC");
$pos=[]; while($r=mysqli_fetch_assoc($pos_q)) $pos[]=$r;

// Pedidos Confirmada con proveedores (para generar OC)
$conf_q=mysqli_query($con,
    "SELECT DISTINCT o.group_ref, MIN(o.orderDate) as fecha, u.name as cliente
     FROM orders o JOIN users u ON u.id=o.userId
     JOIN order_items oi ON oi.order_id=o.id
     WHERE o.orderStatus='Confirmada' AND oi.supplier_id IS NOT NULL
     GROUP BY o.group_ref ORDER BY MIN(o.orderDate) DESC LIMIT 20");
$conf_orders=[]; while($r=mysqli_fetch_assoc($conf_q)) $conf_orders[]=$r;

$st_colors=['Pendiente'=>'#f39c12','Enviada al proveedor'=>'#337ab7','Recibida parcial'=>'#8e44ad','Recibida completa'=>'#27ae60','Cancelada'=>'#aaa'];
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $_ADMIN_SITE_NAME;?> | Órdenes de Compra</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
<link rel="stylesheet" href="css/theme.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
<style>
.oc-card{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:18px 22px;margin-bottom:16px;box-shadow:0 2px 6px rgba(0,0,0,.04)}
.oc-card h4{margin:0 0 12px;font-size:.93em;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:8px}
.badge-st{display:inline-block;padding:2px 9px;border-radius:10px;font-size:.75em;font-weight:700;color:#fff}
</style>
</head><body>
<?php include('include/header.php');?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php');?>
<div class="span9"><div class="content"><div class="module">
<div class="module-head" style="background:linear-gradient(135deg,#2980b9,#1a5276);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em"><i class="icon-truck"></i> Órdenes de Compra a Proveedores</h3>
</div>
<div class="module-body" style="padding:18px">
<?php if($msg):?><div class="alert alert-<?php echo $mtyp;?>"><?php echo $msg;?></div><?php endif;?>

<?php if(!empty($conf_orders)):?>
<div class="oc-card" style="border-color:#27ae60;background:#f0fdf4">
    <h4 style="color:#27ae60"><i class="icon-bolt"></i> Pedidos confirmados listos para generar OC</h4>
    <table class="table table-bordered" style="font-size:.83em;margin-bottom:0">
    <thead><tr style="background:#f0f0f0"><th>Referencia</th><th>Cliente</th><th>Fecha</th><th>Acción</th></tr></thead>
    <tbody>
    <?php foreach($conf_orders as $co):?>
    <tr>
        <td><strong><?php echo htmlspecialchars($co['group_ref']);?></strong></td>
        <td><?php echo htmlspecialchars($co['cliente']);?></td>
        <td><?php echo substr($co['fecha'],0,10);?></td>
        <td><a href="purchase-orders.php?generate=1&ref=<?php echo urlencode($co['group_ref']);?>" class="btn btn-xs btn-success" onclick="return confirm('¿Generar órdenes de compra para este pedido?')"><i class="icon-plus"></i> Generar OC</a></td>
    </tr>
    <?php endforeach;?>
    </tbody></table>
</div>
<?php endif;?>

<div class="oc-card">
    <h4><i class="icon-list" style="color:#2980b9"></i> Órdenes de compra</h4>
    <?php if(empty($pos)):?>
    <p class="muted" style="font-style:italic">No hay órdenes de compra aún.</p>
    <?php else:?>
    <div style="overflow-x:auto">
    <table class="table table-bordered table-striped" style="font-size:.83em">
    <thead><tr style="background:#2c3e50;color:#fff">
        <th>Ref. OC</th><th>Proveedor</th><th>Items</th><th>Total</th><th>Estado</th><th>Creado por</th><th>Fecha</th><th>Acciones</th>
    </tr></thead>
    <tbody>
    <?php foreach($pos as $po):
        $sc=$st_colors[$po['status']]??'#aaa';
    ?>
    <tr>
        <td><strong><?php echo htmlspecialchars($po['po_ref']);?></strong></td>
        <td style="color:#2980b9;font-weight:600"><?php echo htmlspecialchars($po['supplier_name']);?></td>
        <td><?php echo $po['items'];?></td>
        <td style="color:#337ab7;font-weight:700">$<?php echo number_format($po['total']??0,0,'.',',');?></td>
        <td><span class="badge-st" style="background:<?php echo $sc;?>"><?php echo $po['status'];?></span></td>
        <td style="color:#888"><?php echo htmlspecialchars($po['created_by_name']??'—');?></td>
        <td style="color:#888;white-space:nowrap"><?php echo substr($po['created_at'],0,10);?></td>
        <td style="white-space:nowrap">
            <?php
            $next_st=['Pendiente'=>'Enviada al proveedor','Enviada al proveedor'=>'Recibida parcial','Recibida parcial'=>'Recibida completa'];
            if(isset($next_st[$po['status']])):
                $ns=$next_st[$po['status']];
                $btn_color=$ns==='Recibida completa'?'btn-success':'btn-primary';
                $confirm_msg=$ns==='Recibida completa'?'¿Marcar como recibida? Esto sumará el stock automáticamente.':'¿Cambiar estado?';
            ?>
            <a href="purchase-orders.php?st_change=<?php echo urlencode($ns);?>&poid=<?php echo $po['id'];?>"
               class="btn btn-xs <?php echo $btn_color;?>"
               onclick="return confirm('<?php echo $confirm_msg;?>')">→ <?php echo $ns;?></a>
            <?php endif;?>
            <?php if(!in_array($po['status'],['Recibida completa','Cancelada'])):?>
            <a href="purchase-orders.php?st_change=Cancelada&poid=<?php echo $po['id'];?>"
               class="btn btn-xs btn-danger" onclick="return confirm('¿Cancelar esta OC?')">✕</a>
            <?php endif;?>
        </td>
    </tr>
    <?php endforeach;?>
    </tbody></table></div>
    <?php endif;?>
</div>
</div></div></div></div></div></div>
<?php include('include/footer.php');?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body></html>
