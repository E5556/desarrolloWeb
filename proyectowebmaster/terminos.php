<?php
session_start();
error_reporting(0);
include('includes/config.php');
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=no'>
    <title>Terminos y Condiciones | <?php echo $_SITE_NAME; ?></title>
    <link rel='stylesheet' href='assets/css/bootstrap.min.css'>
    <link rel='stylesheet' href='assets/css/main.css'>
    <link rel='stylesheet' href='assets/css/green.css'>
    <link rel='stylesheet' href='assets/css/cosmetics.css'>
    <link rel='stylesheet' href='assets/css/font-awesome.min.css'>
    <link rel='shortcut icon' href='<?php echo $_SITE_FAVICON; ?>'>
</head>
<body class='cnt-home'>
<header class='header-style-1'>
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>
<div class='breadcrumb'><div class='container'><div class='breadcrumb-inner'><ul class='list-inline list-unstyled'><li><a href='index2.php'>Inicio</a></li><li class='active'>Terminos y Condiciones</li></ul></div></div></div>
<div class='body-content outer-top-xs'><div class='container'><div class='row inner-bottom-sm'><div class='col-md-10 col-md-offset-1'><div style='background:#fff;border:1px solid #eee;border-radius:10px;padding:32px 36px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:30px;'><h2 style='color:#333;margin-bottom:6px'><i class='fa fa-file-text-o' style='color:#c0396b;margin-right:10px'></i>Terminos y Condiciones</h2><p style='color:#aaa;font-size:12px;margin-bottom:28px'>Ultima actualizacion: <?php echo date('d/m/Y'); ?></p><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">1. Aceptacion</h4><p style="color:#555;line-height:1.8">Al acceder y utilizar este sitio web, usted acepta estar sujeto a estos terminos. Si no esta de acuerdo, le pedimos que no utilice nuestro sitio.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">2. Uso del sitio</h4><p style="color:#555;line-height:1.8">Este sitio esta destinado al uso personal y comercial legitimo. Queda prohibido el uso para actividades ilegales o fraudulentas.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">3. Productos y precios</h4><p style="color:#555;line-height:1.8">Los precios estan sujetos a cambios sin previo aviso. Nos reservamos el derecho de limitar cantidades y rechazar pedidos.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">4. Compras y pagos</h4><p style="color:#555;line-height:1.8">Al realizar una compra, garantiza que la informacion de pago es valida. Todos los precios se expresan en pesos colombianos (COP).</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">5. Modificaciones</h4><p style="color:#555;line-height:1.8">Nos reservamos el derecho de modificar estos terminos en cualquier momento. Las modificaciones entran en vigor inmediatamente.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">6. Contacto</h4><p style="color:#555;line-height:1.8">Para consultas sobre estos terminos, contactenos a traves de los medios indicados en la seccion de contacto.</p></div><div style='text-align:center;margin-top:30px;padding-top:20px;border-top:1px solid #eee;'><a href='index2.php' class='btn btn-primary'><i class='fa fa-arrow-left' style='margin-right:6px'></i>Volver a la tienda</a></div></div></div></div></div></div>
<?php include('includes/footer.php');?>
<script src='assets/js/jquery-1.11.1.min.js'></script>
<script src='assets/js/bootstrap.min.js'></script>
<script src='assets/js/owl.carousel.min.js'></script>
<script src='assets/js/scripts.js'></script>
<script src='assets/js/cart-drawer.js'></script>
<script src='assets/js/cookies.js'></script>
</body>
</html>
