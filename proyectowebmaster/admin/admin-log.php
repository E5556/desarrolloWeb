<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Crear tabla si no existe
mysqli_query($con, "CREATE TABLE IF NOT EXISTS admin_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(created_at)
)");

$filter_admin = trim($_GET['admin'] ?? '');
$where = $filter_admin ? "WHERE admin_user='" . mysqli_real_escape_string($con, $filter_admin) . "'" : '';

$logs = mysqli_query($con, "SELECT * FROM admin_log $where ORDER BY created_at DESC LIMIT 200");

$admins = mysqli_query($con, "SELECT DISTINCT admin_user FROM admin_log ORDER BY admin_user");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log de actividad | Admin</title>
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

<h3><i class="icon-list"></i> Log de actividad admin</h3>

<form method="get" style="margin-bottom:14px;display:flex;gap:10px;align-items:center">
    <select name="admin" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="">Todos los admins</option>
        <?php while($a=mysqli_fetch_assoc($admins)): ?>
        <option value="<?php echo htmlspecialchars($a['admin_user']); ?>" <?php echo $filter_admin===$a['admin_user']?'selected':''; ?>>
            <?php echo htmlspecialchars($a['admin_user']); ?>
        </option>
        <?php endwhile; ?>
    </select>
    <a href="admin-log.php" class="btn btn-default btn-sm">Limpiar filtro</a>
</form>

<table class="table table-bordered table-condensed table-hover table-striped">
<thead>
<tr><th>#</th><th>Admin</th><th>Acción</th><th>Detalles</th><th>IP</th><th>Fecha</th></tr>
</thead>
<tbody>
<?php if (!$logs || mysqli_num_rows($logs)===0): ?>
<tr><td colspan="6" class="text-center text-muted" style="padding:20px">Sin registros todavía.</td></tr>
<?php else: while ($log = mysqli_fetch_assoc($logs)): ?>
<tr>
    <td><?php echo $log['id']; ?></td>
    <td><strong><?php echo htmlspecialchars($log['admin_user']); ?></strong></td>
    <td><span class="label label-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
    <td style="max-width:300px;word-break:break-word"><small><?php echo htmlspecialchars($log['details']); ?></small></td>
    <td><small class="text-muted"><?php echo htmlspecialchars($log['ip']); ?></small></td>
    <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small></td>
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
