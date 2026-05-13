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
		// Actualizar cantidades
		if(!empty($_SESSION['cart']) && isset($_POST['quantity'])){
			foreach($_POST['quantity'] as $key => $val){
				$key = intval($key);
				$val = intval($val);
				if(isset($_SESSION['cart'][$key])){
					if($val <= 0){ unset($_SESSION['cart'][$key]); }
					else{ $_SESSION['cart'][$key]['quantity'] = $val; }
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
/* =========== CARRITO RESPONSIVO MÓVIL =========== */
@media (max-width: 767px) {
    .shopping-cart-table { padding: 0 !important; }
    .table-responsive { border: none !important; }

    /* Ocultar thead en móvil */
    .cart table thead { display: none; }
    .cart table tfoot td { padding: 10px 0; }
    .cart table tfoot .shopping-cart-btn { display: flex; flex-direction: column; gap: 8px; }
    .cart table tfoot .btn { width: 100%; text-align: center; margin: 0 !important; }

    /* Cada fila = card */
    .cart table tbody tr[data-id] {
        display: block;
        background: #fff;
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        margin-bottom: 14px;
        padding: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .cart table tbody tr[data-id] td {
        display: flex;
        align-items: center;
        border: none !important;
        padding: 4px 0;
        font-size: 13px;
    }
    /* Imagen + nombre lado a lado */
    .cart table tbody tr[data-id] .cart-image { display: none !important; }

    /* Checkbox remover — moverlo a esquina superior derecha via flexbox trick */
    .cart table tbody tr[data-id] .romove-item {
        justify-content: flex-end;
        color: #aaa;
        font-size: 12px;
    }
    .cart table tbody tr[data-id] .romove-item::before { content: "Eliminar "; color: #999; }

    .cart table tbody tr[data-id] .cart-product-name-info {
        flex-direction: column;
        align-items: flex-start;
        padding-bottom: 6px;
        border-bottom: 1px dashed #eee !important;
    }
    .cart table tbody tr[data-id] .cart-product-name-info h4 { margin: 0 0 4px; font-size: 14px; }
    .cart table tbody tr[data-id] .cart-product-name-info .row { display: none; }

    .cart table tbody tr[data-id] .cart-product-quantity::before { content: "Cantidad: "; font-size: 12px; color: #888; margin-right: 8px; }
    .cart table tbody tr[data-id] .cart-product-sub-total:first-of-type::before { content: "Precio unit.: "; font-size: 12px; color: #888; margin-right: 4px; }
    .cart table tbody tr[data-id] td:nth-child(6)::before { content: "Envío: "; font-size: 12px; color: #888; margin-right: 4px; }
    .cart table tbody tr[data-id] .cart-product-grand-total { font-weight: 700; font-size: 14px; color: #e8233a; }
    .cart table tbody tr[data-id] .cart-product-grand-total::before { content: "Total: "; font-size: 12px; color: #888; margin-right: 4px; font-weight: 400; }

    /* Secciones de dirección y total en móvil */
    .col-md-4.estimate-ship-tax,
    .col-md-4.cart-shopping-total { width: 100%; margin-bottom: 14px; }
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
	?>
		<table class="table table-bordered">
			<thead>
				<tr>
					<th class="cart-romove item">Remover</th>
					<th class="cart-description item">Imagen</th>
					<th class="cart-product-name item">Producto</th>
			
					<th class="cart-qty item">Cantidad</th>
					<th class="cart-sub-total item">Precio por unidad</th>
					<th class="cart-sub-total item">Costo de envio</th>
					<th class="cart-total last-item">Total</th>
				</tr>
			</thead><!-- /thead -->
			<tfoot>
				<tr>
					<td colspan="7">
						<div class="shopping-cart-btn">
							<span class="">
								<input type="submit" name="cartupdate" value="Update shopping cart" class="btn btn-upper btn-primary outer-left-xs">
								<a href="index2.php" class="btn btn-upper btn-primary pull-right outer-right-xs">Continue comprando</a>
							</span>
						</div><!-- /.shopping-cart-btn -->
					</td>
				</tr>
			</tfoot>
			<tbody>
 <?php
 $pdtid=array();
    $sql = "SELECT * FROM products WHERE id IN(";
			foreach($_SESSION['cart'] as $id => $value){
			$sql .=$id. ",";
			}
			$sql=substr($sql,0,-1) . ") ORDER BY id ASC";
			$query = mysqli_query($con,$sql);
			$totalprice=0;
			$totalqunty=0;
			if(!empty($query)){
			while($row = mysqli_fetch_array($query)){
				$quantity=$_SESSION['cart'][$row['id']]['quantity'];
				$subtotal= $_SESSION['cart'][$row['id']]['quantity']*$row['productPrice']+$row['shippingCharge'];
				$totalprice += $subtotal;
				$_SESSION['qnty']=$totalqunty+=$quantity;

				array_push($pdtid,$row['id']);
//print_r($_SESSION['pid'])=$pdtid;exit;
	?>

				<tr data-price="<?php echo intval($row['productPrice']); ?>" data-shipping="<?php echo intval($row['shippingCharge']); ?>" data-id="<?php echo intval($row['id']); ?>">
					<td class="romove-item"><input type="checkbox" name="remove_code[]" value="<?php echo htmlentities($row['id']);?>" /></td>
					<td class="cart-image">
						<a class="entry-thumbnail" href="detail.html">
						    <img src="admin/productimages/<?php echo $row['id'];?>/<?php echo $row['productImage1'];?>" alt="" width="114" height="146">
						</a>
					</td>
					<td class="cart-product-name-info">
						<h4 class='cart-product-description'><a href="product-details.php?pid=<?php echo htmlentities($pd=$row['id']);?>" ><?php echo $row['productName'];

$_SESSION['sid']=$pd;
						 ?></a></h4>
<?php if (!empty($_SESSION['cart'][$row['id']]['customization'])): ?>
<div style="font-size:11px;color:#555;margin-top:4px;background:#f5f5f5;border-radius:4px;padding:4px 8px">
<?php foreach ($_SESSION['cart'][$row['id']]['customization'] as $_ck => $_cv): ?>
<div><strong><?php echo htmlspecialchars($_ck); ?>:</strong> <?php echo htmlspecialchars($_cv); ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
						<div class="row">
							<div class="col-sm-4">
								<div class="rating rateit-small"></div>
							</div>
							<div class="col-sm-8">
<?php $rt=mysqli_query($con,"select * from productreviews where productId='$pd'");
$num=mysqli_num_rows($rt);
{
?>
								<div class="reviews">
									( <?php echo htmlentities($num);?> Reviews )
								</div>
								<?php } ?>
							</div>
						</div><!-- /.row -->
						
					</td>
					<td class="cart-product-quantity">
						<div class="quant-input">
				                <div class="arrows">
				                  <div class="arrow plus gradient"><span class="ir"><i class="icon fa fa-sort-asc"></i></span></div>
				                  <div class="arrow minus gradient"><span class="ir"><i class="icon fa fa-sort-desc"></i></span></div>
				                </div>
				             <input type="text" value="<?php echo $_SESSION['cart'][$row['id']]['quantity']; ?>" name="quantity[<?php echo $row['id']; ?>]">
				             
			              </div>
		            </td>
					<td class="cart-product-sub-total"><span class="cart-sub-total-price"><?php echo "COP"." ".$row['productPrice']; ?>.00</span></td>
<td class="cart-product-sub-total"><span class="cart-sub-total-price"><?php echo "$"." ".$row['shippingCharge']; ?>.00</span></td>

					<td class="cart-product-grand-total"><span class="cart-grand-total-price" id="row-total-<?php echo intval($row['id']); ?>"><?php echo ($_SESSION['cart'][$row['id']]['quantity']*$row['productPrice']+$row['shippingCharge']); ?>.00</span></td>
				</tr>

				<?php } }
$_SESSION['pid']=$pdtid;
				?>
				
			</tbody><!-- /tbody -->
		</table><!-- /table -->
		
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
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>
					<span class="estimate-title">Dirección de envío</span>
				</th>
			</tr>
		</thead>
		<tbody>
				<tr>
					<td>
						<!-- Direcciones guardadas -->
						<?php if (!empty($_addr_rows)): ?>
						<p style="font-size:13px;font-weight:600;margin-bottom:8px">Selecciona una dirección guardada:</p>
						<?php foreach($_addr_rows as $_ar): ?>
						<div style="border:1px solid #ddd;border-radius:6px;padding:10px 14px;margin-bottom:8px;background:#f9f9f9;display:flex;justify-content:space-between;align-items:center">
						    <div>
						        <strong style="font-size:13px"><?php echo htmlspecialchars($_ar['label']); ?></strong><br>
						        <small style="color:#555"><?php echo htmlspecialchars($_ar['address']); ?><?php if($_ar['city']) echo ', '.htmlspecialchars($_ar['city']); ?><?php if($_ar['state']) echo ', '.htmlspecialchars($_ar['state']); ?></small>
						    </div>
						    <div style="display:flex;gap:6px">
						        <button type="button" class="btn btn-xs btn-primary" onclick="fillAddress('<?php echo addslashes($_ar['address']); ?>','<?php echo addslashes($_ar['state']); ?>','<?php echo addslashes($_ar['city']); ?>','<?php echo addslashes($_ar['pincode']); ?>')">Usar</button>
						        <a href="my-cart.php?del_addr=<?php echo $_ar['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar esta dirección?')">✕</a>
						    </div>
						</div>
						<?php endforeach; ?>
						<hr>
						<?php endif; ?>

						<!-- Formulario dirección actual -->
						<div class="form-group">
<?php
$query=mysqli_query($con,"SELECT * FROM users WHERE id=".intval($_SESSION['id']));
while($row=mysqli_fetch_array($query))
{
?>

<div class="form-group">
					    <label class="info-title" for="Billing Address">Dirección<span>*</span></label>
					    <textarea class="form-control unicase-form-control text-input" id="f-address" name="billingaddress" required="required"><?php echo $row['billingAddress'];?></textarea>
					  </div>

						<div class="form-group">
					    <label class="info-title" for="Billing State ">Departamento  <span>*</span></label>
			 <input type="text" class="form-control unicase-form-control text-input" id="f-state" name="bilingstate" value="<?php echo $row['billingState'];?>" required>
					  </div>
					  <div class="form-group">
					    <label class="info-title" for="Billing City">Ciudad <span>*</span></label>
					    <input type="text" class="form-control unicase-form-control text-input" id="f-city" name="billingcity" required="required" value="<?php echo $row['billingCity'];?>" >
					  </div>
 <div class="form-group">
					    <label class="info-title" for="Billing Pincode">Código postal <span>*</span></label>
					    <input type="text" class="form-control unicase-form-control text-input" id="f-pincode" name="billingpincode" required="required" value="<?php echo $row['billingPincode'];?>" >
					  </div>

					  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px">
					  <button type="submit" name="update" class="btn-upper btn btn-primary checkout-page-button">Actualizar</button>
					  <button type="button" class="btn btn-default btn-sm" data-toggle="collapse" data-target="#save-addr-form">+ Guardar como nueva dirección</button>
					  </div>

					  <div class="collapse" id="save-addr-form" style="margin-top:12px;padding:12px;background:#f5f5f5;border-radius:6px">
					    <h5 style="margin:0 0 8px">Guardar dirección</h5>
					    <input type="text" name="addr_label" class="form-control" placeholder="Etiqueta (Casa, Oficina…)" style="margin-bottom:6px">
					    <input type="hidden" name="addr_address" id="save-address-val">
					    <input type="hidden" name="addr_state" id="save-state-val">
					    <input type="hidden" name="addr_city" id="save-city-val">
					    <input type="hidden" name="addr_pincode" id="save-pincode-val">
					    <button type="submit" name="save_address" class="btn btn-sm btn-success" onclick="syncSaveAddr()">Guardar dirección</button>
					  </div>

				<?php } ?>

						</div>

					</td>
				</tr>
		</tbody><!-- /tbody -->
	</table><!-- /table -->
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
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>
					<span class="estimate-title">Dirección</span>
				</th>
			</tr>
		</thead>
		<tbody>
				<tr>
					<td>
						<div class="form-group">
		<?php
$query=mysqli_query($con,"SELECT * FROM users WHERE id=".intval($_SESSION['id']));
while($row=mysqli_fetch_array($query))
{
?>

<div class="form-group">
					    <label class="info-title" for="Shipping Address">Dirección de envio<span>*</span></label>
					    <textarea class="form-control unicase-form-control text-input"  name="shippingaddress" required="required"><?php echo $row['shippingAddress'];?></textarea>
					  </div>



						<div class="form-group">
					    <label class="info-title" for="Billing State ">Departamento  <span>*</span></label>
			 <input type="text" class="form-control unicase-form-control text-input" id="shippingstate" name="shippingstate" value="<?php echo $row['shippingState'];?>" required>
					  </div>
					  <div class="form-group">
					    <label class="info-title" for="Billing City">Ciudad <span>*</span></label>
					    <input type="text" class="form-control unicase-form-control text-input" id="shippingcity" name="shippingcity" required="required" value="<?php echo $row['shippingCity'];?>" >
					  </div>
 <div class="form-group">
					    <label class="info-title" for="Billing Pincode">Código postal <span>*</span></label>
					    <input type="text" class="form-control unicase-form-control text-input" id="shippingpincode" name="shippingpincode" required="required" value="<?php echo $row['shippingPincode'];?>" >
					  </div>


					  <button type="submit" name="shipupdate" class="btn-upper btn btn-primary checkout-page-button">Actualizar</button>
					<?php } ?>

		
						</div>
					
					</td>
				</tr>
		</tbody><!-- /tbody -->
	</table><!-- /table -->
</div>
<div class="col-md-4 col-sm-12 cart-shopping-total">
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>
					<div class="cart-grand-total">
						Subtotal<span class="inner-left-md" id="cart-page-total">$<?php echo number_format($totalprice, 0, ',', '.'); ?></span>
					</div>
				</th>
			</tr>
		</thead>
		<tbody>
			<!-- CUPÓN -->
			<tr>
				<td>
					<div id="coupon-applied-box" style="<?php echo isset($_SESSION['coupon'])?'':'display:none'; ?>">
						<div style="display:flex;justify-content:space-between;align-items:center;background:#e6f4ea;border:1px solid #c3e6cb;border-radius:5px;padding:8px 10px;margin-bottom:8px;">
							<span style="color:#276c3a;font-size:.9em">
								<i class="fa fa-tag"></i>
								<strong id="coupon-code-display"><?php echo isset($_SESSION['coupon'])?htmlentities($_SESSION['coupon']['code']):''; ?></strong>
								&nbsp;— ahorro: <strong id="coupon-discount-display">$<?php echo isset($_SESSION['coupon'])?number_format($_SESSION['coupon']['discount'],0,'.',','):'0'; ?></strong>
							</span>
							<a href="#" id="btn-remove-coupon" title="Quitar cupón" style="color:#c0392b;font-size:.85em"><i class="fa fa-times"></i> Quitar</a>
						</div>
					</div>
					<div id="coupon-form-box" style="<?php echo isset($_SESSION['coupon'])?'display:none':''; ?>">
						<div style="font-size:.85em;color:#666;margin-bottom:5px">¿Tienes un cupón de descuento?</div>
						<div style="display:flex;gap:6px;">
							<input type="text" id="coupon-input" placeholder="Código de cupón" style="flex:1;padding:5px 8px;border:1px solid #ccc;border-radius:4px;font-size:.88em;text-transform:uppercase">
							<button type="button" id="btn-apply-coupon" class="btn btn-primary btn-small" style="white-space:nowrap">Aplicar</button>
						</div>
						<div id="coupon-msg" style="font-size:.82em;margin-top:5px"></div>
					</div>
				</td>
			</tr>
			<!-- PUNTOS -->
			<?php if(!empty($_SESSION['login'])): ?>
			<?php include_once('includes/points.php'); $_cart_pts = ps_points_get($con, intval($_SESSION['id'])); ?>
			<?php if($_cart_pts > 0): ?>
			<tr>
				<td>
					<?php
					$_pts_disc = intval($_SESSION['points_discount'] ?? 0);
					$_pts_used = intval($_SESSION['points_redeemed'] ?? 0);
					?>
					<div id="points-applied-box" style="<?php echo $_pts_disc>0?'':'display:none'; ?>">
						<div style="display:flex;justify-content:space-between;align-items:center;background:#fff8e1;border:1px solid #ffe082;border-radius:5px;padding:8px 10px;margin-bottom:8px;">
							<span style="color:#856404;font-size:.9em">
								<i class="fa fa-star" style="color:#f39c12"></i>
								<strong id="points-used-display"><?php echo $_pts_used; ?></strong> puntos canjeados
								— ahorro: <strong>$<span id="points-discount-display"><?php echo number_format($_pts_disc,0,'.',','); ?></span></strong>
							</span>
							<a href="#" id="btn-remove-points" style="color:#c0392b;font-size:.85em"><i class="fa fa-times"></i> Quitar</a>
						</div>
					</div>
					<div id="points-form-box" style="<?php echo $_pts_disc>0?'display:none':''; ?>">
						<div style="font-size:.85em;color:#666;margin-bottom:5px">
							<i class="fa fa-star" style="color:#f39c12"></i>
							Tienes <strong><?php echo number_format($_cart_pts); ?></strong> puntos (= $<?php echo number_format($_cart_pts * 10, 0, ',', '.'); ?> de descuento)
						</div>
						<div style="display:flex;gap:6px;">
							<input type="number" id="points-input" min="1" max="<?php echo $_cart_pts; ?>"
								placeholder="¿Cuántos puntos canjear?" value="<?php echo $_cart_pts; ?>"
								style="flex:1;padding:5px 8px;border:1px solid #ccc;border-radius:4px;font-size:.88em">
							<button type="button" id="btn-apply-points" class="btn btn-warning btn-small" style="white-space:nowrap">
								<i class="fa fa-star"></i> Canjear
							</button>
						</div>
						<div id="points-msg" style="font-size:.82em;margin-top:5px;color:#888">1 punto = $10 de descuento</div>
					</div>
				</td>
			</tr>
			<tr id="points-discount-row" style="<?php echo $_pts_disc>0?'':'display:none'; ?>">
				<td>
					<div style="display:flex;justify-content:space-between;color:#f39c12;">
						<span>Descuento puntos:</span>
						<strong>− $<span id="points-discount-line"><?php echo number_format($_pts_disc,0,'.',','); ?></span></strong>
					</div>
				</td>
			</tr>
			<?php endif; endif; ?>
			<!-- TARIFA DE ENVÍO POR ZONA (U1) -->
			<?php
			$_zone_ship_cost = 0;
			$_zone_ship_label = '';
			$_zone_ship_days  = '';
			$_zone_ship_free  = false;
			if (!empty($_SESSION['login'])) {
			    $uid_s = intval($_SESSION['id']);
			    include_once('includes/membership.php');
			    $_is_premium_cart = ps_membership_active($con, $uid_s);
			    $u_ship = mysqli_query($con, "SELECT shippingCity, shippingState FROM users WHERE id=$uid_s LIMIT 1");
			    if ($u_ship && $u_s = mysqli_fetch_assoc($u_ship)) {
			        $city_s = $u_s['shippingCity'] ?? '';
			        $dept_s = $u_s['shippingState'] ?? '';
			        if ($city_s !== '') {
			            include_once('includes/shipping.php');
			            $_ship_info = ps_shipping_cost($con, $city_s, $totalprice, $dept_s);
			            $_zone_ship_cost  = $_is_premium_cart ? 0 : $_ship_info['cost'];
			            $_zone_ship_label = $_ship_info['zone'] ?? '';
			            $_zone_ship_days  = $_ship_info['days_min'] . '–' . $_ship_info['days_max'] . ' días';
			            $_zone_ship_free  = $_is_premium_cart || $_ship_info['free'];
			        }
			    }
			} else { $_is_premium_cart = false; }
			$_SESSION['zone_shipping'] = $_zone_ship_cost;
			?>
			<?php if ($_zone_ship_label !== ''): ?>
			<tr>
			    <td>
			        <div style="display:flex;justify-content:space-between;font-size:13px;color:#555">
			            <span><i class="fa fa-truck"></i> Envío <?php echo htmlspecialchars($_zone_ship_label); ?> (<?php echo $_zone_ship_days; ?>):</span>
			            <?php if ($_zone_ship_free): ?>
			            <strong style="color:#27ae60">¡Gratis!</strong>
			            <?php else: ?>
			            <strong>$<?php echo number_format($_zone_ship_cost, 0, '.', ','); ?></strong>
			            <?php endif; ?>
			        </div>
			    </td>
			</tr>
			<?php endif; ?>
			<!-- DESCUENTO -->
			<tr id="coupon-discount-row" style="<?php echo isset($_SESSION['coupon'])?'':'display:none'; ?>">
				<td>
					<div style="display:flex;justify-content:space-between;color:#e8233a;">
						<span>Descuento cupón:</span>
						<strong>− $<span id="coupon-discount-line"><?php echo isset($_SESSION['coupon'])?number_format($_SESSION['coupon']['discount'],0,'.',','):'0'; ?></span></strong>
					</div>
				</td>
			</tr>
			<!-- DESCUENTO POR CATEGORÍA (J4) -->
			<?php
			$_cat_discount_total = 0;
			$_cat_discount_labels = [];
			if (!empty($_SESSION['cart'])) {
			    $dr_q = mysqli_query($con, "SELECT dr.*, c.catName FROM category_discounts dr JOIN category c ON dr.cat_id=c.id WHERE dr.active=1");
			    while ($dr = mysqli_fetch_assoc($dr_q)) {
			        // Contar cuántos productos del carrito son de esta categoría
			        $cat_count = 0; $cat_subtotal = 0;
			        foreach ($_SESSION['cart'] as $cart_pid => $cart_item) {
			            $cpq = mysqli_query($con, "SELECT catId, productPrice FROM products WHERE id=" . intval($cart_pid));
			            if ($cpq && $cprow = mysqli_fetch_assoc($cpq)) {
			                if ($cprow['catId'] == $dr['cat_id']) {
			                    $cat_count   += $cart_item['quantity'];
			                    $cat_subtotal += $cart_item['quantity'] * floatval($cprow['productPrice']);
			                }
			            }
			        }
			        if ($cat_count >= $dr['min_qty']) {
			            $disc = $cat_subtotal * ($dr['discount_pct'] / 100);
			            $_cat_discount_total += $disc;
			            $_cat_discount_labels[] = ($dr['label'] ?: $dr['catName'] . ' ' . $dr['discount_pct'] . '%') . ' (−$' . number_format($disc, 0, '.', ',') . ')';
			        }
			    }
			}
			$_SESSION['cat_discount'] = $_cat_discount_total;
			?>
			<?php if ($_cat_discount_total > 0): ?>
			<tr>
			    <td>
			        <?php foreach($_cat_discount_labels as $_cdl): ?>
			        <div style="display:flex;justify-content:space-between;color:#27ae60;font-size:13px">
			            <span><i class="fa fa-tag"></i> <?php echo htmlspecialchars($_cdl); ?></span>
			        </div>
			        <?php endforeach; ?>
			    </td>
			</tr>
			<?php endif; ?>
			<!-- DESCUENTO POR NIVEL (N1) -->
			<?php
			$_level_disc_pct  = 0;
			$_level_disc_amt  = 0;
			$_level_name_cart = '';
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
			<tr>
			    <td>
			        <div style="display:flex;justify-content:space-between;color:#9b59b6;font-size:13px">
			            <span>🏅 Descuento nivel <?php echo htmlspecialchars($_level_name_cart); ?> (<?php echo $_level_disc_pct; ?>%):</span>
			            <strong>− $<?php echo number_format($_level_disc_amt, 0, '.', ','); ?></strong>
			        </div>
			    </td>
			</tr>
			<?php endif; ?>
			<!-- TOTAL FINAL -->
			<tr>
				<td>
					<div style="display:flex;justify-content:space-between;font-size:1.1em;font-weight:700;border-top:1px solid #eee;padding-top:8px;margin-top:4px;">
						<span>Total a pagar:</span>
						<span id="cart-final-total" style="color:#337ab7">
							$<?php
							$_base_total = $totalprice - $_cat_discount_total;
							if (isset($_SESSION['coupon'])) {
								$_base_total = $_SESSION['coupon']['final'] - $_cat_discount_total;
							}
							if (!empty($_SESSION['bundle_discount']['amount'])) { $_base_total -= floatval($_SESSION['bundle_discount']['amount']); }
							echo number_format(max(0, $_base_total - $_level_disc_amt), 0, ',', '.');
							?>
						</span>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div style="background:#f8f9fa;border:1px solid #eee;border-radius:8px;padding:14px;margin-bottom:12px">
<strong style="font-size:13px"><i class="fa fa-truck"></i> Calcular envio</strong>
<div style="display:flex;gap:6px;margin-top:8px">
<input type="text" id="sc-city" placeholder="Ciudad" class="form-control input-sm" style="flex:1">
<input type="text" id="sc-dept" placeholder="Departamento" class="form-control input-sm" style="flex:1">
<button type="button" id="sc-btn" class="btn btn-default btn-sm">Calcular</button>
</div>
<div id="sc-result" style="margin-top:8px;font-size:13px;display:none"></div>
</div>
<div class="cart-checkout-btn pull-right">
						<button type="submit" name="ordersubmit" class="btn btn-primary">PROCEDE A PAGAR</button>
					<a href="checkout-onepage.php" class="btn btn-success" style="margin-top:6px"><i class="fa fa-bolt"></i> Checkout rápido</a>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
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
$(document).ready(function () {

  // Desactivar handlers de scripts.js para las flechas (evita doble disparo)
  $('.quant-input .plus').off('click');
  $('.quant-input .minus').off('click');

  function recalcRow($row) {
    var price    = parseInt($row.data('price'))    || 0;
    var shipping = parseInt($row.data('shipping')) || 0;
    var id       = $row.data('id');
    var qty      = parseInt($row.find('input[name^="quantity"]').val()) || 1;
    if (qty < 1) qty = 1;
    var rowTotal = qty * price + shipping;
    $('#row-total-' + id).text(rowTotal.toLocaleString('es-CO') + '.00');
    return rowTotal;
  }

  function recalcAll() {
    var grand = 0;
    $('table tbody tr[data-id]').each(function () {
      grand += recalcRow($(this));
    });
    $('#cart-page-total').text('$' + grand.toLocaleString('es-CO'));
    $('#header-cart-total').text(grand.toLocaleString('es-CO'));
  }

  function saveQty($row, qty) {
    if (qty < 1) qty = 1;
    var id = $row.data('id');
    $row.find('input[name^="quantity"]').val(qty);
    recalcAll();
    $.post('ajax-cart.php', { action: 'update', id: id, qty: qty }, function(res) {
      if (res && res.count !== undefined) {
        $('#header-cart-count').text(res.count);
        $('.basket-item-count .count').text(res.count);
      }
    }, 'json');
  }

  // Flecha + (reemplaza el handler de scripts.js)
  $('.quant-input .plus').on('click', function () {
    var $row   = $(this).closest('tr[data-id]');
    var $input = $(this).closest('.quant-input').find('input');
    saveQty($row, parseInt($input.val()) + 1);
  });

  // Flecha - (reemplaza el handler de scripts.js)
  $('.quant-input .minus').on('click', function () {
    var $row   = $(this).closest('tr[data-id]');
    var $input = $(this).closest('.quant-input').find('input');
    var v = parseInt($input.val()) - 1;
    saveQty($row, v < 1 ? 1 : v);
  });

  // Cambio manual al salir del campo
  $('input[name^="quantity"]').on('change', function () {
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