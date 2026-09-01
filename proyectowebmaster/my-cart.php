<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if(isset($_POST['cartupdate'])){
		// Remover ítems marcados con checkbox
		if(isset($_POST['remove_code']) && !empty($_SESSION['cart'])){
			foreach($_POST['remove_code'] as $key){
				unset($_SESSION['cart'][intval($key)]);
			}
		}
		// Actualizar cantidades — buscar clave exacta en sesión (simple o compuesta)
		if(!empty($_SESSION['cart']) && isset($_POST['quantity'])){
			foreach($_POST['quantity'] as $key => $val){
				$val = intval($val);
				// Buscar clave que empiece con este pid
				$pid_k = intval($key);
				foreach (array_keys($_SESSION['cart']) as $_ck) {
					$_ckpid = strpos($_ck,'_')!==false ? intval(explode('_',$_ck,2)[0]) : intval($_ck);
					if ($_ckpid === $pid_k) {
						if ($val <= 0) unset($_SESSION['cart'][$_ck]);
						else $_SESSION['cart'][$_ck]['quantity'] = $val;
					}
				}
			}
		}
		if(!empty($_SESSION['login'])){include_once('includes/cart.php');ps_cart_save($con,intval($_SESSION['id']));}
		header('location:my-cart.php');
		exit();
	}
// code for insert product in order table


if(isset($_POST['ordersubmit']))
{
if(empty($_SESSION['login']))
    {
header('location:login.php');
}
else{
    $quantity = $_POST['quantity'];
    $pdd      = $_SESSION['pid'];
    $value    = array_combine($pdd, $quantity);
    $uid      = intval($_SESSION['id']);

    $stmt_ord = mysqli_prepare($con, "INSERT INTO orders(userId,productId,quantity) VALUES(?,?,?)");
    foreach($value as $qty => $val34){
        $pid_o = intval($qty);
        $qty_o = intval($val34);
        mysqli_stmt_bind_param($stmt_ord, 'iii', $uid, $pid_o, $qty_o);
        mysqli_stmt_execute($stmt_ord);
    }
    mysqli_stmt_close($stmt_ord);
    // Registrar uso del cupón si aplica
    if (!empty($_SESSION['coupon']['id'])) {
        $cid = intval($_SESSION['coupon']['id']);
        mysqli_query($con, "UPDATE coupons SET uses_count=uses_count+1 WHERE id=$cid");
    }
    // Descontar puntos canjeados si aplica
    if (!empty($_SESSION['points_redeemed']) && !empty($_SESSION['login'])) {
        include_once('includes/points.php');
        $pts_used = intval($_SESSION['points_redeemed']);
        ps_points_add($con, intval($_SESSION['id']), -$pts_used, 'Puntos canjeados en compra');
        unset($_SESSION['points_redeemed'], $_SESSION['points_discount']);
    }
    header('location:payment-method.php');
    exit();
}
}

// code for billing address updation
	if(isset($_POST['update']))
	{
		$baddress = safe_str($con, $_POST['billingaddress'] ?? '');
		$bstate   = safe_str($con, $_POST['bilingstate']    ?? '');
		$bcity    = safe_str($con, $_POST['billingcity']    ?? '');
		$bpincode = safe_str($con, $_POST['billingpincode'] ?? '');
		$uid_b    = intval($_SESSION['id']);
		$stmt_b = mysqli_prepare($con, "UPDATE users SET billingAddress=?,billingState=?,billingCity=?,billingPincode=? WHERE id=?");
		mysqli_stmt_bind_param($stmt_b, 'ssssi', $baddress, $bstate, $bcity, $bpincode, $uid_b);
		if(mysqli_stmt_execute($stmt_b)) {
			echo "<script>showToast('Dirección de facturación actualizada','success');</script>";
		}
		mysqli_stmt_close($stmt_b);
	}

// code for Shipping address updation
	if(isset($_POST['shipupdate']))
	{
		$saddress = safe_str($con, $_POST['shippingaddress'] ?? '');
		$sstate   = safe_str($con, $_POST['shippingstate']   ?? '');
		$scity    = safe_str($con, $_POST['shippingcity']    ?? '');
		$spincode = safe_str($con, $_POST['shippingpincode'] ?? '');
		$uid_s    = intval($_SESSION['id']);
		$stmt_s = mysqli_prepare($con, "UPDATE users SET shippingAddress=?,shippingState=?,shippingCity=?,shippingPincode=? WHERE id=?");
		mysqli_stmt_bind_param($stmt_s, 'ssssi', $saddress, $sstate, $scity, $spincode, $uid_s);
		if(mysqli_stmt_execute($stmt_s)) {
			echo "<script>showToast('Dirección de envío actualizada','success');</script>";
		}
		mysqli_stmt_close($stmt_s);
	}

// Categorías y subcategorías para carrito vacío
$_empty_suggestions = [];
$_res_cats = mysqli_query($con, "SELECT id, categoryName FROM category ORDER BY RAND() LIMIT 4");
while ($_rc = mysqli_fetch_assoc($_res_cats)) $_empty_suggestions[] = ['type'=>'cat','id'=>$_rc['id'],'name'=>$_rc['categoryName']];
$_res_sub = mysqli_query($con, "SELECT id, subcategory AS subcategoryName FROM subcategory ORDER BY RAND() LIMIT 4");
while ($_rs = mysqli_fetch_assoc($_res_sub)) $_empty_suggestions[] = ['type'=>'sub','id'=>$_rs['id'],'name'=>$_rs['subcategoryName']];
shuffle($_empty_suggestions);
$_empty_suggestions = array_slice($_empty_suggestions, 0, 6);
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

	    <title>Mi carrito | <?php echo $_SITE_NAME; ?></title>
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
	<link rel="stylesheet" href="assets/css/cart-drawer.css">

		<!-- Demo Purpose Only. Should be removed in production -->
		<link rel="stylesheet" href="assets/css/config.css">

		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<!-- Demo Purpose Only. Should be removed in production : END -->

		
		<!-- Icons/Glyphs -->
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">

        <!-- Fonts --> 
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		
		<!-- Favicon -->
		<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">

		<!-- HTML5 elements and media queries Support for IE8 : HTML5 shim and Respond.js -->
		<!--[if lt IE 9]>
			<script src="assets/js/html5shiv.js"></script>
			<script src="assets/js/respond.min.js"></script>
		<![endif]-->

	<style>
/* =========================================================
   CARRITO MODERNO — tabla limpia, cards de producto
   ========================================================= */

/* ── Contenedor general ── */
.body-content { background: #f5f6fa; }

/* ── Tabla de productos ── */
.cart-modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 18px rgba(0,0,0,.07);
    overflow: hidden;
    margin-bottom: 0;
}
.cart-modern-table thead tr {
    background: #2c3e50;
    color: #fff;
}
.cart-modern-table thead th {
    padding: 14px 16px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    border: none;
    white-space: nowrap;
}
.cart-modern-table tbody tr {
    border-bottom: 1px solid #f0f0f5;
    transition: background .15s;
}
.cart-modern-table tbody tr:last-child { border-bottom: none; }
.cart-modern-table tbody tr:hover { background: #fafbff; }
.cart-modern-table td {
    padding: 18px 16px;
    vertical-align: middle;
    border: none;
    font-size: 13px;
    color: #444;
}

/* ── Imagen producto ── */
.cart-img-wrap {
    width: 72px; height: 88px;
    border-radius: 8px; overflow: hidden;
    background: #f5f5f5; flex-shrink: 0;
}
.cart-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

/* ── Nombre + variante ── */
.cart-product-name a {
    font-weight: 700; font-size: 14px; color: #2c3e50;
    text-decoration: none; line-height: 1.3;
}
.cart-product-name a:hover { color: #e8233a; }
.cart-variant-tag {
    display: inline-block; margin-top: 4px;
    background: #eef2ff; color: #5c6bc0;
    border-radius: 20px; padding: 2px 10px; font-size: 11px; font-weight: 600;
}
.cart-custom-tag {
    margin-top: 6px; background: #f5f5f5; border-radius: 6px;
    padding: 5px 10px; font-size: 11px; color: #555;
}

/* ── Precios ── */
.cart-price { font-weight: 700; font-size: 14px; color: #2c3e50; }
.cart-ship  { font-size: 12px; color: #888; }
.cart-total { font-weight: 800; font-size: 15px; color: #e8233a; }

/* ── Quantity stepper ── */
.qty-stepper {
    display: flex; align-items: center; gap: 0;
    border: 1.5px solid #dde; border-radius: 8px;
    overflow: hidden; width: fit-content;
}
.qty-stepper button {
    background: #f5f6fa; border: none; width: 32px; height: 36px;
    font-size: 16px; font-weight: 700; color: #555; cursor: pointer;
    transition: background .15s;
    display: flex; align-items: center; justify-content: center;
}
.qty-stepper button:hover { background: #e8233a; color: #fff; }
.qty-stepper input {
    width: 44px; height: 36px; border: none;
    text-align: center; font-size: 14px; font-weight: 700; color: #2c3e50;
    background: #fff; outline: none;
    -moz-appearance: textfield;
}
.qty-stepper input::-webkit-inner-spin-button,
.qty-stepper input::-webkit-outer-spin-button { -webkit-appearance: none; }

/* ── Remove button ── */
.btn-remove-item {
    background: none; border: 1.5px solid #f5b7b1; color: #e74c3c;
    border-radius: 7px; width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: .2s; font-size: 14px;
}
.btn-remove-item:hover { background: #e74c3c; color: #fff; border-color: #e74c3c; }

/* ── Footer de la tabla ── */
.cart-tfoot { background: #f8f9fc; padding: 16px 20px; border-top: 1px solid #eee; border-radius: 0 0 14px 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.btn-update-cart { background: #2c3e50; color: #fff; border: none; border-radius: 8px; padding: 10px 22px; font-size: 13px; font-weight: 700; cursor: pointer; transition: .2s; }
.btn-update-cart:hover { background: #1a252f; }
.btn-continue { background: #fff; color: #2c3e50; border: 1.5px solid #dde; border-radius: 8px; padding: 10px 22px; font-size: 13px; font-weight: 600; text-decoration: none; transition: .2s; }
.btn-continue:hover { border-color: #2c3e50; text-decoration: none; color: #2c3e50; }

/* ── Panel lateral: tarjetas ── */
.cart-side-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 18px rgba(0,0,0,.07);
    overflow: hidden; margin-bottom: 20px;
}
.cart-side-card-head {
    background: #2c3e50; color: #fff;
    padding: 14px 20px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
}
.cart-side-card-head i { margin-right: 8px; }
.cart-side-card-body { padding: 20px; }

/* ── Botón de checkout ── */
.btn-checkout {
    display: block; width: 100%;
    background: linear-gradient(135deg, #e8233a, #c0392b);
    color: #fff; border: none; border-radius: 9px;
    padding: 14px; font-size: 15px; font-weight: 700;
    text-align: center; cursor: pointer; transition: opacity .2s;
    margin-top: 10px;
}
.btn-checkout:hover { opacity: .88; }
.btn-quick-checkout {
    display: block; width: 100%;
    background: #fff; color: #2c3e50;
    border: 1.5px solid #dde; border-radius: 9px;
    padding: 11px; font-size: 13px; font-weight: 700;
    text-align: center; text-decoration: none; transition: .2s; margin-top: 8px;
}
.btn-quick-checkout:hover { border-color: #2c3e50; color: #2c3e50; text-decoration: none; }

/* ── Total box ── */
.total-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; font-size: 13px; border-bottom: 1px solid #f5f5f5; }
.total-row:last-child { border-bottom: none; }
.total-row.grand { font-size: 16px; font-weight: 800; color: #e8233a; padding-top: 12px; margin-top: 4px; border-top: 2px solid #f0f0f0; border-bottom: none; }

/* ── Responsivo ── */
@media (max-width: 767px) {
    .cart-modern-table thead { display: none; }
    .cart-modern-table tbody tr { display: block; padding: 14px; margin-bottom: 12px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.07); }
    .cart-modern-table td { display: flex; align-items: center; border: none; padding: 5px 0; font-size: 13px; }
    .cart-modern-table .td-img { justify-content: center; }
    .col-md-4 { width: 100%; }
}
</style>
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
<?php
if(!empty($_SESSION['cart'])){

 $pdtid=array();
 $_cart_pid_map = [];
 foreach ($_SESSION['cart'] as $_ckey => $_citem) {
     $_cpid = strpos($_ckey,'_')!==false ? intval(explode('_',$_ckey,2)[0]) : intval($_ckey);
     if (!isset($_cart_pid_map[$_cpid])) $_cart_pid_map[$_cpid] = $_citem + ['_ckey'=>$_ckey];
 }
 $sql = "SELECT * FROM products WHERE id IN(";
 foreach($_cart_pid_map as $id => $value){ $sql .= $id . ","; }
 $sql = substr($sql,0,-1) . ") ORDER BY id ASC";
 $query = mysqli_query($con,$sql);
 $totalprice = 0; $totalqunty = 0;
 $rows_cache = [];
 if(!empty($query)){
     while($row = mysqli_fetch_array($query)){
         $_ci = $_cart_pid_map[$row['id']];
         $quantity = $_ci['quantity'];
         $_item_price = floatval($_ci['price'] ?? $row['productPrice']);
         $subtotal = $quantity * $_item_price + $row['shippingCharge'];
         $totalprice += $subtotal;
         $_SESSION['qnty'] = $totalqunty += $quantity;
         array_push($pdtid, $row['id']);
         $rows_cache[] = ['row'=>$row,'ci'=>$_ci,'quantity'=>$quantity,'item_price'=>$_item_price,'subtotal'=>$subtotal];
     }
 }
 $_SESSION['pid'] = $pdtid;
?>

<table class="cart-modern-table">
    <thead>
        <tr>
            <th style="width:50px"></th>
            <th style="width:90px"></th>
            <th>Producto</th>
            <th style="width:130px;text-align:center">Cantidad</th>
            <th style="width:130px;text-align:right">Precio unit.</th>
            <th style="width:110px;text-align:right">Envío</th>
            <th style="width:130px;text-align:right">Total</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($rows_cache as $_r):
        $row = $_r['row']; $_ci = $_r['ci'];
        $quantity = $_r['quantity']; $_item_price = $_r['item_price'];
        $pd = $row['id'];
        $rt = mysqli_query($con,"SELECT COUNT(*) as n FROM productreviews WHERE productId='$pd'");
        $num = ($rt && $rr = mysqli_fetch_assoc($rt)) ? intval($rr['n']) : 0;
    ?>
    <tr data-price="<?php echo intval($row['productPrice']); ?>" data-shipping="<?php echo intval($row['shippingCharge']); ?>" data-id="<?php echo $pd; ?>">
        <td class="romove-item" style="text-align:center">
            <button type="button" class="btn-remove-item" title="Eliminar del carrito"
                onclick="if(confirm('¿Eliminar este producto del carrito?')){ var cb=this.closest('tr').querySelector('input[name^=remove_code]'); cb.checked=true; document.querySelector('input[name=cartupdate]').click(); }">
                <i class="fa fa-trash"></i>
            </button>
            <input type="checkbox" name="remove_code[]" value="<?php echo $pd; ?>" style="display:none">
        </td>
        <td class="td-img">
            <a href="product-details.php?pid=<?php echo $pd; ?>">
                <div class="cart-img-wrap">
                    <img src="admin/productimages/<?php echo $pd; ?>/<?php echo htmlspecialchars($row['productImage1']); ?>"
                         onerror="this.style.opacity='.3'" alt="">
                </div>
            </a>
        </td>
        <td class="cart-product-name-info">
            <div class="cart-product-name">
                <a href="product-details.php?pid=<?php echo $pd; ?>"><?php echo htmlspecialchars($row['productName']); $_SESSION['sid']=$pd; ?></a>
            </div>
            <?php if (!empty($_ci['variant_label'])): ?>
            <div class="cart-variant-tag"><?php echo htmlspecialchars($_ci['variant_label']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_ci['customization'])): ?>
            <div class="cart-custom-tag">
                <?php foreach ($_ci['customization'] as $_ck => $_cv): ?>
                <div><strong><?php echo htmlspecialchars($_ck); ?>:</strong> <?php echo htmlspecialchars($_cv); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($num > 0): ?>
            <div style="font-size:11px;color:#aaa;margin-top:6px"><i class="fa fa-star" style="color:#f39c12"></i> <?php echo $num; ?> reseña<?php echo $num>1?'s':''; ?></div>
            <?php endif; ?>
        </td>
        <td style="text-align:center">
            <div class="qty-stepper" style="margin:0 auto">
                <button type="button" class="qty-minus">−</button>
                <input type="number" value="<?php echo $quantity; ?>" name="quantity[<?php echo $pd; ?>]" min="1">
                <button type="button" class="qty-plus">+</button>
            </div>
        </td>
        <td style="text-align:right">
            <div class="cart-price">$<?php echo number_format($_item_price,0,'.',','); ?></div>
            <?php if ($row['shippingCharge'] > 0): ?>
            <div class="cart-ship">+ $<?php echo number_format($row['shippingCharge'],0,'.',','); ?> envío</div>
            <?php else: ?>
            <div style="font-size:11px;color:#27ae60">Envío gratis</div>
            <?php endif; ?>
        </td>
        <td style="text-align:right">
            <div class="cart-ship" style="font-size:12px;color:#999">
                <?php echo $row['shippingCharge'] > 0 ? '$'.number_format($row['shippingCharge'],0,'.',',') : 'Gratis'; ?>
            </div>
        </td>
        <td style="text-align:right">
            <div class="cart-total" id="row-total-<?php echo $pd; ?>">
                $<?php echo number_format($quantity*$_item_price+$row['shippingCharge'],0,'.',','); ?>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="cart-tfoot">
    <input type="submit" name="cartupdate" value="Actualizar carrito" class="btn-update-cart" style="display:none" id="hidden-cartupdate">
    <a href="index2.php" class="btn-continue"><i class="fa fa-arrow-left"></i> Seguir comprando</a>
    <button type="button" class="btn-update-cart" onclick="document.getElementById('hidden-cartupdate').click()">
        <i class="fa fa-refresh"></i> Actualizar carrito
    </button>
</div>
		
	</div>
</div><!-- /.shopping-cart-table -->			<div class="col-md-4 col-sm-12 estimate-ship-tax">
	<?php
	// L1: Tabla de direcciones guardadas
	mysqli_query($con, "CREATE TABLE IF NOT EXISTS user_addresses (
	    id INT AUTO_INCREMENT PRIMARY KEY,
	    user_id INT NOT NULL,
	    label VARCHAR(60) DEFAULT 'Casa',
	    address TEXT NOT NULL,
	    state VARCHAR(80) DEFAULT '',
	    city VARCHAR(80) DEFAULT '',
	    pincode VARCHAR(20) DEFAULT '',
	    INDEX(user_id)
	)");
	$_uid_c = intval($_SESSION['id']);
	$_addrs = mysqli_query($con, "SELECT * FROM user_addresses WHERE user_id=$_uid_c ORDER BY id");
	$_addr_rows = [];
	while ($_ar = mysqli_fetch_assoc($_addrs)) $_addr_rows[] = $_ar;

	// Guardar nueva dirección
	if (isset($_POST['save_address'])) {
	    $al = trim(substr($_POST['addr_label']??'Casa',0,60));
	    $aa = trim($_POST['addr_address']??'');
	    $as = trim($_POST['addr_state']??'');
	    $ac = trim($_POST['addr_city']??'');
	    $ap = trim($_POST['addr_pincode']??'');
	    if ($aa !== '') {
	        $stmt_a = mysqli_prepare($con,"INSERT INTO user_addresses (user_id,label,address,state,city,pincode) VALUES(?,?,?,?,?,?)");
	        mysqli_stmt_bind_param($stmt_a,'isssss',$_uid_c,$al,$aa,$as,$ac,$ap);
	        mysqli_stmt_execute($stmt_a);
	        mysqli_stmt_close($stmt_a);
	        header('location:my-cart.php'); exit();
	    }
	}
	// Eliminar dirección
	if (isset($_GET['del_addr'])) {
	    mysqli_query($con,"DELETE FROM user_addresses WHERE id=".intval($_GET['del_addr'])." AND user_id=$_uid_c");
	    header('location:my-cart.php'); exit();
	}
	?>
	<div class="cart-side-card">
    <div class="cart-side-card-head"><i class="fa fa-map-marker"></i> Dirección de facturación</div>
    <div class="cart-side-card-body">
        <!-- Direcciones guardadas -->
        <?php if (!empty($_addr_rows)): ?>
        <p style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Guardadas</p>
        <?php foreach($_addr_rows as $_ar): ?>
        <div style="border:1px solid #eee;border-radius:8px;padding:10px 14px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;background:#fafafa">
            <div>
                <strong style="font-size:13px"><?php echo htmlspecialchars($_ar['label']); ?></strong><br>
                <small style="color:#777;font-size:11px"><?php echo htmlspecialchars($_ar['address']); ?><?php if($_ar['city']) echo ', '.htmlspecialchars($_ar['city']); ?></small>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                <button type="button" class="btn btn-xs btn-primary" onclick="fillAddress('<?php echo addslashes($_ar['address']); ?>','<?php echo addslashes($_ar['state']); ?>','<?php echo addslashes($_ar['city']); ?>','<?php echo addslashes($_ar['pincode']); ?>')">Usar</button>
                <a href="my-cart.php?del_addr=<?php echo $_ar['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')">✕</a>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid #f0f0f0;margin:14px 0 12px"></div>
        <?php endif; ?>

        <?php
        $query=mysqli_query($con,"SELECT * FROM users WHERE id=".intval($_SESSION['id']));
        $row=mysqli_fetch_array($query);
        ?>
        <div class="form-group" style="margin-bottom:10px">
            <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Dirección *</label>
            <textarea class="form-control" id="f-address" name="billingaddress" rows="2" style="font-size:13px;border-radius:7px"><?php echo htmlspecialchars($row['billingAddress']??''); ?></textarea>
        </div>
        <div style="display:flex;gap:8px;margin-bottom:10px">
            <div class="form-group" style="flex:1;margin:0">
                <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Departamento *</label>
                <input type="text" class="form-control" id="f-state" name="bilingstate" value="<?php echo htmlspecialchars($row['billingState']??''); ?>" style="font-size:13px;border-radius:7px">
            </div>
            <div class="form-group" style="flex:1;margin:0">
                <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Ciudad *</label>
                <input type="text" class="form-control" id="f-city" name="billingcity" value="<?php echo htmlspecialchars($row['billingCity']??''); ?>" style="font-size:13px;border-radius:7px">
            </div>
        </div>
        <div class="form-group" style="margin-bottom:14px">
            <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Código postal</label>
            <input type="text" class="form-control" id="f-pincode" name="billingpincode" value="<?php echo htmlspecialchars($row['billingPincode']??''); ?>" style="font-size:13px;border-radius:7px">
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="submit" name="update" style="flex:1;background:#2c3e50;color:#fff;border:none;border-radius:8px;padding:10px;font-size:13px;font-weight:700;cursor:pointer">Actualizar</button>
            <button type="button" class="btn btn-default btn-sm" data-toggle="collapse" data-target="#save-addr-form" style="font-size:12px">+ Guardar</button>
        </div>
        <div class="collapse" id="save-addr-form" style="margin-top:12px;padding:12px;background:#f5f6fa;border-radius:8px">
            <input type="text" name="addr_label" class="form-control" placeholder="Etiqueta (Casa, Oficina…)" style="margin-bottom:6px;font-size:13px;border-radius:7px">
            <input type="hidden" name="addr_address" id="save-address-val">
            <input type="hidden" name="addr_state" id="save-state-val">
            <input type="hidden" name="addr_city" id="save-city-val">
            <input type="hidden" name="addr_pincode" id="save-pincode-val">
            <button type="submit" name="save_address" class="btn btn-sm btn-success" onclick="syncSaveAddr()" style="border-radius:7px;font-size:12px">Guardar dirección</button>
        </div>
    </div>
</div>
<script>
function fillAddress(addr,state,city,pincode){
    document.getElementById('f-address').value=addr;
    document.getElementById('f-state').value=state;
    document.getElementById('f-city').value=city;
    document.getElementById('f-pincode').value=pincode;
}
function syncSaveAddr(){
    document.getElementById('save-address-val').value=document.getElementById('f-address').value;
    document.getElementById('save-state-val').value=document.getElementById('f-state').value;
    document.getElementById('save-city-val').value=document.getElementById('f-city').value;
    document.getElementById('save-pincode-val').value=document.getElementById('f-pincode').value;
}
</script>
</div>

<div class="col-md-4 col-sm-12 estimate-ship-tax">
<div class="cart-side-card">
    <div class="cart-side-card-head"><i class="fa fa-truck"></i> Dirección de envío</div>
    <div class="cart-side-card-body">
        <?php
        $query2=mysqli_query($con,"SELECT * FROM users WHERE id=".intval($_SESSION['id']));
        $row2=mysqli_fetch_array($query2);
        ?>
        <div class="form-group" style="margin-bottom:10px">
            <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Dirección *</label>
            <textarea class="form-control" name="shippingaddress" rows="2" style="font-size:13px;border-radius:7px"><?php echo htmlspecialchars($row2['shippingAddress']??''); ?></textarea>
        </div>
        <div style="display:flex;gap:8px;margin-bottom:10px">
            <div class="form-group" style="flex:1;margin:0">
                <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Departamento *</label>
                <input type="text" class="form-control" id="shippingstate" name="shippingstate" value="<?php echo htmlspecialchars($row2['shippingState']??''); ?>" style="font-size:13px;border-radius:7px">
            </div>
            <div class="form-group" style="flex:1;margin:0">
                <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Ciudad *</label>
                <input type="text" class="form-control" id="shippingcity" name="shippingcity" value="<?php echo htmlspecialchars($row2['shippingCity']??''); ?>" style="font-size:13px;border-radius:7px">
            </div>
        </div>
        <div class="form-group" style="margin-bottom:14px">
            <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Código postal</label>
            <input type="text" class="form-control" id="shippingpincode" name="shippingpincode" value="<?php echo htmlspecialchars($row2['shippingPincode']??''); ?>" style="font-size:13px;border-radius:7px">
        </div>
        <button type="submit" name="shipupdate" style="width:100%;background:#2c3e50;color:#fff;border:none;border-radius:8px;padding:10px;font-size:13px;font-weight:700;cursor:pointer">Actualizar dirección</button>
    </div>
</div>
</div>
<div class="col-md-4 col-sm-12 cart-shopping-total">
<div class="cart-side-card">
    <div class="cart-side-card-head"><i class="fa fa-shopping-bag"></i> Resumen del pedido</div>
    <div class="cart-side-card-body">
    <div class="total-row">
        <span style="color:#888">Subtotal productos</span>
        <strong id="cart-page-total">$<?php echo number_format($totalprice, 0, '.', ','); ?></strong>
    </div>
    <div style="height:1px;background:#f0f0f0;margin:4px 0"></div>
    <div id="totals-extra"><!-- cupón, puntos, zona, descuentos se insertan aquí --></div>

    <!-- Cupón -->
    <div style="margin:14px 0 10px">
        <div id="coupon-applied-box" style="<?php echo isset($_SESSION['coupon'])?'':'display:none'; ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;background:#e6f4ea;border:1px solid #c3e6cb;border-radius:8px;padding:10px 12px;margin-bottom:8px">
                <span style="color:#276c3a;font-size:13px"><i class="fa fa-tag"></i> <strong id="coupon-code-display"><?php echo isset($_SESSION['coupon'])?htmlentities($_SESSION['coupon']['code']):''; ?></strong> — −$<strong id="coupon-discount-display"><?php echo isset($_SESSION['coupon'])?number_format($_SESSION['coupon']['discount'],0,'.',','):'0'; ?></strong></span>
                <a href="#" id="btn-remove-coupon" style="color:#c0392b;font-size:12px"><i class="fa fa-times"></i></a>
            </div>
        </div>
        <div id="coupon-form-box" style="<?php echo isset($_SESSION['coupon'])?'display:none':''; ?>">
            <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px">Cupón de descuento</label>
            <div style="display:flex;gap:6px;margin-top:4px">
                <input type="text" id="coupon-input" placeholder="Código" style="flex:1;padding:8px 10px;border:1.5px solid #dde;border-radius:7px;font-size:13px;text-transform:uppercase">
                <button type="button" id="btn-apply-coupon" style="background:#2c3e50;color:#fff;border:none;border-radius:7px;padding:8px 14px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">Aplicar</button>
            </div>
            <div id="coupon-msg" style="font-size:12px;margin-top:5px"></div>
        </div>
    </div>

    <!-- Puntos -->
    <?php if(!empty($_SESSION['login'])): ?>
    <?php include_once('includes/points.php'); $_cart_pts = ps_points_get($con, intval($_SESSION['id'])); ?>
    <?php if($_cart_pts > 0): ?>
    <?php $_pts_disc = intval($_SESSION['points_discount'] ?? 0); $_pts_used = intval($_SESSION['points_redeemed'] ?? 0); ?>
    <div style="margin-bottom:10px">
        <div id="points-applied-box" style="<?php echo $_pts_disc>0?'':'display:none'; ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:10px 12px;margin-bottom:8px">
                <span style="color:#856404;font-size:13px"><i class="fa fa-star" style="color:#f39c12"></i> <strong id="points-used-display"><?php echo $_pts_used; ?></strong> pts — −$<strong id="points-discount-display"><?php echo number_format($_pts_disc,0,'.',','); ?></strong></span>
                <a href="#" id="btn-remove-points" style="color:#c0392b;font-size:12px"><i class="fa fa-times"></i></a>
            </div>
        </div>
        <div id="points-form-box" style="<?php echo $_pts_disc>0?'display:none':''; ?>">
            <label style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.4px"><i class="fa fa-star" style="color:#f39c12"></i> Tienes <?php echo number_format($_cart_pts); ?> puntos (=$<?php echo number_format($_cart_pts*10,0,'.',','); ?>)</label>
            <div style="display:flex;gap:6px;margin-top:4px">
                <input type="number" id="points-input" min="1" max="<?php echo $_cart_pts; ?>" value="<?php echo $_cart_pts; ?>" style="flex:1;padding:8px 10px;border:1.5px solid #dde;border-radius:7px;font-size:13px">
                <button type="button" id="btn-apply-points" style="background:#f39c12;color:#fff;border:none;border-radius:7px;padding:8px 14px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">Canjear</button>
            </div>
            <div id="points-msg" style="font-size:12px;margin-top:4px;color:#aaa">1 punto = $10 de descuento</div>
        </div>
    </div>
    <?php endif; endif; ?>

    <!-- Envío por zona -->
    <?php
    $_zone_ship_cost = 0; $_zone_ship_label = ''; $_zone_ship_days = ''; $_zone_ship_free = false;
    if (!empty($_SESSION['login'])) {
        $uid_s = intval($_SESSION['id']);
        include_once('includes/membership.php');
        $_is_premium_cart = ps_membership_active($con, $uid_s);
        $u_ship = mysqli_query($con, "SELECT shippingCity, shippingState FROM users WHERE id=$uid_s LIMIT 1");
        if ($u_ship && $u_s = mysqli_fetch_assoc($u_ship)) {
            $city_s = $u_s['shippingCity'] ?? ''; $dept_s = $u_s['shippingState'] ?? '';
            if ($city_s !== '') {
                include_once('includes/shipping.php');
                $_ship_info = ps_shipping_cost($con, $city_s, $totalprice, $dept_s);
                $_zone_ship_cost  = $_is_premium_cart ? 0 : $_ship_info['cost'];
                $_zone_ship_label = $_ship_info['zone'] ?? '';
                $_zone_ship_days  = $_ship_info['days_min'].'–'.$_ship_info['days_max'].' días';
                $_zone_ship_free  = $_is_premium_cart || $_ship_info['free'];
            }
        }
    } else { $_is_premium_cart = false; }
    $_SESSION['zone_shipping'] = $_zone_ship_cost;
    ?>
    <?php if ($_zone_ship_label !== ''): ?>
    <div class="total-row">
        <span><i class="fa fa-truck" style="color:#3498db;margin-right:4px"></i> Envío <?php echo htmlspecialchars($_zone_ship_label); ?> (<?php echo $_zone_ship_days; ?>)</span>
        <?php if ($_zone_ship_free): ?>
        <strong style="color:#27ae60">¡Gratis!</strong>
        <?php else: ?>
        <strong>$<?php echo number_format($_zone_ship_cost, 0, '.', ','); ?></strong>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Descuento cupón -->
    <div class="total-row" id="coupon-discount-row" style="<?php echo isset($_SESSION['coupon'])?'':'display:none'; ?>;color:#e8233a">
        <span><i class="fa fa-tag"></i> Descuento cupón</span>
        <strong>−$<span id="coupon-discount-line"><?php echo isset($_SESSION['coupon'])?number_format($_SESSION['coupon']['discount'],0,'.',','):'0'; ?></span></strong>
    </div>

    <!-- Descuento categoría -->
    <?php
    $_cat_discount_total = 0; $_cat_discount_labels = [];
    if (!empty($_SESSION['cart'])) {
        $dr_q = mysqli_query($con, "SELECT dr.*, c.catName FROM category_discounts dr JOIN category c ON dr.cat_id=c.id WHERE dr.active=1");
        while ($dr = mysqli_fetch_assoc($dr_q)) {
            $cat_count = 0; $cat_subtotal = 0;
            foreach ($_SESSION['cart'] as $cart_ckey => $cart_item) {
                $cart_pid = strpos($cart_ckey,'_')!==false ? intval(explode('_',$cart_ckey,2)[0]) : intval($cart_ckey);
                $cpq = mysqli_query($con, "SELECT catId, productPrice FROM products WHERE id=" . $cart_pid);
                if ($cpq && $cprow = mysqli_fetch_assoc($cpq)) {
                    if ($cprow['catId'] == $dr['cat_id']) { $cat_count += $cart_item['quantity']; $cat_subtotal += $cart_item['quantity'] * floatval($cprow['productPrice']); }
                }
            }
            if ($cat_count >= $dr['min_qty']) {
                $disc = $cat_subtotal * ($dr['discount_pct'] / 100);
                $_cat_discount_total += $disc;
                $_cat_discount_labels[] = ($dr['label'] ?: $dr['catName'].' '.$dr['discount_pct'].'%').' (−$'.number_format($disc,0,'.',',').')';
            }
        }
    }
    $_SESSION['cat_discount'] = $_cat_discount_total;
    ?>
    <?php foreach($_cat_discount_labels as $_cdl): ?>
    <div class="total-row" style="color:#27ae60"><span><i class="fa fa-tag"></i> <?php echo htmlspecialchars($_cdl); ?></span></div>
    <?php endforeach; ?>

    <!-- Descuento nivel -->
    <?php
    $_level_disc_pct = 0; $_level_disc_amt = 0; $_level_name_cart = '';
    if (!empty($_SESSION['login'])) {
        include_once('includes/points.php');
        $_level_disc_pct = ps_level_discount($con, intval($_SESSION['id']));
        if ($_level_disc_pct > 0) {
            $_base_for_level = $totalprice - $_cat_discount_total;
            if (isset($_SESSION['coupon'])) $_base_for_level = $_SESSION['coupon']['final'] - $_cat_discount_total;
            $_level_disc_amt = round($_base_for_level * $_level_disc_pct / 100);
            $_pts_lv = ps_points_get($con, intval($_SESSION['id']));
            $_level_name_cart = ps_level_info($_pts_lv)['name'];
        }
    }
    $_SESSION['level_discount'] = $_level_disc_amt;
    ?>
    <?php if ($_level_disc_amt > 0): ?>
    <div class="total-row" style="color:#9b59b6">
        <span>🏅 Nivel <?php echo htmlspecialchars($_level_name_cart); ?> (<?php echo $_level_disc_pct; ?>%)</span>
        <strong>−$<?php echo number_format($_level_disc_amt,0,'.',','); ?></strong>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['points_discount']) && $_SESSION['points_discount'] > 0): ?>
    <div class="total-row" id="points-discount-row" style="color:#f39c12">
        <span><i class="fa fa-star"></i> Puntos canjeados</span>
        <strong>−$<span id="points-discount-line"><?php echo number_format($_SESSION['points_discount'],0,'.',','); ?></span></strong>
    </div>
    <?php else: ?>
    <div class="total-row" id="points-discount-row" style="display:none;color:#f39c12">
        <span><i class="fa fa-star"></i> Puntos canjeados</span>
        <strong>−$<span id="points-discount-line">0</span></strong>
    </div>
    <?php endif; ?>

    <!-- Calcular envío -->
    <div style="background:#f5f6fa;border-radius:8px;padding:12px;margin:14px 0">
        <strong style="font-size:12px;color:#555"><i class="fa fa-truck"></i> Calcular envío estimado</strong>
        <div style="display:flex;gap:6px;margin-top:8px">
            <input type="text" id="sc-city" placeholder="Ciudad" class="form-control input-sm" style="flex:1;border-radius:6px;font-size:12px">
            <input type="text" id="sc-dept" placeholder="Dpto." class="form-control input-sm" style="width:90px;border-radius:6px;font-size:12px">
            <button type="button" id="sc-btn" class="btn btn-default btn-sm" style="border-radius:6px;font-size:12px">Calcular</button>
        </div>
        <div id="sc-result" style="margin-top:8px;font-size:12px;display:none"></div>
    </div>

    <!-- TOTAL -->
    <div class="total-row grand">
        <span>Total a pagar</span>
        <span id="cart-final-total">
            $<?php
            $_base_total = $totalprice - $_cat_discount_total;
            if (isset($_SESSION['coupon'])) $_base_total = $_SESSION['coupon']['final'] - $_cat_discount_total;
            if (!empty($_SESSION['bundle_discount']['amount'])) $_base_total -= floatval($_SESSION['bundle_discount']['amount']);
            echo number_format(max(0, $_base_total - $_level_disc_amt), 0, '.', ',');
            ?>
        </span>
    </div>

    <button type="submit" name="ordersubmit" class="btn-checkout">
        <i class="fa fa-lock"></i> Proceder al pago
    </button>
    <a href="checkout-onepage.php" class="btn-quick-checkout">
        <i class="fa fa-bolt"></i> Checkout rápido
    </a>
    </div><!-- /.cart-side-card-body -->
</div><!-- /.cart-side-card -->
	<?php } else { ?>
<style>
.empty-cart-wrap{text-align:center;padding:48px 20px 36px;width:100%;}
.empty-cart-wrap .empty-cart-icon{font-size:72px;color:#ddd;line-height:1;margin-bottom:16px;}
.empty-cart-wrap h2{font-size:1.6em;font-weight:700;color:#333;margin-bottom:6px;}
.empty-cart-wrap p{color:#888;font-size:1em;margin-bottom:28px;}
.empty-cart-suggestions{margin-top:18px;}
.empty-cart-suggestions span{display:inline-block;font-size:0.82em;color:#555;margin-bottom:10px;width:100%;}
.empty-cart-tag{display:inline-block;margin:4px 4px;padding:7px 16px;border:1.5px solid #ccc;border-radius:20px;color:#444;font-size:0.88em;text-decoration:none;transition:all .2s;}
.empty-cart-tag:hover{border-color:#e8233a;color:#e8233a;text-decoration:none;}
.empty-cart-cta{margin-top:24px;}
</style>
<div class="empty-cart-wrap">
    <div class="empty-cart-icon"><i class="fa fa-shopping-cart"></i></div>
    <h2>¡Tu carrito está vacío!</h2>
    <p>Parece que aún no has agregado nada.<br>¡Explora nuestros productos y encuentra algo que te encante!</p>
    <a href="index2.php" class="btn btn-primary btn-upper" style="padding:10px 28px;font-size:1em;">
        <i class="fa fa-arrow-left" style="margin-right:6px"></i> Seguir comprando
    </a>
    <?php if (!empty($_empty_suggestions)): ?>
    <div class="empty-cart-suggestions">
        <span>¿Necesitas inspiración? Explora:</span>
        <?php foreach ($_empty_suggestions as $_sug):
            $url = $_sug['type']==='cat'
                ? 'category.php?cat='.$_sug['id']
                : 'sub-category.php?sub='.$_sug['id'];
        ?>
        <a href="<?php echo $url; ?>" class="empty-cart-tag">
            <?php echo htmlentities($_sug['name']); ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php } ?>
</div>			</div>
		</div> 
		</form>
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

<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/cookies.js"></script>
<script>
$(document).ready(function(){
    function fmtMoney(n){ return '$'+Math.round(n).toLocaleString('es-CO'); }

    function getSubtotal(){
        var t=0;
        $('table tbody tr[data-id]').each(function(){
            var price=$(this).data('price')||0, ship=$(this).data('shipping')||0;
            var qty=parseInt($(this).find('input[name^="quantity"]').val())||1;
            t += qty*price + ship;
        });
        return t;
    }

    function updateFinalTotal(discount){
        var sub = getSubtotal();
        $('#cart-page-total').text(fmtMoney(sub));
        var final = Math.max(0, sub - (discount||0));
        $('#cart-final-total').text(fmtMoney(final));
        if(discount>0){
            $('#coupon-discount-line').text(Math.round(discount).toLocaleString('es-CO'));
            $('#coupon-discount-display').text(fmtMoney(discount));
        }
    }

    // Aplicar cupón
    $('#btn-apply-coupon').on('click', function(){
        var code = $('#coupon-input').val().trim();
        if(!code){ $('#coupon-msg').css('color','#c0392b').text('Ingresa un código.'); return; }
        $(this).prop('disabled',true).text('...');
        $.post('ajax-coupon.php', {action:'apply', code:code}, function(r){
            $('#btn-apply-coupon').prop('disabled',false).text('Aplicar');
            if(r.ok){
                $('#coupon-msg').text('');
                $('#coupon-code-display').text(r.code);
                $('#coupon-form-box').hide();
                $('#coupon-applied-box').show();
                $('#coupon-discount-row').show();
                updateFinalTotal(r.discount);
            } else {
                $('#coupon-msg').css('color','#c0392b').text(r.msg);
            }
        },'json');
    });

    // Enter en input
    $('#coupon-input').on('keypress', function(e){
        if(e.which===13){ $('#btn-apply-coupon').trigger('click'); }
    });

    // Quitar cupón
    $('#btn-remove-coupon').on('click', function(e){
        e.preventDefault();
        $.post('ajax-coupon.php', {action:'remove'}, function(r){
            if(r.ok){
                $('#coupon-applied-box').hide();
                $('#coupon-discount-row').hide();
                $('#coupon-form-box').show();
                $('#coupon-input').val('');
                updateFinalTotal(0);
            }
        },'json');
    });

    // Canjear puntos
    $('#btn-apply-points').on('click', function(){
        var pts = parseInt($('#points-input').val()) || 0;
        if (pts < 1) { $('#points-msg').text('Ingresa al menos 1 punto.').css('color','#c0392b'); return; }
        $(this).prop('disabled',true).text('Procesando…');
        $.post('ajax-redeem-points.php', {action:'redeem', points:pts}, function(r){
            $('#btn-apply-points').prop('disabled',false).html('<i class="fa fa-star"></i> Canjear');
            if(r.ok){
                $('#points-applied-box').show();
                $('#points-form-box').hide();
                $('#points-discount-row').show();
                $('#points-used-display').text(r.pts);
                $('#points-discount-display, #points-discount-line').text(r.discount.toLocaleString('es-CO'));
                updateFinalTotal(r.discount + (<?php echo isset($_SESSION['coupon']) ? intval($_SESSION['coupon']['discount']) : 0; ?>));
            } else {
                $('#points-msg').text(r.msg).css('color','#c0392b');
            }
        },'json');
    });

    // Quitar puntos
    $('#btn-remove-points').on('click', function(e){
        e.preventDefault();
        $.post('ajax-redeem-points.php', {action:'remove'}, function(r){
            if(r.ok){
                $('#points-applied-box').hide();
                $('#points-discount-row').hide();
                $('#points-form-box').show();
                updateFinalTotal(<?php echo isset($_SESSION['coupon']) ? intval($_SESSION['coupon']['discount']) : 0; ?>);
            }
        },'json');
    });
});
</script>
<script>
// Cantidad — usa delegación en document para sobrevivir cualquier orden de scripts
$(document).ready(function () {

  function fmtNum(n){ return '$' + Math.round(n).toLocaleString('es-CO'); }

  function recalcRow($row) {
    var price    = parseInt($row.data('price'))    || 0;
    var shipping = parseInt($row.data('shipping')) || 0;
    var id       = $row.data('id');
    var qty      = parseInt($row.find('input[name^="quantity"]').val()) || 1;
    if (qty < 1) qty = 1;
    var rowTotal = qty * price + shipping;
    $('#row-total-' + id).text(fmtNum(rowTotal).replace('$','$'));
    return rowTotal;
  }

  function recalcAll() {
    var grand = 0;
    $('tr[data-id]').each(function () { grand += recalcRow($(this)); });
    $('#cart-page-total').text(fmtNum(grand));
    $('#header-cart-total').text(Math.round(grand).toLocaleString('es-CO'));
    // Recalcular total final restando descuentos ya aplicados
    var couponDisc  = parseFloat($('#coupon-discount-line').text().replace(/[^0-9]/g,'')) || 0;
    var pointsDisc  = parseFloat($('#points-discount-line').text().replace(/[^0-9]/g,'')) || 0;
    var finalTotal  = Math.max(0, grand - couponDisc - pointsDisc);
    $('#cart-final-total').text(fmtNum(finalTotal));
  }

  function saveQty($row, qty) {
    if (qty < 1) qty = 1;
    var id = $row.data('id');
    $row.find('input[name^="quantity"]').val(qty);
    recalcAll();
    $.post('ajax-cart.php', { action:'update', id:id, qty:qty }, function(res){
      if (res && res.count !== undefined) {
        $('#header-cart-count').text(res.count);
        $('.basket-item-count .count').text(res.count);
      }
    }, 'json');
  }

  // + botón (delegado en document)
  $(document).on('click', '.qty-plus', function(){
    var $row = $(this).closest('tr[data-id]');
    var $inp = $(this).closest('.qty-stepper').find('input');
    saveQty($row, (parseInt($inp.val()) || 1) + 1);
  });

  // − botón (delegado en document)
  $(document).on('click', '.qty-minus', function(){
    var $row = $(this).closest('tr[data-id]');
    var $inp = $(this).closest('.qty-stepper').find('input');
    var v = (parseInt($inp.val())||1) - 1;
    saveQty($row, v < 1 ? 1 : v);
  });

  // Edición manual del input
  $(document).on('change', 'input[name^="quantity"]', function(){
    var $row = $(this).closest('tr[data-id]');
    saveQty($row, parseInt($(this).val()) || 1);
  });

});
</script>
<script>
(function(){
    var btn = document.getElementById('sc-btn');
    var res = document.getElementById('sc-result');
    if (!btn) return;
    btn.addEventListener('click', function(){
        var city = document.getElementById('sc-city').value.trim();
        var dept = document.getElementById('sc-dept').value.trim();
        if (!city) { res.style.display='block'; res.innerHTML='<span class="text-danger">Ingresa una ciudad.</span>'; return; }
        btn.disabled = true;
        btn.textContent = '...';
        var total = parseFloat(document.getElementById('totalprice') ? document.getElementById('totalprice').textContent.replace(/[^0-9]/g,'') : 0) || 0;
        fetch('ajax-shipping-calc.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'city='+encodeURIComponent(city)+'&dept='+encodeURIComponent(dept)+'&total='+total})
        .then(function(r){ return r.json(); })
        .then(function(d){
            btn.disabled = false; btn.textContent = 'Calcular';
            if (!d.ok) { res.style.display='block'; res.innerHTML='<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> '+d.msg+'</span>'; return; }
            var color = d.free ? '#27ae60' : '#333';
            var badge = d.premium ? ' <small style="background:#f39c12;color:#fff;border-radius:8px;padding:1px 6px;font-size:10px">PREMIUM</small>' : '';
            res.style.display='block';
            res.innerHTML = '<div style="padding:8px 10px;background:#fff;border-radius:6px;border:1px solid #e0e0e0">'
                + '<div style="display:flex;justify-content:space-between;align-items:center">'
                + '<span><i class="fa fa-map-marker"></i> Zona <strong>'+d.zone+'</strong>'+badge+'</span>'
                + '<strong style="color:'+color+';font-size:15px">'+d.cost_fmt+'</strong>'
                + '</div>'
                + '<div style="color:#888;font-size:11px;margin-top:4px"><i class="fa fa-clock-o"></i> Entrega estimada: '+d.days+'</div>'
                + '</div>';
        })
        .catch(function(){ btn.disabled=false; btn.textContent='Calcular'; res.style.display='block'; res.innerHTML='<span class="text-danger">Error. Intenta de nuevo.</span>'; });
    });
    // Enter key
    ['sc-city','sc-dept'].forEach(function(id){ var el=document.getElementById(id); if(el) el.addEventListener('keydown',function(e){ if(e.key==='Enter') btn.click(); }); });
})();
</script>
</body>
</html>
