<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Auto-create table
mysqli_query($con, "CREATE TABLE IF NOT EXISTS newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(200) NOT NULL UNIQUE,
    active TINYINT DEFAULT 1,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Actions
if (isset($_GET['del'])) {
    mysqli_query($con, "DELETE FROM newsletter WHERE id=".intval($_GET['del']));
    header('location:newsletter-subscribers.php?msg=deleted'); exit();
}
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE newsletter SET active = 1-active WHERE id=$tid");
    header('location:newsletter-subscribers.php'); exit();
}

$search = trim($_GET['s'] ?? '');
$where  = $search ? "WHERE email LIKE '%" . mysqli_real_escape_string($con, $search) . "%'" : '';
$filter = $_GET['filter'] ?? 'all';
if ($filter === 'active')   $where = $where ? "$where AND active=1" : "WHERE active=1";
if ($filter === 'inactive') $where = $where ? "$where AND active=0" : "WHERE active=0";

$total    = intval(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) n FROM newsletter"))['n']);
$activos  = intval(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) n FROM newsletter WHERE active=1"))['n']);

$res = mysqli_query($con, "SELECT * FROM newsletter $where ORDER BY subscribed_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Admin | Suscriptores Newsletter</title>
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
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
        <h3><i class="icon-envelope-alt"></i> Suscriptores de Newsletter</h3>
    </div>
    <div class="module-body">

        <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success"><button class="close" data-dismiss="alert">×</button>
            <?php echo $_GET['msg']==='deleted' ? 'Suscriptor eliminado.' : 'Acción completada.'; ?>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap">
            <div style="background:#f0faf0;border:1px solid #c3e6cb;border-radius:6px;padding:12px 20px;text-align:center;min-width:100px">
                <div style="font-size:24px;font-weight:700;color:#27ae60"><?php echo $activos; ?></div>
                <div style="font-size:12px;color:#555">Activos</div>
            </div>
            <div style="background:#f9f9f9;border:1px solid #eee;border-radius:6px;padding:12px 20px;text-align:center;min-width:100px">
                <div style="font-size:24px;font-weight:700;color:#555"><?php echo $total; ?></div>
                <div style="font-size:12px;color:#555">Total</div>
            </div>
            <div style="background:#fdf0f5;border:1px solid #f5c6d0;border-radius:6px;padding:12px 20px;text-align:center;min-width:100px">
                <div style="font-size:24px;font-weight:700;color:#e8233a"><?php echo $total - $activos; ?></div>
                <div style="font-size:12px;color:#555">Desuscritos</div>
            </div>
        </div>

        <!-- Filters + Search -->
        <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
            <a href="newsletter-subscribers.php" class="btn btn-sm <?php echo $filter==='all'?'btn-primary':''; ?>">Todos</a>
            <a href="newsletter-subscribers.php?filter=active" class="btn btn-sm <?php echo $filter==='active'?'btn-primary':''; ?>">Activos</a>
            <a href="newsletter-subscribers.php?filter=inactive" class="btn btn-sm <?php echo $filter==='inactive'?'btn-primary':''; ?>">Desuscritos</a>
            <form method="get" style="display:flex;gap:4px;margin-left:auto">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="s" value="<?php echo htmlspecialchars($search); ?>" class="span4" placeholder="Buscar email…" style="margin:0">
                <button type="submit" class="btn btn-sm"><i class="icon-search"></i></button>
                <?php if($search): ?><a href="newsletter-subscribers.php" class="btn btn-sm">×</a><?php endif; ?>
            </form>
            <a href="?export=csv&filter=<?php echo $filter; ?>&s=<?php echo urlencode($search); ?>" class="btn btn-sm btn-success"><i class="icon-download"></i> Exportar CSV</a>
        </div>

        <?php
        // CSV export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="newsletter_' . date('Ymd') . '.csv"');
            echo "\xEF\xBB\xBF"; // BOM UTF-8
            echo "ID,Email,Estado,Fecha\r\n";
            while($row = mysqli_fetch_assoc($res)) {
                echo $row['id'].',"'.$row['email'].'",'.($row['active']?'Activo':'Desuscrito').',"'.date('d/m/Y H:i',strtotime($row['subscribed_at']))."\"\r\n";
            }
            exit();
        }
        ?>

        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Email</th>
                    <th style="width:100px">Estado</th>
                    <th>Fecha suscripción</th>
                    <th style="width:100px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$res || mysqli_num_rows($res) === 0): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:#aaa">No hay suscriptores<?php echo $search ? " para '$search'" : ''; ?>.</td></tr>
            <?php else: while($row = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <?php if($row['active']): ?>
                    <span class="label label-success">Activo</span>
                    <?php else: ?>
                    <span class="label label-inverse">Desuscrito</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px"><?php echo date('d/m/Y H:i', strtotime($row['subscribed_at'])); ?></td>
                <td>
                    <a href="newsletter-subscribers.php?toggle=<?php echo $row['id']; ?>" class="btn btn-mini <?php echo $row['active']?'btn-warning':'btn-success'; ?>" title="<?php echo $row['active']?'Desactivar':'Activar'; ?>">
                        <i class="icon-<?php echo $row['active']?'ban-circle':'ok'; ?>"></i>
                    </a>
                    <a href="newsletter-subscribers.php?del=<?php echo $row['id']; ?>" class="btn btn-mini btn-danger" title="Eliminar"
                       onclick="return confirm('¿Eliminar este suscriptor?')"><i class="icon-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
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
