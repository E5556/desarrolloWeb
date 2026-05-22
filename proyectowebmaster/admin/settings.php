<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin'])) {
    header('location:index.php');
    exit();
admin_require_perm('perm_settings');

}

$allowed_exts  = ['jpg','jpeg','png','gif','webp'];
$upload_dir    = '../assets/images/logos/';
$msg = '';
$msg_type = 'success';

if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

if(isset($_POST['submit'])) {

    // Campos de texto simples
    $text_keys = [
        'site_name','currency_symbol','footer_tagline',
        'footer_hours_weekday','footer_hours_saturday','footer_hours_sunday',
        'footer_city','footer_phone','footer_email',
        'social_facebook','social_twitter','social_linkedin','social_rss','social_pinterest','social_tiktok',
        'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','admin_email',
        'review_request_days','cron_token',
        'google_client_id','google_client_secret',
        'deal_title','deal_subtitle','deal_end',
        'low_stock_threshold',
        'mp_access_token',
        'currency_usd_rate','currency_eur_rate','currency_brl_rate',
        'search_synonyms',
        'ga4_measurement_id',
        'meta_pixel_id'
        // maintenance_mode y mp_sandbox se manejan con checkbox separado
    ];
    foreach($text_keys as $key) {
        $val = mysqli_real_escape_string($con, trim($_POST[$key] ?? ''));
        $key_s = mysqli_real_escape_string($con, $key);
        mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('$key_s','$val')
                            ON DUPLICATE KEY UPDATE setting_value='$val'");
    }

    // Logo
    if(!empty($_FILES['site_logo']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['site_logo']['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
        if(!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $msg = 'Solo se permiten imágenes (jpg, png, gif, webp).';
            $msg_type = 'error';
        } else {
            $filename = 'logo_' . time() . '.' . $ext;
            if(move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $filename)) {
                $logo_path = 'assets/images/logos/' . $filename;
                mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('site_logo','$logo_path')
                                    ON DUPLICATE KEY UPDATE setting_value='$logo_path'");
                if(!$msg) $msg = 'Configuración guardada correctamente.';
            } else {
                $msg = 'Error al subir la imagen.'; $msg_type = 'error';
            }
        }
    }

    // Favicon
    if(!empty($_FILES['site_favicon']['name'])) {
        $fav_ext   = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
        $fav_finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fav_mime  = finfo_file($fav_finfo, $_FILES['site_favicon']['tmp_name']);
        finfo_close($fav_finfo);
        $fav_allowed_exts  = ['ico','png','jpg','jpeg','gif','webp','svg'];
        $fav_allowed_mimes = ['image/x-icon','image/vnd.microsoft.icon','image/png',
                              'image/jpeg','image/gif','image/webp','image/svg+xml'];
        if(!in_array($fav_ext, $fav_allowed_exts) || !in_array($fav_mime, $fav_allowed_mimes)) {
            $msg = 'El favicon debe ser ICO, PNG, SVG, JPG, GIF o WEBP.';
            $msg_type = 'error';
        } else {
            $fav_name = 'favicon_' . time() . '.' . $fav_ext;
            if(move_uploaded_file($_FILES['site_favicon']['tmp_name'], $upload_dir . $fav_name)) {
                $fav_path = 'assets/images/logos/' . $fav_name;
                mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('site_favicon','$fav_path')
                                    ON DUPLICATE KEY UPDATE setting_value='$fav_path'");
                if(!$msg) $msg = 'Configuración guardada correctamente.';
            } else {
                $msg = 'Error al subir el favicon.'; $msg_type = 'error';
            }
        }
    }

    // MercadoPago sandbox toggle
    $mp_sandbox_val = isset($_POST['mp_sandbox']) ? '1' : '0';
    mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('mp_sandbox','$mp_sandbox_val')
                        ON DUPLICATE KEY UPDATE setting_value='$mp_sandbox_val'");

    // Modo mantenimiento toggle
    $maint_val = isset($_POST['maintenance_mode']) ? '1' : '0';
    mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('maintenance_mode','$maint_val')
                        ON DUPLICATE KEY UPDATE setting_value='$maint_val'");

    // Countdown deal toggle
    $deal_act_val = isset($_POST['deal_active']) ? '1' : '0';
    mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('deal_active','$deal_act_val')
                        ON DUPLICATE KEY UPDATE setting_value='$deal_act_val'");

    // Google OAuth toggle
    $go_val = isset($_POST['google_oauth_enabled']) ? '1' : '0';
    mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('google_oauth_enabled','$go_val')
                        ON DUPLICATE KEY UPDATE setting_value='$go_val'");

    // Banner de cookies (checkbox — no se envía si está desmarcado)
    $cc_val = isset($_POST['cookie_consent_enabled']) ? '1' : '0';
    mysqli_query($con, "INSERT INTO settings (setting_key,setting_value) VALUES ('cookie_consent_enabled','$cc_val')
                        ON DUPLICATE KEY UPDATE setting_value='$cc_val'");

    if(!$msg) $msg = 'Configuración guardada correctamente.';
}

// Cargar settings actuales
$settings = [];
$res = mysqli_query($con, "SELECT setting_key, setting_value FROM settings");
while($row = mysqli_fetch_assoc($res)) $settings[$row['setting_key']] = $row['setting_value'];

function sv($settings, $key, $default='') {
    return htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Configuración del Sitio</title>
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600' rel='stylesheet'>
    <style>
        .settings-section { background:#f9f9f9; border:1px solid #e0e0e0; border-radius:4px; padding:15px 20px; margin-bottom:20px; }
        .settings-section h4 { margin:0 0 15px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#444; border-bottom:2px solid #ddd; padding-bottom:8px; }
        .social-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .social-row label { width:100px; margin:0; font-size:12px; }
        .social-row input { flex:1; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>

<div class="wrapper">
    <div class="container-fluid">
        <div class="row">
            <?php include('include/sidebar.php'); ?>
            <div class="span9">
                <div class="content">
                    <div class="module">
                        <div class="module-head">
                            <h3><i class="icon-cog"></i> Configuración del Sitio</h3>
                        </div>
                        <div class="module-body">

                            <?php if($msg): ?>
                            <div class="alert alert-<?php echo $msg_type === 'error' ? 'error' : 'success'; ?>">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <?php echo htmlspecialchars($msg); ?>
                            </div>
                            <?php endif; ?>

                            <form class="form-horizontal row-fluid" method="post" enctype="multipart/form-data">

                                <!-- ── IDENTIDAD ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-home"></i> Identidad del sitio</h4>

                                    <div class="control-group">
                                        <label class="control-label">Nombre del sitio</label>
                                        <div class="controls">
                                            <input type="text" name="site_name" class="span8"
                                                   value="<?php echo sv($settings,'site_name','Jade Zapatillas'); ?>" required>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Símbolo de moneda</label>
                                        <div class="controls">
                                            <input type="text" name="currency_symbol" class="span2"
                                                   value="<?php echo sv($settings,'currency_symbol','$'); ?>" maxlength="5">
                                            <span class="help-inline">Ej: $, COP, €, USD</span>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Lema / Tagline</label>
                                        <div class="controls">
                                            <input type="text" name="footer_tagline" class="span8"
                                                   value="<?php echo sv($settings,'footer_tagline','Ofrecemos las mejores zapatillas del mercado'); ?>">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Logo actual</label>
                                        <div class="controls">
                                            <img src="../<?php echo sv($settings,'site_logo','assets/images/oig.jpg'); ?>"
                                                 style="max-width:200px;max-height:120px;border:1px solid #ddd;padding:5px;border-radius:4px;">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Cambiar logo</label>
                                        <div class="controls">
                                            <input type="file" name="site_logo" accept="image/*" class="span8">
                                            <span class="help-block">JPG, PNG, GIF, WEBP. Recomendado: 300×300 px.</span>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Ícono de pestaña (Favicon)</label>
                                        <div class="controls">
                                            <?php
                                            $cur_fav = $settings['site_favicon'] ?? '';
                                            if($cur_fav): ?>
                                            <div style="margin-bottom:8px;">
                                                <img src="../<?php echo htmlspecialchars($cur_fav); ?>"
                                                     style="width:32px;height:32px;object-fit:contain;
                                                            border:1px solid #ddd;padding:3px;border-radius:3px;background:#fff;">
                                                <span style="margin-left:8px;font-size:12px;color:#888;">Favicon actual</span>
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="site_favicon" accept=".ico,.png,.svg,.jpg,.gif,.webp" class="span8">
                                            <span class="help-block">ICO o PNG de 32×32 px (recomendado). También acepta SVG, JPG, GIF, WEBP.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── HORARIOS ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-time"></i> Horarios de atención</h4>

                                    <div class="control-group">
                                        <label class="control-label">Lunes – Viernes</label>
                                        <div class="controls">
                                            <input type="text" name="footer_hours_weekday" class="span6"
                                                   value="<?php echo sv($settings,'footer_hours_weekday','08.00 a 18.00'); ?>" placeholder="08.00 a 18.00">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Sábados</label>
                                        <div class="controls">
                                            <input type="text" name="footer_hours_saturday" class="span6"
                                                   value="<?php echo sv($settings,'footer_hours_saturday','09.00 a 20.00'); ?>" placeholder="09.00 a 20.00">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Domingos</label>
                                        <div class="controls">
                                            <input type="text" name="footer_hours_sunday" class="span6"
                                                   value="<?php echo sv($settings,'footer_hours_sunday','10.00 a 20.00'); ?>" placeholder="10.00 a 20.00">
                                        </div>
                                    </div>
                                </div>

                                <!-- ── CONTACTO ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-map-marker"></i> Información de contacto</h4>

                                    <div class="control-group">
                                        <label class="control-label">Ciudad / Dirección</label>
                                        <div class="controls">
                                            <input type="text" name="footer_city" class="span6"
                                                   value="<?php echo sv($settings,'footer_city','Bogotá'); ?>">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Teléfonos</label>
                                        <div class="controls">
                                            <input type="text" name="footer_phone" class="span6"
                                                   value="<?php echo sv($settings,'footer_phone','3222476963<br>3101234567'); ?>">
                                            <span class="help-block">Separa múltiples números con &lt;br&gt;</span>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Correo electrónico</label>
                                        <div class="controls">
                                            <input type="email" name="footer_email" class="span6"
                                                   value="<?php echo sv($settings,'footer_email','info@modventures.com'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- ── REDES SOCIALES ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-share"></i> Redes sociales</h4>

                                    <?php
                                    $socials = [
                                        'social_facebook'  => ['fa-facebook',  'Facebook'],
                                        'social_twitter'   => ['fa-twitter',   'Twitter'],
                                        'social_linkedin'  => ['fa-linkedin',  'LinkedIn'],
                                        'social_rss'       => ['fa-rss',       'RSS'],
                                        'social_pinterest' => ['fa-pinterest', 'Pinterest'],
                                        'social_tiktok'   => ['fa-music',    'TikTok'],
                                    ];
                                    foreach($socials as $key => [$icon, $label]):
                                    ?>
                                    <div class="social-row">
                                        <label><i class="icon fa <?php echo $icon; ?>"></i> <?php echo $label; ?></label>
                                        <input type="text" name="<?php echo $key; ?>"
                                               value="<?php echo sv($settings, $key, '#'); ?>"
                                               placeholder="https://...">
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- ── GOOGLE OAUTH ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-google-plus"></i> Inicio de sesión con Google</h4>

                                    <div class="control-group">
                                        <label class="control-label">Activar</label>
                                        <div class="controls">
                                            <label class="checkbox">
                                                <input type="checkbox" name="google_oauth_enabled" value="1"
                                                    <?php echo (($settings['google_oauth_enabled'] ?? '0') === '1') ? 'checked' : ''; ?>>
                                                Mostrar botón "Continuar con Google" en el login
                                            </label>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Client ID</label>
                                        <div class="controls">
                                            <input type="text" name="google_client_id" class="span8"
                                                   value="<?php echo sv($settings,'google_client_id',''); ?>"
                                                   placeholder="xxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Client Secret</label>
                                        <div class="controls">
                                            <input type="password" name="google_client_secret" class="span6"
                                                   value="<?php echo sv($settings,'google_client_secret',''); ?>"
                                                   placeholder="GOCSPX-..."
                                                   autocomplete="new-password">
                                        </div>
                                    </div>

                                    <div style="background:#f0f8ff;border:1px solid #bee3f8;border-radius:4px;padding:12px;font-size:12px;">
                                        <strong><i class="icon-info-sign"></i> Cómo obtener las credenciales:</strong>
                                        <ol style="margin:8px 0 0 18px;padding:0;line-height:1.9;">
                                            <li>Ve a <a href="https://console.cloud.google.com" target="_blank">console.cloud.google.com</a> → Crear proyecto</li>
                                            <li>APIs &amp; Services → <strong>Credenciales</strong> → Crear credencial → <strong>ID de cliente OAuth 2.0</strong></li>
                                            <li>Tipo: <strong>Aplicación web</strong> → en "URIs de redireccionamiento autorizados" agrega:<br>
                                                <code style="background:#e8f0fe;padding:2px 6px;border-radius:3px;">
                                                    http://localhost/proyectowebmaster/oauth-callback.php
                                                </code>
                                            </li>
                                            <li>Copia el <strong>Client ID</strong> y el <strong>Client Secret</strong> arriba y guarda.</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- ── COOKIES Y PRIVACIDAD ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-lock"></i> Cookies y privacidad</h4>

                                    <div class="control-group">
                                        <label class="control-label">Banner de cookies</label>
                                        <div class="controls">
                                            <label class="checkbox">
                                                <input type="checkbox" name="cookie_consent_enabled" value="1"
                                                    <?php echo (($settings['cookie_consent_enabled'] ?? '0') === '1') ? 'checked' : ''; ?>>
                                                Mostrar aviso de consentimiento de cookies a nuevos visitantes
                                            </label>
                                            <span class="help-block">
                                                Cuando está activo, aparece un banner en la parte inferior del sitio
                                                donde el visitante puede aceptar, rechazar o personalizar el uso de cookies.
                                                Al desactivarlo, el banner desaparece completamente de todas las páginas.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── COUNTDOWN DEAL ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-time"></i> Oferta con countdown (banner en inicio)</h4>
                                    <p style="color:#666;font-size:12px;margin-bottom:12px;">
                                        Si está activo, aparece un banner rojo con cuenta regresiva en la página de inicio.
                                    </p>

                                    <div class="control-group">
                                        <label class="control-label">Activar banner</label>
                                        <div class="controls">
                                            <?php
                                            $deal_active_val = $settings['deal_active'] ?? '0';
                                            mysqli_query($con, "INSERT IGNORE INTO settings(setting_key,setting_value) VALUES('deal_active','0')");
                                            ?>
                                            <label class="checkbox">
                                                <input type="checkbox" name="deal_active" value="1"
                                                    <?php echo $deal_active_val === '1' ? 'checked' : ''; ?>>
                                                Mostrar banner de cuenta regresiva en la página de inicio
                                            </label>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Título de la oferta</label>
                                        <div class="controls">
                                            <input type="text" name="deal_title" class="span8"
                                                   value="<?php echo sv($settings,'deal_title','¡Oferta del día!'); ?>"
                                                   placeholder="Ej: ¡Oferta del día!">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Subtítulo</label>
                                        <div class="controls">
                                            <input type="text" name="deal_subtitle" class="span8"
                                                   value="<?php echo sv($settings,'deal_subtitle','Descuentos exclusivos por tiempo limitado'); ?>"
                                                   placeholder="Ej: Descuentos exclusivos por tiempo limitado">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Fecha y hora de fin</label>
                                        <div class="controls">
                                            <input type="datetime-local" name="deal_end" class="span6"
                                                   value="<?php echo sv($settings,'deal_end',''); ?>">
                                            <span class="help-block">Cuando llegue esta fecha, el banner desaparece automáticamente.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── MODO MANTENIMIENTO ── -->
                                <div class="settings-section" style="background:#fff8f8;border:1px solid #f5c6cb;border-radius:6px;padding:14px;margin-bottom:16px">
                                    <h4 style="color:#c0392b"><i class="icon-wrench"></i> Modo mantenimiento</h4>
                                    <p style="color:#888;font-size:12px;margin-bottom:10px">
                                        Al activarlo, el sitio público mostrará una página de "En mantenimiento". Los administradores seguirán teniendo acceso al panel.
                                    </p>
                                    <div class="control-group">
                                        <label class="control-label">Estado</label>
                                        <div class="controls">
                                            <?php
                                            $maint_val_cur = $settings['maintenance_mode'] ?? '0';
                                            mysqli_query($con, "INSERT IGNORE INTO settings(setting_key,setting_value) VALUES('maintenance_mode','0')");
                                            ?>
                                            <label class="checkbox" style="color:#c0392b;font-weight:600">
                                                <input type="checkbox" name="maintenance_mode" value="1"
                                                    <?php echo $maint_val_cur === '1' ? 'checked' : ''; ?>>
                                                Activar modo mantenimiento
                                            </label>
                                            <?php if ($maint_val_cur === '1'): ?>
                                            <div class="alert alert-error" style="margin-top:6px;padding:6px 10px;font-size:12px">
                                                <i class="icon-warning-sign"></i> El modo mantenimiento está <strong>ACTIVO</strong>. Los visitantes ven la página de mantenimiento.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── INVENTARIO ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-warning-sign"></i> Alertas de inventario</h4>
                                    <div class="control-group">
                                        <label class="control-label">Umbral stock bajo</label>
                                        <div class="controls">
                                            <input type="number" min="1" name="low_stock_threshold" class="span3"
                                                   value="<?php echo intval(sv($settings,'low_stock_threshold','5')); ?>">
                                            <span class="help-block">El dashboard mostrará una alerta cuando un producto tenga esta cantidad o menos de unidades en stock.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── MERCADOPAGO ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-credit-card"></i> MercadoPago — Pasarela de pagos</h4>
                                    <p style="color:#666;font-size:12px;margin-bottom:12px">
                                        Obtén tu Access Token en <strong>developers.mercadopago.com</strong> → Tu aplicación → Credenciales.<br>
                                        Usa el token de <strong>prueba</strong> (TEST-…) para sandbox y el de <strong>producción</strong> (APP_USR-…) para cobros reales.
                                    </p>
                                    <div class="control-group">
                                        <label class="control-label">Access Token</label>
                                        <div class="controls">
                                            <input type="text" name="mp_access_token" class="span8"
                                                   value="<?php echo sv($settings,'mp_access_token',''); ?>"
                                                   placeholder="TEST-xxxx... o APP_USR-xxxx..."
                                                   autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Modo</label>
                                        <div class="controls">
                                            <?php $mp_sb = $settings['mp_sandbox'] ?? '1';
                                            mysqli_query($con, "INSERT IGNORE INTO settings(setting_key,setting_value) VALUES('mp_sandbox','1')"); ?>
                                            <label class="checkbox">
                                                <input type="checkbox" name="mp_sandbox" value="1"
                                                    <?php echo $mp_sb === '1' ? 'checked' : ''; ?>>
                                                Modo sandbox (pruebas) — desactiva para ir a producción
                                            </label>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">URL Webhook</label>
                                        <div class="controls">
                                            <input type="text" class="span8" readonly
                                                   value="<?php echo (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']),'/').'/mercadopago-webhook.php'; ?>">
                                            <span class="help-block">Copia esta URL y pégala en tu aplicación de MP → Notificaciones IPN/Webhook.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── ANALYTICS Y PIXEL (Y1+Y2) ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-bar-chart"></i> Analytics e integraciones de marketing</h4>
                                    <div class="control-group">
                                        <label class="control-label">Google Analytics 4 — Measurement ID</label>
                                        <div class="controls">
                                            <input type="text" name="ga4_measurement_id" class="span4" value="<?php echo sv($settings,'ga4_measurement_id',''); ?>" placeholder="G-XXXXXXXXXX">
                                            <span class="help-block">Formato: <code>G-XXXXXXXXXX</code>. Dejar vacío para desactivar.</span>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Meta Pixel ID</label>
                                        <div class="controls">
                                            <input type="text" name="meta_pixel_id" class="span4" value="<?php echo sv($settings,'meta_pixel_id',''); ?>" placeholder="123456789012345">
                                            <span class="help-block">Solo el número ID de tu pixel de Facebook/Instagram. Dejar vacío para desactivar.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── BÚSQUEDA (S1) ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-search"></i> Motor de búsqueda — Sinónimos</h4>
                                    <p style="color:#666;font-size:12px;margin-bottom:12px;">Un sinónimo por línea. Formato: <code>término = equivalente1, equivalente2</code><br>Ejemplo: <code>celular = smartphone, móvil, teléfono</code></p>
                                    <div class="control-group">
                                        <label class="control-label">Sinónimos de búsqueda</label>
                                        <div class="controls">
                                            <textarea name="search_synonyms" class="span8" rows="6" placeholder="celular = smartphone, móvil&#10;zapatos = calzado, tenis&#10;tv = televisor, pantalla"><?php echo htmlspecialchars(sv($settings,'search_synonyms','')); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── MULTI-MONEDA (R2) ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-globe"></i> Multi-moneda — Tasas de conversión</h4>
                                    <p style="color:#666;font-size:12px;margin-bottom:12px;">Moneda base: <strong>COP</strong>. Ingresa cuántas unidades de cada moneda equivalen a 1 COP.</p>
                                    <div class="control-group">
                                        <label class="control-label">Tasa USD / COP</label>
                                        <div class="controls">
                                            <input type="text" name="currency_usd_rate" class="span3" value="<?php echo sv($settings,'currency_usd_rate','0.00025'); ?>" placeholder="0.00025">
                                            <span class="help-block">Ej: si 1 USD = 4000 COP → tasa = 0.00025</span>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Tasa EUR / COP</label>
                                        <div class="controls">
                                            <input type="text" name="currency_eur_rate" class="span3" value="<?php echo sv($settings,'currency_eur_rate','0.00023'); ?>" placeholder="0.00023">
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Tasa BRL / COP</label>
                                        <div class="controls">
                                            <input type="text" name="currency_brl_rate" class="span3" value="<?php echo sv($settings,'currency_brl_rate','0.0013'); ?>" placeholder="0.0013">
                                        </div>
                                    </div>
                                </div>

                                <!-- ── SMTP / EMAIL OTP ── -->
                                <div class="settings-section">
                                    <h4><i class="icon-envelope"></i> Correo electrónico SMTP (Recuperación de contraseña)</h4>
                                    <p style="color:#666;font-size:12px;margin-bottom:12px;">
                                        Configuración del servidor de correo para enviar códigos OTP.
                                        Si <strong>Servidor SMTP</strong> está vacío, el sistema mostrará el código en pantalla (modo desarrollo).
                                    </p>

                                    <div class="control-group">
                                        <label class="control-label">Servidor SMTP</label>
                                        <div class="controls">
                                            <input type="text" name="smtp_host" class="span6"
                                                   value="<?php echo sv($settings,'smtp_host',''); ?>"
                                                   placeholder="smtp.gmail.com">
                                            <span class="help-block">Gmail: <code>smtp.gmail.com</code> | Outlook: <code>smtp.office365.com</code></span>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Puerto</label>
                                        <div class="controls">
                                            <input type="text" name="smtp_port" class="span2"
                                                   value="<?php echo sv($settings,'smtp_port','587'); ?>"
                                                   placeholder="587">
                                            <span class="help-block">Normalmente <code>587</code> (TLS) o <code>465</code> (SSL)</span>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Usuario / Correo</label>
                                        <div class="controls">
                                            <input type="text" name="smtp_user" class="span6"
                                                   value="<?php echo sv($settings,'smtp_user',''); ?>"
                                                   placeholder="tucorreo@gmail.com">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Contraseña de app</label>
                                        <div class="controls">
                                            <input type="password" name="smtp_pass" class="span6"
                                                   value="<?php echo sv($settings,'smtp_pass',''); ?>"
                                                   placeholder="xxxx xxxx xxxx xxxx"
                                                   autocomplete="new-password">
                                            <span class="help-block">
                                                <strong>No</strong> es tu contraseña de Gmail.
                                                Es una <em>contraseña de aplicación</em> de 16 caracteres.
                                            </span>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Dirección "De:"</label>
                                        <div class="controls">
                                            <input type="text" name="smtp_from" class="span6"
                                                   value="<?php echo sv($settings,'smtp_from',''); ?>"
                                                   placeholder="tucorreo@gmail.com">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Nombre "De:"</label>
                                        <div class="controls">
                                            <input type="text" name="smtp_from_name" class="span6"
                                                   value="<?php echo sv($settings,'smtp_from_name',''); ?>"
                                                   placeholder="MotoDVentures">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label">Email del administrador <small>(alertas del sistema)</small></label>
                                        <div class="controls">
                                            <input type="email" name="admin_email" class="span6"
                                                   value="<?php echo sv($settings,'admin_email',''); ?>"
                                                   placeholder="admin@tutienda.com">
                                            <span class="help-inline" style="font-size:11px;color:#888">Recibe alertas de nuevas órdenes, stock bajo, devoluciones y mensajes de contacto.</span>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Días post-entrega para solicitar reseña</label>
                                        <div class="controls">
                                            <input type="number" name="review_request_days" class="span2" min="1" max="30"
                                                   value="<?php echo sv($settings,'review_request_days','3'); ?>">
                                            <span class="help-inline" style="font-size:11px;color:#888">Email pidiendo valoración se envía N días después de marcar el pedido como "Delivered".</span>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Token para cron de reseñas</label>
                                        <div class="controls">
                                            <input type="text" name="cron_token" class="span4"
                                                   value="<?php echo sv($settings,'cron_token',''); ?>"
                                                   placeholder="Dejar vacío = sin protección">
                                            <span class="help-inline" style="font-size:11px;color:#888">Llama a <code>/proyectowebmaster/cron-review-request.php?token=TU_TOKEN</code> desde el Task Scheduler de Windows.</span>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label">Ejecutar ahora</label>
                                        <div class="controls">
                                            <a href="../cron-review-request.php?token=<?php echo urlencode(sv($settings,'cron_token','')); ?>" target="_blank" class="btn btn-info btn-small"><i class="icon-send"></i> Enviar solicitudes de reseña ahora</a>
                                            &nbsp;
                                            <a href="../cron-birthday.php?token=<?php echo urlencode(sv($settings,'cron_token','')); ?>" target="_blank" class="btn btn-warning btn-small"><i class="icon-gift"></i> Enviar cupones de cumpleaños ahora</a>
                                        </div>
                                    </div>

                                    <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:4px;padding:12px;font-size:12px;">
                                        <strong><i class="icon-info-sign"></i> Cómo obtener la contraseña de aplicación en Gmail:</strong>
                                        <ol style="margin:8px 0 0 18px;padding:0;line-height:1.8;">
                                            <li>Ve a <strong>myaccount.google.com</strong> → Seguridad</li>
                                            <li>Activa la <strong>Verificación en 2 pasos</strong> (si no está activa)</li>
                                            <li>Vuelve a Seguridad → busca <strong>"Contraseñas de aplicaciones"</strong></li>
                                            <li>Selecciona "Correo" y "Otro dispositivo" → escribe "<?php echo sv($settings,'site_name','Mi sitio'); ?>"</li>
                                            <li>Copia los <strong>16 caracteres</strong> generados y pégalos arriba</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="control-group" style="margin-top:20px; padding:15px 0 40px;">
                                    <div class="controls">
                                        <button type="submit" name="submit" class="btn btn-primary btn-large">
                                            <i class="icon-save"></i> Guardar todos los cambios
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
