<?php
session_start();
error_reporting(0);
include('includes/config.php');
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

	    <title>Track Orders | <?php echo $_SITE_NAME; ?></title>
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/cosmetics.css">
	    <link rel="stylesheet" href="assets/css/owl.carousel.css">
		<link rel="stylesheet" href="assets/css/owl.transitions.css">
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

	<!-- ============================================== TOP MENU ============================================== -->
<?php include('includes/top-header.php');?>
<!-- ============================================== TOP MENU : END ============================================== -->
<?php include('includes/main-header.php');?>
	<!-- ============================================== NAVBAR ============================================== -->
<?php include('includes/menu-bar.php');?>
<!-- ============================================== NAVBAR : END ============================================== -->

</header>
<!-- ============================================== HEADER : END ============================================== -->
<div class="breadcrumb">
	<div class="container">
		<div class="breadcrumb-inner">
			<ul class="list-inline list-unstyled">
				<li><a href="index2.php">Inicio</a></li>
				<li class='active'>Seguimiento de tus pedidos</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-bd">
	<div class="container">
		<div class="track-order-page inner-bottom-sm">
			<div class="row">
				<div class="col-md-12">
	<h2>Rastrea tu pedido</h2>
	<span class="title-tag inner-top-vs">Ingrese su ID de pedido y presione Entrar. Esto se le proporcionó en su recibo y en el correo electrónico de confirmación que debería haber recibido. </span>
	<?php if(!empty($_SESSION['login'])): ?>
<div style="background:#eaf4fb;border:1px solid #cce5ff;border-radius:8px;padding:14px 18px;margin-bottom:18px;font-size:13.5px;color:#004085;">
  <i class="fa fa-user-circle" style="margin-right:6px"></i>
  Estás conectado como <strong><?php echo htmlspecialchars($_SESSION['login']); ?></strong>.
  <a href="order-history.php" class="btn btn-primary btn-sm" style="margin-left:12px">
    <i class="fa fa-list"></i> Ver mis órdenes
  </a>
</div>
<?php endif; ?>
<form class="register-form outer-top-xs" role="form" method="post" action="order-details.php" onsubmit="return trackValidate()">
	<div class="form-group">
	    <label class="info-title" for="exampleOrderId1">Número de orden</label>
	    <input type="text" class="form-control unicase-form-control text-input" name="orderid" id="exampleOrderId1" placeholder="Ej: 1234">
	</div>
  	<div class="form-group">
	    <label class="info-title" for="exampleBillingEmail1">Email</label>
	    <input type="email" class="form-control unicase-form-control text-input" name="email" id="exampleBillingEmail1" placeholder="tu@correo.com">
	</div>
  	<button type="submit" name="submit" class="btn-upper btn btn-primary checkout-page-button">Rastrear pedido</button>
</form>
<script>
function trackValidate(){
    var oid = document.getElementById('exampleOrderId1').value.trim();
    var em  = document.getElementById('exampleBillingEmail1').value.trim();
    if(!oid){ showToast('Ingresa el número de orden.','warning'); return false; }
    if(!em){ showToast('Ingresa tu correo electrónico.','warning'); return false; }
    if(!/^\d+$/.test(oid)){ showToast('El número de orden debe ser numérico.','warning'); return false; }
    return true;
}
</script>	
</div>			</div><!-- /.row -->
		</div><!-- /.sigin-in-->
		<!-- ============================================== BRANDS CAROUSEL ============================================== -->
<div 

<?php echo include('includes/brands-slider.php');?>
</div>
</div>
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
	<script src="assets/js/toast.js"></script>

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