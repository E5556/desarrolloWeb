<?php
/**
 * cron-weekly-report.php — Resumen semanal de rendimiento
 * Ejecutar vía cron cada lunes a las 8am: 0 8 * * 1 php /ruta/cron-weekly-report.php
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

$semana_inicio = date('Y-m-d', strtotime('last monday -7 days'));
$semana_fin    = date('Y-m-d', strtotime('last sunday'));
$label = date('d/m', strtotime($semana_inicio)) . ' – ' . date('d/m/Y', strtotime($semana_fin));

// ── Totales de la semana ──────────────────────────────────────────────────────
$tot_q = mysqli_query($con,
    "SELECT COUNT(DISTINCT o.group_ref) AS pedidos,
            SUM(o.quantity * p.productPrice) AS ingresos,
            COUNT(DISTINCT o.userId) AS clientes_activos
     FROM orders o JOIN products p ON p.id = o.productId
     WHERE DATE(o.orderDate) BETWEEN '$semana_inicio' AND '$semana_fin'
       AND o.paymentMethod IS NOT NULL");
$tot = mysqli_fetch_assoc($tot_q);
$pedidos  = (int)($tot['pedidos'] ?? 0);
$ingresos = (float)($tot['ingresos'] ?? 0);
$clientes = (int)($tot['clientes_activos'] ?? 0);
$ticket_prom = $pedidos > 0 ? $ingresos / $pedidos : 0;

// ── Semana anterior para comparar ────────────────────────────────────────────
$prev_ini = date('Y-m-d', strtotime($semana_inicio . ' -7 days'));
$prev_fin = date('Y-m-d', strtotime($semana_fin . ' -7 days'));
$prev_q = mysqli_query($con,
    "SELECT COUNT(DISTINCT o.group_ref) AS pedidos,
            SUM(o.quantity * p.productPrice) AS ingresos
     FROM orders o JOIN products p ON p.id = o.productId
     WHERE DATE(o.orderDate) BETWEEN '$prev_ini' AND '$prev_fin'
       AND o.paymentMethod IS NOT NULL");
$prev = mysqli_fetch_assoc($prev_q);
$prev_pedidos  = (int)($prev['pedidos'] ?? 0);
$prev_ingresos = (float)($prev['ingresos'] ?? 0);

$pct_pedidos  = $prev_pedidos  > 0 ? round((($pedidos  - $prev_pedidos)  / $prev_pedidos)  * 100, 1) : 0;
$pct_ingresos = $prev_ingresos > 0 ? round((($ingresos - $prev_ingresos) / $prev_ingresos) * 100, 1) : 0;

$fmt = fn($n) => number_format($n, 0, ',', '.');

// ── Top 5 productos de la semana ─────────────────────────────────────────────
$top_q = mysqli_query($con,
    "SELECT p.productName, SUM(o.quantity) AS vendidos, SUM(o.quantity*p.productPrice) AS total
     FROM orders o JOIN products p ON p.id = o.productId
     WHERE DATE(o.orderDate) BETWEEN '$semana_inicio' AND '$semana_fin'
       AND o.paymentMethod IS NOT NULL
     GROUP BY o.productId ORDER BY vendidos DESC LIMIT 5");
$top_rows = '';
$pos = 1;
while ($r = mysqli_fetch_assoc($top_q)) {
    $medal = $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : $pos));
    $top_rows .= "<tr>
        <td style='padding:8px 12px;text-align:center'>{$medal}</td>
        <td style='padding:8px 12px'>" . htmlspecialchars($r['productName']) . "</td>
        <td style='padding:8px 12px;text-align:center'>" . (int)$r['vendidos'] . "</td>
        <td style='padding:8px 12px;text-align:right'>\$" . $fmt($r['total']) . "</td>
    </tr>";
    $pos++;
}
if (!$top_rows) $top_rows = "<tr><td colspan='4' style='padding:10px;text-align:center;color:#999'>Sin ventas esta semana</td></tr>";

// ── Rendimiento por asesor ────────────────────────────────────────────────────
$asesor_q = mysqli_query($con,
    "SELECT a.username,
            COUNT(DISTINCT o.group_ref) AS pedidos,
            SUM(o.quantity * p.productPrice) AS valor,
            SUM(CASE WHEN o.orderStatus='Entregada' THEN 1 ELSE 0 END) AS entregados
     FROM orders o
     JOIN products p ON p.id = o.productId
     JOIN admin a ON a.id = o.created_by
     WHERE DATE(o.orderDate) BETWEEN '$semana_inicio' AND '$semana_fin'
     GROUP BY o.created_by ORDER BY valor DESC LIMIT 10");
$asesor_rows = '';
while ($r = mysqli_fetch_assoc($asesor_q)) {
    $conv = $r['pedidos'] > 0 ? round(($r['entregados'] / $r['pedidos']) * 100) : 0;
    $asesor_rows .= "<tr>
        <td style='padding:8px 12px'>" . htmlspecialchars($r['username']) . "</td>
        <td style='padding:8px 12px;text-align:center'>" . (int)$r['pedidos'] . "</td>
        <td style='padding:8px 12px;text-align:right'>\$" . $fmt($r['valor']) . "</td>
        <td style='padding:8px 12px;text-align:center'>{$conv}%</td>
    </tr>";
}
if (!$asesor_rows) $asesor_rows = "<tr><td colspan='4' style='padding:10px;text-align:center;color:#999'>Sin datos de asesores</td></tr>";

// ── Categorías más vendidas ───────────────────────────────────────────────────
$cat_q = mysqli_query($con,
    "SELECT c.categoryName, SUM(o.quantity) AS vendidos, SUM(o.quantity*p.productPrice) AS total
     FROM orders o
     JOIN products p ON p.id = o.productId
     JOIN category c ON c.id = p.category
     WHERE DATE(o.orderDate) BETWEEN '$semana_inicio' AND '$semana_fin'
       AND o.paymentMethod IS NOT NULL
     GROUP BY p.category ORDER BY total DESC LIMIT 5");
$cat_rows = '';
while ($r = mysqli_fetch_assoc($cat_q)) {
    $cat_rows .= "<tr>
        <td style='padding:8px 12px'>" . htmlspecialchars($r['categoryName']) . "</td>
        <td style='padding:8px 12px;text-align:center'>" . (int)$r['vendidos'] . "</td>
        <td style='padding:8px 12px;text-align:right'>\$" . $fmt($r['total']) . "</td>
    </tr>";
}
if (!$cat_rows) $cat_rows = "<tr><td colspan='3' style='padding:10px;text-align:center;color:#999'>Sin datos</td></tr>";

// ── Nuevos clientes de la semana ──────────────────────────────────────────────
$new_r = mysqli_query($con, "SELECT COUNT(*) n FROM users WHERE DATE(created_at) BETWEEN '$semana_inicio' AND '$semana_fin'");
$new_clients = $new_r ? (int)(mysqli_fetch_assoc($new_r)['n'] ?? 0) : 0;

// ── Reseñas de la semana ──────────────────────────────────────────────────────
$rev_r = @mysqli_query($con, "SELECT COUNT(*) n, AVG(rating) avg_r FROM productreviews WHERE DATE(created_at) BETWEEN '$semana_inicio' AND '$semana_fin'");
$rev = $rev_r ? mysqli_fetch_assoc($rev_r) : ['n' => 0, 'avg_r' => 0];

// ── Construir indicadores de cambio ──────────────────────────────────────────
$ind_p = ($pct_pedidos >= 0 ? '▲ +' : '▼ ') . $pct_pedidos . '% vs semana anterior';
$ind_i = ($pct_ingresos >= 0 ? '▲ +' : '▼ ') . $pct_ingresos . '% vs semana anterior';
$col_p = $pct_pedidos >= 0 ? '#27ae60' : '#e8233a';
$col_i = $pct_ingresos >= 0 ? '#27ae60' : '#e8233a';

$html = "
<!DOCTYPE html><html><head><meta charset='utf-8'></head><body style='font-family:Arial,sans-serif;max-width:650px;margin:0 auto;color:#333'>

<div style='background:linear-gradient(135deg,#1a252f,#2c3e50);color:#fff;padding:28px;border-radius:8px 8px 0 0'>
    <h1 style='margin:0;font-size:24px'>📈 Resumen Semanal</h1>
    <p style='margin:6px 0 0;opacity:.8;font-size:14px'>Semana: $label</p>
</div>

<div style='background:#f8f9fa;padding:20px;gap:12px;display:flex;flex-wrap:wrap'>
    <div style='background:#fff;border-radius:8px;padding:16px;min-width:130px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:28px;font-weight:700;color:#2c3e50'>$pedidos</div>
        <div style='color:#888;font-size:12px;margin-top:4px'>Pedidos</div>
        <div style='color:{$col_p};font-size:11px;margin-top:4px'>$ind_p</div>
    </div>
    <div style='background:#fff;border-radius:8px;padding:16px;min-width:130px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:28px;font-weight:700;color:#27ae60'>\$" . $fmt($ingresos) . "</div>
        <div style='color:#888;font-size:12px;margin-top:4px'>Ingresos</div>
        <div style='color:{$col_i};font-size:11px;margin-top:4px'>$ind_i</div>
    </div>
    <div style='background:#fff;border-radius:8px;padding:16px;min-width:130px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:28px;font-weight:700;color:#3498db'>\$" . $fmt($ticket_prom) . "</div>
        <div style='color:#888;font-size:12px;margin-top:4px'>Ticket promedio</div>
    </div>
    <div style='background:#fff;border-radius:8px;padding:16px;min-width:130px;flex:1;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.08)'>
        <div style='font-size:28px;font-weight:700;color:#9b59b6'>$new_clients</div>
        <div style='color:#888;font-size:12px;margin-top:4px'>Nuevos clientes</div>
    </div>
</div>

<div style='padding:20px;background:#fff'>
    <h3 style='color:#2c3e50;border-bottom:2px solid #ecf0f1;padding-bottom:8px'>🏆 Top Productos de la Semana</h3>
    <table width='100%' border='1' cellspacing='0' style='border-collapse:collapse;font-size:14px'>
        <tr style='background:#f5f5f5'><th style='padding:8px'>#</th><th style='padding:8px'>Producto</th><th style='padding:8px'>Vendidos</th><th style='padding:8px'>Total</th></tr>
        $top_rows
    </table>
</div>

<div style='padding:20px;background:#f8f9fa'>
    <h3 style='color:#2c3e50;border-bottom:2px solid #ecf0f1;padding-bottom:8px'>👨‍💼 Rendimiento Asesores</h3>
    <table width='100%' border='1' cellspacing='0' style='border-collapse:collapse;font-size:14px'>
        <tr style='background:#f5f5f5'><th style='padding:8px'>Asesor</th><th style='padding:8px'>Pedidos</th><th style='padding:8px'>Valor</th><th style='padding:8px'>Conversión</th></tr>
        $asesor_rows
    </table>
</div>

<div style='padding:20px;background:#fff'>
    <h3 style='color:#2c3e50;border-bottom:2px solid #ecf0f1;padding-bottom:8px'>📂 Categorías Más Vendidas</h3>
    <table width='100%' border='1' cellspacing='0' style='border-collapse:collapse;font-size:14px'>
        <tr style='background:#f5f5f5'><th style='padding:8px'>Categoría</th><th style='padding:8px'>Unidades</th><th style='padding:8px'>Total</th></tr>
        $cat_rows
    </table>
</div>

<div style='padding:16px 20px;background:#eaf4fb;border-left:4px solid #3498db'>
    <strong>📝 Reseñas de la semana:</strong> " . (int)($rev['n'] ?? 0) . " reseñas
    " . ($rev['avg_r'] ? "— promedio ⭐ " . number_format((float)$rev['avg_r'], 1) : '') . "
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>👥 Clientes que compraron:</strong> $clientes
</div>

<div style='background:#2c3e50;color:#fff;padding:16px;border-radius:0 0 8px 8px;text-align:center;font-size:13px'>
    <a href='/admin/statistics.php' style='color:#3498db'>Ver Estadísticas Completas →</a>
    &nbsp;|&nbsp;
    <a href='/admin/asesor-report.php' style='color:#3498db'>Reporte Asesores →</a>
</div>

</body></html>";

$subject = "📈 Resumen semanal — $label";
$sent = send_email_raw(null, null, $subject, $html);

@mysqli_query($con, "CREATE TABLE IF NOT EXISTS cron_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    script VARCHAR(100),
    result TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$res = $sent ? "Reporte semanal enviado OK — $pedidos pedidos, \$" . $fmt($ingresos) : "Error al enviar email";
$res_esc = mysqli_real_escape_string($con, $res);
mysqli_query($con, "INSERT INTO cron_log (script, result) VALUES ('cron-weekly-report', '$res_esc')");

if ($is_cli) {
    echo "[" . date('Y-m-d H:i:s') . "] cron-weekly-report: $res\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['ok' => $sent, 'result' => $res]);
}
