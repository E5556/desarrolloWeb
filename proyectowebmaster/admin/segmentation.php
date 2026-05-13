<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
include('../includes/segmentation.php');

ps_segment_init($con);

// Recalcular todos
if (isset($_GET['recalculate'])) {
    $count = ps_segment_all($con);
    header('location:segmentation.php?ok='.$count); exit();
}

// Filtro por segmento
$filter = $_GET['seg'] ?? 'all';
$where = $filter !== 'all' ? "WHERE us.segments LIKE '%" . mysqli_real_escape_string($con, $filter) . "%'" : '';

$users_q = mysqli_query($con, "SELECT u.id, u.name, u.email, u.contactno,
        us.segments, us.total_orders, us.total_spent, us.last_order_date
    FROM users u
    JOIN user_segments us ON us.user_id=u.id
    $where
    ORDER BY us.total_spent DESC
    LIMIT 200");

// Conteos por segmento
$seg_counts = [];
$sc_q = mysqli_query($con, "SELECT segments, COUNT(*) n FROM user_segments GROUP BY segments");
while ($sc_q && $r = mysqli_fetch_assoc($sc_q)) {
    foreach (explode(',', $r['segments']) as $s) {
        $s = trim($s);
        $seg_counts[$s] = ($seg_counts[$s] ?? 0) + $r['n'];
    }
}

$all_segs = ['VIP','Frecuente','Fiel','Nuevo','En riesgo','Inactivo','Regular'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Segmentación | Admin</title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content-area">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
<h3><i class="icon-group"></i> Segmentación de clientes</h3>
<a href="segmentation.php?recalculate=1" class="btn btn-primary"><i class="icon-refresh"></i> Recalcular todos</a>
</div>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">Segmentación actualizada para <?php echo intval($_GET['ok']); ?> clientes.</div>
<?php endif; ?>

<!-- Tarjetas de segmentos -->
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px">
<?php
$seg_colors = ['VIP'=>'#f39c12','Frecuente'=>'#9b59b6','Fiel'=>'#27ae60','Nuevo'=>'#3498db','En riesgo'=>'#e8233a','Inactivo'=>'#95a5a6','Regular'=>'#bdc3c7'];
foreach ($all_segs as $s):
    $cnt = $seg_counts[$s] ?? 0;
    $col = $seg_colors[$s] ?? '#999';
    $active = $filter === $s ? 'border:2px solid #333;' : '';
?>
<a href="segmentation.php?seg=<?php echo urlencode($s); ?>" style="text-decoration:none;background:#fff;border:1px solid #eee;border-radius:10px;padding:12px 18px;min-width:110px;text-align:center;<?php echo $active; ?>transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow=''">
    <div style="font-size:22px;font-weight:700;color:<?php echo $col; ?>"><?php echo $cnt; ?></div>
    <div style="font-size:12px;margin-top:3px"><?php echo ps_segment_badge($s); ?></div>
</a>
<?php endforeach; ?>
<a href="segmentation.php" style="text-decoration:none;background:#f5f5f5;border:1px solid #eee;border-radius:10px;padding:12px 18px;min-width:80px;text-align:center;font-size:12px;color:#555;display:flex;align-items:center;justify-content:center">Ver todos</a>
</div>

<!-- Tabla de clientes -->
<table class="table table-bordered table-striped table-hover" style="font-size:13px">
<thead><tr>
    <th>Cliente</th>
    <th>Segmento</th>
    <th>Pedidos</th>
    <th>Total gastado</th>
    <th>Último pedido</th>
    <th>Acciones</th>
</tr></thead>
<tbody>
<?php if (!$users_q || mysqli_num_rows($users_q) === 0): ?>
<tr><td colspan="6" class="text-center text-muted">Sin datos. Haz clic en "Recalcular todos" primero.</td></tr>
<?php else: while ($u = mysqli_fetch_assoc($users_q)):
    $segs = array_map('trim', explode(',', $u['segments']));
?>
<tr>
    <td>
        <strong><?php echo htmlspecialchars($u['name']); ?></strong><br>
        <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
    </td>
    <td><?php foreach ($segs as $sg) echo ps_segment_badge($sg); ?></td>
    <td><?php echo $u['total_orders']; ?></td>
    <td>$<?php echo number_format($u['total_spent'], 0, '.', ','); ?></td>
    <td><?php echo $u['last_order_date'] ? date('d/m/Y', strtotime($u['last_order_date'])) : '—'; ?></td>
    <td>
        <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" class="btn btn-xs btn-info"><i class="icon-envelope"></i></a>
        <a href="manage-users.php" class="btn btn-xs btn-default"><i class="icon-eye-open"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

</div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
