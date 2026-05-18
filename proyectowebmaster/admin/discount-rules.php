<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS category_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cat_id INT NOT NULL,
    min_qty INT DEFAULT 1,
    discount_pct DECIMAL(5,2) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    label VARCHAR(80) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

if (isset($_GET['del'])) {
    mysqli_query($con, "DELETE FROM category_discounts WHERE id=" . intval($_GET['del']));
    header('location:discount-rules.php?ok=1'); exit();
}
if (isset($_GET['toggle'])) {
    mysqli_query($con, "UPDATE category_discounts SET active=1-active WHERE id=" . intval($_GET['toggle']));
    header('location:discount-rules.php'); exit();
}
if (isset($_POST['save'])) {
    $cat_id  = intval($_POST['cat_id']);
    $min_qty = max(1, intval($_POST['min_qty']));
    $pct     = floatval($_POST['discount_pct']);
    $label   = trim(substr($_POST['label']??'',0,80));
    if ($cat_id > 0 && $pct > 0 && $pct <= 100) {
        $stmt = mysqli_prepare($con, "INSERT INTO category_discounts (cat_id,min_qty,discount_pct,label) VALUES(?,?,?,?)");
        mysqli_stmt_bind_param($stmt,'iids',$cat_id,$min_qty,$pct,$label);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        include_once('../includes/admin-log.php');
        admin_log($con, 'discount_rule', "Regla: cat $cat_id, min $min_qty uds, $pct%");
    }
    header('location:discount-rules.php?ok=1'); exit();
}

$categories = mysqli_query($con, "SELECT id, categoryName FROM category ORDER BY categoryName");
$rules = mysqli_query($con,
    "SELECT dr.*, c.categoryName FROM category_discounts dr JOIN category c ON dr.cat_id=c.id ORDER BY dr.created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Descuentos por categoría | Admin</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content-area">

<h3><i class="icon-percent"></i> Descuentos automáticos por categoría</h3>
<p class="text-muted">Aplica descuentos cuando el cliente compra mínimo X unidades de productos de una categoría.</p>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">Guardado correctamente.</div>
<?php endif; ?>

<div class="row">
<div class="col-md-5">
<div class="panel panel-default">
<div class="panel-heading"><strong>Nueva regla</strong></div>
<div class="panel-body">
<form method="post">
<div class="form-group">
    <label>Categoría</label>
    <select name="cat_id" class="form-control" required>
        <option value="">-- Selecciona --</option>
        <?php while ($c = mysqli_fetch_assoc($categories)): ?>
        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['categoryName']); ?></option>
        <?php endwhile; ?>
    </select>
</div>
<div class="form-group">
    <label>Cantidad mínima de productos de esa categoría</label>
    <input type="number" name="min_qty" class="form-control" min="1" value="2" required>
    <p class="help-block">El descuento aplica si el carrito tiene al menos este número de productos de la categoría.</p>
</div>
<div class="form-group">
    <label>Descuento (%)</label>
    <input type="number" name="discount_pct" class="form-control" min="1" max="100" step="0.5" value="10" required>
</div>
<div class="form-group">
    <label>Descripción (visible en carrito)</label>
    <input type="text" name="label" class="form-control" placeholder="Ej: 10% comprando 2+ en Ropa" maxlength="80">
</div>
<button type="submit" name="save" class="btn btn-primary">Crear regla</button>
</form>
</div>
</div>
</div>

<div class="col-md-7">
<table class="table table-bordered table-hover table-striped">
<thead>
<tr><th>Categoría</th><th>Mín. uds.</th><th>Descuento</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr>
</thead>
<tbody>
<?php if (!$rules || mysqli_num_rows($rules)===0): ?>
<tr><td colspan="6" class="text-center text-muted">Sin reglas todavía.</td></tr>
<?php else: while ($r = mysqli_fetch_assoc($rules)): ?>
<tr>
    <td><?php echo htmlspecialchars($r['categoryName']); ?></td>
    <td><?php echo $r['min_qty']; ?>+</td>
    <td><strong style="color:#e8233a"><?php echo $r['discount_pct']; ?>%</strong></td>
    <td><small><?php echo htmlspecialchars($r['label']); ?></small></td>
    <td><span class="label label-<?php echo $r['active']?'success':'default'; ?>"><?php echo $r['active']?'Activa':'Inactiva'; ?></span></td>
    <td style="white-space:nowrap">
        <a href="discount-rules.php?toggle=<?php echo $r['id']; ?>" class="btn btn-xs btn-<?php echo $r['active']?'warning':'success'; ?>">
            <?php echo $r['active']?'Desactivar':'Activar'; ?>
        </a>
        <a href="discount-rules.php?del=<?php echo $r['id']; ?>" class="btn btn-xs btn-danger"
           onclick="return confirm('¿Eliminar esta regla?')"><i class="icon-trash"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>
</div>
</div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
