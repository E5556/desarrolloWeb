<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS product_customizer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    field_label VARCHAR(120) NOT NULL,
    field_type ENUM('text','textarea','color','select','checkbox') DEFAULT 'text',
    field_options TEXT DEFAULT '' COMMENT 'Para select: opciones separadas por |',
    price_extra DECIMAL(10,2) DEFAULT 0,
    required TINYINT(1) DEFAULT 0,
    sort_order TINYINT UNSIGNED DEFAULT 0,
    INDEX(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';

if (isset($_GET['del'])) {
    mysqli_query($con, "DELETE FROM product_customizer WHERE id=" . intval($_GET['del']));
    header('location:product-customizer.php?product_id=' . intval($_GET['product_id'] ?? 0) . '&ok=deleted'); exit();
}

if (isset($_POST['save_field'])) {
    $pid    = intval($_POST['product_id']);
    $eid    = intval($_POST['edit_id'] ?? 0);
    $label  = trim(substr($_POST['field_label'] ?? '', 0, 120));
    $type   = in_array($_POST['field_type'] ?? '', ['text','textarea','color','select','checkbox']) ? $_POST['field_type'] : 'text';
    $opts   = trim($_POST['field_options'] ?? '');
    $extra  = max(0, floatval($_POST['price_extra'] ?? 0));
    $req    = isset($_POST['required']) ? 1 : 0;
    $sort   = intval($_POST['sort_order'] ?? 0);

    if ($label === '' || $pid === 0) { $msg = 'Completa todos los campos requeridos.'; }
    else {
        if ($eid > 0) {
            $stmt = mysqli_prepare($con, "UPDATE product_customizer SET field_label=?,field_type=?,field_options=?,price_extra=?,required=?,sort_order=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssdiis', $label, $type, $opts, $extra, $req, $sort, $eid);
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO product_customizer (product_id,field_label,field_type,field_options,price_extra,required,sort_order) VALUES(?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'issdiii', $pid, $label, $type, $opts, $extra, $req, $sort);
        }
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:product-customizer.php?product_id=' . $pid . '&ok=1'); exit();
    }
}

$product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$product    = $product_id ? mysqli_fetch_assoc(mysqli_query($con, "SELECT id, productName FROM products WHERE id=$product_id LIMIT 1")) : null;
$fields     = $product_id ? mysqli_query($con, "SELECT * FROM product_customizer WHERE product_id=$product_id ORDER BY sort_order, id") : null;

$edit_row = null;
if (isset($_GET['edit'])) {
    $er = mysqli_query($con, "SELECT * FROM product_customizer WHERE id=" . intval($_GET['edit']));
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
    if ($edit_row) $product_id = $edit_row['product_id'];
}

$products_list = mysqli_query($con, "SELECT id, productName FROM products ORDER BY productName LIMIT 300");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configurador de producto | Admin</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content-area">

<h3><i class="icon-magic"></i> Configurador de producto</h3>
<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Guardado correctamente.</div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-danger"><?php echo $msg; ?></div><?php endif; ?>

<!-- Selector de producto -->
<div class="panel panel-default" style="margin-bottom:16px">
<div class="panel-body">
<form method="get" class="form-inline">
<label><strong>Producto:</strong></label>
<select name="product_id" class="form-control" style="margin:0 8px;min-width:280px">
    <option value="0">— Seleccionar producto —</option>
    <?php while ($pr = mysqli_fetch_assoc($products_list)): ?>
    <option value="<?php echo $pr['id']; ?>" <?php echo $pr['id']==$product_id?'selected':''; ?>><?php echo htmlspecialchars($pr['productName']); ?></option>
    <?php endwhile; ?>
</select>
<button type="submit" class="btn btn-default">Ver campos</button>
</form>
</div>
</div>

<?php if ($product): ?>
<h4>Campos de personalización para: <strong><?php echo htmlspecialchars($product['productName']); ?></strong></h4>

<div class="row">
<div class="col-md-5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit_row ? 'Editar campo' : 'Nuevo campo'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
<input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
<?php $er = $edit_row ?? []; ?>
<div class="form-group">
    <label>Etiqueta del campo <span class="text-danger">*</span></label>
    <input type="text" name="field_label" class="form-control" required value="<?php echo htmlspecialchars($er['field_label'] ?? ''); ?>" placeholder="Ej: Texto a grabar">
</div>
<div class="form-group">
    <label>Tipo de campo</label>
    <select name="field_type" class="form-control" id="ft-select">
        <?php foreach(['text'=>'Texto corto','textarea'=>'Texto largo','color'=>'Color (picker)','select'=>'Selector de opciones','checkbox'=>'Casilla (sí/no)'] as $v=>$l): ?>
        <option value="<?php echo $v; ?>" <?php echo ($er['field_type']??'text')===$v?'selected':''; ?>><?php echo $l; ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group" id="opts-group" style="<?php echo ($er['field_type']??'')!=='select'?'display:none':''; ?>">
    <label>Opciones <small class="text-muted">(separadas por |)</small></label>
    <input type="text" name="field_options" class="form-control" value="<?php echo htmlspecialchars($er['field_options'] ?? ''); ?>" placeholder="Rojo | Azul | Verde">
</div>
<div class="row" style="margin-left:0;margin-right:0;">
<div class="col-sm-6">
<div class="form-group">
    <label>Precio extra ($)</label>
    <input type="number" name="price_extra" class="form-control" min="0" step="100" value="<?php echo $er['price_extra'] ?? 0; ?>">
</div>
</div>
<div class="col-sm-6">
<div class="form-group">
    <label>Orden</label>
    <input type="number" name="sort_order" class="form-control" min="0" value="<?php echo $er['sort_order'] ?? 0; ?>">
</div>
</div>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="required" value="1" <?php echo !empty($er['required'])?'checked':''; ?>> Campo obligatorio</label>
</div>
<button type="submit" name="save_field" class="btn btn-primary"><?php echo $edit_row?'Guardar cambios':'Agregar campo'; ?></button>
<?php if ($edit_row): ?><a href="product-customizer.php?product_id=<?php echo $product_id; ?>" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>
</div>

<div class="col-md-7">
<table class="table table-bordered table-hover" style="font-size:13px">
<thead><tr><th>#</th><th>Campo</th><th>Tipo</th><th>Extra</th><th>Req.</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$fields || mysqli_num_rows($fields) === 0): ?>
<tr><td colspan="6" class="text-center text-muted">Sin campos definidos.</td></tr>
<?php else: while ($f = mysqli_fetch_assoc($fields)): ?>
<tr>
    <td><?php echo $f['sort_order']; ?></td>
    <td><strong><?php echo htmlspecialchars($f['field_label']); ?></strong><?php if ($f['field_options']): ?><br><small class="text-muted"><?php echo htmlspecialchars($f['field_options']); ?></small><?php endif; ?></td>
    <td><span class="label label-default"><?php echo $f['field_type']; ?></span></td>
    <td><?php echo $f['price_extra'] > 0 ? '+$'.number_format($f['price_extra'],0,'.',',') : '—'; ?></td>
    <td><?php echo $f['required'] ? '<i class="fa fa-check text-danger"></i>' : '—'; ?></td>
    <td style="white-space:nowrap">
        <a href="product-customizer.php?edit=<?php echo $f['id']; ?>&product_id=<?php echo $product_id; ?>" class="btn btn-xs btn-primary"><i class="icon-edit"></i></a>
        <a href="product-customizer.php?del=<?php echo $f['id']; ?>&product_id=<?php echo $product_id; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="icon-trash"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

</div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script>
$('#ft-select').on('change', function(){
    document.getElementById('opts-group').style.display = this.value === 'select' ? '' : 'none';
}).trigger('change');
</script>
</body>
</html>
