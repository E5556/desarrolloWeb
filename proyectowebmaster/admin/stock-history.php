<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_products');

// Agregar columnas faltantes si no existen
mysqli_report(MYSQLI_REPORT_OFF);
mysqli_query($con, "ALTER TABLE stock_movements ADD COLUMN type ENUM('in','out','adjustment') NOT NULL DEFAULT 'adjustment' AFTER product_id");
mysqli_query($con, "ALTER TABLE stock_movements ADD COLUMN qty_after INT DEFAULT NULL AFTER change_qty");
mysqli_query($con, "ALTER TABLE stock_movements ADD COLUMN admin_user VARCHAR(80) DEFAULT '' AFTER qty_after");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// Filters
$filter_pid  = intval($_GET['pid'] ?? 0);
$filter_type = $_GET['tipo'] ?? '';
$filter_user = trim($_GET['usuario'] ?? '');
$filter_from = $_GET['desde'] ?? '';
$filter_to   = $_GET['hasta'] ?? '';
$page        = max(1, intval($_GET['page'] ?? 1));
$per_page    = 50;
$offset      = ($page-1)*$per_page;

$conds = [];
if ($filter_pid > 0) $conds[] = "sm.product_id=$filter_pid";
if (in_array($filter_type, ['in','out','adjustment'])) $conds[] = "sm.type='".mysqli_real_escape_string($con,$filter_type)."'";
if ($filter_user !== '') $conds[] = "sm.admin_user LIKE '%".mysqli_real_escape_string($con,$filter_user)."%'";
if ($filter_from !== '') $conds[] = "DATE(sm.created_at) >= '".mysqli_real_escape_string($con,$filter_from)."'";
if ($filter_to   !== '') $conds[] = "DATE(sm.created_at) <= '".mysqli_real_escape_string($con,$filter_to)."'";
$where = !empty($conds) ? 'WHERE ' . implode(' AND ', $conds) : '';

$total_q = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) n FROM stock_movements sm $where"));
$total = intval($total_q['n']);
$pages = max(1, ceil($total/$per_page));

$movements = mysqli_query($con,
    "SELECT sm.*, p.productName FROM stock_movements sm
     JOIN products p ON p.id=sm.product_id
     $where ORDER BY sm.created_at DESC LIMIT $per_page OFFSET $offset");

// Usuarios únicos para el filtro
$users_q = mysqli_query($con, "SELECT DISTINCT admin_user FROM stock_movements WHERE admin_user != '' ORDER BY admin_user");
$users_list = [];
while ($u = mysqli_fetch_assoc($users_q)) $users_list[] = $u['admin_user'];

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

<h3><i class="icon-list-alt"></i> Historial de movimientos de stock
    <span style="float:right;display:flex;gap:6px">
        <a href="../admin/export.php?type=stock_movements" class="btn btn-xs btn-success"><i class="icon-download"></i> Exportar movimientos CSV</a>
        <a href="../admin/export.php?type=inventory" class="btn btn-xs btn-info"><i class="icon-download"></i> Inventario valorizado CSV</a>
        <a href="inventory-adjust.php" class="btn btn-xs btn-default"><i class="icon-inbox"></i> Ajustar inventario</a>
    </span>
</h3>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">Ajuste aplicado correctamente.</div>
<?php endif; ?>

<div class="row">
<div class="col-md-12">
<!-- Filtros avanzados -->
<form method="get" style="margin-bottom:14px;background:#f8f9fa;padding:14px;border-radius:8px;border:1px solid #e0e0e0">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div>
            <label style="font-size:12px;color:#555;display:block;margin-bottom:3px">Producto</label>
            <select name="pid" class="form-control" style="min-width:160px">
                <option value="0">Todos</option>
                <?php mysqli_data_seek($products,0); while ($p = mysqli_fetch_assoc($products)): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo ($filter_pid==$p['id']?'selected':''); ?>>
                    <?php echo htmlspecialchars($p['productName']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="font-size:12px;color:#555;display:block;margin-bottom:3px">Tipo</label>
            <select name="tipo" class="form-control">
                <option value="">Todos</option>
                <option value="in"         <?php echo $filter_type==='in'?'selected':''; ?>>↑ Entrada</option>
                <option value="out"        <?php echo $filter_type==='out'?'selected':''; ?>>↓ Salida</option>
                <option value="adjustment" <?php echo $filter_type==='adjustment'?'selected':''; ?>>⟳ Ajuste</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;color:#555;display:block;margin-bottom:3px">Usuario</label>
            <select name="usuario" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($users_list as $u): ?>
                <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $filter_user===$u?'selected':''; ?>>
                    <?php echo htmlspecialchars($u); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-size:12px;color:#555;display:block;margin-bottom:3px">Desde</label>
            <input type="date" name="desde" class="form-control" value="<?php echo htmlspecialchars($filter_from); ?>">
        </div>
        <div>
            <label style="font-size:12px;color:#555;display:block;margin-bottom:3px">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?php echo htmlspecialchars($filter_to); ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="stock-history.php" class="btn btn-default">Limpiar</a>
        </div>
    </div>
    <?php if (!empty($conds)): ?>
    <div style="margin-top:8px;font-size:12px;color:#888">
        Mostrando <strong><?php echo $total; ?></strong> resultado(s) con filtros aplicados.
    </div>
    <?php endif; ?>
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
    <td style="color:<?php echo $color; ?>;font-weight:600"><?php echo ($m['change_qty']>0?'+':'').$m['change_qty']; ?></td>
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
            <a href="?pid=<?php echo $filter_pid; ?>&tipo=<?php echo urlencode($filter_type); ?>&usuario=<?php echo urlencode($filter_user); ?>&desde=<?php echo urlencode($filter_from); ?>&hasta=<?php echo urlencode($filter_to); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
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
