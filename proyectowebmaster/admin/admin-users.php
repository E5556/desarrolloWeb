<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_settings');


@mysqli_query($con, "ALTER TABLE admin ADD COLUMN IF NOT EXISTS role VARCHAR(30) DEFAULT 'super'");

// Helper: save permissions from POST checkboxes for a given admin_id
function save_perms($con, $admin_id) {
    $perms = ['perm_products','perm_orders','perm_stats','perm_users','perm_settings','perm_marketing'];
    mysqli_query($con, "DELETE FROM admin_role_permissions WHERE admin_id=$admin_id");
    foreach ($perms as $p) {
        if (intval($_POST[$p] ?? 0)) {
            $pk = mysqli_real_escape_string($con, $p);
            mysqli_query($con, "INSERT IGNORE INTO admin_role_permissions (admin_id, permission_key) VALUES ($admin_id, '$pk')");
        }
    }
}

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
            $new_admin_id = mysqli_insert_id($con);
            save_perms($con, $new_admin_id);
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
            save_perms($con, $edit_id);
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
$admins = mysqli_query($con, "SELECT a.id, a.username, a.creationDate, a.updationDate,
    MAX(CASE WHEN arp.permission_key='perm_products'  THEN 1 ELSE 0 END) as perm_products,
    MAX(CASE WHEN arp.permission_key='perm_orders'    THEN 1 ELSE 0 END) as perm_orders,
    MAX(CASE WHEN arp.permission_key='perm_stats'     THEN 1 ELSE 0 END) as perm_stats,
    MAX(CASE WHEN arp.permission_key='perm_users'     THEN 1 ELSE 0 END) as perm_users,
    MAX(CASE WHEN arp.permission_key='perm_settings'  THEN 1 ELSE 0 END) as perm_settings,
    MAX(CASE WHEN arp.permission_key='perm_marketing' THEN 1 ELSE 0 END) as perm_marketing
    FROM admin a LEFT JOIN admin_role_permissions arp ON arp.admin_id=a.id
    GROUP BY a.id ORDER BY a.id ASC");

// Modo edición
$edit_row = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $er  = mysqli_query($con, "SELECT a.id, a.username,
        MAX(CASE WHEN arp.permission_key='perm_products'  THEN 1 ELSE 0 END) as perm_products,
        MAX(CASE WHEN arp.permission_key='perm_orders'    THEN 1 ELSE 0 END) as perm_orders,
        MAX(CASE WHEN arp.permission_key='perm_stats'     THEN 1 ELSE 0 END) as perm_stats,
        MAX(CASE WHEN arp.permission_key='perm_users'     THEN 1 ELSE 0 END) as perm_users,
        MAX(CASE WHEN arp.permission_key='perm_settings'  THEN 1 ELSE 0 END) as perm_settings,
        MAX(CASE WHEN arp.permission_key='perm_marketing' THEN 1 ELSE 0 END) as perm_marketing
        FROM admin a LEFT JOIN admin_role_permissions arp ON arp.admin_id=a.id
        WHERE a.id=$eid GROUP BY a.id");
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
        /* ── Tabla ── */
        .adm-table { border-collapse:separate; border-spacing:0; }
        .adm-table thead tr th {
            background:linear-gradient(135deg,#2c3e50,#34495e);
            color:#fff; font-size:.8em; letter-spacing:.5px;
            text-transform:uppercase; padding:10px 12px; border:none;
        }
        .adm-table tbody tr { transition:background .15s; }
        .adm-table tbody tr:hover { background:#f0f7ff !important; }
        .adm-table td { vertical-align:middle !important; font-size:.87em; padding:10px 12px; border-color:#eaeaea; }

        /* ── Avatar ── */
        .adm-avatar {
            width:36px; height:36px; border-radius:50%;
            display:inline-flex; align-items:center; justify-content:center;
            font-weight:700; font-size:.9em; color:#fff;
            margin-right:8px; vertical-align:middle;
        }

        /* ── Badge Tú ── */
        .badge-you {
            background:#337ab7; color:#fff; border-radius:10px;
            padding:1px 7px; font-size:.68em; font-weight:700;
            margin-left:5px; vertical-align:middle; letter-spacing:.3px;
        }

        /* ── Rol badges ── */
        .role-badge {
            display:inline-block; padding:3px 10px; border-radius:12px;
            font-size:.75em; font-weight:700; letter-spacing:.3px;
        }
        .role-super   { background:#fde8ec; color:#c0392b; border:1px solid #f5b7be; }
        .role-editor  { background:#e8f0fe; color:#1a56db; border:1px solid #bfcffc; }
        .role-viewer  { background:#f0f0f0; color:#555;    border:1px solid #ddd; }

        /* ── Permisos ── */
        .perm-tag {
            display:inline-block; padding:2px 7px; border-radius:8px; margin:2px;
            font-size:.73em; font-weight:600;
            background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9;
        }
        .perm-denied {
            background:#fafafa; color:#bbb; border:1px solid #eee;
        }

        /* ── Formulario ── */
        .form-section {
            background:#fff;
            border:1px solid #e0e0e0;
            border-radius:8px;
            padding:22px 26px;
            margin-bottom:22px;
            box-shadow:0 2px 8px rgba(0,0,0,.05);
        }
        .form-section .form-header {
            display:flex; align-items:center; gap:10px;
            border-bottom:2px solid #f0f0f0; padding-bottom:12px; margin-bottom:18px;
        }
        .form-section .form-header .fh-icon {
            width:36px; height:36px; border-radius:8px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1em; color:#fff;
        }
        .form-section .form-header h4 { margin:0; font-size:1em; font-weight:700; }
        .form-section .form-header small { color:#888; font-size:.82em; }

        .perm-grid {
            display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;
        }
        .perm-check {
            display:flex; align-items:center; gap:6px;
            background:#f8f9fa; border:1px solid #e0e0e0;
            border-radius:6px; padding:6px 12px; cursor:pointer;
            transition:background .15s, border-color .15s;
            font-size:.85em; font-weight:600; color:#555;
        }
        .perm-check input { margin:0; cursor:pointer; }
        .perm-check:hover { background:#e8f5e9; border-color:#a5d6a7; color:#2e7d32; }
        .perm-check input:checked ~ span { color:#2e7d32; }

        /* ── Acciones ── */
        .btn-adm-edit {
            background:#337ab7; color:#fff; border:none;
            padding:4px 10px; border-radius:4px; font-size:.8em; cursor:pointer;
        }
        .btn-adm-edit:hover { background:#2868a0; color:#fff; }
        .btn-adm-del {
            background:#e8233a; color:#fff; border:none;
            padding:4px 10px; border-radius:4px; font-size:.8em; cursor:pointer;
        }
        .btn-adm-del:hover { background:#c0001e; color:#fff; }

        /* ── Stats header ── */
        .adm-stats {
            display:flex; gap:12px; margin-bottom:18px; flex-wrap:wrap;
        }
        .adm-stat-card {
            flex:1; min-width:120px; background:#fff;
            border:1px solid #e0e0e0; border-radius:8px;
            padding:14px 16px; text-align:center;
            box-shadow:0 1px 4px rgba(0,0,0,.05);
        }
        .adm-stat-card .asc-num { font-size:1.8em; font-weight:700; }
        .adm-stat-card .asc-lbl { font-size:.75em; color:#888; margin-top:2px; }
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
<?php
// Colores y etiquetas de rol
$role_colors = ['super'=>'#c0392b','editor'=>'#1a56db','viewer'=>'#555'];
$role_cls    = ['super'=>'role-super','editor'=>'role-editor','viewer'=>'role-viewer'];
$role_labels = ['super'=>'Super Admin','editor'=>'Editor','viewer'=>'Viewer'];
$role_icons  = ['super'=>'icon-star','editor'=>'icon-edit','viewer'=>'icon-eye-open'];
$perm_list   = ['perm_products'=>['📦','Productos'],'perm_orders'=>['🛒','Pedidos'],
                 'perm_stats'=>['📊','Stats'],'perm_users'=>['👥','Clientes'],
                 'perm_settings'=>['⚙️','Config'],'perm_marketing'=>['📣','Marketing']];

// Contar totales para stats
$total_admins = mysqli_num_rows($admins);
$super_count  = 0; $editor_count = 0; $viewer_count = 0;
$admins_arr   = [];
mysqli_data_seek($admins, 0);
while ($a = mysqli_fetch_assoc($admins)) {
    $admins_arr[] = $a;
    $r = $a['role'] ?? 'super';
    if ($r==='super') $super_count++;
    elseif ($r==='editor') $editor_count++;
    else $viewer_count++;
}
?>
<div class="module-head" style="background:linear-gradient(135deg,#2c3e50,#34495e);padding:16px 20px;border-radius:6px 6px 0 0">
    <h3 style="color:#fff;margin:0;font-size:1.1em">
        <i class="icon-lock" style="margin-right:8px;color:#e8233a"></i>
        Gestión de Administradores
        <small style="color:rgba(255,255,255,.6);font-size:.7em;margin-left:10px"><?php echo $total_admins; ?> en total</small>
    </h3>
</div>
<div class="module-body" style="padding:18px">

<?php if ($msg): ?>
<div class="alert alert-<?php echo $mtyp; ?>" style="border-radius:6px;margin-bottom:16px">
    <i class="icon-<?php echo $mtyp==='success'?'ok':'warning-sign'; ?>"></i> <?php echo $msg; ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="adm-stats">
    <div class="adm-stat-card">
        <div class="asc-num" style="color:#c0392b"><?php echo $super_count; ?></div>
        <div class="asc-lbl">⭐ Super Admin</div>
    </div>
    <div class="adm-stat-card">
        <div class="asc-num" style="color:#1a56db"><?php echo $editor_count; ?></div>
        <div class="asc-lbl">✏️ Editores</div>
    </div>
    <div class="adm-stat-card">
        <div class="asc-num" style="color:#555"><?php echo $viewer_count; ?></div>
        <div class="asc-lbl">👁 Viewers</div>
    </div>
    <div class="adm-stat-card" style="border-left:3px solid #337ab7">
        <div class="asc-num" style="color:#337ab7"><?php echo $total_admins; ?></div>
        <div class="asc-lbl">Total</div>
    </div>
</div>

<!-- ── FORMULARIO ── -->
<?php if ($edit_row): ?>
<div class="form-section">
    <div class="form-header">
        <div class="fh-icon" style="background:#337ab7"><i class="icon-edit"></i></div>
        <div>
            <h4>Editar administrador</h4>
            <small>Modificando: <strong><?php echo htmlentities($edit_row['username']); ?></strong></small>
        </div>
    </div>
    <form method="post" action="admin-users.php">
        <input type="hidden" name="action"  value="edit">
        <input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>">
        <div class="row-fluid">
            <div class="span4">
                <label>Nombre de usuario</label>
                <input type="text" class="input-block-level" name="username"
                       value="<?php echo htmlentities($edit_row['username']); ?>" required>
            </div>
            <div class="span4">
                <label>Nueva contraseña <small class="muted">(vacío = no cambiar)</small></label>
                <input type="password" class="input-block-level" name="password" autocomplete="new-password">
            </div>
            <div class="span4">
                <label>Confirmar contraseña</label>
                <input type="password" class="input-block-level" name="confirm" autocomplete="new-password">
            </div>
        </div>
        <div class="row-fluid" style="margin-top:12px">
            <div class="span4">
                <label>Rol</label>
                <select name="role" class="input-block-level">
                    <?php foreach ($role_labels as $rv => $rl): ?>
                    <option value="<?php echo $rv; ?>" <?php echo (($edit_row['role']??'super')===$rv?'selected':''); ?>><?php echo $rl; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span8">
                <label>Permisos granulares</label>
                <div class="perm-grid">
                    <?php foreach ($perm_list as $pk => [$ico, $lbl]): ?>
                    <label class="perm-check">
                        <input type="checkbox" name="<?php echo $pk; ?>" value="1" <?php echo (($edit_row[$pk]??0)?'checked':''); ?>>
                        <span><?php echo $ico; ?> <?php echo $lbl; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="icon-save"></i> Guardar cambios</button>
            <a href="admin-users.php" class="btn btn-default">Cancelar</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="form-section">
    <div class="form-header">
        <div class="fh-icon" style="background:#27ae60"><i class="icon-plus"></i></div>
        <div>
            <h4>Crear nuevo administrador</h4>
            <small>Completa los datos para añadir un acceso al panel</small>
        </div>
    </div>
    <form method="post" action="admin-users.php">
        <input type="hidden" name="action" value="create">
        <div class="row-fluid">
            <div class="span4">
                <label>Nombre de usuario</label>
                <input type="text" class="input-block-level" name="username" placeholder="ej: admin2" required>
            </div>
            <div class="span4">
                <label>Contraseña</label>
                <input type="password" class="input-block-level" name="password" autocomplete="new-password" required>
            </div>
            <div class="span4">
                <label>Confirmar contraseña</label>
                <input type="password" class="input-block-level" name="confirm" autocomplete="new-password" required>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:12px">
            <div class="span4">
                <label>Rol</label>
                <select name="role" class="input-block-level">
                    <option value="super">Super Admin</option>
                    <option value="editor" selected>Editor</option>
                    <option value="viewer">Viewer</option>
                </select>
            </div>
            <div class="span8">
                <label>Permisos granulares</label>
                <div class="perm-grid">
                    <?php $defaults = ['perm_products'=>1,'perm_orders'=>1,'perm_stats'=>1,'perm_users'=>0,'perm_settings'=>0,'perm_marketing'=>1];
                    foreach ($perm_list as $pk => [$ico, $lbl]): ?>
                    <label class="perm-check">
                        <input type="checkbox" name="<?php echo $pk; ?>" value="1" <?php echo ($defaults[$pk]?'checked':''); ?>>
                        <span><?php echo $ico; ?> <?php echo $lbl; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div style="margin-top:16px">
            <button type="submit" class="btn btn-success"><i class="icon-plus"></i> Crear administrador</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ── TABLA ── -->
<table class="table table-bordered adm-table" style="margin-bottom:8px">
    <thead>
        <tr>
            <th width="36">#</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Permisos</th>
            <th>Creado</th>
            <th>Modificado</th>
            <th width="130">Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($admins_arr as $i => $adm):
        $is_me   = ($adm['username'] === $current_admin);
        $adm_role = $adm['role'] ?? 'super';
        $avatar_colors = ['#e8233a','#337ab7','#27ae60','#f39c12','#9b59b6','#e67e22'];
        $av_color = $avatar_colors[ord($adm['username'][0]) % count($avatar_colors)];
        $av_letter = strtoupper($adm['username'][0]);
    ?>
    <tr>
        <td style="color:#aaa;font-size:.8em"><?php echo $i+1; ?></td>
        <td>
            <div style="display:flex;align-items:center;gap:8px">
                <span class="adm-avatar" style="background:<?php echo $av_color; ?>"><?php echo $av_letter; ?></span>
                <div>
                    <strong><?php echo htmlentities($adm['username']); ?></strong>
                    <?php if ($is_me): ?><span class="badge-you">Tú</span><?php endif; ?>
                </div>
            </div>
        </td>
        <td>
            <span class="role-badge <?php echo $role_cls[$adm_role]??'role-viewer'; ?>">
                <?php echo $role_labels[$adm_role]??$adm_role; ?>
            </span>
        </td>
        <td>
            <?php foreach ($perm_list as $pk => [$ico, $lbl]):
                $has = ($adm[$pk] ?? 0);
            ?>
            <span class="perm-tag <?php echo $has?'':'perm-denied'; ?>"
                  title="<?php echo $has?'Permitido':'Denegado'; ?>">
                <?php echo $ico; ?> <?php echo $lbl; ?>
            </span>
            <?php endforeach; ?>
        </td>
        <td style="color:#888;font-size:.8em;white-space:nowrap"><?php echo substr($adm['creationDate'],0,10); ?></td>
        <td style="color:#888;font-size:.8em;white-space:nowrap"><?php echo $adm['updationDate'] ?: '—'; ?></td>
        <td>
            <a href="admin-users.php?edit=<?php echo $adm['id']; ?>" class="btn-adm-edit">
                <i class="icon-edit"></i> Editar
            </a>
            <?php if (!$is_me): ?>
            <a href="admin-users.php?delete=<?php echo $adm['id']; ?>"
               class="btn-adm-del"
               style="display:inline-block;margin-top:3px"
               onclick="return confirm('¿Eliminar a <?php echo htmlspecialchars($adm['username'],ENT_QUOTES); ?>?')">
                <i class="icon-trash"></i> Eliminar
            </a>
            <?php else: ?>
            <button class="btn-adm-del" disabled style="opacity:.4;cursor:not-allowed">
                <i class="icon-trash"></i> Eliminar
            </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($admins_arr)): ?>
    <tr><td colspan="7" style="text-align:center;color:#aaa;font-style:italic;padding:20px">Sin administradores registrados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<small class="muted"><i class="icon-info-sign"></i> <?php echo $total_admins; ?> administrador(es) en total.</small>

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
