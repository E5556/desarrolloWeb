<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }
admin_require_perm('perm_marketing');


$msg = ''; $msg_type = 'success';

// ── ELIMINAR ─────────────────────────────────────────────────────────────
if (isset($_GET['del'])) {
    $del_id = intval($_GET['del']);
    mysqli_query($con, "DELETE FROM coupons WHERE id=$del_id");
    header('location:coupons.php?ok=deleted'); exit();
}

// ── TOGGLE ACTIVO ─────────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE coupons SET active = IF(active=1,0,1) WHERE id=$tid");
    header('location:coupons.php'); exit();
}

// ── GUARDAR (crear o editar) ──────────────────────────────────────────────
if (isset($_POST['save_coupon'])) {
    $edit_id     = intval($_POST['edit_id'] ?? 0);
    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $type        = in_array($_POST['type']??'',['percent','fixed']) ? $_POST['type'] : 'percent';
    $value       = max(0, floatval($_POST['value']   ?? 0));
    $min_p       = max(0, floatval($_POST['min_purchase'] ?? 0));
    $max_uses    = max(0, intval($_POST['max_uses']  ?? 0));
    $expires     = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $active      = isset($_POST['active']) ? 1 : 0;

    if (empty($code)) { $msg='El código no puede estar vacío.'; $msg_type='error'; }
    elseif ($type==='percent' && $value>100) { $msg='El porcentaje no puede superar 100.'; $msg_type='error'; }
    else {
        if ($edit_id > 0) {
            $st = mysqli_prepare($con, "UPDATE coupons SET code=?,type=?,value=?,min_purchase=?,max_uses=?,expires_at=?,active=? WHERE id=?");
            mysqli_stmt_bind_param($st,'ssddisii', $code,$type,$value,$min_p,$max_uses,$expires,$active,$edit_id);
        } else {
            $st = mysqli_prepare($con, "INSERT INTO coupons(code,type,value,min_purchase,max_uses,expires_at,active) VALUES(?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($st,'ssddisl', $code,$type,$value,$min_p,$max_uses,$expires,$active);
        }
        if (mysqli_stmt_execute($st)) {
            $msg = $edit_id>0 ? 'Cupón actualizado correctamente.' : 'Cupón creado correctamente.';
        } else {
            $msg = 'Error: el código ya existe o hubo un problema.'; $msg_type='error';
        }
        mysqli_stmt_close($st);
    }
}

// ── CARGAR PARA EDITAR ────────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $eid   = intval($_GET['edit']);
    $edit  = ($__r = mysqli_query($con, "SELECT * FROM coupons WHERE id=$eid")) ? mysqli_fetch_assoc($__r) : null;
}

// ── LISTAR ───────────────────────────────────────────────────────────────
$coupons = mysqli_query($con, "SELECT * FROM coupons ORDER BY created_at DESC");

if (isset($_GET['ok'])) {
    $msg = $_GET['ok']==='deleted' ? 'Cupón eliminado.' : 'Operación exitosa.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Cupones</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <style>
        .coupon-badge { font-family:monospace; font-size:.95em; background:#f5f5f5;
                        border:1px dashed #bbb; padding:2px 8px; border-radius:4px; letter-spacing:1px; }
        .type-pill { display:inline-block; padding:2px 8px; border-radius:10px; font-size:.78em; font-weight:600; }
        .type-percent { background:#dff0d8; color:#3c763d; }
        .type-fixed   { background:#d9edf7; color:#31708f; }
        .form-section { background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px; margin-bottom:22px; }
        .form-section h4 { margin:0 0 16px; border-bottom:1px solid #eee; padding-bottom:8px; color:#444; }
        .progress-uses { height:6px; border-radius:3px; background:#e0e0e0; margin-top:4px; }
        .progress-uses-fill { height:6px; border-radius:3px; background:#5cb85c; }
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
    <h3><i class="icon-tag" style="margin-right:6px"></i>Cupones y descuentos</h3>
</div>
<div class="module-body">

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_type==='error'?'error':'success'; ?>">
    <button class="close" data-dismiss="alert">×</button>
    <?php echo htmlentities($msg); ?>
</div>
<?php endif; ?>

<!-- ── FORMULARIO ──────────────────────────────────────────── -->
<div class="form-section">
    <h4><?php echo $edit ? '<i class="icon-edit"></i> Editar cupón' : '<i class="icon-plus"></i> Crear nuevo cupón'; ?></h4>
    <form method="post" class="form-horizontal">
        <input type="hidden" name="edit_id" value="<?php echo $edit ? $edit['id'] : 0; ?>">

        <div class="row-fluid">
            <div class="span6">
                <div class="control-group">
                    <label class="control-label">Código <span style="color:red">*</span></label>
                    <div class="controls">
                        <input type="text" name="code" class="span10" required maxlength="50"
                               style="text-transform:uppercase;font-family:monospace;letter-spacing:1px"
                               value="<?php echo $edit ? htmlentities($edit['code']) : ''; ?>"
                               placeholder="Ej: VERANO20">
                        <span class="help-block" style="font-size:.78em">Solo letras y números, sin espacios.</span>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label class="control-label">Tipo</label>
                    <div class="controls">
                        <select name="type" class="span10" id="coupon-type">
                            <option value="percent" <?php echo (!$edit||$edit['type']==='percent')?'selected':''; ?>>Porcentaje (%)</option>
                            <option value="fixed"   <?php echo ($edit&&$edit['type']==='fixed')?'selected':''; ?>>Monto fijo ($)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label class="control-label">Valor <span id="type-unit"><?php echo ($edit&&$edit['type']==='fixed')?'($)':'(%)'; ?></span></label>
                    <div class="controls">
                        <input type="number" name="value" class="span10" required min="0" step="0.01"
                               value="<?php echo $edit ? floatval($edit['value']) : ''; ?>" placeholder="Ej: 15">
                    </div>
                </div>
            </div>
        </div>

        <div class="row-fluid">
            <div class="span4">
                <div class="control-group">
                    <label class="control-label">Compra mínima ($)</label>
                    <div class="controls">
                        <input type="number" name="min_purchase" class="span10" min="0" step="1"
                               value="<?php echo $edit ? floatval($edit['min_purchase']) : '0'; ?>">
                        <span class="help-block" style="font-size:.78em">0 = sin mínimo</span>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label">Usos máximos</label>
                    <div class="controls">
                        <input type="number" name="max_uses" class="span10" min="0" step="1"
                               value="<?php echo $edit ? intval($edit['max_uses']) : '0'; ?>">
                        <span class="help-block" style="font-size:.78em">0 = ilimitado</span>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label">Vence el</label>
                    <div class="controls">
                        <input type="date" name="expires_at" class="span10"
                               value="<?php echo $edit ? htmlentities($edit['expires_at']??'') : ''; ?>">
                        <span class="help-block" style="font-size:.78em">Vacío = sin vencimiento</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <label class="checkbox">
                    <input type="checkbox" name="active" value="1" <?php echo (!$edit||$edit['active'])?'checked':''; ?>>
                    Cupón activo (visible para clientes)
                </label>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <button type="submit" name="save_coupon" class="btn btn-primary">
                    <i class="icon-save"></i> <?php echo $edit ? 'Actualizar cupón' : 'Crear cupón'; ?>
                </button>
                <?php if ($edit): ?>
                <a href="coupons.php" class="btn btn-default" style="margin-left:8px">Cancelar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- ── TABLA DE CUPONES ────────────────────────────────────── -->
<div class="form-section">
    <h4><i class="icon-list"></i> Cupones existentes</h4>
    <?php $total_coupons = $coupons ? mysqli_num_rows($coupons) : 0; ?>
    <?php if ($total_coupons === 0): ?>
    <p class="text-muted">No hay cupones creados aún.</p>
    <?php else: ?>
    <table class="table table-bordered table-condensed" style="font-size:.88em">
        <thead>
            <tr style="background:#f5f5f5">
                <th>Código</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Mín. compra</th>
                <th>Usos</th>
                <th>Vence</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($coupons && $c = mysqli_fetch_assoc($coupons)):
            $expired = $c['expires_at'] && strtotime($c['expires_at']) < strtotime('today');
            $maxed   = $c['max_uses']>0 && $c['uses_count']>=$c['max_uses'];
            $pct_use = $c['max_uses']>0 ? min(100, round(($c['uses_count']/$c['max_uses'])*100)) : null;
        ?>
        <tr style="<?php echo (!$c['active']||$expired||$maxed)?'opacity:.55':''; ?>">
            <td><span class="coupon-badge"><?php echo htmlentities($c['code']); ?></span></td>
            <td>
                <span class="type-pill type-<?php echo $c['type']; ?>">
                    <?php echo $c['type']==='percent'?'Porcentaje':'Fijo'; ?>
                </span>
            </td>
            <td>
                <strong><?php echo $c['type']==='percent' ? floatval($c['value']).'%' : '$'.number_format($c['value'],0,'.',','); ?></strong>
            </td>
            <td><?php echo $c['min_purchase']>0 ? '$'.number_format($c['min_purchase'],0,'.',',') : '<span style="color:#aaa">Sin mínimo</span>'; ?></td>
            <td>
                <?php echo intval($c['uses_count']); ?>
                <?php if ($c['max_uses']>0): ?>
                / <?php echo intval($c['max_uses']); ?>
                <div class="progress-uses"><div class="progress-uses-fill" style="width:<?php echo $pct_use; ?>%;background:<?php echo $maxed?'#d9534f':'#5cb85c'; ?>"></div></div>
                <?php else: ?>
                <small style="color:#aaa">/ ∞</small>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($c['expires_at']): ?>
                <span style="color:<?php echo $expired?'#d9534f':'#555'; ?>">
                    <?php echo date('d/m/Y', strtotime($c['expires_at'])); ?>
                    <?php if($expired): ?><br><small style="color:#d9534f">Expirado</small><?php endif; ?>
                </span>
                <?php else: ?><span style="color:#aaa">Sin vencimiento</span><?php endif; ?>
            </td>
            <td>
                <?php if ($maxed): ?>
                <span class="label label-default">Agotado</span>
                <?php elseif ($expired): ?>
                <span class="label label-important">Expirado</span>
                <?php elseif ($c['active']): ?>
                <span class="label label-success">Activo</span>
                <?php else: ?>
                <span class="label label-default">Inactivo</span>
                <?php endif; ?>
            </td>
            <td style="white-space:nowrap">
                <a href="?toggle=<?php echo $c['id']; ?>" class="btn btn-mini btn-<?php echo $c['active']?'warning':'success'; ?>" title="<?php echo $c['active']?'Desactivar':'Activar'; ?>">
                    <i class="icon-<?php echo $c['active']?'pause':'play'; ?>"></i>
                </a>
                <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-mini btn-info"><i class="icon-edit"></i></a>
                <a href="?del=<?php echo $c['id']; ?>" class="btn btn-mini btn-danger"
                   onclick="return confirm('¿Eliminar cupón <?php echo htmlentities($c['code']); ?>?')">
                    <i class="icon-trash"></i>
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── LEYENDA ──────────────────────────────────────────────── -->
<div style="background:#f9f9f9;border:1px solid #e8e8e8;border-radius:5px;padding:12px 16px;font-size:.82em;color:#666">
    <strong><i class="icon-info-sign"></i> Cómo funcionan los cupones:</strong><br>
    <ul style="margin:6px 0 0 16px;padding:0">
        <li><strong>Porcentaje</strong> — descuenta X% del total del carrito. Máximo 100%.</li>
        <li><strong>Monto fijo</strong> — descuenta una cantidad fija en pesos. No puede superar el total.</li>
        <li>Si se define <strong>compra mínima</strong>, el cliente debe superar ese monto para poder aplicar el cupón.</li>
        <li><strong>Usos máximos = 0</strong> significa ilimitado. El contador sube cada vez que un cliente procede al pago.</li>
    </ul>
</div>

</div><!-- /.module-body -->
</div><!-- /.module -->
</div><!-- /.content -->
</div><!-- /.span9 -->
</div><!-- /.row -->
</div><!-- /.container -->
</div><!-- /.wrapper -->

<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
// Actualizar etiqueta "%" / "$" al cambiar tipo
document.getElementById('coupon-type').addEventListener('change', function(){
    document.getElementById('type-unit').textContent = this.value==='percent' ? '(%)' : '($)';
});
</script>
</body>
</html>
