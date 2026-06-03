<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
$my_role = $_SESSION['arole'] ?? 'super';
if (!in_array($my_role, ['super','editor','asesor'])) { header('location:index.php'); exit(); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_ref VARCHAR(20) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    admin_id INT NOT NULL,
    status ENUM('Borrador','Enviada','Aceptada','Rechazada','Vencida') DEFAULT 'Borrador',
    notes TEXT DEFAULT '',
    valid_days INT DEFAULT 7,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(client_id), INDEX(admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS quotation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    supplier_id INT DEFAULT NULL,
    INDEX(quotation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = ''; $mtyp = '';

// AJAX búsqueda clientes/productos
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] === 'clients') {
        $q = '%'.mysqli_real_escape_string($con,$_GET['q']??'').'%';
        $rs = mysqli_query($con,"SELECT id,name,email FROM users WHERE name LIKE '$q' OR email LIKE '$q' ORDER BY name LIMIT 20");
        $out=[]; while($r=mysqli_fetch_assoc($rs)) $out[]=$r; echo json_encode($out); exit();
    }
    if ($_GET['ajax'] === 'products') {
        $q = '%'.mysqli_real_escape_string($con,$_GET['q']??'').'%';
        $rs = mysqli_query($con,"SELECT p.id,p.productName,p.productPrice,COALESCE(s.name,'—') as supplier_name,p.supplier_id FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id WHERE p.productName LIKE '$q' ORDER BY p.productName LIMIT 30");
        $out=[]; while($r=mysqli_fetch_assoc($rs)) $out[]=$r; echo json_encode($out); exit();
    }
}

// Cambiar estado
if (isset($_GET['status_change'])) {
    $qid = intval($_GET['qid']); $new_st = $_GET['status_change'];
    $allowed = ['Borrador','Enviada','Aceptada','Rechazada','Vencida'];
    if (in_array($new_st,$allowed)) mysqli_query($con,"UPDATE quotations SET status='".mysqli_real_escape_string($con,$new_st)."' WHERE id=$qid");
    // Si aceptada → crear pedido automáticamente
    if ($new_st === 'Aceptada') {
        $qt = mysqli_query($con,"SELECT * FROM quotations WHERE id=$qid LIMIT 1");
        $qrow = mysqli_fetch_assoc($qt);
        $qi = mysqli_query($con,"SELECT * FROM quotation_items WHERE quotation_id=$qid");
        $gref = 'QUO-'.$qid.'-'.time();
        $admin_id = intval($_SESSION['aid']??0);
        $stmt = mysqli_prepare($con,"INSERT INTO orders(userId,productId,quantity,paymentMethod,orderStatus,created_by,group_ref) VALUES(?,?,?,'Por definir','Confirmada',?,?)");
        while($item=mysqli_fetch_assoc($qi)){
            $pid_i=intval($item['product_id']); $qty_i=intval($item['quantity']);
            mysqli_stmt_bind_param($stmt,'iisisi',$qrow['client_id'],$pid_i,$qty_i,$admin_id,$gref);
            mysqli_stmt_execute($stmt);
            $oid=mysqli_insert_id($con);
            $s2=mysqli_prepare($con,"INSERT INTO order_items(order_id,product_id,quantity,supplier_id,unit_price) VALUES(?,?,?,?,?)");
            mysqli_stmt_bind_param($s2,'iiiid',$oid,$pid_i,$qty_i,$item['supplier_id'],$item['unit_price']);
            mysqli_stmt_execute($s2); mysqli_stmt_close($s2);
        }
        mysqli_stmt_close($stmt);
        $msg="✅ Cotización aceptada — pedido creado con referencia <strong>$gref</strong>"; $mtyp='success';
    }
    header("location:quotations.php?msg=".urlencode($msg)."&mtyp=$mtyp"); exit();
}

if (isset($_GET['msg'])) { $msg=urldecode($_GET['msg']); $mtyp=$_GET['mtyp']??'info'; }

// Crear cotización
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_quote'])) {
    $cid = intval($_POST['client_id']??0);
    $notes = mysqli_real_escape_string($con, substr($_POST['notes']??'',0,1000));
    $valid = max(1, intval($_POST['valid_days']??7));
    $admin_id = intval($_SESSION['aid']??0);
    $pids = $_POST['product_id']??[]; $qtys=$_POST['quantity']??[]; $prices=$_POST['unit_price']??[]; $sups=$_POST['supplier_id']??[];
    if ($cid>0 && !empty($pids)) {
        $qref = 'COT-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-4));
        mysqli_query($con,"INSERT INTO quotations(quote_ref,client_id,admin_id,notes,valid_days) VALUES('$qref',$cid,$admin_id,'$notes',$valid)");
        $qid = mysqli_insert_id($con);
        $s=mysqli_prepare($con,"INSERT INTO quotation_items(quotation_id,product_id,quantity,unit_price,supplier_id) VALUES(?,?,?,?,?)");
        foreach($pids as $i=>$pid_i){
            $pid_i=intval($pid_i); $qty_i=max(1,intval($qtys[$i]??1));
            $price_i=floatval($prices[$i]??0); $sup_i=intval($sups[$i]??0)?:null;
            if($pid_i<=0) continue;
            mysqli_stmt_bind_param($s,'iiddi',$qid,$pid_i,$qty_i,$price_i,$sup_i);
            mysqli_stmt_execute($s);
        }
        mysqli_stmt_close($s);
        $msg="✅ Cotización <strong>$qref</strong> creada. <a href='quote-pdf.php?id=$qid'>Ver PDF</a>"; $mtyp='success';
    } else { $msg='Selecciona cliente y al menos un producto.'; $mtyp='danger'; }
}

// Listar cotizaciones (del asesor si es asesor, todas si super/editor)
$admin_id = intval($_SESSION['aid']??0);
$where_q = ($my_role==='asesor') ? "WHERE q.admin_id=$admin_id" : '';
$quotes_q = mysqli_query($con,"SELECT q.*,u.name as client_name,u.email as client_email,a.username as asesor_name,
    COUNT(qi.id) as items, SUM(qi.quantity*qi.unit_price) as total
    FROM quotations q JOIN users u ON u.id=q.client_id JOIN admin a ON a.id=q.admin_id
    LEFT JOIN quotation_items qi ON qi.quotation_id=q.id
    $where_q GROUP BY q.id ORDER BY q.created_at DESC");
$quotes=[]; while($r=mysqli_fetch_assoc($quotes_q)) $quotes[]=$r;

$sup_q=mysqli_query($con,"SELECT id,name FROM suppliers WHERE active=1 ORDER BY name");
$sup_arr=[]; while($s=mysqli_fetch_assoc($sup_q)) $sup_arr[]=$s;

$status_colors=['Borrador'=>'#f39c12','Enviada'=>'#337ab7','Aceptada'=>'#27ae60','Rechazada'=>'#e8233a','Vencida'=>'#aaa'];
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $_ADMIN_SITE_NAME;?> | Cotizaciones</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
<link rel="stylesheet" href="css/theme.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
<style>
.q-card{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:18px 22px;margin-bottom:16px;box-shadow:0 2px 6px rgba(0,0,0,.04)}
.q-card h4{margin:0 0 12px;font-size:.93em;font-weight:700;border-bottom:1px solid #f0f0f0;padding-bottom:8px}
.badge-st{display:inline-block;padding:2px 9px;border-radius:10px;font-size:.75em;font-weight:700;color:#fff}
#prod-results,#cli-results{border:1px solid #ddd;border-radius:0 0 6px 6px;max-height:220px;overflow-y:auto;background:#fff;display:none;position:absolute;z-index:100;width:100%}
.drop-item{padding:8px 12px;cursor:pointer;border-bottom:1px solid #f5f5f5;font-size:.85em}.drop-item:hover{background:#f0f7ff}
.item-row{background:#f8f9fa;border:1px solid #e0e0e0;border-radius:6px;padding:10px 14px;margin-bottom:8px;position:relative}
.btn-del-r{position:absolute;top:8px;right:8px;background:#e8233a;color:#fff;border:none;border-radius:4px;padding:2px 7px;font-size:.75em;cursor:pointer}
.search-wrap{position:relative}
</style>
</head><body>
<?php include('include/header.php');?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php');?>
<div class="span9"><div class="content"><div class="module">
<div class="module-head" style="background:linear-gradient(135deg,#8e44ad,#6c3483);padding:14px 18px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1em"><i class="icon-file-text"></i> Cotizaciones</h3>
</div>
<div class="module-body" style="padding:18px">
<?php if($msg):?><div class="alert alert-<?php echo $mtyp;?>"><?php echo $msg;?></div><?php endif;?>

<!-- Formulario nueva cotización -->
<div class="q-card">
<h4><i class="icon-plus" style="color:#8e44ad"></i> Nueva cotización</h4>
<form method="post" id="qform">
<input type="hidden" name="save_quote" value="1">
<input type="hidden" name="client_id" id="client_id" value="">
<div class="row-fluid" style="margin-bottom:10px">
    <div class="span5">
        <label style="font-size:.82em">Cliente</label>
        <div class="search-wrap">
            <input type="text" id="cli-search" class="input-block-level" placeholder="Buscar cliente...">
            <div id="cli-results"></div>
        </div>
        <div id="cli-selected" style="display:none;margin-top:5px;background:#e8f8f0;border:1px solid #a9dfbf;border-radius:5px;padding:6px 10px;font-size:.83em">
            <strong id="cli-name"></strong> <span id="cli-email" style="color:#888"></span>
            <a href="#" id="cli-clear" style="margin-left:8px;color:#e8233a;font-size:.82em">Cambiar</a>
        </div>
    </div>
    <div class="span2">
        <label style="font-size:.82em">Válida (días)</label>
        <input type="number" name="valid_days" value="7" min="1" max="90" class="input-block-level">
    </div>
    <div class="span5">
        <label style="font-size:.82em">Notas / condiciones</label>
        <input type="text" name="notes" class="input-block-level" placeholder="Notas opcionales...">
    </div>
</div>

<label style="font-size:.82em">Agregar productos</label>
<div class="search-wrap" style="margin-bottom:10px">
    <input type="text" id="prod-search" class="input-block-level" placeholder="Buscar producto...">
    <div id="prod-results"></div>
</div>
<div id="items-container"><p class="muted" id="no-items" style="font-size:.85em;font-style:italic">Agrega productos usando el buscador.</p></div>
<div style="margin-top:10px">
    <button type="submit" class="btn btn-success" id="btn-save" disabled><i class="icon-ok"></i> Crear cotización</button>
</div>
</form>
</div>

<!-- Lista de cotizaciones -->
<div class="q-card">
<h4><i class="icon-list" style="color:#337ab7"></i> Cotizaciones <?php echo $my_role==='asesor'?'(mis cotizaciones)':'(todas)';?></h4>
<?php if(empty($quotes)):?>
<p class="muted" style="font-style:italic">No hay cotizaciones aún.</p>
<?php else:?>
<div style="overflow-x:auto">
<table class="table table-bordered table-striped" style="font-size:.83em">
<thead><tr style="background:#f0f0f0">
    <th>Ref.</th><th>Cliente</th><th>Asesor</th><th>Items</th><th>Total</th><th>Estado</th><th>Válida hasta</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php foreach($quotes as $q):
    $valid_until = date('Y-m-d', strtotime($q['created_at'].' +'.$q['valid_days'].' days'));
    $expired = ($q['status']==='Enviada' && $valid_until < date('Y-m-d'));
    $sc = $status_colors[$q['status']]??'#aaa';
?>
<tr>
    <td><strong><?php echo htmlspecialchars($q['quote_ref']);?></strong></td>
    <td><?php echo htmlspecialchars($q['client_name']);?><br><small style="color:#888"><?php echo htmlspecialchars($q['client_email']);?></small></td>
    <td style="color:#8e44ad"><?php echo htmlspecialchars($q['asesor_name']);?></td>
    <td><?php echo $q['items'];?></td>
    <td style="font-weight:700;color:#337ab7">$<?php echo number_format($q['total']??0,0,'.',',');?></td>
    <td><span class="badge-st" style="background:<?php echo $sc;?>"><?php echo $q['status'];?></span>
        <?php if($expired):?><br><small style="color:#e8233a">⚠ Vencida</small><?php endif;?></td>
    <td style="color:<?php echo $expired?'#e8233a':'#555';?>"><?php echo $valid_until;?></td>
    <td style="white-space:nowrap">
        <a href="quote-pdf.php?id=<?php echo $q['id'];?>" class="btn btn-xs btn-default" target="_blank"><i class="icon-print"></i> PDF</a>
        <?php if($q['status']==='Borrador'):?>
        <a href="quotations.php?status_change=Enviada&qid=<?php echo $q['id'];?>" class="btn btn-xs btn-primary">Enviar</a>
        <?php endif;?>
        <?php if($q['status']==='Enviada'):?>
        <a href="quotations.php?status_change=Aceptada&qid=<?php echo $q['id'];?>" class="btn btn-xs btn-success" onclick="return confirm('¿Crear pedido desde esta cotización?')">✓ Aceptar</a>
        <a href="quotations.php?status_change=Rechazada&qid=<?php echo $q['id'];?>" class="btn btn-xs btn-danger">✗ Rechazar</a>
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
<script>
var SUP = <?php echo json_encode($sup_arr);?>;
var itemCount = 0;

// Clientes
var cliT;
$('#cli-search').on('input',function(){
    clearTimeout(cliT); var q=$(this).val().trim();
    if(q.length<2){$('#cli-results').hide();return;}
    cliT=setTimeout(function(){
        $.getJSON('quotations.php?ajax=clients&q='+encodeURIComponent(q),function(d){
            var h=''; if(!d.length) h='<div class="drop-item" style="color:#aaa">Sin resultados</div>';
            d.forEach(function(c){h+='<div class="drop-item" data-id="'+c.id+'" data-name="'+escH(c.name)+'" data-email="'+escH(c.email)+'"><strong>'+escH(c.name)+'</strong> <span style="color:#888">'+escH(c.email)+'</span></div>';});
            $('#cli-results').html(h).show();
        });
    },300);
});
$(document).on('click','.drop-item[data-id]',function(){
    if($(this).closest('#cli-results').length){
        $('#client_id').val($(this).data('id')); $('#cli-name').text($(this).data('name')); $('#cli-email').text($(this).data('email'));
        $('#cli-selected').show(); $('#cli-search').hide(); $('#cli-results').hide();
    }
});
$('#cli-clear').on('click',function(e){e.preventDefault();$('#client_id').val('');$('#cli-selected').hide();$('#cli-search').val('').show().focus();});

// Productos
var pT;
$('#prod-search').on('input',function(){
    clearTimeout(pT); var q=$(this).val().trim();
    if(q.length<2){$('#prod-results').hide();return;}
    pT=setTimeout(function(){
        $.getJSON('quotations.php?ajax=products&q='+encodeURIComponent(q),function(d){
            var h=''; if(!d.length) h='<div class="drop-item" style="color:#aaa">Sin resultados</div>';
            d.forEach(function(p){h+='<div class="drop-item prod-r" data-id="'+p.id+'" data-name="'+escH(p.productName)+'" data-price="'+p.productPrice+'" data-sup="'+p.supplier_id+'"><strong>'+escH(p.productName)+'</strong> <span style="color:#337ab7">$'+numF(p.productPrice)+'</span></div>';});
            $('#prod-results').html(h).show();
        });
    },300);
});
$(document).on('click','.prod-r',function(){
    addItem($(this).data('id'),$(this).data('name'),parseFloat($(this).data('price')),$(this).data('sup'));
    $('#prod-search').val(''); $('#prod-results').hide();
});
function addItem(pid,name,price,defSup){
    itemCount++; var idx=itemCount; $('#no-items').hide(); $('#btn-save').prop('disabled',false);
    var opts='<option value="">— Sin proveedor —</option>';
    SUP.forEach(function(s){opts+='<option value="'+s.id+'"'+(parseInt(s.id)===parseInt(defSup)?' selected':'')+'>'+escH(s.name)+'</option>';});
    var row='<div class="item-row" id="ir-'+idx+'">'
        +'<button type="button" class="btn-del-r" onclick="remItem('+idx+')">✕</button>'
        +'<input type="hidden" name="product_id[]" value="'+pid+'">'
        +'<strong style="font-size:.88em">'+escH(name)+'</strong>'
        +'<div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap">'
        +'<div><label style="font-size:.78em">Cant.</label><input type="number" name="quantity[]" value="1" min="1" style="width:65px"></div>'
        +'<div><label style="font-size:.78em">Precio unit.</label><input type="number" name="unit_price[]" value="'+price+'" min="0" step="100" style="width:110px"></div>'
        +'<div style="flex:2;min-width:160px"><label style="font-size:.78em">Proveedor</label><select name="supplier_id[]" class="input-block-level" style="max-width:220px">'+opts+'</select></div>'
        +'</div></div>';
    $('#items-container').append(row);
}
function remItem(idx){$('#ir-'+idx).remove();if(!$('.item-row').length){$('#no-items').show();$('#btn-save').prop('disabled',true);}}
$(document).on('click',function(e){if(!$(e.target).closest('.search-wrap').length){$('#prod-results,#cli-results').hide();}});
function numF(n){return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
function escH(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
</script>
</body></html>
