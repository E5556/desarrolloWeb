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
    <title>Politica de Privacidad | <?php echo $_SITE_NAME; ?></title>
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
<div class='breadcrumb'><div class='container'><div class='breadcrumb-inner'><ul class='list-inline list-unstyled'><li><a href='index2.php'>Inicio</a></li><li class='active'>Politica de Privacidad</li></ul></div></div></div>
<div class='body-content outer-top-xs'><div class='container'><div class='row inner-bottom-sm'><div class='col-md-10 col-md-offset-1'><div style='background:#fff;border:1px solid #eee;border-radius:10px;padding:32px 36px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:30px;'><h2 style='color:#333;margin-bottom:6px'><i class='fa fa-shield' style='color:#c0396b;margin-right:10px'></i>Politica de Privacidad</h2><p style='color:#aaa;font-size:12px;margin-bottom:28px'>Ultima actualizacion: <?php echo date('d/m/Y'); ?></p><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">1. Informacion que recopilamos</h4><p style="color:#555;line-height:1.8">Recopilamos nombre, correo electronico, direccion de envio y telefono al crear una cuenta o realizar una compra.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">2. Uso de la informacion</h4><p style="color:#555;line-height:1.8">La informacion se usa para procesar pedidos y mejorar nuestros servicios. No vendemos ni compartimos datos con terceros.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">3. Seguridad</h4><p style="color:#555;line-height:1.8">Implementamos medidas tecnicas para proteger su informacion. Ningun metodo de transmision por Internet es completamente seguro.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">4. Cookies</h4><p style="color:#555;line-height:1.8">Usamos cookies para mejorar su experiencia y mantener su sesion activa. Puede configurar su navegador para rechazarlas.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">5. Sus derechos</h4><p style="color:#555;line-height:1.8">Tiene derecho a acceder, corregir o eliminar su informacion personal. Contactenos para ejercer estos derechos.</p></div><div style="margin-bottom:28px;"><h4 style="color:#c0396b;border-left:4px solid #c0396b;padding-left:10px;margin-bottom:10px">6. Cambios</h4><p style="color:#555;line-height:1.8">Podemos actualizar esta politica periodicamente. Publicaremos los cambios en este sitio.</p></div><div style='text-align:center;margin-top:30px;padding-top:20px;border-top:1px solid #eee;'><a href='index2.php' class='btn btn-primary'><i class='fa fa-arrow-left' style='margin-right:6px'></i>Volver a la tienda</a></div></div></div></div></div></div>
<?php include('includes/footer.php');?>
<script src='assets/js/jquery-1.11.1.min.js'></script>
<script src='assets/js/bootstrap.min.js'></script>
<script src='assets/js/owl.carousel.min.js'></script>
<script src='assets/js/scripts.js'></script>
<script src='assets/js/cart-drawer.js'></script>
<script src='assets/js/cookies.js'></script>
</body>
</html>
