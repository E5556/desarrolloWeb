<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

mysqli_report(MYSQLI_REPORT_OFF);
// Agregar columnas faltantes si no existen
mysqli_query($con, "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS contact VARCHAR(100) DEFAULT ''");
mysqli_query($con, "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS lead_time_days TINYINT UNSIGNED DEFAULT 0");
mysqli_query($con, "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS address TEXT DEFAULT ''");
mysqli_query($con, "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT ''");
mysqli_query($con, "ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$msg = ''; $msg_type = 'success';

// Eliminar
if (isset($_GET['del'])) {
    mysqli_query($con,"DELETE FROM suppliers WHERE id=".intval($_GET['del']));
    header('location:suppliers.php?ok=deleted'); exit();
}
// Toggle activo
if (isset($_GET['toggle'])) {
    mysqli_query($con,"UPDATE suppliers SET active=1-active WHERE id=".intval($_GET['toggle']));
    header('location:suppliers.php'); exit();
}
// Guardar proveedor
if (isset($_POST['save_supplier'])) {
    $eid   = intval($_POST['edit_id'] ?? 0);
    $name  = trim(substr($_POST['name']    ?? '', 0, 120));
    $cont  = trim(substr($_POST['contact'] ?? '', 0, 100));
    $em    = trim(substr($_POST['email']   ?? '', 0, 180));
    $ph    = trim(substr($_POST['phone']   ?? '', 0, 30));
    $addr  = trim($_POST['address'] ?? '');
    $lt    = max(0, intval($_POST['lead_time_days'] ?? 0));
    $notes = trim($_POST['notes'] ?? '');
    if ($name === '') {
        $msg = 'El nombre del proveedor es obligatorio.'; $msg_type = 'danger';
    } else {
        if ($eid > 0) {
            $stmt = mysqli_prepare($con,"UPDATE suppliers SET name=?,contact=?,email=?,phone=?,address=?,lead_time_days=?,notes=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,'sssssiis',$name,$cont,$em,$ph,$addr,$lt,$notes,$eid);
        } else {
            $stmt = mysqli_prepare($con,"INSERT INTO suppliers (name,contact,email,phone,address,lead_time_days,notes) VALUES(?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,'sssssis',$name,$cont,$em,$ph,$addr,$lt,$notes);
        }
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        header('location:suppliers.php?ok=1'); exit();
    }
}
// Asignar proveedor a producto
if (isset($_POST['assign_supplier'])) {
    $ppid = intval($_POST['prod_id']);
    $spid = intval($_POST['sup_id']);
    mysqli_query($con,"UPDATE products SET supplier_id=".($spid ? $spid : 'NULL')." WHERE id=$ppid");
    header('location:suppliers.php?ok=assigned&tab=assign'); exit();
}

$edit_row = null;
$active_tab = $_GET['tab'] ?? 'list';
if (isset($_GET['edit'])) {
    $er = mysqli_query($con,"SELECT * FROM suppliers WHERE id=".intval($_GET['edit']));
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
    $active_tab = 'form';
}

$suppliers = mysqli_query($con,"SELECT s.*, COUNT(p.id) as product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id GROUP BY s.id ORDER BY s.active DESC, s.name");
$products  = mysqli_query($con,"SELECT p.id, p.productName, COALESCE(s.name,'Sin proveedor') as supname FROM products p LEFT JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.productName LIMIT 300");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proveedores | <?php echo $_ADMIN_SITE_NAME; ?></title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
<link href="images/icons/css/font-awesome.css" rel="stylesheet">
<link href="css/theme.css" rel="stylesheet">
<style>
.sup-card { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:20px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,.04); }
.sup-tabs { display:flex; gap:0; margin-bottom:20px; border-bottom:2px solid #e0e0e0; }
.sup-tab  { padding:10px 22px; cursor:pointer; font-weight:600; font-size:.88em; color:#888; border-bottom:3px solid transparent; margin-bottom:-2px; transition:.2s; }
.sup-tab.active { color:#337ab7; border-bottom-color:#337ab7; }
.sup-tab:hover  { color:#337ab7; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }
.badge-active   { background:#e8f8f0; color:#27ae60; padding:3px 10px; border-radius:12px; font-size:.8em; font-weight:700; }
.badge-inactive { background:#f5f5f5; color:#aaa;    padding:3px 10px; border-radius:12px; font-size:.8em; font-weight:700; }
.sup-row { border:1px solid #e8e8e8; border-radius:8px; padding:14px 16px; margin-bottom:10px; background:#fff; display:flex; align-items:center; gap:12px; }
.sup-row:hover { border-color:#c5d9ed; background:#f8fbff; }
.sup-name { font-weight:700; font-size:.95em; color:#2c3e50; }
.sup-meta { font-size:.8em; color:#888; margin-top:2px; }
.sup-actions { margin-left:auto; display:flex; gap:6px; flex-shrink:0; }
.field-group { margin-bottom:14px; }
.field-group label { font-size:.82em; font-weight:600; color:#555; display:block; margin-bottom:4px; }
.field-group input, .field-group textarea, .field-group select { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:6px; font-size:.88em; }
.field-group input:focus, .field-group textarea:focus { border-color:#337ab7; outline:none; box-shadow:0 0 0 2px rgba(51,122,183,.15); }
.row-2col { display:flex; gap:14px; }
.row-2col > div { flex:1; }
</style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper"><div class="container-fluid"><div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9"><div class="content">

<!-- Encabezado -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
        <h3 style="margin:0;color:#2c3e50"><i class="icon-truck"></i> Gestión de Proveedores</h3>
        <small style="color:#888">Administra tus proveedores y asígnalos a productos</small>
    </div>
    <button class="btn btn-primary btn-sm" onclick="setTab('form')">
        <i class="icon-plus"></i> Nuevo proveedor
    </button>
</div>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">✅ Operación completada correctamente.</div>
<?php endif; ?>
<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="sup-tabs">
    <div class="sup-tab <?php echo $active_tab==='list'?'active':''; ?>" onclick="setTab('list')">
        <i class="icon-list"></i> Lista de proveedores
        <span class="badge" style="margin-left:4px"><?php echo $suppliers ? mysqli_num_rows($suppliers) : 0; ?></span>
    </div>
    <div class="sup-tab <?php echo $active_tab==='form'?'active':''; ?>" onclick="setTab('form')">
        <i class="icon-<?php echo $edit_row?'edit':'plus'; ?>"></i> <?php echo $edit_row?'Editar proveedor':'Nuevo proveedor'; ?>
    </div>
    <div class="sup-tab <?php echo $active_tab==='assign'?'active':''; ?>" onclick="setTab('assign')">
        <i class="icon-link"></i> Asignar a productos
    </div>
</div>

<!-- TAB: Lista -->
<div class="tab-pane <?php echo $active_tab==='list'?'active':''; ?>" id="tab-list">
<?php mysqli_data_seek($suppliers, 0);
if (!$suppliers || mysqli_num_rows($suppliers) === 0): ?>
    <div class="alert alert-info">No hay proveedores registrados. <a href="#" onclick="setTab('form')">Crear el primero →</a></div>
<?php else:
$activos = $inactivos = [];
while ($s = mysqli_fetch_assoc($suppliers)) {
    if ($s['active']) $activos[] = $s; else $inactivos[] = $s;
}
$render = function($list) { ?>
    <?php foreach ($list as $s): ?>
    <div class="sup-row">
        <div style="width:36px;height:36px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-weight:700;color:#337ab7;flex-shrink:0">
            <?php echo strtoupper(substr($s['name'],0,1)); ?>
        </div>
        <div style="flex:1;min-width:0">
            <div class="sup-name"><?php echo htmlspecialchars($s['name']); ?></div>
            <div class="sup-meta">
                <?php if ($s['contact']??''): ?><i class="icon-user"></i> <?php echo htmlspecialchars($s['contact']); ?> &nbsp;<?php endif; ?>
                <?php if ($s['email']??''): ?><i class="icon-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($s['email']); ?>"><?php echo htmlspecialchars($s['email']); ?></a> &nbsp;<?php endif; ?>
                <?php if ($s['phone']??''): ?><i class="icon-phone"></i> <?php echo htmlspecialchars($s['phone']); ?> &nbsp;<?php endif; ?>
                <?php if ($s['lead_time_days']??0): ?><i class="icon-time"></i> <?php echo $s['lead_time_days']; ?> días entrega &nbsp;<?php endif; ?>
            </div>
        </div>
        <div style="text-align:center;min-width:60px">
            <div style="font-size:1.4em;font-weight:700;color:#337ab7"><?php echo (int)$s['product_count']; ?></div>
            <div style="font-size:.72em;color:#888">productos</div>
        </div>
        <div>
            <?php if ($s['active']): ?>
            <span class="badge-active">Activo</span>
            <?php else: ?>
            <span class="badge-inactive">Inactivo</span>
            <?php endif; ?>
        </div>
        <div class="sup-actions">
            <a href="suppliers.php?edit=<?php echo $s['id']; ?>" class="btn btn-xs btn-primary" title="Editar"><i class="icon-edit"></i></a>
            <a href="suppliers.php?toggle=<?php echo $s['id']; ?>" class="btn btn-xs btn-<?php echo $s['active']?'warning':'success'; ?>" title="<?php echo $s['active']?'Desactivar':'Activar'; ?>">
                <i class="icon-<?php echo $s['active']?'pause':'play'; ?>"></i>
            </a>
            <a href="suppliers.php?del=<?php echo $s['id']; ?>" class="btn btn-xs btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este proveedor?')"><i class="icon-trash"></i></a>
        </div>
    </div>
    <?php endforeach;
};
if (!empty($activos)): ?>
    <h5 style="color:#27ae60;margin-bottom:10px"><i class="icon-ok-circle"></i> Activos (<?php echo count($activos); ?>)</h5>
    <?php $render($activos); ?>
<?php endif;
if (!empty($inactivos)): ?>
    <h5 style="color:#aaa;margin:16px 0 10px"><i class="icon-ban-circle"></i> Inactivos (<?php echo count($inactivos); ?>)</h5>
    <?php $render($inactivos); ?>
<?php endif; endif; ?>
</div>

<!-- TAB: Formulario -->
<div class="tab-pane <?php echo $active_tab==='form'?'active':''; ?>" id="tab-form">
<div class="sup-card" style="max-width:620px">
    <h4 style="margin:0 0 18px;color:#2c3e50;border-bottom:1px solid #f0f0f0;padding-bottom:10px">
        <i class="icon-<?php echo $edit_row?'edit':'plus-sign'; ?>"></i>
        <?php echo $edit_row ? 'Editar: ' . htmlspecialchars($edit_row['name']) : 'Nuevo Proveedor'; ?>
    </h4>
    <form method="post">
    <input type="hidden" name="edit_id" value="<?php echo $edit_row ? $edit_row['id'] : 0; ?>">
    <?php $er = $edit_row ?? []; ?>

    <div class="field-group">
        <label>Nombre del proveedor <span style="color:#e8233a">*</span></label>
        <input type="text" name="name" required maxlength="120" value="<?php echo htmlspecialchars($er['name']??''); ?>" placeholder="Ej: Distribuidora Colombia S.A.S">
    </div>

    <div class="row-2col">
        <div class="field-group">
            <label>Persona de contacto</label>
            <input type="text" name="contact" maxlength="100" value="<?php echo htmlspecialchars($er['contact']??''); ?>" placeholder="Nombre del contacto">
        </div>
        <div class="field-group">
            <label>Teléfono</label>
            <input type="text" name="phone" maxlength="30" value="<?php echo htmlspecialchars($er['phone']??''); ?>" placeholder="3001234567">
        </div>
    </div>

    <div class="field-group">
        <label>Email</label>
        <input type="email" name="email" maxlength="180" value="<?php echo htmlspecialchars($er['email']??''); ?>" placeholder="contacto@proveedor.com">
    </div>

    <div class="row-2col">
        <div class="field-group">
            <label>Dirección</label>
            <input type="text" name="address" value="<?php echo htmlspecialchars($er['address']??''); ?>" placeholder="Ciudad, Dirección">
        </div>
        <div class="field-group">
            <label>Tiempo de entrega (días)</label>
            <input type="number" name="lead_time_days" min="0" max="365" value="<?php echo intval($er['lead_time_days']??0); ?>">
        </div>
    </div>

    <div class="field-group">
        <label>Notas internas</label>
        <textarea name="notes" rows="3" placeholder="Condiciones de pago, observaciones..."><?php echo htmlspecialchars($er['notes']??''); ?></textarea>
    </div>

    <div style="display:flex;gap:10px;margin-top:6px">
        <button type="submit" name="save_supplier" class="btn btn-primary">
            <i class="icon-ok"></i> <?php echo $edit_row ? 'Guardar cambios' : 'Crear proveedor'; ?>
        </button>
        <?php if ($edit_row): ?>
        <a href="suppliers.php" class="btn btn-default">Cancelar</a>
        <?php endif; ?>
    </div>
    </form>
</div>
</div>

<!-- TAB: Asignar -->
<div class="tab-pane <?php echo $active_tab==='assign'?'active':''; ?>" id="tab-assign">
<div class="sup-card" style="max-width:520px">
    <h4 style="margin:0 0 6px;color:#2c3e50"><i class="icon-link"></i> Asignar proveedor a producto</h4>
    <p style="color:#888;font-size:.85em;margin-bottom:18px">Vincula un producto con su proveedor principal para las órdenes de compra.</p>
    <form method="post">
    <div class="field-group">
        <label>Producto</label>
        <select name="prod_id">
            <?php mysqli_data_seek($products,0); while ($p=mysqli_fetch_assoc($products)): ?>
            <option value="<?php echo $p['id']; ?>">
                <?php echo htmlspecialchars($p['productName']); ?> — (<?php echo htmlspecialchars($p['supname']); ?>)
            </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="field-group">
        <label>Proveedor</label>
        <select name="sup_id">
            <option value="0">— Sin proveedor —</option>
            <?php mysqli_data_seek($suppliers,0); while ($s=mysqli_fetch_assoc($suppliers)): if (!$s['active']) continue; ?>
            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <button type="submit" name="assign_supplier" class="btn btn-info"><i class="icon-link"></i> Asignar</button>
    </form>
</div>
</div>

</div></div><!-- /span9 -->
</div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
function setTab(name) {
    document.querySelectorAll('.sup-tab').forEach(function(t){ t.classList.remove('active'); });
    document.querySelectorAll('.tab-pane').forEach(function(t){ t.classList.remove('active'); });
    document.querySelector('#tab-' + name).classList.add('active');
    // marcar tab activo
    var tabs = document.querySelectorAll('.sup-tab');
    var map = ['list','form','assign'];
    tabs[map.indexOf(name)].classList.add('active');
}
</script>
</body>
</html>
