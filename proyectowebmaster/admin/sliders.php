<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$upload_dir = '../assets/images/sliders/';
$msg = ''; $msg_type = 'success';
if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// M3: Agregar columnas de fechas si no existen
mysqli_query($con, "ALTER TABLE sliders ADD COLUMN IF NOT EXISTS active_from DATE DEFAULT NULL");
mysqli_query($con, "ALTER TABLE sliders ADD COLUMN IF NOT EXISTS active_to DATE DEFAULT NULL");

// Eliminar slide
if(isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $r = mysqli_query($con, "SELECT image_path FROM sliders WHERE id=$del_id");
    $d = mysqli_fetch_assoc($r);
    // Solo borrar si no es una imagen por defecto del template
    if($d && strpos($d['image_path'], 'slider') === false) {
        @unlink('../' . $d['image_path']);
    }
    mysqli_query($con, "DELETE FROM sliders WHERE id=$del_id");
    header('location:sliders.php?msg=deleted'); exit();
}

// Toggle activo
if(isset($_GET['toggle'])) {
    $tog_id = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE sliders SET active = 1 - active WHERE id=$tog_id");
    header('location:sliders.php'); exit();
}

// Guardar orden y keywords via POST
if(isset($_POST['save_order'])) {
    foreach($_POST['keyword'] as $id => $kw) {
        $id   = intval($id);
        $kw   = mysqli_real_escape_string($con, trim($kw));
        $ord  = intval($_POST['sort_order'][$id] ?? 0);
        $af   = !empty($_POST['active_from'][$id]) ? "'" . mysqli_real_escape_string($con, $_POST['active_from'][$id]) . "'" : 'NULL';
        $at   = !empty($_POST['active_to'][$id])   ? "'" . mysqli_real_escape_string($con, $_POST['active_to'][$id])   . "'" : 'NULL';
        mysqli_query($con, "UPDATE sliders SET keyword='$kw', sort_order=$ord, active_from=$af, active_to=$at WHERE id=$id");
    }
    $msg = 'Cambios guardados.';
}

// Subir nueva imagen
if(isset($_POST['add_slide'])) {
    $allowed_exts  = ['jpg','jpeg','png','gif','webp'];
    $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
    if(!empty($_FILES['slide_image']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['slide_image']['tmp_name']);
        finfo_close($finfo);
        if(!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $msg = 'Solo imágenes JPG, PNG, GIF, WEBP.'; $msg_type = 'error';
        } else {
            $filename   = 'slide_' . time() . '.' . $ext;
            $path       = $upload_dir . $filename;
            $db_path    = 'assets/images/sliders/' . $filename;
            $keyword    = mysqli_real_escape_string($con, trim($_POST['new_keyword'] ?? ''));
            $max_order  = (($__r = mysqli_query($con, "SELECT MAX(sort_order) as m FROM sliders")) ? mysqli_fetch_assoc($__r) : ['m'=>0])['m'] ?? 0;
            if(move_uploaded_file($_FILES['slide_image']['tmp_name'], $path)) {
                mysqli_query($con, "INSERT INTO sliders (image_path, keyword, sort_order) VALUES ('$db_path','$keyword'," . ($max_order+1) . ")");
                $msg = 'Imagen agregada al carrusel.';
            } else {
                $msg = 'Error al subir la imagen.'; $msg_type = 'error';
            }
        }
    } else {
        $msg = 'Selecciona una imagen.'; $msg_type = 'error';
    }
}

$sliders = [];
$res = mysqli_query($con, "SELECT * FROM sliders ORDER BY sort_order ASC");
while($row = mysqli_fetch_assoc($res)) $sliders[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Carrusel de Imágenes</title>
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
    <style>
        .slide-card { border:1px solid #ddd; border-radius:4px; padding:10px; margin-bottom:15px; background:#fafafa; }
        .slide-card img { width:100%; max-height:120px; object-fit:cover; border-radius:3px; border:1px solid #ccc; }
        .slide-card .meta { margin-top:8px; }
        .slide-card .meta label { font-size:11px; font-weight:700; text-transform:uppercase; color:#666; }
        .slide-card .actions { margin-top:8px; }
        .badge-active   { background:#5cb85c; color:#fff; padding:2px 8px; border-radius:10px; font-size:11px; }
        .badge-inactive { background:#d9534f; color:#fff; padding:2px 8px; border-radius:10px; font-size:11px; }
        .add-section { background:#fff; border:2px dashed #ccc; border-radius:4px; padding:20px; margin-bottom:20px; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper">
    <div class="container">
        <div class="row">
            <?php include('include/sidebar.php'); ?>
            <div class="span9">
                <div class="content">
                    <div class="module">
                        <div class="module-head">
                            <h3><i class="icon-picture"></i> Carrusel de Imágenes</h3>
                        </div>
                        <div class="module-body">

                        <?php if($msg): ?>
                        <div class="alert alert-<?php echo $msg_type==='error'?'error':'success'; ?>">
                            <button class="close" data-dismiss="alert">×</button>
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
                        <div class="alert alert-success"><button class="close" data-dismiss="alert">×</button>Slide eliminado.</div>
                        <?php endif; ?>

                        <!-- AGREGAR NUEVA IMAGEN -->
                        <div class="add-section">
                            <h4 style="margin-top:0"><i class="icon-plus"></i> Agregar nueva imagen al carrusel</h4>
                            <form method="post" enctype="multipart/form-data" class="form-horizontal">
                                <div class="control-group">
                                    <label class="control-label">Imagen</label>
                                    <div class="controls">
                                        <input type="file" name="slide_image" accept="image/*" required>
                                        <span class="help-block">JPG, PNG, WEBP. Recomendado: 1200×500 px.</span>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label">Palabra clave</label>
                                    <div class="controls">
                                        <input type="text" name="new_keyword" class="span5" placeholder="ej: zapatillas, ofertas, deportivo...">
                                        <span class="help-block">Al hacer clic en la imagen, buscará esta palabra en la tienda.</span>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <div class="controls">
                                        <button type="submit" name="add_slide" class="btn btn-primary"><i class="icon-upload"></i> Subir imagen</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- LISTADO DE SLIDES -->
                        <h4>Slides actuales <small>(arrastra para reordenar)</small></h4>
                        <form method="post">
                        <div class="row-fluid" id="slides-list">
                        <?php foreach($sliders as $s): ?>
                            <div class="span4 slide-card">
                                <a href="../search-result.php?product=<?php echo urlencode($s['keyword']); ?>" target="_blank">
                                    <img src="../<?php echo htmlspecialchars($s['image_path']); ?>" alt="slide">
                                </a>
                                <div class="meta">
                                    <label>Palabra clave (clic en imagen → busca esto)</label>
                                    <input type="text" name="keyword[<?php echo $s['id']; ?>]"
                                           value="<?php echo htmlspecialchars($s['keyword']); ?>"
                                           class="span12" placeholder="palabra clave de búsqueda">
                                    <label style="margin-top:6px">Orden</label>
                                    <input type="number" name="sort_order[<?php echo $s['id']; ?>]"
                                           value="<?php echo intval($s['sort_order']); ?>"
                                           class="span4" min="1">
                                    <label style="margin-top:6px">Visible desde (opcional)</label>
                                    <input type="date" name="active_from[<?php echo $s['id']; ?>]"
                                           value="<?php echo $s['active_from'] ?? ''; ?>" class="span12">
                                    <label style="margin-top:4px">Visible hasta (opcional)</label>
                                    <input type="date" name="active_to[<?php echo $s['id']; ?>]"
                                           value="<?php echo $s['active_to'] ?? ''; ?>" class="span12">
                                    <?php
                                    $today_s = date('Y-m-d');
                                    $expired = (!empty($s['active_to']) && $s['active_to'] < $today_s);
                                    $future  = (!empty($s['active_from']) && $s['active_from'] > $today_s);
                                    if ($expired): ?><small style="color:#d9534f">⚠ Fecha expirada</small>
                                    <?php elseif ($future): ?><small style="color:#f0ad4e">⏳ Aún no vigente</small>
                                    <?php endif; ?>
                                </div>
                                <div class="actions">
                                    <a href="sliders.php?toggle=<?php echo $s['id']; ?>" class="btn btn-mini <?php echo $s['active']?'btn-success':'btn-danger'; ?>">
                                        <?php echo $s['active']?'<i class="icon-eye-open"></i> Activo':'<i class="icon-eye-close"></i> Inactivo'; ?>
                                    </a>
                                    <a href="sliders.php?delete=<?php echo $s['id']; ?>"
                                       onclick="return confirm('¿Eliminar este slide?')"
                                       class="btn btn-mini btn-danger">
                                        <i class="icon-trash"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <div class="control-group" style="margin-top:15px">
                            <div class="controls">
                                <button type="submit" name="save_order" class="btn btn-primary">
                                    <i class="icon-save"></i> Guardar cambios (keywords y orden)
                                </button>
                            </div>
                        </div>
                        </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
