
<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin'])){
    header('location:index.php'); exit();
admin_require_perm('perm_products');

}

$MAX_IMAGES = 15;
$allowed_exts  = ['jpg','jpeg','png','gif','webp'];
$allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];

function validate_upload_file($file): bool {
    global $allowed_exts, $allowed_mimes;
    if(empty($file['name'])) return true;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed_exts)) return false;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    return in_array($mime, $allowed_mimes);
}

function safe_filename($name): string {
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($name, PATHINFO_FILENAME));
    return $base . '.' . $ext;
}

if(isset($_POST['submit'])) {
    // Validate all uploaded images
    $valid = true;
    if(!empty($_FILES['productimages']['name'])) {
        foreach($_FILES['productimages']['name'] as $i => $fname) {
            if(empty($fname)) continue;
            $file = [
                'name'     => $_FILES['productimages']['name'][$i],
                'tmp_name' => $_FILES['productimages']['tmp_name'][$i],
                'error'    => $_FILES['productimages']['error'][$i],
            ];
            if(!validate_upload_file($file)){ $valid = false; break; }
        }
    }

    if(!$valid) {
        $_SESSION['msg'] = "Error: Solo se permiten imágenes (jpg, png, gif, webp).";
        $_SESSION['msg_type'] = 'error';
    } else {
        $category          = intval($_POST['category']              ?? 0);
        $subcat            = intval($_POST['subcategory']           ?? 0);
        $productname       = mysqli_real_escape_string($con, trim($_POST['productName']          ?? ''));
        $productcompany    = mysqli_real_escape_string($con, trim($_POST['productCompany']       ?? ''));
        $productprice      = floatval($_POST['productprice']        ?? 0);
        $productpricebd    = floatval($_POST['productpricebd']      ?? 0);
        $productdescription= mysqli_real_escape_string($con, trim($_POST['productDescription']  ?? ''));
        $productscharge    = floatval($_POST['productShippingcharge']?? 0);
        $productavailability = in_array($_POST['productAvailability'] ?? '', ['In Stock','Out of Stock','On Order'])
                               ? $_POST['productAvailability'] : 'Out of Stock';
        $stock_qty = isset($_POST['stock_qty']) && $_POST['stock_qty'] !== '' ? intval($_POST['stock_qty']) : null;

        // Get new product ID
        $qid = mysqli_query($con,"SELECT MAX(id) pid FROM products");
        $productid = intval(mysqli_fetch_assoc($qid)['pid']) + 1;
        $dir = "productimages/$productid";
        if(!is_dir($dir)) mkdir($dir, 0755, true);

        // Process uploaded images
        $saved = [];
        if(!empty($_FILES['productimages']['name'])) {
            foreach($_FILES['productimages']['name'] as $i => $fname) {
                if(empty($fname) || $_FILES['productimages']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $sname = safe_filename($fname);
                if(move_uploaded_file($_FILES['productimages']['tmp_name'][$i], "$dir/$sname")) {
                    $saved[] = $sname;
                }
            }
        }

        $img1 = $saved[0] ?? '';
        $img2 = $saved[1] ?? '';
        $img3 = $saved[2] ?? '';

        // Auto-create stock_qty column if not exists
        mysqli_query($con, "ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_qty INT DEFAULT NULL");

        $stmt = mysqli_prepare($con,
            "INSERT INTO products(category,subCategory,productName,productCompany,productPrice,
             productDescription,shippingCharge,productAvailability,productImage1,productImage2,
             productImage3,productPriceBeforeDiscount,stock_qty) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'iissddssssdI',
            $category, $subcat, $productname, $productcompany,
            $productprice, $productdescription, $productscharge,
            $productavailability, $img1, $img2, $img3, $productpricebd, $stock_qty);
        mysqli_stmt_execute($stmt);
        $new_id = mysqli_insert_id($con);
        mysqli_stmt_close($stmt);

        // Save extra images (index 3+) to product_images table
        for($i = 3; $i < count($saved); $i++) {
            $sname = mysqli_real_escape_string($con, $saved[$i]);
            mysqli_query($con, "INSERT INTO product_images(productId,imageName,sortOrder) VALUES($new_id,'$sname',$i)");
        }

        $_SESSION['msg']      = "Producto insertado correctamente.";
        $_SESSION['msg_type'] = 'success';
    }
    header('location: insert-product.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Insertar Producto</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet">
    <script src="http://js.nicedit.com/nicEdit-latest.js"></script>
    <script>bkLib.onDomLoaded(nicEditors.allTextAreas);</script>
    <style>
        .img-slot { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
        .img-slot .img-num { font-size:.8em; color:#888; min-width:22px; text-align:right; }
        .img-slot input[type=file] { flex:1; }
        .img-slot .btn-remove { padding:2px 8px; font-size:.8em; }
        .img-preview { width:48px; height:48px; object-fit:cover; border-radius:3px;
                       border:1px solid #ddd; display:none; }
        #img-counter { font-size:.8em; color:#888; margin-bottom:6px; }
        #btn-add-img { margin-top:4px; }
    </style>
    <script>
    function getSubcat(val){
        $.ajax({ type:"POST", url:"get_subcat.php", data:'cat_id='+val,
            success:function(data){ $("#subcategory").html(data); } });
    }
    </script>
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
    <div class="module-head"><h3>Insertar Producto</h3></div>
    <div class="module-body">

    <?php if(isset($_SESSION['msg'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']==='error'?'error':'success'; ?>">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <?php echo htmlentities($_SESSION['msg']); $_SESSION['msg']=''; ?>
    </div>
    <?php endif; ?>

    <form class="form-horizontal row-fluid" method="post" enctype="multipart/form-data">

        <div class="control-group">
            <label class="control-label">Categoría</label>
            <div class="controls">
                <select name="category" class="span8" onchange="getSubcat(this.value)" required>
                    <option value="">Seleccionar Categoría</option>
                    <?php $q=mysqli_query($con,"SELECT * FROM category");
                    while($r=mysqli_fetch_assoc($q)): ?>
                    <option value="<?php echo $r['id']; ?>"><?php echo htmlentities($r['categoryName']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">SubCategoría</label>
            <div class="controls">
                <select name="subcategory" id="subcategory" class="span8" required></select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Nombre</label>
            <div class="controls">
                <input type="text" name="productName" placeholder="Nombre del producto" class="span8" required>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Fabricante</label>
            <div class="controls">
                <input type="text" name="productCompany" placeholder="Marca o fabricante" class="span8" required>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Precio sin descuento</label>
            <div class="controls">
                <input type="number" step="0.01" name="productpricebd" placeholder="Precio original" class="span8" required>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Precio de venta</label>
            <div class="controls">
                <input type="number" step="0.01" name="productprice" placeholder="Precio con descuento" class="span8" required>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Descripción</label>
            <div class="controls">
                <textarea name="productDescription" rows="6" class="span8" placeholder="Descripción del producto"></textarea>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Costo de envío</label>
            <div class="controls">
                <input type="number" step="0.01" name="productShippingcharge" placeholder="0" class="span8" required>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Disponibilidad</label>
            <div class="controls">
                <select name="productAvailability" id="availSelect" class="span8" required onchange="toggleStockField(this.value)">
                    <option value="">Seleccionar</option>
                    <option value="In Stock">En Stock</option>
                    <option value="Out of Stock">Sin Stock</option>
                    <option value="On Order">Bajo Pedido</option>
                </select>
            </div>
        </div>

        <div class="control-group" id="stock-field-group">
            <label class="control-label">Stock inicial (unidades)</label>
            <div class="controls">
                <input type="number" min="0" name="stock_qty" id="stock_qty_input" placeholder="Dejar vacío = sin control de stock" class="span8">
                <span class="help-block" id="stock-help">Si ingresas un número, el stock se descontará automáticamente con cada compra.</span>
            </div>
        </div>
        <script>
        function toggleStockField(val) {
            var grp  = document.getElementById('stock-field-group');
            var inp  = document.getElementById('stock_qty_input');
            var help = document.getElementById('stock-help');
            if (val === 'On Order') {
                grp.style.opacity = '.45';
                grp.style.pointerEvents = 'none';
                inp.value = '';
                help.innerHTML = '<span style="color:#856404"><i class="icon-warning-sign"></i> Bajo Pedido no maneja stock físico.</span>';
            } else {
                grp.style.opacity = '1';
                grp.style.pointerEvents = 'auto';
                help.innerHTML = 'Si ingresas un número, el stock se descontará automáticamente con cada compra.';
            }
        }
        </script>

        <!-- ── IMÁGENES DINÁMICAS ── -->
        <div class="control-group">
            <label class="control-label">Imágenes del producto</label>
            <div class="controls">
                <div id="img-counter">1 de <?php echo $MAX_IMAGES; ?> imágenes (mínimo 1)</div>
                <div id="img-slots">
                    <div class="img-slot" id="slot-1">
                        <span class="img-num">1.</span>
                        <input type="file" name="productimages[]" accept="image/*"
                               onchange="previewImg(this,1)" required>
                        <img id="preview-1" class="img-preview" src="" alt="">
                    </div>
                </div>
                <button type="button" id="btn-add-img" class="btn btn-mini btn-info">
                    <i class="icon-plus"></i> Añadir imagen
                </button>
                <span style="font-size:.78em;color:#aaa;margin-left:8px">Máx. <?php echo $MAX_IMAGES; ?> imágenes · JPG, PNG, GIF, WEBP</span>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="icon-ok"></i> Insertar producto
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
<script>
var MAX_IMAGES = <?php echo $MAX_IMAGES; ?>;
var slotCount  = 1;

function updateCounter() {
    $('#img-counter').text(slotCount + ' de ' + MAX_IMAGES + ' imagen' + (slotCount > 1 ? 'es' : '') + ' (mínimo 1)');
    $('#btn-add-img').prop('disabled', slotCount >= MAX_IMAGES);
}

function previewImg(input, num) {
    var preview = document.getElementById('preview-' + num);
    if(!preview) return;
    if(input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

$('#btn-add-img').on('click', function(){
    if(slotCount >= MAX_IMAGES) return;
    slotCount++;
    var html = '<div class="img-slot" id="slot-' + slotCount + '">' +
        '<span class="img-num">' + slotCount + '.</span>' +
        '<input type="file" name="productimages[]" accept="image/*" onchange="previewImg(this,' + slotCount + ')">' +
        '<img id="preview-' + slotCount + '" class="img-preview" src="" alt="">' +
        '<button type="button" class="btn btn-mini btn-danger btn-remove" onclick="removeSlot(' + slotCount + ')">' +
            '<i class="icon-remove"></i>' +
        '</button>' +
    '</div>';
    $('#img-slots').append(html);
    updateCounter();
});

function removeSlot(num) {
    $('#slot-' + num).remove();
    slotCount--;
    // Renumber remaining slots
    $('#img-slots .img-slot').each(function(i) {
        $(this).attr('id', 'slot-' + (i+1));
        $(this).find('.img-num').text((i+1) + '.');
    });
    slotCount = $('#img-slots .img-slot').length;
    updateCounter();
}

updateCounter();
</script>
</body>
</html>
