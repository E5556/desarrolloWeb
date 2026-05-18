<?php
session_start();
error_reporting(0);
include('include/config.php');
if (!isset($_SESSION['alogin'])) { header('location:index.php'); exit(); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS faq (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    category   VARCHAR(100) DEFAULT 'General',
    question   VARCHAR(500) NOT NULL,
    answer     TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active  TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';

if (isset($_GET['delete'])) {
    mysqli_query($con, "DELETE FROM faq WHERE id=".intval($_GET['delete']));
    header('location:faq.php'); exit();
}
if (isset($_GET['toggle'])) {
    mysqli_query($con, "UPDATE faq SET is_active=1-is_active WHERE id=".intval($_GET['toggle']));
    header('location:faq.php'); exit();
}

if (isset($_POST['save_faq'])) {
    $fid  = intval($_POST['fid'] ?? 0);
    $cat  = mysqli_real_escape_string($con, trim($_POST['category'] ?? 'General'));
    $q    = mysqli_real_escape_string($con, trim($_POST['question'] ?? ''));
    $a    = mysqli_real_escape_string($con, trim($_POST['answer'] ?? ''));
    $sort = intval($_POST['sort_order'] ?? 0);
    $act  = intval($_POST['is_active'] ?? 1);
    if (!$q || !$a) {
        $msg = '<div class="alert alert-danger">Completa pregunta y respuesta.</div>';
    } else {
        if ($fid > 0) {
            mysqli_query($con, "UPDATE faq SET category='$cat',question='$q',answer='$a',sort_order=$sort,is_active=$act WHERE id=$fid");
        } else {
            mysqli_query($con, "INSERT INTO faq (category,question,answer,sort_order,is_active) VALUES('$cat','$q','$a',$sort,$act)");
        }
        $msg = '<div class="alert alert-success">FAQ guardada.</div>';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $er = mysqli_query($con, "SELECT * FROM faq WHERE id=".intval($_GET['edit'])." LIMIT 1");
    if ($er) $edit = mysqli_fetch_assoc($er);
}

$faqs_q = mysqli_query($con, "SELECT * FROM faq ORDER BY category, sort_order, id");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FAQ | Admin</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content-area">

<h3><i class="icon-question-sign"></i> FAQ — Preguntas frecuentes</h3>
<?php if ($msg): ?><?php echo $msg; ?><?php endif; ?>

<div class="row-fluid">
<div class="span5">
<div class="panel panel-default">
<div class="panel-heading"><strong><?php echo $edit ? 'Editar pregunta' : 'Nueva pregunta frecuente'; ?></strong></div>
<div class="panel-body">
<form method="post">
<input type="hidden" name="fid" value="<?php echo $edit ? intval($edit['id']) : 0; ?>">
<div class="form-group">
<label>Categoría</label>
<input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit['category'] ?? 'General'); ?>" placeholder="Ej: Envíos, Pagos, Devoluciones">
</div>
<div class="row-fluid">
<div class="span6">
<div class="form-group">
<label>Orden</label>
<input type="number" name="sort_order" class="form-control" value="<?php echo intval($edit['sort_order'] ?? 0); ?>">
</div>
</div>
<div class="span6">
<div class="form-group">
<label>Estado</label>
<select name="is_active" class="form-control">
<option value="1" <?php echo (!$edit || $edit['is_active']) ? 'selected' : ''; ?>>Activo</option>
<option value="0" <?php echo ($edit && !$edit['is_active']) ? 'selected' : ''; ?>>Inactivo</option>
</select>
</div>
</div>
</div>
<div class="form-group">
<label>Pregunta <span class="text-danger">*</span></label>
<input type="text" name="question" class="form-control" required value="<?php echo htmlspecialchars($edit['question'] ?? ''); ?>">
</div>
<div class="form-group">
<label>Respuesta <span class="text-danger">*</span></label>
<textarea name="answer" class="form-control" rows="4" required><?php echo htmlspecialchars($edit['answer'] ?? ''); ?></textarea>
</div>
<button type="submit" name="save_faq" class="btn btn-primary"><?php echo $edit ? 'Guardar cambios' : 'Agregar'; ?></button>
<?php if ($edit): ?><a href="faq.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</form>
</div>
</div>
</div>

<div class="span7">
<table class="table table-bordered table-hover" style="font-size:13px">
<thead><tr><th>#</th><th>Categoría / Pregunta</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$faqs_q || mysqli_num_rows($faqs_q) === 0): ?>
<tr><td colspan="4" class="text-center text-muted">Sin preguntas definidas.</td></tr>
<?php else: while ($f = mysqli_fetch_assoc($faqs_q)): ?>
<tr>
    <td><?php echo $f['sort_order']; ?></td>
    <td>
        <span class="label label-info"><?php echo htmlspecialchars($f['category']); ?></span><br>
        <strong><?php echo htmlspecialchars($f['question']); ?></strong>
        <div style="font-size:11px;color:#888;margin-top:2px"><?php echo mb_strimwidth(htmlspecialchars($f['answer']),0,100,'…'); ?></div>
    </td>
    <td><?php echo $f['is_active'] ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>'; ?></td>
    <td style="white-space:nowrap">
        <a href="faq.php?edit=<?php echo $f['id']; ?>" class="btn btn-xs btn-primary"><i class="icon-edit"></i></a>
        <a href="faq.php?toggle=<?php echo $f['id']; ?>" class="btn btn-xs btn-warning"><i class="icon-refresh"></i></a>
        <a href="faq.php?delete=<?php echo $f['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="icon-trash"></i></a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
<p><a href="../faq.php" target="_blank" class="btn btn-default btn-sm"><i class="icon-external-link"></i> Ver página pública</a></p>
</div>
</div>

</div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
