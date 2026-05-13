<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_marketing');


// Crear tabla historial si no existe
mysqli_query($con, "CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sent_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = $msg_type = '';

if (isset($_POST['send'])) {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if (strlen($subject) < 3 || strlen($body) < 10) {
        $msg = 'El asunto y el cuerpo son obligatorios.';
        $msg_type = 'danger';
    } else {
        include_once('../includes/mailer.php');

        // Leer suscriptores activos
        $subs = mysqli_query($con, "SELECT email FROM newsletter_subscribers WHERE is_active=1");
        $sent = 0; $errors = 0;

        while ($sub = mysqli_fetch_assoc($subs)) {
            $ok = send_email_raw($sub['email'], '', $subject, $body);
            $ok ? $sent++ : $errors++;
            // Pequeña pausa cada 20 envíos para no saturar el servidor
            if ($sent % 20 === 0) usleep(500000);
        }

        // Guardar historial
        $stmt = mysqli_prepare($con, "INSERT INTO newsletter_campaigns (subject, body, sent_count) VALUES (?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $subject, $body, $sent);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $msg = "Newsletter enviado a {$sent} suscriptores." . ($errors ? " ({$errors} fallidos)" : '');
        $msg_type = 'success';
    }
}

// Historial de campañas
$campaigns = mysqli_query($con, "SELECT * FROM newsletter_campaigns ORDER BY created_at DESC LIMIT 20");

// Conteo de suscriptores activos
$total_q = mysqli_query($con, "SELECT COUNT(*) n FROM newsletter_subscribers WHERE is_active=1");
$total_subs = $total_q ? intval(mysqli_fetch_assoc($total_q)['n']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Enviar Newsletter | Admin</title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
<style>
#body-preview { border:1px solid #ddd; padding:16px; border-radius:4px; background:#fafafa; min-height:120px; margin-top:8px; }
.toolbar-btn { margin-right:4px; }
</style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>

<div class="span9">
<div class="content-area">

<h3><i class="icon-envelope"></i> Enviar Newsletter</h3>
<p class="text-muted">Suscriptores activos: <strong><?php echo $total_subs; ?></strong></p>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row">
<div class="col-md-8">
<div class="panel panel-default">
<div class="panel-heading"><strong>Redactar campaña</strong></div>
<div class="panel-body">

<form method="post" id="newsletter-form">

<div class="form-group">
    <label>Asunto <span class="text-danger">*</span></label>
    <input type="text" name="subject" class="form-control" placeholder="Ej: ¡Nuevos productos disponibles!" value="<?php echo htmlspecialchars($_POST['subject']??''); ?>">
</div>

<div class="form-group">
    <label>Cuerpo del correo <span class="text-danger">*</span></label>
    <div style="margin-bottom:6px">
        <button type="button" class="btn btn-xs btn-default toolbar-btn" onclick="wrapSel('<strong>','</strong>')"><b>B</b></button>
        <button type="button" class="btn btn-xs btn-default toolbar-btn" onclick="wrapSel('<em>','</em>')"><i>I</i></button>
        <button type="button" class="btn btn-xs btn-default toolbar-btn" onclick="wrapSel('<h2>','</h2>')">H2</button>
        <button type="button" class="btn btn-xs btn-default toolbar-btn" onclick="wrapSel('<p>','</p>')">P</button>
        <button type="button" class="btn btn-xs btn-default toolbar-btn" onclick="wrapSel('<a href=\'#\'>','</a>')">Link</button>
        <button type="button" class="btn btn-xs btn-default toolbar-btn" onclick="insertDiv()">Botón CTA</button>
        <button type="button" class="btn btn-xs btn-info toolbar-btn" onclick="togglePreview()">Vista previa</button>
    </div>
    <textarea name="body" id="body-editor" class="form-control" rows="14" placeholder="Escribe el HTML del correo aquí..."><?php echo htmlspecialchars($_POST['body']??''); ?></textarea>
    <div id="body-preview" style="display:none"></div>
</div>

<button type="submit" name="send" class="btn btn-primary"
    onclick="return confirm('¿Enviar este newsletter a <?php echo $total_subs; ?> suscriptores?')">
    <i class="fa fa-paper-plane"></i> Enviar a todos los suscriptores
</button>
<a href="newsletter-subscribers.php" class="btn btn-default">Ver suscriptores</a>

</form>
</div>
</div>
</div>

<div class="col-md-4">
<div class="panel panel-default">
<div class="panel-heading"><strong>Historial de campañas</strong></div>
<div class="panel-body" style="padding:0">
<table class="table table-condensed" style="margin:0">
<thead><tr><th>Asunto</th><th>Enviados</th><th>Fecha</th></tr></thead>
<tbody>
<?php if (!$campaigns || mysqli_num_rows($campaigns)===0): ?>
<tr><td colspan="3" class="text-muted text-center" style="padding:12px">Sin campañas aún</td></tr>
<?php else: while ($c = mysqli_fetch_assoc($campaigns)): ?>
<tr>
    <td title="<?php echo htmlspecialchars($c['subject']); ?>"><?php echo htmlspecialchars(mb_substr($c['subject'],0,30)).(mb_strlen($c['subject'])>30?'…':''); ?></td>
    <td><?php echo $c['sent_count']; ?></td>
    <td><?php echo date('d/m/y', strtotime($c['created_at'])); ?></td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>
</div>
</div>

<div class="panel panel-info">
<div class="panel-heading"><strong>Consejos</strong></div>
<div class="panel-body" style="font-size:13px">
    <ul style="padding-left:16px;margin:0">
        <li>Usa HTML para dar formato rico</li>
        <li>Incluye un botón CTA claro</li>
        <li>Personaliza con el nombre del sitio</li>
        <li>Revisa la vista previa antes de enviar</li>
    </ul>
</div>
</div>

</div>
</div>

</div>
</div><!-- /.span9 -->
</div>
</div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script>
var previewVisible = false;
function togglePreview() {
    previewVisible = !previewVisible;
    var ed = document.getElementById('body-editor');
    var pv = document.getElementById('body-preview');
    if (previewVisible) {
        pv.innerHTML = ed.value;
        ed.style.display = 'none';
        pv.style.display = 'block';
    } else {
        ed.style.display = 'block';
        pv.style.display = 'none';
    }
}
function wrapSel(before, after) {
    var ed = document.getElementById('body-editor');
    var start = ed.selectionStart, end = ed.selectionEnd;
    var sel = ed.value.substring(start, end);
    ed.value = ed.value.substring(0, start) + before + sel + after + ed.value.substring(end);
    ed.focus();
}
function insertDiv() {
    var ed = document.getElementById('body-editor');
    var cta = '<div style="text-align:center;margin:20px 0"><a href="http://tutienda.com" style="background:#e8233a;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:bold">Ver ofertas</a></div>';
    var pos = ed.selectionStart;
    ed.value = ed.value.substring(0,pos) + cta + ed.value.substring(pos);
}
</script>
</body>
</html>
