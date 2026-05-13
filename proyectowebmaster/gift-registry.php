<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if (empty($_SESSION['login'])) { header('location:login.php?redirect=gift-registry.php'); exit(); }

$uid = intval($_SESSION['id']);

mysqli_query($con, "CREATE TABLE IF NOT EXISTS gift_registries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL DEFAULT 'Mi lista de regalos',
    share_token VARCHAR(32) NOT NULL,
    occasion VARCHAR(80) DEFAULT '',
    event_date DATE DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token (share_token),
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS gift_registry_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registry_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    purchased_qty INT DEFAULT 0,
    INDEX(registry_id),
    UNIQUE KEY uq_reg_prod (registry_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$reg = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM gift_registries WHERE user_id=$uid LIMIT 1"));
if (!$reg) {
    $token = bin2hex(random_bytes(16));
    mysqli_query($con, "INSERT INTO gift_registries (user_id, share_token) VALUES ($uid, '$token')");
    $reg = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM gift_registries WHERE user_id=$uid LIMIT 1"));
}
$rid = intval($reg['id']);

if (isset($_POST['save_settings'])) {
    csrf_verify();
    $title   = trim(substr($_POST['title'] ?? 'Mi lista de regalos', 0, 200));
    $occ     = trim(substr($_POST['occasion'] ?? '', 0, 80));
    $ev_date = $_POST['event_date'] ?? '';
    $public  = isset($_POST['is_public']) ? 1 : 0;
    $ev_sql  = $ev_date ? "'".mysqli_real_escape_string($con,$ev_date)."'" : 'NULL';
    $t_esc   = mysqli_real_escape_string($con, $title);
    $o_esc   = mysqli_real_escape_string($con, $occ);
    mysqli_query($con, "UPDATE gift_registries SET title='$t_esc', occasion='$o_esc', event_date=$ev_sql, is_public=$public WHERE id=$rid");
    header('location:gift-registry.php?ok=1'); exit();
}

if (isset($_GET['remove'])) {
    mysqli_query($con, "DELETE FROM gift_registry_items WHERE id=".intval($_GET['remove'])." AND registry_id=$rid");
    header('location:gift-registry.php'); exit();
}

if (isset($_POST['add_product'])) {
    csrf_verify();
    $pid_add = intval($_POST['product_id'] ?? 0);
    $qty_add = max(1, intval($_POST['quantity'] ?? 1));
    if ($pid_add > 0) {
        mysqli_query($con, "INSERT INTO gift_registry_items (registry_id, product_id, quantity) VALUES ($rid, $pid_add, $qty_add) ON DUPLICATE KEY UPDATE quantity=quantity");
    }
    header('location:gift-registry.php'); exit();
}

$items_q = mysqli_query($con, "SELECT gri.*, p.productName, p.productPrice, p.productImage
    FROM gift_registry_items gri
    JOIN products p ON p.id=gri.product_id
    WHERE gri.registry_id=$rid ORDER BY gri.id");

$share_url = 'http://'.$_SERVER['HTTP_HOST'].'/proyectowebmaster/gift-view.php?token='.urlencode($reg['share_token']);
$products_list = mysqli_query($con, "SELECT id, productName FROM products WHERE productStatus='1' ORDER BY productName LIMIT 500");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi lista de regalos | <?php echo $_SITE_NAME; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>
<div class="breadcrumb"><div class="container"><div class="breadcrumb-inner">
<ul class="list-inline list-unstyled">
<li><a href="index2.php">Inicio</a></li>
<li class="active">Lista de regalos</li>
</ul></div></div></div>

<div class="body-content outer-top-bd">
<div class="container">

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Configuracion guardada.</div><?php endif; ?>

<div class="row">
<div class="col-md-8">
<h3><i class="fa fa-gift"></i> <?php echo htmlspecialchars($reg['title']); ?></h3>

<?php if ($reg['occasion'] || $reg['event_date']): ?>
<p class="text-muted">
<?php if ($reg['occasion']) echo htmlspecialchars($reg['occasion']); ?>
<?php if ($reg['event_date']) echo ' &mdash; '.date('d/m/Y', strtotime($reg['event_date'])); ?>
</p>
<?php endif; ?>

<div style="background:#f0f9ff;border:1px solid #b3d9f2;border-radius:8px;padding:14px;margin-bottom:20px">
<strong><i class="fa fa-share-alt"></i> Comparte tu lista:</strong><br>
<div class="input-group" style="margin-top:8px;max-width:500px">
<input type="text" id="share-url" class="form-control" value="<?php echo htmlspecialchars($share_url); ?>" readonly>
<span class="input-group-btn">
<button class="btn btn-default" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value);this.innerHTML='<i class=\'fa fa-check\'></i> Copiado'">
<i class="fa fa-copy"></i> Copiar
</button>
</span>
</div>
<?php if (!$reg['is_public']): ?><small class="text-muted"><i class="fa fa-lock"></i> Lista privada</small><?php endif; ?>
</div>

<table class="table table-bordered" style="font-size:13px">
<thead><tr><th>Producto</th><th>Necesito</th><th>Recibido</th><th>Progreso</th><th></th></tr></thead>
<tbody>
<?php if (!$items_q || mysqli_num_rows($items_q) === 0): ?>
<tr><td colspan="5" class="text-center text-muted">Sin productos. Agrega productos abajo.</td></tr>
<?php else: while ($it = mysqli_fetch_assoc($items_q)):
    $pct = $it['quantity'] > 0 ? min(100, round($it['purchased_qty'] / $it['quantity'] * 100)) : 0;
?>
<tr>
<td>
<img src="admin/assets/images/products/<?php echo htmlspecialchars($it['productImage']); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;margin-right:6px">
<?php echo htmlspecialchars($it['productName']); ?>
</td>
<td><?php echo $it['quantity']; ?></td>
<td><?php echo $it['purchased_qty']; ?></td>
<td style="min-width:100px">
<div style="background:#eee;border-radius:4px;height:10px;overflow:hidden">
<div style="background:<?php echo $pct>=100?'#27ae60':'#3498db'; ?>;height:100%;width:<?php echo $pct; ?>%"></div>
</div>
<small><?php echo $pct; ?>%<?php echo $pct>=100?' <i class="fa fa-check text-success"></i>':''; ?></small>
</td>
<td><a href="gift-registry.php?remove=<?php echo $it['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Quitar?')"><i class="fa fa-times"></i></a></td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

<div class="panel panel-default">
<div class="panel-heading"><strong>Agregar producto a la lista</strong></div>
<div class="panel-body">
<form method="post" class="form-inline">
<?php echo csrf_field(); ?>
<input type="hidden" name="add_product" value="1">
<select name="product_id" class="form-control" style="min-width:280px;margin-right:8px" required>
<option value="">Seleccionar producto</option>
<?php while ($pr = mysqli_fetch_assoc($products_list)): ?>
<option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['productName']); ?></option>
<?php endwhile; ?>
</select>
<input type="number" name="quantity" min="1" value="1" class="form-control" style="width:70px;margin-right:8px">
<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Agregar</button>
</form>
</div>
</div>
</div>

<div class="col-md-4">
<div class="panel panel-default">
<div class="panel-heading"><strong>Configuracion de la lista</strong></div>
<div class="panel-body">
<form method="post">
<?php echo csrf_field(); ?>
<div class="form-group">
<label>Titulo</label>
<input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($reg['title']); ?>" maxlength="200" required>
</div>
<div class="form-group">
<label>Ocasion</label>
<input type="text" name="occasion" class="form-control" value="<?php echo htmlspecialchars($reg['occasion']); ?>" maxlength="80" placeholder="Cumpleanos, Baby shower...">
</div>
<div class="form-group">
<label>Fecha del evento</label>
<input type="date" name="event_date" class="form-control" value="<?php echo $reg['event_date'] ?? ''; ?>">
</div>
<div class="checkbox">
<label><input type="checkbox" name="is_public" value="1" <?php echo $reg['is_public']?'checked':''; ?>> Lista publica</label>
</div>
<button type="submit" name="save_settings" class="btn btn-primary btn-block">Guardar</button>
</form>
</div>
</div>
</div>
</div>

</div></div>
<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
