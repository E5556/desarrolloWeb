<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Auto-create table
mysqli_query($con, "CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in','out','adjustment') NOT NULL DEFAULT 'adjustment',
    qty_change INT NOT NULL,
    qty_after INT DEFAULT NULL,
    reason VARCHAR(200) DEFAULT '',
    admin_user VARCHAR(80) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(product_id),
    INDEX(created_at)
)");

// Manual adjustment
if (isset($_POST['adjust'])) {
    $pid   = intval($_POST['product_id']);
    $delta = intval($_POST['qty_change']);
    $reason = trim(substr($_POST['reason']??'',0,200));
    $type  = $delta > 0 ? 'in' : ($delta < 0 ? 'out' : 'adjustment');
    if ($pid > 0 && $delta !== 0) {
        // Update stock
        mysqli_query($con, "UPDATE products SET stock_qty = GREATEST(0, COALESCE(stock_qty,0) + $delta) WHERE id=$pid");
        $qty_after_row = mysqli_fetch_assoc(mysqli_query($con,"SELECT stock_qty FROM products WHERE id=$pid LIMIT 1"));
        $qty_after = intval($qty_after_row['stock_qty'] ?? 0);
        $admin_u = $_SESSION['alogin'];
        $stmt = mysqli_prepare($con,"INSERT INTO stock_movements (product_id,type,qty_change,qty_after,reason,admin_user) VALUES(?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt,'isiiss',$pid,$type,$delta,$qty_after,$reason,$admin_u);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:stock-history.php?ok=1&pid='.$pid); exit();
    }
}

// Filter
$filter_pid = intval($_GET['pid'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page-1)*$per_page;

$where = $filter_pid > 0 ? "WHERE sm.product_id=$filter_pid" : '';
$total_q = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) n FROM stock_movements sm $where"));
$total = intval($total_q['n']);
$pages = max(1, ceil($total/$per_page));

$movements = mysqli_query($con,
    "SELECT sm.*, p.productName FROM stock_movements sm
     JOIN products p ON p.id=sm.product_id
     $where ORDER BY sm.created_at DESC LIMIT $per_page OFFSET $offset");

$products = mysqli_query($con,"SELECT id,productName,stock_qty FROM products ORDER BY productName");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Historial de stock | Admin</title>
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

<h3><i class="icon-list-alt"></i> Historial de movimientos de stock</h3>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">Ajuste aplicado correctamente.</div>
<?php endif; ?>

<div class="row">
<div class="col-md-4">
<div class="panel panel-default">
<div class="panel-heading"><strong><i class="icon-plus"></i> Ajuste manual de stock</strong></div>
<div class="panel-body">
<form method="post">
<div class="form-group">
    <label>Producto</label>
    <select name="product_id" class="form-control" required>
        <option value="">-- Selecciona --</option>
        <?php mysqli_data_seek($products,0); while ($p = mysqli_fetch_assoc($products)): ?>
        <option value="<?php echo $p['id']; ?>" <?php echo ($filter_pid==$p['id']?'selected':''); ?>>
            <?php echo htmlspecialchars($p['productName']); ?>
            (stock: <?php echo $p['stock_qty'] ?? 'N/A'; ?>)
        </option>
        <?php endwhile; ?>
    </select>
</div>
<div class="form-group">
    <label>Cambio de cantidad</label>
    <input type="number" name="qty_change" class="form-control" required placeholder="+10 = entrada, -5 = salida">
    <p class="help-block">Positivo = entrada, Negativo = salida</p>
</div>
<div class="form-group">
    <label>Motivo</label>
    <input type="text" name="reason" class="form-control" placeholder="Ej: Restock proveedor, Producto dañado…" maxlength="200">
</div>
<button type="submit" name="adjust" class="btn btn-primary">Aplicar ajuste</button>
</form>
</div>
</div>
</div>

<div class="col-md-8">
<!-- Filtro por producto -->
<form method="get" class="form-inline" style="margin-bottom:12px">
    <select name="pid" class="form-control">
        <option value="0">Todos los productos</option>
        <?php mysqli_data_seek($products,0); while ($p = mysqli_fetch_assoc($products)): ?>
        <option value="<?php echo $p['id']; ?>" <?php echo ($filter_pid==$p['id']?'selected':''); ?>>
            <?php echo htmlspecialchars($p['productName']); ?>
        </option>
        <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-default">Filtrar</button>
    <?php if ($filter_pid > 0): ?><a href="stock-history.php" class="btn btn-link">Ver todos</a><?php endif; ?>
</form>

<table class="table table-bordered table-hover table-striped" style="font-size:13px">
<thead><tr>
    <th>Fecha</th>
    <th>Producto</th>
    <th>Tipo</th>
    <th>Cambio</th>
    <th>Stock resultante</th>
    <th>Motivo</th>
    <th>Admin</th>
</tr></thead>
<tbody>
<?php if (!$movements || mysqli_num_rows($movements)===0): ?>
<tr><td colspan="7" class="text-center text-muted">Sin movimientos registrados.</td></tr>
<?php else: while ($m = mysqli_fetch_assoc($movements)): ?>
<?php $color = $m['type']==='in' ? '#27ae60' : ($m['type']==='out' ? '#e8233a' : '#888'); ?>
<tr>
    <td style="white-space:nowrap"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
    <td><?php echo htmlspecialchars($m['productName']); ?></td>
    <td><span class="label" style="background:<?php echo $color; ?>"><?php echo $m['type']==='in'?'Entrada':($m['type']==='out'?'Salida':'Ajuste'); ?></span></td>
    <td style="color:<?php echo $color; ?>;font-weight:600"><?php echo ($m['qty_change']>0?'+':'').$m['qty_change']; ?></td>
    <td><?php echo $m['qty_after'] ?? '—'; ?></td>
    <td><?php echo htmlspecialchars($m['reason']); ?></td>
    <td><small><?php echo htmlspecialchars($m['admin_user']); ?></small></td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

<!-- Paginación -->
<?php if ($pages > 1): ?>
<div class="pagination pagination-sm">
    <ul>
    <?php for ($i=1;$i<=$pages;$i++): ?>
        <li class="<?php echo $i==$page?'active':''; ?>">
            <a href="?pid=<?php echo $filter_pid; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        </li>
    <?php endfor; ?>
    </ul>
</div>
<?php endif; ?>

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
