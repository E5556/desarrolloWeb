<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');

$_sent = false;
$_error = '';

if (isset($_POST['send_contact'])) {
    csrf_verify();
    $ct_name    = safe_str($con, $_POST['ct_name']    ?? '');
    $ct_email   = trim($_POST['ct_email']   ?? '');
    $ct_subject = safe_str($con, $_POST['ct_subject'] ?? '');
    $ct_msg     = safe_str($con, $_POST['ct_msg']     ?? '');

    if (empty($ct_name) || empty($ct_email) || empty($ct_subject) || empty($ct_msg)) {
        $_error = 'Por favor completa todos los campos.';
    } elseif (!filter_var($ct_email, FILTER_VALIDATE_EMAIL)) {
        $_error = 'El correo electronico no es valido.';
    } else {
        mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100), email VARCHAR(180), subject VARCHAR(200),
            message TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, is_read TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = mysqli_prepare($con, "INSERT INTO contact_messages(name,email,subject,message) VALUES(?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $ct_name, $ct_email, $ct_subject, $ct_msg);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        include_once('includes/mailer.php');
        notify_admin('new_contact', ['name'=>$ct_name,'email'=>$ct_email,'subject'=>$ct_subject,'message'=>$ct_msg]);
        $_sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="Contactanos con tus preguntas, sugerencias o comentarios.">
    <title>Contacto | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/cart-drawer.css">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
    .contact-card { background:#fff; border:1px solid #eee; border-radius:10px; padding:32px; box-shadow:0 2px 12px rgba(0,0,0,.07); }
    .contact-info-item { display:flex; align-items:flex-start; gap:14px; margin-bottom:22px; }
    .contact-info-icon { width:40px; height:40px; border-radius:50%; background:#fdf0f5; color:#c0396b; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
    .contact-info-text h5 { margin:0 0 3px; font-size:14px; font-weight:700; color:#333; }
    .contact-info-text p { margin:0; font-size:13px; color:#777; }
    .contact-success { text-align:center; padding:36px 20px; }
    .contact-success .cs-icon { font-size:60px; color:#4caf50; margin-bottom:16px; }
    </style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>
<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <ul class="list-inline list-unstyled">
                <li><a href="index2.php">Inicio</a></li>
                <li class="active">Contacto</li>
            </ul>
        </div>
    </div>
</div>
<div class="body-content outer-top-xs">
    <div class="container">
        <div class="row inner-bottom-sm">
            <div class="col-md-4">
                <div class="contact-card" style="margin-bottom:20px">
                    <h3 style="margin-bottom:24px;color:#333"><i class="fa fa-map-marker" style="color:#c0396b;margin-right:8px"></i>Informacion de contacto</h3>
                    <?php
                    $cfg_c = [];
                    $rr = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('footer_city','footer_phone','footer_email')");
                    while ($rw = mysqli_fetch_assoc($rr)) $cfg_c[$rw['setting_key']] = $rw['setting_value'];
                    ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-map-marker"></i></div>
                        <div class="contact-info-text">
                            <h5>Ubicacion</h5>
                            <p><?php echo htmlspecialchars($cfg_c['footer_city'] ?? 'Bogota, Colombia'); ?></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-phone"></i></div>
                        <div class="contact-info-text">
                            <h5>Telefono</h5>
                            <p><?php echo htmlspecialchars($cfg_c['footer_phone'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-envelope"></i></div>
                        <div class="contact-info-text">
                            <h5>Email</h5>
                            <p><a href="mailto:<?php echo htmlspecialchars($cfg_c['footer_email'] ?? ''); ?>" style="color:#c0396b"><?php echo htmlspecialchars($cfg_c['footer_email'] ?? ''); ?></a></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-clock-o"></i></div>
                        <div class="contact-info-text">
                            <h5>Horario de atencion</h5>
                            <p>Lun - Vie: 8:00am - 6:00pm<br>Sabados: 9:00am - 2:00pm</p>
                        </div>
                    </div>
                    <div class="contact-info-item" style="margin-bottom:0">
                        <div class="contact-info-icon"><i class="fa fa-whatsapp"></i></div>
                        <div class="contact-info-text">
                            <h5>WhatsApp</h5>
                            <p><a href="https://wa.me/<?php echo preg_replace('/\D/','', $cfg_c['footer_phone'] ?? ''); ?>" target="_blank" style="color:#25D366">Escríbenos por WhatsApp</a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="contact-card">
                    <?php if ($_sent): ?>
                    <div class="contact-success">
                        <div class="cs-icon"><i class="fa fa-check-circle"></i></div>
                        <h3 style="color:#333">Mensaje enviado</h3>
                        <p style="color:#888;margin-bottom:24px">Gracias por contactarnos. Te responderemos a la brevedad posible.</p>
                        <a href="index2.php" class="btn btn-primary"><i class="fa fa-home" style="margin-right:6px"></i>Ir al inicio</a>
                        <a href="contact.php" class="btn btn-default" style="margin-left:8px">Enviar otro mensaje</a>
                    </div>
                    <?php else: ?>
                    <h3 style="margin-bottom:24px;color:#333"><i class="fa fa-envelope-o" style="color:#c0396b;margin-right:8px"></i>Envianos un mensaje</h3>
                    <?php if (!empty($_error)): ?>
                    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($_error); ?></div>
                    <?php endif; ?>
                    <form method="post" class="register-form">
                        <?php echo csrf_field(); ?>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="info-title">Nombre <span>*</span></label>
                                    <input type="text" name="ct_name" class="form-control unicase-form-control text-input" required value="<?php echo htmlspecialchars($_POST['ct_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="info-title">Email <span>*</span></label>
                                    <input type="email" name="ct_email" class="form-control unicase-form-control text-input" required value="<?php echo htmlspecialchars($_POST['ct_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="info-title">Asunto <span>*</span></label>
                            <input type="text" name="ct_subject" class="form-control unicase-form-control text-input" required value="<?php echo htmlspecialchars($_POST['ct_subject'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="info-title">Mensaje <span>*</span></label>
                            <textarea name="ct_msg" rows="6" class="form-control unicase-form-control text-input" required style="resize:vertical"><?php echo htmlspecialchars($_POST['ct_msg'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="send_contact" class="btn btn-primary btn-upper" style="padding:10px 28px">
                            <i class="fa fa-paper-plane" style="margin-right:6px"></i>Enviar mensaje
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('includes/footer.php');?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/cookies.js"></script>
</body>
</html>
