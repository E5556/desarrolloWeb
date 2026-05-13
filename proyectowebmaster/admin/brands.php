<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$upload_dir = '../brandsimage/';
$msg = ''; $msg_type = 'success';
if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Eliminar
if(isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $r = mysqli_query($con, "SELECT image_path FROM brands WHERE id=$del_id");
    $d = mysqli_fetch_assoc($r);
    if($d) @unlink('../' . $d['image_path']);
    mysqli_query($con, "DELETE FROM brands WHERE id=$del_id");
    header('location:brands.php?msg=deleted'); exit();
}

// Toggle activo
if(isset($_GET['toggle'])) {
    $tog = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE brands SET active = 1 - active WHERE id=$tog");
    header('location:brands.php'); exit();
}

// Guardar cambios (nombre, keyword, orden)
if(isset($_POST['save_brands'])) {
    foreach($_POST['brand_name'] as $id => $name) {
        $id   = intval($id);
        $name = mysqli_real_escape_string($con, trim($name));
        $kw   = mysqli_real_escape_string($con, trim($_POST['keyword'][$id] ?? ''));
        $ord  = intval($_POST['sort_order'][$id] ?? 0);
        mysqli_query($con, "UPDATE brands SET brand_name='$name', keyword='$kw', sort_order=$ord WHERE id=$id");
    }
    $msg = 'Cambios guardados.';
}

// Agregar nueva marca
if(isset($_POST['add_brand'])) {
    $allowed_exts  = ['jpg','jpeg','png','gif','webp','svg'];
    $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    if(!empty($_FILES['brand_image']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['brand_image']['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['brand_image']['tmp_name']);
        finfo_close($finfo);
        if(!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $msg = 'Solo imágenes JPG, PNG, GIF, WEBP, SVG.'; $msg_type = 'error';
        } else {
            $name     = mysqli_real_escape_string($con, trim($_POST['new_brand_name'] ?? 'Marca'));
            $kw       = mysqli_real_escape_string($con, trim($_POST['new_keyword'] ?? $name));
            $filename = 'brand_' . time() . '.' . $ext;
            $db_path  = 'brandsimage/' . $filename;
            $max_ord  = (($__r = mysqli_query($con,"SELECT MAX(sort_order) as m FROM brands")) ? mysqli_fetch_assoc($__r) : ['m'=>0])['m'] ?? 0;
            if(move_uploaded_file($_FILES['brand_image']['tmp_name'], $upload_dir . $filename)) {
                mysqli_query($con,"INSERT INTO brands (brand_name,image_path,keyword,sort_order) VALUES ('$name','$db_path','$kw'," . ($max_ord+1) . ")");
                $msg = 'Marca agregada.';
            } else { $msg='Error al subir imagen.'; $msg_type='error'; }
        }
    } else { $msg='Selecciona una imagen.'; $msg_type='error'; }
}

$brands = [];
$res = mysqli_query($con,"SELECT * FROM brands ORDER BY sort_order ASC");
while($row = mysqli_fetch_assoc($res)) $brands[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Admin | Carrusel de Marcas</title>
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
    <style>
        .brand-card { border:1px solid #ddd; border-radius:4px; padding:10px; margin-bottom:15px; background:#fafafa; }
        .brand-card img { width:100%; max-height:80px; object-fit:contain; border:1px solid #eee; padding:5px; background:#fff; }
        .brand-card .meta label { font-size:11px; font-weight:700; text-transform:uppercase; color:#666; display:block; margin-top:6px; }
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
                            <h3><i class="icon-star"></i> Carrusel de Marcas</h3>
                        </div>
                        <div class="module-body">

                        <?php if($msg): ?>
                        <div class="alert alert-<?php echo $msg_type==='error'?'error':'success'; ?>">
                            <button class="close" data-dismiss="alert">×</button>
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
                        <div class="alert alert-success"><button class="close" data-dismiss="alert">×</button>Marca eliminada.</div>
                        <?php endif; ?>

                        <!-- AGREGAR MARCA -->
                        <div class="add-section">
                            <h4 style="margin-top:0"><i class="icon-plus"></i> Agregar nueva marca</h4>
                            <form method="post" enctype="multipart/form-data" class="form-horizontal">
                                <div class="control-group">
                                    <label class="control-label">Nombre de la marca</label>
                                    <div class="controls">
                                        <input type="text" name="new_brand_name" class="span5" placeholder="Nike, Adidas..." required>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label">Palabra clave de búsqueda</label>
                                    <div class="controls">
                                        <input type="text" name="new_keyword" class="span5" placeholder="Nike">
                                        <span class="help-block">Al hacer clic en el logo, buscará esta palabra.</span>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label">Logo de la marca</label>
                                    <div class="controls">
                                        <input type="file" name="brand_image" accept="image/*" required>
                                        <span class="help-block">JPG, PNG, SVG, WEBP. Fondo blanco o transparente recomendado.</span>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <div class="controls">
                                        <button type="submit" name="add_brand" class="btn btn-primary"><i class="icon-upload"></i> Agregar marca</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- LISTADO -->
                        <h4>Marcas actuales</h4>
                        <form method="post">
                        <div class="row-fluid">
                        <?php foreach($brands as $b): ?>
                            <div class="span3 brand-card">
                                <img src="../<?php echo htmlspecialchars($b['image_path']); ?>" alt="<?php echo htmlspecialchars($b['brand_name']); ?>">
                                <div class="meta">
                                    <label>Nombre</label>
                                    <input type="text" name="brand_name[<?php echo $b['id']; ?>]"
                                           value="<?php echo htmlspecialchars($b['brand_name']); ?>" class="span12">
                                    <label>Palabra clave (búsqueda)</label>
                                    <input type="text" name="keyword[<?php echo $b['id']; ?>]"
                                           value="<?php echo htmlspecialchars($b['keyword']); ?>" class="span12">
                                    <label>Orden</label>
                                    <input type="number" name="sort_order[<?php echo $b['id']; ?>]"
                                           value="<?php echo intval($b['sort_order']); ?>" class="span6" min="1">
                                </div>
                                <div style="margin-top:8px">
                                    <a href="brands.php?toggle=<?php echo $b['id']; ?>"
                                       class="btn btn-mini <?php echo $b['active']?'btn-success':'btn-danger'; ?>">
                                        <?php echo $b['active']?'<i class="icon-eye-open"></i> Activo':'<i class="icon-eye-close"></i> Inactivo'; ?>
                                    </a>
                                    <a href="brands.php?delete=<?php echo $b['id']; ?>"
                                       onclick="return confirm('¿Eliminar esta marca?')"
                                       class="btn btn-mini btn-danger">
                                        <i class="icon-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <div style="margin-top:10px">
                            <button type="submit" name="save_brands" class="btn btn-primary">
                                <i class="icon-save"></i> Guardar cambios
                            </button>
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
