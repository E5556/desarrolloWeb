<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if(empty($_SESSION['login']))
    {
header('location:login.php');
}
else{
	if (isset($_POST['submit'])) {
		csrf_verify();
		$allowed_methods = ['COD', 'Internet Banking', 'Debit / Credit card'];
		$paymethod = safe_whitelist($_POST['paymethod'] ?? '', $allowed_methods, 'COD');
		$uid_p     = intval($_SESSION['id']);
		$stmt_pm = mysqli_prepare($con, "UPDATE orders SET paymentMethod=? WHERE userId=? AND paymentMethod IS NULL");
		mysqli_stmt_bind_param($stmt_pm, 'si', $paymethod, $uid_p);
		mysqli_stmt_execute($stmt_pm);
		mysqli_stmt_close($stmt_pm);

		// Descontar stock de cada producto comprado
		$_sq = mysqli_query($con, "SELECT productId, quantity FROM orders WHERE userId=$uid_p AND paymentMethod='".mysqli_real_escape_string($con,$paymethod)."'");
		$_threshold_row = mysqli_fetch_assoc(mysqli_query($con,"SELECT setting_value FROM settings WHERE setting_key='low_stock_threshold' LIMIT 1"));
		$_low_thresh = intval($_threshold_row['setting_value'] ?? 5);
		while ($_sr = mysqli_fetch_assoc($_sq)) {
			$_spid = intval($_sr['productId']); $_sqty = intval($_sr['quantity']);
			mysqli_query($con, "UPDATE products SET stock_qty = GREATEST(0, stock_qty - $_sqty) WHERE id=$_spid AND stock_qty IS NOT NULL");
			// Si llega a 0, marcar Out of Stock
			mysqli_query($con, "UPDATE products SET productAvailability='Out of Stock' WHERE id=$_spid AND stock_qty IS NOT NULL AND stock_qty=0");
			// Registrar movimiento de stock
			$_sq_after = mysqli_fetch_assoc(mysqli_query($con,"SELECT stock_qty FROM products WHERE id=$_spid LIMIT 1"));
			$_qty_after_val = intval($_sq_after['stock_qty'] ?? 0);
			$_delta_neg = -$_sqty;
			mysqli_query($con, "CREATE TABLE IF NOT EXISTS stock_movements (id INT AUTO_INCREMENT PRIMARY KEY,product_id INT NOT NULL,type ENUM('in','out','adjustment') NOT NULL DEFAULT 'adjustment',qty_change INT NOT NULL,qty_after INT DEFAULT NULL,reason VARCHAR(200) DEFAULT '',admin_user VARCHAR(80) DEFAULT '',created_at DATETIME DEFAULT CURRENT_TIMESTAMP,INDEX(product_id),INDEX(created_at))");
			$_sm_stmt = mysqli_prepare($con,"INSERT INTO stock_movements (product_id,type,qty_change,qty_after,reason) VALUES(?,?,?,?,?)");
			$_sm_type = 'out'; $_sm_reason = 'Venta confirmada';
			mysqli_stmt_bind_param($_sm_stmt,'isiis',$_spid,$_sm_type,$_delta_neg,$_qty_after_val,$_sm_reason);
			mysqli_stmt_execute($_sm_stmt); mysqli_stmt_close($_sm_stmt);
			// Alerta de stock bajo
			$_stock_check = mysqli_fetch_assoc(mysqli_query($con,"SELECT productName, stock_qty FROM products WHERE id=$_spid LIMIT 1"));
			if ($_stock_check && $_stock_check['stock_qty'] !== null && $_stock_check['stock_qty'] <= $_low_thresh && $_stock_check['stock_qty'] > 0) {
				include_once('includes/mailer.php');
				notify_admin('low_stock', ['product'=>$_stock_check['productName'],'qty'=>$_stock_check['stock_qty']]);
			}
		}

		// Enviar email de confirmación
		$_uq = mysqli_query($con, "SELECT name, email FROM users WHERE id=$uid_p");
		if ($_uq && $_urow = mysqli_fetch_assoc($_uq)) {
			$_pm_esc = mysqli_real_escape_string($con, $paymethod);
			$_oq = mysqli_query($con, "SELECT o.quantity, p.productName, p.productPrice FROM orders o JOIN products p ON p.id=o.productId WHERE o.userId=$uid_p AND o.paymentMethod='$_pm_esc' ORDER BY o.id DESC LIMIT 20");
			$_items = []; $_tot = 0;
			while ($_oq && $_or = mysqli_fetch_assoc($_oq)) {
				$_items[] = ['name'=>$_or['productName'],'qty'=>$_or['quantity'],'price'=>$_or['productPrice']];
				$_tot += $_or['quantity'] * $_or['productPrice'];
			}
			if (!empty($_items)) {
				include_once('includes/mailer.php');
				send_order_confirmation($con, $_urow['email'], $_urow['name'], $_items, $_tot, $paymethod);
				notify_admin('new_order', ['order_id'=>'reciente','customer'=>$_urow['name'].' <'.$_urow['email'].'>','total'=>number_format($_tot,0,'.',','),'method'=>$paymethod]);

				// E1: Acumular puntos (1 pto por cada $1.000)
				include_once('includes/points.php');
				$_pts = max(1, intval($_tot / 1000));
				ps_points_add($con, $uid_p, $_pts, 'Compra confirmada — ' . $paymethod);
				// N2: Tick de reto por orden y gasto
				include_once('includes/challenges.php');
				ps_challenge_tick($con, $uid_p, 'orders', 1);
				ps_challenge_tick($con, $uid_p, 'spend', intval($_tot));
				// S4: Recalcular segmento del cliente
				include_once('includes/segmentation.php');
				ps_segment_user($con, $uid_p);
			}
		}

		$_SESSION['purchase_total_analytics'] = $_tot ?? 0;
		include_once('includes/cart.php'); ps_cart_clear($con, intval($_SESSION['id']));
		unset($_SESSION['cart']);
		header('location:order-history.php');
		exit();
	}
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

	    <title>Método de pago | <?php echo $_SITE_NAME; ?></title>
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
		<link rel="stylesheet" href="assets/css/config.css">
		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
	</head>
    <body class="cnt-home">
	
		
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>
<div class="breadcrumb">
	<div class="container">
		<div class="breadcrumb-inner">
			<ul class="list-inline list-unstyled">
				<li><a href="index2.php">Inicio</a></li>
				<li class='active'>Método de pago</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-bd">
	<div class="container">
		<div class="checkout-box faq-page inner-bottom-sm">
			<div class="row">
				<div class="col-md-12">
					<h2>Cambiar método de pago</h2>
					<div class="panel-group checkout-steps" id="accordion">
						<!-- checkout-step-01  -->
<div class="panel panel-default checkout-step-01">

	<!-- panel-heading -->
		<div class="panel-heading">
    	<h4 class="unicase-checkout-title">
	        <a data-toggle="collapse" class="" data-parent="#accordion" href="#collapseOne">
	         Selecciona tu método de pago
	        </a>
	     </h4>
    </div>
    <!-- panel-heading -->

	<div id="collapseOne" class="panel-collapse collapse in">

		<!-- panel-body  -->
	    <div class="panel-body">
	    <form name="payment" method="post">
	    <?php echo csrf_field(); ?>
	    <input type="radio" name="paymethod" value="COD" checked="checked"> COD (contra entrega)
	     <input type="radio" name="paymethod" value="Internet Banking"> Portal bancario
	     <input type="radio" name="paymethod" value="Debit / Credit card"> Debito / Tarjeta de crédito <br /><br />
	     <input type="submit" value="Confirmar método" name="submit" class="btn btn-default">
	    </form>
	    <?php
	    // Mostrar botón MP solo si está configurado
	    $_mp_cfg_q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='mp_access_token' LIMIT 1");
	    $_mp_tok   = $_mp_cfg_q ? (mysqli_fetch_assoc($_mp_cfg_q)['setting_value'] ?? '') : '';
	    if (!empty(trim($_mp_tok))): ?>
	    <div style="margin-top:16px;padding:14px;background:#f7f7f7;border-radius:6px;border:1px solid #eee">
	        <p style="margin:0 0 10px;font-size:13px;color:#555"><strong>Paga de forma segura con MercadoPago</strong><br>
	        Acepta tarjetas, PSE, Nequi, efectivo y más.</p>
	        <a href="mercadopago-checkout.php" class="btn btn-primary" style="background:#009ee3;border-color:#007eb5">
	            <i class="fa fa-credit-card"></i> Pagar con MercadoPago
	        </a>
	    </div>
	    <?php endif; ?>		
		</div>
		<!-- panel-body  -->

	</div><!-- row -->
</div>
<!-- checkout-step-01  -->
					  
					  	
					</div><!-- /.checkout-steps -->
				</div>
			</div><!-- /.row -->
		</div><!-- /.checkout-box -->
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