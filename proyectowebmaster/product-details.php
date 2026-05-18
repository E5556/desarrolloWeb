<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
include('includes/price-display.php');
include_once('includes/flash-sale.php');

// Registrar vista de producto para analytics
if (isset($_GET['pid'])) {
    $__pvpid = intval($_GET['pid']);
    $__pvuid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 'NULL';
    $__pvdate = date('Y-m-d');
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_views (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, user_id INT DEFAULT NULL, view_date DATE NOT NULL, INDEX(product_id), INDEX(view_date))");
    mysqli_query($con, "INSERT INTO product_views (product_id, user_id, session_id) VALUES ($__pvpid, " . ($__pvuid === 'NULL' ? 'NULL' : $__pvuid) . ", '" . session_id() . "')");
}

if(isset($_GET['action']) && $_GET['action']=="add"){
	$id=intval($_GET['id'] ?? 0);
	if(isset($_SESSION['cart'][$id])){
		$_SESSION['cart'][$id]['quantity']++;
	}else{
		$sql_p="SELECT * FROM products WHERE id={$id}";
		$query_p=mysqli_query($con,$sql_p);
		if(mysqli_num_rows($query_p)!=0){
			$row_p=mysqli_fetch_array($query_p);
			// Usar precio flash si hay uno activo
			$_fs_add = flash_sale_get($con, $id);
			$_cart_price = $_fs_add ? floatval($_fs_add['sale_price']) : floatval($row_p['productPrice']);
			$_SESSION['cart'][$row_p['id']]=array("quantity" => 1, "price" => $_cart_price);
			echo "<script>showToast('Producto agregado al carrito','success')</script>";
			echo "<script type='text/javascript'> document.location ='my-cart.php'; </script>";
		}else{
			$message="Product ID is invalid";
		}
	}
}
$pid=intval($_GET['pid'] ?? 0);
// ── E2: Price history table + snapshot ───────────────────────────────────────
mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    recorded_at DATE NOT NULL,
    UNIQUE KEY uq_pid_date (product_id, recorded_at),
    INDEX idx_pid (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Snapshot today's price
if ($pid > 0) {
    $today_str = date('Y-m-d');
    $prod_price_r = mysqli_query($con, "SELECT productPrice FROM products WHERE id=$pid LIMIT 1");
    if ($prod_price_r && ($pp_row = mysqli_fetch_assoc($prod_price_r))) {
        $cur_price = (float)$pp_row['productPrice'];
        mysqli_query($con, "INSERT IGNORE INTO product_price_history (product_id, price, recorded_at) VALUES ($pid, $cur_price, '$today_str')");
    }
}

// Load history (last 30 days)
$_price_history = [];
if ($pid > 0) {
    $phq = mysqli_query($con, "SELECT recorded_at, price FROM product_price_history WHERE product_id=$pid ORDER BY recorded_at ASC LIMIT 30");
    while ($phq && $ph = mysqli_fetch_assoc($phq)) $_price_history[] = $ph;
}

// SEO pre-fetch
$_seo_p = null;
if ($pid > 0) {
    $_r = mysqli_query($con, "SELECT productName, productDescription, productCompany, productPrice, productImage1 as productImage FROM products WHERE id=$pid");
    $_seo_p = $_r ? mysqli_fetch_assoc($_r) : null;
}

// Track product view (once per session per product)
if ($pid > 0 && !isset($_SESSION['viewed_'.$pid])) {
    $_SESSION['viewed_'.$pid] = true;
    $sid = session_id();
    $uid = intval($_SESSION['id'] ?? 0);
    $stmt_pv = mysqli_prepare($con, "INSERT INTO product_views(product_id,session_id,user_id) VALUES(?,?,?)");
    if ($stmt_pv) {
        mysqli_stmt_bind_param($stmt_pv, 'isi', $pid, $sid, $uid);
        mysqli_stmt_execute($stmt_pv);
        mysqli_stmt_close($stmt_pv);
    }
}

// F4: Auto-add review_photo column if needed
mysqli_query($con, "ALTER TABLE productreviews ADD COLUMN IF NOT EXISTS review_photo VARCHAR(255) DEFAULT NULL");

// T1: Load product customizer fields
$_cust_fields = [];
if ($pid > 0) {
    $_cfq = mysqli_query($con, "SELECT * FROM product_customizer WHERE product_id=$pid ORDER BY sort_order, id");
    while ($_cfq && $_cf = mysqli_fetch_assoc($_cfq)) $_cust_fields[] = $_cf;
}

// T1: Handle add-to-cart with customization data
if (isset($_POST['add_customized'])) {
    $cid = intval($_POST['cust_pid'] ?? 0);
    if ($cid > 0) {
        $sql_pc = "SELECT * FROM products WHERE id=$cid";
        $row_pc = mysqli_fetch_assoc(mysqli_query($con, $sql_pc));
        if ($row_pc) {
            $_fs_pc = flash_sale_get($con, $cid);
            $_base_price = $_fs_pc ? floatval($_fs_pc['sale_price']) : floatval($row_pc['productPrice']);
            $_price_extra = 0;
            $_custom_data = [];
            foreach ($_POST as $k => $v) {
                if (strpos($k, 'cfield_') === 0) {
                    $fid = intval(substr($k, 7));
                    foreach ($_cust_fields as $_cf2) {
                        if ($_cf2['id'] == $fid) {
                            $_custom_data[$_cf2['field_label']] = is_array($v) ? implode(', ', $v) : trim($v);
                            $_price_extra += floatval($_cf2['price_extra']);
                            break;
                        }
                    }
                }
            }
            $_SESSION['cart'][$cid] = [
                'quantity'      => 1,
                'price'         => $_base_price + $_price_extra,
                'customization' => $_custom_data,
            ];
            header('location:my-cart.php'); exit();
        }
    }
}

// Wishlist handled via ajax-wishlist.php
if(isset($_POST['submit']))
{
    $qty     = intval($_POST['quality'] ?? 0);
    $price   = intval($_POST['price']   ?? 0);
    $value   = intval($_POST['value']   ?? 0);
    $name    = safe_str($con, $_POST['name']    ?? '');
    $summary = safe_str($con, $_POST['summary'] ?? '');
    $review  = safe_str($con, $_POST['review']  ?? '');

    // Handle optional review photo
    $_rv_photo = null;
    if (!empty($_FILES['review_photo']['tmp_name'])) {
        $allowed_ext = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['review_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext) && $_FILES['review_photo']['size'] < 3*1024*1024) {
            $upload_dir = 'assets/review-photos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $fname = 'rv_' . $pid . '_' . time() . '_' . mt_rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['review_photo']['tmp_name'], $upload_dir . $fname)) {
                $_rv_photo = $fname;
            }
        }
    }

    $stmt_rv = mysqli_prepare($con, "INSERT INTO productreviews(productId,quality,price,value,name,summary,review,review_photo) VALUES(?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt_rv, 'iiiissss', $pid, $qty, $price, $value, $name, $summary, $review, $_rv_photo);
    mysqli_stmt_execute($stmt_rv);
    mysqli_stmt_close($stmt_rv);
}


?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	    <?php
	    $_seo_title = $_seo_p ? htmlspecialchars($_seo_p['productName']) . ' | ' . $_SITE_NAME : 'Producto | ' . $_SITE_NAME;
	    $_seo_desc  = $_seo_p ? htmlspecialchars(substr(strip_tags($_seo_p['productDescription'] ?? ''), 0, 160)) : '';
	    $_seo_img   = $_seo_p ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/admin/productimages/' . urlencode($_seo_p['productImage'] ?? '') : '';
	    $_seo_url   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/product-details.php?product=' . $pid;
	    $_seo_price = $_seo_p ? number_format(floatval($_seo_p['productPrice']), 2, '.', '') : '0.00';
	    // Promedio de reseñas para Schema.org
	    $_rv_agg = mysqli_query($con, "SELECT COUNT(*) n, AVG((quality+price+value)/3.0) avg_r FROM productreviews WHERE productId=$pid AND status='approved'");
	    $_rv_agg_data = $_rv_agg ? mysqli_fetch_assoc($_rv_agg) : null;
	    $_rv_count = $_rv_agg_data ? intval($_rv_agg_data['n']) : 0;
	    $_rv_avg   = $_rv_agg_data ? round(floatval($_rv_agg_data['avg_r']), 1) : 0;
	    ?>
		<meta name="description" content="<?php echo $_seo_desc; ?>">
		<meta name="author" content="">
	    <meta name="keywords" content="<?php echo $_seo_p ? htmlspecialchars($_seo_p['productName']).','.$_SITE_NAME : $_SITE_NAME; ?>">
	    <meta name="robots" content="all">
	    <title><?php echo $_seo_title; ?></title>
	    <!-- Open Graph (P2) -->
	    <meta property="og:type"        content="product">
	    <meta property="og:title"       content="<?php echo $_seo_title; ?>">
	    <meta property="og:description" content="<?php echo $_seo_desc; ?>">
	    <meta property="og:image"       content="<?php echo $_seo_img; ?>">
	    <meta property="og:url"         content="<?php echo htmlspecialchars($_seo_url); ?>">
	    <meta property="og:site_name"   content="<?php echo htmlspecialchars($_SITE_NAME); ?>">
	    <meta property="product:price:amount"   content="<?php echo $_seo_price; ?>">
	    <meta property="product:price:currency" content="COP">
	    <!-- Twitter Card -->
	    <meta name="twitter:card"        content="summary_large_image">
	    <meta name="twitter:title"       content="<?php echo $_seo_title; ?>">
	    <meta name="twitter:description" content="<?php echo $_seo_desc; ?>">
	    <meta name="twitter:image"       content="<?php echo $_seo_img; ?>">
	    <!-- Schema.org Product + AggregateRating (P3) -->
	    <?php if ($_seo_p): ?>
	    <script type="application/ld+json">
	    {
	      "@context": "https://schema.org/",
	      "@type": "Product",
	      "name": <?php echo json_encode($_seo_p['productName']); ?>,
	      "description": <?php echo json_encode(substr(strip_tags($_seo_p['productDescription'] ?? ''), 0, 300)); ?>,
	      "image": <?php echo json_encode($_seo_img); ?>,
	      "brand": {"@type":"Brand","name": <?php echo json_encode($_seo_p['productCompany'] ?? ''); ?>},
	      "offers": {
	        "@type": "Offer",
	        "priceCurrency": "COP",
	        "price": "<?php echo $_seo_price; ?>",
	        "availability": "https://schema.org/InStock",
	        "url": <?php echo json_encode($_seo_url); ?>
	      }
	      <?php if ($_rv_count > 0): ?>
	      ,"aggregateRating": {
	        "@type": "AggregateRating",
	        "ratingValue": "<?php echo $_rv_avg; ?>",
	        "reviewCount": "<?php echo $_rv_count; ?>",
	        "bestRating": "5",
	        "worstRating": "1"
	      }
	      <?php endif; ?>
	    }
	    </script>
	    <?php endif; ?>
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/cosmetics.css">
	<link rel="stylesheet" href="assets/css/cart-drawer.css">
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

        <!-- Fonts --> 
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
		<?php include('includes/analytics.php'); ?>
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
<?php
$ret=mysqli_query($con,"select category.categoryName as catname,subcategory.subcategory as subcatname,products.productName as pname from products join category on category.id=products.category join subcategory on subcategory.id=products.subCategory where products.id='$pid'");
while ($ret && $rw=mysqli_fetch_array($ret)) {

?>


			<ul class="list-inline list-unstyled">
				<li><a href="index2.php">Inicio</a></li>
				<li><?php echo htmlentities($rw['catname']);?></a></li>
				<li><?php echo htmlentities($rw['subcatname']);?></li>
				<li class='active'><?php echo htmlentities($rw['pname']);?></li>
			</ul>
			<?php }?>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->
<div class="body-content outer-top-xs">
	<div class='container'>
		<div class='row single-product outer-bottom-sm '>
			<div class='col-md-2 sidebar'>
				<div class="sidebar-module-container">
<style>
/* ── L5: Zoom en imagen de producto ── */
.single-product-gallery-item { overflow: hidden; border-radius: 6px; cursor: zoom-in; }
.single-product-gallery-item img {
    transition: transform .35s cubic-bezier(.25,.46,.45,.94);
    will-change: transform;
}
.single-product-gallery-item:hover img { transform: scale(1.45); }

/* En móvil: categorías arriba, producto abajo */
@media (max-width: 991px) {
    .single-product { display: flex; flex-direction: column; }
    .single-product .sidebar { order: 1; width: 100%; }
    .single-product .sidebar .hot-deals { display: none; }
    .single-product .col-md-10 { order: 2; width: 100%; }
}
/* hot-deals reubicado por JS queda fuera del sidebar, sin restricción */
.hot-deals-mobile-relocated { display: block !important; }
@media (max-width: 767px) {
    .single-product-gallery-thumbs { display: none; }
}
.ps-sidebar { font-family: 'Open Sans', sans-serif; }
.ps-sidebar-group { background:#fff; border:1px solid #eee; border-radius:10px; margin-bottom:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.05); }
.ps-sidebar-toggle { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; cursor:pointer; font-weight:600; font-size:13px; color:#333; user-select:none; }
.ps-sidebar-toggle:hover { background:#fdf0f5; color:#c0396b; }
.ps-sidebar-toggle .ps-icon { width:28px; height:28px; border-radius:50%; background:#f5f5f5; display:flex; align-items:center; justify-content:center; font-size:13px; transition:background .2s; }
.ps-sidebar-toggle:hover .ps-icon { background:#fce4ec; color:#c0396b; }
.ps-sidebar-toggle .ps-arrow { font-size:10px; color:#aaa; transition:transform .25s; }
.ps-sidebar-group.open .ps-arrow { transform:rotate(180deg); }
.ps-sidebar-body { display:none; padding:4px 0 8px; }
.ps-sidebar-group.open .ps-sidebar-body { display:block; }
.ps-sidebar-body a { display:flex; align-items:center; gap:8px; padding:8px 16px; font-size:12.5px; color:#555; text-decoration:none; transition:background .15s,color .15s; }
.ps-sidebar-body a:hover { background:#fdf0f5; color:#c0396b; }
.ps-sidebar-body a .dot { width:6px; height:6px; border-radius:50%; background:#ddd; flex-shrink:0; transition:background .15s; }
.ps-sidebar-body a:hover .dot { background:#c0396b; }
</style>
<div class="ps-sidebar">
<div class="ps-sidebar-group">
    <div class="ps-sidebar-toggle" onclick="this.parentElement.classList.toggle('open')">
        <span><span class="ps-icon"><i class="fa fa-th-large"></i></span> &nbsp;Categorías</span>
        <span class="ps-arrow">▼</span>
    </div>
    <div class="ps-sidebar-body">
        <?php $sql=mysqli_query($con,"SELECT id,categoryName FROM category");
        while($sql && $row=mysqli_fetch_array($sql)): ?>
        <a href="category.php?cid=<?php echo $row['id']; ?>">
            <span class="dot"></span><?php echo htmlspecialchars($row['categoryName']); ?>
        </a>
        <?php endwhile; ?>
    </div>
</div>
</div><!-- /.ps-sidebar -->					<!-- ============================================== HOT DEALS ============================================== -->
<div class="sidebar-widget hot-deals wow fadeInUp">
	<h3 class="section-title">Las mejores ofertas</h3>
	<div class="owl-carousel sidebar-carousel custom-carousel owl-theme outer-top-xs">
		
								   <?php
$ret=mysqli_query($con,"select * from products order by rand() limit 4 ");
while ($ret && $rws=mysqli_fetch_array($ret)) {

?>

								        
													<div class="item">
					<div class="products">
						<div class="hot-deal-wrapper">
							<div class="image">
								<img src="admin/productimages/<?php echo htmlentities($rws['id']);?>/<?php echo htmlentities($rws['productImage1']);?>"  width="200" height="334" alt="">
							</div>
							
						</div><!-- /.hot-deal-wrapper -->

						<div class="product-info text-left m-t-20">
							<h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($rws['id']);?>"><?php echo htmlentities($rws['productName']);?></a></h3>
							<div class="rating rateit-small"></div>

							<div class="product-price">
								<?php render_price($rws, $_CURRENCY); ?>
							</div><!-- /.product-price -->

						</div><!-- /.product-info -->

						<div class="cart clearfix animate-effect">
							<div class="action">

								<div class="add-cart-button">
								<?php if($rws['productAvailability']=='In Stock'){?>
									<button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $rws['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Agregar</button>
								<?php } elseif($rws['productAvailability']=='On Order'){?>
									<button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $rws['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Bajo Pedido</button>
								<?php } else {?>
									<div style="color:red;font-weight:600">Sin Stock</div>
							<div style="margin-top:10px" id="restock-box">
							<?php $_rs_email = !empty($_SESSION['login']) ? htmlspecialchars($_SESSION['login']) : ''; ?>
							<p style="font-size:13px;color:#555"><i class="fa fa-bell-o"></i> ¿Quieres que te avisemos cuando vuelva?</p>
							<div style="display:flex;gap:6px;flex-wrap:wrap">
							<input type="email" id="restock-email" value="<?php echo $_rs_email; ?>" placeholder="Tu email" style="flex:1;min-width:180px;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px">
							<button onclick="psRestockSub(<?php echo intval($row['id']); ?>)" class="btn btn-sm btn-default" style="white-space:nowrap"><i class="fa fa-bell"></i> Avísame</button>
							</div>
							<div id="restock-msg" style="font-size:12px;margin-top:6px"></div>
							</div>
								<?php } ?>
								</div>
								
							</div><!-- /.action -->
						</div><!-- /.cart -->
					</div>	
					</div>		
					<?php } ?>        
						
	    
    </div><!-- /.sidebar-widget -->
</div>

<!-- ============================================== COLOR: END ============================================== -->
				</div>
			</div><!-- /.sidebar -->
<?php 
$ret=mysqli_query($con,"select * from products where id='$pid'");
while($ret && $row=mysqli_fetch_array($ret))
{

?>


			<div class='col-md-10'>
				<div class="row  wow fadeInUp">
					     <div class="col-xs-12 col-sm-6 col-md-5 gallery-holder">
    <div class="product-item-holder size-big single-product-gallery small-gallery">

<?php
// Build unified image list: productImage1/2/3 + product_images extras
$_gallery_imgs = [];
foreach (['productImage1','productImage2','productImage3'] as $_col) {
    if (!empty($row[$_col])) $_gallery_imgs[] = $row[$_col];
}
$_extra_q = mysqli_query($con, "SELECT imageName FROM product_images WHERE productId='" . intval($row['id']) . "' ORDER BY sortOrder");
while ($_extra_q && $_ei = mysqli_fetch_assoc($_extra_q)) {
    if (!empty($_ei['imageName'])) $_gallery_imgs[] = $_ei['imageName'];
}
if (empty($_gallery_imgs)) $_gallery_imgs[] = '';
$_pid_e = htmlentities($row['id']);
$_pname_e = htmlentities($row['productName']);
?>
        <div id="owl-single-product">
<?php foreach ($_gallery_imgs as $_gi => $_gimg): $__slide = $_gi + 1; $__href = "admin/productimages/$_pid_e/" . htmlspecialchars($_gimg); ?>
            <div class="single-product-gallery-item" id="slide<?php echo $__slide; ?>">
                <a data-lightbox="image-1" data-title="<?php echo $_pname_e; ?>" href="<?php echo $__href; ?>">
                    <img class="img-responsive" alt="" src="<?php echo $__href; ?>" width="370" height="350" />
                </a>
            </div>
<?php endforeach; ?>

        </div><!-- /.single-product-slider -->


        <div class="single-product-gallery-thumbs gallery-thumbs">

            <div id="owl-single-product-thumbnails">
<?php foreach ($_gallery_imgs as $_gi => $_gimg): $__slide = $_gi + 1; $__href = "admin/productimages/$_pid_e/" . htmlspecialchars($_gimg); ?>
                <div class="item">
                    <a class="horizontal-thumb<?php echo $_gi === 0 ? ' active' : ''; ?>" data-target="#owl-single-product" data-slide="<?php echo $__slide; ?>" href="#slide<?php echo $__slide; ?>">
                        <img class="img-responsive" width="85" alt="" src="<?php echo $__href; ?>" height="200" />
                    </a>
                </div>
<?php endforeach; ?>
            </div><!-- /#owl-single-product-thumbnails -->

            

        </div>

    </div>
</div>     			




					<div class='col-sm-6 col-md-7 product-info-block'>
						<div class="product-info">
							<h1 class="name"><?php echo htmlentities($row['productName']);?></h1>
<?php $rt=mysqli_query($con,"select * from productreviews where productId='$pid'");
$num=mysqli_num_rows($rt);
{
?>		
							<div class="rating-reviews m-t-20">
								<div class="row">
									<div class="col-sm-3">
										<div class="rating rateit-small"></div>
									</div>
									<div class="col-sm-8">
										<div class="reviews">
											<a href="#" class="lnk">(<?php echo htmlentities($num);?> Reviews)</a>
										</div>
									</div>
								</div><!-- /.row -->		
							</div><!-- /.rating-reviews -->
<?php } ?>
							<div class="stock-container info-container m-t-10">
								<div class="row">
									<div class="col-sm-3">
										<div class="stock-box">
											<span class="label">Disponibilidad :</span>
										</div>	
									</div>
									<div class="col-sm-9">
										<div class="stock-box">
											<?php
											$_av_labels=['In Stock'=>'En Stock','Out of Stock'=>'Sin Stock','On Order'=>'Bajo Pedido'];
											$_av_val=$row['productAvailability']??'';
											echo '<span class="value">'.htmlentities($_av_labels[$_av_val]??$_av_val).'</span>';
											?>
										</div>	
									</div>
								</div><!-- /.row -->	
							</div>



<div class="stock-container info-container m-t-10">
								<div class="row">
									<div class="col-sm-3">
										<div class="stock-box">
											<span class="label">Marca del producto :</span>
										</div>	
									</div>
									<div class="col-sm-9">
										<div class="stock-box">
											<span class="value"><?php echo htmlentities($row['productCompany']);?></span>
										</div>	
									</div>
								</div><!-- /.row -->	
							</div>


<div class="stock-container info-container m-t-10">
								<div class="row">
									<div class="col-sm-3">
										<div class="stock-box">
											<span class="label">Costo de envio :</span>
										</div>	
									</div>
									<div class="col-sm-9">
										<div class="stock-box">
											<span class="value"><?php if($row['shippingCharge']==0)
											{
												echo "Free";
											}
											else
											{
												echo htmlentities($row['shippingCharge']);
											}

											?></span>
										</div>	
									</div>
								</div><!-- /.row -->	
							</div>

							<?php
							// Flash sale activa para este producto
							$_fs_pd = flash_sale_get($con, $row['id']);
							?>
							<div class="price-container info-container m-t-20">
								<div class="row">


									<div class="col-sm-6">
										<div class="price-box">
											<?php if ($_fs_pd): ?>
											<div style="margin-bottom:6px">
											    <span style="background:#ff6f00;color:#fff;font-size:11px;font-weight:700;border-radius:4px;padding:2px 8px"><i class="fa fa-bolt"></i> <?php echo htmlspecialchars($_fs_pd['label']); ?></span>
											</div>
											<span style="font-size:22px;font-weight:800;color:#e8233a">$<?php echo number_format($_fs_pd['sale_price'],0,'.',','); ?></span>
											<span style="font-size:14px;color:#aaa;text-decoration:line-through;margin-left:8px">$<?php echo number_format($row['productPrice'],0,'.',','); ?></span>
											<div style="font-size:11px;color:#e65100;margin-top:4px" id="fs-countdown-pd" data-end="<?php echo htmlspecialchars($_fs_pd['ends_at']); ?>"></div>
											<script>
											(function(){
											    var el=document.getElementById('fs-countdown-pd');
											    var end=new Date(el.dataset.end.replace(' ','T')).getTime();
											    function tick(){var d=Math.max(0,end-Date.now()),h=Math.floor(d/3600000),m=Math.floor((d%3600000)/60000),s=Math.floor((d%60000)/1000);el.textContent='Termina en: '+(h>0?h+'h ':'')+String(m).padStart(2,'0')+'m '+String(s).padStart(2,'0')+'s';if(d>0)setTimeout(tick,1000);}tick();
											})();
											</script>
											<?php else: ?>
											<?php render_price($row, $_CURRENCY); ?>
											<?php endif; ?>
										</div>
									</div>




									<div class="col-sm-6">
										<div class="favorite-button m-t-10">
											<a class="btn btn-primary" data-toggle="tooltip" data-placement="right" title="Añadir a lista de deseos" href="#" data-wl-pid="<?php echo intval($row['id']); ?>">
											    <i class="fa fa-heart"></i>
											</a>
											
											</a>
										</div>
									</div>

								</div><!-- /.row -->
							</div><!-- /.price-container -->

	




							<?php
							// G3: Cargar variantes para este producto
							$_pvars = [];
							@mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_variants (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, attr_name VARCHAR(60) NOT NULL, attr_value VARCHAR(60) NOT NULL, stock_qty INT DEFAULT NULL, price_modifier DECIMAL(12,2) DEFAULT 0, INDEX(product_id))");
							$_pvq = @mysqli_query($con, "SELECT id, product_id, variant_name as attr_name, variant_value as attr_value, stock_qty, price_extra as price_modifier FROM product_variants WHERE product_id={$row['id']} ORDER BY variant_name, id");
							while ($_pvq && $_pvr = mysqli_fetch_assoc($_pvq)) $_pvars[$_pvr['attr_name']][] = $_pvr;
							if (!empty($_pvars)):
							?>
							<div style="margin-bottom:16px">
							<?php foreach($_pvars as $_attr_name => $_opts): ?>
							<div style="margin-bottom:10px">
							    <label style="font-weight:600;margin-bottom:6px;display:block"><?php echo htmlspecialchars($_attr_name); ?>:</label>
							    <div style="display:flex;flex-wrap:wrap;gap:6px" id="var-group-<?php echo md5($_attr_name); ?>">
							    <?php foreach($_opts as $_oi => $_opt): ?>
							    <button type="button"
							        class="btn btn-default btn-sm var-btn"
							        data-attr="<?php echo htmlspecialchars($_attr_name); ?>"
							        data-val="<?php echo htmlspecialchars($_opt['attr_value']); ?>"
							        data-mod="<?php echo floatval($_opt['price_modifier']); ?>"
							        data-stock="<?php echo $_opt['stock_qty'] !== null ? intval($_opt['stock_qty']) : -1; ?>"
							        onclick="selectVariant(this)"
							        <?php echo ($_opt['stock_qty'] !== null && intval($_opt['stock_qty']) <= 0) ? 'disabled style="opacity:.5;cursor:not-allowed"' : ''; ?>>
							        <?php echo htmlspecialchars($_opt['attr_value']); ?>
							        <?php if ($_opt['price_modifier'] != 0): ?>
							        <small style="font-size:10px;color:#888"><?php echo ($_opt['price_modifier']>0?'+':'').number_format($_opt['price_modifier'],0,'.',','); ?></small>
							        <?php endif; ?>
							    </button>
							    <?php endforeach; ?>
							    </div>
							</div>
							<?php endforeach; ?>
							<div id="var-stock-msg" style="font-size:12px;color:#888;margin-top:4px"></div>
							</div>
							<script>
							var _basePrice = <?php echo floatval($_fs_pd ? $_fs_pd['sale_price'] : $row['productPrice']); ?>;
							var _selectedVars = {};
							function selectVariant(btn){
							    var attr  = btn.dataset.attr;
							    var mod   = parseFloat(btn.dataset.mod||0);
							    var stock = parseInt(btn.dataset.stock);
							    // Toggle active en el grupo
							    btn.closest('[id^="var-group"]').querySelectorAll('.var-btn').forEach(function(b){ b.classList.remove('btn-primary'); b.classList.add('btn-default'); });
							    btn.classList.remove('btn-default'); btn.classList.add('btn-primary');
							    _selectedVars[attr] = {val:btn.dataset.val, mod:mod};
							    // Recalcular precio
							    var total = _basePrice;
							    Object.values(_selectedVars).forEach(function(v){ total += v.mod; });
							    document.querySelectorAll('.main-price-display').forEach(function(el){ el.textContent = '$'+Math.round(total).toLocaleString('es-CO'); });
							    // Mostrar stock
							    if(stock >= 0) document.getElementById('var-stock-msg').textContent = stock > 0 ? 'Stock: '+stock+' uds.' : 'Sin stock para esta variante';
							    else document.getElementById('var-stock-msg').textContent = '';
							}
							</script>
							<?php endif; ?>

							<div class="quantity-container info-container">
								<div class="row">

									<div class="col-sm-2">
										<span class="label">Qty :</span>
									</div>
									
									<div class="col-sm-2">
										<div class="cart-quantity">
											<div class="quant-input">
								                <div class="arrows">
								                  <div class="arrow plus gradient"><span class="ir"><i class="icon fa fa-sort-asc"></i></span></div>
								                  <div class="arrow minus gradient"><span class="ir"><i class="icon fa fa-sort-desc"></i></span></div>
								                </div>
								                <input type="text" value="1">
							              </div>
							            </div>
									</div>

									<div class="col-sm-7">
<?php if($row['productAvailability']=='In Stock'){?>
<?php if (!empty($_cust_fields)): ?>
<form method="post" id="form-customizer">
<input type="hidden" name="cust_pid" value="<?php echo $row['id']; ?>">
<div style="background:#f9f9f9;border:1px solid #e5e5e5;border-radius:6px;padding:14px;margin-bottom:10px">
<strong style="font-size:13px"><i class="fa fa-magic"></i> Personaliza tu producto</strong>
<?php foreach ($_cust_fields as $_cf): ?>
<div class="form-group" style="margin-top:10px;margin-bottom:0">
<label style="font-size:12px;font-weight:600"><?php echo htmlspecialchars($_cf['field_label']); ?><?php echo $_cf['required'] ? ' <span style="color:red">*</span>' : ''; ?><?php if ($_cf['price_extra'] > 0) echo ' <small style="color:#888">(+$'.number_format($_cf['price_extra'],0,'.',',').')</small>'; ?></label>
<?php if ($_cf['field_type'] === 'text'): ?>
<input type="text" name="cfield_<?php echo $_cf['id']; ?>" class="form-control input-sm cust-field" <?php echo $_cf['required']?'required':''; ?> data-extra="<?php echo $_cf['price_extra']; ?>">
<?php elseif ($_cf['field_type'] === 'textarea'): ?>
<textarea name="cfield_<?php echo $_cf['id']; ?>" class="form-control input-sm cust-field" rows="2" <?php echo $_cf['required']?'required':''; ?> data-extra="<?php echo $_cf['price_extra']; ?>"></textarea>
<?php elseif ($_cf['field_type'] === 'color'): ?>
<input type="color" name="cfield_<?php echo $_cf['id']; ?>" class="form-control cust-field" style="height:34px;padding:2px" data-extra="<?php echo $_cf['price_extra']; ?>">
<?php elseif ($_cf['field_type'] === 'select'): ?>
<select name="cfield_<?php echo $_cf['id']; ?>" class="form-control input-sm cust-field" <?php echo $_cf['required']?'required':''; ?> data-extra="<?php echo $_cf['price_extra']; ?>">
<option value="">— Seleccionar —</option>
<?php foreach (explode('|', $_cf['field_options']) as $_opt): $_opt=trim($_opt); if($_opt): ?><option value="<?php echo htmlspecialchars($_opt); ?>"><?php echo htmlspecialchars($_opt); ?></option><?php endif; endforeach; ?>
</select>
<?php elseif ($_cf['field_type'] === 'checkbox'): ?>
<label style="font-weight:normal"><input type="checkbox" name="cfield_<?php echo $_cf['id']; ?>" value="Sí" class="cust-field" data-extra="<?php echo $_cf['price_extra']; ?>"> Sí</label>
<?php endif; ?>
</div>
<?php endforeach; ?>
<div style="margin-top:10px;font-size:12px;color:#555">Costo de personalización: <strong id="cust-extra-total">$0</strong></div>
</div>
<button type="submit" name="add_customized" class="btn btn-primary"><i class="fa fa-shopping-cart inner-right-vs"></i> AGREGAR AL CARRITO</button>
<a href="buy-now.php?id=<?php echo $row['id']; ?>&qty=1" class="btn btn-success" style="margin-left:6px"><i class="fa fa-bolt inner-right-vs"></i> COMPRAR AHORA</a>
</form>
<?php else: ?>
										<button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart inner-right-vs"></i> AGREGAR AL CARRITO</button>
										<a href="buy-now.php?id=<?php echo $row['id']; ?>&qty=1" class="btn btn-success" style="margin-left:6px;margin-top:4px"><i class="fa fa-bolt inner-right-vs"></i> COMPRAR AHORA</a>
<?php endif; ?>
								<?php } elseif($row['productAvailability']=='On Order'){?>
										<button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart inner-right-vs"></i> BAJO PEDIDO</button>
								<?php } else {?>
							<div class="action" style="color:red">Sin Stock</div>
					<?php } ?>
									</div>

									
								</div><!-- /.row -->
							</div><!-- /.quantity-container -->

							<div class="product-social-link m-t-20 text-right">
								<span class="social-label">Compartir :</span>
								<?php
								$_share_url = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
								$_share_name = urlencode($row['productName']);
								$_wa_url = 'https://api.whatsapp.com/send?text='.urlencode($row['productName'].' — ').$_share_url;
								$_fb_url = 'https://www.facebook.com/sharer/sharer.php?u='.$_share_url;
								$_tw_url = 'https://twitter.com/intent/tweet?text='.$_share_name.'&url='.$_share_url;
								?>
								<div class="social-icons">
						            <ul class="list-inline">
						                <li><a class="fa fa-whatsapp" href="<?php echo $_wa_url; ?>" target="_blank" rel="noopener" title="Compartir por WhatsApp" style="color:#25D366;font-size:18px"></a></li>
						                <li><a class="fa fa-facebook" href="<?php echo $_fb_url; ?>" target="_blank" rel="noopener" title="Compartir en Facebook" style="color:#1877F2;font-size:18px"></a></li>
						                <li><a class="fa fa-twitter" href="<?php echo $_tw_url; ?>" target="_blank" rel="noopener" title="Compartir en Twitter" style="color:#1DA1F2;font-size:18px"></a></li>
						                <li><a class="fa fa-copy" href="#" title="Copiar enlace" onclick="navigator.clipboard.writeText(window.location.href);showToast('Enlace copiado','success');return false;" style="font-size:18px"></a></li>
						            </ul>
						        </div>
							</div>

							

							
						</div><!-- /.product-info -->
					</div><!-- /.col-sm-7 -->
				</div><!-- /.row -->

				
				<div class="product-tabs inner-bottom-xs  wow fadeInUp">
					<div class="row">
						<div class="col-sm-3">
							<ul id="product-tabs" class="nav nav-tabs nav-tab-cell">
								<li class="active"><a data-toggle="tab" href="#description">DESCRIPCIÓN</a></li>
								<li><a data-toggle="tab" href="#specifications">ESPECIFICACIONES</a></li>
								<li><a data-toggle="tab" href="#review">RESEÑAS</a></li>
								<li><a data-toggle="tab" href="#questions">PREGUNTAS</a></li>
								<?php if (count($_price_history) > 1): ?>
								<li><a data-toggle="tab" href="#price-history">HISTORIAL PRECIO</a></li>
								<?php endif; ?>
							</ul><!-- /.nav-tabs #product-tabs -->
						</div>
						<div class="col-sm-9">

							<div class="tab-content">
								
								<div id="description" class="tab-pane in active">
									<div class="product-tab">
										<p class="text"><?php echo $row['productDescription'];?></p>
									</div>
								</div><!-- /.tab-pane -->

								<!-- ESPECIFICACIONES -->
								<div id="specifications" class="tab-pane">
									<div class="product-tab">
									<?php
									// Show product variants as specs table
									$_specs_q = @mysqli_query($con, "SELECT variant_name as attr_name, GROUP_CONCAT(variant_value ORDER BY variant_value SEPARATOR ', ') as `values` FROM product_variants WHERE product_id=$pid GROUP BY variant_name");
									$_has_specs = $_specs_q && mysqli_num_rows($_specs_q) > 0;
									// Base specs from product table
									$_base_specs = [
									    'Marca / Empresa' => $row['productCompany'] ?? null,
									    'Disponibilidad'  => $row['productAvailability'] ?? null,
									    'Envío'           => !empty($row['shippingCharge']) ? '$'.number_format($row['shippingCharge'],0,'.',',') : 'Gratis',
									];
									?>
									<table class="table table-bordered" style="font-size:13px;max-width:500px">
									<tbody>
									<?php foreach ($_base_specs as $k=>$v): if ($v): ?>
									<tr><th style="width:40%;background:#f9f9f9"><?php echo htmlspecialchars($k); ?></th><td><?php echo htmlspecialchars($v); ?></td></tr>
									<?php endif; endforeach; ?>
									<?php if ($_has_specs): while ($_sp = mysqli_fetch_assoc($_specs_q)): ?>
									<tr><th style="background:#f9f9f9"><?php echo htmlspecialchars($_sp['attr_name']); ?></th><td><?php echo htmlspecialchars($_sp['values']); ?></td></tr>
									<?php endwhile; endif; ?>
									</tbody>
									</table>
									<?php if (!$_has_specs && !array_filter($_base_specs)): ?>
									<p class="text-muted" style="font-size:13px">No hay especificaciones registradas para este producto.</p>
									<?php endif; ?>
									</div>
								</div><!-- /.tab-pane #specifications -->

								<div id="review" class="tab-pane">
									<div class="product-tab">
										<style>
										.rv-pub { border-radius:12px; background:#fff; border:1px solid #f0f0f0; padding:18px 20px; margin-bottom:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
										.rv-pub-top { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
										.rv-pub-avatar { width:36px;height:36px;border-radius:50%;background:#c0396b;color:#fff;font-size:.85em;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
										.rv-pub-name { font-weight:600;font-size:.88em;color:#222; }
										.rv-pub-stars { color:#c9a84c;font-size:.9em;letter-spacing:1px; }
										.rv-pub-verified { font-size:.68em;background:#e8f5e9;color:#2e7d32;border-radius:10px;padding:2px 7px;font-weight:700; }
										.rv-pub-date { font-size:.72em;color:#bbb;margin-left:auto; }
										.rv-pub-title { font-size:.9em;font-weight:700;color:#1a1a1a;margin-bottom:4px; }
										.rv-pub-text  { font-size:.84em;color:#555;line-height:1.6; }
										.rv-empty-msg { text-align:center;padding:36px;color:#bbb;font-size:.88em; }
										.rv-cta-box { background:#f9f9f9;border-radius:12px;padding:20px;text-align:center;margin-top:8px; }
										.rv-cta-box p { font-size:.84em;color:#999;margin-bottom:12px; }
										</style>

										<div class="product-reviews">
										<?php
										$qry = mysqli_query($con, "SELECT * FROM productreviews WHERE productId='$pid' AND status='approved' ORDER BY reviewDate DESC");
										$rev_count = mysqli_num_rows($qry);
										if ($rev_count === 0): ?>
										<div class="rv-empty-msg"><i class="fa fa-comment-o" style="font-size:2em;display:block;margin-bottom:8px"></i>Aún no hay reseñas para este producto.</div>
										<?php else: while ($rvw = mysqli_fetch_assoc($qry)):
											$initial_rv = strtoupper(mb_substr($rvw['name'] ?? 'U', 0, 1));
											$stars_rv = str_repeat('★', intval($rvw['quality'])) . str_repeat('☆', 5-intval($rvw['quality']));
										?>
										<div class="rv-pub">
											<div class="rv-pub-top">
												<div class="rv-pub-avatar"><?php echo htmlspecialchars($initial_rv); ?></div>
												<div>
													<div class="rv-pub-name"><?php echo htmlspecialchars($rvw['name']); ?></div>
													<div class="rv-pub-stars"><?php echo $stars_rv; ?>
														<?php if ($rvw['verified']): ?><span class="rv-pub-verified">✓ Compra verificada</span><?php endif; ?>
													</div>
												</div>
												<span class="rv-pub-date"><?php echo date('d/m/Y', strtotime($rvw['reviewDate'])); ?></span>
											</div>
											<?php if ($rvw['title']): ?><div class="rv-pub-title">"<?php echo htmlspecialchars($rvw['title']); ?>"</div><?php endif; ?>
											<div class="rv-pub-text"><?php echo nl2br(htmlspecialchars($rvw['review'])); ?></div>
										<?php if (!empty($rvw['review_photo'])): ?>
										<div style="margin-top:10px">
											<img src="assets/review-photos/<?php echo htmlspecialchars($rvw['review_photo']); ?>"
												 onerror="this.style.display='none'"
												 style="max-width:200px;max-height:160px;border-radius:6px;border:1px solid #eee;cursor:pointer;object-fit:cover"
												 onclick="this.style.maxWidth=this.style.maxWidth==='200px'?'100%':'200px'">
										</div>
										<?php endif; ?>
										</div>
										<?php endwhile; endif; ?>
										</div>

										<!-- Formulario inline de reseña -->
										<div class="rv-cta-box" style="text-align:left">
											<?php if (empty($_SESSION['login'])): ?>
											<p style="text-align:center">¿Ya compraste este producto? <a href="login.php">Inicia sesión</a> para dejar tu reseña.</p>
											<?php else: ?>
											<h5 style="margin-bottom:12px"><i class="fa fa-star"></i> Escribe tu reseña</h5>
											<form method="post" action="product-details.php?pid=<?php echo $pid; ?>" enctype="multipart/form-data">
												<div class="row">
													<div class="col-sm-6">
														<div class="form-group">
															<label style="font-size:12px">Calidad (1-5)</label>
															<select name="quality" class="form-control input-sm"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select>
														</div>
													</div>
													<div class="col-sm-6">
														<div class="form-group">
															<label style="font-size:12px">Tu nombre</label>
															<input type="text" name="name" class="form-control input-sm" value="<?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>" required>
														</div>
													</div>
												</div>
												<div class="form-group">
													<label style="font-size:12px">Resumen breve</label>
													<input type="text" name="summary" class="form-control input-sm" placeholder="Ej: Excelente producto" required>
												</div>
												<div class="form-group">
													<label style="font-size:12px">Tu comentario</label>
													<textarea name="review" class="form-control" rows="3" style="font-size:13px" required></textarea>
												</div>
												<div class="form-group">
													<label style="font-size:12px"><i class="fa fa-camera"></i> Foto (opcional, máx. 3MB)</label>
													<input type="file" name="review_photo" accept="image/*" class="form-control input-sm" style="font-size:12px">
												</div>
												<input type="hidden" name="price" value="3">
												<input type="hidden" name="value" value="3">
												<button type="submit" name="submit" class="btn btn-primary btn-sm"><i class="fa fa-paper-plane"></i> Publicar reseña</button>
											</form>
											<?php endif; ?>
										</div>

							        </div><!-- /.product-tab -->
								</div><!-- /.tab-pane -->

								<!-- PREGUNTAS Y RESPUESTAS -->
								<div id="questions" class="tab-pane">
									<div class="product-tab">
									<?php
									mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_questions (
									    id INT AUTO_INCREMENT PRIMARY KEY,
									    product_id INT NOT NULL,
									    user_id INT DEFAULT NULL,
									    question TEXT NOT NULL,
									    answer TEXT DEFAULT NULL,
									    answered_at DATETIME DEFAULT NULL,
									    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
									    INDEX(product_id)
									)");
									// Submit question
									if (isset($_POST['submit_question']) && !empty($_SESSION['login'])) {
									    $q_txt = trim($_POST['question_text'] ?? '');
									    if ($q_txt !== '') {
									        $q_uid = intval($_SESSION['id']);
									        $q_txt_e = mysqli_real_escape_string($con, $q_txt);
									        mysqli_query($con,"INSERT INTO product_questions (product_id,user_id,question) VALUES ($pid,$q_uid,'$q_txt_e')");
									        echo '<script>showToast && showToast("¡Pregunta enviada! Te notificaremos cuando sea respondida.","success");</script>';
									    }
									}
									$_qs = mysqli_query($con,"SELECT pq.*,u.name as uname FROM product_questions pq LEFT JOIN users u ON u.id=pq.user_id WHERE pq.product_id=$pid ORDER BY pq.created_at DESC");
									?>
									<h5 style="margin-bottom:14px"><i class="fa fa-question-circle"></i> Preguntas sobre este producto</h5>
									<?php if ($_qs && mysqli_num_rows($_qs) > 0): while ($_pq = mysqli_fetch_assoc($_qs)): ?>
									<div style="border:1px solid #eee;border-radius:8px;padding:12px 16px;margin-bottom:10px;background:#fafafa">
									    <p style="margin:0 0 6px;font-size:13px"><strong><i class="fa fa-question" style="color:#337ab7"></i></strong> <?php echo nl2br(htmlspecialchars($_pq['question'])); ?></p>
									    <small style="color:#aaa">— <?php echo htmlspecialchars($_pq['uname']??'Anónimo'); ?> · <?php echo date('d/m/Y', strtotime($_pq['created_at'])); ?></small>
									    <?php if ($_pq['answer']): ?>
									    <div style="margin-top:8px;padding-left:12px;border-left:3px solid #e8233a;font-size:13px;color:#333">
									        <strong style="color:#e8233a"><i class="fa fa-reply"></i> Respuesta:</strong><br>
									        <?php echo nl2br(htmlspecialchars($_pq['answer'])); ?>
									        <br><small style="color:#aaa"><?php echo date('d/m/Y', strtotime($_pq['answered_at'])); ?></small>
									    </div>
									    <?php else: ?>
									    <div style="margin-top:6px;font-size:11px;color:#f39c12"><i class="fa fa-clock-o"></i> Pendiente de respuesta</div>
									    <?php endif; ?>
									</div>
									<?php endwhile; else: ?>
									<p class="text-muted" style="font-size:13px">Aún no hay preguntas. ¡Sé el primero en preguntar!</p>
									<?php endif; ?>

									<!-- Formulario pregunta -->
									<?php if (!empty($_SESSION['login'])): ?>
									<form method="post" style="margin-top:16px">
									    <div class="form-group">
									        <label style="font-size:12px">Tu pregunta</label>
									        <textarea name="question_text" class="form-control" rows="2" placeholder="Ej: ¿Este producto viene en talla XL?" style="font-size:13px" required></textarea>
									    </div>
									    <button type="submit" name="submit_question" class="btn btn-primary btn-sm"><i class="fa fa-paper-plane"></i> Enviar pregunta</button>
									</form>
									<?php else: ?>
									<p style="font-size:13px;margin-top:12px"><a href="login.php">Inicia sesión</a> para hacer una pregunta.</p>
									<?php endif; ?>
									</div>
								</div><!-- /.tab-pane #questions -->

								<?php if (count($_price_history) > 1): ?>
								<div id="price-history" class="tab-pane">
									<div class="product-tab" style="padding:20px 0">
										<h4 style="margin-bottom:12px;font-size:15px;font-weight:700">Evolución del precio</h4>
										<canvas id="priceChart" style="max-height:240px"></canvas>
									</div>
								</div>
								<?php endif; ?>

							</div><!-- /.tab-content -->
						</div><!-- /.col -->
					</div><!-- /.row -->
				</div><!-- /.product-tabs -->

<?php $cid=$row['category'];
			$subcid=$row['subCategory']; } ?>
				<!-- ============================================== UPSELL PRODUCTS ============================================== -->
<section class="section featured-product wow fadeInUp">
	<h3 class="section-title">Productos relacionados</h3>
	<div class="owl-carousel home-owl-carousel upsell-product custom-carousel owl-theme outer-top-xs">
	   
<?php
$_rel_q = mysqli_query($con, "SELECT * FROM products WHERE (subCategory='$subcid' OR category='$cid') AND id != $pid ORDER BY RAND() LIMIT 8");
$_has_rel = $_rel_q && mysqli_num_rows($_rel_q) > 0;
if ($_has_rel):
    while ($rw = mysqli_fetch_array($_rel_q)):
?>
        <div class="item item-carousel">
            <div class="products">
                <div class="product" style="position:relative">
                    <div class="product-image">
                        <div class="image">
                            <a href="product-details.php?pid=<?php echo htmlentities($rw['id']); ?>">
                                <?php product_img_tag($rw, 150, 240); ?>
                            </a>
                            <?php if($rw['productAvailability'] !== 'In Stock' && $rw['productAvailability'] !== 'On Order'): ?>
                            <div class="product-sold-out-overlay"><span class="product-sold-out-badge">Agotado</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-info text-left">
                        <h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($rw['id']); ?>"><?php echo htmlentities($rw['productName']); ?></a></h3>
                        <div class="rating rateit-small"></div>
                        <div class="product-price"><?php render_price($rw, $_CURRENCY); ?></div>
                    </div>
                    <div class="cart clearfix animate-effect">
                        <div class="action"><ul class="list-unstyled">
                            <li class="add-cart-button btn-group">
                                <button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $rw['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Agregar</button>
                            </li>
                        </ul></div>
                    </div>
                </div>
            </div>
        </div>
<?php
    endwhile;
else:
?>
        <div class="item" style="text-align:center;padding:40px 20px;color:#aaa;">
            <i class="fa fa-search" style="font-size:32px;display:block;margin-bottom:10px"></i>
            No hay productos relacionados disponibles
        </div>
<?php endif; ?>


<!-- ============================================== UPSELL PRODUCTS : END ============================================== -->
			
			</div><!-- /.col -->
			<div class="clearfix"></div>
		</div>
<?php include('includes/brands-slider.php');?>
</div>
</div>

<?php
// ── E4: Frecuentemente comprado junto ────────────────────────────────────────
$_together = [];
$_buyers_q = mysqli_query($con, "SELECT DISTINCT userId FROM orders WHERE productId=$pid LIMIT 100");
if ($_buyers_q && mysqli_num_rows($_buyers_q) > 0) {
    $buyer_ids = [];
    while ($_b = mysqli_fetch_assoc($_buyers_q)) $buyer_ids[] = intval($_b['userId']);
    $buyer_list = implode(',', $buyer_ids);
    $_tog_q = mysqli_query($con, "SELECT p.id, p.productName, p.productImage1, p.productPrice, p.productAvailability, COUNT(*) as freq
        FROM orders o JOIN products p ON p.id=o.productId
        WHERE o.userId IN ($buyer_list) AND o.productId != $pid
        GROUP BY p.id ORDER BY freq DESC LIMIT 6");
    while ($_tog_q && $_tr = mysqli_fetch_assoc($_tog_q)) $_together[] = $_tr;
}
?>

<?php if (!empty($_together)): ?>
<div style="background:#fff;border-top:1px solid #f0f0f0;padding:32px 0 20px">
<div class="container">
    <h3 style="font-size:18px;font-weight:700;margin-bottom:20px;color:#333">
        <i class="fa fa-users" style="color:#e8233a;margin-right:8px"></i>Frecuentemente comprado junto
    </h3>
    <div class="row">
    <?php foreach ($_together as $_tp): ?>
    <div class="col-xs-6 col-sm-4 col-md-2" style="margin-bottom:16px">
        <div style="border:1px solid #eee;border-radius:6px;padding:10px;text-align:center;background:#fff;height:100%">
            <a href="product-details.php?pid=<?php echo $_tp['id']; ?>">
                <img src="admin/productimages/<?php echo intval($_tp['id']); ?>/<?php echo htmlspecialchars($_tp['productImage1']); ?>"
                     onerror="this.src='assets/images/blank.gif'"
                     style="max-width:100%;max-height:100px;object-fit:contain;display:block;margin:0 auto 8px">
            </a>
            <div style="font-size:11px;font-weight:600;color:#333;margin-bottom:4px;line-height:1.3">
                <a href="product-details.php?pid=<?php echo $_tp['id']; ?>" style="color:inherit"><?php echo htmlspecialchars(substr($_tp['productName'],0,40)); ?></a>
            </div>
            <div style="color:#e8233a;font-weight:700;font-size:13px;margin-bottom:8px">
                $<?php echo number_format($_tp['productPrice'],0,',','.'); ?>
            </div>
            <?php if ($_tp['productAvailability'] !== 'Out of Stock'): ?>
            <button class="btn btn-primary btn-xs btn-add-to-cart" data-id="<?php echo $_tp['id']; ?>" style="width:100%;font-size:11px">
                <i class="fa fa-shopping-cart"></i> Agregar
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</div>
<?php endif; ?>

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
	<script src="assets/js/wishlist.js"></script>

	<!-- For demo purposes – can be removed on production -->
	
	
	
	<script>
		$(document).ready(function(){
			if ($.fn.switchstylesheet) $(".changecolor").switchstylesheet( { seperator:"color"} );
			$('.show-theme-options').click(function(){
				$(this).parent().toggleClass('open');
				return false;
			});

			// En móvil: mover "Las Mejores Ofertas" después de productos relacionados
			if ($(window).width() < 768) {
				var $hotDeals = $('.single-product .sidebar .hot-deals');
				var $afterRelated = $('section.featured-product');
				if ($hotDeals.length && $afterRelated.length) {
					$hotDeals.css('display', 'block').insertAfter($afterRelated);
				}
			}
		});

		$(window).bind("load", function() {
		   $('.show-theme-options').delay(2000).trigger('click');
		});
	</script>
	<!-- For demo purposes – can be removed on production : End -->

	

<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/quickview.js"></script>
<script src="assets/js/cookies.js"></script>
<script>
// GA4 view_item + Meta Pixel ViewContent (Y1+Y2)
(function(){
    var _p = {
        item_id: '<?php echo intval($row['id']); ?>',
        item_name: <?php echo json_encode($row['productName']); ?>,
        price: <?php echo floatval($row['productPrice']); ?>,
        currency: 'COP'
    };
    if (window.psGA4Event) window.psGA4Event('view_item', { currency:'COP', value:_p.price, items:[_p] });
    if (window.psFBEvent) window.psFBEvent('ViewContent', { content_ids:[_p.item_id], content_type:'product', value:_p.price, currency:'COP' });
})();
(function(){
    var KEY='ps_recently_viewed';
    var prod={id:<?php echo intval($row['id']); ?>,name:<?php echo json_encode($row['productName']); ?>,img:<?php echo json_encode($_gallery_imgs[0]??''); ?>};
    try{
        var list=JSON.parse(localStorage.getItem(KEY)||'[]');
        list=list.filter(function(p){ return p.id!==prod.id; });
        list.unshift(prod);
        list=list.slice(0,5);
        localStorage.setItem(KEY,JSON.stringify(list));
    }catch(e){}
})();
</script>
<?php if (count($_price_history) > 1): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var labels = <?php echo json_encode(array_column($_price_history, 'recorded_at')); ?>;
    var prices = <?php echo json_encode(array_map(function($r){ return (float)$r['price']; }, $_price_history)); ?>;
    var ctx = document.getElementById('priceChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Precio ($)',
                data: prices,
                borderColor: '#e8233a',
                backgroundColor: 'rgba(232,35,58,.08)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#e8233a',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ return '$' + c.parsed.y.toLocaleString('es-CO'); } } } },
            scales: { y: { ticks: { callback: function(v){ return '$' + v.toLocaleString('es-CO'); } } } }
        }
    });
    // Activate tab click
    $('a[href="#price-history"]').on('shown.bs.tab', function(){ window.dispatchEvent(new Event('resize')); });
})();
</script>
<?php endif; ?>
<?php if (!empty($_cust_fields)): ?>
<script>
(function(){
    function updateCustExtra(){
        var total = 0;
        document.querySelectorAll('.cust-field').forEach(function(el){
            var extra = parseFloat(el.dataset.extra) || 0;
            if (!extra) return;
            if (el.type === 'checkbox') { if (el.checked) total += extra; }
            else if (el.tagName === 'SELECT') { if (el.value) total += extra; }
            else if (el.value.trim()) total += extra;
        });
        var el = document.getElementById('cust-extra-total');
        if (el) el.textContent = '$' + total.toLocaleString('es-CO');
    }
    document.querySelectorAll('.cust-field').forEach(function(el){
        el.addEventListener('input', updateCustExtra);
        el.addEventListener('change', updateCustExtra);
    });
    updateCustExtra();
})();
</script>
<?php endif; ?>

<script>
function psRestockSub(pid){
    var email = document.getElementById('restock-email').value.trim();
    var msg   = document.getElementById('restock-msg');
    if(!email){msg.innerHTML='<span style="color:red">Ingresa tu email.</span>';return;}
    var fd = new FormData();
    fd.append('pid', pid);
    fd.append('email', email);
    fd.append('action','subscribe');
    fetch('ajax-restock-notify.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        msg.innerHTML='<span style="color:'+(d.ok?'#27ae60':'red')+'">'+(d.msg||'')+'</span>';
        if(d.ok) document.getElementById('restock-box').style.opacity='0.6';
    });
}
</script>
</body>
</html>