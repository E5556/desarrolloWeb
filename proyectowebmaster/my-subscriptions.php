<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if (empty($_SESSION['login'])) { header('location:login.php'); exit(); }

$uid = intval($_SESSION['id']);

// Auto-crear tablas (por si se accede antes que admin)
mysqli_query($con, "CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT DEFAULT '',
    product_id INT DEFAULT NULL,
    price_monthly DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_pct TINYINT UNSIGNED DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($con, "CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active','paused','cancelled') DEFAULT 'active',
    next_billing DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME DEFAULT NULL,
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Suscribirse a un plan
if (isset($_POST['subscribe_plan'])) {
    csrf_verify();
    $plan_id = intval($_POST['plan_id']);
    $plan_q  = mysqli_query($con, "SELECT * FROM subscription_plans WHERE id=$plan_id AND active=1 LIMIT 1");
    if ($plan_q && $plan = mysqli_fetch_assoc($plan_q)) {
        // Verificar si ya tiene suscripción activa a este plan
        $exists = mysqli_query($con, "SELECT id FROM subscriptions WHERE user_id=$uid AND plan_id=$plan_id AND status='active' LIMIT 1");
        if (!$exists || mysqli_num_rows($exists) === 0) {
            $next = date('Y-m-d', strtotime('+1 month'));
            $stmt = mysqli_prepare($con, "INSERT INTO subscriptions (user_id, plan_id, next_billing) VALUES(?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iis', $uid, $plan_id, $next);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            $flash_ok = '¡Suscripción activada! Tu próximo cobro será el ' . date('d/m/Y', strtotime($next)) . '.';
        } else {
            $flash_err = 'Ya tienes una suscripción activa a este plan.';
        }
    }
}

// Cancelar
if (isset($_GET['cancel']) && isset($_GET['sid'])) {
    $sid = intval($_GET['sid']);
    mysqli_query($con, "UPDATE subscriptions SET status='cancelled', cancelled_at=NOW() WHERE id=$sid AND user_id=$uid");
    header('location:my-subscriptions.php?ok=cancelled'); exit();
}

$plans = mysqli_query($con, "SELECT sp.*, p.productName FROM subscription_plans sp LEFT JOIN products p ON p.id=sp.product_id WHERE sp.active=1 ORDER BY sp.price_monthly ASC");
$my_subs = mysqli_query($con, "SELECT s.*, sp.name as plan_name, sp.price_monthly, sp.discount_pct, sp.description as plan_desc FROM subscriptions s JOIN subscription_plans sp ON sp.id=s.plan_id WHERE s.user_id=$uid ORDER BY s.id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mis suscripciones | <?php echo $_SITE_NAME ?? 'Tienda'; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/cosmetics.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON ?? ''; ?>">
<style>
.plan-card{border:1px solid #e5e5e5;border-radius:12px;padding:20px 22px;margin-bottom:16px;transition:box-shadow .2s}
.plan-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.1)}
.plan-card .plan-price{font-size:26px;font-weight:700;color:#337ab7}
.plan-card .plan-disc{background:#27ae60;color:#fff;border-radius:10px;padding:2px 10px;font-size:12px;margin-left:8px}
.sub-row{background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:14px 16px;margin-bottom:10px}
</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>
<div class="breadcrumb"><div class="container"><ul class="list-inline list-unstyled">
    <li><a href="index2.php">Inicio</a></li>
    <li><a href="my-account.php">Mi cuenta</a></li>
    <li class="active">Suscripciones</li>
</ul></div></div>

<div class="body-content outer-top-bd">
<div class="container" style="max-width:860px">

<?php if (!empty($flash_ok)): ?><div class="alert alert-success"><?php echo $flash_ok; ?></div><?php endif; ?>
<?php if (!empty($flash_err)): ?><div class="alert alert-danger"><?php echo $flash_err; ?></div><?php endif; ?>
<?php if (isset($_GET['ok']) && $_GET['ok'] === 'cancelled'): ?><div class="alert alert-info">Suscripción cancelada.</div><?php endif; ?>

<!-- Mis suscripciones activas -->
<?php if ($my_subs && mysqli_num_rows($my_subs) > 0): ?>
<h4><i class="fa fa-repeat" style="color:#337ab7"></i> Mis suscripciones</h4>
<?php while ($s = mysqli_fetch_assoc($my_subs)):
    $st_color = ['active'=>'#27ae60','paused'=>'#f39c12','cancelled'=>'#e8233a'][$s['status']] ?? '#888';
    $st_label = ['active'=>'Activa','paused'=>'Pausada','cancelled'=>'Cancelada'][$s['status']] ?? $s['status'];
?>
<div class="sub-row">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <div>
            <strong><?php echo htmlspecialchars($s['plan_name']); ?></strong>
            <span style="background:<?php echo $st_color; ?>;color:#fff;border-radius:8px;padding:2px 8px;font-size:11px;margin-left:8px"><?php echo $st_label; ?></span><br>
            <small class="text-muted"><?php echo htmlspecialchars($s['plan_desc']); ?></small>
        </div>
        <div style="text-align:right">
            <div style="font-size:18px;font-weight:700;color:#337ab7">$<?php echo number_format($s['price_monthly'], 0, '.', ','); ?>/mes</div>
            <?php if ($s['discount_pct'] > 0): ?><div style="font-size:12px;color:#27ae60"><?php echo $s['discount_pct']; ?>% descuento incluido</div><?php endif; ?>
            <div style="font-size:12px;color:#888">Próx. cobro: <?php echo date('d/m/Y', strtotime($s['next_billing'])); ?></div>
        </div>
    </div>
    <?php if ($s['status'] === 'active'): ?>
    <div style="margin-top:10px">
        <a href="my-subscriptions.php?cancel=1&sid=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar suscripción?')"><i class="fa fa-times"></i> Cancelar</a>
    </div>
    <?php endif; ?>
</div>
<?php endwhile; ?>
<hr>
<?php endif; ?>

<!-- Planes disponibles -->
<h4><i class="fa fa-tags" style="color:#e8233a"></i> Planes disponibles</h4>
<?php if (!$plans || mysqli_num_rows($plans) === 0): ?>
<div class="alert alert-info">No hay planes de suscripción disponibles actualmente.</div>
<?php else: while ($pl = mysqli_fetch_assoc($plans)): ?>
<div class="plan-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
        <div>
            <h5 style="margin:0 0 6px;font-weight:700"><?php echo htmlspecialchars($pl['name']); ?></h5>
            <p style="color:#888;font-size:13px;margin:0"><?php echo htmlspecialchars($pl['description']); ?></p>
            <?php if ($pl['productName']): ?><div style="font-size:12px;margin-top:4px"><i class="fa fa-tag" style="color:#888"></i> Incluye: <?php echo htmlspecialchars($pl['productName']); ?></div><?php endif; ?>
        </div>
        <div style="text-align:right;min-width:140px">
            <div>
                <span class="plan-price">$<?php echo number_format($pl['price_monthly'], 0, '.', ','); ?></span>
                <span style="font-size:13px;color:#888">/mes</span>
                <?php if ($pl['discount_pct'] > 0): ?><span class="plan-disc"><?php echo $pl['discount_pct']; ?>% OFF</span><?php endif; ?>
            </div>
            <form method="post" style="margin-top:10px">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="plan_id" value="<?php echo $pl['id']; ?>">
                <button type="submit" name="subscribe_plan" class="btn btn-primary btn-sm"><i class="fa fa-check"></i> Suscribirme</button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>

</div>
</div>
<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/toast.js"></script>
</body>
</html>
