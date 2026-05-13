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
<title><?php echo htmlspecialchars($bundle['name']); ?> | Bundle</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<style>
body{background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;padding:40px 0}
.bundle-card{max-width:800px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden}
.bundle-header{background:linear-gradient(135deg,#e8233a,#c0392b);color:#fff;padding:28px 32px}
.bundle-body{padding:28px 32px}
.item-card{display:flex;align-items:center;gap:14px;padding:12px;background:#f8f9fa;border-radius:8px;margin-bottom:10px}
.item-card img{width:60px;height:75px;object-fit:contain;background:#fff;border-radius:6px}
.savings-badge{background:#f39c12;color:#fff;border-radius:20px;padding:4px 14px;font-size:13px;font-weight:600}
</style>
</head>
<body>
<div class="bundle-card">
<div class="bundle-header">
<h2><i class="fa fa-archive"></i> <?php echo htmlspecialchars($bundle['name']); ?></h2>
<?php if ($bundle['description']): ?><p style="margin:6px 0 0;opacity:.85"><?php echo htmlspecialchars($bundle['description']); ?></p><?php endif; ?>
</div>
<div class="bundle-body">

<h5>Contenido del pack</h5>
<?php foreach ($items as $it): ?>
<div class="item-card">
<img src="admin/productimages/<?php echo intval($it['id']); ?>/<?php echo htmlspecialchars($it['productImage1']); ?>" alt="">
<div style="flex:1">
<strong><?php echo htmlspecialchars($it['productName']); ?></strong><br>
<span class="text-muted" style="font-size:13px">Cantidad: <?php echo intval($it['bqty']); ?> &bull; Precio unitario: $<?php echo number_format($it['productPrice'],0,'.',','); ?></span>
</div>
<div style="text-align:right">
<strong>$<?php echo number_format($it['productPrice'] * $it['bqty'],0,'.',','); ?></strong>
</div>
</div>
<?php endforeach; ?>

<div style="border-top:2px solid #eee;margin-top:16px;padding-top:16px">
<div style="display:flex;justify-content:space-between;margin-bottom:6px">
<span class="text-muted">Precio regular</span>
<span style="text-decoration:line-through;color:#999">$<?php echo number_format($regular_total,0,'.',','); ?></span>
</div>
<?php if ($savings > 0): ?>
<div style="display:flex;justify-content:space-between;margin-bottom:12px">
<span>Ahorro del pack <span class="savings-badge">-<?php echo $pct; ?>%</span></span>
<span style="color:#27ae60;font-weight:600">-$<?php echo number_format($savings,0,'.',','); ?></span>
</div>
<?php endif; ?>
<div style="display:flex;justify-content:space-between;align-items:center">
<span style="font-size:1.3em;font-weight:700">Total del pack</span>
<span style="font-size:1.5em;font-weight:700;color:#e8233a">$<?php echo number_format($bundle['bundle_price'],0,'.',','); ?></span>
</div>
</div>

<form method="post" style="text-align:center;margin-top:20px">
<button type="submit" name="add_bundle" class="btn btn-danger btn-lg">
<i class="fa fa-shopping-cart"></i> Agregar pack al carrito
</button>
</form>

<div style="text-align:center;margin-top:12px">
<a href="index2.php" style="color:#888;font-size:13px">Seguir comprando</a>
</div>
</div>
</div>

<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
