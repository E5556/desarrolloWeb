
<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin']))
	{	
header('location:index.php');
}
else{
	$pid=intval($_GET['id']);// product id
if(isset($_POST['submit']))
{
	$category=$_POST['category'];
	$subcat=$_POST['subcategory'];
	$productname=$_POST['productName'];
	$productcompany=$_POST['productCompany'];
	$productpurchaseprice = floatval($_POST['productpurchaseprice'] ?? 0);
	$productsaleprice     = floatval($_POST['productsaleprice']     ?? 0);
	$productdiscountprice = floatval($_POST['productdiscountprice'] ?? 0);
	$hasdiscount          = isset($_POST['hasDiscount']) ? 1 : 0;
	$productdescription=$_POST['productDescription'];
	$productscharge=$_POST['productShippingcharge'];
	$productavailability=$_POST['productAvailability'];
	$stock_qty = isset($_POST['stock_qty']) && $_POST['stock_qty'] !== '' ? intval($_POST['stock_qty']) : null;

	// productPriceBeforeDiscount = precio de venta (siempre visible)
	// productPrice               = precio con descuento (visible solo si hasDiscount=1)
	mysqli_query($con, "ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_qty INT DEFAULT NULL");
	mysqli_query($con, "ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL");
	$_sq_val = is_null($stock_qty) ? 'NULL' : intval($stock_qty);
	$_sup_id = isset($_POST['supplier_id']) && intval($_POST['supplier_id']) > 0 ? intval($_POST['supplier_id']) : 'NULL';
// FF3 — Auditoría de precios: guardar precio anterior antes de actualizar
mysqli_query($con,"CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    old_price DECIMAL(12,2) DEFAULT 0,
    new_price DECIMAL(12,2) DEFAULT 0,
    old_sale_price DECIMAL(12,2) DEFAULT 0,
    new_sale_price DECIMAL(12,2) DEFAULT 0,
    changed_by VARCHAR(80) DEFAULT '',
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$_prev_q = mysqli_query($con, "SELECT stock_qty, productAvailability, productPrice, productPriceBeforeDiscount FROM products WHERE id='$pid' LIMIT 1");
$_prev   = $_prev_q ? mysqli_fetch_assoc($_prev_q) : [];
$_was_out = (!isset($_prev['stock_qty']) || intval($_prev['stock_qty']) <= 0) || $_prev['productAvailability'] === 'Out of Stock';

$sql=mysqli_query($con,"UPDATE products SET category='$category', subCategory='$subcat', productName='$productname', productCompany='$productcompany', productPurchasePrice='$productpurchaseprice', productPriceBeforeDiscount='$productsaleprice', productPrice='$productdiscountprice', hasDiscount='$hasdiscount', productDescription='$productdescription', shippingCharge='$productscharge', productAvailability='$productavailability', stock_qty=$_sq_val, supplier_id=$_sup_id WHERE id='$pid'");

// Registrar cambio de precio si cambió
$old_price = floatval($_prev['productPrice'] ?? 0);
$old_sale  = floatval($_prev['productPriceBeforeDiscount'] ?? 0);
if ($sql && ($old_price !== $productdiscountprice || $old_sale !== $productsaleprice)) {
    $adm_user = mysqli_real_escape_string($con, $_SESSION['alogin'] ?? '');
    mysqli_query($con,"INSERT INTO price_history(product_id,old_price,new_price,old_sale_price,new_sale_price,changed_by)
        VALUES($pid,$old_price,$productdiscountprice,$old_sale,$productsaleprice,'$adm_user')");
}

// BB2: Trigger restock notifications if product came back in stock
if ($sql && $_was_out && !is_null($stock_qty) && $stock_qty > 0 && $productavailability === 'In Stock') {
    include_once('../includes/restock.php');
    ps_restock_notify($con, intval($pid), $productname);
}

// ── Eliminar imágenes marcadas ────────────────────────────────────────────
$_del_cols  = $_POST['del_img_col']  ?? [];
$_del_names = $_POST['del_img_name'] ?? [];
$_del_eids  = $_POST['del_img_eid']  ?? [];
$_img_dir   = "productimages/$pid";
$_col_map   = ['productImage1'=>'productImage1','productImage2'=>'productImage2','productImage3'=>'productImage3'];
foreach($_del_cols as $_di => $_col) {
    $_fname = $_del_names[$_di] ?? '';
    if ($_col === 'extra') {
        $eid = intval($_del_eids[$_di]);
        mysqli_query($con, "DELETE FROM product_images WHERE id=$eid AND productId=$pid");
    } elseif (isset($_col_map[$_col])) {
        $safe_col = $_col_map[$_col];
        mysqli_query($con, "UPDATE products SET $safe_col='' WHERE id=$pid");
    }
    if ($_fname && file_exists("$_img_dir/$_fname")) @unlink("$_img_dir/$_fname");
}

// ── Subir nuevas imágenes ─────────────────────────────────────────────────
$allowed_exts  = ['jpg','jpeg','png','gif','webp'];
$allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
if (!is_dir($_img_dir)) mkdir($_img_dir, 0755, true);

// Contar imágenes actuales tras eliminaciones
$_cur_q = mysqli_query($con, "SELECT productImage1,productImage2,productImage3 FROM products WHERE id=$pid");
$_cur   = mysqli_fetch_assoc($_cur_q);
$_saved_cols = ['productImage1','productImage2','productImage3'];
$_next_col = null;
foreach ($_saved_cols as $_sc) { if (empty($_cur[$_sc])) { $_next_col = $_sc; break; } }

if (!empty($_FILES['new_productimages']['name'])) {
    foreach($_FILES['new_productimages']['name'] as $_ni => $_fname) {
        if (empty($_fname) || $_FILES['new_productimages']['error'][$_ni] !== UPLOAD_ERR_OK) continue;
        $ext  = strtolower(pathinfo($_fname, PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['new_productimages']['tmp_name'][$_ni]);
        finfo_close($finfo);
        if (!in_array($ext,$allowed_exts)||!in_array($mime,$allowed_mimes)) continue;
        $sname = preg_replace('/[^a-zA-Z0-9_-]/','_',pathinfo($_fname,PATHINFO_FILENAME)).'.'.$ext;
        if (!move_uploaded_file($_FILES['new_productimages']['tmp_name'][$_ni],"$_img_dir/$sname")) continue;
        // Save to col or product_images
        if ($_next_col) {
            $safe = $_next_col;
            mysqli_query($con,"UPDATE products SET $safe='$sname' WHERE id=$pid");
            $_cur[$_next_col] = $sname;
            $_next_col = null;
            foreach($_saved_cols as $_sc){ if(empty($_cur[$_sc])){$_next_col=$_sc;break;} }
        } else {
            $order = intval(mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) n FROM product_images WHERE productId=$pid"))['n']);
            mysqli_query($con,"INSERT INTO product_images(productId,imageName,sortOrder) VALUES($pid,'$sname',$order)");
        }
    }
}

// ── G3: Guardar variantes (modelo SKU padre/hijo) ────────────────────────
// DDL: asegurar columnas y tablas nuevas
mysqli_report(MYSQLI_REPORT_OFF);
$_need = ['sku'=>'VARCHAR(80) NULL DEFAULT NULL','barcode'=>'VARCHAR(80) NULL DEFAULT NULL','image'=>'VARCHAR(255) NULL DEFAULT NULL','is_active'=>'TINYINT(1) NOT NULL DEFAULT 1'];
foreach ($_need as $_cn => $_cdef) {
    $_cq = mysqli_query($con,"SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='product_variants' AND COLUMN_NAME='$_cn'");
    if (!$_cq || !mysqli_fetch_row($_cq)) mysqli_query($con,"ALTER TABLE product_variants ADD COLUMN $_cn $_cdef");
}
foreach (['stock_movements','order_items','purchase_order_items'] as $_t) {
    $_cq = mysqli_query($con,"SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$_t' AND COLUMN_NAME='variant_id'");
    if (!$_cq || !mysqli_fetch_row($_cq)) mysqli_query($con,"ALTER TABLE $_t ADD COLUMN variant_id INT NULL DEFAULT NULL");
}
mysqli_query($con,"CREATE TABLE IF NOT EXISTS product_attributes (
  id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, attr_name VARCHAR(80) NOT NULL, sort_order TINYINT NOT NULL DEFAULT 0,
  INDEX idx_pa_product(product_id), UNIQUE KEY uq_pa_pid_attr(product_id,attr_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($con,"CREATE TABLE IF NOT EXISTS variant_attribute_values (
  id INT AUTO_INCREMENT PRIMARY KEY, variant_id INT NOT NULL, attr_name VARCHAR(80) NOT NULL, attr_value VARCHAR(120) NOT NULL,
  INDEX idx_vav_variant(variant_id), INDEX idx_vav_attr(attr_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!empty($_POST['has_variants'])) {
    // Guardar atributos del producto
    $attr_names = array_map('trim', $_POST['attr_name'] ?? []);
    mysqli_query($con,"DELETE FROM product_attributes WHERE product_id=$pid");
    foreach ($attr_names as $_ao => $_an) {
        if ($_an === '') continue;
        $esa = mysqli_real_escape_string($con, $_an);
        mysqli_query($con,"INSERT INTO product_attributes(product_id,attr_name,sort_order) VALUES($pid,'$esa',$_ao)");
    }

    // IDs de variantes que llegaron en el POST (para marcar inactivas las que falten)
    $posted_var_ids = array_map('intval', $_POST['var_id'] ?? []);
    $existing_ids_q = mysqli_query($con,"SELECT id FROM product_variants WHERE product_id=$pid");
    while ($_eid = mysqli_fetch_assoc($existing_ids_q)) {
        if (!in_array(intval($_eid['id']), array_filter($posted_var_ids))) {
            mysqli_query($con,"UPDATE product_variants SET is_active=0 WHERE id=".intval($_eid['id']));
        }
    }

    // Preparar stmt de update e insert
    $v_upd = mysqli_prepare($con,"UPDATE product_variants SET sku=?,price_extra=?,stock_qty=?,is_active=1 WHERE id=? AND product_id=?");
    $v_ins = mysqli_prepare($con,"INSERT INTO product_variants(product_id,variant_name,variant_value,sku,stock_qty,price_extra,is_active) VALUES(?,?,?,?,?,?,1)");
    $v_img_upd = "UPDATE product_variants SET image=? WHERE id=?";

    $var_ids_saved = [];
    foreach (($_POST['var_id'] ?? []) as $vi => $vid) {
        $vid   = intval($vid);
        $vsku  = trim(substr($_POST['var_sku'][$vi]  ?? '', 0, 80));
        $vprice= floatval($_POST['var_price'][$vi] ?? 0);
        $vstk  = (isset($_POST['var_stock'][$vi]) && $_POST['var_stock'][$vi] !== '') ? intval($_POST['var_stock'][$vi]) : 0;
        // combinación de atributos para variant_name/value (compatibilidad)
        $combo_keys = []; $combo_vals = [];
        foreach ($attr_names as $_an) {
            $fk = 'vattr_'.preg_replace('/[^a-z0-9]/i','_',strtolower($_an));
            $combo_keys[] = $_an;
            $combo_vals[] = trim(($_POST[$fk][$vi] ?? ''));
        }
        $vname = implode('/', $combo_keys);
        $vval  = implode('/', $combo_vals);
        if ($vval === '' || str_replace('/','',trim($vval)) === '') continue;

        if ($vid > 0) {
            mysqli_stmt_bind_param($v_upd,'sdiid',$vsku,$vprice,$vstk,$vid,$pid);
            mysqli_stmt_execute($v_upd);
            $saved_id = $vid;
        } else {
            mysqli_stmt_bind_param($v_ins,'isssiid',$pid,$vname,$vval,$vsku,$vstk,$vprice);
            mysqli_stmt_execute($v_ins);
            $saved_id = mysqli_insert_id($con);
        }
        $var_ids_saved[$vi] = $saved_id;

        // Imagen de variante
        if (isset($_FILES['var_image']['name'][$vi]) && $_FILES['var_image']['error'][$vi] === UPLOAD_ERR_OK) {
            $_vext = strtolower(pathinfo($_FILES['var_image']['name'][$vi], PATHINFO_EXTENSION));
            if (in_array($_vext,['jpg','jpeg','png','gif','webp'])) {
                $_vfname = "variant_{$saved_id}.{$_vext}";
                if (move_uploaded_file($_FILES['var_image']['tmp_name'][$vi], "productimages/$pid/$_vfname")) {
                    $stmt_vi = mysqli_prepare($con, $v_img_upd);
                    mysqli_stmt_bind_param($stmt_vi,'si',$_vfname,$saved_id);
                    mysqli_stmt_execute($stmt_vi);
                    mysqli_stmt_close($stmt_vi);
                }
            }
        }

        // variant_attribute_values
        mysqli_query($con,"DELETE FROM variant_attribute_values WHERE variant_id=$saved_id");
        foreach ($attr_names as $_an) {
            $fk = 'vattr_'.preg_replace('/[^a-z0-9]/i','_',strtolower($_an));
            $_av = trim(($_POST[$fk][$vi] ?? ''));
            if ($_an === '' || $_av === '') continue;
            $esa = mysqli_real_escape_string($con,$_an);
            $esv = mysqli_real_escape_string($con,$_av);
            mysqli_query($con,"INSERT INTO variant_attribute_values(variant_id,attr_name,attr_value) VALUES($saved_id,'$esa','$esv')");
        }
    }
    if (isset($v_upd)) mysqli_stmt_close($v_upd);
    if (isset($v_ins)) mysqli_stmt_close($v_ins);

    // Recalcular stock total del producto padre
    $stot = mysqli_fetch_assoc(mysqli_query($con,"SELECT COALESCE(SUM(stock_qty),0) t FROM product_variants WHERE product_id=$pid AND is_active=1"));
    mysqli_query($con,"UPDATE products SET stock_qty=".intval($stot['t'])." WHERE id=$pid");

} elseif (isset($_POST['has_variants'])) {
    // checkbox presente pero desmarcado = sin variantes, no tocar nada
}

$_SESSION['msg'] = "Producto actualizado correctamente.";
include_once('../includes/admin-log.php');
admin_log($con, 'edit_product', "Producto ID $pid actualizado");

}


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin| Insertar Producto</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
<script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script>

   <script>
function getSubcat(val) {
	$.ajax({
	type: "POST",
	url: "get_subcat.php",
	data:'cat_id='+val,
	success: function(data){
		$("#subcategory").html(data);
	}
	});
}
function toggleDiscount(cb) {
    var field = document.getElementById('discountPriceField');
    var hint  = document.getElementById('discount-hint');
    if (cb.checked) {
        field.style.opacity = '1';
        field.style.pointerEvents = 'auto';
        field.focus();
        hint.innerHTML = 'El cliente verá el precio con descuento y el precio de venta tachado';
    } else {
        field.style.opacity = '.4';
        field.style.pointerEvents = 'none';
        hint.innerHTML = 'Si se activa, se muestra el precio con descuento y el precio de venta tachado';
    }
}
</script>	


</head>
<body>
<?php include('include/header.php');?>

	<div class="wrapper">
		<div class="container">
			<div class="row">
<?php include('include/sidebar.php');?>				
			<div class="span9">
					<div class="content">

						<div class="module">
							<div class="module-head">
								<h3>Insertar Productos</h3>
							</div>
							<div class="module-body">

									<?php if(isset($_POST['submit']))
{?>
									<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">×</button>
									<strong>Bien hecho!</strong>	<?php echo htmlentities($_SESSION['msg']);?><?php echo htmlentities($_SESSION['msg']="");?>
									</div>
<?php } ?>


									<?php if(isset($_GET['del']))
{?>
									<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">×</button>
									<strong>Oh no!</strong> 	<?php echo htmlentities($_SESSION['delmsg']);?><?php echo htmlentities($_SESSION['delmsg']="");?>
									</div>
<?php } ?>

									<br />

			<form class="form-horizontal row-fluid" name="insertproduct" method="post" enctype="multipart/form-data">

<?php 

$query=mysqli_query($con,"select products.*,category.categoryName as catname,category.id as cid,subcategory.subcategory as subcatname,subcategory.id as subcatid from products join category on category.id=products.category join subcategory on subcategory.id=products.subCategory where products.id='$pid'");
$cnt=1;
while($row=mysqli_fetch_array($query))
{
  


?>


<div class="control-group">
<label class="control-label" for="basicinput">Categoría</label>
<div class="controls">
<select name="category" class="span8 tip" onChange="getSubcat(this.value);"  required>
<option value="<?php echo htmlentities($row['cid']);?>"><?php echo htmlentities($row['catname']);?></option> 
<?php $query=mysqli_query($con,"select * from category");
while($rw=mysqli_fetch_array($query))
{
	if($row['catname']==$rw['categoryName'])
	{
		continue;
	}
	else{
	?>

<option value="<?php echo $rw['id'];?>"><?php echo $rw['categoryName'];?></option>
<?php }} ?>
</select>
</div>
</div>

									
<div class="control-group">
<label class="control-label" for="basicinput">SubCategoría</label>
<div class="controls">

<select   name="subcategory"  id="subcategory" class="span8 tip" required>
<option value="<?php echo htmlentities($row['subcatid']);?>"><?php echo htmlentities($row['subcatname']);?></option>
</select>
</div>
</div>


<div class="control-group">
<label class="control-label" for="basicinput">Producto</label>
<div class="controls">
<input type="text"    name="productName"  placeholder="Enter Product Name" value="<?php echo htmlentities($row['productName']);?>" class="span8 tip" >
</div>
</div>

<div class="control-group">
<label class="control-label" for="basicinput">Fabricante</label>
<div class="controls">
<input type="text"    name="productCompany"  placeholder="Enter Product Comapny Name" value="<?php echo htmlentities($row['productCompany']);?>" class="span8 tip" required>
</div>
</div>
<!-- ── PRECIOS ── -->
<div class="control-group" style="background:#f9f9f9;border:1px solid #e0e0e0;border-radius:5px;padding:14px 10px;margin-bottom:10px;">
    <div style="font-weight:700;color:#555;margin-bottom:10px;font-size:.9em;border-bottom:1px solid #ddd;padding-bottom:6px;">
        <i class="icon-tag"></i> Configuración de precios
    </div>

    <div class="control-group" style="margin-bottom:8px">
        <label class="control-label" style="color:#888">
            <i class="icon-lock"></i> Precio de compra <small>(solo admin)</small>
        </label>
        <div class="controls">
            <input type="number" step="0.01" name="productpurchaseprice"
                   placeholder="Lo que pagaste al proveedor"
                   value="<?php echo htmlentities($row['productPurchasePrice'] ?? 0); ?>"
                   class="span6">
            <span class="help-inline" style="color:#aaa">No visible al cliente</span>
        </div>
    </div>

    <div class="control-group" style="margin-bottom:8px">
        <label class="control-label">
            <i class="icon-usd"></i> Precio de venta <span style="color:#c00">*</span>
        </label>
        <div class="controls">
            <input type="number" step="0.01" name="productsaleprice"
                   placeholder="Precio que ve el cliente"
                   value="<?php echo htmlentities($row['productPriceBeforeDiscount']); ?>"
                   class="span6" required>
            <span class="help-inline">Siempre visible al cliente</span>
        </div>
    </div>

    <div class="control-group" style="margin-bottom:8px">
        <label class="control-label">
            <i class="icon-ticket"></i> Precio con descuento
        </label>
        <div class="controls" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:6px;font-weight:normal;margin:0">
                <input type="checkbox" name="hasDiscount" id="hasDiscount" value="1"
                       <?php echo (!empty($row['hasDiscount']) ? 'checked' : ''); ?>
                       onchange="toggleDiscount(this)">
                Activar descuento
            </label>
            <input type="number" step="0.01" name="productdiscountprice" id="discountPriceField"
                   placeholder="Precio con descuento"
                   value="<?php echo htmlentities($row['productPrice']); ?>"
                   class="span4"
                   style="<?php echo empty($row['hasDiscount']) ? 'opacity:.4;pointer-events:none' : ''; ?>">
        </div>
        <div class="controls" style="margin-top:4px">
            <span class="help-inline" id="discount-hint" style="color:#888;font-size:.8em">
                <?php if(!empty($row['hasDiscount'])): ?>
                El cliente verá: <strong><?php echo htmlentities($row['productPrice']); ?></strong>
                y <del><?php echo htmlentities($row['productPriceBeforeDiscount']); ?></del> tachado
                <?php else: ?>
                Si se activa, se muestra el precio con descuento y el precio de venta tachado
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<div class="control-group">
<label class="control-label" for="basicinput">Descripción</label>
<div class="controls">
<textarea  name="productDescription"  placeholder="Enter Product Description" rows="6" class="span8 tip">
<?php echo htmlentities($row['productDescription']);?>
</textarea>  
</div>
</div>

<div class="control-group">
<label class="control-label" for="basicinput">Cargo por envío del producto</label>
<div class="controls">
<input type="text"    name="productShippingcharge"  placeholder="Enter Product Shipping Charge" value="<?php echo htmlentities($row['shippingCharge']);?>" class="span8 tip" required>
</div>
</div>

<div class="control-group">
<label class="control-label" for="basicinput">Disponibilidad</label>
<div class="controls">
<select name="productAvailability" id="productAvailability" class="span8 tip" required>
<?php
$_av = $row['productAvailability'] ?? '';
$_av_opts = ['In Stock'=>'En Stock','Out of Stock'=>'Sin Stock','On Order'=>'Bajo Pedido'];
foreach($_av_opts as $_val=>$_label): ?>
<option value="<?php echo $_val; ?>"<?php echo ($_av===$_val)?' selected':''; ?>><?php echo $_label; ?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="control-group">
<label class="control-label">Stock (unidades)</label>
<div class="controls">
<?php
$_stk_val  = isset($row['stock_qty']) && $row['stock_qty'] !== null ? intval($row['stock_qty']) : null;
$_is_on_order = ($row['productAvailability'] ?? '') === 'On Order';
?>
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <?php if ($_is_on_order): ?>
        <span style="background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:5px 12px;border-radius:5px;font-size:.85em">
            <i class="icon-warning-sign"></i> Bajo Pedido — sin control de stock
        </span>
        <span style="font-size:.78em;color:#aaa">Los productos Bajo Pedido no manejan inventario físico</span>
    <?php else: ?>
        <span style="font-size:1.4em;font-weight:700;color:<?php echo ($_stk_val === null ? '#aaa' : ($_stk_val > 5 ? '#27ae60' : ($_stk_val > 0 ? '#e67e22' : '#e8233a'))); ?>">
            <?php echo $_stk_val !== null ? $_stk_val . ' uds.' : '— sin control'; ?>
        </span>
        <a href="inventory-adjust.php?load_pid=<?php echo $pid; ?>" class="btn btn-sm btn-default" style="padding:5px 12px;font-size:.82em">
            <i class="icon-inbox"></i> Ajustar stock
        </a>
        <span style="font-size:.78em;color:#aaa">Se descuenta automáticamente con cada compra</span>
    <?php endif; ?>
</div>
<input type="hidden" name="stock_qty" value="<?php echo $_stk_val !== null ? $_stk_val : ''; ?>">
</div>
</div>

<div class="control-group">
<label class="control-label">Proveedor</label>
<div class="controls">
<?php
$_sup_list = mysqli_query($con,"SELECT id,name FROM suppliers WHERE active=1 ORDER BY name");
$_cur_sup = isset($row['supplier_id']) ? intval($row['supplier_id']) : 0;
?>
<select name="supplier_id" class="span6">
    <option value="0">— Sin proveedor —</option>
    <?php while ($_sl = mysqli_fetch_assoc($_sup_list)): ?>
    <option value="<?php echo $_sl['id']; ?>" <?php echo $_cur_sup==$_sl['id']?'selected':''; ?>><?php echo htmlspecialchars($_sl['name']); ?></option>
    <?php endwhile; ?>
</select>
<span class="help-inline"><a href="suppliers.php" target="_blank">Gestionar proveedores</a></span>
</div>
</div>



<?php
// ── G3: Cargar variantes y atributos existentes ──────────────────────────
$_variants = [];
$_vq = mysqli_query($con, "SELECT * FROM product_variants WHERE product_id=$pid AND is_active=1 ORDER BY id");
while ($_vr = mysqli_fetch_assoc($_vq)) $_variants[] = $_vr;

$_attr_defs = []; // [attr_name => [val1, val2, ...]]
$_pa_q = mysqli_query($con,"SELECT attr_name FROM product_attributes WHERE product_id=$pid ORDER BY sort_order");
if ($_pa_q) while ($_pa = mysqli_fetch_assoc($_pa_q)) $_attr_defs[$_pa['attr_name']] = [];

// Cargar valores de cada variante indexados por variant_id
$_vav_map = []; // [variant_id][attr_name] = attr_value
if (!empty($_variants)) {
    $vids_str = implode(',', array_column($_variants,'id'));
    $_vav_q = mysqli_query($con,"SELECT * FROM variant_attribute_values WHERE variant_id IN ($vids_str)");
    if ($_vav_q) while ($_vav = mysqli_fetch_assoc($_vav_q)) $_vav_map[$_vav['variant_id']][$_vav['attr_name']] = $_vav['attr_value'];
}
$_has_variants = !empty($_variants) || !empty($_attr_defs);
$_attr_names   = array_keys($_attr_defs);
?>

<!-- ── G3: VARIANTES (SKU Padre/Hijo) ── -->
<style>
.g3-box { background:#f8fbff; border:1px solid #c5d9ed; border-radius:8px; padding:16px; margin-bottom:14px; }
.g3-attr-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
.g3-attr-row input { flex:1; padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:.85em; }
.sku-table { width:100%; border-collapse:collapse; font-size:.82em; }
.sku-table th { background:#e8f0fe; padding:6px 8px; text-align:left; border:1px solid #d0d9e8; white-space:nowrap; }
.sku-table td { padding:5px 6px; border:1px solid #e8e8e8; vertical-align:middle; }
.sku-table tr:nth-child(even) td { background:#fafbff; }
.sku-table input[type=text], .sku-table input[type=number] { width:100%; padding:4px 6px; border:1px solid #ccc; border-radius:3px; font-size:.82em; box-sizing:border-box; }
.sku-table .td-img input[type=file] { font-size:.75em; }
.sku-inactive { opacity:.5; }
</style>

<div class="control-group">
<label class="control-label">Variantes / SKUs</label>
<div class="controls">

<label style="font-weight:normal;margin-bottom:10px;display:flex;align-items:center;gap:8px;cursor:pointer">
    <input type="checkbox" name="has_variants" id="has_variants_cb" value="1" <?php echo $_has_variants?'checked':''; ?> onchange="toggleG3(this.checked)">
    <strong>Este producto tiene variantes</strong>
    <span style="font-size:.78em;color:#888">(tallas, colores, materiales, etc.)</span>
</label>

<div id="g3-section" style="<?php echo $_has_variants?'':'display:none'; ?>">

    <!-- Definición de atributos -->
    <div class="g3-box" style="margin-bottom:12px">
        <div style="font-weight:700;font-size:.85em;color:#337ab7;margin-bottom:8px"><i class="icon-tags"></i> Paso 1 — Define los atributos</div>
        <div id="attr-rows">
        <?php
        $shown = !empty($_attr_names) ? $_attr_names : [''];
        foreach ($shown as $_i => $_an): ?>
        <div class="g3-attr-row" id="atrow-<?php echo $_i; ?>">
            <input type="text" name="attr_name[]" placeholder="Atributo (ej: Talla)" value="<?php echo htmlspecialchars($_an); ?>" style="max-width:160px">
            <span style="color:#aaa;font-size:.8em">Valores (separados por coma):</span>
            <input type="text" id="attr-vals-<?php echo $_i; ?>" placeholder="ej: S, M, L, XL" style="max-width:260px"
                   value="<?php
                       // Collect unique values for this attr from existing variants
                       $uvals = [];
                       foreach ($_variants as $_vv) { if (isset($_vav_map[$_vv['id']][$_an])) $uvals[] = $_vav_map[$_vv['id']][$_an]; }
                       echo htmlspecialchars(implode(', ', array_unique($uvals)));
                   ?>">
            <button type="button" class="btn btn-mini btn-danger" onclick="removeAttrRow(<?php echo $_i; ?>)" title="Eliminar atributo">✕</button>
        </div>
        <?php endforeach; ?>
        </div>
        <div style="margin-top:8px;display:flex;gap:8px">
            <button type="button" class="btn btn-mini btn-default" onclick="addAttrRow()"><i class="icon-plus"></i> Agregar atributo</button>
            <button type="button" class="btn btn-mini btn-info" onclick="generateMatrix()"><i class="icon-magic"></i> Generar matriz de SKUs</button>
        </div>
    </div>

    <!-- Tabla de SKUs -->
    <div class="g3-box">
        <div style="font-weight:700;font-size:.85em;color:#337ab7;margin-bottom:8px"><i class="icon-list"></i> Paso 2 — Configura cada SKU</div>
        <div style="overflow-x:auto">
        <table class="sku-table" id="sku-table">
        <thead id="sku-thead">
        <tr>
            <th>SKU</th>
            <?php foreach ($_attr_names as $_an): ?>
            <th><?php echo htmlspecialchars($_an); ?></th>
            <?php endforeach; ?>
            <th>Precio</th>
            <th>Stock</th>
            <th>Imagen</th>
            <th>Activo</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="sku-tbody">
        <?php foreach ($_variants as $_vi => $_v):
            $_vattrs = $_vav_map[$_v['id']] ?? [];
            $_vimg = !empty($_v['image']) ? "productimages/$pid/{$_v['image']}" : '';
        ?>
        <tr class="sku-row" data-vid="<?php echo $_v['id']; ?>">
            <input type="hidden" name="var_id[]" value="<?php echo $_v['id']; ?>">
            <td><input type="text" name="var_sku[]" value="<?php echo htmlspecialchars($_v['sku']??''); ?>" placeholder="AUTO"></td>
            <?php foreach ($_attr_names as $_an): ?>
            <td><input type="text" name="vattr_<?php echo preg_replace('/[^a-z0-9]/i','_',strtolower($_an)); ?>[]"
                       value="<?php echo htmlspecialchars($_vattrs[$_an]??''); ?>"></td>
            <?php endforeach; ?>
            <td style="width:80px"><input type="number" name="var_price[]" step="0.01" value="<?php echo floatval($_v['price_extra']); ?>"></td>
            <td style="width:70px"><input type="number" name="var_stock[]" min="0" value="<?php echo intval($_v['stock_qty']); ?>"></td>
            <td class="td-img" style="width:120px">
                <?php if ($_vimg): ?><img src="<?php echo htmlspecialchars($_vimg); ?>" style="height:32px;margin-bottom:3px;display:block;border-radius:3px"><?php endif; ?>
                <input type="file" name="var_image[<?php echo $_vi; ?>]" accept="image/*" style="font-size:.72em">
            </td>
            <td style="text-align:center"><input type="checkbox" name="var_active[<?php echo $_vi; ?>]" value="1" checked></td>
            <td><button type="button" class="btn btn-mini btn-danger" onclick="removeSkuRow(this)" title="Eliminar">✕</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        </div>
        <div style="margin-top:8px;font-size:.75em;color:#aaa">
            El campo "Precio" es el precio de venta de esta variante (0 = usa el precio base del producto). "Stock" es independiente por SKU.
        </div>
    </div>

</div><!-- /g3-section -->
</div>
</div>

<script>
var _g3AttrCount = <?php echo max(1, count($shown ?? [''])); ?>;

function toggleG3(on) {
    document.getElementById('g3-section').style.display = on ? '' : 'none';
}

function addAttrRow() {
    var idx = _g3AttrCount++;
    var html = '<div class="g3-attr-row" id="atrow-'+idx+'">'
        + '<input type="text" name="attr_name[]" placeholder="Atributo (ej: Color)" style="max-width:160px">'
        + '<span style="color:#aaa;font-size:.8em">Valores (separados por coma):</span>'
        + '<input type="text" id="attr-vals-'+idx+'" placeholder="ej: Rojo, Azul, Negro" style="max-width:260px">'
        + '<button type="button" class="btn btn-mini btn-danger" onclick="removeAttrRow('+idx+')" title="Eliminar">✕</button>'
        + '</div>';
    document.getElementById('attr-rows').insertAdjacentHTML('beforeend', html);
}

function removeAttrRow(idx) {
    var el = document.getElementById('atrow-'+idx);
    if (el) el.remove();
}

function generateMatrix() {
    // Leer atributos y valores
    var attrs = [];
    document.querySelectorAll('#attr-rows .g3-attr-row').forEach(function(row, i) {
        var name = row.querySelector('input[name="attr_name[]"]').value.trim();
        var valsEl = row.querySelector('input[id^="attr-vals-"]');
        var vals = valsEl ? valsEl.value.split(',').map(function(v){ return v.trim(); }).filter(Boolean) : [];
        if (name && vals.length) attrs.push({name:name, vals:vals});
    });
    if (!attrs.length) { alert('Define al menos un atributo con valores.'); return; }

    // Calcular producto cartesiano
    var combos = [[]];
    attrs.forEach(function(a) {
        var next = [];
        combos.forEach(function(c) {
            a.vals.forEach(function(v) { next.push(c.concat([{n:a.name, v:v}])); });
        });
        combos = next;
    });

    // Actualizar thead
    var pid = <?php echo $pid; ?>;
    var thead = '<tr><th>SKU</th>';
    attrs.forEach(function(a){ thead += '<th>'+escH(a.name)+'</th>'; });
    thead += '<th>Precio</th><th>Stock</th><th>Imagen</th><th>Activo</th><th></th></tr>';
    document.getElementById('sku-thead').innerHTML = thead;

    // Generar filas (preservar existentes por SKU/combo si coinciden)
    var tbody = document.getElementById('sku-tbody');
    // Recopilar SKUs ya existentes en la tabla para no duplicar
    var existingCombos = {};
    tbody.querySelectorAll('.sku-row').forEach(function(tr){
        var key = [];
        tr.querySelectorAll('td input[name^="vattr_"]').forEach(function(inp){ key.push(inp.value.trim()); });
        existingCombos[key.join('|')] = tr;
    });

    var newRows = '';
    combos.forEach(function(combo, ci) {
        var key = combo.map(function(c){ return c.v; }).join('|');
        if (existingCombos[key]) return; // ya existe
        var autoSku = 'PROD<?php echo $pid; ?>-' + combo.map(function(c){ return c.v.toUpperCase().replace(/\s+/g,''); }).join('-');
        var row = '<tr class="sku-row">'
            + '<input type="hidden" name="var_id[]" value="">'
            + '<td><input type="text" name="var_sku[]" value="'+escH(autoSku)+'" placeholder="AUTO"></td>';
        combo.forEach(function(c){
            var fk = 'vattr_'+c.n.toLowerCase().replace(/[^a-z0-9]/g,'_');
            row += '<td><input type="text" name="'+fk+'[]" value="'+escH(c.v)+'"></td>';
        });
        row += '<td><input type="number" name="var_price[]" step="0.01" value="0"></td>'
             + '<td><input type="number" name="var_stock[]" min="0" value="0"></td>'
             + '<td class="td-img"><input type="file" name="var_image[new_'+ci+']" accept="image/*" style="font-size:.72em"></td>'
             + '<td style="text-align:center"><input type="checkbox" name="var_active[new_'+ci+']" value="1" checked></td>'
             + '<td><button type="button" class="btn btn-mini btn-danger" onclick="removeSkuRow(this)">✕</button></td>'
             + '</tr>';
        newRows += row;
    });
    tbody.insertAdjacentHTML('beforeend', newRows);
    if (newRows) { alert(combos.length + ' combinaciones generadas. Revisa la tabla y ajusta stock/precio.'); }
    else { alert('Todas las combinaciones ya existen en la tabla.'); }
}

function removeSkuRow(btn) { btn.closest('tr').remove(); }
function escH(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>

<?php
// Recopilar todas las imágenes actuales (img1/2/3 + product_images table)
$_existing_imgs = [];
if (!empty($row['productImage1'])) $_existing_imgs[] = ['src'=>$row['productImage1'], 'col'=>'productImage1'];
if (!empty($row['productImage2'])) $_existing_imgs[] = ['src'=>$row['productImage2'], 'col'=>'productImage2'];
if (!empty($row['productImage3'])) $_existing_imgs[] = ['src'=>$row['productImage3'], 'col'=>'productImage3'];
$_extra = mysqli_query($con, "SELECT id, imageName FROM product_images WHERE productId=$pid ORDER BY sortOrder");
while ($_ei = mysqli_fetch_assoc($_extra)) {
    $_existing_imgs[] = ['src'=>$_ei['imageName'], 'col'=>'extra', 'extra_id'=>$_ei['id']];
}
$_MAX = 15;
?>

<!-- ── IMÁGENES ── -->
<div class="control-group">
<label class="control-label">Imágenes del producto</label>
<div class="controls">
<style>
.img-grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:10px; }
.img-card { position:relative; width:110px; border:1px solid #ddd; border-radius:6px; overflow:hidden; background:#f9f9f9; }
.img-card img { width:110px; height:110px; object-fit:cover; display:block; }
.img-card .img-label { font-size:10px; color:#888; text-align:center; padding:3px 0; }
.img-card .btn-del-img { position:absolute; top:4px; right:4px; background:rgba(220,53,69,.85);
    color:#fff; border:none; border-radius:50%; width:22px; height:22px; cursor:pointer;
    font-size:13px; line-height:22px; text-align:center; padding:0; }
.img-card .btn-del-img:hover { background:#c82333; }
.img-card.img-broken img { opacity:.3; }
.img-new-slot { display:flex; align-items:center; gap:8px; margin-bottom:7px; }
.img-new-slot .img-num { font-size:.8em; color:#888; min-width:20px; }
.img-new-slot input[type=file] { flex:1; }
.img-new-slot .btn-remove { padding:2px 7px; font-size:.8em; }
.img-preview-new { width:46px; height:46px; object-fit:cover; border-radius:3px;
    border:1px solid #ddd; display:none; }
</style>

<!-- Imágenes existentes -->
<div class="img-grid" id="existing-imgs">
<?php foreach($_existing_imgs as $_i => $_img): ?>
<div class="img-card" id="imgcard-<?php echo $_i; ?>">
    <img src="productimages/<?php echo $pid; ?>/<?php echo htmlspecialchars($_img['src']); ?>"
         onerror="this.parentElement.classList.add('img-broken')">
    <div class="img-label"><?php echo $_i+1; ?></div>
    <button type="button" class="btn-del-img" title="Eliminar imagen"
        onclick="deleteImg(<?php echo $_i; ?>,'<?php echo htmlspecialchars($_img['col']); ?>',<?php echo intval($_img['extra_id'] ?? 0); ?>,'<?php echo htmlspecialchars($_img['src']); ?>')">
        &times;
    </button>
</div>
<?php endforeach; ?>
</div>

<!-- Inputs para imágenes eliminadas -->
<div id="delete-inputs"></div>

<!-- Añadir nuevas imágenes -->
<div id="img-counter-edit" style="font-size:.8em;color:#888;margin-bottom:6px">
    <?php echo count($_existing_imgs); ?> imagen(es) actuales · máx. <?php echo $_MAX; ?>
</div>
<div id="new-img-slots"></div>
<button type="button" id="btn-add-img-edit" class="btn btn-mini btn-info"
    <?php echo count($_existing_imgs) >= $_MAX ? 'disabled' : ''; ?>>
    <i class="icon-plus"></i> Añadir imagen
</button>
<span style="font-size:.78em;color:#aaa;margin-left:8px">JPG, PNG, GIF, WEBP</span>

</div><!-- /.controls -->
</div><!-- /.control-group -->
<?php } ?>
	<div class="control-group">
											<div class="controls">
												<button type="submit" name="submit" class="btn">Actualizar</button>
											</div>
										</div>
									</form>
							</div>
						</div>


	
						
						
					</div><!--/.content-->
				</div><!--/.span9-->
			</div>
		</div><!--/.container-->
	</div><!--/.wrapper-->

<?php include('include/footer.php');?>

	<script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
	<script src="scripts/flot/jquery.flot.js" type="text/javascript"></script>
	<script src="scripts/datatables/jquery.dataTables.js"></script>
	<script>
		$(document).ready(function() {
			$('.datatable-1').dataTable();
		} );
	</script>
<script>
var MAX_IMGS  = <?php echo $_MAX ?? 15; ?>;
var existCount = <?php echo count($_existing_imgs ?? []); ?>;
var newSlots  = 0;
var deletedCount = 0;

function totalImgs() { return existCount - deletedCount + newSlots; }

function updateCounter() {
    var total = totalImgs();
    var el = document.getElementById('img-counter-edit');
    if (el) el.textContent = total + ' imagen(es) · máx. ' + MAX_IMGS;
    var btn = document.getElementById('btn-add-img-edit');
    if (btn) btn.disabled = total >= MAX_IMGS;
}

function deleteImg(idx, col, eid, fname) {
    var card = document.getElementById('imgcard-' + idx);
    if (card) card.style.opacity = '.3';
    if (card) card.querySelector('.btn-del-img').disabled = true;

    var div = document.getElementById('delete-inputs');
    div.innerHTML +=
        '<input type="hidden" name="del_img_col[]"  value="' + col   + '">' +
        '<input type="hidden" name="del_img_name[]" value="' + fname + '">' +
        '<input type="hidden" name="del_img_eid[]"  value="' + eid   + '">';
    deletedCount++;
    updateCounter();
}

function previewNewImg(input, num) {
    var p = document.getElementById('np-' + num);
    if (!p || !input.files || !input.files[0]) return;
    var r = new FileReader();
    r.onload = function(e){ p.src = e.target.result; p.style.display='inline-block'; };
    r.readAsDataURL(input.files[0]);
}

function removeNewSlot(num) {
    var s = document.getElementById('nslot-' + num);
    if (s) s.remove();
    newSlots--;
    updateCounter();
}

document.getElementById('btn-add-img-edit').addEventListener('click', function(){
    if (totalImgs() >= MAX_IMGS) return;
    newSlots++;
    var n = newSlots;
    var div = document.createElement('div');
    div.className = 'img-new-slot';
    div.id = 'nslot-' + n;
    div.innerHTML =
        '<span class="img-num">+' + n + '</span>' +
        '<input type="file" name="new_productimages[]" accept="image/*" onchange="previewNewImg(this,' + n + ')">' +
        '<img id="np-' + n + '" class="img-preview-new" src="" alt="">' +
        '<button type="button" class="btn btn-mini btn-danger btn-remove" onclick="removeNewSlot(' + n + ')">' +
            '<i class="icon-remove"></i>' +
        '</button>';
    document.getElementById('new-img-slots').appendChild(div);
    updateCounter();
});

updateCounter();
</script>
</body>
<?php } ?>