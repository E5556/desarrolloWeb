<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/price-display.php');
include('includes/search-engine.php');
$product_search = trim($_POST['product'] ?? $_GET['product'] ?? '');
// Paginación y ordenamiento
$_sort_key  = in_array($_GET['ord'] ?? '', ['relevancia','precio_asc','precio_desc']) ? $_GET['ord'] : 'relevancia';
$_order_map = ['relevancia'=>'relevance','precio_asc'=>'price_asc','precio_desc'=>'price_desc'];
$_per_page  = 12;
$_page      = max(1, intval($_GET['pag'] ?? 1));
$_offset    = ($_page - 1) * $_per_page;
function _sr_url($params) {
    global $_GET, $product_search;
    $base = array_merge($_GET, $params);
    $base['product'] = $product_search;
    return 'search-result.php?' . http_build_query($base);
}
// Motor de búsqueda mejorado (S1)
$_search_result = ps_search($con, $product_search, $_per_page, $_offset, $_order_map[$_sort_key]);
$_total_count   = $_search_result['total'];
$_total_pages   = max(1, ceil($_total_count / $_per_page));
$_did_you_mean  = $_total_count === 0 ? ps_search_did_you_mean($con, $product_search) : null;
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
						echo "<script>showToast('Producto agregado al carrito','success')</script>";
		echo "<script type='text/javascript'> document.location ='my-cart.php'; </script>";
		}else{
			$message="Product ID is invalid";
		}
	}
}
// COde for Wishlist
// Wishlist handled via ajax-wishlist.php
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

	    <title>Categorías | <?php echo $_SITE_NAME; ?></title>

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
                <?php if(!empty($product_search)): ?>
                <li><a href="search-result.php">Búsqueda</a></li>
                <li class="active">"<?php echo htmlspecialchars($product_search); ?>"</li>
                <?php else: ?>
                <li class="active">Búsqueda</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<div class="body-content outer-top-xs">
	<div class='container'>
		<div class='row outer-bottom-sm'>
			<div class='col-md-2 sidebar'>
<style>
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
<div class="ps-sidebar-group">
    <div class="ps-sidebar-toggle" onclick="this.parentElement.classList.toggle('open')">
        <span><span class="ps-icon"><i class="fa fa-list"></i></span> &nbsp;Subcategorías</span>
        <span class="ps-arrow">▼</span>
    </div>
    <div class="ps-sidebar-body">
        <?php $sql=mysqli_query($con,"SELECT id,subcategory FROM subcategory ORDER BY subcategory");
        while($sql && $row=mysqli_fetch_array($sql)): ?>
        <a href="sub-category.php?scid=<?php echo $row['id']; ?>">
            <span class="dot"></span><?php echo htmlspecialchars($row['subcategory']); ?>
        </a>
        <?php endwhile; ?>
    </div>
</div>
</div><!-- /.ps-sidebar -->
            </div><!-- /.sidebar -->
			<div class='col-md-10'>
					<!-- ========================================== SECTION – HERO ========================================= -->

	<div id="category" class="category-carousel hidden-xs">
		<div class="item">	
			<div class="image">
				<img src="assets/images/banners/cat-banner-3.jpg" alt="" class="img-responsive">
			</div>
			<div class="container-fluid">
				<div class="caption vertical-top text-left">
					<div class="big-text">
						<br />
					</div>

			
			
				</div><!-- /.caption -->
			</div><!-- /.container-fluid -->
		</div>
</div>

				<div class="search-result-container">
					<div id="myTabContent" class="tab-content">
						<div class="tab-pane active " id="grid-container">
							<div class="category-product  inner-top-vs">
								<div class="row">									
			<?php
// Toolbar
$_sort_labels = ['relevancia'=>'Relevancia','precio_asc'=>'Precio: menor a mayor','precio_desc'=>'Precio: mayor a menor'];
?>
<style>
.sr-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:10px 0 14px;border-bottom:1px solid #eee;margin-bottom:16px;}
.sr-count{font-size:13px;color:#666;}
.sr-count strong{color:#333;}
.sr-sort select{border:1px solid #ddd;border-radius:5px;padding:6px 10px;font-size:13px;color:#555;background:#fafafa;cursor:pointer;}
.ps-pagination{display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:28px;padding:16px 0;}
.ps-pagination a,.ps-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border:1px solid #ddd;border-radius:5px;color:#555;font-size:13px;text-decoration:none;transition:all .2s;}
.ps-pagination a:hover{background:#e8233a;color:#fff;border-color:#e8233a;}
.ps-pagination span.active{background:#e8233a;color:#fff;border-color:#e8233a;font-weight:700;}
.ps-pagination span.dots{border:none;color:#aaa;}
</style>
<div class="sr-toolbar">
  <div class="sr-count">
    <?php if($_total_count > 0): ?>
      <strong><?php echo $_total_count; ?></strong> resultado<?php echo $_total_count!=1?'s':''; ?> para "<strong><?php echo htmlspecialchars($product_search); ?></strong>"
    <?php else: ?>
      Sin resultados para "<strong><?php echo htmlspecialchars($product_search); ?></strong>"
      <?php if ($_did_you_mean): ?>
      &nbsp;— ¿Quisiste decir: <a href="search-result.php?product=<?php echo urlencode($_did_you_mean); ?>" style="color:#e8233a;font-weight:600"><?php echo htmlspecialchars($_did_you_mean); ?></a>?
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php if($_total_count > 0): ?>
  <div class="sr-sort">
    <select onchange="location.href=this.value">
      <?php foreach($_sort_labels as $k=>$lbl): ?>
      <option value="<?php echo htmlspecialchars(_sr_url(['ord'=>$k,'pag'=>1])); ?>" <?php echo $k==$_sort_key?'selected':''; ?>>
        <?php echo $lbl; ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
</div>
<?php
// Usar resultados del motor S1
$_sr_rows = $_search_result['rows'];
$num = $_total_count;
// Enriquecer filas con datos completos si faltan columnas
foreach ($_sr_rows as &$_sr_r) {
    if (!isset($_sr_r['productStatus'])) {
        $full = mysqli_query($con, "SELECT * FROM products WHERE id=" . intval($_sr_r['id']));
        if ($full) $_sr_r = array_merge(mysqli_fetch_assoc($full) ?: [], $_sr_r);
    }
}
unset($_sr_r);
if($num>0)
{
foreach ($_sr_rows as $row)
{ $row = array_merge($row, $row); // compatibilidad con fetch_array (índices numéricos no usados)
?>							
		<div class="col-sm-6 col-md-4 wow fadeInUp">
			<div class="products">				
	<div class="product">		
		<div class="product-image">
			<div class="image" style="position:relative">
				<a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>"><?php product_img_tag($row, 200, 300); ?></a>
				<?php if($row['productAvailability'] !== 'In Stock' && $row['productAvailability'] !== 'On Order'): ?>
				<div class="product-sold-out-overlay"><span class="product-sold-out-badge">Agotado</span></div>
				<?php elseif($row['productAvailability'] === 'On Order'): ?>
				<div class="product-sold-out-overlay" style="background:rgba(0,0,0,.3)"><span class="product-sold-out-badge" style="background:#f39c12">Bajo pedido</span></div>
				<?php endif; ?>
			</div><!-- /.image -->
		</div><!-- /.product-image -->
			
		
		<div class="product-info text-left">
			<h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>"><?php echo htmlentities($row['productName']);?></a></h3>
			<div class="rating rateit-small"></div>
			<div class="description"></div>

			<div class="product-price">
				<?php render_price($row, $_CURRENCY); ?>
			</div><!-- /.product-price -->
			
		</div><!-- /.product-info -->
					<div class="cart clearfix animate-effect">
				<div class="action">
					<ul class="list-unstyled">
						<li class="add-cart-button btn-group">
					<?php if($row['productAvailability']=='In Stock'){?>
										<button class="btn btn-primary icon" data-toggle="dropdown" type="button">
								<i class="fa fa-shopping-cart"></i>
							</button>
							<a href="category.php?page=product&action=add&id=<?php echo $row['id']; ?>">
							<button class="btn btn-primary" type="button">Agregar al carrito</button></a>
						<?php } elseif($row['productAvailability']=='On Order'){?>
							<button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Bajo Pedido</button>
							<?php } else {?>
							<div class="action" style="color:red">Sin Stock</div>
					<?php } ?>
													
						</li>
	                   
		                <li class="lnk wishlist">
							<a class="add-to-cart" href="#" data-wl-pid="<?php echo intval($row['id']); ?>" title="Añadir a lista de deseos">
								 <i class="icon fa fa-heart"></i>
							</a>
						</li>
						<li class="lnk"><a href="#" class="btn-compare" title="Comparar" data-id="<?php echo intval($row['id']); ?>" data-name="<?php echo htmlspecialchars($row['productName'],ENT_QUOTES); ?>" data-img="admin/productimages/<?php echo intval($row['id']); ?>/<?php echo htmlspecialchars($row['productImage1']); ?>" data-price="<?php echo intval($row['productPrice']); ?>"><i class="icon fa fa-balance-scale"></i></a></li>
						<li class="lnk"><a href="#" class="btn-quickview" title="Vista rápida" data-id="<?php echo intval($row['id']); ?>"><i class="icon fa fa-eye"></i></a></li>
					</ul>
				</div><!-- /.action -->
			</div><!-- /.cart -->
			</div>
			</div>
		</div>
	  <?php } } else {?>
	
		<div class="col-sm-12" style="text-align:center;padding:48px 20px;">
		<div style="font-size:60px;color:#ddd;margin-bottom:16px"><i class="fa fa-search"></i></div>
		<h3 style="color:#555;margin-bottom:8px">No encontramos resultados</h3>
		<p style="color:#888">Intenta con otro término o explora nuestras categorías.</p>
		<a href="index2.php" class="btn btn-primary" style="margin-top:12px">Ver todos los productos</a>
	</div>
		
<?php } ?>	
		
	
		
		
	
		
	
		
	
		
										</div><!-- /.row -->

<?php if($_total_pages > 1): ?>
<div class="ps-pagination">
  <?php if($_page>1): ?><a href="<?php echo htmlspecialchars(_sr_url(['pag'=>$_page-1])); ?>">&#8592;</a><?php endif; ?>
  <?php for($i=1;$i<=$_total_pages;$i++):
      if($i==1||$i==$_total_pages||abs($i-$_page)<=1): ?>
    <?php if($i==$_page): ?><span class="active"><?php echo $i; ?></span>
    <?php else: ?><a href="<?php echo htmlspecialchars(_sr_url(['pag'=>$i])); ?>"><?php echo $i; ?></a><?php endif; ?>
  <?php elseif(abs($i-$_page)==2): ?><span class="dots">&hellip;</span>
  <?php endif; endfor; ?>
  <?php if($_page<$_total_pages): ?><a href="<?php echo htmlspecialchars(_sr_url(['pag'=>$_page+1])); ?>">&#8594;</a><?php endif; ?>
</div>
<?php endif; ?>

							</div><!-- /.category-product -->
						
						</div><!-- /.tab-pane -->
						
				

				</div><!-- /.search-result-container -->

			</div><!-- /.col -->
		</div></div>
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
	<script src="assets/js/toast.js"></script>
	<script src="assets/js/compare.js"></script>
<script src="assets/js/quickview.js"></script>
	<script src="assets/js/wishlist.js"></script>
	<script src="assets/js/product-hover-gallery.js"></script>

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
</body>
</html>