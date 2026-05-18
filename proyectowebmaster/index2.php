<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');
include('includes/price-display.php');
if(isset($_GET['action']) && $_GET['action']=="add"){
	$id=intval($_GET['id']);
	if(isset($_SESSION['cart'][$id])){
		$_SESSION['cart'][$id]['quantity']++;
	}else{
		$sql_p="SELECT * FROM products WHERE id={$id}";
		$query_p=mysqli_query($con,$sql_p);
		if(mysqli_num_rows($query_p)!=0){
			$row_p=mysqli_fetch_array($query_p);
			$_SESSION['cart'][$row_p['id']]=array("quantity" => 1, "price" => $row_p['productPrice']);
		
		}else{
			$message="Product ID is invalid";
		}
	}
		echo "<script>showToast('Producto agregado al carrito','success')</script>";
		echo "<script type='text/javascript'> document.location ='my-cart.php'; </script>";
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

	    <title><?php echo $_SITE_NAME; ?></title>

	    <!-- Bootstrap Core CSS -->
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    
	    <!-- Customizable CSS -->
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/cosmetics.css">
	<link rel="stylesheet" href="assets/css/cart-drawer.css">
	    <link rel="stylesheet" href="assets/css/owl.carousel.css">
		<link rel="stylesheet" href="assets/css/owl.transitions.css">
		<!---->
		<link href="assets/css/lightbox.css" rel="stylesheet">
		<link rel="stylesheet" href="assets/css/animate.min.css">
		<link rel="stylesheet" href="assets/css/rateit.css">
		<link rel="stylesheet" href="assets/css/bootstrap-select.min.css">

		<style>
/* ── Botones custom carrusel productos destacados ── */
/* Ocultar controles nativos de owl en esta sección */
#product-tabs-slider .owl-controls { display: none !important; }

#product-tabs-slider .product-slider {
    position: relative;
    padding: 0 50px;
}
.ps-owl-btn {
    position: absolute;
    top: 90px;   /* centro aproximado del área de imagen */
    z-index: 20;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #9575cd;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 12px rgba(149,117,205,0.5);
    animation: ps-pulse 1.8s ease-in-out infinite;
    transition: background 0.2s;
    outline: none;
}
.ps-owl-btn.ps-prev { left: 4px; }
.ps-owl-btn.ps-next { right: 4px; }
.ps-owl-btn:hover {
    background: #7c52c8;
    animation: none;
    transform: scale(1.12);
}
@keyframes ps-pulse {
    0%   { box-shadow: 0 0 0 0   rgba(149,117,205,0.65); }
    65%  { box-shadow: 0 0 0 12px rgba(149,117,205,0);   }
    100% { box-shadow: 0 0 0 0   rgba(149,117,205,0);    }
}
</style>
	<!-- Demo Purpose Only. Should be removed in production -->
		<link rel="stylesheet" href="assets/css/config.css">

		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		
		<!-- Favicon -->
		<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
		<?php include('includes/analytics.php'); ?>
		<!-- PWA (W1) -->
		<link rel="manifest" href="manifest.json">
		<meta name="theme-color" content="#e8233a">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
		<meta name="apple-mobile-web-app-title" content="Personal Shopper">

	</head>
    <body class="cnt-home">
	
		
	
		<!-- ============================================== HEADER ============================================== -->
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>

<!-- ============================================== HEADER : END ============================================== -->
<div class="body-content outer-top-xs" id="top-banner-and-menu">
	<div class="container">
		<div class="furniture-container homepage-container">
		<div class="row">
		
			<div class="col-xs-12 col-sm-12 col-md-12 homebanner-holder">
				<!-- ========================================== SECTION – HERO ========================================= -->
			
<div id="hero" class="homepage-slider3">
	<div id="owl-main" class="owl-carousel owl-inner-nav owl-ui-sm">
	<?php
	$sl_res = mysqli_query($con, "SELECT * FROM sliders WHERE active=1
    AND (active_from IS NULL OR active_from <= CURDATE())
    AND (active_to IS NULL OR active_to >= CURDATE())
    ORDER BY sort_order ASC");
	while($sl = mysqli_fetch_assoc($sl_res)):
		$sl_url = !empty($sl['keyword'])
			? 'search-result.php?product=' . urlencode($sl['keyword'])
			: '#';
	?>
		<div class="full-width-slider">
			<a href="<?php echo htmlspecialchars($sl_url); ?>" style="display:block;width:100%;height:100%">
				<div class="item" style="background-image: url(<?php echo htmlspecialchars($sl['image_path']); ?>); cursor:pointer;">
				</div>
			</a>
		</div>
	<?php endwhile; ?>
	</div><!-- /.owl-carousel -->
</div>
			
<!-- ========================================= SECTION – HERO : END ========================================= -->	
				<!-- ============================================== INFO BOXES ============================================== -->
<div class="info-boxes wow fadeInUp">
	<div class="info-boxes-inner">
		<div class="row">
			<div class="col-md-6 col-sm-4 col-lg-4">
				<div class="info-box">
					<div class="row">
						<div class="col-xs-2">
						     <i class="icon fa fa-dollar"></i>
						</div>
						<div class="col-xs-10">
							<h4 class="info-box-heading green">regresamos tu dinero</h4>
						</div>
					</div>	
					<h6 class="text">30 días de garantia en tus pagos</h6>
				</div>
			</div><!-- .col -->

			<div class="hidden-md col-sm-4 col-lg-4">
				<div class="info-box">
					<div class="row">
						<div class="col-xs-2">
							<i class="icon fa fa-truck"></i>
						</div>
						<div class="col-xs-10">
							<h4 class="info-box-heading orange">envio gratis</h4>
						</div>
					</div>
					<h6 class="text">Envio gratis por compras superiores a $200.000</h6>	
				</div>
			</div><!-- .col -->

			<div class="col-md-6 col-sm-4 col-lg-4">
				<div class="info-box">
					<div class="row">
						<div class="col-xs-2">
							<i class="icon fa fa-gift"></i>
						</div>
						<div class="col-xs-10">
							<h4 class="info-box-heading red">Venta especial</h4>
						</div>
					</div>
					<h6 class="text">Todos los productos tienen 20% de descuento </h6>	
				</div>
			</div><!-- .col -->
		</div><!-- /.row -->
	</div><!-- /.info-boxes-inner -->
	
</div><!-- /.info-boxes -->
<!-- ============================================== INFO BOXES : END ============================================== -->		
			</div><!-- /.homebanner-holder -->
			
		</div><!-- /.row -->


<!-- ============================================== OFERTA CON COUNTDOWN — D3 ============================================== -->
<?php
// Auto-create setting if not exists
mysqli_query($con, "INSERT IGNORE INTO settings(setting_key, setting_value) VALUES
    ('deal_title',    'Oferta especial del día'),
    ('deal_subtitle', 'No te pierdas estos precios exclusivos por tiempo limitado'),
    ('deal_end',      ''),
    ('deal_active',   '0')
");
$_deal = [];
$_dr = mysqli_query($con, "SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('deal_title','deal_subtitle','deal_end','deal_active')");
while ($_drow = mysqli_fetch_assoc($_dr)) $_deal[$_drow['setting_key']] = $_drow['setting_value'];
$_show_deal = ($_deal['deal_active'] ?? '0') === '1' && !empty($_deal['deal_end']);
?>
<?php if ($_show_deal): ?>
<div class="countdown-section wow fadeInUp" style="background:linear-gradient(135deg,#c0396b 0%,#e8233a 100%);padding:28px 0;margin-bottom:0">
    <div class="container">
        <div class="row" style="display:flex;align-items:center;flex-wrap:wrap;gap:20px">
            <div class="col-md-5">
                <h3 style="color:#fff;margin:0 0 6px;font-size:1.6em;font-weight:800"><?php echo htmlspecialchars($_deal['deal_title']); ?></h3>
                <p style="color:rgba(255,255,255,.85);margin:0;font-size:13px"><?php echo htmlspecialchars($_deal['deal_subtitle']); ?></p>
            </div>
            <div class="col-md-4">
                <div class="ps-countdown" data-end="<?php echo htmlspecialchars($_deal['deal_end']); ?>"
                     style="display:flex;gap:10px;align-items:center">
                    <?php foreach(['days'=>'Días','hours'=>'Hrs','minutes'=>'Min','seconds'=>'Seg'] as $_ck=>$_cl): ?>
                    <div style="text-align:center">
                        <div id="cd-<?php echo $_ck; ?>"
                             style="background:rgba(255,255,255,.2);border-radius:8px;min-width:54px;padding:8px 6px;font-size:28px;font-weight:800;color:#fff;line-height:1">00</div>
                        <div style="color:rgba(255,255,255,.75);font-size:10px;margin-top:3px;text-transform:uppercase;letter-spacing:.5px"><?php echo $_cl; ?></div>
                    </div>
                    <?php if($_ck!=='seconds'): ?>
                    <div style="font-size:24px;color:rgba(255,255,255,.6);font-weight:700;margin-bottom:14px">:</div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-3" style="text-align:right">
                <a href="search-result.php?product=" class="btn" style="background:#fff;color:#e8233a;font-weight:700;padding:10px 22px;border-radius:25px;font-size:13px">
                    Ver ofertas <i class="fa fa-arrow-right" style="margin-left:6px"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    var end = new Date('<?php echo addslashes($_deal['deal_end']); ?>').getTime();
    function tick(){
        var now  = Date.now();
        var diff = Math.max(0, end - now);
        var d = Math.floor(diff/86400000);
        var h = Math.floor((diff%86400000)/3600000);
        var m = Math.floor((diff%3600000)/60000);
        var s = Math.floor((diff%60000)/1000);
        var pad = function(n){ return String(n).padStart(2,'0'); };
        var ids = {days:d,hours:h,minutes:m,seconds:s};
        for(var k in ids){
            var el = document.getElementById('cd-'+k);
            if(el) el.textContent = pad(ids[k]);
        }
        if(diff > 0) setTimeout(tick, 1000);
        else {
            var sec = document.querySelector('.countdown-section');
            if(sec) sec.style.display='none';
        }
    }
    tick();
})();
</script>
<?php endif; ?>
<!-- ============================================== OFERTA CON COUNTDOWN : END ============================================== -->

<!-- ============================================== FLASH SALES ============================================== -->
<?php
include_once('includes/flash-sale.php');
$_flash_items = flash_sales_active($con);
if (!empty($_flash_items)):
?>
<div class="wow fadeInUp" style="background:#fff8e1;border-top:3px solid #ff6f00;border-bottom:3px solid #ff6f00;padding:24px 0;margin-bottom:0">
<div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
        <h3 style="margin:0;color:#e65100;font-weight:800"><i class="fa fa-bolt"></i> Flash Sales</h3>
        <span style="font-size:12px;color:#888">Precios válidos mientras dure el tiempo indicado</span>
    </div>
    <div class="row">
    <?php foreach($_flash_items as $_fi):
        $discount_pct = $_fi['productPrice']>0 ? round((1-$_fi['sale_price']/$_fi['productPrice'])*100) : 0;
        $ends_ts = strtotime($_fi['ends_at']);
    ?>
    <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:16px">
        <a href="product-details.php?pid=<?php echo $_fi['pid']; ?>" style="text-decoration:none;color:inherit">
        <div style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);transition:transform .2s" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
            <div style="position:relative">
                <img src="admin/productimages/<?php echo $_fi['pid'].'/'.htmlspecialchars($_fi['productImage1']); ?>"
                     style="width:100%;height:160px;object-fit:cover" onerror="this.src='assets/images/placeholder.jpg'">
                <?php if($discount_pct>0): ?>
                <span style="position:absolute;top:8px;left:8px;background:#e8233a;color:#fff;font-weight:700;font-size:12px;border-radius:4px;padding:2px 7px">-<?php echo $discount_pct; ?>%</span>
                <?php endif; ?>
                <span style="position:absolute;top:8px;right:8px;background:#ff6f00;color:#fff;font-size:10px;border-radius:4px;padding:2px 6px"><?php echo htmlspecialchars($_fi['label']); ?></span>
            </div>
            <div style="padding:10px 12px">
                <div style="font-size:13px;font-weight:600;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($_fi['productName']); ?></div>
                <div style="display:flex;align-items:baseline;gap:6px">
                    <span style="font-size:16px;font-weight:800;color:#e8233a">$<?php echo number_format($_fi['sale_price'],0,'.',','); ?></span>
                    <span style="font-size:12px;color:#aaa;text-decoration:line-through">$<?php echo number_format($_fi['productPrice'],0,'.',','); ?></span>
                </div>
                <div class="flash-countdown" data-end="<?php echo $_fi['ends_at']; ?>"
                     style="font-size:11px;color:#e65100;margin-top:5px;font-weight:600"></div>
            </div>
        </div>
        </a>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</div>
<script>
document.querySelectorAll('.flash-countdown').forEach(function(el){
    var end = new Date(el.dataset.end.replace(' ','T')).getTime();
    function tick(){
        var diff = Math.max(0, end - Date.now());
        var h = Math.floor(diff/3600000);
        var m = Math.floor((diff%3600000)/60000);
        var s = Math.floor((diff%60000)/1000);
        el.textContent = 'Termina en: ' + (h>0?h+'h ':'')+String(m).padStart(2,'0')+'m '+String(s).padStart(2,'0')+'s';
        if(diff>0) setTimeout(tick,1000);
        else el.textContent = 'Oferta expirada';
    }
    tick();
});
</script>
<?php endif; ?>
<!-- ============================================== FLASH SALES : END ============================================== -->

		<!-- ============================================== SCROLL TABS ============================================== -->
<?php
// Pre-fetch: top 2 categories for dynamic tabs
$_cats_res = mysqli_query($con, "SELECT id, categoryName FROM category ORDER BY id ASC LIMIT 2");
$_cats = [];
while ($_cr = mysqli_fetch_assoc($_cats_res)) $_cats[] = $_cr;
?>
		<div id="product-tabs-slider" class="scroll-tabs inner-bottom-vs  wow fadeInUp">
			<div class="more-info-tab clearfix">
			   <h3 class="new-product-title pull-left">Productos destacados</h3>
				<ul class="nav nav-tabs nav-tab-line pull-right" id="new-products-1">
					<li class="active"><a href="#tab-top-sellers" data-toggle="tab">Más vendidos</a></li>
					<?php foreach ($_cats as $_cat): ?>
					<li><a href="#tab-cat-<?php echo intval($_cat['id']); ?>" data-toggle="tab"><?php echo htmlspecialchars($_cat['categoryName']); ?></a></li>
					<?php endforeach; ?>
				</ul><!-- /.nav-tabs -->
			</div>

			<div class="tab-content outer-top-xs">
				<!-- TAB: Más vendidos -->
				<div class="tab-pane in active" id="tab-top-sellers">
					<div class="product-slider">
						<div class="owl-carousel home-owl-carousel custom-carousel owl-theme" data-item="4">
<?php
$ret = mysqli_query($con,
	"SELECT p.*, COALESCE(SUM(o.quantity),0) as total_sold
	 FROM products p
	 LEFT JOIN orders o ON o.productId = p.id AND o.paymentMethod IS NOT NULL
	 GROUP BY p.id
	 ORDER BY total_sold DESC
	 LIMIT 32");
while ($row = mysqli_fetch_array($ret)):
?>
		<div class="item item-carousel">
			<div class="products">
	<div class="product">
		<div class="product-image">
			<div class="image">
				<a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>">
				<?php product_img_tag($row, 180, 300); ?></a>
			</div>
		</div>
		<div class="product-info text-left">
			<h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>"><?php echo htmlentities($row['productName']);?></a></h3>
			<div class="rating rateit-small"></div>
			<div class="product-price">
				<?php render_price($row, $_CURRENCY); ?>
			</div>
		</div>
		<div class="cart clearfix animate-effect">
			<div class="action">
				<ul class="list-unstyled">
					<li class="add-cart-button btn-group">
						<?php if ($row['productAvailability']=='In Stock'): ?>
						<button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Agregar</button>
						<?php elseif ($row['productAvailability']=='On Order'): ?>
						<button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Bajo Pedido</button>
						<?php else: ?>
						<div class="action" style="color:red">Sin Stock</div>
						<?php endif; ?>
					</li>
					<li class="lnk wishlist">
						<a href="#" data-wl-pid="<?php echo intval($row['id']); ?>" title="Añadir a lista de deseos"><i class="icon fa fa-heart"></i></a>
					</li>
				</ul>
			</div>
		</div>
	</div>
			</div>
		</div>
<?php endwhile; ?>
						</div><!-- /.home-owl-carousel -->
					</div><!-- /.product-slider -->
				</div><!-- /.tab-pane -->

				<!-- TABS: Categorías dinámicas -->
				<?php foreach ($_cats as $_cat):
					$_cat_id = intval($_cat['id']);
					$_ret_cat = mysqli_query($con, "SELECT * FROM products WHERE category=$_cat_id LIMIT 32");
				?>
				<div class="tab-pane" id="tab-cat-<?php echo $_cat_id; ?>">
					<div class="product-slider">
						<div class="owl-carousel home-owl-carousel custom-carousel owl-theme" data-item="4">
				<?php while ($row = mysqli_fetch_array($_ret_cat)): ?>
		<div class="item item-carousel">
			<div class="products">
	<div class="product">
		<div class="product-image">
			<div class="image">
				<a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>">
				<?php product_img_tag($row, 180, 300); ?></a>
			</div>
		</div>
		<div class="product-info text-left">
			<h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>"><?php echo htmlentities($row['productName']);?></a></h3>
			<div class="rating rateit-small"></div>
			<div class="product-price">
				<?php render_price($row, $_CURRENCY); ?>
			</div>
		</div>
		<div class="cart clearfix animate-effect">
			<div class="action">
				<ul class="list-unstyled">
					<li class="add-cart-button btn-group">
						<?php if ($row['productAvailability']=='In Stock'): ?>
						<button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Agregar</button>
						<?php elseif ($row['productAvailability']=='On Order'): ?>
						<button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Bajo Pedido</button>
						<?php else: ?>
						<div class="action" style="color:red">Sin Stock</div>
						<?php endif; ?>
					</li>
					<li class="lnk wishlist">
						<a href="#" data-wl-pid="<?php echo intval($row['id']); ?>" title="Añadir a lista de deseos"><i class="icon fa fa-heart"></i></a>
					</li>
				</ul>
			</div>
		</div>
	</div>
			</div>
		</div>
				<?php endwhile; ?>
						</div><!-- /.home-owl-carousel -->
					</div><!-- /.product-slider -->
				</div><!-- /.tab-pane -->
				<?php endforeach; ?>

			</div><!-- /.tab-content -->
		</div><!-- /#product-tabs-slider -->
		    

         <!-- ============================================== CARRUSELES DINÁMICOS ============================================== -->
<?php
$_hc_res = mysqli_query($con, "SELECT * FROM home_carousels WHERE active=1 ORDER BY sort_order ASC");
$_hc_rows = $_hc_res ? mysqli_fetch_all($_hc_res, MYSQLI_ASSOC) : [];
$_hc_chunks = array_chunk($_hc_rows, 2);
foreach ($_hc_chunks as $_hc_pair):
?>
			<div class="sections prod-slider-small outer-top-small">
				<div class="row">
<?php foreach ($_hc_pair as $_hc):
    $es_hc = mysqli_real_escape_string($con, $_hc["search_value"]);
    if ($_hc["search_type"] === "category") {
        $_hc_q = mysqli_query($con, "SELECT * FROM products WHERE category='" . $es_hc . "' ORDER BY RAND() LIMIT 20");
    } elseif ($_hc["search_type"] === "subcategory") {
        $_hc_q = mysqli_query($con, "SELECT * FROM products WHERE subCategory='" . $es_hc . "' ORDER BY RAND() LIMIT 20");
    } else {
        $_hc_q = mysqli_query($con, "SELECT * FROM products WHERE productDescription LIKE '%" . $es_hc . "%' OR productName LIKE '%" . $es_hc . "%' ORDER BY RAND() LIMIT 20");
    }
    $_hc_items = $_hc_q ? mysqli_fetch_all($_hc_q, MYSQLI_ASSOC) : [];
?>
				<div class="col-md-6">
					<section class="section">
						<h3 class="section-title"><?php echo htmlspecialchars($_hc["title"]); ?></h3>
						<?php if (empty($_hc_items)): ?>
						<p class="text-muted" style="font-style:italic;font-size:.85em">No hay productos para este criterio.</p>
						<?php else: ?>
						<div class="owl-carousel homepage-owl-carousel custom-carousel outer-top-xs owl-theme" data-item="2">
						<?php foreach ($_hc_items as $row): ?>
						<div class="item item-carousel">
							<div class="products"><div class="product">
								<div class="product-image"><div class="image">
									<a href="product-details.php?pid=<?php echo htmlentities($row["id"]); ?>"><?php product_img_tag($row, 180, 300); ?></a>
								</div></div>
								<div class="product-info text-left">
									<h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($row["id"]); ?>"><?php echo htmlentities($row["productName"]); ?></a></h3>
									<div class="rating rateit-small"></div>
									<div class="product-price"><?php render_price($row, $_CURRENCY); ?></div>
								</div>
								<?php if ($row["productAvailability"] === "In Stock"): ?>
								<div class="action"><button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $row["id"]; ?>" type="button"><i class="fa fa-shopping-cart"></i> Agregar</button></div>
								<?php elseif ($row["productAvailability"] === "On Order"): ?>
								<div class="action"><button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $row["id"]; ?>" type="button"><i class="fa fa-shopping-cart"></i> Bajo Pedido</button></div>
								<?php else: ?>
								<div class="action" style="color:red">Sin Stock</div>
								<?php endif; ?>
							</div></div>
						</div>
						<?php endforeach; ?>
						</div><!-- /.homepage-owl-carousel -->
						<?php endif; ?>
					</section>
				</div>
<?php endforeach; ?>
				</div>
			</div>
<?php endforeach; ?>
		<!-- ============================================== CARRUSELES DINÁMICOS : END ============================================== -->

		


</div>
<?php include('includes/brands-slider.php');?>
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

			// Crear botones propios para cada carrusel en #product-tabs-slider
			$('#product-tabs-slider .product-slider').each(function() {
				var $slider  = $(this);
				var $owl     = $slider.find('.home-owl-carousel');
				var uniq     = 'owl-' + Math.random().toString(36).slice(2,7);
				$owl.attr('id', uniq);

				var $prev = $('<button class="ps-owl-btn ps-prev" aria-label="Anterior"><i class="fa fa-chevron-left"></i></button>');
				var $next = $('<button class="ps-owl-btn ps-next" aria-label="Siguiente"><i class="fa fa-chevron-right"></i></button>');
				$slider.append($prev).append($next);

				$prev.on('click', function(){ $owl.trigger('owl.prev'); });
				$next.on('click', function(){ $owl.trigger('owl.next'); });

				// Autoplay manual: funciona aunque items == total visible
				var paused = false;
				$owl.on('mouseenter', function(){ paused = true; })
				    .on('mouseleave', function(){ paused = false; });
				$prev.on('mouseenter', function(){ paused = true; }).on('mouseleave', function(){ paused = false; });
				$next.on('mouseenter', function(){ paused = true; }).on('mouseleave', function(){ paused = false; });
				setInterval(function(){
					if (!paused) $owl.trigger('owl.next');
				}, 3500);
			});

			// Re-initialize owl carousel when switching tabs (fixes hidden-tab initialization)
			$('#new-products-1 a[data-toggle="tab"]').on('shown shown.bs.tab', function() {
				$(window).trigger('resize');
				if (typeof echo !== 'undefined') echo.render();
				var $pane = $($(this).attr('href'));
				$pane.find('.home-owl-carousel').each(function() {
					$(this).trigger('owl.play', 3500);
				});
			});
			// Force echo.js to scan after owl carousel builds its items
			$(window).on('load', function() {
				if (typeof echo !== 'undefined') echo.render();
			});
		});

		$(window).bind("load", function() {
		   $('.show-theme-options').delay(2000).trigger('click');
		});
	</script>
	<!-- For demo purposes – can be removed on production : End -->

	

<script src="assets/js/wishlist.js"></script>
<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/product-hover-gallery.js"></script>
<script src="assets/js/cookies.js"></script>

<!-- BB4: PWA install prompt -->
<div id="pwa-install-bar" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#e8233a;color:#fff;padding:12px 20px;z-index:9999;align-items:center;justify-content:space-between;box-shadow:0 -2px 12px rgba(0,0,0,.15)">
<span><i class="fa fa-download"></i> Instala nuestra app para una mejor experiencia</span>
<div>
<button id="pwa-install-btn" style="background:#fff;color:#e8233a;border:none;border-radius:6px;padding:7px 18px;font-weight:600;cursor:pointer;margin-right:8px">Instalar</button>
<button onclick="document.getElementById('pwa-install-bar').style.display='none'" style="background:transparent;color:#fff;border:1px solid rgba(255,255,255,.5);border-radius:6px;padding:7px 12px;cursor:pointer">No gracias</button>
</div>
</div>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/proyectowebmaster/sw.js').catch(()=>{});
}
var _pwaPrompt = null;
window.addEventListener('beforeinstallprompt', function(e){
  e.preventDefault();
  _pwaPrompt = e;
  document.getElementById('pwa-install-bar').style.display = 'flex';
});
document.getElementById('pwa-install-btn') && document.getElementById('pwa-install-btn').addEventListener('click', function(){
  if (_pwaPrompt) { _pwaPrompt.prompt(); _pwaPrompt.userChoice.then(function(){ document.getElementById('pwa-install-bar').style.display='none'; }); }
});
window.addEventListener('appinstalled', function(){ document.getElementById('pwa-install-bar').style.display='none'; });
</script>
</body>
</html>