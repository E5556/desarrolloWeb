<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_marketing');


// Crear tabla
mysqli_query($con, "CREATE TABLE IF NOT EXISTS flash_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sale_price DECIMAL(12,2) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    label VARCHAR(60) DEFAULT 'Flash Sale',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = $msg_type = '';

// Eliminar
if (isset($_GET['del'])) {
    mysqli_query($con, "DELETE FROM flash_sales WHERE id=" . intval($_GET['del']));
    header('location:flash-sales.php?ok=deleted'); exit();
}
// Toggle activo
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE flash_sales SET active = 1-active WHERE id=$tid");
    header('location:flash-sales.php'); exit();
}

// Crear / editar
if (isset($_POST['save'])) {
    $pid       = intval($_POST['product_id']);
    $price     = floatval($_POST['sale_price']);
    $starts    = $_POST['starts_at'] ?? '';
    $ends      = $_POST['ends_at']   ?? '';
    $label     = trim(substr($_POST['label'] ?? 'Flash Sale', 0, 60));
    $edit_id   = intval($_POST['edit_id'] ?? 0);

    if ($pid <= 0 || $price <= 0 || empty($starts) || empty($ends)) {
        $msg = 'Todos los campos son obligatorios y el precio debe ser mayor a 0.';
        $msg_type = 'danger';
    } else {
        if ($edit_id > 0) {
            $stmt = mysqli_prepare($con, "UPDATE flash_sales SET product_id=?,sale_price=?,starts_at=?,ends_at=?,label=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,'idsssi',$pid,$price,$starts,$ends,$label,$edit_id);
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO flash_sales (product_id,sale_price,starts_at,ends_at,label) VALUES(?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,'idsss',$pid,$price,$starts,$ends,$label);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('location:flash-sales.php?ok=saved'); exit();
    }
}

// Cargar para editar
$edit = null;
if (isset($_GET['edit'])) {
    $r = mysqli_query($con, "SELECT * FROM flash_sales WHERE id=" . intval($_GET['edit']));
    $edit = $r ? mysqli_fetch_assoc($r) : null;
}

// Cargar productos para selector
$products = mysqli_query($con, "SELECT id, productName, productPrice FROM products ORDER BY productName ASC");

// Lista de flash sales con nombre de producto
$sales = mysqli_query($con,
    "SELECT fs.*, p.productName, p.productPrice, p.productImage1
     FROM flash_sales fs JOIN products p ON fs.product_id=p.id
     ORDER BY fs.ends_at DESC");

$now = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Flash Sales | Admin</title>
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
<h3><i class="icon-tag"></i> Flash Sales programadas</h3>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">Guardado correctamente.</div>
<?php endif; ?>
<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row">
<div class="col-md-5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit ? 'Editar Flash Sale' : 'Nueva Flash Sale'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="edit_id" value="<?php echo $edit ? $edit['id'] : 0; ?>">

<div class="form-group">
    <label>Producto</label>
    <select name="product_id" class="form-control" required>
        <option value="">-- Selecciona --</option>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <option value="<?php echo $p['id']; ?>" <?php echo ($edit && $edit['product_id']==$p['id'])?'selected':''; ?>>
            <?php echo htmlspecialchars($p['productName']); ?> — $<?php echo number_format($p['productPrice'],0,'.',','); ?>
        </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Precio Flash Sale</label>
    <input type="number" name="sale_price" class="form-control" step="0.01" min="1"
           value="<?php echo $edit ? $edit['sale_price'] : ''; ?>" required placeholder="Ej: 49900">
</div>

<div class="form-group">
    <label>Etiqueta (visible en tienda)</label>
    <input type="text" name="label" class="form-control" maxlength="60"
           value="<?php echo htmlspecialchars($edit['label'] ?? 'Flash Sale'); ?>" placeholder="Flash Sale">
</div>

<div class="form-group">
    <label>Inicio</label>
    <input type="datetime-local" name="starts_at" class="form-control"
           value="<?php echo $edit ? date('Y-m-d\TH:i', strtotime($edit['starts_at'])) : $now; ?>" required>
</div>

<div class="form-group">
    <label>Fin</label>
    <input type="datetime-local" name="ends_at" class="form-control"
           value="<?php echo $edit ? date('Y-m-d\TH:i', strtotime($edit['ends_at'])) : ''; ?>" required>
</div>

<button type="submit" name="save" class="btn btn-primary"><?php echo $edit ? 'Guardar cambios' : 'Crear Flash Sale'; ?></button>
<?php if ($edit): ?><a href="flash-sales.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>
</div>

<div class="col-md-7">
<table class="table table-bordered table-hover table-striped">
<thead>
<tr><th>Producto</th><th>Precio flash</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acciones</th></tr>
</thead>
<tbody>
<?php if (!$sales || mysqli_num_rows($sales)===0): ?>
<tr><td colspan="6" class="text-center text-muted">Sin flash sales.</td></tr>
<?php else: while ($fs = mysqli_fetch_assoc($sales)):
    $now_dt = new DateTime();
    $start  = new DateTime($fs['starts_at']);
    $end    = new DateTime($fs['ends_at']);
    if (!$fs['active']) { $badge='<span class="label label-default">Inactiva</span>'; }
    elseif ($now_dt < $start) { $badge='<span class="label label-info">Programada</span>'; }
    elseif ($now_dt > $end)   { $badge='<span class="label label-important">Expirada</span>'; }
    else                      { $badge='<span class="label label-success">Activa</span>'; }
    $discount = $fs['productPrice'] > 0 ? round((1 - $fs['sale_price']/$fs['productPrice'])*100) : 0;
?>
<tr>
    <td>
        <img src="../admin/productimages/<?php echo $fs['product_id'].'/'.htmlspecialchars($fs['productImage1']); ?>" width="36" style="border-radius:3px;margin-right:6px" onerror="this.style.display='none'">
        <?php echo htmlspecialchars(mb_substr($fs['productName'],0,30)); ?>
        <br><small class="text-muted"><?php echo htmlspecialchars($fs['label']); ?></small>
    </td>
    <td><strong style="color:#e8233a">$<?php echo number_format($fs['sale_price'],0,'.',','); ?></strong><br>
        <small class="text-muted">antes $<?php echo number_format($fs['productPrice'],0,'.',','); ?> &nbsp;(-<?php echo $discount; ?>%)</small></td>
    <td><small><?php echo date('d/m/y H:i', strtotime($fs['starts_at'])); ?></small></td>
    <td><small><?php echo date('d/m/y H:i', strtotime($fs['ends_at'])); ?></small></td>
    <td><?php echo $badge; ?></td>
    <td style="white-space:nowrap">
        <a href="flash-sales.php?edit=<?php echo $fs['id']; ?>" class="btn btn-xs btn-default"><i class="icon-edit"></i></a>
        <a href="flash-sales.php?toggle=<?php echo $fs['id']; ?>" class="btn btn-xs btn-<?php echo $fs['active']?'warning':'success'; ?>"><i class="icon-<?php echo $fs['active']?'pause':'play'; ?>"></i></a>
        <a href="flash-sales.php?del=<?php echo $fs['id']; ?>" class="btn btn-xs btn-danger"
           onclick="return confirm('¿Eliminar esta flash sale?')"><i class="icon-trash"></i></a>
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
