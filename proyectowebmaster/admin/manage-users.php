<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_users');


date_default_timezone_set('America/Bogota');

// ── BÚSQUEDA ──────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$where  = '';
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where = "WHERE u.name LIKE '%$s%' OR u.email LIKE '%$s%' OR u.contactno LIKE '%$s%'";
}

// ── MÉTRICAS GLOBALES ─────────────────────────────────────────────────────
$total_users    = intval((($__r = mysqli_query($con,"SELECT COUNT(*) n FROM users")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$new_this_month = intval((($__r = mysqli_query($con,"SELECT COUNT(*) n FROM users WHERE regDate>=DATE_FORMAT(NOW(),'%Y-%m-01')")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$buyers         = intval((($__r = mysqli_query($con,"SELECT COUNT(DISTINCT userId) n FROM orders")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
$repeat_buyers  = intval((($__r = mysqli_query($con,"SELECT COUNT(*) n FROM (SELECT userId FROM orders GROUP BY userId HAVING COUNT(*)>1) t")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);

// ── LISTA CLIENTES CON MÉTRICAS ───────────────────────────────────────────
$users = mysqli_query($con,
    "SELECT u.id, u.name, u.email, u.contactno, u.shippingCity, u.shippingState, u.regDate,
            COUNT(o.id)                                    AS total_orders,
            COALESCE(SUM(o.quantity * p.productPrice), 0)  AS total_spent,
            MAX(o.orderDate)                               AS last_order
     FROM users u
     LEFT JOIN orders o   ON o.userId    = u.id
     LEFT JOIN products p ON o.productId = p.id
     $where
     GROUP BY u.id
     ORDER BY total_spent DESC, u.regDate DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | CRM — Clientes</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <style>
        .kpi-row { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
        .kpi-card { flex:1 1 130px; background:#fff; border:1px solid #e0e0e0; border-radius:7px;
                    padding:14px 16px; text-align:center; }
        .kpi-card .val { font-size:1.9em; font-weight:700; line-height:1.1; }
        .kpi-card .lbl { font-size:.75em; color:#888; margin-top:2px; }
        .kpi-blue   { border-top:3px solid #337ab7; } .kpi-blue .val   { color:#337ab7; }
        .kpi-green  { border-top:3px solid #5cb85c; } .kpi-green .val  { color:#5cb85c; }
        .kpi-orange { border-top:3px solid #f0ad4e; } .kpi-orange .val { color:#f0ad4e; }
        .kpi-purple { border-top:3px solid #9b59b6; } .kpi-purple .val { color:#9b59b6; }

        .avatar { width:34px; height:34px; border-radius:50%; background:#337ab7; color:#fff;
                  font-size:.9em; font-weight:700; display:inline-flex; align-items:center;
                  justify-content:center; margin-right:8px; flex-shrink:0; }
        .avatar.vip    { background:#f0ad4e; }
        .avatar.repeat { background:#5cb85c; }

        .crm-table td { vertical-align:middle !important; font-size:.86em; }
        .crm-table th { font-size:.82em; background:#f9f9f9; }
        .badge-vip    { background:#f0ad4e; color:#fff; border-radius:10px; padding:2px 7px; font-size:.72em; font-weight:700; }
        .badge-repeat { background:#5cb85c; color:#fff; border-radius:10px; padding:2px 7px; font-size:.72em; font-weight:700; }
        .badge-new    { background:#337ab7; color:#fff; border-radius:10px; padding:2px 7px; font-size:.72em; font-weight:700; }
        .search-bar   { display:flex; gap:8px; margin-bottom:16px; }
        .search-bar input { flex:1; padding:6px 10px; border:1px solid #ccc; border-radius:4px; font-size:.9em; }
        .client-name  { display:flex; align-items:center; }
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
<div class="module">
<div class="module-head">
    <h3><i class="icon-group" style="margin-right:6px"></i>CRM — Gestión de Clientes <a href="export.php?type=users" class="btn btn-xs btn-default pull-right" style="margin-top:4px"><i class="icon-download"></i> Exportar CSV</a></h3>
</div>
<div class="module-body">

    <!-- KPIs -->
    <div class="kpi-row">
        <div class="kpi-card kpi-blue">
            <div class="val"><?php echo $total_users; ?></div>
            <div class="lbl">Total clientes</div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="val"><?php echo $new_this_month; ?></div>
            <div class="lbl">Nuevos este mes</div>
        </div>
        <div class="kpi-card kpi-orange">
            <div class="val"><?php echo $buyers; ?></div>
            <div class="lbl">Han comprado</div>
        </div>
        <div class="kpi-card kpi-purple">
            <div class="val"><?php echo $repeat_buyers; ?></div>
            <div class="lbl">Compradores recurrentes</div>
        </div>
    </div>

    <!-- BÚSQUEDA -->
    <form method="get" class="search-bar">
        <input type="text" name="q" placeholder="Buscar por nombre, email o teléfono..."
               value="<?php echo htmlentities($search); ?>">
        <button type="submit" class="btn btn-primary"><i class="icon-search"></i> Buscar</button>
        <?php if ($search): ?>
        <a href="manage-users.php" class="btn btn-default"><i class="icon-remove"></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- LEYENDA -->
    <div style="font-size:.78em;color:#888;margin-bottom:10px;">
        <span class="badge-vip">VIP</span> Top 20% en gasto &nbsp;
        <span class="badge-repeat">Recurrente</span> Más de 1 compra &nbsp;
        <span class="badge-new">Nuevo</span> Registrado este mes
    </div>

    <!-- TABLA -->
    <?php
    $rows       = mysqli_fetch_all($users, MYSQLI_ASSOC);
    $total_rows = count($rows);
    // calcular umbral VIP (top 20%)
    $spents = array_column($rows, 'total_spent');
    rsort($spents);
    $vip_threshold = isset($spents[max(0, floor($total_rows*0.2)-1)]) ? $spents[max(0,floor($total_rows*0.2)-1)] : 0;
    if ($vip_threshold <= 0 && !empty($spents)) $vip_threshold = $spents[0];
    ?>

    <?php if (empty($rows)): ?>
    <p class="text-muted" style="font-style:italic">No se encontraron clientes.</p>
    <?php else: ?>
    <table class="table table-bordered table-striped crm-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Ubicación</th>
                <th>Pedidos</th>
                <th>Total gastado</th>
                <th>Último pedido</th>
                <th>Registro</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $u):
            $initial  = strtoupper(mb_substr($u['name'], 0, 1));
            $is_vip   = floatval($u['total_spent']) >= $vip_threshold && floatval($u['total_spent']) > 0;
            $is_rep   = intval($u['total_orders']) > 1;
            $is_new   = strtotime($u['regDate']) >= strtotime('first day of this month');
            $av_class = $is_vip ? 'vip' : ($is_rep ? 'repeat' : '');
            $days_ago = $u['last_order'] ? round((time()-strtotime($u['last_order']))/86400) : null;
        ?>
        <tr>
            <td>
                <div class="client-name">
                    <div class="avatar <?php echo $av_class; ?>"><?php echo htmlentities($initial); ?></div>
                    <div>
                        <strong><?php echo htmlentities($u['name']); ?></strong><br>
                        <?php if ($is_vip):   ?><span class="badge-vip">VIP</span> <?php endif; ?>
                        <?php if ($is_rep):   ?><span class="badge-repeat">Recurrente</span> <?php endif; ?>
                        <?php if ($is_new):   ?><span class="badge-new">Nuevo</span> <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <?php echo htmlentities($u['email']); ?><br>
                <small style="color:#888"><?php echo htmlentities($u['contactno'] ?: '—'); ?></small>
            </td>
            <td>
                <small><?php echo htmlentities(($u['shippingCity']?$u['shippingCity'].', ':'').$u['shippingState']); ?></small>
            </td>
            <td style="text-align:center">
                <?php if ($u['total_orders'] > 0): ?>
                <strong><?php echo $u['total_orders']; ?></strong>
                <?php else: ?>
                <span style="color:#bbb">0</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($u['total_spent'] > 0): ?>
                <strong style="color:#337ab7">$<?php echo number_format($u['total_spent'],0,'.',','); ?></strong>
                <?php else: ?>
                <span style="color:#bbb">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($u['last_order']): ?>
                <small><?php echo date('d/m/Y', strtotime($u['last_order'])); ?></small><br>
                <small style="color:#<?php echo $days_ago>90?'d9534f':($days_ago>30?'f0ad4e':'5cb85c'); ?>">
                    hace <?php echo $days_ago; ?> días
                </small>
                <?php else: ?>
                <small style="color:#bbb">Sin pedidos</small>
                <?php endif; ?>
            </td>
            <td><small><?php echo date('d/m/Y', strtotime($u['regDate'])); ?></small></td>
            <td>
                <a href="customer-profile.php?id=<?php echo $u['id']; ?>" class="btn btn-mini btn-primary">
                    <i class="icon-eye-open"></i> Ver perfil
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <small class="text-muted"><?php echo $total_rows; ?> cliente(s) encontrado(s)<?php echo $search?' para "'.htmlentities($search).'"':''; ?></small>
    <?php endif; ?>

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
