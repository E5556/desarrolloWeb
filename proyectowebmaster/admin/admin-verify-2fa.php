<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['2fa_pending'])) { header('location:index.php'); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp'] ?? '');
    if (time() > ($_SESSION['2fa_expires'] ?? 0)) {
        $error = 'El código ha expirado. <a href="index.php">Volver a iniciar sesión.</a>';
        unset($_SESSION['2fa_pending'],$_SESSION['2fa_otp'],$_SESSION['2fa_expires'],$_SESSION['2fa_uid'],$_SESSION['2fa_username'],$_SESSION['2fa_role']);
    } elseif (password_verify($otp_input, $_SESSION['2fa_otp'] ?? '')) {
        $uid  = $_SESSION['2fa_uid'];
        $user = $_SESSION['2fa_username'];
        $role = $_SESSION['2fa_role'];
        unset($_SESSION['2fa_pending'],$_SESSION['2fa_otp'],$_SESSION['2fa_expires'],$_SESSION['2fa_uid'],$_SESSION['2fa_username'],$_SESSION['2fa_role']);
        session_regenerate_id(true);
        $_SESSION['alogin'] = $user;
        $_SESSION['id']     = $uid;
        $_SESSION['aid']    = $uid;
        $_SESSION['arole']  = $role;
        include_once('../includes/admin-log.php');
        admin_log($con, 'login_2fa', 'Sesión con 2FA desde ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        header('location:dashboard.php'); exit();
    } else {
        $error = 'Código incorrecto. Inténtalo de nuevo.';
    }
}

$cfg_q = mysqli_query($con,"SELECT setting_value FROM settings WHERE setting_key='site_name' LIMIT 1");
$site  = mysqli_fetch_assoc($cfg_q)['setting_value'] ?? 'Admin';
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verificación 2FA | <?php echo htmlspecialchars($site);?></title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="css/theme.css">
<style>
body{background:#f0f0f0;display:flex;align-items:center;justify-content:center;min-height:100vh}
.box{background:#fff;border-radius:10px;padding:40px;max-width:380px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center}
.otp-input{font-size:2em;letter-spacing:.5em;text-align:center;font-weight:700;border:2px solid #337ab7;border-radius:8px;padding:10px}
</style>
</head><body>
<div class="box">
    <div style="font-size:2.5em;margin-bottom:8px">🔐</div>
    <h3 style="margin:0 0 6px;color:#333">Verificación en dos pasos</h3>
    <p style="color:#888;font-size:.88em;margin-bottom:20px">Ingresa el código de 6 dígitos enviado al correo del administrador.</p>
    <?php if($error):?><div class="alert alert-danger" style="font-size:.85em"><?php echo $error;?></div><?php endif;?>
    <form method="post">
        <input type="text" name="otp" class="otp-input input-block-level" maxlength="6" pattern="\d{6}" required autofocus placeholder="000000">
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;font-size:1.1em">Verificar</button>
    </form>
    <p style="margin-top:14px;font-size:.82em;color:#aaa">El código expira en 10 minutos.</p>
    <a href="index.php" style="font-size:.82em;color:#e8233a">← Volver al login</a>
</div>
</body></html>
