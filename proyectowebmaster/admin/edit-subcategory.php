<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$id  = intval($_GET['id']);
$msg = $errmsg = '';

if (isset($_POST['submit'])) {
    $catid  = intval($_POST['category']);
    $subcat = trim($_POST['subcategory']);

    $cur = ($__r = mysqli_query($con, "SELECT subcategoryImage FROM subcategory WHERE id=$id")) ? mysqli_fetch_assoc($__r) : null;
    $imgName = $cur['subcategoryImage'] ?? '';

    if (!empty($_FILES['subimg']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['subimg']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errmsg = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
        } elseif ($_FILES['subimg']['size'] > 3 * 1024 * 1024) {
            $errmsg = 'La imagen no debe superar 3 MB.';
        } else {
            if ($imgName) @unlink('subcategoryimages/' . $imgName);
            $imgName = uniqid('sub_') . '.' . $ext;
            move_uploaded_file($_FILES['subimg']['tmp_name'], 'subcategoryimages/' . $imgName);
        }
    }

    if (!$errmsg) {
        $st = mysqli_prepare($con,
            "UPDATE subcategory SET categoryid=?, subcategory=?, subcategoryImage=?, updationDate=NOW() WHERE id=?");
        mysqli_stmt_bind_param($st, 'issi', $catid, $subcat, $imgName, $id);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $msg = 'SubCategoría actualizada.';
    }
}

$row = ($__r = mysqli_query($con,
    "SELECT s.*, c.categoryName FROM subcategory s JOIN category c ON c.id=s.categoryid WHERE s.id=$id")) ? mysqli_fetch_assoc($__r) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Editar SubCategoría</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
    .cur-img { max-height:100px; border-radius:6px; border:1px solid #ddd; margin-bottom:6px; display:block; }
    .img-preview-wrap img { max-height:100px; border-radius:6px; border:1px solid #ddd; display:none; margin-top:6px; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content">

<div class="module">
    <div class="module-head"><h3>Editar SubCategoría</h3></div>
    <div class="module-body">
        <?php if ($msg):   ?><div class="alert alert-success"><button class="close" data-dismiss="alert">×</button><?php echo $msg; ?></div><?php endif; ?>
        <?php if ($errmsg):?><div class="alert alert-error"><button class="close" data-dismiss="alert">×</button><?php echo $errmsg; ?></div><?php endif; ?>

        <form class="form-horizontal row-fluid" method="post" enctype="multipart/form-data">
            <div class="control-group">
                <label class="control-label">Categoría padre</label>
                <div class="controls">
                    <select name="category" class="span8 tip" required>
                        <?php $q = mysqli_query($con, "SELECT id, categoryName FROM category");
                        while ($q && $r = mysqli_fetch_assoc($q)): ?>
                        <option value="<?php echo $r['id']; ?>"
                            <?php echo ($r['id'] == $row['categoryid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['categoryName']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Nombre SubCategoría</label>
                <div class="controls">
                    <input type="text" name="subcategory" class="span8 tip" required
                           value="<?php echo htmlspecialchars($row['subcategory'] ?? ''); ?>">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Imagen de banner</label>
                <div class="controls">
                    <?php if (!empty($row['subcategoryImage'])): ?>
                    <p class="help-block">Imagen actual:</p>
                    <img src="subcategoryimages/<?php echo htmlspecialchars($row['subcategoryImage']); ?>"
                         class="cur-img" alt="imagen actual">
                    <?php endif; ?>
                    <input type="file" name="subimg" accept="image/*" id="subimg-input">
                    <p class="help-block">Sube una nueva imagen para reemplazar la actual (JPG, PNG, WEBP · máx 3 MB)</p>
                    <div class="img-preview-wrap">
                        <img id="subimg-preview" src="" alt="Vista previa">
                    </div>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="icon-save"></i> Guardar cambios
                    </button>
                    <a href="subcategory.php" class="btn" style="margin-left:8px">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

</div></div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
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
