<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
include('includes/mailer.php');

// Oculta parcialmente un email: juan@gmail.com → ju***@gmail.com
function _email_hint(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return '***';
    $local  = $parts[0];
    $domain = $parts[1];
    $show   = substr($local, 0, min(2, strlen($local)));
    return $show . '***@' . $domain;
}

// ── Reiniciar flujo ──────────────────────────────────────────────────────────
if (isset($_GET['restart'])) {
    unset($_SESSION['fp_step'], $_SESSION['fp_user_id'],
          $_SESSION['fp_email_hint'], $_SESSION['fp_phone_hint'], $_SESSION['fp_dev_otp']);
    header('location: forgot-password.php');
    exit();
}

// ── Validar estado de sesión — evita quedarse atrapado en paso 2 o 3 sin usuario ──
$_fp_step = (int)($_SESSION['fp_step'] ?? 1);
if ($_fp_step >= 2 && empty($_SESSION['fp_user_id'])) {
    // Sesión de flujo anterior sin usuario válido → reiniciar
    unset($_SESSION['fp_step'], $_SESSION['fp_email_hint'], $_SESSION['fp_dev_otp']);
    $_fp_step = 1;
}
$_SESSION['fp_step'] = $_fp_step;

$current_step = $_fp_step;
$step_error   = '';

// ────────────────────────────────────────────────────────────────────────────
// HANDLER STEP 1 — Verificar email + teléfono, generar y enviar OTP
// ────────────────────────────────────────────────────────────────────────────
if (isset($_POST['step']) && $_POST['step'] === '1') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    $stmt = mysqli_prepare($con, "SELECT id, email FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    // Mismo mensaje tanto si existe como si no (evita enumeración de emails)
    if (!$user) {
        $_SESSION['fp_step']       = 2;
        $_SESSION['fp_user_id']    = 0;
        $_SESSION['fp_email_hint'] = _email_hint($email);
        $_SESSION['fp_dev_otp']    = null;
        header('location: forgot-password.php');
        exit();
    }

    // Generar OTP de 6 dígitos
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Invalidar OTPs previos del usuario
    $stmt_inv = mysqli_prepare($con, "UPDATE password_otp SET used=1 WHERE user_id=? AND used=0");
    mysqli_stmt_bind_param($stmt_inv, 'i', $user['id']);
    mysqli_stmt_execute($stmt_inv);
    mysqli_stmt_close($stmt_inv);

    // Insertar nuevo OTP (hash bcrypt)
    $otp_hash   = password_hash($otp, PASSWORD_BCRYPT);
    $expires_at = date('Y-m-d H:i:s', time() + 600); // +10 minutos
    $stmt_ins   = mysqli_prepare($con,
        "INSERT INTO password_otp (user_id, otp_hash, expires_at) VALUES (?,?,?)");
    mysqli_stmt_bind_param($stmt_ins, 'iss', $user['id'], $otp_hash, $expires_at);
    mysqli_stmt_execute($stmt_ins);
    mysqli_stmt_close($stmt_ins);

    // Enviar OTP por correo
    $mail_result = send_email_otp($con, $email, $otp);

    if (!$mail_result['success']) {
        $step_error = 'No se pudo enviar el correo: ' . htmlspecialchars($mail_result['error']);
    } else {
        $_SESSION['fp_step']       = 2;
        $_SESSION['fp_user_id']    = $user['id'];
        $_SESSION['fp_email_hint'] = _email_hint($email);
        if ($mail_result['dev_mode']) {
            $_SESSION['fp_dev_otp'] = $mail_result['otp'];
        } else {
            unset($_SESSION['fp_dev_otp']);
        }
        header('location: forgot-password.php');
        exit();
    }
    $current_step = 1;
}

// ────────────────────────────────────────────────────────────────────────────
// HANDLER STEP 2 — Verificar OTP ingresado
// ────────────────────────────────────────────────────────────────────────────
if (isset($_POST['step']) && $_POST['step'] === '2') {
    csrf_verify();

    if (($current_step) !== 2) {
        header('location: forgot-password.php?restart=1');
        exit();
    }

    $otp_entered = trim($_POST['otp'] ?? '');
    $user_id     = (int)$_SESSION['fp_user_id'];

    // Buscar OTP activo
    $stmt = mysqli_prepare($con,
        "SELECT id, otp_hash, expires_at, attempts, locked_until
         FROM password_otp
         WHERE user_id=? AND used=0
         ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row) {
        $step_error = 'El código ha expirado o ya fue usado. <a href="forgot-password.php?restart=1">Volver a empezar</a>.';
    } elseif ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        $mins = ceil((strtotime($row['locked_until']) - time()) / 60);
        $step_error = "Demasiados intentos fallidos. Espera {$mins} minuto(s) para intentar de nuevo.";
    } elseif (strtotime($row['expires_at']) < time()) {
        // OTP expirado → marcarlo como usado
        $stmt_exp = mysqli_prepare($con, "UPDATE password_otp SET used=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt_exp, 'i', $row['id']);
        mysqli_stmt_execute($stmt_exp);
        mysqli_stmt_close($stmt_exp);
        $step_error = 'El código ha expirado. <a href="forgot-password.php?restart=1">Solicita uno nuevo</a>.';
    } elseif (!password_verify($otp_entered, $row['otp_hash'])) {
        // OTP incorrecto
        $new_attempts = $row['attempts'] + 1;
        if ($new_attempts >= 3) {
            $lock_until = date('Y-m-d H:i:s', time() + 900); // bloqueo 15 min
            $stmt_lk = mysqli_prepare($con,
                "UPDATE password_otp SET attempts=?, locked_until=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_lk, 'isi', $new_attempts, $lock_until, $row['id']);
            mysqli_stmt_execute($stmt_lk);
            mysqli_stmt_close($stmt_lk);
            $step_error = 'Código incorrecto. Demasiados intentos. Bloqueado 15 minutos.';
        } else {
            $remaining = 3 - $new_attempts;
            $stmt_at = mysqli_prepare($con, "UPDATE password_otp SET attempts=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_at, 'ii', $new_attempts, $row['id']);
            mysqli_stmt_execute($stmt_at);
            mysqli_stmt_close($stmt_at);
            $step_error = "Código incorrecto. Te quedan {$remaining} intento(s).";
        }
    } else {
        // OTP correcto
        $stmt_ok = mysqli_prepare($con, "UPDATE password_otp SET used=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt_ok, 'i', $row['id']);
        mysqli_stmt_execute($stmt_ok);
        mysqli_stmt_close($stmt_ok);
        unset($_SESSION['fp_dev_otp']);
        $_SESSION['fp_step'] = 3;
        header('location: forgot-password.php');
        exit();
    }
    // Mantener en step 2 si hubo error
    $current_step = 2;
}

// ────────────────────────────────────────────────────────────────────────────
// HANDLER STEP 3 — Guardar nueva contraseña
// ────────────────────────────────────────────────────────────────────────────
if (isset($_POST['step']) && $_POST['step'] === '3') {
    csrf_verify();

    if ($current_step !== 3) {
        header('location: forgot-password.php?restart=1');
        exit();
    }

    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirmpassword'] ?? '';
    $user_id   = (int)$_SESSION['fp_user_id'];

    if ($password !== $confirm) {
        $step_error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $step_error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $new_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt_up  = mysqli_prepare($con, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_up, 'si', $new_hash, $user_id);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        // Limpiar sesión OTP
        session_regenerate_id(true);
        unset($_SESSION['fp_step'], $_SESSION['fp_user_id'],
              $_SESSION['fp_email_hint'], $_SESSION['fp_dev_otp']);

        $_SESSION['errmsg'] = 'Contraseña cambiada exitosamente. Ya puedes iniciar sesión.';
        header('location: login.php');
        exit();
    }
    $current_step = 3;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Olvidó su contraseña | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.css">
    <link rel="stylesheet" href="assets/css/owl.transitions.css">
    <link href="assets/css/lightbox.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/rateit.css">
    <link rel="stylesheet" href="assets/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link href="assets/css/green.css"     rel="alternate stylesheet" title="Green color">
    <link href="assets/css/blue.css"      rel="alternate stylesheet" title="Blue color">
    <link href="assets/css/red.css"       rel="alternate stylesheet" title="Red color">
    <link href="assets/css/orange.css"    rel="alternate stylesheet" title="Orange color">
    <link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link href="http://fonts.googleapis.com/css?family=Roboto:300,400,500,700" rel="stylesheet">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
        .otp-steps { display:flex; gap:0; margin-bottom:20px; list-style:none; padding:0; }
        .otp-steps li { flex:1; text-align:center; padding:8px 4px; font-size:12px; font-weight:600;
                        background:#f5f5f5; border:1px solid #ddd; color:#999; }
        .otp-steps li.active { background:#5cb85c; border-color:#4cae4c; color:#fff; }
        .otp-steps li.done   { background:#d4edda; border-color:#c3e6cb; color:#3c763d; }
        .dev-banner { background:#fff3cd; border:1px solid #ffc107; border-radius:4px;
                      padding:12px 15px; margin-bottom:15px; }
        .dev-banner strong { display:block; margin-bottom:4px; }
        .dev-banner .otp-code { font-size:28px; font-weight:700; letter-spacing:6px;
                                color:#856404; font-family:monospace; }
    </style>
    <script>
    function valid() {
        if (document.getElementById('password').value !==
            document.getElementById('confirmpassword').value) {
            showToast('Las contraseñas no coinciden.','error');
            document.getElementById('confirmpassword').focus();
            return false;
        }
        if (document.getElementById('password').value.length < 8) {
            showToast('La contraseña debe tener al menos 8 caracteres.','warning');
            document.getElementById('password').focus();
            return false;
        }
        return true;
    }
    </script>
</head>
<body class="cnt-home">

<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <ul class="list-inline list-unstyled">
                <li><a href="index2.php">Inicio</a></li>
                <li class="active">Olvidó su contraseña</li>
            </ul>
        </div>
    </div>
</div>

<div class="body-content outer-top-bd">
    <div class="container">
        <div class="sign-in-page inner-bottom-sm">
            <div class="row">
                <div class="col-md-6 col-sm-8 col-sm-offset-2 col-md-offset-3 sign-in">

                    <h4>Recuperar contraseña</h4>

                    <!-- Indicador de pasos -->
                    <ul class="otp-steps">
                        <li class="<?php echo $current_step == 1 ? 'active' : ($current_step > 1 ? 'done' : ''); ?>">
                            1. Verificar identidad
                        </li>
                        <li class="<?php echo $current_step == 2 ? 'active' : ($current_step > 2 ? 'done' : ''); ?>">
                            2. Código por correo
                        </li>
                        <li class="<?php echo $current_step == 3 ? 'active' : ''; ?>">
                            3. Nueva contraseña
                        </li>
                    </ul>

                    <?php if ($step_error): ?>
                    <div class="alert alert-danger">
                        <?php echo $step_error; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['errmsg'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['errmsg']); ?>
                        <?php $_SESSION['errmsg'] = ''; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ═══════════════════════ STEP 1 ═══════════════════════ -->
                    <?php if ($current_step === 1): ?>
                    <p class="text-muted" style="margin-bottom:16px;">
                        Ingresa tu correo electrónico registrado.<br>
                        Te enviaremos un código de verificación a ese correo.
                    </p>
                    <form class="register-form outer-top-xs" method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="step" value="1">
                        <div class="form-group">
                            <label class="info-title">Correo electrónico <span>*</span></label>
                            <input type="email" name="email"
                                   class="form-control unicase-form-control text-input"
                                   required autocomplete="email">
                        </div>
                        <button type="submit"
                                class="btn-upper btn btn-primary checkout-page-button">
                            <i class="fa fa-envelope"></i> Enviar código por correo
                        </button>
                    </form>

                    <!-- ═══════════════════════ STEP 2 ═══════════════════════ -->
                    <?php elseif ($current_step === 2): ?>
                    <p class="text-muted" style="margin-bottom:10px;">
                        Enviamos un código de 6 dígitos al correo
                        <strong><?php echo htmlspecialchars($_SESSION['fp_email_hint'] ?? ''); ?></strong>.
                        Revisa también la carpeta de spam.
                    </p>

                    <?php if (!empty($_SESSION['fp_dev_otp'])): ?>
                    <div class="dev-banner">
                        <strong><i class="fa fa-wrench"></i> Modo desarrollo (sin configuración SMTP)</strong>
                        El código que llegaría al correo es:
                        <div class="otp-code"><?php echo htmlspecialchars($_SESSION['fp_dev_otp']); ?></div>
                        <small>Configura el SMTP en <a href="admin/settings.php">admin → Configuración</a> para envío real.</small>
                    </div>
                    <?php endif; ?>

                    <form class="register-form outer-top-xs" method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="step" value="2">
                        <div class="form-group">
                            <label class="info-title">Código de verificación <span>*</span></label>
                            <input type="text" name="otp"
                                   class="form-control unicase-form-control text-input"
                                   maxlength="6" pattern="\d{6}"
                                   required autocomplete="one-time-code"
                                   placeholder="000000"
                                   style="font-size:24px;letter-spacing:8px;text-align:center;">
                        </div>
                        <button type="submit"
                                class="btn-upper btn btn-primary checkout-page-button">
                            Verificar código
                        </button>
                        <a href="forgot-password.php?restart=1"
                           class="btn btn-default" style="margin-left:8px;">
                            <i class="fa fa-arrow-left"></i> Volver
                        </a>
                    </form>

                    <!-- ═══════════════════════ STEP 3 ═══════════════════════ -->
                    <?php elseif ($current_step === 3): ?>
                    <p class="text-muted" style="margin-bottom:16px;">
                        Identidad verificada. Ingresa tu nueva contraseña.
                    </p>
                    <form class="register-form outer-top-xs" method="post"
                          onsubmit="return valid();">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="step" value="3">
                        <div class="form-group">
                            <label class="info-title">Nueva contraseña <span>*</span></label>
                            <input type="password" name="password" id="password"
                                   class="form-control unicase-form-control text-input"
                                   minlength="8" required>
                            <small class="text-muted">Mínimo 8 caracteres.</small>
                        </div>
                        <div class="form-group">
                            <label class="info-title">Confirmar contraseña <span>*</span></label>
                            <input type="password" name="confirmpassword" id="confirmpassword"
                                   class="form-control unicase-form-control text-input"
                                   minlength="8" required>
                        </div>
                        <button type="submit"
                                class="btn-upper btn btn-primary checkout-page-button">
                            <i class="fa fa-lock"></i> Cambiar contraseña
                        </button>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php include('includes/brands-slider.php'); ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/bootstrap-hover-dropdown.min.js"></script>
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/echo.min.js"></script>
<script src="assets/js/jquery.easing-1.3.min.js"></script>
<script src="assets/js/bootstrap-slider.min.js"></script>
<script src="assets/js/jquery.rateit.min.js"></script>
<script src="assets/js/lightbox.min.js"></script>
<script src="assets/js/bootstrap-select.min.js"></script>
<script src="assets/js/scripts.js"></script>
	<script src="assets/js/toast.js"></script>
<script>
    $(document).ready(function(){
        if ($.fn.switchstylesheet) $(".changecolor").switchstylesheet({ seperator:"color" });
        $('.show-theme-options').click(function(){
            $(this).parent().toggleClass('open');
            return false;
        });
    });
    $(window).bind("load", function() {
        $('.show-theme-options').delay(2000).trigger('click');
    });
</script>
<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/cookies.js"></script>
</body>
</html>
