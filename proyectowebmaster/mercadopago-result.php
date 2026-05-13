<?php
/**
 * Página de retorno desde MercadoPago
 * MP redirige aquí con ?collection_id, ?collection_status, ?payment_type, etc.
 */
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
include_once('includes/points.php');

if (empty($_SESSION['login'])) { header('location:login.php'); exit(); }

$_status      = $_GET['status']            ?? '';
$_payment_id  = intval($_GET['collection_id'] ?? 0);
$_mp_status   = $_GET['collection_status'] ?? '';
$_uid         = intval($_SESSION['id']);

// Solo procesar si el pago fue aprobado (approved)
if ($_status === 'success' && $_mp_status === 'approved' && $_payment_id > 0) {

    // Verificar que no se haya procesado ya
    if (empty($_SESSION['mp_processed_' . $_payment_id])) {
        $_SESSION['mp_processed_' . $_payment_id] = true;

        // Marcar órdenes como pagadas con MercadoPago
        $stmt_mp = mysqli_prepare($con,
            "UPDATE orders SET paymentMethod='MercadoPago', mp_payment_id=? WHERE userId=? AND paymentMethod IS NULL");
        // Si la columna mp_payment_id no existe, se ignora el error (error_reporting=0)
        // Crear columna si no existe
        mysqli_query($con, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS mp_payment_id BIGINT DEFAULT NULL");
        $stmt_mp = mysqli_prepare($con,
            "UPDATE orders SET paymentMethod='MercadoPago', mp_payment_id=? WHERE userId=? AND paymentMethod IS NULL");
        mysqli_stmt_bind_param($stmt_mp, 'ii', $_payment_id, $_uid);
        mysqli_stmt_execute($stmt_mp);
        mysqli_stmt_close($stmt_mp);

        // Descontar stock
        $_sq = mysqli_query($con, "SELECT productId, quantity FROM orders WHERE userId=$_uid AND paymentMethod='MercadoPago' AND mp_payment_id=$_payment_id");
        while ($_sr = mysqli_fetch_assoc($_sq)) {
            $_spid = intval($_sr['productId']); $_sqty = intval($_sr['quantity']);
            mysqli_query($con, "UPDATE products SET stock_qty = GREATEST(0, stock_qty - $_sqty) WHERE id=$_spid AND stock_qty IS NOT NULL");
            mysqli_query($con, "UPDATE products SET productAvailability='Out of Stock' WHERE id=$_spid AND stock_qty IS NOT NULL AND stock_qty=0");
        }

        // Acumular puntos
        $_tot_q = mysqli_query($con, "SELECT COALESCE(SUM(o.quantity*p.productPrice),0) t FROM orders o JOIN products p ON o.productId=p.id WHERE o.userId=$_uid AND o.mp_payment_id=$_payment_id");
        $_tot   = floatval(mysqli_fetch_assoc($_tot_q)['t'] ?? 0);
        if ($_tot > 0) {
            ps_points_add($con, $_uid, max(1, intval($_tot/1000)), 'Compra MercadoPago #' . $_payment_id);
        }

        // Enviar email de confirmación
        $_uq = mysqli_query($con, "SELECT name, email FROM users WHERE id=$_uid");
        if ($_uq && $_urow = mysqli_fetch_assoc($_uq)) {
            $_oq = mysqli_query($con, "SELECT o.quantity, p.productName, p.productPrice FROM orders o JOIN products p ON p.id=o.productId WHERE o.userId=$_uid AND o.mp_payment_id=$_payment_id");
            $_items = []; $_itot = 0;
            while ($_oq && $_or = mysqli_fetch_assoc($_oq)) {
                $_items[] = ['name'=>$_or['productName'],'qty'=>$_or['quantity'],'price'=>$_or['productPrice']];
                $_itot += $_or['quantity'] * $_or['productPrice'];
            }
            if (!empty($_items)) {
                include_once('includes/mailer.php');
                send_order_confirmation($con, $_urow['email'], $_urow['name'], $_items, $_itot, 'MercadoPago');
            }
        }

        include_once('includes/cart.php'); ps_cart_clear($con, intval($_SESSION['id']));
        unset($_SESSION['cart']);
        $_msg_type = 'success';
        $_msg = '¡Pago realizado con éxito! Recibirás un correo de confirmación.';
    } else {
        $_msg_type = 'info';
        $_msg = 'Este pago ya fue procesado anteriormente.';
    }

} elseif ($_status === 'pending') {
    $_msg_type = 'warning';
    $_msg = 'Tu pago está pendiente de confirmación. Te notificaremos cuando sea aprobado.';
} else {
    $_msg_type = 'error';
    $_msg = 'El pago no se completó. Puedes intentarlo de nuevo.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resultado del pago | <?php echo $_SITE_NAME; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/cosmetics.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); include('includes/main-header.php'); include('includes/menu-bar.php'); ?>
</header>

<div class="body-content outer-top-xs">
<div class="container">
<div class="row">
<div class="col-md-8 col-md-offset-2" style="margin-top:40px;margin-bottom:60px;text-align:center">

<?php if ($_msg_type === 'success'): ?>
<div style="font-size:72px;margin-bottom:20px">✅</div>
<h2 style="color:#27ae60">¡Pago exitoso!</h2>
<?php elseif ($_msg_type === 'warning'): ?>
<div style="font-size:72px;margin-bottom:20px">⏳</div>
<h2 style="color:#f39c12">Pago pendiente</h2>
<?php else: ?>
<div style="font-size:72px;margin-bottom:20px">❌</div>
<h2 style="color:#e74c3c">Pago no completado</h2>
<?php endif; ?>

<p style="font-size:16px;color:#555;margin:16px 0 32px"><?php echo htmlspecialchars($_msg); ?></p>

<div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a href="order-history.php" class="btn btn-primary">Ver mis pedidos</a>
    <?php if ($_msg_type !== 'success'): ?>
    <a href="my-cart.php" class="btn btn-default">Volver al carrito</a>
    <?php endif; ?>
    <a href="index2.php" class="btn btn-default">Seguir comprando</a>
</div>

</div>
</div>
</div>
</div>
<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/scripts.js"></script>
</body>
</html>
