<?php
/**
 * cron-daily-report.php — Reporte diario de ventas
 * Ejecutar vía cron cada día a las 8am: 0 8 * * * php /ruta/cron-daily-report.php
 * O desde navegador: ?token=CRON_SECRET
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

$ayer = date('Y-m-d', strtotime('-1 day'));
$ayer_label = date('d/m/Y', strtotime('-1 day'));

// ── Ventas del día anterior ──────────────────────────────────────────────────
$ventas_q = mysqli_query($con,
    "SELECT COUNT(DISTINCT group_ref) AS pedidos,
            COUNT(*) AS items,
            SUM(quantity * productPrice) AS ingresos
     FROM orders o
     JOIN products p ON p.id = o.productId
     WHERE DATE(o.orderDate) = '$ayer'
       AND o.paymentMethod IS NOT NULL");
$ventas = mysqli_fetch_assoc($ventas_q);
$pedidos   = (int)($ventas['pedidos'] ?? 0);
$ingresos  = number_format((float)($ventas['ingresos'] ?? 0), 0, ',', '.');

// ── Top 5 productos del día ──────────────────────────────────────────────────
$top_q = mysqli_query($con,
    "SELECT p.productName, SUM(o.quantity) AS vendidos,
            SUM(o.quantity * p.productPrice) AS total
     FROM orders o
     JOIN products p ON p.id = o.productId
     WHERE DATE(o.orderDate) = '$ayer' AND o.paymentMethod IS NOT NULL
     GROUP BY o.productId ORDER BY vendidos DESC LIMIT 5");
$top_rows = '';
$pos = 1;
while ($r = mysqli_fetch_assoc($top_q)) {
    $top_rows .= "<tr>
        <td style='padding:6px 12px'>{$pos}</td>
        <td style='padding:6px 12px'>" . htmlspecialchars($r['productName']) . "</td>
        <td style='padding:6px 12px;text-align:center'>" . (int)$r['vendidos'] . "</td>
        <td style='padding:6px 12px;text-align:right'>$" . number_format((float)$r['total'], 0, ',', '.') . "</td>
    </tr>";
    $pos++;
}
if (!$top_rows) $top_rows = "<tr><td colspan='4' style='padding:10px;text-align:center;color:#999'>Sin ventas ayer</td></tr>";

// ── Pedidos por estado ────────────────────────────────────────────────────────
$estados_q = mysqli_query($con,
    "SELECT orderStatus, COUNT(DISTINCT group_ref) AS total
     FROM orders
     WHERE DATE(orderDate) = '$ayer'
     GROUP BY orderStatus");
$estados_rows = '';
while ($r = mysqli_fetch_assoc($estados_q)) {
    $estados_rows .= "<tr>
        <td style='padding:6px 12px'>" . htmlspecialchars($r['orderStatus'] ?? 'Sin estado') . "</td>
        <td style='padding:6px 12px;text-align:center'>" . (int)$r['total'] . "</td>
    </tr>";
}
if (!$estados_rows) $estados_rows = "<tr><td colspan='2' style='padding:10px;text-align:center;color:#999'>Sin pedidos</td></tr>";

// ── Nuevos clientes ───────────────────────────────────────────────────────────
$new_clients_r = mysqli_query($con,
    "SELECT COUNT(*) n FROM users WHERE DATE(created_at) = '$ayer'");
$new_clients = $new_clients_r ? (int)(mysqli_fetch_assoc($new_clients_r)['n'] ?? 0) : 0;

// ── Stock crítico al momento ──────────────────────────────────────────────────
$critico_q = mysqli_query($con,
    "SELECT productName, stock_qty FROM products
     WHERE stock_qty <= 3 AND stock_qty >= 0 ORDER BY stock_qty ASC LIMIT 5");
$critico_rows = '';
while ($r = mysqli_fetch_assoc($critico_q)) {
    $color = $r['stock_qty'] == 0 ? '#e8233a' : '#e67e22';
    $critico_rows .= "<tr>
        <td style='padding:6px 12px'>" . htmlspecialchars($r['productName']) . "</td>
        <td style='padding:6px 12px;color:{$color};font-weight:700'>" . (int)$r['stock_qty'] . " uds</td>
    </tr>";
}
if (!$critico_rows) $critico_rows = "<tr><td colspan='2' style='padding:10px;text-align:center;color:#27ae60'>✅ Sin productos críticos</td></tr>";

// ── Comparativa con día anterior ─────────────────────────────────────────────
$antes_q = mysqli_query($con,
    "SELECT COUNT(DISTINCT group_ref) AS pedidos,
            SUM(quantity * productPrice) AS ingresos
     FROM orders o
     JOIN products p ON p.id = o.productId
     WHERE DATE(o.orderDate) = DATE_SUB('$ayer', INTERVAL 1 DAY)
       AND o.paymentMethod IS NOT NULL");
$antes = mysqli_fetch_assoc($antes_q);
$diff_pedidos  = $pedidos - (int)($antes['pedidos'] ?? 0);
$diff_ingresos = (float)($ventas['ingresos'] ?? 0) - (float)($antes['ingresos'] ?? 0);
$arrow_p = $diff_pedidos >= 0 ? '▲' : '▼';
$arrow_i = $diff_ingresos >= 0 ? '▲' : '▼';
$color_p = $diff_pedidos >= 0 ? '#27ae60' : '#e8233a';
$color_i = $diff_ingresos >= 0 ? '#27ae60' : '#e8233a';

// ── Construir email ───────────────────────────────────────────────────────────
$html = "
<!DOCTYPE html><html><head><meta charset='utf-8'></head><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#333'>

<div style='background:#2c3e50;color:#fff;padding:24px;border-radius:8px 8px 0 0'>
    <h1 style='margin:0;font-size:22px'>📊 Reporte Diario de Ventas</h1>
    <p style='margin:6px 0 0;opacity:.8'>$ayer_label</p>
</div>

<div style='background:#f8f9fa;padding:20px;display:flex;gap:16px'>
    <div style='background:#fff;border-radius:8px;padding:16px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:32px;font-weight:700;color:#2c3e50'>$pedidos</div>
        <div style='color:#888;font-size:13px'>Pedidos</div>
        <div style='color:{$color_p};font-size:12px'>{$arrow_p} " . abs($diff_pedidos) . " vs anteayer</div>
    </div>
    <div style='background:#fff;border-radius:8px;padding:16px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:32px;font-weight:700;color:#27ae60'>\$$ingresos</div>
        <div style='color:#888;font-size:13px'>Ingresos</div>
        <div style='color:{$color_i};font-size:12px'>{$arrow_i} \$" . number_format(abs($diff_ingresos), 0, ',', '.') . " vs anteayer</div>
    </div>
    <div style='background:#fff;border-radius:8px;padding:16px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:32px;font-weight:700;color:#3498db'>$new_clients</div>
        <div style='color:#888;font-size:13px'>Nuevos clientes</div>
    </div>
</div>

<div style='padding:20px;background:#fff'>
    <h3 style='color:#2c3e50;border-bottom:2px solid #ecf0f1;padding-bottom:8px'>🏆 Top 5 Productos</h3>
    <table width='100%' border='1' cellspacing='0' style='border-collapse:collapse;font-size:14px'>
        <tr style='background:#f5f5f5'><th style='padding:8px 12px'>#</th><th style='padding:8px 12px'>Producto</th><th style='padding:8px 12px'>Vendidos</th><th style='padding:8px 12px'>Total</th></tr>
        $top_rows
    </table>
</div>

<div style='padding:20px;background:#f8f9fa'>
    <h3 style='color:#2c3e50;border-bottom:2px solid #ecf0f1;padding-bottom:8px'>📦 Pedidos por Estado</h3>
    <table width='100%' border='1' cellspacing='0' style='border-collapse:collapse;font-size:14px'>
        <tr style='background:#f5f5f5'><th style='padding:8px 12px'>Estado</th><th style='padding:8px 12px'>Cantidad</th></tr>
        $estados_rows
    </table>
</div>

<div style='padding:20px;background:#fff'>
    <h3 style='color:#e67e22;border-bottom:2px solid #ecf0f1;padding-bottom:8px'>⚠️ Stock Crítico (≤3 uds)</h3>
    <table width='100%' border='1' cellspacing='0' style='border-collapse:collapse;font-size:14px'>
        <tr style='background:#f5f5f5'><th style='padding:8px 12px'>Producto</th><th style='padding:8px 12px'>Stock</th></tr>
        $critico_rows
    </table>
</div>

<div style='background:#2c3e50;color:#fff;padding:16px;border-radius:0 0 8px 8px;text-align:center;font-size:13px'>
    <a href='/admin/dashboard.php' style='color:#3498db'>Ir al Panel Admin →</a>
    &nbsp;|&nbsp;
    <a href='/admin/statistics.php' style='color:#3498db'>Ver Estadísticas →</a>
</div>

</body></html>";

$subject = "📊 Reporte diario — $ayer_label";
$sent = send_email_raw(null, null, $subject, $html);

// Log
@mysqli_query($con, "CREATE TABLE IF NOT EXISTS cron_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    script VARCHAR(100),
    result TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$res = $sent ? "Email enviado OK — $pedidos pedidos, \$$ingresos ingresos" : "Error al enviar email";
$res_esc = mysqli_real_escape_string($con, $res);
mysqli_query($con, "INSERT INTO cron_log (script, result) VALUES ('cron-daily-report', '$res_esc')");

if ($is_cli) {
    echo "[" . date('Y-m-d H:i:s') . "] cron-daily-report: $res\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['ok' => $sent, 'result' => $res, 'pedidos' => $pedidos, 'ingresos' => $ingresos]);
}
