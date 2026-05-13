<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$msg = $errmsg = '';

if (isset($_POST['submit'])) {
    $category    = trim($_POST['category']);
    $description = trim($_POST['description']);
    $imgName     = '';

    // ── Upload imagen ────────────────────────────────────────────────────────
    if (!empty($_FILES['catimg']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['catimg']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errmsg = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
        } elseif ($_FILES['catimg']['size'] > 3 * 1024 * 1024) {
            $errmsg = 'La imagen no debe superar 3 MB.';
        } else {
            $imgName = uniqid('cat_') . '.' . $ext;
            move_uploaded_file($_FILES['catimg']['tmp_name'], 'categoryimages/' . $imgName);
        }
    }

    if (!$errmsg) {
        $st = mysqli_prepare($con,
            "INSERT INTO category(categoryName, categoryDescription, categoryImage) VALUES(?,?,?)");
        mysqli_stmt_bind_param($st, 'sss', $category, $description, $imgName);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $msg = 'Categoría creada correctamente.';
    }
}

if (isset($_GET['del'])) {
    $delid = intval($_GET['id']);
    // borrar imagen
    $row_d = ($__r = mysqli_query($con, "SELECT categoryImage FROM category WHERE id=$delid")) ? mysqli_fetch_assoc($__r) : null;
    if (!empty($row_d['categoryImage'])) @unlink('categoryimages/' . $row_d['categoryImage']);
    mysqli_query($con, "DELETE FROM category WHERE id=$delid");
    $msg = 'Categoría eliminada.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Categorías</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
    .img-thumb { width:52px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #ddd; }
    .img-preview-wrap { margin-top:8px; }
    .img-preview-wrap img { max-height:100px; border-radius:6px; border:1px solid #ddd; display:none; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content">

<div class="module">
    <div class="module-head"><h3>Crear Categoría</h3></div>
    <div class="module-body">
        <?php if ($msg):   ?><div class="alert alert-success"><button class="close" data-dismiss="alert">×</button><?php echo $msg; ?></div><?php endif; ?>
        <?php if ($errmsg):?><div class="alert alert-error"><button class="close" data-dismiss="alert">×</button><?php echo $errmsg; ?></div><?php endif; ?>

        <form class="form-horizontal row-fluid" method="post" enctype="multipart/form-data">
            <div class="control-group">
                <label class="control-label">Nombre de categoría</label>
                <div class="controls">
                    <input type="text" name="category" class="span8 tip" placeholder="Ej: Electrónica" required>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Descripción</label>
                <div class="controls">
                    <textarea class="span8" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Imagen de banner</label>
                <div class="controls">
                    <input type="file" name="catimg" accept="image/*" id="catimg-input">
                    <p class="help-block">JPG, PNG, WEBP · máx 3 MB · recomendado 1200×400 px</p>
                    <div class="img-preview-wrap">
                        <img id="catimg-preview" src="" alt="Vista previa">
                    </div>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="icon-plus"></i> Crear categoría
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="module">
    <div class="module-head"><h3>Categorías existentes</h3></div>
    <div class="module-body table">
        <table cellpadding="0" cellspacing="0" border="0"
               class="datatable-1 table table-bordered table-striped display" width="100%">
            <thead>
                <tr><th>#</th><th>Imagen</th><th>Categoría</th><th>Descripción</th><th>Creado</th><th>Acción</th></tr>
            </thead>
            <tbody>
            <?php
            $q = mysqli_query($con, "SELECT * FROM category ORDER BY id DESC");
            $cnt = 1;
            while ($q && $row = mysqli_fetch_assoc($q)):
            ?>
            <tr>
                <td><?php echo $cnt++; ?></td>
                <td>
                    <?php if (!empty($row['categoryImage'])): ?>
                    <img src="categoryimages/<?php echo htmlspecialchars($row['categoryImage']); ?>"
                         class="img-thumb" alt="">
                    <?php else: ?>
                    <span class="label">Sin imagen</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['categoryName']); ?></td>
                <td><?php echo htmlspecialchars(mb_substr($row['categoryDescription'] ?? '', 0, 60)); ?>…</td>
                <td><?php echo $row['creationDate']; ?></td>
                <td>
                    <a href="edit-category.php?id=<?php echo $row['id']; ?>"><i class="icon-edit"></i></a>
                    <a href="category.php?id=<?php echo $row['id']; ?>&del=delete"
                       onclick="return confirm('¿Eliminar categoría?')"><i class="icon-remove-sign"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</div></div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="scripts/datatables/jquery.dataTables.js"></script>
<script>
$('.datatable-1').dataTable();
$('.dataTables_paginate').addClass("btn-group datatable-pagination");
$('.dataTables_paginate > a').wrapInner('<span />');

// preview imagen antes de subir
document.getElementById('catimg-input').addEventListener('change', function(){
    var prev = document.getElementById('catimg-preview');
    if (this.files && this.files[0]) {
        prev.src = URL.createObjectURL(this.files[0]);
        prev.style.display = 'block';
    }
});
</script>
</body>
</html>
