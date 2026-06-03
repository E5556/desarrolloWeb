
<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin']))
	{	
header('location:index.php');
}
else{
admin_require_perm('perm_products');

date_default_timezone_set('Asia/Kolkata');
$currentTime = date( 'd-m-Y h:i:s A', time () );

if(isset($_GET['del'])) {
    mysqli_query($con,"DELETE FROM products WHERE id=" . intval($_GET['id']));
    $_SESSION['delmsg']="Product deleted !!";
}

// ── Filtros ──────────────────────────────────────────────────────────────────
$f_avail    = $_GET['avail']    ?? '';
$f_stock    = $_GET['stock']    ?? '';
$f_cat      = intval($_GET['cat'] ?? 0);
$f_sup      = intval($_GET['sup'] ?? 0);

$where = [];
if ($f_avail !== '') $where[] = "p.productAvailability='" . mysqli_real_escape_string($con,$f_avail) . "'";
if ($f_cat > 0)      $where[] = "p.category=$f_cat";
if ($f_sup > 0)      $where[] = "p.supplier_id=$f_sup";
if ($f_stock === 'low')  $where[] = "p.stock_qty IS NOT NULL AND p.stock_qty > 0 AND p.stock_qty <= 5";
if ($f_stock === 'zero') $where[] = "p.stock_qty IS NOT NULL AND p.stock_qty = 0";
if ($f_stock === 'null') $where[] = "p.stock_qty IS NULL";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cats_q = mysqli_query($con, "SELECT id, categoryName FROM category ORDER BY categoryName");
$cats_arr = [];
while ($c = mysqli_fetch_assoc($cats_q)) $cats_arr[] = $c;

$sups_q = mysqli_query($con, "SELECT id, name FROM suppliers WHERE active=1 ORDER BY name");
$sups_arr = [];
while ($s = mysqli_fetch_assoc($sups_q)) $sups_arr[] = $s;

$low_threshold = 5;

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin| Administrar Productos</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
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
								<h3>Administrar Productos
                                    <span style="float:right;display:flex;gap:6px">
                                        <a href="export.php?type=products" class="btn btn-xs btn-default"><i class="icon-download"></i> Exportar CSV</a>
                                        <a href="inventory-adjust.php" class="btn btn-xs btn-success"><i class="icon-inbox"></i> Ajustar inventario</a>
                                        <a href="product-sales.php" class="btn btn-xs btn-info"><i class="icon-bar-chart"></i> Ventas por ref.</a>
                                    </span>
                                </h3>
							</div>
							<div class="module-body table">
	<?php if(isset($_GET['del'])): ?>
									<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">×</button>
										<strong>Eliminado:</strong> <?php echo htmlentities($_SESSION['delmsg']); $_SESSION['delmsg']=''; ?>
									</div>
	<?php endif; ?>

    <!-- Filtros -->
    <form method="get" style="background:#f8f9fa;border:1px solid #e0e0e0;border-radius:6px;padding:12px 16px;margin-bottom:14px">
        <div class="row-fluid" style="margin:0">
            <div class="span3">
                <label style="font-size:.8em;color:#555">Disponibilidad</label>
                <select name="avail" class="input-block-level">
                    <option value="">Todas</option>
                    <option value="In Stock"    <?php echo $f_avail==='In Stock'?'selected':'';    ?>>In Stock</option>
                    <option value="Out of Stock"<?php echo $f_avail==='Out of Stock'?'selected':'';?>>Out of Stock</option>
                    <option value="On Order"    <?php echo $f_avail==='On Order'?'selected':'';    ?>>On Order</option>
                </select>
            </div>
            <div class="span3">
                <label style="font-size:.8em;color:#555">Stock</label>
                <select name="stock" class="input-block-level">
                    <option value="">Todos</option>
                    <option value="low"  <?php echo $f_stock==='low'?'selected':'';  ?>>Stock crítico (≤5)</option>
                    <option value="zero" <?php echo $f_stock==='zero'?'selected':''; ?>>Agotado (0)</option>
                    <option value="null" <?php echo $f_stock==='null'?'selected':''; ?>>Sin stock definido</option>
                </select>
            </div>
            <div class="span2">
                <label style="font-size:.8em;color:#555">Categoría</label>
                <select name="cat" class="input-block-level">
                    <option value="0">Todas</option>
                    <?php foreach ($cats_arr as $c): ?>
                    <option value="<?php echo $c['id'];?>" <?php echo $f_cat==$c['id']?'selected':'';?>><?php echo htmlspecialchars($c['categoryName']);?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span2">
                <label style="font-size:.8em;color:#555">Proveedor</label>
                <select name="sup" class="input-block-level">
                    <option value="0">Todos</option>
                    <?php foreach ($sups_arr as $s): ?>
                    <option value="<?php echo $s['id'];?>" <?php echo $f_sup==$s['id']?'selected':'';?>><?php echo htmlspecialchars($s['name']);?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span2" style="padding-top:18px">
                <button type="submit" class="btn btn-primary btn-small">Filtrar</button>
                <a href="manage-products.php" class="btn btn-default btn-small">Limpiar</a>
            </div>
        </div>
    </form>

								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" width="100%">
									<thead>
										<tr>
											<th>#</th>
											<th>Nombre</th>
											<th>Categoría</th>
											<th>Subcategoría</th>
											<th>Fabricante</th>
											<th>Stock</th>
											<th>Disponibilidad</th>
											<th>Proveedor</th>
											<th>Acción</th>
										</tr>
									</thead>
									<tbody>

<?php $query=mysqli_query($con,"SELECT p.*, c.categoryName, sc.subcategory, COALESCE(s.name,'—') as supplier_name
    FROM products p
    JOIN category c ON c.id=p.category
    JOIN subcategory sc ON sc.id=p.subCategory
    LEFT JOIN suppliers s ON s.id=p.supplier_id
    $where_sql
    ORDER BY p.productName");
$cnt=1;
while($row=mysqli_fetch_array($query)) {
    $avail_color = ['In Stock'=>'#27ae60','Out of Stock'=>'#e8233a','On Order'=>'#f39c12'];
    $ac = $avail_color[$row['productAvailability']] ?? '#aaa';
    $stock_val = $row['stock_qty'];
    $stock_color = $stock_val === null ? '#aaa' : ($stock_val <= 0 ? '#e8233a' : ($stock_val <= $low_threshold ? '#f39c12' : '#27ae60'));
?>
										<tr>
											<td><?php echo htmlentities($cnt);?></td>
											<td><strong><?php echo htmlentities($row['productName']);?></strong></td>
											<td><?php echo htmlentities($row['categoryName']);?></td>
											<td><?php echo htmlentities($row['subcategory']);?></td>
											<td><?php echo htmlentities($row['productCompany']);?></td>
											<td style="text-align:center">
                                                <span style="font-weight:700;color:<?php echo $stock_color;?>">
                                                    <?php echo $stock_val !== null ? $stock_val : '—'; ?>
                                                </span>
                                            </td>
											<td>
                                                <span style="background:<?php echo $ac;?>;color:#fff;padding:2px 7px;border-radius:8px;font-size:.75em;font-weight:700">
                                                    <?php echo htmlentities($row['productAvailability']);?>
                                                </span>
                                            </td>
                                            <td style="font-size:.82em;color:#555"><?php echo htmlentities($row['supplier_name']);?></td>
											<td style="white-space:nowrap">
                                                <a href="edit-products.php?id=<?php echo $row['id']?>" title="Editar"><i class="icon-edit"></i></a>
                                                <a href="product-sales.php?pid=<?php echo $row['id']?>" title="Ver ventas" style="color:#8e44ad"><i class="icon-bar-chart"></i></a>
                                                <a href="manage-products.php?id=<?php echo $row['id']?>&del=delete" onclick="return confirm('¿Eliminar producto?')" title="Eliminar"><i class="icon-remove-sign"></i></a>
                                            </td>
										</tr>
										<?php $cnt++; } ?>

								</table>
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
			$('.dataTables_paginate').addClass("btn-group datatable-pagination");
			$('.dataTables_paginate > a').wrapInner('<span />');
			$('.dataTables_paginate > a:first-child').append('<i class="icon-chevron-left shaded"></i>');
			$('.dataTables_paginate > a:last-child').append('<i class="icon-chevron-right shaded"></i>');
		} );
	</script>
</body>
<?php } ?>