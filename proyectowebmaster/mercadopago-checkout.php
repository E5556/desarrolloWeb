<?php
/**
 * MercadoPago Checkout Pro — Crea la preferencia y redirige al usuario a MP
 * Sin composer: usa cURL directamente contra la API REST de MercadoPago
 */
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/security.php');

if (empty($_SESSION['login'])) { header('location:login.php'); exit(); }

// ── Leer Access Token desde settings ────────────────────────────────────────
$_mp_r  = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('mp_access_token','mp_sandbox')");
$_mp_cfg = [];
while ($_mr = mysqli_fetch_assoc($_mp_r)) $_mp_cfg[$_mr['setting_key']] = $_mr['setting_value'];

$_mp_token = trim($_mp_cfg['mp_access_token'] ?? '');
if (empty($_mp_token)) {
    die('<h3 style="font-family:sans-serif;color:#c00;text-align:center;margin-top:80px">MercadoPago no está configurado.<br><a href="admin/settings.php">Configurar</a></h3>');
}

$_uid = intval($_SESSION['id']);

// ── Obtener los ítems del carrito pendiente ──────────────────────────────────
$_items_q = mysqli_query($con,
    "SELECT p.productName, p.productPrice, p.shippingCharge, o.quantity, o.id as oid
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.userId=$_uid AND o.paymentMethod IS NULL");

$_mp_items = [];
$_total    = 0;
while ($_ir = mysqli_fetch_assoc($_items_q)) {
    $unit_price = floatval($_ir['productPrice']) + floatval($_ir['shippingCharge']);
    $_mp_items[] = [
        'title'       => $_ir['productName'],
        'quantity'    => intval($_ir['quantity']),
        'unit_price'  => $unit_price,
        'currency_id' => 'COP',
    ];
    $_total += $unit_price * intval($_ir['quantity']);
}

if (empty($_mp_items)) {
    header('location:my-cart.php'); exit();
}

// ── Construir URLs base ──────────────────────────────────────────────────────
$_base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$_base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// ── Preferencia de pago ──────────────────────────────────────────────────────
$_preference = [
    'items' => $_mp_items,
    'payer' => [
        'email' => $_SESSION['email'] ?? '',
    ],
    'back_urls' => [
        'success' => "$_base$_base_path/mercadopago-result.php?status=success",
        'failure' => "$_base$_base_path/mercadopago-result.php?status=failure",
        'pending' => "$_base$_base_path/mercadopago-result.php?status=pending",
    ],
    'auto_return'         => 'approved',
    'notification_url'    => "$_base$_base_path/mercadopago-webhook.php",
    'external_reference'  => 'user_' . $_uid . '_' . time(),
    'statement_descriptor'=> 'Personal Shopper',
];

// ── Llamada a la API de MercadoPago ─────────────────────────────────────────
$_ch = curl_init('https://api.mercadopago.com/checkout/preferences');
curl_setopt_array($_ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($_preference),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_mp_token,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$_mp_response = curl_exec($_ch);
$_http_code   = curl_getinfo($_ch, CURLINFO_HTTP_CODE);
curl_close($_ch);

$_mp_data = json_decode($_mp_response, true);

if ($_http_code !== 201 || empty($_mp_data['id'])) {
    // Guardar error para debug (solo en desarrollo)
    error_log("MP Error $_http_code: $_mp_response");
    die('<h3 style="font-family:sans-serif;color:#c00;text-align:center;margin-top:80px">Error al conectar con MercadoPago. Intenta más tarde.</h3>');
}

// ── Guardar preference_id en sesión para verificar en el webhook ─────────────
$_SESSION['mp_preference_id'] = $_mp_data['id'];
$_SESSION['mp_external_ref']  = $_preference['external_reference'];

// ── Redirigir al usuario a MercadoPago ──────────────────────────────────────
// init_point = producción | sandbox_init_point = sandbox
$_redirect_url = !empty($_mp_cfg['mp_sandbox']) && $_mp_cfg['mp_sandbox'] === '1'
    ? ($_mp_data['sandbox_init_point'] ?? $_mp_data['init_point'])
    : $_mp_data['init_point'];

header('Location: ' . $_redirect_url);
exit();
