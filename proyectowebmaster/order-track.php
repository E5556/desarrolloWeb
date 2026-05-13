<?php
session_start();
error_reporting(0);
include('includes/config.php');

$token = trim($_GET['token'] ?? '');
if (empty($token)) { header('location:index2.php'); exit(); }

$tok_esc = mysqli_real_escape_string($con, $token);

// Get order info (without exposing sensitive data)
$order_q = mysqli_query($con, "SELECT o.id, o.orderDate, o.orderStatus, o.paymentMethod,
    o.tracking_url, p.productName, p.productImage1, p.id as pid,
    o.quantity, p.productPrice, u.name as customer_name
    FROM orders o
    JOIN products p ON p.id=o.productId
    JOIN users u ON u.id=o.userId
    WHERE o.track_token='$tok_esc' LIMIT 1");

if (!$order_q || mysqli_num_rows($order_q) === 0) {
    echo '<!DOCTYPE html><html><body style="text-align:center;padding:60px;font-family:sans-serif"><h2>Enlace no valido o expirado.</h2><a href="index2.php">Ir a la tienda</a></body></html>';
    exit();
}
$order = mysqli_fetch_assoc($order_q);

// Status timeline steps
$statuses = ['Pending', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
$status_labels = [
    'Pending'          => 'Pedido recibido',
    'Processing'       => 'En preparacion',
    'Shipped'          => 'Enviado',
    'Out for Delivery' => 'En camino',
    'Delivered'        => 'Entregado',
];
$current_idx = array_search($order['orderStatus'], $statuses);
if ($current_idx === false) $current_idx = 0;

$host_base = 'http://'.$_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Seguimiento de pedido #<?php echo intval($order['id']); ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<style>
body { background:#f4f6f9; font-family:'Segoe UI',Arial,sans-serif; padding:30px 0; }
.track-card { max-width:680px; margin:0 auto; background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.08); overflow:hidden; }
.track-header { background:linear-gradient(135deg,#e8233a,#c0392b); color:#fff; padding:28px 32px; }
.track-header h2 { margin:0 0 4px; font-size:1.4em; }
.track-header p { margin:0; opacity:.85; font-size:13px; }
.track-body { padding:28px 32px; }
.timeline { display:flex; justify-content:space-between; position:relative; margin:28px 0 32px; }
.timeline::before { content:''; position:absolute; top:20px; left:0; right:0; height:3px; background:#e0e0e0; z-index:0; }
.tl-step { flex:1; text-align:center; position:relative; z-index:1; }
.tl-dot { width:40px; height:40px; border-radius:50%; background:#e0e0e0; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-size:16px; color:#fff; transition:background .3s; }
.tl-step.done .tl-dot { background:#27ae60; }
.tl-step.current .tl-dot { background:#e8233a; box-shadow:0 0 0 4px rgba(232,35,58,.2); }
.tl-label { font-size:11px; color:#666; line-height:1.3; }
.tl-step.done .tl-label, .tl-step.current .tl-label { color:#333; font-weight:600; }
.progress-bar-track { background:#e0e0e0; border-radius:8px; height:8px; margin-bottom:24px; overflow:hidden; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#27ae60,#2ecc71); border-radius:8px; transition:width .5s; }
.product-row { display:flex; align-items:center; gap:16px; padding:16px; background:#f8f9fa; border-radius:10px; margin-bottom:16px; }
.product-row img { width:70px; height:90px; object-fit:contain; border-radius:6px; background:#fff; }
</style>
</head>
<body>

<div class="track-card">
<div class="track-header">
<h2><i class="fa fa-truck"></i> Seguimiento de pedido</h2>
<p>Pedido #<?php echo intval($order['id']); ?> &bull; <?php echo date('d/m/Y', strtotime($order['orderDate'])); ?></p>
</div>

<div class="track-body">

<!-- Product info -->
<div class="product-row">
<img src="admin/productimages/<?php echo intval($order['pid']); ?>/<?php echo htmlspecialchars($order['productImage1']); ?>" alt="">
<div>
<strong style="font-size:15px"><?php echo htmlspecialchars($order['productName']); ?></strong><br>
<span class="text-muted" style="font-size:13px">Cantidad: <?php echo intval($order['quantity']); ?></span><br>
<span class="text-muted" style="font-size:13px">Metodo de pago: <?php echo htmlspecialchars($order['paymentMethod']); ?></span>
</div>
</div>

<!-- Progress bar -->
<?php $pct = count($statuses) > 1 ? round($current_idx / (count($statuses)-1) * 100) : 0; ?>
<div class="progress-bar-track">
<div class="progress-bar-fill" style="width:<?php echo $pct; ?>%"></div>
</div>

<!-- Timeline -->
<div class="timeline">
<?php foreach ($statuses as $i => $st): ?>
<?php $cls = $i < $current_idx ? 'done' : ($i === $current_idx ? 'current' : ''); ?>
<div class="tl-step <?php echo $cls; ?>">
<div class="tl-dot">
<?php if ($i < $current_idx): ?><i class="fa fa-check"></i><?php elseif ($i === $current_idx): ?><i class="fa fa-circle"></i><?php else: ?><i class="fa fa-circle-o"></i><?php endif; ?>
</div>
<div class="tl-label"><?php echo $status_labels[$st] ?? $st; ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- Status badge -->
<div style="text-align:center;margin-bottom:20px">
<?php
$badge_color = ['Pending'=>'#f39c12','Processing'=>'#3498db','Shipped'=>'#9b59b6','Out for Delivery'=>'#e67e22','Delivered'=>'#27ae60'];
$bc = $badge_color[$order['orderStatus']] ?? '#95a5a6';
?>
<span style="background:<?php echo $bc; ?>;color:#fff;border-radius:20px;padding:6px 20px;font-size:14px;font-weight:600">
<?php echo $status_labels[$order['orderStatus']] ?? htmlspecialchars($order['orderStatus']); ?>
</span>
</div>

<!-- Courier tracking link -->
<?php if (!empty($order['tracking_url'])): ?>
<div style="text-align:center;margin-bottom:20px">
<a href="<?php echo htmlspecialchars($order['tracking_url']); ?>" target="_blank" class="btn btn-primary">
<i class="fa fa-external-link"></i> Rastrear con la transportadora
</a>
</div>
<?php endif; ?>

<!-- Share this link -->
<div style="background:#f0f9ff;border:1px solid #b3d9f2;border-radius:8px;padding:14px;text-align:center">
<small class="text-muted"><i class="fa fa-share-alt"></i> Comparte este enlace de seguimiento:</small><br>
<div class="input-group" style="margin-top:8px;max-width:480px;margin-left:auto;margin-right:auto">
<input type="text" id="share-url" class="form-control input-sm" value="<?php echo htmlspecialchars($host_base.'/proyectowebmaster/order-track.php?token='.urlencode($token)); ?>" readonly>
<span class="input-group-btn">
<button class="btn btn-default btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value);this.innerHTML='<i class=\'fa fa-check\'></i>'"><i class="fa fa-copy"></i></button>
</span>
</div>
</div>

</div>
</div>

<div style="text-align:center;margin-top:20px">
<a href="index2.php" style="color:#888;font-size:13px">Ir a la tienda</a>
</div>

<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
