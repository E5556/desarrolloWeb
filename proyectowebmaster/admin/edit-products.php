
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

// ── G3: Guardar variantes ─────────────────────────────────────────────────
if (!empty($_POST['var_attr_name'])) {
    mysqli_query($con, "DELETE FROM product_variants WHERE product_id=$pid");
    $vstmt = mysqli_prepare($con, "INSERT INTO product_variants (product_id,variant_name,variant_value,stock_qty,price_extra) VALUES(?,?,?,?,?)");
    foreach ($_POST['var_attr_name'] as $vi => $vattr) {
        $vattr  = trim($vattr);
        $vval   = trim($_POST['var_attr_value'][$vi] ?? '');
        $vstk   = (isset($_POST['var_stock'][$vi]) && $_POST['var_stock'][$vi] !== '') ? intval($_POST['var_stock'][$vi]) : null;
        $vmod   = floatval($_POST['var_price_mod'][$vi] ?? 0);
        if ($vattr === '' || $vval === '') continue;
        mysqli_stmt_bind_param($vstmt,'issid',$pid,$vattr,$vval,$vstk,$vmod);
        mysqli_stmt_execute($vstmt);
    }
    mysqli_stmt_close($vstmt);
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
// ── G3: Cargar variantes existentes ──────────────────────────────────────
$_variants = [];
$_vq = mysqli_query($con, "SELECT * FROM product_variants WHERE product_id=$pid ORDER BY id");
while ($_vr = mysqli_fetch_assoc($_vq)) $_variants[] = $_vr;
?>

<!-- ── VARIANTES (talla/color/etc.) ── -->
<style>
.var-card { background:#f8f9fa; border:1px solid #e0e0e0; border-radius:6px; padding:10px 12px; margin-bottom:8px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; position:relative; }
.var-card .var-field { display:flex; flex-direction:column; gap:3px; }
.var-card .var-field label { font-size:.75em; color:#777; font-weight:600; margin:0; }
.var-card .var-field input { border:1px solid #ccc; border-radius:4px; padding:5px 8px; font-size:.85em; }
.var-card .var-field input:focus { border-color:#337ab7; outline:none; }
.var-card .var-del { position:absolute; top:8px; right:8px; background:#e8233a; color:#fff; border:none; border-radius:50%; width:22px; height:22px; cursor:pointer; font-size:13px; line-height:22px; text-align:center; padding:0; }
.var-card .var-del:hover { background:#c0392b; }
.var-sep { font-size:.75em; color:#aaa; border-top:1px dashed #ddd; margin:4px 0 8px; padding-top:8px; }
</style>
<div class="control-group">
<label class="control-label">Variantes <small style="color:#aaa">(talla / color / etc.)</small></label>
<div class="controls" style="max-width:580px">
<div id="variants-body">
<?php if (empty($_variants)): ?>
<div class="var-card">
    <div class="var-field" style="flex:2;min-width:110px">
        <label>Atributo</label>
        <input type="text" name="var_attr_name[]" placeholder="ej: Talla">
    </div>
    <div class="var-field" style="flex:2;min-width:90px">
        <label>Valor</label>
        <input type="text" name="var_attr_value[]" placeholder="ej: XL">
    </div>
    <div class="var-field" style="flex:1;min-width:70px">
        <label>Stock</label>
        <input type="number" name="var_stock[]" min="0" placeholder="—">
    </div>
    <div class="var-field" style="flex:1;min-width:80px">
        <label>+/- Precio</label>
        <input type="number" name="var_price_mod[]" step="0.01" placeholder="0">
    </div>
    <button type="button" class="var-del" onclick="removeVarRow(this)" title="Eliminar variante">✕</button>
</div>
<?php else: foreach($_variants as $_v): ?>
<div class="var-card">
    <div class="var-field" style="flex:2;min-width:110px">
        <label>Atributo</label>
        <input type="text" name="var_attr_name[]" value="<?php echo htmlspecialchars($_v['variant_name']); ?>">
    </div>
    <div class="var-field" style="flex:2;min-width:90px">
        <label>Valor</label>
        <input type="text" name="var_attr_value[]" value="<?php echo htmlspecialchars($_v['variant_value']); ?>">
    </div>
    <div class="var-field" style="flex:1;min-width:70px">
        <label>Stock</label>
        <input type="number" name="var_stock[]" min="0" value="<?php echo $_v['stock_qty'] !== null ? intval($_v['stock_qty']) : ''; ?>">
    </div>
    <div class="var-field" style="flex:1;min-width:80px">
        <label>+/- Precio</label>
        <input type="number" name="var_price_mod[]" step="0.01" value="<?php echo floatval($_v['price_extra']); ?>">
    </div>
    <button type="button" class="var-del" onclick="removeVarRow(this)" title="Eliminar variante">✕</button>
</div>
<?php endforeach; endif; ?>
</div>
<div style="margin-top:8px;display:flex;align-items:center;gap:12px">
    <button type="button" class="btn btn-mini btn-default" onclick="addVarRow()">
        <i class="icon-plus"></i> Agregar variante
    </button>
    <span style="font-size:.78em;color:#aaa">Deja vacío si el producto no tiene variantes. El campo "+/- Precio" suma o resta del precio base.</span>
</div>
</div>
</div>
<script>
function addVarRow(){
    var card = '<div class="var-card">'
        + '<div class="var-field" style="flex:2;min-width:110px"><label>Atributo</label><input type="text" name="var_attr_name[]" placeholder="ej: Talla"></div>'
        + '<div class="var-field" style="flex:2;min-width:90px"><label>Valor</label><input type="text" name="var_attr_value[]" placeholder="ej: XL"></div>'
        + '<div class="var-field" style="flex:1;min-width:70px"><label>Stock</label><input type="number" name="var_stock[]" min="0" placeholder="—"></div>'
        + '<div class="var-field" style="flex:1;min-width:80px"><label>+/- Precio</label><input type="number" name="var_price_mod[]" step="0.01" placeholder="0"></div>'
        + '<button type="button" class="var-del" onclick="removeVarRow(this)" title="Eliminar variante">✕</button>'
        + '</div>';
    document.getElementById('variants-body').insertAdjacentHTML('beforeend', card);
}
function removeVarRow(btn){ btn.closest('.var-card').remove(); }
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