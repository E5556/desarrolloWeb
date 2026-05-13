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
<title>FAQ | Admin</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/font-awesome.min.css">
<link rel="stylesheet" href="assets/css/admin.css">
<style>body{background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif}
.card{background:#fff;border-radius:10px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:24px}
.faq-row{padding:10px 0;border-bottom:1px solid #f0f0f0;display:flex;align-items:flex-start;gap:12px}
.faq-row .q{font-weight:600;flex:1}
.cat-badge{background:#3498db;color:#fff;border-radius:10px;padding:2px 8px;font-size:11px}
</style>
</head>
<body>
<div class="container" style="padding:30px 0">
<?php echo $msg; ?>

<div class="card">
<h3><i class="fa fa-question-circle"></i> <?php echo $edit ? 'Editar FAQ' : 'Nueva pregunta frecuente'; ?></h3>
<form method="post">
<input type="hidden" name="fid" value="<?php echo $edit ? intval($edit['id']) : 0; ?>">
<div class="row">
<div class="col-md-4">
<label>Categoría</label>
<input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit['category'] ?? 'General'); ?>" placeholder="Ej: Envíos, Pagos, Devoluciones">
</div>
<div class="col-md-2">
<label>Orden</label>
<input type="number" name="sort_order" class="form-control" value="<?php echo intval($edit['sort_order'] ?? 0); ?>">
</div>
<div class="col-md-2">
<label>Estado</label>
<select name="is_active" class="form-control">
<option value="1" <?php echo (!$edit || $edit['is_active']) ? 'selected' : ''; ?>>Activo</option>
<option value="0" <?php echo ($edit && !$edit['is_active']) ? 'selected' : ''; ?>>Inactivo</option>
</select>
</div>
</div>
<div class="row" style="margin-top:10px">
<div class="col-md-12">
<label>Pregunta *</label>
<input type="text" name="question" class="form-control" required value="<?php echo htmlspecialchars($edit['question'] ?? ''); ?>">
</div>
</div>
<div class="row" style="margin-top:10px">
<div class="col-md-12">
<label>Respuesta *</label>
<textarea name="answer" class="form-control" rows="3" required><?php echo htmlspecialchars($edit['answer'] ?? ''); ?></textarea>
</div>
</div>
<div style="margin-top:12px">
<button type="submit" name="save_faq" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
<?php if ($edit): ?><a href="faq.php" class="btn btn-default">Cancelar</a><?php endif; ?>
</div>
</form>
</div>

<div class="card">
<h4><i class="fa fa-list"></i> Preguntas frecuentes (<a href="../faq.php" target="_blank">Ver página pública</a>)</h4>
<?php
$current_cat = null;
if ($faqs_q) while ($f = mysqli_fetch_assoc($faqs_q)):
    if ($f['category'] !== $current_cat) {
        $current_cat = $f['category'];
        echo '<h5 style="margin:16px 0 6px;color:#3498db"><i class="fa fa-folder-o"></i> '.htmlspecialchars($current_cat).'</h5>';
    }
?>
<div class="faq-row">
<div class="q">
<?php if (!$f['is_active']): ?><span style="color:#bbb">[Inactiva] </span><?php endif; ?>
<?php echo htmlspecialchars($f['question']); ?>
<div style="font-size:12px;color:#888;font-weight:400;margin-top:2px"><?php echo mb_strimwidth(htmlspecialchars($f['answer']),0,120,'…'); ?></div>
</div>
<div style="white-space:nowrap">
<a href="faq.php?edit=<?php echo $f['id']; ?>" class="btn btn-xs btn-default"><i class="fa fa-edit"></i></a>
<a href="faq.php?toggle=<?php echo $f['id']; ?>" class="btn btn-xs btn-warning"><i class="fa fa-toggle-on"></i></a>
<a href="faq.php?delete=<?php echo $f['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="fa fa-trash"></i></a>
</div>
</div>
<?php endwhile; ?>
</div>
</div>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
</body>
</html>
