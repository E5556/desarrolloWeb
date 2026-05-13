<?php
/* Cron de cumpleaños (Q2)
   Corre diariamente. Envía cupón de descuento a usuarios que cumplen años hoy.
   URL: /proyectowebmaster/cron-birthday.php?token=TU_TOKEN
   CLI: php cron-birthday.php
*/
session_start();
include('includes/config.php');
include('includes/mailer.php');

// Protección por token
$cron_token_q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='cron_token' LIMIT 1");
$cron_token = $cron_token_q ? (mysqli_fetch_assoc($cron_token_q)['setting_value'] ?? '') : '';
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli && $cron_token !== '' && ($_GET['token'] ?? '') !== $cron_token) {
    http_response_code(403); die('Forbidden');
}

// Auto-crear tabla de control (evitar duplicados)
mysqli_query($con, "CREATE TABLE IF NOT EXISTS birthday_coupons_sent (
    user_id INT NOT NULL,
    year SMALLINT NOT NULL,
    PRIMARY KEY (user_id, year)
) ENGINE=InnoDB");

// Auto-crear columna birthday si no existe
@mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS birthday DATE DEFAULT NULL");

$today_md = date('m-d'); // mes-día de hoy
$this_year = intval(date('Y'));

// Buscar usuarios que cumplen hoy y NO han recibido cupón este año
$bday_q = mysqli_query($con, "
    SELECT u.id, u.name, u.email
    FROM users u
    LEFT JOIN birthday_coupons_sent bcs ON bcs.user_id=u.id AND bcs.year=$this_year
    WHERE DATE_FORMAT(u.birthday, '%m-%d') = '$today_md'
      AND u.birthday IS NOT NULL
      AND bcs.user_id IS NULL
");

$sent = 0;
while ($bday_q && $u = mysqli_fetch_assoc($bday_q)) {
    $uid = intval($u['id']);

    // Crear cupón único de 20% para este usuario
    $code = 'BDAY' . strtoupper(substr(md5($uid . $this_year . rand()), 0, 6));
    $expires = date('Y-m-d', strtotime('+7 days'));
    mysqli_query($con, "INSERT IGNORE INTO coupons (code, discount_type, discount_value, min_order, max_uses, active, expires_at)
        VALUES ('$code', 'percent', 20, 0, 1, 1, '$expires')");

    // Email de cumpleaños
    $body = '<div style="font-family:sans-serif;max-width:520px;margin:auto;padding:20px">
        <h2 style="color:#e8233a">🎂 ¡Feliz cumpleaños, ' . htmlspecialchars($u['name']) . '!</h2>
        <p>En tu día especial queremos regalarte un <strong>20% de descuento</strong> en tu próxima compra.</p>
        <div style="text-align:center;margin:24px 0">
          <span style="font-size:28px;font-weight:700;letter-spacing:4px;color:#337ab7;border:2px dashed #337ab7;padding:10px 20px;border-radius:8px">' . $code . '</span>
        </div>
        <p style="color:#888;font-size:13px">Válido por 7 días. Un solo uso.</p>
    </div>';

    send_email_raw($u['email'], $u['name'], '🎂 ¡Feliz cumpleaños! Tu cupón de regalo', $body);

    // Marcar como enviado
    mysqli_query($con, "INSERT IGNORE INTO birthday_coupons_sent (user_id, year) VALUES ($uid, $this_year)");
    $sent++;
    if ($is_cli) echo "Enviado a {$u['email']} — cupón $code\n";
}

if ($is_cli) echo "Total enviados: $sent\n";
else header('Content-Type: application/json'), print json_encode(['sent' => $sent, 'date' => date('Y-m-d')]);
