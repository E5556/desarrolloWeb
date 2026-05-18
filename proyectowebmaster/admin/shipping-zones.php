<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL,
    departments TEXT NOT NULL COMMENT 'CSV de departamentos/ciudades',
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_per_kg DECIMAL(10,2) DEFAULT 0,
    delivery_days_min TINYINT UNSIGNED DEFAULT 1,
    delivery_days_max TINYINT UNSIGNED DEFAULT 3,
    free_from DECIMAL(12,2) DEFAULT NULL COMMENT 'Envío gratis si total >= este valor',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (isset($_GET['del'])) {
    mysqli_query($con, "DELETE FROM shipping_zones WHERE id=" . intval($_GET['del']));
    header('location:shipping-zones.php'); exit();
}
if (isset($_GET['toggle'])) {
    mysqli_query($con, "UPDATE shipping_zones SET active=1-active WHERE id=" . intval($_GET['toggle']));
    header('location:shipping-zones.php'); exit();
}
if (isset($_POST['save_zone'])) {
    $eid   = intval($_POST['edit_id'] ?? 0);
    $name  = trim(substr($_POST['zone_name'] ?? '', 0, 100));
    $depts = trim($_POST['departments'] ?? '');
    $base  = max(0, floatval($_POST['base_price'] ?? 0));
    $ppkg  = max(0, floatval($_POST['price_per_kg'] ?? 0));
    $dmin  = max(1, intval($_POST['delivery_days_min'] ?? 1));
    $dmax  = max($dmin, intval($_POST['delivery_days_max'] ?? 3));
    $free  = !empty($_POST['free_from']) ? max(0, floatval($_POST['free_from'])) : null;

    if ($name === '') { $msg = 'El nombre es obligatorio.'; }
    else {
        if ($eid > 0) {
            $stmt = mysqli_prepare($con, "UPDATE shipping_zones SET zone_name=?,departments=?,base_price=?,price_per_kg=?,delivery_days_min=?,delivery_days_max=?,free_from=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssddiiid', $name, $depts, $base, $ppkg, $dmin, $dmax, $free, $eid);
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO shipping_zones (zone_name,departments,base_price,price_per_kg,delivery_days_min,delivery_days_max,free_from) VALUES(?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssddiid', $name, $depts, $base, $ppkg, $dmin, $dmax, $free);
        }
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:shipping-zones.php?ok=1'); exit();
    }
}

$edit_row = null;
if (isset($_GET['edit'])) {
    $er = mysqli_query($con, "SELECT * FROM shipping_zones WHERE id=" . intval($_GET['edit']));
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
}
$zones = mysqli_query($con, "SELECT * FROM shipping_zones ORDER BY active DESC, zone_name ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Zonas de envío | Admin</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content-area">

<h3><i class="icon-map-marker"></i> Zonas de envío y tarifas</h3>
<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Guardado correctamente.</div><?php endif; ?>
<?php if (!empty($msg)): ?><div class="alert alert-danger"><?php echo $msg; ?></div><?php endif; ?>

<div class="row-fluid">
<div class="span5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit_row ? 'Editar zona' : 'Nueva zona'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
<?php $er = $edit_row ?? []; ?>
<div class="form-group">
    <label>Nombre de zona <span class="text-danger">*</span></label>
    <input type="text" name="zone_name" class="form-control" required value="<?php echo htmlspecialchars($er['zone_name'] ?? ''); ?>" placeholder="Ej: Bogotá, Costa, Nacional">
</div>
<div class="form-group">
    <label>Departamentos / ciudades <small class="text-muted">(separados por coma)</small></label>
    <textarea name="departments" class="form-control" rows="3" placeholder="Bogotá, Cundinamarca, Medellín, Antioquia"><?php echo htmlspecialchars($er['departments'] ?? ''); ?></textarea>
</div>
<div class="row-fluid">
<div class="span6">
<div class="form-group">
    <label>Tarifa base ($)</label>
    <input type="number" name="base_price" class="form-control" min="0" step="100" value="<?php echo $er['base_price'] ?? 0; ?>">
</div>
</div>
<div class="span6">
<div class="form-group">
    <label>Precio/kg adicional ($)</label>
    <input type="number" name="price_per_kg" class="form-control" min="0" step="100" value="<?php echo $er['price_per_kg'] ?? 0; ?>">
</div>
</div>
</div>
<div class="row-fluid">
<div class="span6">
<div class="form-group">
    <label>Días entrega mín.</label>
    <input type="number" name="delivery_days_min" class="form-control" min="1" max="30" value="<?php echo $er['delivery_days_min'] ?? 1; ?>">
</div>
</div>
<div class="span6">
<div class="form-group">
    <label>Días entrega máx.</label>
    <input type="number" name="delivery_days_max" class="form-control" min="1" max="30" value="<?php echo $er['delivery_days_max'] ?? 3; ?>">
</div>
</div>
</div>
<div class="form-group">
    <label>Envío gratis desde ($) <small class="text-muted">(dejar vacío para no aplicar)</small></label>
    <input type="number" name="free_from" class="form-control" min="0" step="1000" value="<?php echo $er['free_from'] ?? ''; ?>" placeholder="150000">
</div>
<button type="submit" name="save_zone" class="btn btn-primary"><?php echo $edit_row ? 'Guardar cambios' : 'Crear zona'; ?></button>
<?php if ($edit_row): ?><a href="shipping-zones.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>
</div>

<div class="span7">
<table class="table table-bordered table-striped table-hover" style="font-size:12px">
<thead><tr><th>Zona</th><th>Departamentos</th><th>Tarifa</th><th>Días</th><th>Gratis desde</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$zones || mysqli_num_rows($zones) === 0): ?>
<tr><td colspan="7" class="text-center text-muted">Sin zonas definidas.</td></tr>
<?php else: while ($z = mysqli_fetch_assoc($zones)): ?>
<tr>
    <td><strong><?php echo htmlspecialchars($z['zone_name']); ?></strong></td>
    <td style="max-width:160px;word-break:break-word"><small><?php echo htmlspecialchars($z['departments']); ?></small></td>
    <td>$<?php echo number_format($z['base_price'], 0, '.', ','); ?><?php echo $z['price_per_kg'] > 0 ? '<br><small>+$'.number_format($z['price_per_kg'],0,'.',',').'/kg</small>' : ''; ?></td>
    <td><?php echo $z['delivery_days_min']; ?>–<?php echo $z['delivery_days_max']; ?> días</td>
    <td><?php echo $z['free_from'] ? '$'.number_format($z['free_from'],0,'.',',') : '—'; ?></td>
    <td><span class="label label-<?php echo $z['active'] ? 'success' : 'default'; ?>"><?php echo $z['active'] ? 'Activa' : 'Inactiva'; ?></span></td>
    <td style="white-space:nowrap">
        <a href="shipping-zones.php?edit=<?php echo $z['id']; ?>" class="btn btn-xs btn-primary"><i class="icon-edit"></i></a>
        <a href="shipping-zones.php?toggle=<?php echo $z['id']; ?>" class="btn btn-xs btn-<?php echo $z['active']?'warning':'success'; ?>"><?php echo $z['active']?'Desact.':'Activar'; ?></a>
        <a href="shipping-zones.php?del=<?php echo $z['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="icon-trash"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
</div>
</div>

</div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
