<?php
session_start();

include_once 'include/config.php';
if(empty($_SESSION['alogin']))
  {
header('location:index.php');
}
else{
$oid=intval($_GET['oid']);

// Auto-add tracking_url column if missing
mysqli_query($con, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_url VARCHAR(500) DEFAULT NULL");

if(isset($_POST['submit2'])){
$status=$_POST['status'];
$remark=$_POST['remark'];//space char
$tracking_url = trim($_POST['tracking_url'] ?? '');

$query=mysqli_query($con,"insert into ordertrackhistory(orderId,status,remark) values('$oid','$status','$remark')");
$sql=mysqli_query($con,"update orders set orderStatus='$status' where id='$oid'");
if ($tracking_url !== '') {
    $stmt_tu = mysqli_prepare($con, "UPDATE orders SET tracking_url=? WHERE id=?");
    mysqli_stmt_bind_param($stmt_tu, 'si', $tracking_url, $oid);
    mysqli_stmt_execute($stmt_tu);
    mysqli_stmt_close($stmt_tu);
}
include_once('../includes/admin-log.php');
admin_log($con, 'update_order', "Orden #$oid → $status");

// EE2 — Notificar al cliente por email al cambiar estado
include_once('../includes/mailer.php');
$ord_info = mysqli_fetch_assoc(mysqli_query($con,"SELECT o.*,u.name,u.email FROM orders o JOIN users u ON u.id=o.userId WHERE o.id=$oid LIMIT 1"));
if($ord_info && !empty($ord_info['email'])){
    $st_msgs=['Confirmada'=>'Tu pedido ha sido confirmado y está siendo procesado.','En gestión'=>'Tu pedido está siendo gestionado. Estamos preparando tus artículos.','Despachada'=>'¡Tu pedido está en camino! '.($tracking_url?'Sigue tu envío: '.$tracking_url:''),'Entregada'=>'Tu pedido ha sido entregado. ¡Gracias por tu compra!'];
    if(isset($st_msgs[$status])){
        $cfg_q=mysqli_query($con,"SELECT setting_value FROM settings WHERE setting_key='site_name' LIMIT 1");
        $site=mysqli_fetch_assoc($cfg_q)['setting_value']??'Tienda';
        $html='<html><body style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:20px">
        <div style="background:#e8233a;padding:16px;border-radius:6px 6px 0 0;text-align:center"><h2 style="color:#fff;margin:0">'.htmlspecialchars($site).'</h2></div>
        <div style="border:1px solid #eee;border-top:none;padding:24px;border-radius:0 0 6px 6px">
        <p>Hola <strong>'.htmlspecialchars($ord_info['name']).'</strong>,</p>
        <p style="color:#555">'.htmlspecialchars($st_msgs[$status]).'</p>
        <p style="margin-top:14px"><strong>Orden #:</strong> '.$oid.'<br><strong>Estado:</strong> <span style="color:#e8233a;font-weight:700">'.$status.'</span></p>
        '.($remark?'<p style="background:#f9f9f9;padding:10px;border-radius:4px;font-size:13px;color:#555">'.nl2br(htmlspecialchars($remark)).'</p>':'').'
        </div></body></html>';
        send_email_raw($ord_info['email'],$ord_info['name'],"[$site] Estado de tu pedido #$oid — $status",$html);
    }
}

// DD3 — Alertas de reabastecimiento: revisar stock bajo tras cada actualización
$low_thr_q = mysqli_query($con,"SELECT setting_value FROM settings WHERE setting_key='low_stock_threshold' LIMIT 1");
$low_thr   = intval(mysqli_fetch_assoc($low_thr_q)['setting_value'] ?? 5);
$low_q     = mysqli_query($con,"SELECT id,productName,stock_qty FROM products WHERE stock_qty IS NOT NULL AND stock_qty > 0 AND stock_qty <= $low_thr ORDER BY stock_qty ASC LIMIT 5");
while ($lp = mysqli_fetch_assoc($low_q)) {
    notify_admin('low_stock', ['product'=>$lp['productName'], 'qty'=>$lp['stock_qty']]);
}
echo "<script>alert('Order updated sucessfully...');</script>";
//}
}

 ?>
<script language="javascript" type="text/javascript">
function f2()
{
window.close();
}ser
function f3()
{
window.print(); 
}
</script>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Update Compliant</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<link href="anuj.css" rel="stylesheet" type="text/css">
</head>
<body>

<div style="margin-left:50px;">
 <form name="updateticket" id="updateticket" method="post"> 
<table width="100%" border="0" cellspacing="0" cellpadding="0">

    <tr height="50">
      <td colspan="2" class="fontkink2" style="padding-left:0px;"><div class="fontpink2"> <b>Update Order !</b></div></td>
      
    </tr>
    <tr height="30">
      <td  class="fontkink1"><b>order Id:</b></td>
      <td  class="fontkink"><?php echo $oid;?></td>
    </tr>
    <?php 
$ret = mysqli_query($con,"SELECT * FROM ordertrackhistory WHERE orderId='$oid'");
     while($row=mysqli_fetch_array($ret))
      {
     ?>
		
    
    
      <tr height="20">
      <td class="fontkink1" ><b>At Date:</b></td>
      <td  class="fontkink"><?php echo $row['postingDate'];?></td>
    </tr>
     <tr height="20">
      <td  class="fontkink1"><b>Status:</b></td>
      <td  class="fontkink"><?php echo $row['status'];?></td>
    </tr>
     <tr height="20">
      <td  class="fontkink1"><b>Remark:</b></td>
      <td  class="fontkink"><?php echo $row['remark'];?></td>
    </tr>

   
    <tr>
      <td colspan="2"><hr /></td>
    </tr>
   <?php } ?>
   <?php 
$st='Delivered';
   $rt = mysqli_query($con,"SELECT * FROM orders WHERE id='$oid'");
     while($num=mysqli_fetch_array($rt))
     {
     $currrentSt=$num['orderStatus'];
   }
     if($st==$currrentSt)
     { ?>
   <tr><td colspan="2"><b>
      Product Delivered </b></td>
   <?php }else  {
      ?>
   
    <tr height="50">
      <td class="fontkink1">Status: </td>
      <td  class="fontkink"><span class="fontkink1" >
        <select name="status" class="fontkink" required="required" >
          <option value="">Seleccionar estado</option>
          <option value="Borrador">⏳ Borrador</option>
          <option value="Confirmada">✅ Confirmada</option>
          <option value="En gestión">🔄 En gestión</option>
          <option value="Despachada">🚚 Despachada</option>
          <option value="Entregada">📦 Entregada</option>
          <option value="in Process">In Process (legado)</option>
          <option value="Delivered">Delivered (legado)</option>
        </select>
        </span></td>
    </tr>

     <tr style=''>
      <td class="fontkink1" >Remark:</td>
      <td class="fontkink" align="justify" ><span class="fontkink">
        <textarea cols="50" rows="4" name="remark"  required="required" ></textarea>
        </span></td>
    </tr>
    <tr>
      <td class="fontkink1">URL de seguimiento:</td>
      <td class="fontkink">
        <?php
        $_tu_q = mysqli_query($con, "SELECT tracking_url FROM orders WHERE id=$oid LIMIT 1");
        $_tu_row = $_tu_q ? mysqli_fetch_assoc($_tu_q) : null;
        $_tu_val = htmlspecialchars($_tu_row['tracking_url'] ?? '');
        ?>
        <input type="url" name="tracking_url" value="<?php echo $_tu_val; ?>" placeholder="https://tracking.transportista.com/..." style="width:100%;padding:4px 6px;border:1px solid #ccc;border-radius:3px">
        <small style="color:#888">Opcional — el cliente verá el enlace en su historial de pedidos</small>
      </td>
    </tr>
    <tr>
      <td class="fontkink1">&nbsp;</td>
      <td  >&nbsp;</td>
    </tr>
    <tr>
      <td class="fontkink">       </td>
      <td  class="fontkink"> <input type="submit" name="submit2"  value="update"   size="40" style="cursor: pointer;" /> &nbsp;&nbsp;   
      <input name="Submit2" type="submit" class="txtbox4" value="Close this Window " onClick="return f2();" style="cursor: pointer;"  /></td>
    </tr>
<?php } ?>
</table>
 </form>
</div>

</body>
</html>
<?php } ?>

     