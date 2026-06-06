<?php
$_host = $_SERVER['HTTP_HOST'];
$_host_ip = explode(':', $_host)[0]; // quitar puerto si hay
if ($_host === 'localhost' || $_host_ip === '127.0.0.1' || substr($_host_ip, 0, 8) === '192.168.') {
    define('DB_SERVER', 'localhost');
    define('DB_USER',   'root');
    define('DB_PASS',   '');
    define('DB_NAME',   'shopping');
} else {
    define('DB_SERVER', 'sql305.infinityfree.com');
    define('DB_USER',   'if0_41615564');
    define('DB_PASS',   '7QwT5ffUkd9p');
    define('DB_NAME',   'if0_41615564_shopping');
}
$con = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);
// Check connection
if (mysqli_connect_errno())
{
 echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// ── Modo mantenimiento ───────────────────────────────────────────────────────
$_maint_r = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='maintenance_mode' LIMIT 1");
$_maint_on = $_maint_r && (($_mv = mysqli_fetch_assoc($_maint_r)) ? $_mv['setting_value'] === '1' : false);
if ($_maint_on) {
    $__script  = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
    $__is_admin = !empty($_SESSION['alogin']);
    $__is_maint_page = ($__script === 'mantenimiento.php');
    $__is_admin_area = (strpos($_SERVER['SCRIPT_FILENAME'] ?? '', DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR) !== false);
    if (!$__is_admin && !$__is_maint_page && !$__is_admin_area) {
        header('Location: /mantenimiento.php');
        exit();
    }
}

// Cargar nombre del sitio y favicon para usar en <title> y <link> de todas las páginas
$_sr = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name','site_favicon')");
$_site_cfg = [];
if ($_sr) { while ($_row = mysqli_fetch_assoc($_sr)) $_site_cfg[$_row['setting_key']] = $_row['setting_value']; }
$_SITE_NAME    = htmlspecialchars($_site_cfg['site_name']    ?? 'Mi Tienda',    ENT_QUOTES, 'UTF-8');
$_SITE_FAVICON = htmlspecialchars($_site_cfg['site_favicon'] ?? 'assets/images/favicon.ico', ENT_QUOTES, 'UTF-8');
$_sr2 = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='currency_symbol' LIMIT 1");
$_CURRENCY = $_sr2 ? (mysqli_fetch_assoc($_sr2)['setting_value'] ?? '$') : '$';
unset($_sr, $_site_cfg, $_row);
?>