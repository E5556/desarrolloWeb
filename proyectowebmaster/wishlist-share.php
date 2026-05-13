<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/price-display.php');

$_token = trim($_GET['token'] ?? '');

// Find user whose token matches
$_owner = null;
if (strlen($_token) === 32) {
    $res = mysqli_query($con, "SELECT id, fullName FROM users");
    while ($res && $u = mysqli_fetch_assoc($res)) {
        if (md5($u['id'] . 'ps_wish_salt_2024') === $_token) {
            $_owner = $u;
            break;
        }
    }
}

$_items = [];
if ($_owner) {
    $q = mysqli_query($con, "SELECT p.id, p.productName, p.productImage1, p.productPrice, p.productAvailability
        FROM wishlist w JOIN products p ON p.id = w.productId
        WHERE w.userId = '" . intval($_owner['id']) . "'");
    while ($q && $row = mysqli_fetch_assoc($q)) $_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo $_owner ? htmlspecialchars($_owner['fullName'])."'s Wishlist" : 'Lista no encontrada'; ?> | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
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
                <li class="active">Lista de deseos compartida</li>
            </ul>
        </div>
    </div>
</div>

<div class="body-content outer-top-xs">
    <div class="container">
        <div class="row inner-bottom-sm">
            <div class="col-md-12">

<?php if (!$_owner): ?>
<div style="text-align:center;padding:60px 20px;color:#aaa">
    <i class="fa fa-heart-o" style="font-size:56px;display:block;margin-bottom:16px"></i>
    <h3 style="color:#555">Lista no encontrada</h3>
    <p>El enlace es inválido o ha expirado.</p>
    <a href="index2.php" class="btn btn-primary" style="margin-top:12px">Ver catálogo</a>
</div>
<?php elseif (empty($_items)): ?>
<div style="text-align:center;padding:60px 20px;color:#aaa">
    <i class="fa fa-heart-o" style="font-size:56px;display:block;margin-bottom:16px"></i>
    <h3 style="color:#555">La lista está vacía</h3>
    <p><?php echo htmlspecialchars($_owner['fullName']); ?> aún no ha agregado productos a su lista de deseos.</p>
    <a href="index2.php" class="btn btn-primary" style="margin-top:12px">Ver catálogo</a>
</div>
<?php else: ?>
<div style="margin-bottom:24px">
    <h2 style="margin-bottom:4px"><i class="fa fa-heart" style="color:#e8233a;margin-right:8px"></i>Lista de deseos de <?php echo htmlspecialchars($_owner['fullName']); ?></h2>
    <p style="color:#888;font-size:13px"><?php echo count($_items); ?> producto<?php echo count($_items) !== 1 ? 's' : ''; ?> en esta lista</p>
</div>

<div class="table-responsive">
<table class="table table-bordered" style="background:#fff">
    <thead>
        <tr style="background:#f9f9f9">
            <th>Producto</th>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Disponibilidad</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($_items as $p): ?>
    <tr>
        <td style="width:80px">
            <img src="admin/productimages/<?php echo intval($p['id']); ?>/<?php echo htmlspecialchars($p['productImage1']); ?>"
                 onerror="this.src='assets/images/blank.gif'" width="60" style="max-height:80px;object-fit:contain">
        </td>
        <td><a href="product-details.php?pid=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['productName']); ?></a></td>
        <td style="color:#e8233a;font-weight:700">$<?php echo number_format($p['productPrice'],0,',','.'); ?></td>
        <td>
            <?php if ($p['productAvailability'] === 'In Stock'): ?>
                <span style="color:#27ae60"><i class="fa fa-check-circle"></i> En stock</span>
            <?php elseif ($p['productAvailability'] === 'On Order'): ?>
                <span style="color:#f39c12"><i class="fa fa-clock-o"></i> Bajo pedido</span>
            <?php else: ?>
                <span style="color:#e8233a"><i class="fa fa-times-circle"></i> Agotado</span>
            <?php endif; ?>
        </td>
        <td>
            <a href="product-details.php?pid=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">Ver producto</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div style="margin-top:16px">
    <a href="index2.php" class="btn btn-default"><i class="fa fa-arrow-left" style="margin-right:6px"></i>Seguir explorando</a>
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
<script src="assets/js/cookies.js"></script>
</body>
</html>
