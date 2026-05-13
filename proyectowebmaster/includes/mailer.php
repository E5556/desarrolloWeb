<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

/**
 * send_email_otp — Envía un código OTP de 6 dígitos por correo electrónico usando SMTP.
 *
 * @param  mysqli $con      Conexión activa a la BD (para leer credenciales y site_name)
 * @param  string $to_email Dirección de correo del destinatario
 * @param  string $otp      Código de 6 dígitos en texto plano
 * @return array  ['success'=>bool, 'dev_mode'=>bool, 'otp'=>string|null, 'error'=>string]
 */
function send_email_otp($con, $to_email, $otp): array
{
    // Leer configuración SMTP y nombre del sitio
    $cfg = [];
    $r = mysqli_query($con,
        "SELECT setting_key, setting_value FROM settings
         WHERE setting_key IN ('smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','site_name')"
    );
    while ($row = mysqli_fetch_assoc($r)) {
        $cfg[$row['setting_key']] = $row['setting_value'];
    }

    $smtp_host      = trim($cfg['smtp_host']      ?? '');
    $smtp_port      = (int)($cfg['smtp_port']     ?? 587);
    $smtp_user      = trim($cfg['smtp_user']      ?? '');
    $smtp_pass      = trim($cfg['smtp_pass']      ?? '');
    $smtp_from      = trim($cfg['smtp_from']      ?? $smtp_user);
    $smtp_from_name = trim($cfg['smtp_from_name'] ?? 'Tienda');
    $site_name      = trim($cfg['site_name']      ?? 'Tienda');

    // Modo desarrollo si no hay configuración
    if ($smtp_host === '') {
        return ['success' => true, 'dev_mode' => true, 'otp' => $otp, 'error' => ''];
    }

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port ?: 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        // Remitente y destinatario
        $mail->setFrom($smtp_from, $smtp_from_name);
        $mail->addAddress($to_email);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = "Tu código de verificación — {$site_name}";

        $mail->Body = '
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
        <!-- Cabecera -->
        <tr><td style="background:#2d7a2d;padding:20px 30px;text-align:center;">
          <h2 style="color:#ffffff;margin:0;font-size:20px;">' . htmlspecialchars($site_name) . '</h2>
        </td></tr>
        <!-- Cuerpo -->
        <tr><td style="padding:30px;">
          <p style="margin:0 0 16px;font-size:15px;color:#333;">
            Recibiste este correo porque solicitaste restablecer tu contraseña.
          </p>
          <p style="margin:0 0 8px;font-size:14px;color:#555;">Tu código de verificación es:</p>
          <div style="text-align:center;margin:20px 0;">
            <span style="display:inline-block;background:#f0f7f0;border:2px solid #2d7a2d;
                         border-radius:8px;padding:14px 32px;font-size:36px;font-weight:700;
                         letter-spacing:10px;color:#1a5c1a;font-family:monospace;">
              ' . htmlspecialchars($otp) . '
            </span>
          </div>
          <p style="margin:0 0 8px;font-size:13px;color:#888;text-align:center;">
            Válido por <strong>10 minutos</strong>.
          </p>
          <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
          <p style="margin:0;font-size:12px;color:#aaa;text-align:center;">
            Si no solicitaste este código, ignora este mensaje. Tu contraseña no cambiará.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

        $mail->AltBody =
            "Tu código de verificación para {$site_name} es: {$otp}\n\n" .
            "Válido por 10 minutos.\n" .
            "Si no solicitaste este código, ignora este mensaje.";

        $mail->send();
        return ['success' => true, 'dev_mode' => false, 'otp' => null, 'error' => ''];

    } catch (PHPMailerException $e) {
        return ['success' => false, 'dev_mode' => false, 'otp' => null,
                'error'   => 'Error al enviar correo: ' . $mail->ErrorInfo];
    }
}


/**
 * send_order_confirmation — Envía email de confirmación de orden al comprador.
 */
function send_order_confirmation($con, $to_email, $to_name, $order_items, $total, $paymethod): array
{
    $cfg = [];
    $r = mysqli_query($con,
        "SELECT setting_key, setting_value FROM settings
         WHERE setting_key IN ('smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','site_name')"
    );
    while ($row = mysqli_fetch_assoc($r)) $cfg[$row['setting_key']] = $row['setting_value'];

    $smtp_host      = trim($cfg['smtp_host']      ?? '');
    $smtp_port      = (int)($cfg['smtp_port']     ?? 587);
    $smtp_user      = trim($cfg['smtp_user']      ?? '');
    $smtp_pass      = trim($cfg['smtp_pass']      ?? '');
    $smtp_from      = trim($cfg['smtp_from']      ?? $smtp_user);
    $smtp_from_name = trim($cfg['smtp_from_name'] ?? 'Tienda');
    $site_name      = trim($cfg['site_name']      ?? 'Tienda');

    if ($smtp_host === '') {
        return ['success' => true, 'dev_mode' => true, 'error' => ''];
    }

    // Build items HTML
    $items_html = '';
    foreach ($order_items as $item) {
        $items_html .= '<tr>
            <td style="padding:8px;border-bottom:1px solid #eee">' . htmlspecialchars($item['name']) . '</td>
            <td style="padding:8px;border-bottom:1px solid #eee;text-align:center">' . intval($item['qty']) . '</td>
            <td style="padding:8px;border-bottom:1px solid #eee;text-align:right">$' . number_format($item['price'],0,'.',',') . '</td>
        </tr>';
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port ?: 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;
        $mail->setFrom($smtp_from, $smtp_from_name);
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);
        $mail->Subject = "Confirmacion de tu orden — {$site_name}";
        $mail->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
<tr><td align="center">
<table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
<tr><td style="background:#e8233a;padding:20px 30px;text-align:center;">
  <h2 style="color:#fff;margin:0;font-size:22px;">' . htmlspecialchars($site_name) . '</h2>
</td></tr>
<tr><td style="padding:30px;">
  <h3 style="color:#333;margin:0 0 8px">Hola, ' . htmlspecialchars($to_name) . '!</h3>
  <p style="color:#555;margin:0 0 20px">Tu pedido ha sido confirmado. A continuacion el resumen:</p>
  <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:20px;">
    <thead><tr style="background:#f9f9f9;">
      <th style="padding:10px;text-align:left;font-size:13px;color:#555">Producto</th>
      <th style="padding:10px;text-align:center;font-size:13px;color:#555">Cant.</th>
      <th style="padding:10px;text-align:right;font-size:13px;color:#555">Precio</th>
    </tr></thead>
    <tbody>' . $items_html . '</tbody>
    <tfoot><tr>
      <td colspan="2" style="padding:10px;font-weight:700;color:#333">Total</td>
      <td style="padding:10px;font-weight:700;color:#e8233a;text-align:right">$' . number_format($total,0,'.',',') . '</td>
    </tr></tfoot>
  </table>
  <p style="margin:0 0 8px;font-size:13px;color:#555"><strong>Metodo de pago:</strong> ' . htmlspecialchars($paymethod) . '</p>
  <hr style="border:none;border-top:1px solid #eee;margin:20px 0">
  <p style="margin:0;font-size:12px;color:#aaa;text-align:center">Gracias por tu compra en ' . htmlspecialchars($site_name) . '. Si tienes alguna pregunta, contactanos.</p>
</td></tr>
</table>
</td></tr>
</table>
</body></html>';
        $mail->AltBody = "Hola {$to_name}, tu pedido fue confirmado. Total: ${$total}. Metodo: {$paymethod}. Gracias por comprar en {$site_name}.";
        $mail->send();
        return ['success' => true, 'dev_mode' => false, 'error' => ''];
    } catch (PHPMailerException $e) {
        return ['success' => false, 'dev_mode' => false, 'error' => $mail->ErrorInfo];
    }
}


/**
 * send_email_raw — Envía un email HTML genérico (para notificaciones admin/devoluciones).
 */
function send_email_raw($to_email, $to_name, $subject, $html_body): bool
{
    global $con;
    $cfg = [];
    $r = mysqli_query($con,
        "SELECT setting_key, setting_value FROM settings
         WHERE setting_key IN ('smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','site_name')"
    );
    while ($row = mysqli_fetch_assoc($r)) $cfg[$row['setting_key']] = $row['setting_value'];

    $smtp_host      = trim($cfg['smtp_host']      ?? '');
    $smtp_port      = (int)($cfg['smtp_port']     ?? 587);
    $smtp_user      = trim($cfg['smtp_user']      ?? '');
    $smtp_pass      = trim($cfg['smtp_pass']      ?? '');
    $smtp_from      = trim($cfg['smtp_from']      ?? $smtp_user);
    $smtp_from_name = trim($cfg['smtp_from_name'] ?? 'Tienda');
    $site_name      = trim($cfg['site_name']      ?? 'Tienda');

    if ($smtp_host === '') return true;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port ?: 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;
        $mail->setFrom($smtp_from, $smtp_from_name);
        $mail->addAddress($to_email, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags($html_body);
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        return false;
    }
}


/**
 * notify_admin — Envía una alerta por email al admin del sitio.
 * $type: 'new_order' | 'low_stock' | 'new_return' | 'new_contact'
 * $data: array con los datos relevantes al tipo de alerta
 */
function notify_admin(string $type, array $data = []): bool
{
    global $con;
    $cfg = [];
    $r = mysqli_query($con,
        "SELECT setting_key, setting_value FROM settings
         WHERE setting_key IN ('smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','site_name','admin_email')"
    );
    while ($row = mysqli_fetch_assoc($r)) $cfg[$row['setting_key']] = $row['setting_value'];

    $smtp_host = trim($cfg['smtp_host'] ?? '');
    if ($smtp_host === '') return true; // dev mode

    $admin_email    = trim($cfg['admin_email'] ?? $cfg['smtp_from'] ?? '');
    $smtp_from      = trim($cfg['smtp_from'] ?? '');
    $smtp_from_name = trim($cfg['smtp_from_name'] ?? 'Sistema');
    $site_name      = trim($cfg['site_name'] ?? 'Tienda');

    if ($admin_email === '') return false;

    switch ($type) {
        case 'new_order':
            $subject = "[$site_name] Nueva orden #{$data['order_id']}";
            $body = "<h2 style='color:#337ab7'>Nueva orden recibida</h2>
                <p><strong>Orden #:</strong> {$data['order_id']}<br>
                <strong>Cliente:</strong> " . htmlspecialchars($data['customer'] ?? '') . "<br>
                <strong>Total:</strong> \${$data['total']}<br>
                <strong>Método:</strong> " . htmlspecialchars($data['method'] ?? '') . "</p>
                <a href='admin/pending-orders.php' style='display:inline-block;padding:10px 20px;background:#337ab7;color:#fff;border-radius:4px;text-decoration:none'>Ver órdenes pendientes</a>";
            break;
        case 'low_stock':
            $subject = "[$site_name] Alerta: stock bajo en «{$data['product']}»";
            $body = "<h2 style='color:#e8233a'>Stock bajo</h2>
                <p>El producto <strong>" . htmlspecialchars($data['product'] ?? '') . "</strong> tiene solo <strong>{$data['qty']}</strong> unidades disponibles.</p>
                <a href='admin/manage-products.php' style='display:inline-block;padding:10px 20px;background:#e8233a;color:#fff;border-radius:4px;text-decoration:none'>Administrar productos</a>";
            break;
        case 'new_return':
            $subject = "[$site_name] Nueva solicitud de devolución #" . ($data['return_id'] ?? '');
            $body = "<h2 style='color:#f39c12'>Nueva solicitud de devolución</h2>
                <p><strong>Cliente:</strong> " . htmlspecialchars($data['customer'] ?? '') . "<br>
                <strong>Motivo:</strong> " . htmlspecialchars($data['reason'] ?? '') . "</p>
                <a href='admin/returns.php' style='display:inline-block;padding:10px 20px;background:#f39c12;color:#fff;border-radius:4px;text-decoration:none'>Ver devoluciones</a>";
            break;
        case 'new_contact':
            $subject = "[$site_name] Nuevo mensaje de contacto de " . htmlspecialchars($data['name'] ?? '');
            $body = "<h2 style='color:#5bc0de'>Nuevo mensaje de contacto</h2>
                <p><strong>De:</strong> " . htmlspecialchars($data['name'] ?? '') . " &lt;" . htmlspecialchars($data['email'] ?? '') . "&gt;<br>
                <strong>Asunto:</strong> " . htmlspecialchars($data['subject'] ?? '') . "</p>
                <blockquote style='border-left:3px solid #ccc;padding-left:12px;color:#555'>" . nl2br(htmlspecialchars($data['message'] ?? '')) . "</blockquote>
                <a href='admin/contact-messages.php' style='display:inline-block;padding:10px 20px;background:#5bc0de;color:#fff;border-radius:4px;text-decoration:none'>Ver mensajes</a>";
            break;
        default:
            return false;
    }

    return send_email_raw($admin_email, 'Admin', $subject,
        "<html><body style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px'>$body
        <hr style='margin-top:30px'><small style='color:#aaa'>Alerta automática de $site_name</small></body></html>");
}
