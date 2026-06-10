
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

        // ── G3: Guardar variantes si aplica ──────────────────────────────
        if (!empty($_POST['has_variants'])) {
            // Reutilizar misma lógica que edit-products, pero con $pid = $new_id
            $pid = $new_id;
            // DDL guard (misma función inline)
            mysqli_report(MYSQLI_REPORT_OFF);
            $_need = ['sku'=>'VARCHAR(80) NULL DEFAULT NULL','barcode'=>'VARCHAR(80) NULL DEFAULT NULL','image'=>'VARCHAR(255) NULL DEFAULT NULL','is_active'=>'TINYINT(1) NOT NULL DEFAULT 1'];
            foreach ($_need as $_cn => $_cdef) {
                $_cq = mysqli_query($con,"SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='product_variants' AND COLUMN_NAME='$_cn'");
                if (!$_cq || !mysqli_fetch_row($_cq)) mysqli_query($con,"ALTER TABLE product_variants ADD COLUMN $_cn $_cdef");
            }
            mysqli_query($con,"CREATE TABLE IF NOT EXISTS product_attributes (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, attr_name VARCHAR(80) NOT NULL, sort_order TINYINT NOT NULL DEFAULT 0, INDEX idx_pa_product(product_id), UNIQUE KEY uq_pa_pid_attr(product_id,attr_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            mysqli_query($con,"CREATE TABLE IF NOT EXISTS variant_attribute_values (id INT AUTO_INCREMENT PRIMARY KEY, variant_id INT NOT NULL, attr_name VARCHAR(80) NOT NULL, attr_value VARCHAR(120) NOT NULL, INDEX idx_vav_variant(variant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $attr_names = array_map('trim', $_POST['attr_name'] ?? []);
            foreach ($attr_names as $_ao => $_an) {
                if ($_an === '') continue;
                $esa = mysqli_real_escape_string($con,$_an);
                mysqli_query($con,"INSERT IGNORE INTO product_attributes(product_id,attr_name,sort_order) VALUES($pid,'$esa',$_ao)");
            }
            if (!is_dir("productimages/$pid")) mkdir("productimages/$pid",0755,true);
            $v_ins = mysqli_prepare($con,"INSERT INTO product_variants(product_id,variant_name,variant_value,sku,stock_qty,price_extra,is_active) VALUES(?,?,?,?,?,?,1)");
            foreach (($_POST['var_id'] ?? []) as $vi => $vid) {
                $vsku  = trim(substr($_POST['var_sku'][$vi]  ?? '',0,80));
                $vprice= floatval($_POST['var_price'][$vi] ?? 0);
                $vstk  = intval($_POST['var_stock'][$vi] ?? 0);
                $combo_keys=[]; $combo_vals=[];
                foreach ($attr_names as $_an) {
                    $fk='vattr_'.preg_replace('/[^a-z0-9]/i','_',strtolower($_an));
                    $combo_keys[]=$_an; $combo_vals[]=trim(($_POST[$fk][$vi]??''));
                }
                $vname=implode('/',$combo_keys); $vval=implode('/',$combo_vals);
                if (str_replace('/','',trim($vval))==='') continue;
                mysqli_stmt_bind_param($v_ins,'isssiid',$pid,$vname,$vval,$vsku,$vstk,$vprice);
                mysqli_stmt_execute($v_ins);
                $saved_id = mysqli_insert_id($con);
                // variant_attribute_values
                foreach ($attr_names as $_an) {
                    $fk='vattr_'.preg_replace('/[^a-z0-9]/i','_',strtolower($_an));
                    $_av=trim(($_POST[$fk][$vi]??''));
                    if ($_an===''||$_av==='') continue;
                    $esa=mysqli_real_escape_string($con,$_an); $esv=mysqli_real_escape_string($con,$_av);
                    mysqli_query($con,"INSERT INTO variant_attribute_values(variant_id,attr_name,attr_value) VALUES($saved_id,'$esa','$esv')");
                }
                // imagen de variante
                if (isset($_FILES['var_image']['name'][$vi]) && $_FILES['var_image']['error'][$vi]===UPLOAD_ERR_OK) {
                    $_vext=strtolower(pathinfo($_FILES['var_image']['name'][$vi],PATHINFO_EXTENSION));
                    if (in_array($_vext,['jpg','jpeg','png','gif','webp'])) {
                        $_vfname="variant_{$saved_id}.{$_vext}";
                        if (move_uploaded_file($_FILES['var_image']['tmp_name'][$vi],"productimages/$pid/$_vfname"))
                            mysqli_query($con,"UPDATE product_variants SET image='$_vfname' WHERE id=$saved_id");
                    }
                }
            }
            if (isset($v_ins)) mysqli_stmt_close($v_ins);
            // Recalcular stock total
            $stot=mysqli_fetch_assoc(mysqli_query($con,"SELECT COALESCE(SUM(stock_qty),0) t FROM product_variants WHERE product_id=$pid AND is_active=1"));
            mysqli_query($con,"UPDATE products SET stock_qty=".intval($stot['t'])." WHERE id=$pid");
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

        <!-- ── G3: VARIANTES / SKUs ── -->
        <div class="control-group">
        <label class="control-label">Variantes / SKUs</label>
        <div class="controls">
        <label style="font-weight:normal;margin-bottom:10px;display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="has_variants" id="ins_has_variants" value="1" onchange="insToggleG3(this.checked)">
            <strong>Este producto tiene variantes</strong>
            <span style="font-size:.78em;color:#888">(tallas, colores, materiales…)</span>
        </label>
        <div id="ins-g3-section" style="display:none">
            <div class="g3-box" style="margin-bottom:12px">
                <div style="font-weight:700;font-size:.85em;color:#337ab7;margin-bottom:8px"><i class="icon-tags"></i> Paso 1 — Define los atributos</div>
                <div id="ins-attr-rows">
                    <div class="g3-attr-row" id="ins-atrow-0">
                        <input type="text" name="attr_name[]" placeholder="Atributo (ej: Talla)" style="max-width:160px">
                        <span style="color:#aaa;font-size:.8em">Valores:</span>
                        <input type="text" id="ins-attr-vals-0" placeholder="ej: S, M, L, XL" style="max-width:260px">
                        <button type="button" class="btn btn-mini btn-danger" onclick="insRemoveAttrRow(0)">✕</button>
                    </div>
                </div>
                <div style="margin-top:8px;display:flex;gap:8px">
                    <button type="button" class="btn btn-mini btn-default" onclick="insAddAttrRow()"><i class="icon-plus"></i> Agregar atributo</button>
                    <button type="button" class="btn btn-mini btn-info" onclick="insGenerateMatrix()"><i class="icon-magic"></i> Generar matriz de SKUs</button>
                </div>
            </div>
            <div class="g3-box">
                <div style="font-weight:700;font-size:.85em;color:#337ab7;margin-bottom:8px"><i class="icon-list"></i> Paso 2 — Configura cada SKU</div>
                <div style="overflow-x:auto">
                <table class="sku-table" id="ins-sku-table">
                    <thead id="ins-sku-thead"><tr><th>SKU</th><th>Precio</th><th>Stock</th><th>Imagen</th><th></th></tr></thead>
                    <tbody id="ins-sku-tbody"></tbody>
                </table>
                </div>
            </div>
        </div>
        </div>
        </div>
        <script>
        var _insG3AttrCount = 1;
        function insToggleG3(on){ document.getElementById('ins-g3-section').style.display=on?'':'none'; }
        function insAddAttrRow(){
            var idx=_insG3AttrCount++;
            document.getElementById('ins-attr-rows').insertAdjacentHTML('beforeend',
                '<div class="g3-attr-row" id="ins-atrow-'+idx+'">'
                +'<input type="text" name="attr_name[]" placeholder="Atributo" style="max-width:160px">'
                +'<span style="color:#aaa;font-size:.8em">Valores:</span>'
                +'<input type="text" id="ins-attr-vals-'+idx+'" placeholder="ej: Rojo, Azul" style="max-width:260px">'
                +'<button type="button" class="btn btn-mini btn-danger" onclick="insRemoveAttrRow('+idx+')">✕</button>'
                +'</div>');
        }
        function insRemoveAttrRow(idx){ var el=document.getElementById('ins-atrow-'+idx); if(el) el.remove(); }
        function insGenerateMatrix(){
            var attrs=[];
            document.querySelectorAll('#ins-attr-rows .g3-attr-row').forEach(function(row,i){
                var nm=row.querySelector('input[name="attr_name[]"]').value.trim();
                var vi=row.querySelector('input[id^="ins-attr-vals-"]');
                var vs=vi?vi.value.split(',').map(function(v){return v.trim();}).filter(Boolean):[];
                if(nm&&vs.length) attrs.push({name:nm,vals:vs});
            });
            if(!attrs.length){alert('Define al menos un atributo con valores.');return;}
            var combos=[[]];
            attrs.forEach(function(a){var next=[];combos.forEach(function(c){a.vals.forEach(function(v){next.push(c.concat([{n:a.name,v:v}]));});});combos=next;});
            var th='<tr><th>SKU</th>';
            attrs.forEach(function(a){th+='<th>'+escH(a.name)+'</th>';});
            th+='<th>Precio</th><th>Stock</th><th>Imagen</th><th></th></tr>';
            document.getElementById('ins-sku-thead').innerHTML=th;
            var rows='';
            combos.forEach(function(combo,ci){
                var autoSku='NUEVO-'+combo.map(function(c){return c.v.toUpperCase().replace(/\s+/g,'');}).join('-');
                rows+='<tr class="sku-row"><input type="hidden" name="var_id[]" value="">'
                    +'<td><input type="text" name="var_sku[]" value="'+escH(autoSku)+'" placeholder="AUTO"></td>';
                combo.forEach(function(c){var fk='vattr_'+c.n.toLowerCase().replace(/[^a-z0-9]/g,'_');
                    rows+='<td><input type="text" name="'+fk+'[]" value="'+escH(c.v)+'"></td>';});
                rows+='<td><input type="number" name="var_price[]" step="0.01" value="0"></td>'
                    +'<td><input type="number" name="var_stock[]" min="0" value="0"></td>'
                    +'<td><input type="file" name="var_image[new_'+ci+']" accept="image/*" style="font-size:.72em"></td>'
                    +'<td><button type="button" class="btn btn-mini btn-danger" onclick="this.closest(\'tr\').remove()">✕</button></td>'
                    +'</tr>';
            });
            document.getElementById('ins-sku-tbody').innerHTML=rows;
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
