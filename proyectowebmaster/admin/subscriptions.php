<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Auto-crear tablas
mysqli_query($con, "CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT DEFAULT '',
    product_id INT DEFAULT NULL,
    price_monthly DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_pct TINYINT UNSIGNED DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active','paused','cancelled') DEFAULT 'active',
    next_billing DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME DEFAULT NULL,
    INDEX(user_id), INDEX(plan_id), INDEX(next_billing)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';

// Cambiar estado de suscripción
if (isset($_GET['sub_action']) && isset($_GET['sid'])) {
    $sid = intval($_GET['sid']);
    $act = in_array($_GET['sub_action'], ['active','paused','cancelled']) ? $_GET['sub_action'] : null;
    if ($act) {
        $extra = $act === 'cancelled' ? ", cancelled_at=NOW()" : "";
        mysqli_query($con, "UPDATE subscriptions SET status='$act'$extra WHERE id=$sid");
    }
    header('location:subscriptions.php?ok=1'); exit();
}

// CRUD planes
if (isset($_POST['save_plan'])) {
    $eid    = intval($_POST['edit_id'] ?? 0);
    $name   = trim(substr($_POST['name'] ?? '', 0, 120));
    $desc   = trim($_POST['description'] ?? '');
    $pid    = intval($_POST['product_id'] ?? 0) ?: null;
    $price  = max(0, floatval($_POST['price_monthly'] ?? 0));
    $disc   = max(0, min(100, intval($_POST['discount_pct'] ?? 0)));

    if ($name === '') { $msg = 'El nombre es obligatorio.'; }
    else {
        if ($eid > 0) {
            $stmt = mysqli_prepare($con, "UPDATE subscription_plans SET name=?,description=?,product_id=?,price_monthly=?,discount_pct=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssidii', $name, $desc, $pid, $price, $disc, $eid);
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO subscription_plans (name,description,product_id,price_monthly,discount_pct) VALUES(?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssdii', $name, $desc, $pid, $price, $disc);
        }
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:subscriptions.php?ok=1'); exit();
    }
}
if (isset($_GET['del_plan'])) {
    mysqli_query($con, "DELETE FROM subscription_plans WHERE id=" . intval($_GET['del_plan']));
    header('location:subscriptions.php'); exit();
}

$edit_row = null;
if (isset($_GET['edit'])) {
    $er = mysqli_query($con, "SELECT * FROM subscription_plans WHERE id=" . intval($_GET['edit']));
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
}

$plans = mysqli_query($con, "SELECT sp.*, p.productName, (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id=sp.id AND s.status='active') as active_count FROM subscription_plans sp LEFT JOIN products p ON p.id=sp.product_id ORDER BY sp.id DESC");
$subs  = mysqli_query($con, "SELECT s.*, u.name as uname, u.email as uemail, sp.name as plan_name FROM subscriptions s JOIN users u ON u.id=s.user_id JOIN subscription_plans sp ON sp.id=s.plan_id ORDER BY s.id DESC LIMIT 100");
$products = mysqli_query($con, "SELECT id, productName FROM products WHERE productStatus='1' ORDER BY productName LIMIT 200");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Suscripciones | Admin</title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content-area">

<h3><i class="icon-repeat"></i> Suscripciones recurrentes</h3>
<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Operación completada.</div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-danger"><?php echo $msg; ?></div><?php endif; ?>

<!-- PLANES -->
<div class="row">
<div class="col-md-5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit_row ? 'Editar plan' : 'Nuevo plan de suscripción'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
<?php $er = $edit_row ?? []; ?>
<div class="form-group">
    <label>Nombre del plan <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($er['name'] ?? ''); ?>">
</div>
<div class="form-group">
    <label>Descripción</label>
    <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($er['description'] ?? ''); ?></textarea>
</div>
<div class="form-group">
    <label>Producto asociado (opcional)</label>
    <select name="product_id" class="form-control">
        <option value="0">— Sin producto específico —</option>
        <?php while ($pr = mysqli_fetch_assoc($products)): ?>
        <option value="<?php echo $pr['id']; ?>" <?php echo ($er['product_id'] ?? 0) == $pr['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pr['productName']); ?></option>
        <?php endwhile; ?>
    </select>
</div>
<div class="row">
<div class="col-sm-6">
<div class="form-group">
    <label>Precio mensual ($)</label>
    <input type="number" name="price_monthly" class="form-control" min="0" step="100" value="<?php echo $er['price_monthly'] ?? 0; ?>">
</div>
</div>
<div class="col-sm-6">
<div class="form-group">
    <label>Descuento (%)</label>
    <input type="number" name="discount_pct" class="form-control" min="0" max="100" value="<?php echo $er['discount_pct'] ?? 0; ?>">
</div>
</div>
</div>
<button type="submit" name="save_plan" class="btn btn-primary"><?php echo $edit_row ? 'Guardar cambios' : 'Crear plan'; ?></button>
<?php if ($edit_row): ?><a href="subscriptions.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>
</div>

<div class="col-md-7">
<table class="table table-bordered table-striped table-hover" style="font-size:13px">
<thead><tr><th>Plan</th><th>Precio/mes</th><th>Descuento</th><th>Activos</th><th>Acciones</th></tr></thead>
<tbody>
<?php mysqli_data_seek($plans, 0);
if (!$plans || mysqli_num_rows($plans) === 0): ?>
<tr><td colspan="5" class="text-center text-muted">Sin planes todavía.</td></tr>
<?php else: while ($pl = mysqli_fetch_assoc($plans)): ?>
<tr>
    <td><strong><?php echo htmlspecialchars($pl['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($pl['description']); ?></small><?php if ($pl['productName']): ?><br><small><i class="fa fa-tag"></i> <?php echo htmlspecialchars($pl['productName']); ?></small><?php endif; ?></td>
    <td>$<?php echo number_format($pl['price_monthly'], 0, '.', ','); ?></td>
    <td><?php echo $pl['discount_pct'] > 0 ? '<span class="badge" style="background:#27ae60">'.$pl['discount_pct'].'%</span>' : '—'; ?></td>
    <td><span class="badge"><?php echo $pl['active_count']; ?></span></td>
    <td style="white-space:nowrap">
        <a href="subscriptions.php?edit=<?php echo $pl['id']; ?>" class="btn btn-xs btn-primary"><i class="icon-edit"></i></a>
        <a href="subscriptions.php?del_plan=<?php echo $pl['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="icon-trash"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
</div>
</div>

<!-- SUSCRIPCIONES ACTIVAS -->
<h4 style="margin-top:20px"><i class="icon-list"></i> Suscripciones de clientes</h4>
<table class="table table-bordered table-striped table-hover" style="font-size:13px">
<thead><tr><th>#</th><th>Cliente</th><th>Plan</th><th>Próx. cobro</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$subs || mysqli_num_rows($subs) === 0): ?>
<tr><td colspan="6" class="text-center text-muted">Sin suscripciones todavía.</td></tr>
<?php else: while ($s = mysqli_fetch_assoc($subs)):
    $st_color = ['active'=>'success','paused'=>'warning','cancelled'=>'danger'][$s['status']] ?? 'default';
    $st_label = ['active'=>'Activa','paused'=>'Pausada','cancelled'=>'Cancelada'][$s['status']] ?? $s['status'];
?>
<tr>
    <td><?php echo $s['id']; ?></td>
    <td><?php echo htmlspecialchars($s['uname']); ?><br><small class="text-muted"><?php echo htmlspecialchars($s['uemail']); ?></small></td>
    <td><?php echo htmlspecialchars($s['plan_name']); ?></td>
    <td><?php echo date('d/m/Y', strtotime($s['next_billing'])); ?></td>
    <td><span class="label label-<?php echo $st_color; ?>"><?php echo $st_label; ?></span></td>
    <td style="white-space:nowrap">
        <?php if ($s['status'] !== 'active'): ?><a href="subscriptions.php?sid=<?php echo $s['id']; ?>&sub_action=active" class="btn btn-xs btn-success">Activar</a><?php endif; ?>
        <?php if ($s['status'] === 'active'): ?><a href="subscriptions.php?sid=<?php echo $s['id']; ?>&sub_action=paused" class="btn btn-xs btn-warning">Pausar</a><?php endif; ?>
        <?php if ($s['status'] !== 'cancelled'): ?><a href="subscriptions.php?sid=<?php echo $s['id']; ?>&sub_action=cancelled" class="btn btn-xs btn-danger" onclick="return confirm('¿Cancelar suscripción?')">Cancelar</a><?php endif; ?>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

</div>
</div>
</div>
</div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
