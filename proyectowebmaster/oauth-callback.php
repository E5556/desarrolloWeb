<?php
session_start();
error_reporting(0);
include('includes/config.php');

function oauth_error($msg) {
    $_SESSION['errmsg'] = $msg;
    header('location: login.php');
    exit();
}

// ── 1. Verificar state CSRF ───────────────────────────────────────────────────
if (empty($_GET['state']) || empty($_SESSION['oauth_state'])
    || !hash_equals($_SESSION['oauth_state'], $_GET['state'])) {
    oauth_error('Error de seguridad en la autenticación. Inténtalo de nuevo.');
}
unset($_SESSION['oauth_state']);

if (empty($_GET['code'])) {
    oauth_error('Google no devolvió un código de autorización.');
}

// ── 2. Leer credenciales Google desde settings ────────────────────────────────
$r = mysqli_query($con, "SELECT setting_key, setting_value FROM settings
                          WHERE setting_key IN ('google_client_id','google_client_secret')");
$gcfg = [];
while ($row = mysqli_fetch_assoc($r)) $gcfg[$row['setting_key']] = $row['setting_value'];

if (empty($gcfg['google_client_id']) || empty($gcfg['google_client_secret'])) {
    oauth_error('Configuración de Google OAuth incompleta.');
}

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/proyectowebmaster/oauth-callback.php';

// ── 3. Intercambiar code por access_token ────────────────────────────────────
$token_data = http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => $gcfg['google_client_id'],
    'client_secret' => $gcfg['google_client_secret'],
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code',
]);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $token_data,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$token_response = curl_exec($ch);
$curl_err       = curl_error($ch);
curl_close($ch);

if ($curl_err) oauth_error('No se pudo conectar con Google. Inténtalo más tarde.');

$token_json = json_decode($token_response, true);
if (empty($token_json['access_token'])) {
    oauth_error('Google no devolvió un token válido. Inténtalo de nuevo.');
}
$access_token = $token_json['access_token'];

// ── 4. Obtener datos del usuario ─────────────────────────────────────────────
$ch2 = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$user_response = curl_exec($ch2);
curl_close($ch2);

$guser = json_decode($user_response, true);
if (empty($guser['email'])) {
    oauth_error('No se pudo obtener el correo de tu cuenta Google.');
}

$g_id    = $guser['sub']   ?? '';
$g_email = $guser['email'] ?? '';
$g_name  = $guser['name']  ?? explode('@', $g_email)[0];

// ── 5. Buscar o crear usuario en la BD ───────────────────────────────────────
// Primero por oauth_id
$stmt = mysqli_prepare($con, "SELECT * FROM users WHERE oauth_provider='google' AND oauth_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $g_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    // Buscar por email (usuario registrado previamente con contraseña)
    $stmt2 = mysqli_prepare($con, "SELECT * FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt2, 's', $g_email);
    mysqli_stmt_execute($stmt2);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    mysqli_stmt_close($stmt2);

    if ($user) {
        // Vincular cuenta Google al usuario existente
        $stmt3 = mysqli_prepare($con,
            "UPDATE users SET oauth_provider='google', oauth_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt3, 'si', $g_id, $user['id']);
        mysqli_stmt_execute($stmt3);
        mysqli_stmt_close($stmt3);
    } else {
        // Crear nuevo usuario
        $stmt4 = mysqli_prepare($con,
            "INSERT INTO users (name, email, oauth_provider, oauth_id, password, contactno)
             VALUES (?, ?, 'google', ?, '', '')");
        mysqli_stmt_bind_param($stmt4, 'sss', $g_name, $g_email, $g_id);
        mysqli_stmt_execute($stmt4);
        $new_id = mysqli_insert_id($con);
        mysqli_stmt_close($stmt4);

        // Recargar usuario recién creado
        $stmt5 = mysqli_prepare($con, "SELECT * FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt5, 'i', $new_id);
        mysqli_stmt_execute($stmt5);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt5));
        mysqli_stmt_close($stmt5);
    }
}

if (!$user) {
    oauth_error('No se pudo crear la cuenta. Inténtalo de nuevo.');
}

// ── 6. Iniciar sesión ────────────────────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['login']    = $user['email'];
$_SESSION['id']       = $user['id'];
$_SESSION['username'] = $user['name'];

// Registrar en userlog
$uip    = $_SERVER['REMOTE_ADDR'];
$status = 1;
$stmt6  = mysqli_prepare($con, "INSERT INTO userlog(userEmail,userip,status) VALUES(?,?,?)");
mysqli_stmt_bind_param($stmt6, 'ssi', $user['email'], $uip, $status);
mysqli_stmt_execute($stmt6);
mysqli_stmt_close($stmt6);

header('location: my-cart.php');
exit();
