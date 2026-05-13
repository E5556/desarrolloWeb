<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if (empty($_SESSION['login'])) { header('location:login.php'); exit(); }
if (empty($_SESSION['cart']))  { header('location:my-cart.php'); exit(); }

$uid = intval($_SESSION['id']);
include_once('includes/points.php');

// ── Procesar orden ───────────────────────────────────────────────────────────
if (isset($_POST['checkout_submit'])) {
    csrf_verify();
    $paymethod  = safe_str($con, $_POST['payment_method'] ?? 'COD');
    $ship_addr  = safe_str($con, $_POST['shippingaddress'] ?? '');
    $ship_city  = safe_str($con, $_POST['shippingcity']   ?? '');
    $ship_state = safe_str($con, $_POST['shippingstate']  ?? '');
    $ship_pin   = safe_str($con, $_POST['shippingpincode']?? '');

    if ($ship_addr === '' || $ship_city === '') {
        $form_error = 'Por favor completa la dirección de envío.';
    } else {
        // Actualizar dirección de envío
        $stmt_up = mysqli_prepare($con, "UPDATE users SET shippingAddress=?,shippingCity=?,shippingState=?,shippingPincode=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_up, 'ssssi', $ship_addr, $ship_city, $ship_state, $ship_pin, $uid);
        mysqli_stmt_execute($stmt_up); mysqli_stmt_close($stmt_up);

        // Insertar órdenes
        $stmt_ord = mysqli_prepare($con, "INSERT INTO orders(userId,productId,quantity) VALUES(?,?,?)");
        foreach ($_SESSION['cart'] as $pid => $item) {
            $pid_i = intval($pid); $qty_i = intval($item['quantity']);
            mysqli_stmt_bind_param($stmt_ord, 'iii', $uid, $pid_i, $qty_i);
            mysqli_stmt_execute($stmt_ord);
        }
        mysqli_stmt_close($stmt_ord);

        // Registrar método de pago en las órdenes recién creadas
        $last_id = mysqli_insert_id($con);
        mysqli_query($con, "UPDATE orders SET paymentMethod='" . mysqli_real_escape_string($con, $paymethod) . "' WHERE userId=$uid AND paymentMethod IS NULL");

        // Cupón
        if (!empty($_SESSION['coupon']['id'])) {
            mysqli_query($con, "UPDATE coupons SET uses_count=uses_count+1 WHERE id=" . intval($_SESSION['coupon']['id']));
        }

        // Puntos y retos
        $_cart_total = 0;
        foreach ($_SESSION['cart'] as $pid => $item) {
            $pr = mysqli_query($con, "SELECT productPrice, shippingCharge FROM products WHERE id=" . intval($pid));
            if ($pr && $row_p = mysqli_fetch_assoc($pr)) {
                $_cart_total += ($row_p['productPrice'] + $row_p['shippingCharge']) * $item['quantity'];
            }
        }
        $pts_earn = max(1, intval($_cart_total / 1000));
        ps_points_add($con, $uid, $pts_earn, 'Compra — checkout rápido');
        include_once('includes/challenges.php');
        ps_challenge_tick($con, $uid, 'orders', 1);
        ps_challenge_tick($con, $uid, 'spend', intval($_cart_total));

        unset($_SESSION['cart'], $_SESSION['coupon'], $_SESSION['points_redeemed'], $_SESSION['points_discount'], $_SESSION['level_discount'], $_SESSION['cat_discount']);
        header('location:order-history.php');
        exit();
    }
}

// ── Datos del usuario ────────────────────────────────────────────────────────
$u_q = mysqli_query($con, "SELECT * FROM users WHERE id=$uid LIMIT 1");
$u   = $u_q ? mysqli_fetch_assoc($u_q) : [];

// ── Calcular totales del carrito ─────────────────────────────────────────────
$cart_items = [];
$subtotal   = 0;
foreach ($_SESSION['cart'] as $pid => $item) {
    $pr = mysqli_query($con, "SELECT id, productName, productPrice, shippingCharge, productImage FROM products WHERE id=" . intval($pid));
    if ($pr && $row_p = mysqli_fetch_assoc($pr)) {
        $row_p['qty'] = $item['quantity'];
        $row_p['line_total'] = ($row_p['productPrice'] + $row_p['shippingCharge']) * $item['quantity'];
        $subtotal += $row_p['line_total'];
        $cart_items[] = $row_p;
    }
}
$coupon_disc  = isset($_SESSION['coupon']) ? floatval($_SESSION['coupon']['discount']) : 0;
$level_disc   = ps_level_discount($con, $uid) > 0 ? round(($subtotal - $coupon_disc) * ps_level_discount($con, $uid) / 100) : 0;
$grand_total  = max(0, $subtotal - $coupon_disc - $level_disc);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout rápido | <?php echo $_SITE_NAME ?? 'Tienda'; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/cosmetics.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON ?? ''; ?>">
<style>
.onepage-wrap{max-width:960px;margin:30px auto;padding:0 15px;}
.onepage-section{background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:20px 24px;margin-bottom:18px;}
.onepage-section h4{margin:0 0 14px;font-size:15px;font-weight:700;color:#333;border-bottom:1px solid #f0f0f0;padding-bottom:8px}
.cart-line{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f8f8f8;font-size:13px}
.cart-line:last-child{border:none}
.cart-line img{width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #eee}
.total-row{display:flex;justify-content:space-between;padding:5px 0;font-size:13px}
.total-final{font-size:16px;font-weight:700;color:#337ab7;border-top:2px solid #eee;padding-top:10px;margin-top:6px}
.pay-option{display:flex;align-items:center;gap:10px;border:1px solid #ddd;border-radius:8px;padding:10px 14px;cursor:pointer;margin-bottom:8px;transition:border .15s}
.pay-option:hover,.pay-option.selected{border-color:#337ab7;background:#f0f7ff}
.pay-option input{margin:0}
</style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<div class="breadcrumb">
  <div class="container">
    <ul class="list-inline list-unstyled">
      <li><a href="index2.php">Inicio</a></li>
      <li><a href="my-cart.php">Carrito</a></li>
      <li class="active">Checkout</li>
    </ul>
  </div>
</div>

<div class="body-content outer-top-bd">
<div class="onepage-wrap">

<?php if (!empty($form_error)): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($form_error); ?></div>
<?php endif; ?>

<form method="post">
<?php echo csrf_field(); ?>
<div class="row">

<!-- IZQUIERDA: datos -->
<div class="col-md-7">

  <!-- Dirección de envío -->
  <div class="onepage-section">
    <h4><i class="fa fa-truck"></i> Dirección de envío</h4>
    <div class="form-group">
      <label>Dirección <span class="text-danger">*</span></label>
      <input type="text" name="shippingaddress" class="form-control" required value="<?php echo htmlspecialchars($u['shippingAddress'] ?? ''); ?>">
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Ciudad <span class="text-danger">*</span></label>
          <input type="text" name="shippingcity" class="form-control" required value="<?php echo htmlspecialchars($u['shippingCity'] ?? ''); ?>">
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Departamento</label>
          <input type="text" name="shippingstate" class="form-control" value="<?php echo htmlspecialchars($u['shippingState'] ?? ''); ?>">
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Código postal</label>
      <input type="text" name="shippingpincode" class="form-control" value="<?php echo htmlspecialchars($u['shippingPincode'] ?? ''); ?>">
    </div>
  </div>

  <!-- Método de pago -->
  <div class="onepage-section">
    <h4><i class="fa fa-credit-card"></i> Método de pago</h4>
    <label class="pay-option selected" id="pay-cod">
      <input type="radio" name="payment_method" value="COD" checked> <i class="fa fa-money fa-lg" style="color:#27ae60"></i> Pago contra entrega
    </label>
    <?php
    $mp_q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='mp_access_token' LIMIT 1");
    $mp_tok = $mp_q ? (mysqli_fetch_assoc($mp_q)['setting_value'] ?? '') : '';
    if (!empty($mp_tok)):
    ?>
    <label class="pay-option" id="pay-mp">
      <input type="radio" name="payment_method" value="MercadoPago"> <img src="assets/images/mercadopago.svg" height="22" onerror="this.style.display='none'"> <strong style="color:#009ee3">MercadoPago</strong>
    </label>
    <?php endif; ?>
    <label class="pay-option" id="pay-bank">
      <input type="radio" name="payment_method" value="BankTransfer"> <i class="fa fa-university fa-lg" style="color:#555"></i> Transferencia bancaria
    </label>
  </div>

</div>

<!-- DERECHA: resumen -->
<div class="col-md-5">
  <div class="onepage-section">
    <h4><i class="fa fa-shopping-cart"></i> Resumen del pedido</h4>
    <?php foreach ($cart_items as $ci): ?>
    <div class="cart-line">
      <img src="admin/productimages/<?php echo htmlspecialchars($ci['productImage'] ?? ''); ?>" onerror="this.src='assets/images/no-image.jpg'">
      <span style="flex:1"><?php echo htmlspecialchars($ci['productName']); ?> ×<?php echo $ci['qty']; ?></span>
      <strong>$<?php echo number_format($ci['line_total'], 0, '.', ','); ?></strong>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:12px">
      <div class="total-row"><span>Subtotal:</span><span>$<?php echo number_format($subtotal, 0, '.', ','); ?></span></div>
      <?php if ($coupon_disc > 0): ?>
      <div class="total-row" style="color:#e8233a"><span>Cupón:</span><span>−$<?php echo number_format($coupon_disc, 0, '.', ','); ?></span></div>
      <?php endif; ?>
      <?php if ($level_disc > 0): ?>
      <div class="total-row" style="color:#9b59b6"><span>Descuento nivel:</span><span>−$<?php echo number_format($level_disc, 0, '.', ','); ?></span></div>
      <?php endif; ?>
      <div class="total-row total-final"><span>Total:</span><span>$<?php echo number_format($grand_total, 0, '.', ','); ?></span></div>
    </div>

    <button type="submit" name="checkout_submit" class="btn btn-primary btn-block" style="margin-top:16px;font-size:15px;padding:12px">
      <i class="fa fa-lock"></i> Confirmar pedido
    </button>
    <a href="my-cart.php" class="btn btn-default btn-block" style="margin-top:8px">← Volver al carrito</a>
  </div>
</div>

</div>
</form>
</div>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/toast.js"></script>
<script>
// Resaltar opción de pago seleccionada
$('input[name=payment_method]').on('change', function(){
    $('.pay-option').removeClass('selected');
    $(this).closest('.pay-option').addClass('selected');
});
</script>
</body>
</html>
