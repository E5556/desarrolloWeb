<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if(empty($_SESSION['login']))
    {
header('location:login.php');
}
else{
	if(isset($_POST['update']))
	{
		csrf_verify();
		// Q2: asegurar columna birthday
		@mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS birthday DATE DEFAULT NULL");
		$name      = safe_str($con, $_POST['name']);
		$contactno = safe_str($con, $_POST['contactno']);
		$birthday  = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
		$uid       = intval($_SESSION['id']);
		$stmt = mysqli_prepare($con, "UPDATE users SET name=?, contactno=?, birthday=? WHERE id=?");
		mysqli_stmt_bind_param($stmt, 'sssi', $name, $contactno, $birthday, $uid);
		if(mysqli_stmt_execute($stmt)) {
			echo "<script>showToast('Información actualizada correctamente','success');</script>";
		}
		mysqli_stmt_close($stmt);
	}

	date_default_timezone_set('America/Bogota');
	$currentTime = date('d-m-Y H:i:s', time());

	if(isset($_POST['submit']))
	{
		csrf_verify();
		$uid = intval($_SESSION['id']);
		// Fetch stored password hash
		$stmt = mysqli_prepare($con, "SELECT password FROM users WHERE id=?");
		mysqli_stmt_bind_param($stmt, 'i', $uid);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$row_pw = mysqli_fetch_array($result);
		mysqli_stmt_close($stmt);

		if($row_pw && password_verify($_POST['cpass'], $row_pw['password']))
		{
			$newpass = password_hash($_POST['newpass'], PASSWORD_BCRYPT);
			$stmt2 = mysqli_prepare($con, "UPDATE users SET password=? WHERE id=?");
			mysqli_stmt_bind_param($stmt2, 'si', $newpass, $uid);
			mysqli_stmt_execute($stmt2);
			mysqli_stmt_close($stmt2);
			echo "<script>showToast('Contraseña actualizada correctamente','success');</script>";
		}
		else
		{
			echo "<script>showToast('La contraseña actual no coincide','error');</script>";
		}
	}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Meta -->
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta name="description" content="">
		<meta name="author" content="">
	    <meta name="keywords" content="MediaCenter, Template, eCommerce">
	    <meta name="robots" content="all">

	    <title>Mi cuenta | <?php echo $_SITE_NAME; ?></title>

	    <!-- Bootstrap Core CSS -->
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    
	    <!-- Customizable CSS -->
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/green.css">
	<link rel="stylesheet" href="assets/css/cosmetics.css">
	    <link rel="stylesheet" href="assets/css/owl.carousel.css">
		<link rel="stylesheet" href="assets/css/owl.transitions.css">
		<!---->
		<link href="assets/css/lightbox.css" rel="stylesheet">
		<link rel="stylesheet" href="assets/css/animate.min.css">
		<link rel="stylesheet" href="assets/css/rateit.css">
		<link rel="stylesheet" href="assets/css/bootstrap-select.min.css">

		<!-- Demo Purpose Only. Should be removed in production -->
		<link rel="stylesheet" href="assets/css/config.css">

		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
<script type="text/javascript">
function valid()
{
if(document.chngpwd.cpass.value=="")
{
showToast('El campo de contraseña actual está vacío','warning');
document.chngpwd.cpass.focus();
return false;
}
else if(document.chngpwd.newpass.value=="")
{
showToast('El campo de nueva contraseña está vacío','warning');
document.chngpwd.newpass.focus();
return false;
}
else if(document.chngpwd.cnfpass.value=="")
{
showToast('El campo de confirmar contraseña está vacío','warning');
document.chngpwd.cnfpass.focus();
return false;
}
else if(document.chngpwd.newpass.value!= document.chngpwd.cnfpass.value)
{
showToast('Las contraseñas no coinciden','error');
document.chngpwd.cnfpass.focus();
return false;
}
return true;
}
</script>

	</head>
    <body class="cnt-home">
<header class="header-style-1">

	<!-- ============================================== TOP MENU ============================================== -->
<?php include('includes/top-header.php');?>
<!-- ============================================== TOP MENU : END ============================================== -->
<?php include('includes/main-header.php');?>
	<!-- ============================================== NAVBAR ============================================== -->
<?php include('includes/menu-bar.php');?>
<!-- ============================================== NAVBAR : END ============================================== -->

</header>
<!-- ============================================== HEADER : END ============================================== -->
<div class="breadcrumb">
	<div class="container">
		<div class="breadcrumb-inner">
			<ul class="list-inline list-unstyled">
				<li><a href="index2.php">Inicio</a></li>
				<li class='active'>Mi cuenta</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-bd">
	<div class="container">
		<div class="checkout-box inner-bottom-sm">
			<div class="row">
				<div class="col-md-8">
					<div class="panel-group checkout-steps" id="accordion">
						<?php
// Dashboard: últimas órdenes, wishlist, puntos del usuario
$_uid_dash = intval($_SESSION['id']);
include_once('includes/points.php');
$_user_points = ps_points_get($con, $_uid_dash);
$_points_log  = ps_points_log($con, $_uid_dash, 5);
$_level_info  = ps_level_info($_user_points);
$_dash_orders = mysqli_query($con,"SELECT o.id, o.productId, p.productName, o.quantity, o.status FROM orders o LEFT JOIN products p ON p.id=o.productId WHERE o.userId=$_uid_dash ORDER BY o.id DESC LIMIT 3");
$_dash_wish   = mysqli_query($con,"SELECT COUNT(*) as total FROM wishlist WHERE userId=$_uid_dash");
$_dash_wish_c = ($_r=mysqli_fetch_assoc($_dash_wish)) ? intval($_r['total']) : 0;
$_dash_ord_total = mysqli_query($con,"SELECT COUNT(*) as total FROM orders WHERE userId=$_uid_dash");
$_dash_ord_c  = ($_r=mysqli_fetch_assoc($_dash_ord_total)) ? intval($_r['total']) : 0;
?>
<style>
.dash-summary{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.dash-stat{flex:1;min-width:120px;background:#fafafa;border:1px solid #eee;border-radius:8px;padding:14px 16px;text-align:center;}
.dash-stat .ds-num{font-size:28px;font-weight:700;color:#e8233a;line-height:1.1;}
.dash-stat .ds-lbl{font-size:12px;color:#888;margin-top:4px;}
.dash-orders-list{margin-top:6px;}
.dash-order-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:13px;}
.dash-order-row:last-child{border:none;}
.dash-order-id{background:#f5f5f5;border-radius:4px;padding:2px 7px;font-size:11px;color:#555;}
.dash-order-status{margin-left:auto;font-size:11px;padding:2px 8px;border-radius:10px;}
.status-pending{background:#fff3cd;color:#856404;}
.status-delivered{background:#d4edda;color:#155724;}
.status-processing{background:#cce5ff;color:#004085;}
.dash-empty{color:#aaa;font-size:13px;padding:10px 0;}
</style>
<div class="panel panel-default checkout-step-00" style="margin-bottom:16px">
  <div class="panel-heading">
    <h4 class="unicase-checkout-title">
      <a data-toggle="collapse" class="" data-parent="#accordion" href="#collapseDash">
        <span><i class="fa fa-tachometer" style="font-size:14px"></i></span> Mi resumen
      </a>
    </h4>
  </div>
  <div id="collapseDash" class="panel-collapse collapse in">
    <div class="panel-body">
      <div class="dash-summary">
        <div class="dash-stat">
          <div class="ds-num"><?php echo $_dash_ord_c; ?></div>
          <div class="ds-lbl"><i class="fa fa-shopping-bag"></i> Órdenes totales</div>
        </div>
        <div class="dash-stat">
          <div class="ds-num"><?php echo $_dash_wish_c; ?></div>
          <div class="ds-lbl"><i class="fa fa-heart"></i> Lista de deseos</div>
        </div>
        <div class="dash-stat">
          <div class="ds-num" style="color:#f39c12"><?php echo number_format($_user_points); ?></div>
          <div class="ds-lbl"><i class="fa fa-star" style="color:#f39c12"></i> Puntos acumulados</div>
        </div>
        <div class="dash-stat" style="cursor:pointer" onclick="location.href='order-history.php'">
          <div class="ds-num"><i class="fa fa-history" style="font-size:20px;color:#888"></i></div>
          <div class="ds-lbl">Ver historial</div>
        </div>
      </div>
      <?php
      $lvl = $_level_info;
      $lvl_pct = $lvl['next'] ? min(100, round(($_user_points / $lvl['next_pts']) * 100)) : 100;
      ?>
      <div style="background:#f9f9f9;border:1px solid #eee;border-radius:10px;padding:14px 16px;margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <span style="font-size:28px"><?php echo $lvl['icon']; ?></span>
          <div>
            <strong style="color:<?php echo $lvl['color']; ?>;font-size:16px"><?php echo $lvl['name']; ?></strong>
            <?php if ($lvl['discount'] > 0): ?>
            <span style="background:<?php echo $lvl['color']; ?>;color:#fff;border-radius:10px;padding:2px 8px;font-size:11px;margin-left:6px"><?php echo $lvl['discount']; ?>% descuento permanente</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($lvl['next']): ?>
        <div style="font-size:12px;color:#888;margin-bottom:4px">Progreso a <?php echo $lvl['next']; ?>: <?php echo number_format($_user_points); ?> / <?php echo number_format($lvl['next_pts']); ?> pts</div>
        <div style="background:#e0e0e0;border-radius:10px;height:8px;overflow:hidden">
          <div style="background:<?php echo $lvl['color']; ?>;height:100%;width:<?php echo $lvl_pct; ?>%;border-radius:10px;transition:width .5s"></div>
        </div>
        <?php else: ?>
        <div style="font-size:12px;color:#27ae60">🎉 ¡Has alcanzado el nivel máximo!</div>
        <?php endif; ?>
      </div>
      <?php if (!empty($_points_log)): ?>
      <h5 style="color:#555;margin:12px 0 8px"><i class="fa fa-star" style="color:#f39c12"></i> Historial de puntos</h5>
      <div style="font-size:12px;border:1px solid #f0f0f0;border-radius:6px;overflow:hidden">
        <?php foreach ($_points_log as $_pl): ?>
        <div style="display:flex;align-items:center;padding:7px 12px;border-bottom:1px solid #f8f8f8;gap:8px">
          <span style="color:<?php echo $_pl['delta']>0 ? '#27ae60' : '#e8233a'; ?>;font-weight:700;min-width:40px"><?php echo ($_pl['delta']>0?'+':'').$_pl['delta']; ?> pts</span>
          <span style="flex:1"><?php echo htmlspecialchars($_pl['reason']); ?></span>
          <span style="color:#aaa;white-space:nowrap"><?php echo substr($_pl['created_at'],0,10); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <h5 style="color:#555;margin-bottom:10px"><i class="fa fa-clock-o"></i> Últimas órdenes</h5>
      <div class="dash-orders-list">
        <?php
        $has_orders = false;
        while($_dash_orders && $_do=mysqli_fetch_assoc($_dash_orders)):
            $has_orders = true;
            $st = strtolower($_do['status'] ?? 'pending');
            $stClass = in_array($st,['delivered','completado','entregado']) ? 'status-delivered' : (in_array($st,['processing','procesando']) ? 'status-processing' : 'status-pending');
            $stLabel = ucfirst($_do['status'] ?? 'Pendiente');
        ?>
        <div class="dash-order-row">
          <span class="dash-order-id">#<?php echo intval($_do['id']); ?></span>
          <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($_do['productName'] ?? 'Producto'); ?></span>
          <span style="color:#888">x<?php echo intval($_do['quantity']); ?></span>
          <span class="dash-order-status <?php echo $stClass; ?>"><?php echo htmlspecialchars($stLabel); ?></span>
        </div>
        <?php endwhile; ?>
        <?php if(!$has_orders): ?>
        <div class="dash-empty"><i class="fa fa-info-circle"></i> Aún no tienes órdenes registradas.</div>
        <?php endif; ?>
      </div>
      <?php if($has_orders): ?>
      <a href="order-history.php" class="btn btn-default btn-sm" style="margin-top:10px">Ver todas las órdenes &rarr;</a>
      <?php endif; ?>

      <?php
      // J2: Bloque de referidos
      $ref_q = mysqli_query($con, "SELECT referral_code, referred_by FROM users WHERE id=$_uid_dash LIMIT 1");
      $ref_data = $ref_q ? mysqli_fetch_assoc($ref_q) : null;
      if ($ref_data && !empty($ref_data['referral_code'])):
          $ref_link = 'http://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/login.php?ref=' . urlencode($ref_data['referral_code']);
          $ref_count_q = mysqli_query($con, "SELECT COUNT(*) n FROM users WHERE referred_by=$_uid_dash");
          $ref_count = $ref_count_q ? intval(mysqli_fetch_assoc($ref_count_q)['n']) : 0;
      ?>
      <hr>
      <h5 style="color:#555;margin:14px 0 8px"><i class="fa fa-users" style="color:#9b59b6"></i> Programa de referidos</h5>
      <div style="background:#f9f4ff;border:1px solid #d8b4fe;border-radius:8px;padding:14px 16px">
          <p style="margin:0 0 8px;font-size:13px">Comparte tu enlace y <strong>gana 50 puntos</strong> por cada amigo que se registre:</p>
          <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
              <input type="text" id="ref-link-input" value="<?php echo htmlspecialchars($ref_link); ?>"
                     class="form-control" readonly style="flex:1;min-width:200px;font-size:12px">
              <button type="button" class="btn btn-sm btn-default" onclick="copyRef()"><i class="fa fa-copy"></i> Copiar</button>
          </div>
          <p style="margin:8px 0 0;font-size:12px;color:#888">
              Amigos registrados: <strong><?php echo $ref_count; ?></strong> &nbsp;·&nbsp;
              Código: <code><?php echo htmlspecialchars($ref_data['referral_code']); ?></code>
          </p>
      </div>
      <script>
      function copyRef(){
          var el=document.getElementById('ref-link-input');
          el.select(); document.execCommand('copy');
          if(typeof showToast==='function') showToast('Enlace copiado','success');
      }
      </script>
      <?php endif; ?>
    </div>
  </div>
</div>
<!-- checkout-step-01  -->
<div class="panel panel-default checkout-step-01">

	<!-- panel-heading -->
		<div class="panel-heading">
    	<h4 class="unicase-checkout-title">
	        <a data-toggle="collapse" class="collapsed" data-parent="#accordion" href="#collapseOne">
	          <span>1</span>Mi perfil
	        </a>
	     </h4>
    </div>
    <!-- panel-heading -->

	<div id="collapseOne" class="panel-collapse collapse">

		<!-- panel-body  -->
	    <div class="panel-body">
			<div class="row">		
<h4>Información Personal</h4>
				<div class="col-md-12 col-sm-12 already-registered-login">

<?php
$uid_q = intval($_SESSION['id']);
$stmt_q = mysqli_prepare($con, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt_q, 'i', $uid_q);
mysqli_stmt_execute($stmt_q);
$query = mysqli_stmt_get_result($stmt_q);
while($row=mysqli_fetch_array($query))
{
?>

					<form class="register-form" role="form" method="post">
					<?php echo csrf_field(); ?>
<div class="form-group">
					    <label class="info-title" for="name">Nombre<span>*</span></label>
					    <input type="text" class="form-control unicase-form-control text-input" value="<?php echo $row['name'];?>" id="name" name="name" required="required">
					  </div>



						<div class="form-group">
					    <label class="info-title" for="exampleInputEmail1">Email  <span>*</span></label>
			 <input type="email" class="form-control unicase-form-control text-input" id="exampleInputEmail1" value="<?php echo $row['email'];?>" readonly>
					  </div>
					  <div class="form-group">
					    <label class="info-title" for="Contact No.">Teléfono <span>*</span></label>
					    <input type="text" class="form-control unicase-form-control text-input" id="contactno" name="contactno" required="required" value="<?php echo $row['contactno'];?>"  maxlength="10">
					  </div>
					  <div class="form-group">
					    <label class="info-title" for="birthday">Fecha de cumpleaños <small class="text-muted">(opcional — recibe un cupón de regalo)</small></label>
					    <input type="date" class="form-control unicase-form-control text-input" id="birthday" name="birthday" value="<?php echo htmlspecialchars($row['birthday']??''); ?>">
					  </div>
					  <button type="submit" name="update" class="btn-upper btn btn-primary checkout-page-button">Actualizar</button>
					</form>
					<?php } ?>
				</div>	
				<!-- already-registered-login -->		

			</div>			
		</div>
		<!-- panel-body  -->

	</div><!-- row -->
</div>
<!-- checkout-step-01  -->
					    <!-- checkout-step-02  -->
					  	<div class="panel panel-default checkout-step-02">
						    <div class="panel-heading">
						      <h4 class="unicase-checkout-title">
						        <a data-toggle="collapse" class="collapsed" data-parent="#accordion" href="#collapseTwo">
						          <span>2</span>Cambiar contraseña
						        </a>
						      </h4>
						    </div>
						    <div id="collapseTwo" class="panel-collapse collapse">
						      <div class="panel-body">
						     
					<form class="register-form" role="form" method="post" name="chngpwd" onSubmit="return valid();">
				<?php echo csrf_field(); ?>
<div class="form-group">
					    <label class="info-title" for="Current Password">Contraseña actual<span>*</span></label>
					    <input type="password" class="form-control unicase-form-control text-input" id="cpass" name="cpass" required="required">
					  </div>



						<div class="form-group">
					    <label class="info-title" for="New Password">Nueva contraseña <span>*</span></label>
			 <input type="password" class="form-control unicase-form-control text-input" id="newpass" name="newpass">
					  </div>
					  <div class="form-group">
					    <label class="info-title" for="Confirm Password">Confirmar contraseña <span>*</span></label>
					    <input type="password" class="form-control unicase-form-control text-input" id="cnfpass" name="cnfpass" required="required" >
					  </div>
					  <button type="submit" name="submit" class="btn-upper btn btn-primary checkout-page-button">Cambiar </button>
					</form> 




						      </div>
						    </div>
					  	</div>
					  	<!-- checkout-step-02  -->
					  	
					<!-- RETOS Y RECOMPENSAS (N2) -->
					<?php
					$_ch_q = @mysqli_query($con, "SELECT c.*, cp.progress, cp.completed FROM challenges c LEFT JOIN challenge_progress cp ON cp.challenge_id=c.id AND cp.user_id=$_uid_dash AND cp.period_key=CASE c.period WHEN 'weekly' THEN DATE_FORMAT(NOW(),'%Y-%u') WHEN 'once' THEN 'once' ELSE DATE_FORMAT(NOW(),'%Y-%m') END WHERE c.active=1 ORDER BY cp.completed ASC, c.id ASC LIMIT 10");
					if ($_ch_q && mysqli_num_rows($_ch_q) > 0):
					?>
					<div class="panel panel-default" style="margin-top:16px">
					  <div class="panel-heading"><h4 class="unicase-checkout-title"><a data-toggle="collapse" class="collapsed" data-parent="#accordion" href="#collapseChallenge"><span><i class="fa fa-trophy" style="font-size:14px;color:#f39c12"></i></span> Retos y recompensas</a></h4></div>
					  <div id="collapseChallenge" class="panel-collapse collapse">
					    <div class="panel-body">
					    <?php while ($_ch = mysqli_fetch_assoc($_ch_q)):
					        $_ch_prog   = intval($_ch['progress'] ?? 0);
					        $_ch_target = intval($_ch['target_value']);
					        $_ch_pct    = min(100, $_ch_target > 0 ? round($_ch_prog / $_ch_target * 100) : 0);
					        $_ch_done   = !empty($_ch['completed']);
					    ?>
					    <div style="margin-bottom:14px;padding:10px 14px;border:1px solid <?php echo $_ch_done?'#d4edda':'#f0f0f0'; ?>;border-radius:8px;background:<?php echo $_ch_done?'#f8fff8':'#fafafa'; ?>">
					      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
					        <strong style="font-size:13px"><?php echo $_ch_done?'✅ ':'🎯 '; echo htmlspecialchars($_ch['title']); ?></strong>
					        <span style="background:#f39c12;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px">+<?php echo $ch['reward_points'] ?? $_ch['reward_points']; ?> pts</span>
					      </div>
					      <?php if ($_ch['description']): ?><div style="font-size:12px;color:#888;margin-bottom:6px"><?php echo htmlspecialchars($_ch['description']); ?></div><?php endif; ?>
					      <div style="background:#e0e0e0;border-radius:10px;height:7px;overflow:hidden">
					        <div style="background:<?php echo $_ch_done?'#27ae60':'#f39c12'; ?>;height:100%;width:<?php echo $_ch_pct; ?>%;border-radius:10px"></div>
					      </div>
					      <div style="font-size:11px;color:#aaa;margin-top:3px"><?php echo $_ch_prog; ?>/<?php echo $_ch_target; ?> <?php echo $_ch_done?'— ¡Completado!':''; ?></div>
					    </div>
					    <?php endwhile; ?>
					    </div>
					  </div>
					</div>
					<?php endif; ?>

					</div><!-- /.checkout-steps -->
				</div>
				<!-- RECOMENDACIONES PERSONALIZADAS (Q1) -->
				<?php
				include_once('includes/recommendations.php');
				$_recs = ps_recommendations($con, $_uid_dash, 6);
				if (!empty($_recs)):
				?>
				<div class="col-md-12" style="margin-top:20px">
				  <h5 style="color:#555"><i class="fa fa-magic" style="color:#e8233a"></i> Para ti — recomendados</h5>
				  <div style="display:flex;flex-wrap:wrap;gap:10px">
				  <?php foreach ($_recs as $_rec): ?>
				    <a href="product-details.php?product=<?php echo $_rec['id']; ?>" style="text-decoration:none;color:#333;border:1px solid #eee;border-radius:8px;overflow:hidden;display:block;width:140px;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
				      <img src="admin/productimages/<?php echo htmlspecialchars($_rec['productImage']??''); ?>" style="width:100%;height:100px;object-fit:cover" onerror="this.src='assets/images/no-image.jpg'">
				      <div style="padding:6px 8px">
				        <div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($_rec['productName']); ?></div>
				        <div style="font-size:11px;color:#e8233a;font-weight:700">$<?php echo number_format($_rec['productPrice'],0,'.',','); ?></div>
				      </div>
				    </a>
				  <?php endforeach; ?>
				  </div>
				</div>
				<?php endif; ?>
			<?php include('includes/myaccount-sidebar.php');?>
			</div><!-- /.row -->
		</div><!-- /.checkout-box -->
	<?php include('includes/brands-slider.php');?>

</div>
</div>
<?php include('includes/footer.php');?>
	<script src="assets/js/jquery-1.11.1.min.js"></script>
	
	<script src="assets/js/bootstrap.min.js"></script>
	
	<script src="assets/js/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/js/owl.carousel.min.js"></script>
	
	<script src="assets/js/echo.min.js"></script>
	<script src="assets/js/jquery.easing-1.3.min.js"></script>
	<script src="assets/js/bootstrap-slider.min.js"></script>
    <script src="assets/js/jquery.rateit.min.js"></script>
    <script type="text/javascript" src="assets/js/lightbox.min.js"></script>
    <script src="assets/js/bootstrap-select.min.js"></script>
    
	<script src="assets/js/scripts.js"></script>
	<script src="assets/js/toast.js"></script>

	<!-- For demo purposes – can be removed on production -->
	
	
	
	<script>
		$(document).ready(function(){ 
			if ($.fn.switchstylesheet) $(".changecolor").switchstylesheet( { seperator:"color"} );
			$('.show-theme-options').click(function(){
				$(this).parent().toggleClass('open');
				return false;
			});
		});

		$(window).bind("load", function() {
		   $('.show-theme-options').delay(2000).trigger('click');
		});
	</script>
<script src="assets/js/cookies.js"></script>
</body>
</html>
<?php } ?>