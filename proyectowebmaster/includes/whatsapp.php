<?php
/**
 * send_whatsapp_otp — Envía un código OTP de 6 dígitos por WhatsApp usando UltraMsg.
 *
 * @param  mysqli $con   Conexión activa a la BD (para leer credenciales)
 * @param  string $phone Número del destinatario (puede ser 10 dígitos Colombia o internacional)
 * @param  string $otp   Código de 6 dígitos en texto plano
 * @return array  ['success'=>bool, 'dev_mode'=>bool, 'otp'=>string|null, 'error'=>string]
 */
function send_whatsapp_otp($con, $phone, $otp): array
{
    // Leer credenciales desde la tabla settings
    $instance = '';
    $token    = '';
    $r = mysqli_query($con, "SELECT setting_key, setting_value FROM settings
                              WHERE setting_key IN ('ultramsg_instance','ultramsg_token')");
    while ($row = mysqli_fetch_assoc($r)) {
        if ($row['setting_key'] === 'ultramsg_instance') $instance = trim($row['setting_value']);
        if ($row['setting_key'] === 'ultramsg_token')    $token    = trim($row['setting_value']);
    }

    // Modo desarrollo si no hay credenciales configuradas
    if ($instance === '' || $token === '') {
        return ['success' => true, 'dev_mode' => true, 'otp' => $otp, 'error' => ''];
    }

    // Formatear teléfono: solo dígitos
    $phone_clean = preg_replace('/\D/', '', $phone);

    // Si es número colombiano de 10 dígitos → agregar prefijo 57
    if (strlen($phone_clean) === 10) {
        $phone_clean = '57' . $phone_clean;
    }

    $mensaje = "Tu código de verificación para restablecer tu contraseña es: *{$otp}*\n\nVigente por 10 minutos. No lo compartas con nadie.";

    $url  = "https://api.ultramsg.com/{$instance}/messages/chat";
    $data = http_build_query([
        'token'  => $token,
        'to'     => $phone_clean,
        'body'   => $mensaje,
        'priority' => 10,
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'dev_mode' => false, 'otp' => null,
                'error'   => 'Error de conexión: ' . $curl_err];
    }

    $json = json_decode($response, true);
    // UltraMsg devuelve {"sent":"true"} en éxito
    if (isset($json['sent']) && $json['sent'] === 'true') {
        return ['success' => true, 'dev_mode' => false, 'otp' => null, 'error' => ''];
    }

    $err_msg = $json['message'] ?? $response;
    return ['success' => false, 'dev_mode' => false, 'otp' => null,
            'error'   => 'UltraMsg: ' . $err_msg];
}
