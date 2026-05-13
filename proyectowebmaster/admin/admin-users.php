<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_settings');


// Auto-add role column and create permissions table
mysqli_query($con, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS role VARCHAR(30) DEFAULT 'super'");
mysqli_query($con, "CREATE TABLE IF NOT EXISTS admin_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    perm_products TINYINT(1) DEFAULT 1,
    perm_orders TINYINT(1) DEFAULT 1,
    perm_stats TINYINT(1) DEFAULT 1,
    perm_users TINYINT(1) DEFAULT 1,
    perm_settings TINYINT(1) DEFAULT 0,
    perm_marketing TINYINT(1) DEFAULT 1
)");

// Impedir que el admin actual se elimine a sí mismo
$current_admin = $_SESSION['alogin']; // username del admin logueado

$msg  = '';
$mtyp = '';

// ── ELIMINAR ─────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Obtener username antes de borrar
    $chk = mysqli_query($con, "SELECT username FROM admin WHERE id=$del_id");
    $chk_row = $chk ? mysqli_fetch_assoc($chk) : null;
    if ($chk_row && $chk_row['username'] === $current_admin) {
        $msg  = 'No puedes eliminar tu propia cuenta de administrador.';
        $mtyp = 'danger';
    } elseif ($chk_row) {
        mysqli_query($con, "DELETE FROM admin WHERE id=$del_id");
        $msg  = 'Administrador eliminado correctamente.';
        $mtyp = 'success';
    } else {
        $msg  = 'Administrador no encontrado.';
        $mtyp = 'danger';
    }
}

// ── CREAR ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $new_user = trim($_POST['username'] ?? '');
    $new_pass = $_POST['password'] ?? '';
    $new_conf = $_POST['confirm']   ?? '';

    if ($new_user === '' || $new_pass === '') {
        $msg = 'El usuario y la contraseña son obligatorios.'; $mtyp = 'danger';
    } elseif ($new_pass !== $new_conf) {
        $msg = 'Las contraseñas no coinciden.'; $mtyp = 'danger';
    } elseif (strlen($new_pass) < 6) {
        $msg = 'La contraseña debe tener al menos 6 caracteres.'; $mtyp = 'danger';
    } else {
        $eu = mysqli_real_escape_string($con, $new_user);
        $exists = mysqli_query($con, "SELECT id FROM admin WHERE username='$eu'");
        if (mysqli_num_rows($exists) > 0) {
            $msg = "El nombre de usuario \"$new_user\" ya existe."; $mtyp = 'danger';
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $eh   = mysqli_real_escape_string($con, $hash);
            $now  = date('d-m-Y h:i:s A');
            $role = in_array($_POST['role']??'editor',['super','editor','viewer']) ? $_POST['role'] : 'editor';
            mysqli_query($con, "INSERT INTO admin (username,password,updationDate,role) VALUES ('$eu','$eh','$now','$role')");
            $pp = intval($_POST['perm_products']??1); $po = intval($_POST['perm_orders']??1);
            $ps = intval($_POST['perm_stats']??1); $pu = intval($_POST['perm_users']??0);
            $pset = intval($_POST['perm_settings']??0); $pm = intval($_POST['perm_marketing']??1);
            mysqli_query($con,"INSERT INTO admin_role_permissions (username,perm_products,perm_orders,perm_stats,perm_users,perm_settings,perm_marketing) VALUES ('$eu',$pp,$po,$ps,$pu,$pset,$pm) ON DUPLICATE KEY UPDATE perm_products=$pp,perm_orders=$po,perm_stats=$ps,perm_users=$pu,perm_settings=$pset,perm_marketing=$pm");
            $msg = "Administrador <strong>$new_user</strong> creado correctamente."; $mtyp = 'success';
        }
    }
}

// ── EDITAR (guardar) ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id   = intval($_POST['edit_id'] ?? 0);
    $edit_user = trim($_POST['username'] ?? '');
    $edit_pass = $_POST['password']  ?? '';
    $edit_conf = $_POST['confirm']   ?? '';

    if ($edit_user === '') {
        $msg = 'El nombre de usuario no puede estar vacío.'; $mtyp = 'danger';
    } else {
        $eu = mysqli_real_escape_string($con, $edit_user);
        // Verificar duplicado (excluyendo el propio id)
        $dup = mysqli_query($con, "SELECT id FROM admin WHERE username='$eu' AND id!=$edit_id");
        if (mysqli_num_rows($dup) > 0) {
            $msg = "El nombre de usuario \"$edit_user\" ya está en uso."; $mtyp = 'danger';
        } else {
            $now = mysqli_real_escape_string($con, date('d-m-Y h:i:s A'));
            $erole = in_array($_POST['role']??'editor',['super','editor','viewer']) ? $_POST['role'] : 'editor';
            $epp = intval($_POST['perm_products']??1); $epo = intval($_POST['perm_orders']??1);
            $eps = intval($_POST['perm_stats']??1); $epu = intval($_POST['perm_users']??0);
            $epset = intval($_POST['perm_settings']??0); $epm = intval($_POST['perm_marketing']??1);
            mysqli_query($con,"INSERT INTO admin_role_permissions (username,perm_products,perm_orders,perm_stats,perm_users,perm_settings,perm_marketing) VALUES ('$eu',$epp,$epo,$eps,$epu,$epset,$epm) ON DUPLICATE KEY UPDATE perm_products=$epp,perm_orders=$epo,perm_stats=$eps,perm_users=$epu,perm_settings=$epset,perm_marketing=$epm");
            if ($edit_pass !== '') {
                if ($edit_pass !== $edit_conf) {
                    $msg = 'Las contraseñas no coinciden.'; $mtyp = 'danger';
                } elseif (strlen($edit_pass) < 6) {
                    $msg = 'La contraseña debe tener al menos 6 caracteres.'; $mtyp = 'danger';
                } else {
                    $eh = mysqli_real_escape_string($con, password_hash($edit_pass, PASSWORD_BCRYPT));
                    mysqli_query($con, "UPDATE admin SET username='$eu', password='$eh', updationDate='$now', role='$erole' WHERE id=$edit_id");
                    $msg = 'Administrador actualizado (usuario + contraseña).'; $mtyp = 'success';
                }
            } else {
                mysqli_query($con, "UPDATE admin SET username='$eu', updationDate='$now', role='$erole' WHERE id=$edit_id");
                $msg = 'Nombre de usuario actualizado.'; $mtyp = 'success';
            }
        }
    }
}

// ── LISTAR ────────────────────────────────────────────────────────────────────
$admins = mysqli_query($con, "SELECT a.id, a.username, a.creationDate, a.updationDate, COALESCE(a.role,'super') as role, arp.perm_products, arp.perm_orders, arp.perm_stats, arp.perm_users, arp.perm_settings, arp.perm_marketing FROM admin a LEFT JOIN admin_role_permissions arp ON arp.username=a.username ORDER BY a.id ASC");

// Modo edición
$edit_row = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $er  = mysqli_query($con, "SELECT a.id, a.username, COALESCE(a.role,'super') as role, arp.perm_products, arp.perm_orders, arp.perm_stats, arp.perm_users, arp.perm_settings, arp.perm_marketing FROM admin a LEFT JOIN admin_role_permissions arp ON arp.username=a.username WHERE a.id=$eid");
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Administradores</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <style>
        .adm-table td { vertical-align:middle !important; font-size:.88em; }
        .adm-table th { background:#f9f9f9; font-size:.83em; }
        .badge-you { background:#337ab7; color:#fff; border-radius:10px;
                     padding:2px 8px; font-size:.72em; font-weight:700; margin-left:5px; }
        .form-section { background:#fafafa; border:1px solid #e4e4e4;
                        border-radius:6px; padding:20px 24px; margin-bottom:20px; }
        .form-section h4 { margin-top:0; border-bottom:1px solid #e0e0e0; padding-bottom:8px; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper">
<div class="container">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content">
<div class="module">
<div class="module-head">
    <h3><i class="icon-lock" style="margin-right:6px"></i>Gestión de Administradores</h3>
</div>
<div class="module-body">

<?php if ($msg): ?>
<div class="alert alert-<?php echo $mtyp; ?>"><?php echo $msg; ?></div>
<?php endif; ?>

<!-- ── FORMULARIO CREAR / EDITAR ── -->
<?php if ($edit_row): ?>
<div class="form-section">
    <h4><i class="icon-edit"></i> Editar administrador: <strong><?php echo htmlentities($edit_row['username']); ?></strong></h4>
    <form method="post" action="admin-users.php">
        <input type="hidden" name="action"  value="edit">
        <input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>">
        <div class="row-fluid">
            <div class="span6">
                <label>Nombre de usuario</label>
                <input type="text" class="input-block-level" name="username"
                       value="<?php echo htmlentities($edit_row['username']); ?>" required>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:10px">
            <div class="span6">
                <label>Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                <input type="password" class="input-block-level" name="password" autocomplete="new-password">
            </div>
            <div class="span6">
                <label>Confirmar contraseña</label>
                <input type="password" class="input-block-level" name="confirm" autocomplete="new-password">
            </div>
        </div>
        <div class="row-fluid" style="margin-top:14px">
            <div class="span12">
                <label>Rol</label>
                <select name="role" class="input-block-level" style="width:auto">
                    <option value="super" <?php echo (($edit_row['role']??'super')==='super'?'selected':''); ?>>Super Admin (acceso total)</option>
                    <option value="editor" <?php echo (($edit_row['role']??'')==='editor'?'selected':''); ?>>Editor (sin configuración)</option>
                    <option value="viewer" <?php echo (($edit_row['role']??'')==='viewer'?'selected':''); ?>>Viewer (solo lectura)</option>
                </select>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:12px">
            <div class="span12">
                <label>Permisos granulares:</label><br>
                <?php $pr = $edit_row; ?>
                <label class="checkbox inline"><input type="checkbox" name="perm_products" value="1" <?php echo (($pr['perm_products']??1)?'checked':''); ?>> Productos</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_orders" value="1" <?php echo (($pr['perm_orders']??1)?'checked':''); ?>> Pedidos</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_stats" value="1" <?php echo (($pr['perm_stats']??1)?'checked':''); ?>> Estadísticas</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_users" value="1" <?php echo (($pr['perm_users']??0)?'checked':''); ?>> Clientes</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_settings" value="1" <?php echo (($pr['perm_settings']??0)?'checked':''); ?>> Configuración</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_marketing" value="1" <?php echo (($pr['perm_marketing']??1)?'checked':''); ?>> Marketing</label>
            </div>
        </div>
        <div style="margin-top:14px">
            <button type="submit" class="btn btn-primary"><i class="icon-save"></i> Guardar cambios</button>
            <a href="admin-users.php" class="btn btn-default" style="margin-left:8px">Cancelar</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="form-section">
    <h4><i class="icon-plus"></i> Crear nuevo administrador</h4>
    <form method="post" action="admin-users.php">
        <input type="hidden" name="action" value="create">
        <div class="row-fluid">
            <div class="span6">
                <label>Nombre de usuario</label>
                <input type="text" class="input-block-level" name="username" placeholder="ej: admin2" required>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:10px">
            <div class="span6">
                <label>Contraseña</label>
                <input type="password" class="input-block-level" name="password" autocomplete="new-password" required>
            </div>
            <div class="span6">
                <label>Confirmar contraseña</label>
                <input type="password" class="input-block-level" name="confirm" autocomplete="new-password" required>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:14px">
            <div class="span12">
                <label>Rol</label>
                <select name="role" class="input-block-level" style="width:auto">
                    <option value="super">Super Admin (acceso total)</option>
                    <option value="editor" selected>Editor (sin configuración)</option>
                    <option value="viewer">Viewer (solo lectura)</option>
                </select>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:12px">
            <div class="span12">
                <label>Permisos granulares:</label><br>
                <label class="checkbox inline"><input type="checkbox" name="perm_products" value="1" checked> Productos</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_orders" value="1" checked> Pedidos</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_stats" value="1" checked> Estadísticas</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_users" value="1"> Clientes</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_settings" value="1"> Configuración</label>
                <label class="checkbox inline"><input type="checkbox" name="perm_marketing" value="1" checked> Marketing</label>
            </div>
        </div>
        <div style="margin-top:14px">
            <button type="submit" class="btn btn-success"><i class="icon-plus"></i> Crear administrador</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ── TABLA DE ADMINISTRADORES ── -->
<table class="table table-bordered table-striped adm-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Permisos</th>
            <th>Creado</th>
            <th>Última modificación</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    while ($adm = mysqli_fetch_assoc($admins)):
        $i++;
        $is_me = ($adm['username'] === $current_admin);
    ?>
    <?php $role_colors=['super'=>'#e8233a','editor'=>'#337ab7','viewer'=>'#888']; $role_labels=['super'=>'Super Admin','editor'=>'Editor','viewer'=>'Viewer']; ?>
    <tr>
        <td><?php echo $i; ?></td>
        <td>
            <i class="icon-user" style="margin-right:4px;color:#888"></i>
            <strong><?php echo htmlentities($adm['username']); ?></strong>
            <?php if ($is_me): ?><span class="badge-you">Tú</span><?php endif; ?>
        </td>
        <td><span style="background:<?php echo $role_colors[$adm['role']]??'#888'; ?>;color:#fff;padding:2px 8px;border-radius:10px;font-size:.78em"><?php echo $role_labels[$adm['role']]??$adm['role']; ?></span></td>
        <td style="font-size:.78em">
            <?php $picons = ['perm_products'=>'Productos','perm_orders'=>'Pedidos','perm_stats'=>'Stats','perm_users'=>'Clientes','perm_settings'=>'Config','perm_marketing'=>'Marketing'];
            foreach ($picons as $pk=>$pl): if($adm[$pk]??false): ?><span style="background:#e8f5e9;color:#388e3c;padding:1px 5px;border-radius:8px;margin:1px;display:inline-block"><?php echo $pl; ?></span><?php endif; endforeach; ?>
        </td>
        <td><small><?php echo htmlentities($adm['creationDate']); ?></small></td>
        <td><small><?php echo htmlentities($adm['updationDate'] ?: '—'); ?></small></td>
        <td>
            <a href="admin-users.php?edit=<?php echo $adm['id']; ?>" class="btn btn-mini btn-primary">
                <i class="icon-edit"></i> Editar
            </a>
            <?php if (!$is_me): ?>
            <a href="admin-users.php?delete=<?php echo $adm['id']; ?>"
               class="btn btn-mini btn-danger"
               onclick="return confirm('¿Eliminar al administrador \'<?php echo htmlspecialchars($adm['username'], ENT_QUOTES); ?>\'? Esta acción no se puede deshacer.')">
                <i class="icon-trash"></i> Eliminar
            </a>
            <?php else: ?>
            <button class="btn btn-mini btn-danger disabled" disabled title="No puedes eliminarte a ti mismo">
                <i class="icon-trash"></i> Eliminar
            </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if ($i === 0): ?>
    <tr><td colspan="7" class="text-muted" style="text-align:center;font-style:italic">No hay administradores registrados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<small class="text-muted"><?php echo $i; ?> administrador(es) en total.</small>

</div>
</div>
</div>
</div>
</div>
</div>
</div>

<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
