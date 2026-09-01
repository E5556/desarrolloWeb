<?php
session_start();
error_reporting(0);
include('includes/config.php');

$bid = intval($_GET['id'] ?? 0);
if ($bid <= 0) { header('location:index2.php'); exit(); }

$bq = mysqli_query($con, "SELECT * FROM bundles WHERE id=$bid AND is_active=1 LIMIT 1");
if (!$bq || mysqli_num_rows($bq) === 0) {
    echo '<!DOCTYPE html><html><body style="text-align:center;padding:60px;font-family:sans-serif"><h2>Bundle no disponible.</h2><a href="index2.php">Ir a la tienda</a></body></html>';
    exit();
}
$bundle = mysqli_fetch_assoc($bq);

$items_q = mysqli_query($con, "SELECT bi.quantity as bqty, p.id, p.productName, p.productPrice, p.productImage1, p.productAvailability
    FROM bundle_items bi JOIN products p ON p.id=bi.product_id WHERE bi.bundle_id=$bid");
$items = [];
$regular_total = 0;
while ($items_q && $row = mysqli_fetch_assoc($items_q)) {
    $items[] = $row;
    $regular_total += $row['productPrice'] * $row['bqty'];
}
$savings = $regular_total - $bundle['bundle_price'];
$pct     = $regular_total > 0 ? round($savings / $regular_total * 100) : 0;

// Add bundle to cart
if (isset($_POST['add_bundle'])) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    foreach ($items as $it) {
        $pid = intval($it['id']);
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] += intval($it['bqty']);
        } else {
            $_SESSION['cart'][$pid] = ['quantity' => intval($it['bqty']), 'price' => floatval($it['productPrice'])];
        }
    }
    // Apply bundle price discount as a virtual coupon in session
    $_SESSION['bundle_discount'] = ['name' => $bundle['name'], 'amount' => max(0, $savings)];
    if (!empty($_SESSION['login'])) {
        include_once('includes/cart.php'); ps_cart_save($con, intval($_SESSION['id']));
    }
    header('location:my-cart.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($bundle['name']); ?> | Pack Oferta</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/cosmetics.css">
<style>
.bundle-wrap { max-width: 780px; margin: 36px auto 60px; }
.bundle-hero {
    background: linear-gradient(135deg, #e8233a, #c0392b);
    color: #fff; border-radius: 14px 14px 0 0; padding: 28px 32px;
}
.bundle-hero h1 { margin: 0 0 6px; font-size: 1.6em; font-weight: 800; }
.bundle-hero p  { margin: 0; opacity: .85; font-size: 13px; }
.bundle-body { background: #fff; border-radius: 0 0 14px 14px; box-shadow: 0 4px 24px rgba(0,0,0,.09); padding: 28px 32px; }
.b-item { display:flex; align-items:center; gap:14px; padding:12px; background:#f8f9fa; border-radius:8px; margin-bottom:10px; }
.b-item img { width:58px; height:70px; object-fit:cover; border-radius:6px; background:#fff; flex-shrink:0; }
.b-item-info { flex:1; }
.b-item-info strong { font-size:13px; }
.b-item-info small  { color:#888; font-size:12px; }
.b-item-subtotal { font-weight:700; font-size:14px; white-space:nowrap; }
.b-totals { border-top:2px solid #f0f0f0; margin-top:20px; padding-top:18px; }
.b-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px; }
.badge-saving { background:#f39c12; color:#fff; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:700; }
.btn-add-bundle {
    display:block; width:100%; margin-top:20px;
    background:linear-gradient(135deg,#e8233a,#c0392b);
    color:#fff; border:none; border-radius:9px;
    padding:14px; font-size:15px; font-weight:700; cursor:pointer;
    transition:opacity .2s;
}
.btn-add-bundle:hover { opacity:.88; }
</style>
</head>
<body>
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>

<div class="container">
<div class="bundle-wrap">

    <!-- breadcrumb -->
    <p style="font-size:12px;color:#aaa;margin-bottom:14px">
        <a href="index2.php" style="color:#aaa">Inicio</a> &rsaquo;
        <a href="bundles-list.php" style="color:#aaa">Packs Oferta</a> &rsaquo;
        <?php echo htmlspecialchars($bundle['name']); ?>
    </p>

    <div class="bundle-hero">
        <h1><i class="fa fa-gift"></i> <?php echo htmlspecialchars($bundle['name']); ?></h1>
        <?php if ($bundle['description']): ?>
        <p><?php echo htmlspecialchars($bundle['description']); ?></p>
        <?php endif; ?>
    </div>

    <div class="bundle-body">
        <h5 style="margin:0 0 16px;font-size:14px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px">
            <i class="fa fa-cubes" style="color:#e8233a;margin-right:6px"></i> Productos incluidos
        </h5>

        <?php foreach ($items as $it): ?>
        <div class="b-item">
            <img src="admin/productimages/<?php echo intval($it['id']); ?>/<?php echo htmlspecialchars($it['productImage1']); ?>"
                 onerror="this.style.display='none'" alt="">
            <div class="b-item-info">
                <strong><?php echo htmlspecialchars($it['productName']); ?></strong><br>
                <small>Cant: <?php echo intval($it['bqty']); ?> &bull; Precio unitario: $<?php echo number_format($it['productPrice'],0,'.',','); ?></small>
            </div>
            <div class="b-item-subtotal">$<?php echo number_format($it['productPrice']*$it['bqty'],0,'.',','); ?></div>
        </div>
        <?php endforeach; ?>

        <div class="b-totals">
            <div class="b-row">
                <span style="color:#999">Precio regular</span>
                <span style="text-decoration:line-through;color:#bbb">$<?php echo number_format($regular_total,0,'.',','); ?></span>
            </div>
            <?php if ($savings > 0): ?>
            <div class="b-row">
                <span>Ahorro del pack <span class="badge-saving">-<?php echo $pct; ?>%</span></span>
                <span style="color:#27ae60;font-weight:700">-$<?php echo number_format($savings,0,'.',','); ?></span>
            </div>
            <?php endif; ?>
            <div class="b-row" style="font-size:1.2em;font-weight:800;margin-top:8px">
                <span>Total del pack</span>
                <span style="color:#e8233a">$<?php echo number_format($bundle['bundle_price'],0,'.',','); ?></span>
            </div>
        </div>

        <form method="post">
            <button type="submit" name="add_bundle" class="btn-add-bundle">
                <i class="fa fa-shopping-cart"></i> Agregar pack al carrito
            </button>
        </form>

        <div style="text-align:center;margin-top:14px">
            <a href="bundles-list.php" style="color:#aaa;font-size:12px"><i class="fa fa-arrow-left"></i> Ver todos los packs</a>
        </div>
    </div>

</div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
