<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/price-display.php');

// IDs come from JS via GET (compare.php?ids=1,2,3)
$_raw_ids = $_GET['ids'] ?? '';
$_ids = array_filter(array_map('intval', explode(',', $_raw_ids)));
$_ids = array_slice(array_unique($_ids), 0, 3);

$_products = [];
if (!empty($_ids)) {
    $id_list = implode(',', $_ids);
    $q = mysqli_query($con, "SELECT p.*, c.categoryName, s.subcategory AS subcategoryName FROM products p LEFT JOIN category c ON c.id=p.category LEFT JOIN subcategory s ON s.id=p.subCategory WHERE p.id IN ($id_list)");
    while ($q && $row = mysqli_fetch_assoc($q)) {
        $_products[$row['id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Comparar productos | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
    .compare-wrap { overflow-x: auto; }
    .compare-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .compare-table td, .compare-table th { border: 1px solid #eee; padding: 12px 16px; vertical-align: top; font-size: 13px; }
    .compare-table th { background: #f9f9f9; font-weight: 700; color: #555; white-space: nowrap; width: 140px; }
    .compare-table .ct-header { text-align: center; background: #fdf0f5; }
    .compare-table .ct-header img { max-width: 120px; max-height: 160px; object-fit: contain; margin-bottom: 8px; display: block; margin-inline: auto; }
    .compare-table .ct-header h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px; }
    .compare-table .ct-price { color: #e8233a; font-weight: 700; font-size: 15px; }
    .compare-table .ct-avail-ok { color: #27ae60; }
    .compare-table .ct-avail-no { color: #e8233a; }
    .compare-table .ct-avail-ord { color: #f39c12; }
    .compare-empty { text-align: center; padding: 60px 20px; color: #aaa; }
    .compare-empty i { font-size: 56px; display: block; margin-bottom: 16px; }
    </style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>
<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <ul class="list-inline list-unstyled">
                <li><a href="index2.php">Inicio</a></li>
                <li class="active">Comparar productos</li>
            </ul>
        </div>
    </div>
</div>
<div class="body-content outer-top-xs">
    <div class="container">
        <div class="row inner-bottom-sm">
            <div class="col-md-12">
                <h2 style="margin-bottom:6px">Comparar productos</h2>
                <p style="color:#888;font-size:13px;margin-bottom:20px">Compara hasta 3 productos lado a lado para tomar la mejor decisión.</p>

                <?php if (empty($_products)): ?>
                <div class="compare-empty">
                    <i class="fa fa-balance-scale"></i>
                    <h3 style="color:#555">No hay productos para comparar</h3>
                    <p>Agrega productos desde el catálogo usando el botón "Comparar".</p>
                    <a href="index2.php" class="btn btn-primary" style="margin-top:12px">Ver catálogo</a>
                </div>
                <?php else: ?>

                <div class="compare-wrap">
                    <table class="compare-table">
                        <!-- Encabezado con imágenes y nombres -->
                        <tr>
                            <th style="background:#fff;border:none"></th>
                            <?php foreach ($_products as $p): ?>
                            <td class="ct-header">
                                <button onclick="psCompareRemove(<?php echo $p['id']; ?>)" class="btn btn-default btn-xs" style="float:right;margin-bottom:4px" title="Quitar"><i class="fa fa-times"></i></button>
                                <?php
                                $img_src = 'admin/productimages/'.intval($p['id']).'/'.htmlspecialchars($p['productImage1']);
                                ?>
                                <img src="<?php echo $img_src; ?>" onerror="this.src='assets/images/blank.gif'" alt="<?php echo htmlspecialchars($p['productName']); ?>">
                                <h4><a href="product-details.php?pid=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['productName']); ?></a></h4>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Precio -->
                        <tr>
                            <th>Precio</th>
                            <?php foreach ($_products as $p): ?>
                            <td class="ct-price"><?php echo '$'.number_format($p['productPrice'],0,',','.'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Disponibilidad -->
                        <tr>
                            <th>Disponibilidad</th>
                            <?php foreach ($_products as $p): ?>
                            <td>
                                <?php if ($p['productAvailability'] === 'In Stock'): ?>
                                <span class="ct-avail-ok"><i class="fa fa-check-circle"></i> En stock</span>
                                <?php elseif ($p['productAvailability'] === 'On Order'): ?>
                                <span class="ct-avail-ord"><i class="fa fa-clock-o"></i> Bajo pedido</span>
                                <?php else: ?>
                                <span class="ct-avail-no"><i class="fa fa-times-circle"></i> Agotado</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Categoría -->
                        <tr>
                            <th>Categoría</th>
                            <?php foreach ($_products as $p): ?>
                            <td><?php echo htmlspecialchars($p['categoryName'] ?? '—'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Subcategoría -->
                        <tr>
                            <th>Subcategoría</th>
                            <?php foreach ($_products as $p): ?>
                            <td><?php echo htmlspecialchars($p['subcategoryName'] ?? '—'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Marca -->
                        <tr>
                            <th>Marca</th>
                            <?php foreach ($_products as $p): ?>
                            <td><?php echo htmlspecialchars($p['productCompany'] ?? '—'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Envío -->
                        <tr>
                            <th>Costo de envío</th>
                            <?php foreach ($_products as $p): ?>
                            <td><?php echo $p['shippingCharge'] > 0 ? '$'.number_format($p['shippingCharge'],0,',','.') : '<span style="color:#27ae60">Gratis</span>'; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Descripción -->
                        <tr>
                            <th>Descripción</th>
                            <?php foreach ($_products as $p): ?>
                            <td style="font-size:12px;color:#777"><?php echo htmlspecialchars(substr(strip_tags($p['productDescription']),0,120)).'...'; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Agregar al carrito -->
                        <tr>
                            <th>Acción</th>
                            <?php foreach ($_products as $p): ?>
                            <td>
                                <a href="product-details.php?pid=<?php echo $p['id']; ?>" class="btn btn-default btn-sm" style="margin-bottom:6px;display:block">Ver producto</a>
                                <?php if ($p['productAvailability'] === 'In Stock' || $p['productAvailability'] === 'On Order'): ?>
                                <button class="btn btn-primary btn-sm btn-add-to-cart" data-id="<?php echo $p['id']; ?>" style="display:block;width:100%">
                                    <i class="fa fa-shopping-cart"></i> Agregar
                                </button>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </table>
                </div>

                <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
                    <button onclick="psCompareClear()" class="btn btn-default"><i class="fa fa-trash" style="margin-right:6px"></i>Limpiar comparación</button>
                    <a href="index2.php" class="btn btn-primary"><i class="fa fa-arrow-left" style="margin-right:6px"></i>Seguir explorando</a>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include('includes/footer.php');?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/compare.js"></script>
<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/cookies.js"></script>
<script>
// Load products from localStorage into the page URL if no ids in GET
$(document).ready(function(){
    var urlIds = '<?php echo implode(',', array_keys($_products)); ?>';
    if (!urlIds) {
        var stored = psCompareGet();
        if (stored.length > 0) {
            var ids = stored.map(function(p){ return p.id; }).join(',');
            location.href = 'compare.php?ids=' + ids;
        }
    }
});
</script>
</body>
</html>
