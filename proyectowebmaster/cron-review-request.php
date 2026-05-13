<?php
/**
 * cron-review-request.php
 * Envía emails pidiendo valoración a clientes cuya orden pasó a "Delivered"
 * hace exactamente N días (configurable: review_request_days, default 3).
 *
 * Llamar via Task Scheduler de Windows o manualmente desde admin/dashboard.
 * Token de seguridad en setting: cron_token (si está vacío, cualquiera puede llamarlo).
 */
include('includes/config.php');
include_once('includes/mailer.php');

// Verificar token
$cfg_q = mysqli_query($con, "SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('cron_token','review_request_days','site_name')");
$cfg = [];
while ($r = mysqli_fetch_assoc($cfg_q)) $cfg[$r['setting_key']] = $r['setting_value'];

$token = trim($cfg['cron_token'] ?? '');
if ($token !== '' && ($_GET['token'] ?? '') !== $token) {
    http_response_code(403); die('Forbidden');
}

$days = max(1, intval($cfg['review_request_days'] ?? 3));
$site_name = $cfg['site_name'] ?? 'Tienda';

// Auto-create review_requests_sent table to avoid duplicates
mysqli_query($con, "CREATE TABLE IF NOT EXISTS review_requests_sent (
    order_id INT PRIMARY KEY,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Find orders delivered N days ago that haven't gotten a review request yet
$target_date = date('Y-m-d', strtotime("-{$days} days"));

$q = mysqli_query($con,
    "SELECT DISTINCT o.id as oid, o.userId, u.email, u.name,
            GROUP_CONCAT(p.productName SEPARATOR ', ') as products
     FROM orders o
     JOIN users u ON u.id=o.userId
     JOIN products p ON p.id=o.productId
     LEFT JOIN review_requests_sent rrs ON rrs.order_id=o.id
     WHERE o.orderStatus='Delivered'
       AND DATE(o.orderDate) = '$target_date'
       AND o.paymentMethod IS NOT NULL
       AND rrs.order_id IS NULL
     GROUP BY o.id, o.userId, u.email, u.name"
);

$sent = 0; $skipped = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $oid      = intval($row['oid']);
    $to_email = $row['email'];
    $to_name  = $row['name'] ?? 'Cliente';
    $products = $row['products'];

    $review_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                  . '://' . ($_SERVER['HTTP_HOST']??'localhost')
                  . '/proyectowebmaster/product-details.php';

    $body = "
<html><body style='font-family:Arial,sans-serif;max-width:580px;margin:auto;padding:20px'>
<h2 style='color:#e8233a'>¿Qué te pareció tu compra?</h2>
<p>Hola <strong>" . htmlspecialchars($to_name) . "</strong>,</p>
<p>Recibiste tu pedido hace $days días. ¡Nos encantaría saber tu opinión!</p>
<p style='background:#f9f9f9;border:1px solid #eee;padding:12px;border-radius:5px'>
<strong>Productos comprados:</strong><br>" . htmlspecialchars($products) . "
</p>
<p>Dejar una reseña solo toma 1 minuto y ayuda a otros compradores:</p>
<a href='" . $review_url . "' style='display:inline-block;padding:12px 24px;background:#e8233a;color:#fff;border-radius:5px;text-decoration:none;font-weight:bold'>
    ⭐ Dejar mi reseña
</a>
<p style='margin-top:20px;font-size:12px;color:#aaa'>Si ya dejaste una reseña, ignora este mensaje. Gracias por comprar en $site_name.</p>
</body></html>";

    $ok = send_email_raw($to_email, $to_name, "[$site_name] ¿Cómo fue tu experiencia?", $body);
    if ($ok) {
        mysqli_query($con, "INSERT IGNORE INTO review_requests_sent (order_id) VALUES ($oid)");
        $sent++;
    } else {
        $skipped++;
    }
}

// Response
if (php_sapi_name() === 'cli') {
    echo "Enviados: $sent | Fallidos: $skipped\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['sent'=>$sent,'skipped'=>$skipped,'days'=>$days,'target_date'=>$target_date]);
}
