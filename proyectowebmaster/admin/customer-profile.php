<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

date_default_timezone_set('America/Bogota');

$uid = intval($_GET['id'] ?? 0);
if ($uid <= 0) { header('location:manage-users.php'); exit(); }

// ── DATOS DEL CLIENTE ────────────────────────────────────────────────────
$user = ($__r = mysqli_query($con, "SELECT * FROM users WHERE id=$uid")) ? mysqli_fetch_assoc($__r) : null;
if (!$user) { header('location:manage-users.php'); exit(); }

// ── NOTA DEL ADMIN (guardar) ─────────────────────────────────────────────
if (isset($_POST['save_note'])) {
    $note = mysqli_real_escape_string($con, trim($_POST['admin_note'] ?? ''));
    mysqli_query($con, "UPDATE users SET updationDate='$note' WHERE id=$uid");
    // Reutilizamos updationDate como campo de nota (solo en users admin-side)
    header("location:customer-profile.php?id=$uid&saved=1"); exit();
}

// ── MÉTRICAS DEL CLIENTE ─────────────────────────────────────────────────
$stats = ($__r = mysqli_query($con,
    "SELECT COUNT(o.id) total_orders,
            COALESCE(SUM(o.quantity * p.productPrice), 0) total_spent,
            COALESCE(AVG(o.quantity * p.productPrice), 0) avg_order,
            MIN(o.orderDate) first_order,
            MAX(o.orderDate) last_order
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.userId=$uid")) ? mysqli_fetch_assoc($__r) : [];

$total_items = intval((($__r = mysqli_query($con,
    "SELECT COALESCE(SUM(quantity),0) n FROM orders WHERE userId=$uid")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

$wishlist_count = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM wishlist WHERE userId=$uid")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

$views_count = intval((($__r = mysqli_query($con,
    "SELECT COUNT(*) n FROM product_views WHERE user_id=$uid")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// Login count
$st_lc = mysqli_prepare($con, "SELECT COUNT(*) n FROM userlog WHERE userEmail=?");
mysqli_stmt_bind_param($st_lc, 's', $user['email']);
mysqli_stmt_execute($st_lc);
$login_count = intval(mysqli_fetch_assoc(mysqli_stmt_get_result($st_lc))['n']);
mysqli_stmt_close($st_lc);

// ── HISTORIAL DE PEDIDOS ─────────────────────────────────────────────────
$orders = mysqli_query($con,
    "SELECT o.id, o.orderDate, o.quantity, o.paymentMethod, o.orderStatus,
            p.productName, p.productPrice, p.productImage1, p.id AS pid
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.userId=$uid ORDER BY o.orderDate DESC");

// ── CATEGORÍAS FAVORITAS ─────────────────────────────────────────────────
$fav_cats = mysqli_query($con,
    "SELECT c.categoryName, COUNT(*) n
     FROM orders o JOIN products p ON o.productId=p.id JOIN category c ON p.category=c.id
     WHERE o.userId=$uid GROUP BY p.category ORDER BY n DESC LIMIT 5");

// ── PRODUCTOS MÁS COMPRADOS ──────────────────────────────────────────────
$top_prods = mysqli_query($con,
    "SELECT p.id, p.productName, p.productImage1, SUM(o.quantity) units
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.userId=$uid GROUP BY o.productId ORDER BY units DESC LIMIT 5");

// ── WISHLIST ─────────────────────────────────────────────────────────────
$wishlist = mysqli_query($con,
    "SELECT p.id, p.productName, p.productPrice, p.productImage1, p.productAvailability
     FROM wishlist w JOIN products p ON w.productId=p.id
     WHERE w.userId=$uid LIMIT 8");

// ── ACTIVIDAD DE LOGIN ───────────────────────────────────────────────────
$st_lg = mysqli_prepare($con, "SELECT loginTime, status FROM userlog WHERE userEmail=? ORDER BY loginTime DESC LIMIT 8");
mysqli_stmt_bind_param($st_lg, 's', $user['email']);
mysqli_stmt_execute($st_lg);
$logins = mysqli_stmt_get_result($st_lg);

$status_colors = ['Delivered'=>'success','Pending'=>'warning','Shipped'=>'info','Cancelado'=>'important'];
$avail_labels  = ['In Stock'=>'En Stock','Out of Stock'=>'Sin Stock','On Order'=>'Bajo Pedido'];
$initial = strtoupper(mb_substr($user['name'],0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Perfil cliente</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Profile header */
        .profile-hero { background:linear-gradient(135deg,#337ab7,#23527c); color:#fff;
                        border-radius:8px; padding:24px 28px; margin-bottom:22px; display:flex; align-items:center; gap:20px; }
        .profile-avatar { width:68px; height:68px; border-radius:50%; background:rgba(255,255,255,.25);
                          font-size:1.8em; font-weight:700; display:flex; align-items:center; justify-content:center;
                          flex-shrink:0; border:3px solid rgba(255,255,255,.5); }
        .profile-hero h2 { margin:0 0 4px; font-size:1.3em; }
        .profile-hero .meta { font-size:.82em; opacity:.8; }
        .profile-hero .badges span { display:inline-block; padding:2px 8px; border-radius:10px;
                                      font-size:.72em; font-weight:700; margin:2px 2px 0 0; }
        .badge-vip    { background:#f0ad4e; color:#fff; }
        .badge-repeat { background:#5cb85c; color:#fff; }
        .badge-new    { background:rgba(255,255,255,.3); color:#fff; }
        /* KPI cards */
        .kpi-row { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
        .kpi-c { flex:1 1 110px; background:#fff; border:1px solid #e0e0e0; border-radius:6px;
                 padding:12px; text-align:center; }
        .kpi-c .v { font-size:1.5em; font-weight:700; color:#337ab7; }
        .kpi-c .l { font-size:.72em; color:#888; margin-top:2px; }
        /* Section title */
        .sec { font-size:.92em; font-weight:700; color:#444; border-left:3px solid #337ab7;
               padding-left:8px; margin:20px 0 10px; }
        /* Tables */
        .profile-table td, .profile-table th { font-size:.84em; vertical-align:middle !important; }
        .profile-table td img { width:32px; height:32px; object-fit:cover; border-radius:3px; }
        /* Notes */
        .note-box { background:#fffde7; border:1px solid #ffe082; border-radius:5px; padding:12px; }
        .note-box textarea { width:100%; border:none; background:transparent; resize:vertical;
                             font-size:.88em; color:#555; min-height:70px; outline:none; }
        /* Info list */
        .info-list { list-style:none; padding:0; margin:0; font-size:.87em; }
        .info-list li { padding:6px 0; border-bottom:1px solid #f0f0f0; display:flex; gap:8px; }
        .info-list li:last-child { border-bottom:none; }
        .info-list .il { color:#888; min-width:130px; flex-shrink:0; }
        /* Back link */
        .back-link { margin-bottom:14px; display:inline-block; font-size:.85em; color:#337ab7; }
        .back-link:hover { text-decoration:underline; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper">
<div class="container">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content">

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success"><button class="close" data-dismiss="alert">×</button>Nota guardada.</div>
<?php endif; ?>

<a href="manage-users.php" class="back-link"><i class="icon-arrow-left"></i> Volver a clientes</a>

<!-- ── PERFIL HERO ─────────────────────────────────────────── -->
<?php
$is_vip  = floatval($stats['total_spent']) > 0 && intval($stats['total_orders']) >= 3;
$is_rep  = intval($stats['total_orders']) > 1;
$is_new  = strtotime($user['regDate']) >= strtotime('first day of this month');
?>
<div class="profile-hero">
    <div class="profile-avatar"><?php echo $initial; ?></div>
    <div style="flex:1">
        <h2><?php echo htmlentities($user['name']); ?></h2>
        <div class="meta">
            <i class="icon-envelope-alt"></i> <?php echo htmlentities($user['email']); ?>
            &nbsp;&nbsp;<i class="icon-phone"></i> <?php echo htmlentities($user['contactno']?:'—'); ?>
        </div>
        <div class="meta" style="margin-top:3px">
            <i class="icon-calendar"></i> Cliente desde <?php echo date('d/m/Y', strtotime($user['regDate'])); ?>
            <?php if ($user['oauth_provider']): ?>
            &nbsp;&nbsp;<i class="icon-google-plus"></i> Registrado vía <?php echo htmlentities($user['oauth_provider']); ?>
            <?php endif; ?>
        </div>
        <div class="badges" style="margin-top:6px">
            <?php if ($is_vip):  ?><span class="badge-vip">⭐ VIP</span><?php endif; ?>
            <?php if ($is_rep):  ?><span class="badge-repeat">↩ Recurrente</span><?php endif; ?>
            <?php if ($is_new):  ?><span class="badge-new">✦ Nuevo</span><?php endif; ?>
            <?php if (!$is_rep && intval($stats['total_orders'])===0): ?>
            <span style="background:rgba(255,255,255,.2);color:#fff;display:inline-block;padding:2px 8px;border-radius:10px;font-size:.72em">Sin compras</span>
            <?php endif; ?>
        </div>
    </div>
    <div style="text-align:right">
        <div style="font-size:1.8em;font-weight:700;line-height:1">$<?php echo number_format($stats['total_spent'],0,'.',','); ?></div>
        <div style="font-size:.75em;opacity:.8">Total gastado</div>
    </div>
</div>

<!-- ── KPIs ───────────────────────────────────────────────── -->
<div class="kpi-row">
    <div class="kpi-c"><div class="v"><?php echo $stats['total_orders']; ?></div><div class="l">Pedidos</div></div>
    <div class="kpi-c"><div class="v"><?php echo $total_items; ?></div><div class="l">Artículos comprados</div></div>
    <div class="kpi-c"><div class="v">$<?php echo number_format($stats['avg_order'],0,'.',','); ?></div><div class="l">Ticket promedio</div></div>
    <div class="kpi-c"><div class="v"><?php echo $wishlist_count; ?></div><div class="l">En wishlist</div></div>
    <div class="kpi-c"><div class="v"><?php echo $views_count; ?></div><div class="l">Fichas vistas</div></div>
    <div class="kpi-c"><div class="v"><?php echo $login_count; ?></div><div class="l">Inicios de sesión</div></div>
</div>

<!-- ── DOS COLUMNAS ──────────────────────────────────────── -->
<div class="row-fluid">

    <!-- IZQUIERDA -->
    <div class="span8">

        <!-- Historial de pedidos -->
        <div class="sec">Historial de pedidos</div>
        <?php
        $order_rows = mysqli_fetch_all($orders, MYSQLI_ASSOC);
        // Datos para gráfica
        $chart_months = []; $chart_spend = [];
        foreach ($order_rows as $or) {
            $mon = date('Y-m', strtotime($or['orderDate']));
            $chart_months[$mon] = ($chart_months[$mon] ?? 0) + intval($or['quantity']) * intval($or['productPrice']);
        }
        ksort($chart_months);
        $chart_months_labels = array_keys($chart_months);
        $chart_months_data   = array_values($chart_months);
        ?>
        <?php if (!empty($chart_months)): ?>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:12px;margin-bottom:14px;">
            <small style="color:#888;font-weight:600">Gasto por mes</small>
            <canvas id="chartClient" height="60"></canvas>
        </div>
        <?php endif; ?>

        <?php if (empty($order_rows)): ?>
        <p class="text-muted" style="font-style:italic">Este cliente no ha realizado pedidos aún.</p>
        <?php else: ?>
        <table class="table table-bordered table-condensed profile-table">
            <thead>
                <tr><th>#</th><th>Producto</th><th>Cant.</th><th>Total</th><th>Pago</th><th>Estado</th><th>Fecha</th></tr>
            </thead>
            <tbody>
            <?php foreach ($order_rows as $or):
                $lbl   = $status_colors[$or['orderStatus']] ?? 'default';
                $total = intval($or['quantity']) * intval($or['productPrice']);
            ?>
            <tr>
                <td><small>#<?php echo $or['id']; ?></small></td>
                <td>
                    <?php if ($or['productImage1']): ?>
                    <img src="productimages/<?php echo $or['pid'].'/'.htmlentities($or['productImage1']); ?>" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <a href="edit-products.php?pid=<?php echo $or['pid']; ?>" style="font-size:.85em">
                        <?php echo htmlentities(mb_substr($or['productName'],0,30)); ?><?php echo mb_strlen($or['productName'])>30?'…':''; ?>
                    </a>
                </td>
                <td style="text-align:center"><?php echo $or['quantity']; ?></td>
                <td><strong>$<?php echo number_format($total,0,'.',','); ?></strong></td>
                <td><small><?php echo htmlentities($or['paymentMethod']?:'—'); ?></small></td>
                <td>
                    <span class="label label-<?php echo $lbl; ?>">
                        <?php echo htmlentities($or['orderStatus']?:'Pendiente'); ?>
                    </span>
                </td>
                <td><small><?php echo date('d/m/Y', strtotime($or['orderDate'])); ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Wishlist -->
        <?php $wl_rows = mysqli_fetch_all($wishlist, MYSQLI_ASSOC); ?>
        <?php if (!empty($wl_rows)): ?>
        <div class="sec">Wishlist (<?php echo count($wl_rows); ?> productos)</div>
        <table class="table table-condensed profile-table">
            <thead><tr><th>Producto</th><th>Precio</th><th>Disponibilidad</th></tr></thead>
            <tbody>
            <?php foreach ($wl_rows as $wl): ?>
            <tr>
                <td>
                    <?php if ($wl['productImage1']): ?>
                    <img src="productimages/<?php echo $wl['id'].'/'.htmlentities($wl['productImage1']); ?>" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <a href="edit-products.php?pid=<?php echo $wl['id']; ?>" style="font-size:.85em">
                        <?php echo htmlentities($wl['productName']); ?>
                    </a>
                </td>
                <td>$<?php echo number_format($wl['productPrice'],0,'.',','); ?></td>
                <td>
                    <?php $av=$wl['productAvailability']; ?>
                    <span class="label label-<?php echo $av==='In Stock'?'success':($av==='On Order'?'warning':'important'); ?>">
                        <?php echo $avail_labels[$av] ?? $av; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div><!-- /span8 -->

    <!-- DERECHA -->
    <div class="span4">

        <!-- Datos de contacto -->
        <div class="sec">Datos personales</div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:14px;margin-bottom:14px;">
            <ul class="info-list">
                <li><span class="il"><i class="icon-user"></i> Nombre</span> <?php echo htmlentities($user['name']); ?></li>
                <li><span class="il"><i class="icon-envelope-alt"></i> Email</span> <?php echo htmlentities($user['email']); ?></li>
                <li><span class="il"><i class="icon-phone"></i> Teléfono</span> <?php echo htmlentities($user['contactno']?:'—'); ?></li>
                <li><span class="il"><i class="icon-map-marker"></i> Envío</span>
                    <span><?php echo htmlentities(implode(', ', array_filter([
                        $user['shippingAddress'], $user['shippingCity'],
                        $user['shippingState'], $user['shippingPincode']
                    ]))?:'—'); ?></span>
                </li>
                <li><span class="il"><i class="icon-file-text"></i> Facturación</span>
                    <span><?php echo htmlentities(implode(', ', array_filter([
                        $user['billingAddress'], $user['billingCity'],
                        $user['billingState'], $user['billingPincode']
                    ]))?:'—'); ?></span>
                </li>
                <?php if ($stats['first_order']): ?>
                <li><span class="il"><i class="icon-shopping-cart"></i> Primera compra</span> <?php echo date('d/m/Y', strtotime($stats['first_order'])); ?></li>
                <li><span class="il"><i class="icon-time"></i> Última compra</span> <?php echo date('d/m/Y', strtotime($stats['last_order'])); ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Categorías favoritas -->
        <?php $fav_rows = mysqli_fetch_all($fav_cats, MYSQLI_ASSOC); ?>
        <?php if (!empty($fav_rows)): ?>
        <div class="sec">Categorías favoritas</div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:14px;margin-bottom:14px;">
            <?php $max_fc = max(array_column($fav_rows,'n')); ?>
            <?php foreach ($fav_rows as $fc): $pct=round(($fc['n']/$max_fc)*100); ?>
            <div style="margin-bottom:8px">
                <div style="display:flex;justify-content:space-between;font-size:.82em;margin-bottom:2px">
                    <span><?php echo htmlentities($fc['categoryName']); ?></span>
                    <span style="color:#888"><?php echo $fc['n']; ?> pedido(s)</span>
                </div>
                <div style="height:5px;background:#e0e0e0;border-radius:3px">
                    <div style="height:5px;background:#337ab7;border-radius:3px;width:<?php echo $pct; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Top productos comprados -->
        <?php $tp_rows = mysqli_fetch_all($top_prods, MYSQLI_ASSOC); ?>
        <?php if (!empty($tp_rows)): ?>
        <div class="sec">Productos más comprados</div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:14px;margin-bottom:14px;">
            <?php foreach ($tp_rows as $i=>$tp): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;<?php echo $i<count($tp_rows)-1?'border-bottom:1px solid #f5f5f5;padding-bottom:8px':''; ?>">
                <?php if ($tp['productImage1']): ?>
                <img src="productimages/<?php echo $tp['id'].'/'.htmlentities($tp['productImage1']); ?>"
                     style="width:32px;height:32px;object-fit:cover;border-radius:3px" onerror="this.style.display='none'">
                <?php endif; ?>
                <div style="flex:1;font-size:.82em">
                    <a href="edit-products.php?pid=<?php echo $tp['id']; ?>">
                        <?php echo htmlentities(mb_substr($tp['productName'],0,28)); ?><?php echo mb_strlen($tp['productName'])>28?'…':''; ?>
                    </a>
                </div>
                <span style="font-size:.8em;color:#888"><?php echo $tp['units']; ?> uds</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Actividad de login -->
        <div class="sec">Actividad reciente</div>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:14px;margin-bottom:14px;">
            <?php $lg_rows = mysqli_fetch_all($logins, MYSQLI_ASSOC); mysqli_stmt_close($st_lg); ?>
            <?php if (empty($lg_rows)): ?>
            <small class="text-muted">Sin actividad registrada.</small>
            <?php else: ?>
            <ul class="info-list">
            <?php foreach ($lg_rows as $lg): ?>
            <li>
                <span class="label label-<?php echo $lg['status']?'success':'important'; ?>" style="font-size:.7em">
                    <?php echo $lg['status']?'Entró':'Salió'; ?>
                </span>
                <small style="color:#888"><?php echo date('d/m/Y H:i', strtotime($lg['loginTime'])); ?></small>
            </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Nota del admin -->
        <div class="sec">Nota interna</div>
        <div class="note-box">
            <form method="post">
                <textarea name="admin_note" placeholder="Escribe aquí notas internas sobre este cliente..."><?php echo htmlentities($user['updationDate'] ?? ''); ?></textarea>
                <button type="submit" name="save_note" class="btn btn-mini btn-primary" style="margin-top:4px">
                    <i class="icon-save"></i> Guardar nota
                </button>
            </form>
        </div>

    </div><!-- /span4 -->
</div><!-- /row-fluid -->

</div><!-- /.content -->
</div><!-- /.span9 -->
</div><!-- /.row -->
</div><!-- /.container -->
</div><!-- /.wrapper -->

<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<?php if (!empty($chart_months)): ?>
<script>
new Chart(document.getElementById('chartClient'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_months_labels); ?>,
        datasets: [{
            label: 'Gasto ($)',
            data: <?php echo json_encode($chart_months_data); ?>,
            backgroundColor: 'rgba(51,122,183,0.7)',
            borderColor: '#337ab7',
            borderWidth: 1,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display:false } },
        scales: {
            y: { beginAtZero:true, ticks:{ callback: v=>'$'+v.toLocaleString('es-CO') } }
        }
    }
});
</script>
<?php endif; ?>
</body>
</html>
