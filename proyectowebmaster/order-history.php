<?php 
session_start();
error_reporting(0);
include('includes/config.php');
if(empty($_SESSION['login']))
    {   
header('location:login.php');
}
else{

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Meta -->
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta name="description" content="">
		<meta name="author" content="">
	    <meta name="keywords" content="MediaCenter, Template, eCommerce">
	    <meta name="robots" content="all">

	    <title>Historial de pedidos | <?php echo $_SITE_NAME; ?></title>
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/cosmetics.css">
	    <link rel="stylesheet" href="assets/css/owl.carousel.css">
		<link rel="stylesheet" href="assets/css/owl.transitions.css">
		<!---->
		<link href="assets/css/lightbox.css" rel="stylesheet">
		<link rel="stylesheet" href="assets/css/animate.min.css">
		<link rel="stylesheet" href="assets/css/rateit.css">
		<link rel="stylesheet" href="assets/css/bootstrap-select.min.css">

		<!-- Demo Purpose Only. Should be removed in production -->
		<link rel="stylesheet" href="assets/css/config.css">

		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
		<?php include('includes/analytics.php'); ?>
		<?php if (!empty($_SESSION['purchase_total_analytics'])): ?>
		<script>
		window.addEventListener('load', function(){
		    var total = <?php echo floatval($_SESSION['purchase_total_analytics']); ?>;
		    if (window.psGA4Event) window.psGA4Event('purchase', { currency:'COP', value:total, transaction_id: Date.now().toString() });
		    if (window.psFBEvent) window.psFBEvent('Purchase', { value:total, currency:'COP' });
		});
		</script>
		<?php unset($_SESSION['purchase_total_analytics']); endif; ?>
	<script language="javascript" type="text/javascript">
var popUpWin=0;
function popUpWindow(URLStr, left, top, width, height)
{
 if(popUpWin)
{
if(!popUpWin.closed) popUpWin.close();
}
popUpWin = open(URLStr,'popUpWin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=yes,width='+600+',height='+600+',left='+left+', top='+top+',screenX='+left+',screenY='+top+'');
}

</script>

	</head>
    <body class="cnt-home">
	
		
	
		<!-- ============================================== HEADER ============================================== -->
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>
<!-- ============================================== HEADER : END ============================================== -->
<div class="breadcrumb">
	<div class="container">
		<div class="breadcrumb-inner">
			<ul class="list-inline list-unstyled">
				<li><a href="#">Inicio</a></li>
				<li class='active'>Carrito de compras</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-xs">
	<div class="container">
		<div class="row inner-bottom-sm">
			<div class="shopping-cart">
				<div class="col-md-12 col-sm-12 shopping-cart-table ">
	<div class="table-responsive">
<form name="cart" method="post">	

		<table class="table table-bordered">
			<thead>
				<tr>
					<th class="cart-romove item">#</th>
					<th class="cart-description item">Imagen</th>
					<th class="cart-product-name item">Nombre</th>
			
					<th class="cart-qty item">Cantidad</th>
					<th class="cart-sub-total item">Precio por unidad</th>
					<th class="cart-sub-total item">Costo de envio</th>
					<th class="cart-total item">Total</th>
					<th class="cart-total item">Método de pago</th>
					<th class="cart-description item">Fecha de compra</th>
					<th class="cart-total last-item">Acción</th>
				</tr>
			</thead><!-- /thead -->

			<tbody>

<?php
// Crear tabla returns si no existe
// AA5: ensure track_token column exists
mysqli_query($con, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS track_token VARCHAR(32) DEFAULT NULL");
// Generate tokens for orders that don't have one
$_tq = mysqli_query($con, "SELECT id FROM orders WHERE track_token IS NULL AND paymentMethod IS NOT NULL LIMIT 100");
while ($_tq && $_tr = mysqli_fetch_assoc($_tq)) {
    $_tok = bin2hex(random_bytes(16));
    mysqli_query($con, "UPDATE orders SET track_token='$_tok' WHERE id=".intval($_tr['id']));
}

mysqli_query($con, "CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
)");

$query=mysqli_query($con,"SELECT products.productImage1 as pimg1,products.productName as pname,products.id as proid,orders.productId as opid,orders.quantity as qty,products.productPrice as pprice,products.shippingCharge as shippingcharge,orders.paymentMethod as paym,orders.orderDate as odate,orders.id as orderid,orders.orderStatus as ostatus,orders.tracking_url as tracking_url,orders.track_token as track_token FROM orders JOIN products ON orders.productId=products.id WHERE orders.userId=".intval($_SESSION['id'])." AND orders.paymentMethod IS NOT NULL");
$cnt=1;
while($row=mysqli_fetch_array($query))
{
// Verificar si ya hay devolución para esta orden
$_ret_q = mysqli_query($con, "SELECT status FROM returns WHERE order_id=".intval($row['orderid'])." AND user_id=".intval($_SESSION['id'])." LIMIT 1");
$_ret = $_ret_q ? mysqli_fetch_assoc($_ret_q) : null;
?>
				<tr>
					<td><?php echo $cnt;?></td>
					<td class="cart-image">
						<a class="entry-thumbnail" href="detail.html">
						    <img src="admin/productimages/<?php echo $row['proid'];?>/<?php echo $row['pimg1'];?>" alt="" width="84" height="146">
						</a>
					</td>
					<td class="cart-product-name-info">
						<h4 class='cart-product-description'><a href="product-details.php?pid=<?php echo $row['opid'];?>">
						<?php echo $row['pname'];?></a></h4>


					</td>
					<td class="cart-product-quantity">
						<?php echo $qty=$row['qty']; ?>
		            </td>
					<td class="cart-product-sub-total"><?php echo $price=$row['pprice']; ?>  </td>
					<td class="cart-product-sub-total"><?php echo $shippcharge=$row['shippingcharge']; ?>  </td>
					<td class="cart-product-grand-total"><?php echo (($qty*$price)+$shippcharge);?></td>
					<td class="cart-product-sub-total"><?php echo $row['paym']; ?>  </td>
					<td class="cart-product-sub-total"><?php echo $row['odate']; ?>  </td>

					<td>
 <a href="javascript:void(0);" onClick="popUpWindow('track-order.php?oid=<?php echo htmlentities($row['orderid']);?>');" title="Track order" class="btn btn-xs btn-default">Track</a>
 <?php if ($_ret): ?>
     <?php $st_map=['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'success']; $st_lbl=['pending'=>'Devolución pendiente','approved'=>'Devolución aprobada','rejected'=>'Devolución rechazada','completed'=>'Devolución completada']; ?>
     <span class="label label-<?php echo $st_map[$_ret['status']]??'default'; ?>" style="margin-top:4px;display:inline-block"><?php echo $st_lbl[$_ret['status']]??$_ret['status']; ?></span>
 <?php elseif (!empty($row['paym'])): ?>
     <a href="return-request.php?oid=<?php echo intval($row['orderid']); ?>" class="btn btn-xs btn-warning" style="margin-top:4px"><i class="fa fa-undo"></i> Devolver</a>
 <?php endif; ?>
 <?php if (!empty($row['tracking_url'])): ?>
     <br><a href="<?php echo htmlspecialchars($row['tracking_url']); ?>" target="_blank" class="btn btn-xs btn-info" style="margin-top:4px"><i class="fa fa-truck"></i> Rastrear envío</a>
 <?php endif; ?>
 <br><a href="invoice.php?oid=<?php echo intval($row['orderid']); ?>" target="_blank" class="btn btn-xs btn-default" style="margin-top:4px"><i class="fa fa-file-pdf-o"></i> Factura</a>
 <?php if (!empty($row['track_token'])): ?>
 <br><button type="button" class="btn btn-xs btn-default" style="margin-top:4px" onclick="var u='http://'+location.host+'/proyectowebmaster/order-track.php?token=<?php echo urlencode($row['track_token']); ?>';navigator.clipboard.writeText(u).then(function(){alert('Enlace de seguimiento copiado!');});"><i class="fa fa-share-alt"></i> Compartir</button>
 <?php endif; ?>
					</td>
				</tr>
<?php $cnt=$cnt+1;} ?>
				
			</tbody><!-- /tbody -->
		</table><!-- /table -->
		
	</div>
</div>

		</div><!-- /.shopping-cart -->
		</div> <!-- /.row -->
		</form>
		<!-- ============================================== BRANDS CAROUSEL ============================================== -->
<?php echo include('includes/brands-slider.php');?>
<!-- ============================================== BRANDS CAROUSEL : END ============================================== -->	</div><!-- /.container -->
</div><!-- /.body-content -->
<?php include('includes/footer.php');?>

	<script src="assets/js/jquery-1.11.1.min.js"></script>
	
	<script src="assets/js/bootstrap.min.js"></script>
	
	<script src="assets/js/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/js/owl.carousel.min.js"></script>
	
	<script src="assets/js/echo.min.js"></script>
	<script src="assets/js/jquery.easing-1.3.min.js"></script>
	<script src="assets/js/bootstrap-slider.min.js"></script>
    <script src="assets/js/jquery.rateit.min.js"></script>
    <script type="text/javascript" src="assets/js/lightbox.min.js"></script>
    <script src="assets/js/bootstrap-select.min.js"></script>
    
	<script src="assets/js/scripts.js"></script>

	<!-- For demo purposes – can be removed on production -->
	
	
	
	<script>
		$(document).ready(function(){ 
			if ($.fn.switchstylesheet) $(".changecolor").switchstylesheet( { seperator:"color"} );
			$('.show-theme-options').click(function(){
				$(this).parent().toggleClass('open');
				return false;
			});
		});

		$(window).bind("load", function() {
		   $('.show-theme-options').delay(2000).trigger('click');
		});
	</script>
	<!-- For demo purposes – can be removed on production : End -->
<script src="assets/js/cookies.js"></script>
</body>
</html>
<?php } ?>