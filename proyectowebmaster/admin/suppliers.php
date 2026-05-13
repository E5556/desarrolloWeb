<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Auto-create tables
mysqli_query($con, "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    contact_name VARCHAR(100) DEFAULT '',
    email VARCHAR(180) DEFAULT '',
    phone VARCHAR(30) DEFAULT '',
    address TEXT DEFAULT '',
    lead_time_days TINYINT UNSIGNED DEFAULT 0,
    notes TEXT DEFAULT '',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($con, "ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL");

$msg = '';

// Delete
if (isset($_GET['del'])) {
    mysqli_query($con,"DELETE FROM suppliers WHERE id=".intval($_GET['del']));
    header('location:suppliers.php?ok=deleted'); exit();
}
// Toggle active
if (isset($_GET['toggle'])) {
    mysqli_query($con,"UPDATE suppliers SET active=1-active WHERE id=".intval($_GET['toggle']));
    header('location:suppliers.php'); exit();
}
// Save supplier
if (isset($_POST['save_supplier'])) {
    $eid  = intval($_POST['edit_id']??0);
    $name = trim(substr($_POST['name']??'',0,120));
    $cn   = trim(substr($_POST['contact_name']??'',0,100));
    $em   = trim(substr($_POST['email']??'',0,180));
    $ph   = trim(substr($_POST['phone']??'',0,30));
    $addr = trim($_POST['address']??'');
    $lt   = max(0, intval($_POST['lead_time_days']??0));
    $notes= trim($_POST['notes']??'');
    if ($name === '') { $msg = 'El nombre es obligatorio.'; }
    else {
        if ($eid > 0) {
            $stmt = mysqli_prepare($con,"UPDATE suppliers SET name=?,contact_name=?,email=?,phone=?,address=?,lead_time_days=?,notes=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,'sssssiis',$name,$cn,$em,$ph,$addr,$lt,$notes,$eid);
        } else {
            $stmt = mysqli_prepare($con,"INSERT INTO suppliers (name,contact_name,email,phone,address,lead_time_days,notes) VALUES(?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,'sssssis',$name,$cn,$em,$ph,$addr,$lt,$notes);
        }
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:suppliers.php?ok=1'); exit();
    }
}
// Assign supplier to product
if (isset($_POST['assign_supplier'])) {
    $ppid = intval($_POST['prod_id']);
    $spid = intval($_POST['sup_id']);
    mysqli_query($con,"UPDATE products SET supplier_id=".($spid?$spid:'NULL')." WHERE id=$ppid");
    header('location:suppliers.php?ok=assigned'); exit();
}

$edit_row = null;
if (isset($_GET['edit'])) {
    $er = mysqli_query($con,"SELECT * FROM suppliers WHERE id=".intval($_GET['edit']));
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
}

$suppliers = mysqli_query($con,"SELECT s.*, COUNT(p.id) as product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id GROUP BY s.id ORDER BY s.name");
$products  = mysqli_query($con,"SELECT p.id, p.productName, COALESCE(s.name,'—') as supname FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.productName LIMIT 200");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proveedores | Admin</title>
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

<h3><i class="icon-truck"></i> Gestión de proveedores</h3>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Operación completada.</div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-danger"><?php echo $msg; ?></div><?php endif; ?>

<div class="row">
<!-- FORMULARIO -->
<div class="col-md-5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit_row ? 'Editar proveedor' : 'Nuevo proveedor'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
<?php $er = $edit_row ?? []; ?>
<div class="form-group">
    <label>Nombre del proveedor <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" required maxlength="120" value="<?php echo htmlspecialchars($er['name']??''); ?>">
</div>
<div class="form-group">
    <label>Persona de contacto</label>
    <input type="text" name="contact_name" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($er['contact_name']??''); ?>">
</div>
<div class="row">
<div class="col-sm-6">
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" class="form-control" maxlength="180" value="<?php echo htmlspecialchars($er['email']??''); ?>">
</div>
</div>
<div class="col-sm-6">
<div class="form-group">
    <label>Teléfono</label>
    <input type="text" name="phone" class="form-control" maxlength="30" value="<?php echo htmlspecialchars($er['phone']??''); ?>">
</div>
</div>
</div>
<div class="form-group">
    <label>Dirección</label>
    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($er['address']??''); ?></textarea>
</div>
<div class="form-group">
    <label>Tiempo de entrega (días)</label>
    <input type="number" name="lead_time_days" class="form-control" min="0" max="365" value="<?php echo intval($er['lead_time_days']??0); ?>">
</div>
<div class="form-group">
    <label>Notas internas</label>
    <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($er['notes']??''); ?></textarea>
</div>
<button type="submit" name="save_supplier" class="btn btn-primary"><?php echo $edit_row?'Guardar cambios':'Crear proveedor'; ?></button>
<?php if ($edit_row): ?><a href="suppliers.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>

<!-- Asignar proveedor a producto -->
<div class="panel panel-default">
<div class="panel-heading"><strong>Asignar proveedor a producto</strong></div>
<div class="panel-body">
<form method="post">
<div class="form-group">
    <label>Producto</label>
    <select name="prod_id" class="form-control">
        <?php mysqli_data_seek($products,0); while ($p=mysqli_fetch_assoc($products)): ?>
        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (<?php echo htmlspecialchars($p['supname']); ?>)</option>
        <?php endwhile; ?>
    </select>
</div>
<div class="form-group">
    <label>Proveedor</label>
    <select name="sup_id" class="form-control">
        <option value="0">— Sin proveedor —</option>
        <?php mysqli_data_seek($suppliers,0); while ($s=mysqli_fetch_assoc($suppliers)): ?>
        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
        <?php endwhile; ?>
    </select>
</div>
<button type="submit" name="assign_supplier" class="btn btn-info btn-sm">Asignar</button>
</form>
</div>
</div>
</div>

<!-- TABLA DE PROVEEDORES -->
<div class="col-md-7">
<table class="table table-bordered table-striped table-hover" style="font-size:13px">
<thead><tr>
    <th>Proveedor</th>
    <th>Contacto</th>
    <th>Entrega</th>
    <th>Productos</th>
    <th>Estado</th>
    <th>Acciones</th>
</tr></thead>
<tbody>
<?php mysqli_data_seek($suppliers,0);
if (!$suppliers || mysqli_num_rows($suppliers)===0): ?>
<tr><td colspan="6" class="text-center text-muted">Sin proveedores todavía.</td></tr>
<?php else: while ($s = mysqli_fetch_assoc($suppliers)): ?>
<tr>
    <td>
        <strong><?php echo htmlspecialchars($s['name']); ?></strong><br>
        <?php if ($s['email']): ?><small><a href="mailto:<?php echo htmlspecialchars($s['email']); ?>"><?php echo htmlspecialchars($s['email']); ?></a></small><?php endif; ?>
    </td>
    <td>
        <?php echo htmlspecialchars($s['contact_name']??'—'); ?><br>
        <small><?php echo htmlspecialchars($s['phone']??''); ?></small>
    </td>
    <td><?php echo $s['lead_time_days']; ?> días</td>
    <td><span class="badge"><?php echo $s['product_count']; ?></span></td>
    <td><span class="label label-<?php echo $s['active']?'success':'default'; ?>"><?php echo $s['active']?'Activo':'Inactivo'; ?></span></td>
    <td style="white-space:nowrap">
        <a href="suppliers.php?edit=<?php echo $s['id']; ?>" class="btn btn-xs btn-primary"><i class="icon-edit"></i></a>
        <a href="suppliers.php?toggle=<?php echo $s['id']; ?>" class="btn btn-xs btn-<?php echo $s['active']?'warning':'success'; ?>"><?php echo $s['active']?'Desactivar':'Activar'; ?></a>
        <a href="suppliers.php?del=<?php echo $s['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar proveedor?')"><i class="icon-trash"></i></a>
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
