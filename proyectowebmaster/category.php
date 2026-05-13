<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
include('includes/price-display.php');
$cid=intval($_GET['cid'] ?? 0);
if(isset($_GET['action']) && $_GET['action']=="add"){
	$id=intval($_GET['id'] ?? 0);
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

	    <title><?php echo $_seo_cat ? htmlspecialchars($_seo_cat['categoryName']).' | '.$_SITE_NAME : 'Categorías | '.$_SITE_NAME; ?></title>

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
<?php
$_bc = ($__r = mysqli_query($con, "SELECT categoryName FROM category WHERE id=$cid")) ? mysqli_fetch_assoc($__r) : null;
?>
<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <ul class="list-inline list-unstyled">
                <li><a href="index2.php">Inicio</a></li>
                <li class="active"><?php echo htmlspecialchars($_bc['categoryName'] ?? 'Categoría'); ?></li>
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
.ps-sidebar-body a { display:flex; align-items:center; gap:8px; padding:8px 16px; font-size:12.5px; color:#555; text-decoration:none; transition:background .15s, color .15s; }
.ps-sidebar-body a:hover { background:#fdf0f5; color:#c0396b; }
.ps-sidebar-body a .dot { width:6px; height:6px; border-radius:50%; background:#ddd; flex-shrink:0; transition:background .15s; }
.ps-sidebar-body a:hover .dot { background:#c0396b; }
.ps-sidebar-body .see-all { font-weight:600; color:#c0396b; border-top:1px solid #f5f5f5; margin-top:4px; padding-top:10px; }
.ps-sidebar-body .see-all .dot { background:#c0396b; }
</style>

<div class="ps-sidebar">
<?php
// Panel: Subcategorías de esta categoría
$_cat_info = mysqli_fetch_assoc(mysqli_query($con, "SELECT categoryName FROM category WHERE id=$cid"));
$_sub_res  = mysqli_query($con, "SELECT id, subcategory FROM subcategory WHERE categoryid=$cid");
$_subs     = [];
while ($_sr = mysqli_fetch_assoc($_sub_res)) $_subs[] = $_sr;

if (!empty($_subs)):
?>
<div class="ps-sidebar-group">
    <div class="ps-sidebar-toggle" onclick="this.parentElement.classList.toggle('open')">
        <span><span class="ps-icon"><i class="fa fa-list"></i></span> &nbsp;<?php echo htmlspecialchars($_cat_info['categoryName'] ?? 'Subcategorías'); ?></span>
        <span class="ps-arrow">▼</span>
    </div>
    <div class="ps-sidebar-body">
        <?php foreach($_subs as $_s): ?>
        <a href="sub-category.php?scid=<?php echo (int)$_s['id']; ?>">
            <span class="dot"></span><?php echo htmlspecialchars($_s['subcategory']); ?>
        </a>
        <?php endforeach; ?>
        <a href="category.php?cid=<?php echo $cid; ?>" class="see-all">
            <span class="dot"></span>Ver todo →
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Panel: Todas las categorías (colapsado por defecto) -->
<div class="ps-sidebar-group">
    <div class="ps-sidebar-toggle" onclick="this.parentElement.classList.toggle('open')">
        <span><span class="ps-icon"><i class="fa fa-th-large"></i></span> &nbsp;Todas las categorías</span>
        <span class="ps-arrow">▼</span>
    </div>
    <div class="ps-sidebar-body">
        <?php $sql=mysqli_query($con,"SELECT id,categoryName FROM category");
        while($sql && $row=mysqli_fetch_array($sql)): ?>
        <a href="category.php?cid=<?php echo $row['id']; ?>"<?php echo ($row['id']==$cid)?' style="color:#c0396b;font-weight:600"':''; ?>>
            <span class="dot"></span><?php echo htmlspecialchars($row['categoryName']); ?>
        </a>
        <?php endwhile; ?>
    </div>
</div>

<!-- Panel: Filtro de precio -->
<div class="ps-sidebar-group open">
    <div class="ps-sidebar-toggle" onclick="this.parentElement.classList.toggle('open')">
        <span><span class="ps-icon"><i class="fa fa-tag"></i></span> &nbsp;Precio</span>
        <span class="ps-arrow">▼</span>
    </div>
    <div class="ps-sidebar-body" style="padding:12px 16px">
        <style>
        .ps-price-range{display:flex;justify-content:space-between;font-size:12px;color:#888;margin-bottom:8px;}
        .ps-price-inputs{display:flex;gap:6px;align-items:center;margin-top:10px;}
        .ps-price-inputs input{width:70px;border:1px solid #ddd;border-radius:4px;padding:4px 6px;font-size:12px;text-align:center;}
        .ps-price-inputs span{color:#aaa;font-size:12px;}
        .ps-price-apply{display:block;width:100%;margin-top:8px;font-size:12px;padding:6px;border-radius:5px;}
        .slider.slider-horizontal{width:100%!important;}
        .slider-track{background:#eee!important;}
        .slider-selection{background:#e8233a!important;}
        .slider-handle{background:#e8233a!important;border:2px solid #fff!important;box-shadow:0 1px 4px rgba(0,0,0,.2)!important;}
        </style>
        <?php
        $_pr = mysqli_query($con,"SELECT MIN(productPrice) as mn, MAX(productPrice) as mx FROM products p WHERE p.category=$cid");
        $_pr_row = mysqli_fetch_assoc($_pr);
        $_mn = max(0, intval($_pr_row['mn'] ?? 0));
        $_mx = max($_mn+1000, intval($_pr_row['mx'] ?? 100000));
        $_cur_min = ($_pmin > 0) ? $_pmin : $_mn;
        $_cur_max = ($_pmax > 0 && $_pmax >= $_pmin) ? $_pmax : $_mx;
        ?>
        <div class="ps-price-range">
            <span id="cat-price-label-min"><?php echo '$'.number_format($_cur_min,0,',','.'); ?></span>
            <span id="cat-price-label-max"><?php echo '$'.number_format($_cur_max,0,',','.'); ?></span>
        </div>
        <input id="cat-price-slider" type="text" style="display:none"
            data-slider-min="<?php echo $_mn; ?>"
            data-slider-max="<?php echo $_mx; ?>"
            data-slider-value="[<?php echo $_cur_min; ?>,<?php echo $_cur_max; ?>]"
            data-slider-step="500">
        <div class="ps-price-inputs">
            <input type="number" id="cat-pmin-input" value="<?php echo $_cur_min; ?>" min="<?php echo $_mn; ?>" max="<?php echo $_mx; ?>">
            <span>—</span>
            <input type="number" id="cat-pmax-input" value="<?php echo $_cur_max; ?>" min="<?php echo $_mn; ?>" max="<?php echo $_mx; ?>">
        </div>
        <a id="cat-price-apply" href="<?php echo htmlspecialchars(_cat_url(['pmin'=>$_cur_min,'pmax'=>$_cur_max,'pag'=>1])); ?>" class="btn btn-primary btn-sm ps-price-apply">Aplicar filtro</a>
        <?php if($_pmin > 0 || $_pmax > 0): ?>
        <a href="<?php echo htmlspecialchars(_cat_url(['pmin'=>'','pmax'=>'','pag'=>1])); ?>" style="display:block;text-align:center;font-size:11px;color:#c0396b;margin-top:5px">Limpiar filtro &times;</a>
        <?php endif; ?>
    </div>
</div>
<script>
$(document).ready(function(){
    if(!$('#cat-price-slider').length) return;
    try {
        $('#cat-price-slider').slider({
            tooltip: 'hide'
        }).on('slide', function(e){
            var v = e.value;
            $('#cat-pmin-input').val(v[0]);
            $('#cat-pmax-input').val(v[1]);
            $('#cat-price-label-min').text('$'+v[0].toLocaleString('es-CO'));
            $('#cat-price-label-max').text('$'+v[1].toLocaleString('es-CO'));
            updatePriceApplyLink(v[0], v[1]);
        });
    } catch(e){}
    $('#cat-pmin-input, #cat-pmax-input').on('change', function(){
        updatePriceApplyLink($('#cat-pmin-input').val(), $('#cat-pmax-input').val());
    });
    function updatePriceApplyLink(mn, mx){
        var base = '<?php echo htmlspecialchars(_cat_url(['pmin'=>'__MN__','pmax'=>'__MX__','pag'=>1])); ?>';
        base = base.replace('__MN__', mn).replace('__MX__', mx);
        $('#cat-price-apply').attr('href', base);
    }
});
</script>


</div><!-- /.ps-sidebar -->
            </div><!-- /.sidebar -->
			<div class='col-md-10'>
					<!-- ========================================== SECTION – HERO ========================================= -->

	<?php
	$cat_row = ($__r = mysqli_query($con, "SELECT categoryName, categoryImage FROM category WHERE id=$cid")) ? mysqli_fetch_assoc($__r) : null;
	$cat_banner = (!empty($cat_row['categoryImage']))
		? 'admin/categoryimages/' . htmlspecialchars($cat_row['categoryImage'])
		: 'assets/images/banners/cat-banner-1.jpg';
	?>
	<style>
	.ps-cat-banner { position:relative; width:100%; overflow:hidden; border-radius:0 0 12px 12px; }
	.ps-cat-banner img { width:100%; max-height:320px; object-fit:cover; display:block; }
	.ps-cat-banner::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.10) 60%, transparent 100%); }
	.ps-cat-label {
		position:absolute; top:50%; left:6%; transform:translateY(-50%);
		z-index:2; color:#fff;
		opacity:0; transform:translateY(-50%) translateX(-28px);
		animation: ps-slide-in .65s cubic-bezier(.22,1,.36,1) .15s forwards;
	}
	.ps-cat-label .ps-cat-tag {
		display:block; font-size:.72rem; font-weight:700; letter-spacing:3px;
		text-transform:uppercase; color:rgba(255,255,255,.65); margin-bottom:6px;
	}
	.ps-cat-label h2 {
		font-family:'Poppins','Montserrat',sans-serif;
		font-size:clamp(1.6rem, 4vw, 2.8rem);
		font-weight:700; margin:0; line-height:1.1;
		text-shadow:0 2px 16px rgba(0,0,0,0.3);
	}
	.ps-cat-label .ps-cat-line {
		display:block; width:0; height:3px;
		background:#c0396b; border-radius:2px; margin-top:10px;
		animation: ps-line-grow .5s ease .75s forwards;
	}
	@keyframes ps-slide-in {
		to { opacity:1; transform:translateY(-50%) translateX(0); }
	}
	@keyframes ps-line-grow {
		to { width:60px; }
	}
	</style>
	<div class="ps-cat-banner hidden-xs">
		<img src="<?php echo $cat_banner; ?>" alt="<?php echo htmlspecialchars($cat_row['categoryName'] ?? ''); ?>">
		<div class="ps-cat-label">
			<span class="ps-cat-tag">Categoría</span>
			<h2><?php echo htmlspecialchars($cat_row['categoryName'] ?? ''); ?></h2>
			<span class="ps-cat-line"></span>
		</div>
	</div>

				<?php
// ── Ordenamiento
$_sort_opts = [
    'relevancia' => 'p.id DESC',
    'precio_asc' => 'p.productPrice ASC',
    'precio_desc'=> 'p.productPrice DESC',
    'vendidos'   => 'total_sold DESC',
];
$_sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $_sort_opts) ? $_GET['sort'] : 'relevancia';
$_order_sql = $_sort_opts[$_sort];

// ── Paginación
$_per_page = 12;
$_page = max(1, intval($_GET['pag'] ?? 1));
$_offset = ($_page - 1) * $_per_page;

if ($_sort === 'vendidos') {
    $_base_where = "LEFT JOIN orders o ON o.productId=p.id AND o.paymentMethod IS NOT NULL WHERE p.category=$cid";
    $_count_q = mysqli_query($con, "SELECT COUNT(*) as total FROM products p WHERE p.category=$cid$_price_cond");
    $_total = ($r = mysqli_fetch_assoc($_count_q)) ? (int)$r['total'] : 0;
    $_ret = mysqli_query($con, "SELECT p.*, COALESCE(SUM(o.quantity),0) as total_sold FROM products p $_base_where GROUP BY p.id ORDER BY $_order_sql LIMIT $_per_page OFFSET $_offset");
} else {
    $_count_q = mysqli_query($con, "SELECT COUNT(*) as total FROM products p WHERE p.category=$cid$_price_cond");
    $_total = ($r = mysqli_fetch_assoc($_count_q)) ? (int)$r['total'] : 0;
    $_ret = mysqli_query($con, "SELECT * FROM products p WHERE p.category=$cid$_price_cond ORDER BY $_order_sql LIMIT $_per_page OFFSET $_offset");
}
$_total_pages = max(1, ceil($_total / $_per_page));

function _cat_url($params) {
    $base = array_merge($_GET, $params);
    return 'category.php?' . http_build_query($base);
}
?>
<style>
.ps-toolbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:14px 0 10px; border-bottom:1px solid #f0f0f0; margin-bottom:16px; }
.ps-toolbar .ps-count { font-size:13px; color:#888; }
.ps-sort-select { font-size:13px; padding:6px 12px; border:1px solid #ddd; border-radius:6px; background:#fff; color:#333; cursor:pointer; }
.ps-sort-select:focus { outline:none; border-color:#c0396b; }
.ps-pagination { display:flex; align-items:center; justify-content:center; gap:4px; padding:20px 0 4px; }
.ps-pagination a, .ps-pagination span {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:34px; height:34px; padding:0 8px;
    border:1px solid #e0e0e0; border-radius:6px;
    font-size:13px; color:#555; text-decoration:none; transition:all .18s;
}
.ps-pagination a:hover { background:#fdf0f5; border-color:#c0396b; color:#c0396b; }
.ps-pagination span.active { background:#c0396b; border-color:#c0396b; color:#fff; font-weight:600; }
.ps-pagination span.dots { border:none; color:#aaa; }
</style>

<style>
/* ── L4: Vista lista ── */
#grid-container.list-view .col-sm-6 { width:100%; }
#grid-container.list-view .products .product { display:flex; align-items:flex-start; gap:16px; padding:10px 0; border-bottom:1px solid #f0f0f0; }
#grid-container.list-view .product-image { flex-shrink:0; width:110px; }
#grid-container.list-view .product-image img { width:110px; height:140px; object-fit:contain; }
#grid-container.list-view .product-info { flex:1; text-align:left !important; }
#grid-container.list-view .product-info .name { font-size:14px; margin-bottom:4px; }
#grid-container.list-view .rating { display:none; }
#grid-container.list-view .cart { margin-top:8px; }
#grid-container.list-view .product-sold-out-overlay { display:none; }
#grid-container.list-view .ps-stock-badge { display:none; }
.ps-view-btn { background:none; border:1px solid #ddd; border-radius:4px; padding:4px 8px; cursor:pointer; margin-left:4px; color:#777; }
.ps-view-btn.active, .ps-view-btn:hover { background:#e8233a; border-color:#e8233a; color:#fff; }
</style>

<div class="search-result-container">
    <div class="ps-toolbar">
        <span class="ps-count"><?php echo $_total; ?> producto<?php echo $_total!=1?'s':''; ?></span>
        <div style="display:flex;align-items:center;gap:6px;margin-left:auto">
        <button class="ps-view-btn active" id="btn-view-grid" title="Vista cuadrícula" onclick="psSetView('grid')">
            <i class="fa fa-th"></i>
        </button>
        <button class="ps-view-btn" id="btn-view-list" title="Vista lista" onclick="psSetView('list')">
            <i class="fa fa-list"></i>
        </button>
        </div>
        <select class="ps-sort-select" onchange="location.href=this.value">
            <?php foreach(['relevancia'=>'Relevancia','precio_asc'=>'Precio: menor a mayor','precio_desc'=>'Precio: mayor a menor','vendidos'=>'M&aacute;s vendidos'] as $_k=>$_v): ?>
            <option value="<?php echo htmlspecialchars(_cat_url(['sort'=>$_k,'pag'=>1])); ?>"<?php echo $_sort===$_k?' selected':''; ?>><?php echo $_v; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane active" id="grid-container">
            <div class="category-product inner-top-vs">
                <div class="row">
<?php if ($_total > 0): while ($row = mysqli_fetch_array($_ret)): ?>
    <div class="col-sm-6 col-md-3 wow fadeInUp">
        <div class="products"><div class="product">
            <div class="product-image"><div class="image" style="position:relative">
                <?php
    // D4: badge stock basado en ID del producto (simulación determinista)
    $_stock_mod = $row['id'] % 10;
    if ($row['productAvailability'] === 'On Order') {
        echo '<div class="ps-stock-badge ps-stock-last">¡Últimas unidades!</div>';
    } elseif ($row['productAvailability'] === 'In Stock' && $_stock_mod <= 2) {
        echo '<div class="ps-stock-badge ps-stock-low">Pocas unidades</div>';
    }
?>
                <a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>"><?php product_img_tag($row, 200, 300); ?></a>
                <?php if($row['productAvailability'] !== 'In Stock' && $row['productAvailability'] !== 'On Order'): ?>
                <div class="product-sold-out-overlay"><span class="product-sold-out-badge">Agotado</span></div>
                <?php elseif($row['productAvailability'] === 'On Order'): ?>
                <div class="product-sold-out-overlay" style="background:rgba(0,0,0,.3)"><span class="product-sold-out-badge" style="background:#f39c12">Bajo pedido</span></div>
                <?php endif; ?>
            </div></div>
            <div class="product-info text-left">
                <h3 class="name"><a href="product-details.php?pid=<?php echo htmlentities($row['id']);?>"><?php echo htmlentities($row['productName']);?></a></h3>
                <div class="rating rateit-small"></div>
                <div class="product-price"><?php render_price($row, $_CURRENCY); ?></div>
            </div>
            <div class="cart clearfix animate-effect"><div class="action"><ul class="list-unstyled">
                <li class="add-cart-button btn-group">
                    <?php if($row['productAvailability']==='In Stock'): ?>
                    <button class="btn btn-primary btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Agregar</button>
                    <?php elseif($row['productAvailability']==='On Order'): ?>
                    <button class="btn btn-warning btn-add-to-cart" data-id="<?php echo $row['id']; ?>" type="button"><i class="fa fa-shopping-cart"></i> Bajo Pedido</button>
                    <?php else: ?><div style="color:red">Sin Stock</div><?php endif; ?>
                </li>
                <li class="lnk wishlist">
                    <a class="add-to-cart" href="#" data-wl-pid="<?php echo intval($row['id']); ?>" title="A&ntilde;adir a lista de deseos"><i class="icon fa fa-heart"></i></a>
                </li>
                <li class="lnk">
                    <a href="#" class="btn-compare" title="Comparar"
                        data-id="<?php echo intval($row['id']); ?>"
                        data-name="<?php echo htmlspecialchars($row['productName'],ENT_QUOTES); ?>"
                        data-img="admin/productimages/<?php echo intval($row['id']); ?>/<?php echo htmlspecialchars($row['productImage1']); ?>"
                        data-price="<?php echo intval($row['productPrice']); ?>">
                        <i class="icon fa fa-balance-scale"></i>
                    </a>
                </li>
                <li class="lnk">
                    <a href="#" class="btn-quickview" title="Vista rápida" data-id="<?php echo intval($row['id']); ?>">
                        <i class="icon fa fa-eye"></i>
                    </a>
                </li>
            </ul></div></div>
        </div></div>
    </div>
<?php endwhile; else: ?>
    <div class="col-sm-12"><h3>No se encontraron productos</h3></div>
<?php endif; ?>
                </div><!-- /.row -->

                <?php if($_total_pages > 1): ?>
                <div class="ps-pagination">
                    <?php if($_page>1): ?><a href="<?php echo htmlspecialchars(_cat_url(['pag'=>$_page-1])); ?>">&#8592;</a><?php endif; ?>
                    <?php for($i=1;$i<=$_total_pages;$i++):
                        if($i==1||$i==$_total_pages||abs($i-$_page)<=1): ?>
                        <?php if($i==$_page): ?><span class="active"><?php echo $i; ?></span>
                        <?php else: ?><a href="<?php echo htmlspecialchars(_cat_url(['pag'=>$i])); ?>"><?php echo $i; ?></a><?php endif; ?>
                    <?php elseif(abs($i-$_page)==2): ?><span class="dots">&hellip;</span>
                    <?php endif; endfor; ?>
                    <?php if($_page<$_total_pages): ?><a href="<?php echo htmlspecialchars(_cat_url(['pag'=>$_page+1])); ?>">&#8594;</a><?php endif; ?>
                </div>
                <?php endif; ?>

            </div><!-- /.category-product -->
        </div><!-- /.tab-pane -->
    </div><!-- /.tab-content -->
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
<script>
// L4 — Toggle vista grid / lista
function psSetView(mode) {
    var gc = document.getElementById('grid-container');
    var bg = document.getElementById('btn-view-grid');
    var bl = document.getElementById('btn-view-list');
    if (!gc) return;
    if (mode === 'list') {
        gc.classList.add('list-view');
        bg.classList.remove('active'); bl.classList.add('active');
    } else {
        gc.classList.remove('list-view');
        bl.classList.remove('active'); bg.classList.add('active');
    }
    localStorage.setItem('ps_cat_view', mode);
}
// Restaurar preferencia guardada
(function(){ var v = localStorage.getItem('ps_cat_view'); if (v === 'list') psSetView('list'); })();
</script>
</body>
</html>