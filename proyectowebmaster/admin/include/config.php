<?php
$_host = $_SERVER['HTTP_HOST'];
$_host_ip = explode(':', $_host)[0];
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

// Nombre del sitio para usar en el panel admin
$_adm_r = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='site_name' LIMIT 1");
$_adm_row = $_adm_r ? mysqli_fetch_assoc($_adm_r) : null;
$_ADMIN_SITE_NAME = htmlspecialchars($_adm_row['setting_value'] ?? 'Mi Tienda', ENT_QUOTES, 'UTF-8');
unset($_adm_r, $_adm_row);

// AA3: Permission helper — returns current admin's permission row (cached in $_SESSION)
function admin_perm(string $key): bool {
    global $con;
    if (!isset($_SESSION['_admin_perms'])) {
        $u = mysqli_real_escape_string($con, $_SESSION['alogin'] ?? '');
        $r = mysqli_query($con, "SELECT * FROM admin_role_permissions WHERE username='$u' LIMIT 1");
        $_SESSION['_admin_perms'] = ($r && $row = mysqli_fetch_assoc($r)) ? $row : [];
    }
    // Super-admins (no row or role='super') have all permissions
    if (empty($_SESSION['_admin_perms'])) return true;
    return !empty($_SESSION['_admin_perms'][$key]);
}

// Map current page to required permission
function admin_require_perm(string $perm): void {
    if (!admin_perm($perm)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:60px">
        <h2 style="color:#e8233a">&#128683; Acceso denegado</h2>
        <p>No tienes permiso para ver esta pagina.</p>
        <a href="dashboard.php">Volver al dashboard</a></body></html>';
        exit();
    }
}
?>