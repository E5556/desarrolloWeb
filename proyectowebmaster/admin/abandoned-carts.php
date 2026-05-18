<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Enviar email recordatorio
$reminder_sent = 0;
if (isset($_POST['send_reminder'])) {
    $uid_r = intval($_POST['user_id']);
    include_once('../includes/mailer.php');

    $uq = mysqli_query($con, "SELECT name, email FROM users WHERE id=$uid_r LIMIT 1");
    if ($uq && $urow = mysqli_fetch_assoc($uq)) {
        // Obtener items del carrito abandonado (órdenes sin método de pago)
        $iq = mysqli_query($con, "SELECT o.quantity, p.productName, p.productPrice
            FROM orders o JOIN products p ON o.productId=p.id
            WHERE o.userId=$uid_r AND o.paymentMethod IS NULL");
        $items_html = ''; $total = 0;
        while ($ir = mysqli_fetch_assoc($iq)) {
            $sub = $ir['quantity'] * $ir['productPrice'];
            $total += $sub;
            $items_html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($ir['productName']) . '</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:center">' . $ir['quantity'] . '</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:right">$' . number_format($ir['productPrice'],0,'.',',') . '</td></tr>';
        }

        $cfg_r = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='site_name' LIMIT 1");
        $site_name = $cfg_r ? (mysqli_fetch_assoc($cfg_r)['setting_value'] ?? 'Tienda') : 'Tienda';

        $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:30px">
        <table width="520" style="margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)">
        <tr><td style="background:#e8233a;padding:20px;text-align:center"><h2 style="color:#fff;margin:0">' . htmlspecialchars($site_name) . '</h2></td></tr>
        <tr><td style="padding:30px">
          <h3>¡Hola, ' . htmlspecialchars($urow['name']) . '!</h3>
          <p>Dejaste algunos productos en tu carrito. ¡No dejes que se te escapen!</p>
          <table width="100%" style="border:1px solid #eee;border-radius:6px;overflow:hidden">
            <thead><tr style="background:#f9f9f9">
              <th style="padding:10px;text-align:left">Producto</th>
              <th style="padding:10px;text-align:center">Cant.</th>
              <th style="padding:10px;text-align:right">Precio</th>
            </tr></thead>
            <tbody>' . $items_html . '</tbody>
            <tfoot><tr>
              <td colspan="2" style="padding:10px;font-weight:700">Total</td>
              <td style="padding:10px;font-weight:700;color:#e8233a;text-align:right">$' . number_format($total,0,'.',',') . '</td>
            </tr></tfoot>
          </table>
          <div style="text-align:center;margin:24px 0">
            <a href="http://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/my-cart.php"
               style="background:#e8233a;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:bold">
              Completar mi compra
            </a>
          </div>
          <p style="font-size:12px;color:#aaa;text-align:center">Si ya realizaste tu compra, ignora este mensaje.</p>
        </td></tr></table></body></html>';

        send_email_raw($urow['email'], $urow['name'], "¿Olvidaste algo? Tu carrito te espera — {$site_name}", $body);
        $reminder_sent = $uid_r;
    }
    header('location:abandoned-carts.php?reminded=' . $uid_r); exit();
}

// Horas mínimas de abandono (configurable via GET)
$hours = max(1, intval($_GET['hours'] ?? 24));

// Carritos abandonados: usuarios con órdenes sin paymentMethod, agrupados
$abandoned = mysqli_query($con,
    "SELECT u.id, u.name, u.email, u.regDate,
            COUNT(o.id) items,
            SUM(o.quantity * p.productPrice) cart_value,
            MIN(o.orderDate) first_item,
            MAX(o.orderDate) last_item
     FROM orders o
     JOIN users u ON o.userId=u.id
     JOIN products p ON o.productId=p.id
     WHERE o.paymentMethod IS NULL
       AND o.orderDate < DATE_SUB(NOW(), INTERVAL $hours HOUR)
     GROUP BY o.userId
     ORDER BY cart_value DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Carritos abandonados | Admin</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>

<div class="span9">
<div class="content-area">

<h3><i class="icon-shopping-cart"></i> Carritos abandonados</h3>
<p class="text-muted">Usuarios que agregaron productos al carrito pero no completaron el pago.</p>

<?php if (isset($_GET['reminded'])): ?>
<div class="alert alert-success">Email recordatorio enviado al usuario #<?php echo intval($_GET['reminded']); ?>.</div>
<?php endif; ?>

<form method="get" style="margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <label style="margin:0">Abandonados hace más de:</label>
    <select name="hours" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="1"  <?php echo $hours==1?'selected':''; ?>>1 hora</option>
        <option value="6"  <?php echo $hours==6?'selected':''; ?>>6 horas</option>
        <option value="24" <?php echo $hours==24?'selected':''; ?>>24 horas</option>
        <option value="48" <?php echo $hours==48?'selected':''; ?>>48 horas</option>
        <option value="168"<?php echo $hours==168?'selected':''; ?>>1 semana</option>
    </select>
</form>

<table class="table table-bordered table-hover table-striped">
<thead>
<tr>
    <th>Cliente</th>
    <th>Email</th>
    <th>Productos</th>
    <th>Valor carrito</th>
    <th>Último ítem agregado</th>
    <th>Acción</th>
</tr>
</thead>
<tbody>
<?php if (!$abandoned || mysqli_num_rows($abandoned)===0): ?>
<tr><td colspan="6" class="text-center text-muted" style="padding:20px">No hay carritos abandonados en este período.</td></tr>
<?php else: while ($ab = mysqli_fetch_assoc($abandoned)): ?>
<tr>
    <td>
        <strong><?php echo htmlspecialchars($ab['name']); ?></strong><br>
        <small class="text-muted">ID #<?php echo $ab['id']; ?></small>
    </td>
    <td><small><?php echo htmlspecialchars($ab['email']); ?></small></td>
    <td><span class="badge"><?php echo $ab['items']; ?> ítem(s)</span></td>
    <td><strong style="color:#e8233a">$<?php echo number_format($ab['cart_value'],0,'.',','); ?></strong></td>
    <td><small><?php echo date('d/m/Y H:i', strtotime($ab['last_item'])); ?></small></td>
    <td>
        <button class="btn btn-xs btn-default" data-toggle="collapse" data-target="#items-<?php echo $ab['id']; ?>">
            <i class="icon-eye-open"></i> Ver
        </button>
        <form method="post" style="display:inline" onsubmit="return confirm('¿Enviar email recordatorio a <?php echo htmlspecialchars(addslashes($ab['name'])); ?>?')">
            <input type="hidden" name="user_id" value="<?php echo $ab['id']; ?>">
            <button type="submit" name="send_reminder" class="btn btn-xs btn-warning">
                <i class="icon-envelope"></i> Recordatorio
            </button>
        </form>
    </td>
</tr>
<tr>
<td colspan="6" style="padding:0">
<div class="collapse" id="items-<?php echo $ab['id']; ?>" style="padding:10px 16px;background:#f9f9f9">
    <?php
    $detail = mysqli_query($con,
        "SELECT o.quantity, p.productName, p.productPrice, p.productImage1, p.id pid
         FROM orders o JOIN products p ON o.productId=p.id
         WHERE o.userId={$ab['id']} AND o.paymentMethod IS NULL");
    while ($di = mysqli_fetch_assoc($detail)):
    ?>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <img src="../admin/productimages/<?php echo $di['pid'].'/'.htmlspecialchars($di['productImage1']); ?>" width="40" style="border-radius:4px" onerror="this.style.display='none'">
        <span><?php echo htmlspecialchars($di['productName']); ?></span>
        <span class="label label-default">x<?php echo $di['quantity']; ?></span>
        <span style="color:#e8233a">$<?php echo number_format($di['productPrice'],0,'.',','); ?></span>
    </div>
    <?php endwhile; ?>
</div>
</td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

</div>
</div>
</div>
</div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
