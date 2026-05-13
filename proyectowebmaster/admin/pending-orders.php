
<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin']))
	{	
header('location:index.php');
}
else{

date_default_timezone_set('Asia/Kolkata');// change according timezone
$currentTime = date( 'd-m-Y h:i:s A', time () );


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin| Ordenes Pendientes</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
	<style>
		.dataTables_wrapper { overflow-x: auto; }
		.datatable-1 th, .datatable-1 td { white-space: nowrap; font-size: 12px; }
		.datatable-1 td:nth-child(3) { max-width: 160px; overflow: hidden; text-overflow: ellipsis; }
		.datatable-1 td:nth-child(4) { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: normal; font-size: 11px; }
	</style>
	<script language="javascript" type="text/javascript">
var popUpWin=0;
function popUpWindow(URLStr, left, top, width, height)
{
 if(popUpWin)
{
if(!popUpWin.closed) popUpWin.close();
}
popUpWin = open(URLStr,'popUpWin', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=yes,width='+600+',height='+600+',left='+left+', top='+top+',screenX='+left+',screenY='+top+'');
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
								<h3>Ordenes pendientes <a href="export.php?type=orders" class="btn btn-xs btn-default pull-right" style="margin-top:4px"><i class="icon-download"></i> Exportar CSV</a></h3>
							</div>
							<div class="module-body table">
	<?php if(isset($_GET['del']))
{?>
									<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">×</button>
									<strong>Oh no!</strong> 	<?php echo htmlentities($_SESSION['delmsg']);?><?php echo htmlentities($_SESSION['delmsg']="");?>
									</div>
<?php } ?>

									<br />

							
							<div style="overflow-x:auto">
								<table cellpadding="0" cellspacing="0" border="0" class="datatable-1 table table-bordered table-striped display" >
									<thead>
										<tr>
											<th>ID Orden</th>
											<th>Nombre</th>
											<th>Email / Teléfono</th>
											<th>Dirección</th>
											<th>Producto</th>
											<th>Qty</th>
											<th>Total</th>
											<th>Estado</th>
											<th>Fecha</th>
											<th>Acción</th>
											
										
										</tr>
									</thead>
								
<tbody>
<?php 
$status='Delivered';
$query=mysqli_query($con,"select users.name as username,users.email as useremail,users.contactno as usercontact,users.shippingAddress as shippingaddress,users.shippingCity as shippingcity,users.shippingState as shippingstate,users.shippingPincode as shippingpincode,products.productName as productname,products.shippingCharge as shippingcharge,orders.quantity as quantity,orders.orderDate as orderdate,products.productPrice as productprice,orders.id as id,orders.orderStatus as orderstatus  from orders join users on orders.userId=users.id join products on products.id=orders.productId where orders.orderStatus!='$status' or orders.orderStatus is null");
$cnt=1;
while($row=mysqli_fetch_array($query))
{
?>										
										<tr>
											<td><?php echo htmlentities($row['id']);?></td>
											<td><?php echo htmlentities($row['username']);?></td>
<td><?php echo htmlentities($row['useremail']);?>/<?php echo htmlentities($row['usercontact']);?></td>
<td><?php echo htmlentities($row['shippingaddress'].",".$row['shippingcity'].",".$row['shippingstate']."-".$row['shippingpincode']);?></td>
											<td><?php echo htmlentities($row['productname']);?></td>
											<td><?php echo htmlentities($row['quantity']);?></td>
											<td><?php echo htmlentities($row['quantity']*$row['productprice']+$row['shippingcharge']);?></td>
											<td><?php echo htmlentities($row['orderstatus'] ?: 'Pendiente'); ?></td>
											<td><?php echo htmlentities($row['orderdate']);?></td>
											<td><a href="updateorder.php?oid=<?php echo htmlentities($row['id']);?>" title="Actualizar orden" target="_blank"><i class="icon-edit"></i></a></td>
											</tr>

										<?php $cnt=$cnt+1; } ?>
										</tbody>
								</table>
							</div><!-- /.overflow -->
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
			$('.datatable-1').dataTable({ "bAutoWidth": false });
			$('.dataTables_paginate').addClass("btn-group datatable-pagination");
			$('.dataTables_paginate > a').wrapInner('<span />');
			$('.dataTables_paginate > a:first-child').append('<i class="icon-chevron-left shaded"></i>');
			$('.dataTables_paginate > a:last-child').append('<i class="icon-chevron-right shaded"></i>');
		} );
	</script>
</body>
<?php } ?>