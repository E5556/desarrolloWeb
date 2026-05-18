<?php
session_start();
error_reporting(0);
include('include/config.php');
if (!isset($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Create tables
mysqli_query($con, "CREATE TABLE IF NOT EXISTS bundles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    bundle_price DECIMAL(10,2) NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS bundle_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bundle_id  INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';

// Delete
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM bundles WHERE id=$did");
    header('location:bundles.php'); exit();
}

// Toggle active
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE bundles SET is_active = 1 - is_active WHERE id=$tid");
    header('location:bundles.php'); exit();
}

// Save (create or update)
if (isset($_POST['save_bundle'])) {
    $bid   = intval($_POST['bid'] ?? 0);
    $name  = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
    $desc  = mysqli_real_escape_string($con, trim($_POST['description'] ?? ''));
    $price = floatval($_POST['bundle_price'] ?? 0);
    $active= intval($_POST['is_active'] ?? 1);
    $pids  = array_map('intval', $_POST['product_ids'] ?? []);
    $qtys  = array_map('intval', $_POST['quantities'] ?? []);

    if (empty($name) || $price <= 0 || empty($pids)) {
        $msg = '<div class="alert alert-danger">Completa todos los campos obligatorios.</div>';
    } else {
        if ($bid > 0) {
            mysqli_query($con, "UPDATE bundles SET name='$name', description='$desc', bundle_price=$price, is_active=$active WHERE id=$bid");
            mysqli_query($con, "DELETE FROM bundle_items WHERE bundle_id=$bid");
        } else {
            mysqli_query($con, "INSERT INTO bundles (name,description,bundle_price,is_active) VALUES('$name','$desc',$price,$active)");
            $bid = mysqli_insert_id($con);
        }
        $stmt = mysqli_prepare($con, "INSERT INTO bundle_items (bundle_id,product_id,quantity) VALUES(?,?,?)");
        foreach ($pids as $i => $pid) {
            if ($pid <= 0) continue;
            $qty = max(1, $qtys[$i] ?? 1);
            mysqli_stmt_bind_param($stmt, 'iii', $bid, $pid, $qty);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        $msg = '<div class="alert alert-success">Bundle guardado correctamente.</div>';
    }
}

// Load bundle for editing
$edit_bundle = null;
$edit_items  = [];
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $er  = mysqli_query($con, "SELECT * FROM bundles WHERE id=$eid LIMIT 1");
    if ($er) $edit_bundle = mysqli_fetch_assoc($er);
    $ir = mysqli_query($con, "SELECT bi.*, p.productName FROM bundle_items bi JOIN products p ON p.id=bi.product_id WHERE bi.bundle_id=$eid");
    while ($ir && $row = mysqli_fetch_assoc($ir)) $edit_items[] = $row;
}

// Products list for selector
$products_q = mysqli_query($con, "SELECT id, productName, productPrice FROM products WHERE productAvailability='In Stock' ORDER BY productName");
$all_products = [];
while ($products_q && $p = mysqli_fetch_assoc($products_q)) $all_products[] = $p;

// All bundles
$bundles_q = mysqli_query($con, "SELECT b.*, COUNT(bi.id) as item_count FROM bundles b LEFT JOIN bundle_items bi ON bi.bundle_id=b.id GROUP BY b.id ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Bundles | Admin</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
<link rel="stylesheet" href="css/theme.css">
<style>body{background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif}
.card{background:#fff;border-radius:10px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:24px}
.bundle-row{display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #f0f0f0}
.badge-active{background:#27ae60;color:#fff;border-radius:12px;padding:2px 10px;font-size:11px}
.badge-inactive{background:#95a5a6;color:#fff;border-radius:12px;padding:2px 10px;font-size:11px}
.item-row{display:flex;gap:8px;align-items:center;margin-bottom:6px}
</style>
</head>
<body>
<div class="container" style="padding:30px 0">
<?php echo $msg; ?>
<div class="card">
<h3><i class="fa fa-archive"></i> <?php echo $edit_bundle ? 'Editar Bundle' : 'Nuevo Bundle'; ?></h3>
<form method="post">
<input type="hidden" name="bid" value="<?php echo $edit_bundle ? intval($edit_bundle['id']) : 0; ?>">
<div class="row">
<div class="col-md-6">
<label>Nombre del bundle *</label>
<input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_bundle['name'] ?? ''); ?>">
</div>
<div class="col-md-3">
<label>Precio especial del bundle *</label>
<input type="number" name="bundle_price" class="form-control" min="0" step="0.01" required value="<?php echo $edit_bundle ? floatval($edit_bundle['bundle_price']) : ''; ?>">
</div>
<div class="col-md-3">
<label>Estado</label>
<select name="is_active" class="form-control">
<option value="1" <?php echo (!$edit_bundle || $edit_bundle['is_active']) ? 'selected' : ''; ?>>Activo</option>
<option value="0" <?php echo ($edit_bundle && !$edit_bundle['is_active']) ? 'selected' : ''; ?>>Inactivo</option>
</select>
</div>
</div>
<div class="row" style="margin-top:10px">
<div class="col-md-12">
<label>Descripción</label>
<textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_bundle['description'] ?? ''); ?></textarea>
</div>
</div>

<div style="margin-top:16px">
<label><strong>Productos del bundle</strong></label>
<div id="bundle-items">
<?php if ($edit_items): foreach ($edit_items as $it): ?>
<div class="item-row">
<select name="product_ids[]" class="form-control" style="flex:1">
<option value="">-- Producto --</option>
<?php foreach ($all_products as $ap): ?>
<option value="<?php echo $ap['id']; ?>" <?php echo $ap['id']==$it['product_id']?'selected':''; ?>><?php echo htmlspecialchars($ap['productName']); ?> ($<?php echo number_format($ap['productPrice'],0,'.',','); ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantities[]" value="<?php echo intval($it['quantity']); ?>" min="1" class="form-control" style="width:80px"> x
<button type="button" class="btn btn-danger btn-xs" onclick="this.parentNode.remove()"><i class="fa fa-trash"></i></button>
</div>
<?php endforeach; else: ?>
<div class="item-row">
<select name="product_ids[]" class="form-control" style="flex:1">
<option value="">-- Producto --</option>
<?php foreach ($all_products as $ap): ?>
<option value="<?php echo $ap['id']; ?>"><?php echo htmlspecialchars($ap['productName']); ?> ($<?php echo number_format($ap['productPrice'],0,'.',','); ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantities[]" value="1" min="1" class="form-control" style="width:80px"> x
<button type="button" class="btn btn-danger btn-xs" onclick="this.parentNode.remove()"><i class="fa fa-trash"></i></button>
</div>
<?php endif; ?>
</div>
<button type="button" id="add-item" class="btn btn-default btn-sm" style="margin-top:8px"><i class="fa fa-plus"></i> Agregar producto</button>
</div>

<div style="margin-top:16px">
<button type="submit" name="save_bundle" class="btn btn-primary"><i class="fa fa-save"></i> Guardar bundle</button>
<?php if ($edit_bundle): ?><a href="bundles.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</div>
</form>
</div>

<div class="card">
<h4><i class="fa fa-list"></i> Bundles existentes</h4>
<?php if (!$bundles_q || mysqli_num_rows($bundles_q) === 0): ?>
<p class="text-muted">No hay bundles creados aún.</p>
<?php else: while ($b = mysqli_fetch_assoc($bundles_q)): ?>
<div class="bundle-row">
<div style="flex:1">
<strong><?php echo htmlspecialchars($b['name']); ?></strong>
<span style="color:#888;font-size:12px;margin-left:8px"><?php echo intval($b['item_count']); ?> productos</span>
</div>
<div><strong>$<?php echo number_format($b['bundle_price'],0,'.',','); ?></strong></div>
<div><?php echo $b['is_active'] ? '<span class="badge-active">Activo</span>' : '<span class="badge-inactive">Inactivo</span>'; ?></div>
<div>
<a href="bundles.php?edit=<?php echo $b['id']; ?>" class="btn btn-xs btn-default"><i class="fa fa-edit"></i></a>
<a href="bundles.php?toggle=<?php echo $b['id']; ?>" class="btn btn-xs btn-warning"><i class="fa fa-toggle-on"></i></a>
<a href="bundles.php?delete=<?php echo $b['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar bundle?')"><i class="fa fa-trash"></i></a>
<a href="../bundle.php?id=<?php echo $b['id']; ?>" class="btn btn-xs btn-info" target="_blank"><i class="fa fa-eye"></i></a>
</div>
</div>
<?php endwhile; endif; ?>
</div>

</div>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script>
var allProducts = <?php echo json_encode(array_map(function($p){ return ['id'=>$p['id'],'name'=>$p['productName'],'price'=>$p['productPrice']]; }, $all_products)); ?>;
document.getElementById('add-item').addEventListener('click', function(){
    var opts = allProducts.map(p=>'<option value="'+p.id+'">'+p.name+' ($'+Number(p.price).toLocaleString()+')</option>').join('');
    var div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = '<select name="product_ids[]" class="form-control" style="flex:1"><option value="">-- Producto --</option>'+opts+'</select>'
        +'<input type="number" name="quantities[]" value="1" min="1" class="form-control" style="width:80px"> x'
        +'<button type="button" class="btn btn-danger btn-xs" onclick="this.parentNode.remove()"><i class="fa fa-trash"></i></button>';
    document.getElementById('bundle-items').appendChild(div);
});
</script>
</body>
</html>
