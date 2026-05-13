<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$msg = $errmsg = '';

if (isset($_POST['submit'])) {
    $catid  = intval($_POST['category']);
    $subcat = trim($_POST['subcategory']);
    $imgName = '';

    if (!empty($_FILES['subimg']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['subimg']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errmsg = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
        } elseif ($_FILES['subimg']['size'] > 3 * 1024 * 1024) {
            $errmsg = 'La imagen no debe superar 3 MB.';
        } else {
            $imgName = uniqid('sub_') . '.' . $ext;
            move_uploaded_file($_FILES['subimg']['tmp_name'], 'subcategoryimages/' . $imgName);
        }
    }

    if (!$errmsg) {
        $st = mysqli_prepare($con,
            "INSERT INTO subcategory(categoryid, subcategory, subcategoryImage) VALUES(?,?,?)");
        mysqli_stmt_bind_param($st, 'iss', $catid, $subcat, $imgName);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $msg = 'SubCategoría creada correctamente.';
    }
}

if (isset($_GET['del'])) {
    $delid = intval($_GET['id']);
    $row_d = ($__r = mysqli_query($con, "SELECT subcategoryImage FROM subcategory WHERE id=$delid")) ? mysqli_fetch_assoc($__r) : null;
    if (!empty($row_d['subcategoryImage'])) @unlink('subcategoryimages/' . $row_d['subcategoryImage']);
    mysqli_query($con, "DELETE FROM subcategory WHERE id=$delid");
    $msg = 'SubCategoría eliminada.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | SubCategorías</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
    .img-thumb { width:52px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #ddd; }
    .img-preview-wrap img { max-height:100px; border-radius:6px; border:1px solid #ddd; display:none; margin-top:6px; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content">

<div class="module">
    <div class="module-head"><h3>Crear SubCategoría</h3></div>
    <div class="module-body">
        <?php if ($msg):   ?><div class="alert alert-success"><button class="close" data-dismiss="alert">×</button><?php echo $msg; ?></div><?php endif; ?>
        <?php if ($errmsg):?><div class="alert alert-error"><button class="close" data-dismiss="alert">×</button><?php echo $errmsg; ?></div><?php endif; ?>

        <form class="form-horizontal row-fluid" method="post" enctype="multipart/form-data">
            <div class="control-group">
                <label class="control-label">Categoría padre</label>
                <div class="controls">
                    <select name="category" class="span8 tip" required>
                        <option value="">Seleccione Categoría</option>
                        <?php $q = mysqli_query($con, "SELECT id, categoryName FROM category");
                        while ($q && $r = mysqli_fetch_assoc($q)): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['categoryName']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Nombre SubCategoría</label>
                <div class="controls">
                    <input type="text" name="subcategory" class="span8 tip"
                           placeholder="Ej: Smartphones" required>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Imagen de banner</label>
                <div class="controls">
                    <input type="file" name="subimg" accept="image/*" id="subimg-input">
                    <p class="help-block">JPG, PNG, WEBP · máx 3 MB · recomendado 1200×400 px</p>
                    <div class="img-preview-wrap">
                        <img id="subimg-preview" src="" alt="Vista previa">
                    </div>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="icon-plus"></i> Crear SubCategoría
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="module">
    <div class="module-head"><h3>SubCategorías existentes</h3></div>
    <div class="module-body table">
        <table cellpadding="0" cellspacing="0" border="0"
               class="datatable-1 table table-bordered table-striped display" width="100%">
            <thead>
                <tr><th>#</th><th>Imagen</th><th>Categoría</th><th>SubCategoría</th><th>Creado</th><th>Acción</th></tr>
            </thead>
            <tbody>
            <?php
            $q = mysqli_query($con,
                "SELECT s.id, s.subcategory, s.subcategoryImage, s.creationDate, c.categoryName
                 FROM subcategory s JOIN category c ON c.id = s.categoryid
                 ORDER BY s.id DESC");
            $cnt = 1;
            while ($q && $row = mysqli_fetch_assoc($q)):
            ?>
            <tr>
                <td><?php echo $cnt++; ?></td>
                <td>
                    <?php if (!empty($row['subcategoryImage'])): ?>
                    <img src="subcategoryimages/<?php echo htmlspecialchars($row['subcategoryImage']); ?>"
                         class="img-thumb" alt="">
                    <?php else: ?>
                    <span class="label">Sin imagen</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['categoryName']); ?></td>
                <td><?php echo htmlspecialchars($row['subcategory']); ?></td>
                <td><?php echo $row['creationDate']; ?></td>
                <td>
                    <a href="edit-subcategory.php?id=<?php echo $row['id']; ?>"><i class="icon-edit"></i></a>
                    <a href="subcategory.php?id=<?php echo $row['id']; ?>&del=delete"
                       onclick="return confirm('¿Eliminar subcategoría?')"><i class="icon-remove-sign"></i></a>
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

document.getElementById('subimg-input').addEventListener('change', function(){
    var prev = document.getElementById('subimg-preview');
    if (this.files && this.files[0]) {
        prev.src = URL.createObjectURL(this.files[0]);
        prev.style.display = 'block';
    }
});
</script>
</body>
</html>
