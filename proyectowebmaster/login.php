<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
include('includes/mailer.php');

// Helper: ocultar email parcialmente
function _lotp_hint(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return '***';
    return substr($parts[0], 0, min(2, strlen($parts[0]))) . '***@' . $parts[1];
}

// Reiniciar flujo OTP login
if (isset($_GET['lotp_restart'])) {
    unset($_SESSION['lotp_step'], $_SESSION['lotp_uid'],
          $_SESSION['lotp_hint'], $_SESSION['lotp_dev']);
    header('location: login.php');
    exit();
}

// Validar estado OTP — si step=2 sin uid, resetear
if ((int)($_SESSION['lotp_step'] ?? 0) === 2 && empty($_SESSION['lotp_uid'])) {
    unset($_SESSION['lotp_step'], $_SESSION['lotp_hint'], $_SESSION['lotp_dev']);
}

$lotp_step  = (int)($_SESSION['lotp_step'] ?? 0);
$lotp_error = '';

// ── Programa de referidos: asegurar columna referral_code en users ───────────
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_code VARCHAR(12) DEFAULT NULL");
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS referred_by INT DEFAULT NULL");
// Guardar código de referido en sesión si viene en URL
if (!empty($_GET['ref'])) $_SESSION['ref_code'] = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['ref']));

// ── Registro de usuario ───────────────────────────────────────────────────────
if (isset($_POST['submit'])) {
    csrf_verify();
    $name      = safe_str($con, $_POST['fullname']);
    $email     = safe_str($con, $_POST['emailid']);
    $contactno = safe_str($con, $_POST['contactno']);
    $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Generar código único de referido
    do {
        $ref_code = strtoupper(substr(md5(uniqid($email, true)), 0, 8));
        $rc_check = mysqli_query($con, "SELECT id FROM users WHERE referral_code='$ref_code' LIMIT 1");
    } while (mysqli_num_rows($rc_check) > 0);

    // Referido desde ?ref=CODE
    $referred_by = null;
    $ref_input = trim($_GET['ref'] ?? $_SESSION['ref_code'] ?? '');
    if ($ref_input) {
        $ref_q = mysqli_query($con, "SELECT id FROM users WHERE referral_code='" . mysqli_real_escape_string($con, $ref_input) . "' LIMIT 1");
        if ($ref_q && $ref_row = mysqli_fetch_assoc($ref_q)) $referred_by = intval($ref_row['id']);
    }

    $stmt = mysqli_prepare($con, "INSERT INTO users(name,email,contactno,password,referral_code,referred_by) VALUES(?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $contactno, $password, $ref_code, $referred_by);
    if (mysqli_stmt_execute($stmt)) {
        $new_uid = mysqli_insert_id($con);
        // Dar puntos al referidor
        if ($referred_by) {
            include_once('includes/points.php');
            ps_points_add($con, $referred_by, 50, 'Referido registrado — nuevo usuario #' . $new_uid);
        }
        echo "<script>showToast('Registro exitoso. Ya puedes iniciar sesión.','success');</script>";
    } else {
        echo "<script>showToast('Error en el registro, inténtalo de nuevo.','error');</script>";
    }
    mysqli_stmt_close($stmt);
}

// ── Login con email + contraseña ─────────────────────────────────────────────
if (isset($_POST['login'])) {
    csrf_verify();
    // Brute force: max 10 intentos en 5 minutos por IP
    if (rate_limit_check('login', 10, 300)) {
        $_SESSION['errmsg'] = 'Demasiados intentos. Espera 5 minutos antes de intentar de nuevo.';
        header('location: login.php'); exit();
    }
    $email = safe_str($con, $_POST['email']);

    $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $num = mysqli_fetch_array(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($num && $num['password'] !== '' && password_verify($_POST['password'], $num['password'])) {
        rate_limit_reset('login');
        session_regenerate_id(true);
        $_SESSION['login']    = $email;
        $_SESSION['id']       = $num['id'];
        $_SESSION['username'] = $num['name'];
        include_once('includes/cart.php'); ps_cart_load($con, intval($num['id']));
        $uip = safe_str($con, $_SERVER['REMOTE_ADDR']); $status = 1;
        $s2  = mysqli_prepare($con, "INSERT INTO userlog(userEmail,userip,status) VALUES(?,?,?)");
        mysqli_stmt_bind_param($s2, 'ssi', $email, $uip, $status);
        mysqli_stmt_execute($s2); mysqli_stmt_close($s2);
        header('location: my-cart.php'); exit();
    } else {
        $uip = safe_str($con, $_SERVER['REMOTE_ADDR']); $status = 0;
        $s2  = mysqli_prepare($con, "INSERT INTO userlog(userEmail,userip,status) VALUES(?,?,?)");
        mysqli_stmt_bind_param($s2, 'ssi', $email, $uip, $status);
        mysqli_stmt_execute($s2); mysqli_stmt_close($s2);
        $_SESSION['errmsg'] = 'Correo o contraseña incorrectos.';
        header('location: login.php'); exit();
    }
}

// ── OTP Login — Paso 1: enviar código ────────────────────────────────────────
if (isset($_POST['lotp_send'])) {
    csrf_verify();
    $email = trim($_POST['lotp_email'] ?? '');

    $stmt = mysqli_prepare($con, "SELECT id, email FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$user) {
        $lotp_error = 'No existe ninguna cuenta con ese correo. <a href="login.php">Regístrate</a>.';
    } else {
        $otp      = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $expires  = date('Y-m-d H:i:s', time() + 600);

        // Invalidar OTPs previos
        $si = mysqli_prepare($con, "UPDATE password_otp SET used=1 WHERE user_id=? AND used=0");
        mysqli_stmt_bind_param($si, 'i', $user['id']); mysqli_stmt_execute($si); mysqli_stmt_close($si);

        // Insertar nuevo OTP
        $si2 = mysqli_prepare($con,
            "INSERT INTO password_otp (user_id, otp_hash, expires_at) VALUES (?,?,?)");
        mysqli_stmt_bind_param($si2, 'iss', $user['id'], $otp_hash, $expires);
        mysqli_stmt_execute($si2); mysqli_stmt_close($si2);

        $result = send_email_otp($con, $email, $otp);

        if (!$result['success']) {
            $lotp_error = 'No se pudo enviar el código: ' . htmlspecialchars($result['error']);
        } else {
            $_SESSION['lotp_step'] = 2;
            $_SESSION['lotp_uid']  = $user['id'];
            $_SESSION['lotp_hint'] = _lotp_hint($email);
            $_SESSION['lotp_dev']  = $result['dev_mode'] ? $result['otp'] : null;
            header('location: login.php'); exit();
        }
    }
}

// ── OTP Login — Paso 2: verificar código ─────────────────────────────────────
if (isset($_POST['lotp_verify'])) {
    csrf_verify();

    if ($lotp_step !== 2 || empty($_SESSION['lotp_uid'])) {
        header('location: login.php?lotp_restart=1'); exit();
    }

    $otp_entered = trim($_POST['otp'] ?? '');
    $user_id     = (int)$_SESSION['lotp_uid'];

    $stmt = mysqli_prepare($con,
        "SELECT id, otp_hash, expires_at, attempts, locked_until
         FROM password_otp WHERE user_id=? AND used=0 ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        $lotp_error = 'El código expiró o ya fue usado. <a href="login.php?lotp_restart=1">Solicita uno nuevo</a>.';
    } elseif ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        $mins = ceil((strtotime($row['locked_until']) - time()) / 60);
        $lotp_error = "Demasiados intentos. Espera {$mins} minuto(s).";
    } elseif (strtotime($row['expires_at']) < time()) {
        $su = mysqli_prepare($con, "UPDATE password_otp SET used=1 WHERE id=?");
        mysqli_stmt_bind_param($su, 'i', $row['id']); mysqli_stmt_execute($su); mysqli_stmt_close($su);
        $lotp_error = 'El código expiró. <a href="login.php?lotp_restart=1">Solicita uno nuevo</a>.';
    } elseif (!password_verify($otp_entered, $row['otp_hash'])) {
        $new_att = $row['attempts'] + 1;
        if ($new_att >= 3) {
            $lock = date('Y-m-d H:i:s', time() + 900);
            $su2  = mysqli_prepare($con, "UPDATE password_otp SET attempts=?, locked_until=? WHERE id=?");
            mysqli_stmt_bind_param($su2, 'isi', $new_att, $lock, $row['id']);
            mysqli_stmt_execute($su2); mysqli_stmt_close($su2);
            $lotp_error = 'Código incorrecto. Cuenta bloqueada 15 minutos.';
        } else {
            $rem = 3 - $new_att;
            $su3 = mysqli_prepare($con, "UPDATE password_otp SET attempts=? WHERE id=?");
            mysqli_stmt_bind_param($su3, 'ii', $new_att, $row['id']);
            mysqli_stmt_execute($su3); mysqli_stmt_close($su3);
            $lotp_error = "Código incorrecto. Te quedan {$rem} intento(s).";
        }
    } else {
        // ✅ Código correcto
        $sok = mysqli_prepare($con, "UPDATE password_otp SET used=1 WHERE id=?");
        mysqli_stmt_bind_param($sok, 'i', $row['id']); mysqli_stmt_execute($sok); mysqli_stmt_close($sok);

        $su4 = mysqli_prepare($con, "SELECT * FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($su4, 'i', $user_id);
        mysqli_stmt_execute($su4);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($su4));
        mysqli_stmt_close($su4);

        session_regenerate_id(true);
        $_SESSION['login']    = $user['email'];
        $_SESSION['id']       = $user['id'];
        $_SESSION['username'] = $user['name'];
        include_once('includes/cart.php'); ps_cart_load($con, intval($user['id']));
        unset($_SESSION['lotp_step'], $_SESSION['lotp_uid'],
              $_SESSION['lotp_hint'], $_SESSION['lotp_dev']);

        $uip = $_SERVER['REMOTE_ADDR']; $status = 1;
        $sl  = mysqli_prepare($con, "INSERT INTO userlog(userEmail,userip,status) VALUES(?,?,?)");
        mysqli_stmt_bind_param($sl, 'ssi', $user['email'], $uip, $status);
        mysqli_stmt_execute($sl); mysqli_stmt_close($sl);

        header('location: my-cart.php'); exit();
    }
}

// Leer si Google OAuth está activo
$_goog_res = mysqli_query($con,
    "SELECT setting_value FROM settings WHERE setting_key='google_oauth_enabled' LIMIT 1");
$_goog_row    = mysqli_fetch_assoc($_goog_res);
$google_enabled = ($_goog_row && $_goog_row['setting_value'] === '1');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="robots" content="all">
    <title>Login | <?php echo $_SITE_NAME; ?></title>

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
    <link href="assets/css/green.css"      rel="alternate stylesheet" title="Green color">
    <link href="assets/css/blue.css"       rel="alternate stylesheet" title="Blue color">
    <link href="assets/css/red.css"        rel="alternate stylesheet" title="Red color">
    <link href="assets/css/orange.css"     rel="alternate stylesheet" title="Orange color">
    <link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link href="http://fonts.googleapis.com/css?family=Roboto:300,400,500,700" rel="stylesheet">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
    .btn-google {
        display: -webkit-box; display: -ms-flexbox; display: flex;
        -webkit-box-align: center; -ms-flex-align: center; align-items: center;
        -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center;
        gap: 10px; width: 100%; padding: 10px 16px;
        border: 1.5px solid #dadce0; border-radius: 4px;
        background: #fff; color: #3c4043; font-size: 14px; font-weight: 500;
        cursor: pointer; margin-bottom: 14px; text-decoration: none;
        -webkit-transition: background 0.2s, box-shadow 0.2s;
        transition: background 0.2s, box-shadow 0.2s; font-family: inherit;
    }
    .btn-google:hover { background: #f8f9fa; box-shadow: 0 1px 4px rgba(0,0,0,.15); color: #3c4043; text-decoration: none; }
    .btn-google svg  { width: 20px; height: 20px; -ms-flex-negative: 0; flex-shrink: 0; }
    .or-divider { display: -webkit-box; display: -ms-flexbox; display: flex;
                  -webkit-box-align: center; -ms-flex-align: center; align-items: center;
                  margin: 0 0 14px; color: #aaa; font-size: 12px; }
    .or-divider::before, .or-divider::after {
        content: ''; -webkit-box-flex: 1; -ms-flex: 1; flex: 1;
        height: 1px; background: #e0e0e0; }
    .or-divider::before { margin-right: 10px; }
    .or-divider::after  { margin-left: 10px; }
    .link-otp { font-size: 13px; color: #555; display: block; margin-top: 12px; text-align: center; }
    .link-otp:hover { color: #222; }
    .dev-banner { background:#fff3cd; border:1px solid #ffc107; border-radius:4px;
                  padding:12px 15px; margin-bottom:15px; }
    .dev-banner strong { display:block; margin-bottom:4px; }
    .otp-code { font-size:28px; font-weight:700; letter-spacing:6px;
                color:#856404; font-family:monospace; }
    </style>
    <script>
    function valid() {
        if (document.register.password.value !== document.register.confirmpassword.value) {
            showToast('Las contraseñas no coinciden.','error');
            document.register.confirmpassword.focus();
            return false;
        }
        return true;
    }
    </script>
    <script>
    function userAvailability() {
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "check_availability.php",
            data: 'email=' + $("#email").val(),
            type: "POST",
            success: function(data) { $("#user-availability-status1").html(data); $("#loaderIcon").hide(); },
            error: function() {}
        });
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
                <li class="active">Autenticación</li>
            </ul>
        </div>
    </div>
</div>

<div class="body-content outer-top-bd">
    <div class="container">
        <div class="sign-in-page inner-bottom-sm">
            <div class="row">

<?php if ($lotp_step === 2): ?>
<!-- ═══════════════════════ PASO 2: Ingresar código OTP ═══════════════════════ -->
<div class="col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 sign-in">
    <h4>Ingresa tu código</h4>
    <p class="text-muted">
        Enviamos un código de 6 dígitos a
        <strong><?php echo htmlspecialchars($_SESSION['lotp_hint'] ?? ''); ?></strong>.
        Revisa también la carpeta de spam.
    </p>

    <?php if (!empty($_SESSION['lotp_dev'])): ?>
    <div class="dev-banner">
        <strong><i class="fa fa-wrench"></i> Modo desarrollo (sin SMTP configurado)</strong>
        El código que llegaría al correo es:
        <div class="otp-code"><?php echo htmlspecialchars($_SESSION['lotp_dev']); ?></div>
        <small>Configura SMTP en <a href="admin/settings.php">admin → Configuración</a>.</small>
    </div>
    <?php endif; ?>

    <?php if ($lotp_error): ?>
    <div class="alert alert-danger"><?php echo $lotp_error; ?></div>
    <?php endif; ?>

    <form class="register-form outer-top-xs" method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label class="info-title">Código de verificación <span>*</span></label>
            <input type="text" name="otp" class="form-control unicase-form-control text-input"
                   maxlength="6" pattern="\d{6}" required autocomplete="one-time-code"
                   placeholder="000000"
                   style="font-size:24px;letter-spacing:8px;text-align:center;" autofocus>
        </div>
        <button type="submit" name="lotp_verify"
                class="btn-upper btn btn-primary checkout-page-button">
            <i class="fa fa-check"></i> Verificar código
        </button>
        <a href="login.php?lotp_restart=1" class="link-otp">
            <i class="fa fa-arrow-left"></i> Usar otro correo
        </a>
    </form>
</div>

<?php else: ?>
<!-- ═══════════════════════ VISTA NORMAL: Login + Registro ═══════════════════════ -->

                <!-- Sign-in -->
                <div class="col-md-6 col-sm-6 sign-in">
                    <h4>Iniciar sesión</h4>
                    <p>Hola, Bienvenido</p>

                    <?php if ($google_enabled): ?>
                    <a href="oauth-google.php" class="btn-google">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Continuar con Google
                    </a>
                    <div class="or-divider">o</div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['flash_info'])): ?>
                    <div class="alert alert-info" style="border-radius:6px;">
                        <i class="fa fa-info-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['flash_info']); unset($_SESSION['flash_info']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['errmsg'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_SESSION['errmsg']); $_SESSION['errmsg'] = ''; ?>
                    </div>
                    <?php else: $_SESSION['errmsg'] = ''; endif; ?>

                    <form class="register-form outer-top-xs" method="post">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="info-title">Email <span>*</span></label>
                            <input type="email" name="email"
                                   class="form-control unicase-form-control text-input">
                        </div>
                        <div class="form-group">
                            <label class="info-title">Contraseña <span>*</span></label>
                            <input type="password" name="password"
                                   class="form-control unicase-form-control text-input">
                        </div>
                        <div class="radio outer-xs">
                            <a href="forgot-password.php" class="forgot-password pull-right">
                                ¿Olvidó su contraseña?
                            </a>
                        </div>
                        <button type="submit" name="login"
                                class="btn-upper btn btn-primary checkout-page-button">
                            Iniciar sesión
                        </button>
                    </form>

                    <?php if ($lotp_error): ?>
                    <div class="alert alert-danger" style="margin-top:10px;"><?php echo $lotp_error; ?></div>
                    <?php endif; ?>

                    <!-- Formulario OTP email -->
                    <div style="margin-top:16px;padding-top:14px;border-top:1px solid #eee;">
                        <p style="font-size:13px;color:#777;margin-bottom:8px;">
                            ¿Prefieres iniciar sin contraseña?
                        </p>
                        <form method="post" style="display:flex;gap:8px;-ms-flex-wrap:wrap;flex-wrap:wrap;">
                            <?php echo csrf_field(); ?>
                            <input type="email" name="lotp_email"
                                   class="form-control unicase-form-control text-input"
                                   placeholder="Tu correo electrónico"
                                   style="-webkit-box-flex:1;-ms-flex:1;flex:1;min-width:180px;"
                                   required>
                            <button type="submit" name="lotp_send"
                                    class="btn btn-default"
                                    style="white-space:nowrap;">
                                <i class="fa fa-envelope"></i> Enviar código
                            </button>
                        </form>
                    </div>
                </div>
                <!-- /Sign-in -->

                <!-- Crear cuenta -->
                <div class="col-md-6 col-sm-6 create-new-account">
                    <h4 class="checkout-subtitle">Crear cuenta</h4>
                    <p class="text title-tag-line">Crea tu cuenta en <?php echo $_SITE_NAME; ?>.</p>
                    <form class="register-form outer-top-xs" role="form" method="post"
                          name="register" onsubmit="return valid();">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="info-title">Nombre completo <span>*</span></label>
                            <input type="text" class="form-control unicase-form-control text-input"
                                   name="fullname" required>
                        </div>
                        <div class="form-group">
                            <label class="info-title">Email <span>*</span></label>
                            <input type="email" class="form-control unicase-form-control text-input"
                                   id="email" name="emailid" onblur="userAvailability()" required>
                            <span id="user-availability-status1" style="font-size:12px;"></span>
                        </div>
                        <div class="form-group">
                            <label class="info-title">Teléfono <span>*</span></label>
                            <input type="text" class="form-control unicase-form-control text-input"
                                   name="contactno" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label class="info-title">Contraseña <span>*</span></label>
                            <input type="password" class="form-control unicase-form-control text-input"
                                   id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label class="info-title">Confirmar contraseña <span>*</span></label>
                            <input type="password" class="form-control unicase-form-control text-input"
                                   id="confirmpassword" name="confirmpassword" required>
                        </div>
                        <button type="submit" name="submit"
                                class="btn-upper btn btn-primary checkout-page-button">
                            Registrarse
                        </button>
                    </form>
                    <span class="checkout-subtitle outer-top-xs">Al registrarte podrás:</span>
                    <div class="checkbox">
                        <label class="checkbox">Acelerar tus compras.</label>
                        <label class="checkbox">Rastrear tus pedidos.</label>
                        <label class="checkbox">Llevar un registro de todas tus compras.</label>
                    </div>
                </div>
                <!-- /Crear cuenta -->

<?php endif; ?>

            </div><!-- /.row -->
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
        $('.show-theme-options').click(function(){ $(this).parent().toggleClass('open'); return false; });
    });
    $(window).bind("load", function() { $('.show-theme-options').delay(2000).trigger('click'); });
</script>
<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/cookies.js"></script>
</body>
</html>
