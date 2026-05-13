<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if(empty($_SESSION['login']))
    {
$_SESSION['flash_info'] = 'Debes iniciar sesión para ver tu lista de deseos.';
header('location:login.php');
exit();
}
else{
// Code for Product deletion from wishlist (CSRF-protected via token)
if(isset($_GET['del']) && isset($_GET['tok']) && hash_equals(csrf_token(), $_GET['tok'])) {
    $wid = intval($_GET['del']);
    $uid = intval($_SESSION['id']);
    // Only delete if the wishlist item belongs to this user
    mysqli_query($con, "DELETE FROM wishlist WHERE id=$wid AND userId=$uid");
}


if(isset($_GET['action']) && $_GET['action']=="add"){
	$id=intval($_GET['id']);
	$query=mysqli_query($con,"delete from wishlist where productId='$id'");
	if(isset($_SESSION['cart'][$id])){
		$_SESSION['cart'][$id]['quantity']++;
	}else{
		$sql_p="SELECT * FROM products WHERE id={$id}";
		$query_p=mysqli_query($con,$sql_p);
		if(mysqli_num_rows($query_p)!=0){
			$row_p=mysqli_fetch_array($query_p);
			$_SESSION['cart'][$row_p['id']]=array("quantity" => 1, "price" => $row_p['productPrice']);	
header('location:my-wishlist.php');
}
		else{
			$message="Product ID is invalid";
		}
	}
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

	    <title>Mi lista de deseos | <?php echo $_SITE_NAME; ?></title>
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    
	    <!-- Customizable CSS -->
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
				<li class='active'>Mi lista de deseos</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-bd">
	<div class="container">
		<div class="my-wishlist-page inner-bottom-sm">
			<div class="row">
				<div class="col-md-12 my-wishlist">
	<div class="table-responsive">
		<table class="table">
			<thead>
				<tr>
					<th colspan="4" style="vertical-align:middle">
						Mi lista de deseos
						<?php
						$_share_token = md5($_SESSION['id'] . 'ps_wish_salt_2024');
						$_share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/wishlist-share.php?token=' . $_share_token;
						?>
						<button onclick="psWishlistShare()" class="btn btn-default btn-sm" style="float:right;margin-top:-2px" title="Compartir lista">
							<i class="fa fa-share-alt"></i> Compartir lista
						</button>
					</th>
				</tr>
			</thead>
			<tbody>
<?php
$_wl_uid = intval($_SESSION['id']);
$ret=mysqli_query($con,"SELECT products.productName as pname,products.productName as proid,products.productImage1 as pimage,products.productPrice as pprice,wishlist.productId as pid,wishlist.id as wid FROM wishlist JOIN products ON products.id=wishlist.productId WHERE wishlist.userId=$_wl_uid");
$num=mysqli_num_rows($ret);
	if($num>0)
	{
while ($row=mysqli_fetch_array($ret)) {

?>

				<tr>
					<td class="col-md-2"><img src="admin/productimages/<?php echo htmlentities($row['pid']);?>/<?php echo htmlentities($row['pimage']);?>" alt="<?php echo htmlentities($row['pname']);?>" width="60" height="100"></td>
					<td class="col-md-6">
						<div class="product-name"><a href="product-details.php?pid=<?php echo htmlentities($pd=$row['pid']);?>"><?php echo htmlentities($row['pname']);?></a></div>
<?php $rt=mysqli_query($con,"select * from productreviews where productId='$pd'");
$num=mysqli_num_rows($rt);
{
?>

						<div class="rating">
							<i class="fa fa-star rate"></i>
							<i class="fa fa-star rate"></i>
							<i class="fa fa-star rate"></i>
							<i class="fa fa-star rate"></i>
							<i class="fa fa-star non-rate"></i>
							<span class="review">( <?php echo htmlentities($num);?> Reviews )</span>
						</div>
						<?php } ?>
						<div class="price">$ 
							<?php echo htmlentities($row['pprice']);?>.00
							<span>$900.00</span>
						</div>
					</td>
					<td class="col-md-2">
						<a href="my-wishlist.php?page=product&action=add&id=<?php echo $row['pid']; ?>" class="btn-upper btn btn-primary">Add to cart</a>
					</td>
					<td class="col-md-2 close-btn">
						<a href="my-wishlist.php?del=<?php echo intval($row['wid']); ?>&tok=<?php echo htmlspecialchars(csrf_token()); ?>" onClick="return confirm('¿Eliminar de la lista?')" class=""><i class="fa fa-times"></i></a>
					</td>
				</tr>
				<?php } } else{ ?>
				<tr>
					<td style="font-size: 18px; font-weight:bold ">Tu lista de deseos está vacia</td>

				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>			</div><!-- /.row -->
		</div><!-- /.sigin-in-->
	<?php include('includes/brands-slider.php');?>
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
<script src="assets/js/toast.js"></script>
<script src="assets/js/cookies.js"></script>
<script>
function psWishlistShare() {
    var url = <?php echo json_encode($_share_url); ?>;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(function(){
            showToast('Enlace copiado al portapapeles', 'success');
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('Enlace copiado al portapapeles', 'success');
    }
}
</script>
</body>
</html>
<?php } ?>