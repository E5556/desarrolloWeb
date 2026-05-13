<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');
if (empty($_SESSION['login'])) { header('location:login.php'); exit(); }

$_uid = intval($_SESSION['id']);
$_oid = intval($_GET['oid'] ?? 0);

// Crear tabla returns si no existe
mysqli_query($con, "CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
)");

// Verificar que la orden pertenece al usuario y tiene método de pago
$_oq = mysqli_prepare($con, "SELECT o.id, o.orderDate, o.paymentMethod, o.orderStatus, p.productName
    FROM orders o JOIN products p ON o.productId = p.id
    WHERE o.id=? AND o.userId=? AND o.paymentMethod IS NOT NULL LIMIT 1");
mysqli_stmt_bind_param($_oq, 'ii', $_oid, $_uid);
mysqli_stmt_execute($_oq);
$_order = mysqli_fetch_assoc(mysqli_stmt_get_result($_oq));

if (!$_order) {
    header('location:order-history.php');
    exit();
}

// Verificar que no existe ya una solicitud para esta orden
$_rq = mysqli_prepare($con, "SELECT id, status FROM returns WHERE order_id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($_rq, 'ii', $_oid, $_uid);
mysqli_stmt_execute($_rq);
$_existing = mysqli_fetch_assoc(mysqli_stmt_get_result($_rq));

$_success = $_error = '';

if (isset($_POST['submit'])) {
    csrf_verify();

    if ($_existing) {
        $_error = 'Ya existe una solicitud de devolución para este pedido.';
    } else {
        $reason = trim($_POST['reason'] ?? '');
        if (strlen($reason) < 10) {
            $_error = 'Por favor describe el motivo con al menos 10 caracteres.';
        } else {
            $photo_name = null;
            if (!empty($_FILES['photo']['name'])) {
                include_once('includes/security.php');
                $upload_dir = 'assets/return-photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                    file_put_contents($upload_dir . '.htaccess', "php_flag engine off\n");
                }
                $photo_name = safe_image_upload('photo', $upload_dir);
                if (!$photo_name) {
                    $_error = 'El archivo de imagen no es válido (JPG/PNG/GIF/WEBP, máx 5MB).';
                }
            }

            if (!$_error) {
                $stmt = mysqli_prepare($con, "INSERT INTO returns (order_id, user_id, reason, photo) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($stmt, 'iiss', $_oid, $_uid, $reason, $photo_name);
                mysqli_stmt_execute($stmt);
                $ret_id = mysqli_insert_id($con);
                mysqli_stmt_close($stmt);
                // Notify admin
                $_usr_ret = mysqli_fetch_assoc(mysqli_query($con,"SELECT name,email FROM users WHERE id=$_uid LIMIT 1"));
                include_once('includes/mailer.php');
                notify_admin('new_return', ['return_id'=>$ret_id,'customer'=>($_usr_ret['name']??'').' <'.($_usr_ret['email']??').'>','reason'=>$reason]);
                $_success = '¡Solicitud enviada! El equipo la revisará y te notificaremos.';
                $_existing = ['status' => 'pending'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Solicitar devolución | <?php echo $_SITE_NAME; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/green.css">
<link rel="stylesheet" href="assets/css/cosmetics.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php'); include('includes/main-header.php'); include('includes/menu-bar.php'); ?>
</header>

<div class="breadcrumb">
<div class="container">
<div class="breadcrumb-inner">
<ul class="list-inline list-unstyled">
    <li><a href="index2.php">Inicio</a></li>
    <li><a href="order-history.php">Mis pedidos</a></li>
    <li class="active">Solicitar devolución</li>
</ul>
</div>
</div>
</div>

<div class="body-content outer-top-xs">
<div class="container">
<div class="row">
<div class="col-md-8 col-md-offset-2" style="margin-bottom:60px">

<h2><i class="fa fa-undo"></i> Solicitar devolución</h2>
<p class="text-muted">Pedido #<?php echo $_oid; ?> — <?php echo htmlspecialchars($_order['productName']); ?> — <?php echo $_order['orderDate']; ?></p>
<hr>

<?php if ($_success): ?>
<div class="alert alert-success"><i class="fa fa-check"></i> <?php echo htmlspecialchars($_success); ?></div>
<a href="order-history.php" class="btn btn-primary">Ver mis pedidos</a>

<?php elseif ($_existing): ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> Ya tienes una solicitud de devolución para este pedido.
    <strong>Estado: <?php
        $st_map = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','completed'=>'Completada'];
        echo $st_map[$_existing['status']] ?? $_existing['status'];
    ?></strong>
</div>
<a href="order-history.php" class="btn btn-default">Volver a mis pedidos</a>

<?php else: ?>

<?php if ($_error): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($_error); ?></div>
<?php endif; ?>

<div class="panel panel-default">
<div class="panel-body">
<form method="post" enctype="multipart/form-data">
<?php echo csrf_field(); ?>

<div class="form-group">
    <label>Motivo de la devolución <span class="text-danger">*</span></label>
    <select name="reason" class="form-control" id="reason_select" onchange="toggleCustom(this.value)">
        <option value="">-- Selecciona un motivo --</option>
        <option value="Producto defectuoso o dañado">Producto defectuoso o dañado</option>
        <option value="Producto diferente al descrito">Producto diferente al descrito</option>
        <option value="Talla o medida incorrecta">Talla o medida incorrecta</option>
        <option value="No era lo que esperaba">No era lo que esperaba</option>
        <option value="otro">Otro motivo...</option>
    </select>
</div>

<div class="form-group" id="custom_reason_div" style="display:none">
    <label>Describe el motivo <span class="text-danger">*</span></label>
    <textarea name="reason" class="form-control" rows="4" id="reason_text" placeholder="Describe con detalle el problema..."></textarea>
</div>

<div class="form-group">
    <label>Foto del producto (opcional)</label>
    <input type="file" name="photo" accept="image/*" class="form-control">
    <p class="help-block">JPG, PNG o GIF. Máximo 5MB. Útil para mostrar daños o defectos.</p>
</div>

<button type="submit" name="submit" class="btn btn-warning"><i class="fa fa-paper-plane"></i> Enviar solicitud</button>
<a href="order-history.php" class="btn btn-default">Cancelar</a>
</form>
</div>
</div>

<?php endif; ?>

</div>
</div>
</div>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/scripts.js"></script>
<script>
function toggleCustom(val) {
    var div = document.getElementById('custom_reason_div');
    var sel = document.getElementById('reason_select');
    var txt = document.getElementById('reason_text');
    if (val === 'otro') {
        div.style.display = 'block';
        sel.removeAttribute('name');
        txt.setAttribute('name', 'reason');
    } else {
        div.style.display = 'none';
        sel.setAttribute('name', 'reason');
        txt.removeAttribute('name');
    }
}
</script>
</body>
</html>
