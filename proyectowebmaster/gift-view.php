<?php
session_start();
error_reporting(0);
include('includes/config.php');

$token = trim($_GET['token'] ?? '');
if (empty($token)) { header('location:index2.php'); exit(); }

$tok_esc = mysqli_real_escape_string($con, $token);
$reg = mysqli_fetch_assoc(mysqli_query($con, "SELECT gr.*, u.name as owner_name FROM gift_registries gr JOIN users u ON u.id=gr.user_id WHERE gr.share_token='$tok_esc' LIMIT 1"));
if (!$reg || (!$reg['is_public'] && (empty($_SESSION['id']) || intval($_SESSION['id']) != intval($reg['user_id'])))) {
    echo '<p style="text-align:center;margin-top:60px">Lista no disponible.</p>'; exit();
}

$rid = intval($reg['id']);

// Handle gift purchase (mark as purchased)
if (isset($_POST['gift_purchase']) && !empty($_SESSION['login'])) {
    include('includes/security.php');
    csrf_verify();
    $item_id = intval($_POST['item_id'] ?? 0);
    $qty_gift = max(1, intval($_POST['qty'] ?? 1));
    if ($item_id > 0) {
        mysqli_query($con, "UPDATE gift_registry_items SET purchased_qty = LEAST(quantity, purchased_qty + $qty_gift) WHERE id=$item_id AND registry_id=$rid");
    }
    header('location:gift-view.php?token='.urlencode($token).'&ok=1'); exit();
}

$items_q = mysqli_query($con, "SELECT gri.*, p.productName, p.productPrice, p.productImage, p.id as pid
    FROM gift_registry_items gri
    JOIN products p ON p.id=gri.product_id
    WHERE gri.registry_id=$rid ORDER BY gri.id");

include('includes/security.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($reg['title']); ?> | Lista de regalos</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
</head>
<body class="cnt-home">
<div class="body-content outer-top-bd" style="padding-top:40px">
<div class="container">

<div style="text-align:center;margin-bottom:30px">
<h2><i class="fa fa-gift" style="color:#e8233a"></i> <?php echo htmlspecialchars($reg['title']); ?></h2>
<p class="text-muted">Lista de <strong><?php echo htmlspecialchars($reg['owner_name']); ?></strong>
<?php if ($reg['occasion']): ?> &mdash; <?php echo htmlspecialchars($reg['occasion']); ?><?php endif; ?>
<?php if ($reg['event_date']): ?> &mdash; <?php echo date('d/m/Y', strtotime($reg['event_date'])); ?><?php endif; ?>
</p>
</div>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success text-center">Gracias! Tu regalo ha sido registrado.</div><?php endif; ?>

<div class="row">
<?php if (!$items_q || mysqli_num_rows($items_q) === 0): ?>
<div class="col-md-12 text-center text-muted">La lista esta vacia.</div>
<?php else: while ($it = mysqli_fetch_assoc($items_q)):
    $remaining = $it['quantity'] - $it['purchased_qty'];
    $pct = $it['quantity'] > 0 ? min(100, round($it['purchased_qty'] / $it['quantity'] * 100)) : 0;
    $fulfilled = $pct >= 100;
?>
<div class="col-md-4 col-sm-6" style="margin-bottom:20px">
<div style="border:1px solid #eee;border-radius:8px;padding:16px;position:relative;<?php echo $fulfilled?'opacity:.6':''; ?>">
<?php if ($fulfilled): ?><div style="position:absolute;top:8px;right:8px;background:#27ae60;color:#fff;border-radius:12px;padding:2px 10px;font-size:11px"><i class="fa fa-check"></i> Completo</div><?php endif; ?>
<img src="admin/assets/images/products/<?php echo htmlspecialchars($it['productImage']); ?>" style="width:100%;height:140px;object-fit:contain;margin-bottom:10px">
<h5 style="font-size:14px"><?php echo htmlspecialchars($it['productName']); ?></h5>
<p style="color:#e8233a;font-weight:700">$<?php echo number_format($it['productPrice'],0,'.',','); ?></p>
<div style="background:#eee;border-radius:4px;height:8px;overflow:hidden;margin-bottom:6px">
<div style="background:<?php echo $fulfilled?'#27ae60':'#3498db'; ?>;height:100%;width:<?php echo $pct; ?>%"></div>
</div>
<small class="text-muted"><?php echo $it['purchased_qty']; ?> de <?php echo $it['quantity']; ?> regalado<?php echo $it['quantity']!=1?'s':''; ?></small>

<?php if (!$fulfilled && !empty($_SESSION['login'])): ?>
<form method="post" style="margin-top:10px">
<?php echo csrf_field(); ?>
<input type="hidden" name="gift_purchase" value="1">
<input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
<div class="input-group input-group-sm">
<input type="number" name="qty" min="1" max="<?php echo $remaining; ?>" value="1" class="form-control">
<span class="input-group-btn">
<button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-gift"></i> Regalar</button>
</span>
</div>
</form>
<?php elseif (!$fulfilled): ?>
<div style="margin-top:10px">
<a href="login.php?redirect=gift-view.php?token=<?php echo urlencode($token); ?>" class="btn btn-default btn-sm btn-block">Inicia sesion para regalar</a>
</div>
<?php endif; ?>
</div>
</div>
<?php endwhile; endif; ?>
</div>

</div></div>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
