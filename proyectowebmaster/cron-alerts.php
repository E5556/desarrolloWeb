<?php
/**
 * cron-alerts.php — Alertas automáticas
 * Ejecutar vía cron: php /ruta/cron-alerts.php
 * O llamar desde navegador (protegido por token): ?token=CRON_SECRET
 */
define('CRON_SECRET', 'ps_cron_2026_secret');
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
    $tok = $_GET['token'] ?? '';
    if ($tok !== CRON_SECRET) { http_response_code(403); exit('Forbidden'); }
}

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/mailer.php';

mysqli_report(MYSQLI_REPORT_OFF);

$log = [];
$now = date('Y-m-d H:i:s');

// ── 1. STOCK BAJO ────────────────────────────────────────────────────────────
$stock_threshold_r = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='low_stock_threshold' LIMIT 1");
$threshold = $stock_threshold_r ? (int)(mysqli_fetch_assoc($stock_threshold_r)['setting_value'] ?? 5) : 5;

$low_q = mysqli_query($con,
    "SELECT productName, stock_qty FROM products
     WHERE stock_qty <= $threshold AND stock_qty >= 0 AND productAvailability='Available'
     ORDER BY stock_qty ASC LIMIT 20");
$low_items = [];
while ($r = mysqli_fetch_assoc($low_q)) $low_items[] = $r;

if (!empty($low_items)) {
    $rows = '';
    foreach ($low_items as $p) {
        $rows .= "<tr><td style='padding:6px 12px'>" . htmlspecialchars($p['productName']) . "</td>"
               . "<td style='padding:6px 12px;color:#e8233a;font-weight:700'>" . (int)$p['stock_qty'] . " uds</td></tr>";
    }
    $html = "
    <h2 style='color:#e8233a'>⚠️ Alerta: Stock Bajo</h2>
    <p>Los siguientes productos están por debajo del umbral de <strong>$threshold unidades</strong>:</p>
    <table border='1' cellspacing='0' style='border-collapse:collapse;width:100%'>
        <tr style='background:#f5f5f5'><th style='padding:6px 12px'>Producto</th><th style='padding:6px 12px'>Stock actual</th></tr>
        $rows
    </table>
    <p style='margin-top:16px'><a href='/admin/manage-products.php'>Ver productos →</a></p>";
    notify_admin('low_stock_cron', ['html' => $html]);
    $log[] = "Stock bajo: " . count($low_items) . " productos alertados.";
}

// ── 2. PEDIDOS CONFIRMADOS SIN GESTIONAR (+4 horas) ─────────────────────────
$stuck_q = mysqli_query($con,
    "SELECT o.id, o.orderDate, o.group_ref,
            u.name AS cliente, COUNT(*) AS items
     FROM orders o
     LEFT JOIN users u ON u.id = o.userId
     WHERE o.orderStatus = 'Confirmada'
       AND o.orderDate <= DATE_SUB(NOW(), INTERVAL 4 HOUR)
     GROUP BY o.group_ref
     ORDER BY o.orderDate ASC LIMIT 20");
$stuck = [];
while ($r = mysqli_fetch_assoc($stuck_q)) $stuck[] = $r;

if (!empty($stuck)) {
    $rows = '';
    foreach ($stuck as $s) {
        $hrs = round((time() - strtotime($s['orderDate'])) / 3600, 1);
        $rows .= "<tr><td style='padding:6px 12px'>" . htmlspecialchars($s['cliente'] ?? 'N/A') . "</td>"
               . "<td style='padding:6px 12px'>" . htmlspecialchars($s['group_ref']) . "</td>"
               . "<td style='padding:6px 12px;color:#e67e22;font-weight:700'>{$hrs}h sin gestionar</td></tr>";
    }
    $html = "
    <h2 style='color:#e67e22'>🕐 Pedidos sin gestionar</h2>
    <p>Los siguientes pedidos llevan más de <strong>4 horas</strong> en estado <em>Confirmada</em> sin avanzar:</p>
    <table border='1' cellspacing='0' style='border-collapse:collapse;width:100%'>
        <tr style='background:#f5f5f5'><th style='padding:6px 12px'>Cliente</th><th style='padding:6px 12px'>Ref</th><th style='padding:6px 12px'>Tiempo</th></tr>
        $rows
    </table>
    <p style='margin-top:16px'><a href='/admin/pending-orders.php'>Ver pedidos pendientes →</a></p>";
    notify_admin('stuck_orders', ['html' => $html]);
    $log[] = "Pedidos sin gestionar: " . count($stuck) . " alertados.";
}

// ── 3. CARRITOS ABANDONADOS (+24 horas) ─────────────────────────────────────
$cart_q = mysqli_query($con,
    "SELECT u.name, u.email, COUNT(*) AS items, MAX(o.orderDate) AS ultima
     FROM orders o
     JOIN users u ON u.id = o.userId
     WHERE o.paymentMethod IS NULL
       AND o.orderDate < DATE_SUB(NOW(), INTERVAL 24 HOUR)
     GROUP BY o.userId
     ORDER BY ultima DESC LIMIT 10");
$carts = [];
while ($r = mysqli_fetch_assoc($cart_q)) $carts[] = $r;

if (!empty($carts)) {
    $rows = '';
    foreach ($carts as $c) {
        $rows .= "<tr><td style='padding:6px 12px'>" . htmlspecialchars($c['name']) . "</td>"
               . "<td style='padding:6px 12px'>" . htmlspecialchars($c['email']) . "</td>"
               . "<td style='padding:6px 12px'>" . (int)$c['items'] . " items</td>"
               . "<td style='padding:6px 12px'>" . $c['ultima'] . "</td></tr>";
    }
    $html = "
    <h2 style='color:#8e44ad'>🛒 Carritos Abandonados</h2>
    <p>Clientes con carritos abandonados hace más de <strong>24 horas</strong>:</p>
    <table border='1' cellspacing='0' style='border-collapse:collapse;width:100%'>
        <tr style='background:#f5f5f5'><th style='padding:6px 12px'>Cliente</th><th style='padding:6px 12px'>Email</th><th style='padding:6px 12px'>Items</th><th style='padding:6px 12px'>Última actividad</th></tr>
        $rows
    </table>
    <p style='margin-top:16px'><a href='/admin/abandoned-carts.php'>Ver carritos abandonados →</a></p>";
    notify_admin('abandoned_carts_cron', ['html' => $html]);
    $log[] = "Carritos abandonados: " . count($carts) . " clientes alertados.";
}

// ── 4. CLIENTES INACTIVOS (+30 días sin comprar) ─────────────────────────────
$inactive_q = mysqli_query($con,
    "SELECT u.name, u.email, MAX(o.orderDate) AS ultima_compra,
            COUNT(DISTINCT o.id) AS total_pedidos
     FROM users u
     JOIN orders o ON o.userId = u.id
     WHERE o.paymentMethod IS NOT NULL
     GROUP BY u.id
     HAVING ultima_compra < DATE_SUB(NOW(), INTERVAL 30 DAY)
     ORDER BY ultima_compra DESC LIMIT 15");
$inactive = [];
while ($r = mysqli_fetch_assoc($inactive_q)) $inactive[] = $r;

if (!empty($inactive)) {
    $rows = '';
    foreach ($inactive as $c) {
        $dias = round((time() - strtotime($c['ultima_compra'])) / 86400);
        $rows .= "<tr><td style='padding:6px 12px'>" . htmlspecialchars($c['name']) . "</td>"
               . "<td style='padding:6px 12px'>" . htmlspecialchars($c['email']) . "</td>"
               . "<td style='padding:6px 12px'>" . $c['ultima_compra'] . "</td>"
               . "<td style='padding:6px 12px;color:#7f8c8d'>{$dias} días</td></tr>";
    }
    $html = "
    <h2 style='color:#2980b9'>😴 Clientes Inactivos</h2>
    <p>Clientes que no compran hace más de <strong>30 días</strong> (considera enviarles un cupón):</p>
    <table border='1' cellspacing='0' style='border-collapse:collapse;width:100%'>
        <tr style='background:#f5f5f5'><th style='padding:6px 12px'>Cliente</th><th style='padding:6px 12px'>Email</th><th style='padding:6px 12px'>Última compra</th><th style='padding:6px 12px'>Días inactivo</th></tr>
        $rows
    </table>
    <p style='margin-top:16px'><a href='/admin/manage-users.php'>Ver clientes →</a></p>";
    notify_admin('inactive_clients', ['html' => $html]);
    $log[] = "Clientes inactivos: " . count($inactive) . " alertados.";
}

// ── 5. DEVOLUCIONES PENDIENTES SIN RESOLVER (+48 horas) ─────────────────────
$ret_q = @mysqli_query($con,
    "SELECT r.id, r.order_id, r.reason, r.created_at, u.name
     FROM returns r
     LEFT JOIN orders o ON o.id = r.order_id
     LEFT JOIN users u ON u.id = o.userId
     WHERE r.status = 'pending'
       AND r.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
     LIMIT 10");
$rets = [];
if ($ret_q) while ($r = mysqli_fetch_assoc($ret_q)) $rets[] = $r;

if (!empty($rets)) {
    $rows = '';
    foreach ($rets as $rv) {
        $hrs = round((time() - strtotime($rv['created_at'])) / 3600);
        $rows .= "<tr><td style='padding:6px 12px'>#" . (int)$rv['id'] . "</td>"
               . "<td style='padding:6px 12px'>" . htmlspecialchars($rv['name'] ?? 'N/A') . "</td>"
               . "<td style='padding:6px 12px'>" . htmlspecialchars(substr($rv['reason'], 0, 50)) . "</td>"
               . "<td style='padding:6px 12px;color:#e8233a'>{$hrs}h pendiente</td></tr>";
    }
    $html = "
    <h2 style='color:#c0392b'>↩️ Devoluciones Sin Resolver</h2>
    <p>Solicitudes de devolución pendientes hace más de <strong>48 horas</strong>:</p>
    <table border='1' cellspacing='0' style='border-collapse:collapse;width:100%'>
        <tr style='background:#f5f5f5'><th style='padding:6px 12px'>ID</th><th style='padding:6px 12px'>Cliente</th><th style='padding:6px 12px'>Motivo</th><th style='padding:6px 12px'>Tiempo</th></tr>
        $rows
    </table>
    <p style='margin-top:16px'><a href='/admin/returns.php'>Ver devoluciones →</a></p>";
    notify_admin('pending_returns', ['html' => $html]);
    $log[] = "Devoluciones sin resolver: " . count($rets) . " alertadas.";
}

// ── Log de ejecución ─────────────────────────────────────────────────────────
if (empty($log)) $log[] = "Sin alertas — todo en orden.";

// Guardar log en BD si existe tabla
@mysqli_query($con, "CREATE TABLE IF NOT EXISTS cron_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    script VARCHAR(100),
    result TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$result_json = mysqli_real_escape_string($con, implode(' | ', $log));
mysqli_query($con, "INSERT INTO cron_log (script, result) VALUES ('cron-alerts', '$result_json')");

if ($is_cli) {
    echo "[" . date('Y-m-d H:i:s') . "] cron-alerts:\n";
    foreach ($log as $l) echo "  - $l\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'time' => $now, 'log' => $log]);
}
