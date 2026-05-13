<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Leer configuración Google OAuth
$r = mysqli_query($con, "SELECT setting_key, setting_value FROM settings
                          WHERE setting_key IN ('google_oauth_enabled','google_client_id')");
$gcfg = [];
while ($row = mysqli_fetch_assoc($r)) $gcfg[$row['setting_key']] = $row['setting_value'];

if (($gcfg['google_oauth_enabled'] ?? '0') !== '1' || empty($gcfg['google_client_id'])) {
    $_SESSION['errmsg'] = 'El inicio de sesión con Google no está configurado.';
    header('location: login.php');
    exit();
}

// Generar state CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/oauth-callback.php';

$params = http_build_query([
    'client_id'     => $gcfg['google_client_id'],
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

header('location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit();
