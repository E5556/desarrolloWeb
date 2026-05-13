<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
include_once('includes/membership.php');

$uid = intval($_SESSION['id'] ?? 0);
$is_logged = !empty($_SESSION['login']);

ps_membership_init($con);

// Ensure at least one premium plan exists
mysqli_query($con, "INSERT IGNORE INTO subscription_plans (id,name,description,price_monthly,discount_pct,is_premium,free_shipping,active)
    VALUES (1,'Premium Mensual','Envio gratis en todos tus pedidos, 8% de descuento permanente y acceso anticipado a ofertas.',29900,8,1,1,1)");

$plans_q = mysqli_query($con, "SELECT * FROM subscription_plans WHERE is_premium=1 AND active=1 ORDER BY price_monthly");

$is_premium = $uid > 0 && ps_membership_active($con, $uid);
$my_plan    = $is_premium ? ps_membership_plan($con, $uid) : null;

// Subscribe
if (isset($_POST['subscribe']) && $is_logged) {
    csrf_verify();
    $plan_id = intval($_POST['plan_id']);
    if (!$is_premium) {
        $next = date('Y-m-d', strtotime('+1 month'));
        $stmt = mysqli_prepare($con, "INSERT INTO subscriptions (user_id, plan_id, next_billing) VALUES(?,?,?)");
        mysqli_stmt_bind_param($stmt, 'iis', $uid, $plan_id, $next);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:premium.php?ok=1'); exit();
    }
}

// Cancel
if (isset($_POST['cancel_premium']) && $is_logged && $is_premium) {
    csrf_verify();
    mysqli_query($con, "UPDATE subscriptions s JOIN subscription_plans sp ON sp.id=s.plan_id
        SET s.status='cancelled', s.cancelled_at=NOW()
        WHERE s.user_id=$uid AND s.status='active' AND sp.is_premium=1");
    header('location:premium.php?cancelled=1'); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Membresia Premium | <?php echo $_SITE_NAME; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
<style>
.premium-hero{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff;padding:60px 0 40px;text-align:center}
.premium-hero h1{font-size:2.5em;font-weight:700;margin-bottom:10px}
.premium-hero .badge-prem{background:linear-gradient(90deg,#f39c12,#e74c3c);border-radius:20px;padding:6px 20px;font-size:14px;font-weight:700;letter-spacing:1px}
.benefit-card{border:none;border-radius:12px;padding:24px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);height:100%}
.benefit-icon{font-size:36px;margin-bottom:12px;color:#f39c12}
.plan-card{border:2px solid #f39c12;border-radius:16px;padding:30px;text-align:center;max-width:380px;margin:0 auto}
.plan-price{font-size:2.2em;font-weight:800;color:#f39c12}
</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success text-center" style="margin:0;border-radius:0">
<i class="fa fa-star"></i> <strong>Bienvenido a Premium!</strong> Tu membresia esta activa. Disfruta todos los beneficios.
</div>
<?php endif; ?>
<?php if (isset($_GET['cancelled'])): ?>
<div class="alert alert-warning text-center" style="margin:0;border-radius:0">Membresia cancelada. Tus beneficios siguen activos hasta el proximo ciclo.</div>
<?php endif; ?>

<!-- Hero -->
<div class="premium-hero">
<div class="container">
<span class="badge-prem"><i class="fa fa-star"></i> PREMIUM</span>
<h1 style="margin-top:16px">Compra mejor, ahorra mas</h1>
<p style="font-size:16px;opacity:.85;max-width:560px;margin:0 auto">Una membresia mensual con beneficios reales: envio gratis, descuentos permanentes y acceso anticipado a las mejores ofertas.</p>
</div>
</div>

<div class="body-content outer-top-bd">
<div class="container">

<!-- Benefits -->
<div class="row" style="margin-bottom:40px">
<div class="col-md-3 col-sm-6" style="margin-bottom:20px">
<div class="benefit-card">
<div class="benefit-icon"><i class="fa fa-truck"></i></div>
<h4>Envio gratis</h4>
<p class="text-muted" style="font-size:13px">En todos tus pedidos, sin minimo de compra.</p>
</div>
</div>
<div class="col-md-3 col-sm-6" style="margin-bottom:20px">
<div class="benefit-card">
<div class="benefit-icon"><i class="fa fa-tag"></i></div>
<h4>8% de descuento</h4>
<p class="text-muted" style="font-size:13px">Descuento permanente en todos los productos.</p>
</div>
</div>
<div class="col-md-3 col-sm-6" style="margin-bottom:20px">
<div class="benefit-card">
<div class="benefit-icon"><i class="fa fa-bolt"></i></div>
<h4>Acceso anticipado</h4>
<p class="text-muted" style="font-size:13px">Ve las ofertas flash 2 horas antes que todos.</p>
</div>
</div>
<div class="col-md-3 col-sm-6" style="margin-bottom:20px">
<div class="benefit-card">
<div class="benefit-icon"><i class="fa fa-star"></i></div>
<h4>Badge Premium</h4>
<p class="text-muted" style="font-size:13px">Identificacion especial en tu perfil y reseñas.</p>
</div>
</div>
</div>

<!-- Plan / Status -->
<div class="row">
<div class="col-md-6 col-md-offset-3">

<?php if ($is_premium && $my_plan): ?>
<div class="plan-card" style="border-color:#27ae60">
<div style="margin-bottom:12px"><?php echo ps_membership_badge(); ?></div>
<h3 style="margin-top:0">Tu membresia esta activa</h3>
<p class="text-muted">Plan: <strong><?php echo htmlspecialchars($my_plan['name']); ?></strong></p>
<p class="text-muted">Proximo cobro: <strong><?php echo date('d/m/Y', strtotime($my_plan['next_billing'])); ?></strong></p>
<p class="text-muted">Activa desde: <?php echo date('d/m/Y', strtotime($my_plan['sub_start'])); ?></p>
<hr>
<p style="font-size:13px;color:#555">Todos tus beneficios estan activos. Sigue disfrutando el envio gratis y el 8% de descuento en cada compra.</p>
<form method="post" onsubmit="return confirm('Cancelar tu membresia Premium?')">
<?php echo csrf_field(); ?>
<button type="submit" name="cancel_premium" class="btn btn-link text-muted" style="font-size:12px"><i class="fa fa-times"></i> Cancelar membresia</button>
</form>
</div>

<?php else: ?>
<?php mysqli_data_seek($plans_q, 0); while ($plan = mysqli_fetch_assoc($plans_q)): ?>
<div class="plan-card">
<div class="plan-price">$<?php echo number_format($plan['price_monthly'],0,'.',','); ?><span style="font-size:14px;color:#999;font-weight:400">/mes</span></div>
<h3 style="margin-top:8px"><?php echo htmlspecialchars($plan['name']); ?></h3>
<p class="text-muted" style="font-size:13px"><?php echo htmlspecialchars($plan['description']); ?></p>
<ul style="text-align:left;font-size:13px;list-style:none;padding:0;margin:16px 0">
<li><i class="fa fa-check text-success"></i> Envio gratis en todos los pedidos</li>
<li><i class="fa fa-check text-success"></i> <?php echo $plan['discount_pct']; ?>% de descuento permanente</li>
<li><i class="fa fa-check text-success"></i> Acceso anticipado a ofertas flash</li>
<li><i class="fa fa-check text-success"></i> Badge Premium en tu perfil</li>
</ul>
<?php if ($is_logged): ?>
<form method="post">
<?php echo csrf_field(); ?>
<input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
<button type="submit" name="subscribe" class="btn btn-warning btn-lg btn-block" style="font-weight:700;background:linear-gradient(90deg,#f39c12,#e74c3c);border:none;color:#fff">
<i class="fa fa-star"></i> Activar Premium ahora
</button>
</form>
<?php else: ?>
<a href="login.php?redirect=premium.php" class="btn btn-warning btn-lg btn-block" style="font-weight:700;background:linear-gradient(90deg,#f39c12,#e74c3c);border:none;color:#fff">
<i class="fa fa-star"></i> Iniciar sesion para suscribirse
</a>
<?php endif; ?>
</div>
<?php endwhile; ?>
<?php endif; ?>

</div>
</div>

</div></div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
