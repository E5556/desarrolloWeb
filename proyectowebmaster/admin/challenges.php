<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Auto-crear tablas
mysqli_query($con, "CREATE TABLE IF NOT EXISTS challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT '',
    type ENUM('orders','spend','referrals','reviews') DEFAULT 'orders',
    target_value INT NOT NULL DEFAULT 1,
    reward_points INT NOT NULL DEFAULT 100,
    period ENUM('monthly','weekly','once') DEFAULT 'monthly',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS challenge_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    period_key VARCHAR(20) NOT NULL,
    progress INT DEFAULT 0,
    completed TINYINT(1) DEFAULT 0,
    rewarded TINYINT(1) DEFAULT 0,
    UNIQUE KEY uniq_cp (challenge_id, user_id, period_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';

if (isset($_GET['del'])) {
    mysqli_query($con, "DELETE FROM challenges WHERE id=" . intval($_GET['del']));
    header('location:challenges.php?ok=deleted'); exit();
}
if (isset($_GET['toggle'])) {
    mysqli_query($con, "UPDATE challenges SET active=1-active WHERE id=" . intval($_GET['toggle']));
    header('location:challenges.php'); exit();
}

if (isset($_POST['save_challenge'])) {
    $eid  = intval($_POST['edit_id'] ?? 0);
    $title = trim(substr($_POST['title'] ?? '', 0, 150));
    $desc  = trim($_POST['description'] ?? '');
    $type  = in_array($_POST['type'] ?? '', ['orders','spend','referrals','reviews']) ? $_POST['type'] : 'orders';
    $target = max(1, intval($_POST['target_value'] ?? 1));
    $reward = max(1, intval($_POST['reward_points'] ?? 100));
    $period = in_array($_POST['period'] ?? '', ['monthly','weekly','once']) ? $_POST['period'] : 'monthly';

    if ($title === '') { $msg = 'El título es obligatorio.'; }
    else {
        if ($eid > 0) {
            $stmt = mysqli_prepare($con, "UPDATE challenges SET title=?,description=?,type=?,target_value=?,reward_points=?,period=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssiiisi', $title, $desc, $type, $target, $reward, $period, $eid);
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO challenges (title,description,type,target_value,reward_points,period) VALUES(?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssii s', $title, $desc, $type, $target, $reward, $period);
            // fix bind
            $stmt = mysqli_prepare($con, "INSERT INTO challenges (title,description,type,target_value,reward_points,period) VALUES(?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssiis', $title, $desc, $type, $target, $reward, $period);
        }
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:challenges.php?ok=1'); exit();
    }
}

$edit_row = null;
if (isset($_GET['edit'])) {
    $er = mysqli_query($con, "SELECT * FROM challenges WHERE id=" . intval($_GET['edit']));
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
}

$challenges = mysqli_query($con, "SELECT c.*, (SELECT COUNT(DISTINCT user_id) FROM challenge_progress WHERE challenge_id=c.id AND completed=1) as completions FROM challenges c ORDER BY c.id DESC");

$type_labels = ['orders'=>'Número de órdenes','spend'=>'Gasto total ($)','referrals'=>'Referidos','reviews'=>'Reseñas'];
$period_labels = ['monthly'=>'Mensual','weekly'=>'Semanal','once'=>'Una vez'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Retos y recompensas | Admin</title>
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

<h3><i class="icon-trophy"></i> Retos y recompensas</h3>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Operación completada.</div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-danger"><?php echo $msg; ?></div><?php endif; ?>

<div class="row">
<div class="col-md-5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit_row ? 'Editar reto' : 'Nuevo reto'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
<?php $er = $edit_row ?? []; ?>
<div class="form-group">
    <label>Título <span class="text-danger">*</span></label>
    <input type="text" name="title" class="form-control" required maxlength="150" value="<?php echo htmlspecialchars($er['title'] ?? ''); ?>">
</div>
<div class="form-group">
    <label>Descripción</label>
    <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($er['description'] ?? ''); ?></textarea>
</div>
<div class="row">
<div class="col-sm-6">
<div class="form-group">
    <label>Tipo de reto</label>
    <select name="type" class="form-control">
        <?php foreach ($type_labels as $v => $l): ?>
        <option value="<?php echo $v; ?>" <?php echo (($er['type']??'orders')===$v)?'selected':''; ?>><?php echo $l; ?></option>
        <?php endforeach; ?>
    </select>
</div>
</div>
<div class="col-sm-6">
<div class="form-group">
    <label>Período</label>
    <select name="period" class="form-control">
        <?php foreach ($period_labels as $v => $l): ?>
        <option value="<?php echo $v; ?>" <?php echo (($er['period']??'monthly')===$v)?'selected':''; ?>><?php echo $l; ?></option>
        <?php endforeach; ?>
    </select>
</div>
</div>
</div>
<div class="row">
<div class="col-sm-6">
<div class="form-group">
    <label>Meta (valor)</label>
    <input type="number" name="target_value" class="form-control" min="1" value="<?php echo intval($er['target_value'] ?? 1); ?>">
</div>
</div>
<div class="col-sm-6">
<div class="form-group">
    <label>Recompensa (puntos)</label>
    <input type="number" name="reward_points" class="form-control" min="1" value="<?php echo intval($er['reward_points'] ?? 100); ?>">
</div>
</div>
</div>
<button type="submit" name="save_challenge" class="btn btn-primary"><?php echo $edit_row ? 'Guardar cambios' : 'Crear reto'; ?></button>
<?php if ($edit_row): ?><a href="challenges.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>
</div>

<div class="col-md-7">
<table class="table table-bordered table-striped table-hover" style="font-size:13px">
<thead><tr>
    <th>Reto</th>
    <th>Tipo</th>
    <th>Meta</th>
    <th>Puntos</th>
    <th>Período</th>
    <th>Completados</th>
    <th>Estado</th>
    <th>Acciones</th>
</tr></thead>
<tbody>
<?php if (!$challenges || mysqli_num_rows($challenges) === 0): ?>
<tr><td colspan="8" class="text-center text-muted">Sin retos todavía.</td></tr>
<?php else: while ($c = mysqli_fetch_assoc($challenges)): ?>
<tr>
    <td><strong><?php echo htmlspecialchars($c['title']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($c['description']); ?></small></td>
    <td><?php echo $type_labels[$c['type']] ?? $c['type']; ?></td>
    <td><?php echo $c['target_value']; ?></td>
    <td><span class="badge" style="background:#f39c12"><?php echo $c['reward_points']; ?> pts</span></td>
    <td><?php echo $period_labels[$c['period']] ?? $c['period']; ?></td>
    <td><span class="badge"><?php echo $c['completions']; ?></span></td>
    <td><span class="label label-<?php echo $c['active'] ? 'success' : 'default'; ?>"><?php echo $c['active'] ? 'Activo' : 'Inactivo'; ?></span></td>
    <td style="white-space:nowrap">
        <a href="challenges.php?edit=<?php echo $c['id']; ?>" class="btn btn-xs btn-primary"><i class="icon-edit"></i></a>
        <a href="challenges.php?toggle=<?php echo $c['id']; ?>" class="btn btn-xs btn-<?php echo $c['active'] ? 'warning' : 'success'; ?>"><?php echo $c['active'] ? 'Desactivar' : 'Activar'; ?></a>
        <a href="challenges.php?del=<?php echo $c['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="icon-trash"></i></a>
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
