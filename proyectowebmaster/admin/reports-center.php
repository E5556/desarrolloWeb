<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_stats');

$msg = '';

// Ejecutar cron manualmente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $scripts = [
        'alerts' => realpath(__DIR__ . '/../cron-alerts.php'),
        'daily'  => realpath(__DIR__ . '/../cron-daily-report.php'),
        'weekly' => realpath(__DIR__ . '/../cron-weekly-report.php'),
    ];
    if (isset($scripts[$action]) && $scripts[$action]) {
        $php = PHP_BINARY ?: 'php';
        $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($scripts[$action]) . ' 2>&1';
        $out = [];
        exec($cmd, $out, $code);
        $output = implode(' ', $out);
        if ($code === 0) {
            $msg = '<div class="alert alert-success">✅ ' . htmlspecialchars($output ?: 'Ejecutado correctamente') . '</div>';
        } else {
            $msg = '<div class="alert alert-danger">❌ ' . htmlspecialchars($output ?: 'Error al ejecutar') . '</div>';
        }
    }
}

// Últimas ejecuciones del log
@mysqli_query($con, "CREATE TABLE IF NOT EXISTS cron_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    script VARCHAR(100),
    result TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$log_q = mysqli_query($con, "SELECT * FROM cron_log ORDER BY executed_at DESC LIMIT 30");
$logs = [];
while ($r = mysqli_fetch_assoc($log_q)) $logs[] = $r;

// Stats rápidas para el panel
$hoy = date('Y-m-d');
$stock_thr_r = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='low_stock_threshold' LIMIT 1");
$thr = $stock_thr_r ? (int)(mysqli_fetch_assoc($stock_thr_r)['setting_value'] ?? 5) : 5;

$low_stock_r = mysqli_query($con, "SELECT COUNT(*) n FROM products WHERE stock_qty <= $thr AND stock_qty >= 0");
$low_stock_n = $low_stock_r ? (int)(mysqli_fetch_assoc($low_stock_r)['n']) : 0;

$stuck_r = mysqli_query($con, "SELECT COUNT(DISTINCT group_ref) n FROM orders WHERE orderStatus='Confirmada' AND orderDate <= DATE_SUB(NOW(), INTERVAL 4 HOUR)");
$stuck_n = $stuck_r ? (int)(mysqli_fetch_assoc($stuck_r)['n']) : 0;

$carts_r = mysqli_query($con, "SELECT COUNT(DISTINCT userId) n FROM orders WHERE paymentMethod IS NULL AND orderDate < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$carts_n = $carts_r ? (int)(mysqli_fetch_assoc($carts_r)['n']) : 0;

$inactive_r = mysqli_query($con, "SELECT COUNT(DISTINCT u.id) n FROM users u JOIN orders o ON o.userId=u.id WHERE o.paymentMethod IS NOT NULL GROUP BY u.id HAVING MAX(o.orderDate) < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$inactive_n = $inactive_r ? mysqli_num_rows($inactive_r) : 0;

$returns_r = @mysqli_query($con, "SELECT COUNT(*) n FROM returns WHERE status='pending' AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
$returns_n = $returns_r ? (int)(mysqli_fetch_assoc($returns_r)['n']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Centro de Reportes | <?php echo $_ADMIN_SITE_NAME; ?></title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="css/theme.css">
<style>
.alert-card { border-radius:10px; padding:20px; color:#fff; text-align:center; margin-bottom:16px; }
.alert-card .num { font-size:42px; font-weight:700; line-height:1; }
.alert-card .lbl { font-size:13px; opacity:.9; margin-top:4px; }
.alert-card.danger  { background:linear-gradient(135deg,#e8233a,#c0392b); }
.alert-card.warning { background:linear-gradient(135deg,#e67e22,#d35400); }
.alert-card.info    { background:linear-gradient(135deg,#3498db,#2980b9); }
.alert-card.purple  { background:linear-gradient(135deg,#9b59b6,#8e44ad); }
.alert-card.teal    { background:linear-gradient(135deg,#1abc9c,#16a085); }
.cron-card { border:1px solid #e0e0e0; border-radius:10px; padding:20px; margin-bottom:16px; background:#fff; }
.cron-card h4 { margin:0 0 8px; color:#2c3e50; }
.cron-card p  { color:#888; font-size:13px; margin-bottom:12px; }
.badge-script { font-size:11px; padding:3px 8px; border-radius:4px; }
.log-ok   { color:#27ae60; }
.log-err  { color:#e8233a; }
</style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">

<div style="padding:20px 0">
    <h2 style="color:#2c3e50;margin-bottom:4px">🤖 Centro de Reportes y Alertas Automáticas</h2>
    <p style="color:#888;margin-bottom:20px">Monitoreo en tiempo real y envío de reportes por email.</p>

    <?php if ($msg) echo $msg; ?>

    <!-- Estado actual de alertas -->
    <h4 style="color:#555;margin-bottom:12px">📊 Estado Actual</h4>
    <div class="row">
        <div class="span2">
            <div class="alert-card <?php echo $low_stock_n > 0 ? 'danger' : 'teal'; ?>">
                <div class="num"><?php echo $low_stock_n; ?></div>
                <div class="lbl">⚠️ Stock bajo<br>(≤<?php echo $thr; ?> uds)</div>
            </div>
        </div>
        <div class="span2">
            <div class="alert-card <?php echo $stuck_n > 0 ? 'warning' : 'teal'; ?>">
                <div class="num"><?php echo $stuck_n; ?></div>
                <div class="lbl">🕐 Pedidos sin gestionar<br>(+4h Confirmados)</div>
            </div>
        </div>
        <div class="span2">
            <div class="alert-card <?php echo $carts_n > 0 ? 'purple' : 'teal'; ?>">
                <div class="num"><?php echo $carts_n; ?></div>
                <div class="lbl">🛒 Carritos<br>abandonados (+24h)</div>
            </div>
        </div>
        <div class="span2">
            <div class="alert-card info">
                <div class="num"><?php echo $inactive_n; ?></div>
                <div class="lbl">😴 Clientes inactivos<br>(+30 días)</div>
            </div>
        </div>
        <div class="span2">
            <div class="alert-card <?php echo $returns_n > 0 ? 'danger' : 'teal'; ?>">
                <div class="num"><?php echo $returns_n; ?></div>
                <div class="lbl">↩️ Devoluciones<br>sin resolver (+48h)</div>
            </div>
        </div>
    </div>

    <!-- Ejecutar scripts manualmente -->
    <h4 style="color:#555;margin:24px 0 12px">⚡ Ejecutar Ahora</h4>
    <div class="row">
        <div class="span3">
            <div class="cron-card">
                <h4>🚨 Alertas Automáticas</h4>
                <p>Envía alertas de stock bajo, pedidos sin gestionar, carritos abandonados, clientes inactivos y devoluciones pendientes.</p>
                <small class="badge-script" style="background:#fdecea;color:#e8233a">cron-alerts.php</small>
                <br><br>
                <form method="post">
                    <input type="hidden" name="action" value="alerts">
                    <button type="submit" class="btn btn-danger btn-block">▶ Ejecutar alertas</button>
                </form>
            </div>
        </div>
        <div class="span3">
            <div class="cron-card">
                <h4>📊 Reporte Diario</h4>
                <p>Envía un email con el resumen de ventas del día anterior: pedidos, ingresos, top productos, comparativa y stock crítico.</p>
                <small class="badge-script" style="background:#eaf4fb;color:#3498db">cron-daily-report.php</small>
                <br><br>
                <form method="post">
                    <input type="hidden" name="action" value="daily">
                    <button type="submit" class="btn btn-primary btn-block">▶ Enviar reporte diario</button>
                </form>
            </div>
        </div>
        <div class="span3">
            <div class="cron-card">
                <h4>📈 Resumen Semanal</h4>
                <p>Envía el resumen de la semana: top productos, rendimiento de asesores, categorías, comparativa semanal y nuevos clientes.</p>
                <small class="badge-script" style="background:#f0faf5;color:#27ae60">cron-weekly-report.php</small>
                <br><br>
                <form method="post">
                    <input type="hidden" name="action" value="weekly">
                    <button type="submit" class="btn btn-success btn-block">▶ Enviar resumen semanal</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Instrucciones de cron -->
    <div class="alert alert-info" style="margin-top:8px">
        <strong>⏰ Automatización con Cron (servidor Linux):</strong><br>
        <code>0 8 * * * php <?php echo realpath(__DIR__ . '/..'); ?>/cron-daily-report.php</code><br>
        <code>0 8 * * 1 php <?php echo realpath(__DIR__ . '/..'); ?>/cron-weekly-report.php</code><br>
        <code>0 */6 * * * php <?php echo realpath(__DIR__ . '/..'); ?>/cron-alerts.php</code><br>
        <small>En InfinityFree usa el panel Softaculous → Cron Jobs, o llama las URLs con <code>?token=ps_cron_2026_secret</code></small>
    </div>

    <!-- Log de ejecuciones -->
    <h4 style="color:#555;margin:24px 0 12px">📋 Historial de Ejecuciones</h4>
    <?php if (empty($logs)): ?>
    <div class="alert alert-info">Sin ejecuciones registradas aún. Ejecuta algún script arriba.</div>
    <?php else: ?>
    <table class="table table-striped table-bordered" style="font-size:13px">
        <thead><tr>
            <th>Script</th>
            <th>Resultado</th>
            <th>Ejecutado</th>
        </tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td><span class="badge"><?php echo htmlspecialchars($l['script']); ?></span></td>
            <td class="<?php echo strpos($l['result'], 'Error') !== false ? 'log-err' : 'log-ok'; ?>">
                <?php echo htmlspecialchars($l['result']); ?>
            </td>
            <td><?php echo $l['executed_at']; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div><!-- /padding -->
</div><!-- /span9 -->
</div><!-- /row -->
</div><!-- /container -->
<?php include('include/footer.php'); ?>
</body>
</html>
