<?php
/**
 * MercadoPago Webhook — Recibe notificaciones IPN/webhook de MP
 * MP llama a esta URL automáticamente cuando cambia el estado de un pago
 * NO requiere sesión activa — es una llamada server-to-server
 */
error_reporting(0);
include('includes/config.php');
include_once('includes/points.php');

// Solo aceptar POST de MercadoPago
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit();
}

$_raw = file_get_contents('php://input');
$_data = json_decode($_raw, true);

// Tipo de notificación
$_type   = $_data['type']           ?? ($_GET['topic'] ?? '');
$_mp_id  = $_data['data']['id']     ?? ($_GET['id']    ?? 0);

if (empty($_mp_id)) { http_response_code(200); exit(); }

// Leer Access Token
$_mp_r  = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='mp_access_token' LIMIT 1");
$_mp_token = $_mp_r ? (mysqli_fetch_assoc($_mp_r)['setting_value'] ?? '') : '';

if (empty($_mp_token)) { http_response_code(200); exit(); }

// Consultar el pago en la API de MP para verificar su estado
$_ch = curl_init("https://api.mercadopago.com/v1/payments/" . intval($_mp_id));
curl_setopt_array($_ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $_mp_token],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$_mp_resp = curl_exec($_ch);
curl_close($_ch);

$_payment = json_decode($_mp_resp, true);

if (empty($_payment['status'])) { http_response_code(200); exit(); }

$_pay_status = $_payment['status'];        // approved, pending, rejected, etc.
$_pay_id     = intval($_payment['id']);
$_ext_ref    = $_payment['external_reference'] ?? ''; // formato: user_123_timestamp
$_amount     = floatval($_payment['transaction_amount'] ?? 0);

// Extraer userId del external_reference
preg_match('/^user_(\d+)_/', $_ext_ref, $_m);
$_uid = intval($_m[1] ?? 0);

if ($_uid <= 0) { http_response_code(200); exit(); }

// Solo procesar pagos aprobados
if ($_pay_status === 'approved') {
    // Asegurar columna existe
    mysqli_query($con, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS mp_payment_id BIGINT DEFAULT NULL");

    // Verificar si ya fue procesado
    $_check = mysqli_query($con, "SELECT COUNT(*) n FROM orders WHERE mp_payment_id=$_pay_id");
    $_already = intval(mysqli_fetch_assoc($_check)['n'] ?? 0);

    if ($_already === 0) {
        // Marcar órdenes como pagadas
        $stmt = mysqli_prepare($con,
            "UPDATE orders SET paymentMethod='MercadoPago', mp_payment_id=? WHERE userId=? AND paymentMethod IS NULL");
        mysqli_stmt_bind_param($stmt, 'ii', $_pay_id, $_uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Descontar stock
        $_sq = mysqli_query($con, "SELECT productId, quantity FROM orders WHERE userId=$_uid AND mp_payment_id=$_pay_id");
        while ($_sr = mysqli_fetch_assoc($_sq)) {
            $_spid = intval($_sr['productId']); $_sqty = intval($_sr['quantity']);
            mysqli_query($con, "UPDATE products SET stock_qty = GREATEST(0, stock_qty - $_sqty) WHERE id=$_spid AND stock_qty IS NOT NULL");
            mysqli_query($con, "UPDATE products SET productAvailability='Out of Stock' WHERE id=$_spid AND stock_qty IS NOT NULL AND stock_qty=0");
        }

        // Acumular puntos
        if ($_amount > 0) {
            ps_points_add($con, $_uid, max(1, intval($_amount/1000)), 'Compra MP webhook #' . $_pay_id);
        }

        // Email de confirmación
        $_uq = mysqli_query($con, "SELECT name, email FROM users WHERE id=$_uid");
        if ($_uq && $_urow = mysqli_fetch_assoc($_uq)) {
            $_oq = mysqli_query($con, "SELECT o.quantity, p.productName, p.productPrice FROM orders o JOIN products p ON p.id=o.productId WHERE o.userId=$_uid AND o.mp_payment_id=$_pay_id");
            $_items = []; $_itot = 0;
            while ($_oq && $_or = mysqli_fetch_assoc($_oq)) {
                $_items[] = ['name'=>$_or['productName'],'qty'=>$_or['quantity'],'price'=>$_or['productPrice']];
                $_itot += $_or['quantity'] * $_or['productPrice'];
            }
            if (!empty($_items)) {
                include_once('includes/mailer.php');
                send_order_confirmation($con, $_urow['email'], $_urow['name'], $_items, $_itot, 'MercadoPago');
            }
        }
    }
}

// MercadoPago espera HTTP 200 para saber que recibimos la notificación
http_response_code(200);
echo 'OK';
