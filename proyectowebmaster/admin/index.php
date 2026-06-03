<?php
session_start();
error_reporting(0);
include("include/config.php");
if(isset($_POST['submit']))
{
    // Rate limit: 5 intentos en 10 minutos por IP
    $rl_key = 'adm_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $rl_now  = time();
    $rl_data = $_SESSION[$rl_key] ?? ['c'=>0,'t'=>$rl_now];
    if ($rl_now - $rl_data['t'] > 600) $rl_data = ['c'=>0,'t'=>$rl_now];
    $rl_data['c']++;
    $_SESSION[$rl_key] = $rl_data;
    if ($rl_data['c'] > 5) {
        $_SESSION['errmsg'] = 'Demasiados intentos. Espera 10 minutos.';
        header('location:index.php'); exit();
    }

    $username = trim($_POST['username'] ?? '');
    // Fetch admin by username only, then verify password
    $stmt = mysqli_prepare($con, "SELECT * FROM admin WHERE username=?");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $num    = mysqli_fetch_array($result);
    mysqli_stmt_close($stmt);

    // Support both legacy MD5 and new bcrypt hashes
    $pw_input   = $_POST['password'] ?? '';
    $pw_stored  = $num['password'] ?? '';
    $pw_valid   = false;
    if($num) {
        if(strlen($pw_stored) === 32) {
            // Legacy MD5 — accept but flag for upgrade
            $pw_valid = (md5($pw_input) === $pw_stored);
        } else {
            $pw_valid = password_verify($pw_input, $pw_stored);
        }
    }

    if($pw_valid)
    {
        unset($_SESSION[$rl_key]);
        // FF1 — 2FA: si está habilitado, enviar OTP y redirigir a verificación
        $tfa_q = mysqli_query($con,"SELECT setting_value FROM settings WHERE setting_key='two_factor_enabled' LIMIT 1");
        $tfa_on = $tfa_q && (mysqli_fetch_assoc($tfa_q)['setting_value'] ?? '0') === '1';
        // Obtener email del admin
        $adm_email_q = mysqli_query($con,"SELECT setting_value FROM settings WHERE setting_key='admin_email' LIMIT 1");
        $adm_email = trim(mysqli_fetch_assoc($adm_email_q)['setting_value'] ?? '');
        if ($tfa_on && $adm_email !== '') {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['2fa_pending']  = true;
            $_SESSION['2fa_otp']      = password_hash($otp, PASSWORD_BCRYPT);
            $_SESSION['2fa_expires']  = time() + 600;
            $_SESSION['2fa_uid']      = $num['id'];
            $_SESSION['2fa_username'] = $username;
            $_SESSION['2fa_role']     = $num['role'] ?? 'super';
            include_once('../includes/mailer.php');
            send_email_otp($con, $adm_email, $otp);
            header("location:admin-verify-2fa.php"); exit();
        }
        session_regenerate_id(true);
        $_SESSION['alogin'] = $username;
        $_SESSION['id']     = $num['id'];
        $_SESSION['aid']    = $num['id'];
        $_SESSION['arole']  = $num['role'] ?? 'super';
        include_once('../includes/admin-log.php');
        admin_log($con, 'login', 'Sesión iniciada desde ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        header("location:dashboard.php");
        exit();
    }
    else
    {
        $_SESSION['errmsg'] = "Invalid username or password";
        header("location:index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $_ADMIN_SITE_NAME; ?> | Admin</title>
	<link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
	<link type="text/css" href="css/theme.css" rel="stylesheet">
	<link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
	<link type="text/css" href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
</head>
<body>

	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".navbar-inverse-collapse">
					<i class="icon-reorder shaded"></i>
				</a>

			  	<a class="brand" href="index.html">
			  		<?php echo $_ADMIN_SITE_NAME; ?> | Admin
			  	</a>

				<div class="nav-collapse collapse navbar-inverse-collapse">
				
					<ul class="nav pull-right">

						<li><a href="http://localhost/shopping/">
						Volver al Portal
						
						</a></li>

						

						
					</ul>
				</div><!-- /.nav-collapse -->
			</div>
		</div><!-- /navbar-inner -->
	</div><!-- /navbar -->



	<div class="wrapper">
		<div class="container">
			<div class="row">
				<div class="module module-login span4 offset4">
					<form class="form-vertical" method="post">
						<div class="module-head">
							<h3>Registrarse</h3>
						</div>
						<span style="color:red;" ><?php echo htmlentities($_SESSION['errmsg']); ?><?php echo htmlentities($_SESSION['errmsg']="");?></span>
						<div class="module-body">
							<div class="control-group">
								<div class="controls row-fluid">
									<input class="span12" type="text" id="inputEmail" name="username" placeholder="Username">
								</div>
							</div>
							<div class="control-group">
								<div class="controls row-fluid">
						<input class="span12" type="password" id="inputPassword" name="password" placeholder="Password">
								</div>
							</div>
						</div>
						<div class="module-foot">
							<div class="control-group">
								<div class="controls clearfix">
									<button type="submit" class="btn btn-primary pull-right" name="submit">Login</button>
									
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div><!--/.wrapper-->

	<div class="footer">
		<div class="container">
			 

			<b class="copyright">&copy; <?php echo date('Y'); ?> <?php echo $_ADMIN_SITE_NAME; ?> </b> All rights reserved.
		</div>
	</div>
	<script src="scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
	<script src="scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>