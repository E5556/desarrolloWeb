<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Crear tabla si no existe
mysqli_query($con, "CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
)");
// R3: columnas de reembolso
@mysqli_query($con, "ALTER TABLE returns ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(12,2) DEFAULT NULL");
@mysqli_query($con, "ALTER TABLE returns ADD COLUMN IF NOT EXISTS refund_method VARCHAR(50) DEFAULT NULL");
@mysqli_query($con, "ALTER TABLE returns ADD COLUMN IF NOT EXISTS refund_date DATE DEFAULT NULL");

// R3: procesar reembolso
if (isset($_POST['save_refund'])) {
    $rid    = intval($_POST['rid']);
    $amount = floatval($_POST['refund_amount'] ?? 0);
    $method = mysqli_real_escape_string($con, trim($_POST['refund_method'] ?? 'Transferencia'));
    $date   = !empty($_POST['refund_date']) ? $_POST['refund_date'] : date('Y-m-d');
    $stmt_rf = mysqli_prepare($con, "UPDATE returns SET refund_amount=?, refund_method=?, refund_date=? WHERE id=?");
    mysqli_stmt_bind_param($stmt_rf, 'dssi', $amount, $method, $date, $rid);
    mysqli_stmt_execute($stmt_rf); mysqli_stmt_close($stmt_rf);
    header('location:returns.php?ok=refund'); exit();
}

$msg = '';

// Cambiar estado
if (isset($_GET['action']) && isset($_GET['id'])) {
    $rid  = intval($_GET['id']);
    $act  = $_GET['action'];
    $note = trim($_POST['admin_note'] ?? '');
    $allowed = ['approved','rejected','completed'];
    if (in_array($act, $allowed)) {
        $stmt = mysqli_prepare($con, "UPDATE returns SET status=?, admin_note=?, updated_at=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $act, $note, $rid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Enviar email al cliente
        $rq = mysqli_query($con, "SELECT r.*, u.name, u.email FROM returns r JOIN users u ON r.user_id=u.id WHERE r.id=$rid LIMIT 1");
        if ($rq && $rdata = mysqli_fetch_assoc($rq)) {
            include_once('../includes/mailer.php');
            $act_labels = ['approved'=>'aprobada','rejected'=>'rechazada','completed'=>'completada'];
            $subject = "Actualización de tu solicitud de devolución #{$rid}";
            $body = "<p>Hola {$rdata['name']},</p>
            <p>Tu solicitud de devolución para el pedido #{$rdata['order_id']} ha sido <strong>{$act_labels[$act]}</strong>.</p>";
            if ($note) $body .= "<p><strong>Nota del equipo:</strong> " . htmlspecialchars($note) . "</p>";
            $body .= "<p>Gracias por comprar con nosotros.</p>";
            send_email_raw($rdata['email'], $rdata['name'], $subject, $body);
        }
        $msg = "Solicitud actualizada correctamente.";
    }
    header('location:returns.php?ok=1');
    exit();
}

// Filtro de estado
$filter = $_GET['filter'] ?? 'all';
$where  = ($filter !== 'all') ? "WHERE r.status='" . mysqli_real_escape_string($con, $filter) . "'" : '';
$returns = mysqli_query($con, "SELECT r.*, u.name AS uname, u.email AS uemail FROM returns r JOIN users u ON r.user_id=u.id $where ORDER BY r.created_at DESC");

$status_labels = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','completed'=>'Completada'];
$status_colors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'primary'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Devoluciones | Admin</title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
<style>
.filter-btns { margin-bottom:16px; }
.filter-btns a { margin-right:6px; }
</style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>

<div class="span9">
<div class="content-area">
<h3><i class="icon-undo"></i> Solicitudes de devolución</h3>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success">Solicitud actualizada correctamente.</div>
<?php endif; ?>

<div class="filter-btns">
    <a href="returns.php?filter=all" class="btn btn-sm <?php echo $filter==='all'?'btn-primary':'btn-default'; ?>">Todas</a>
    <a href="returns.php?filter=pending" class="btn btn-sm <?php echo $filter==='pending'?'btn-warning':'btn-default'; ?>">Pendientes</a>
    <a href="returns.php?filter=approved" class="btn btn-sm <?php echo $filter==='approved'?'btn-success':'btn-default'; ?>">Aprobadas</a>
    <a href="returns.php?filter=rejected" class="btn btn-sm <?php echo $filter==='rejected'?'btn-danger':'btn-default'; ?>">Rechazadas</a>
    <a href="returns.php?filter=completed" class="btn btn-sm <?php echo $filter==='completed'?'btn-primary':'btn-default'; ?>">Completadas</a>
</div>

<table class="table table-bordered table-hover table-striped">
<thead>
<tr>
    <th>#</th>
    <th>Pedido</th>
    <th>Cliente</th>
    <th>Motivo</th>
    <th>Foto</th>
    <th>Fecha</th>
    <th>Estado</th>
    <th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if (!$returns || mysqli_num_rows($returns) === 0): ?>
<tr><td colspan="8" class="text-center text-muted">No hay solicitudes <?php echo $filter!=='all'?'con este estado':''; ?>.</td></tr>
<?php else: while ($r = mysqli_fetch_assoc($returns)): ?>
<tr>
    <td><?php echo $r['id']; ?></td>
    <td>#<?php echo $r['order_id']; ?></td>
    <td>
        <?php echo htmlspecialchars($r['uname']); ?><br>
        <small class="text-muted"><?php echo htmlspecialchars($r['uemail']); ?></small>
    </td>
    <td><?php echo htmlspecialchars(mb_substr($r['reason'],0,80)) . (strlen($r['reason'])>80?'…':''); ?></td>
    <td>
        <?php if ($r['photo']): ?>
        <a href="../assets/return-photos/<?php echo htmlspecialchars($r['photo']); ?>" target="_blank">
            <img src="../assets/return-photos/<?php echo htmlspecialchars($r['photo']); ?>" width="60" style="border-radius:4px">
        </a>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
    <td><span class="label label-<?php echo $status_colors[$r['status']]??'default'; ?>"><?php echo $status_labels[$r['status']]??$r['status']; ?></span></td>
    <td>
        <button class="btn btn-xs btn-default" data-toggle="collapse" data-target="#detail-<?php echo $r['id']; ?>">
            <i class="icon-eye-open"></i> Gestionar
        </button>
    </td>
</tr>
<tr>
<td colspan="8" class="no-padding">
<div class="collapse" id="detail-<?php echo $r['id']; ?>" style="padding:12px;background:#fafafa">
    <p><strong>Motivo completo:</strong> <?php echo nl2br(htmlspecialchars($r['reason'])); ?></p>
    <?php if ($r['admin_note']): ?>
    <p><strong>Nota anterior:</strong> <?php echo nl2br(htmlspecialchars($r['admin_note'])); ?></p>
    <?php endif; ?>
    <form method="post" action="returns.php?id=<?php echo $r['id']; ?>&action={ACTION}" id="form-<?php echo $r['id']; ?>">
    <div class="form-group">
        <label>Nota para el cliente (opcional)</label>
        <textarea name="admin_note" class="form-control" rows="2" placeholder="Explicación de la decisión..."></textarea>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($r['status'] !== 'approved'): ?>
        <button type="submit" class="btn btn-sm btn-success"
            onclick="document.getElementById('form-<?php echo $r['id']; ?>').action='returns.php?id=<?php echo $r['id']; ?>&action=approved'">
            <i class="icon-ok"></i> Aprobar
        </button>
        <?php endif; ?>
        <?php if ($r['status'] !== 'rejected'): ?>
        <button type="submit" class="btn btn-sm btn-danger"
            onclick="document.getElementById('form-<?php echo $r['id']; ?>').action='returns.php?id=<?php echo $r['id']; ?>&action=rejected'">
            <i class="icon-remove"></i> Rechazar
        </button>
        <?php endif; ?>
        <?php if ($r['status'] === 'approved'): ?>
        <button type="submit" class="btn btn-sm btn-primary"
            onclick="document.getElementById('form-<?php echo $r['id']; ?>').action='returns.php?id=<?php echo $r['id']; ?>&action=completed'">
            <i class="icon-check"></i> Marcar completada
        </button>
        <?php endif; ?>
    </div>
    </form>
    <!-- R3: Reembolso -->
    <hr style="margin:12px 0">
    <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:12px">
      <strong style="font-size:13px"><i class="fa fa-money" style="color:#27ae60"></i> Registro de reembolso</strong>
      <?php if (!empty($r['refund_amount'])): ?>
      <div style="margin:8px 0;font-size:13px;color:#27ae60">
        ✅ Reembolso registrado: <strong>$<?php echo number_format($r['refund_amount'],0,'.',','); ?></strong>
        vía <?php echo htmlspecialchars($r['refund_method']); ?>
        el <?php echo date('d/m/Y', strtotime($r['refund_date'])); ?>
      </div>
      <?php endif; ?>
      <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:8px">
        <input type="hidden" name="rid" value="<?php echo $r['id']; ?>">
        <div>
          <label style="font-size:12px">Monto ($)</label>
          <input type="number" name="refund_amount" class="form-control input-sm" style="width:120px" min="0" step="100" value="<?php echo $r['refund_amount']??''; ?>" placeholder="0">
        </div>
        <div>
          <label style="font-size:12px">Método</label>
          <select name="refund_method" class="form-control input-sm" style="width:130px">
            <?php foreach(['Transferencia','Efectivo','MercadoPago','PayPal','Otro'] as $m): ?>
            <option <?php echo ($r['refund_method']??'')===$m?'selected':''; ?>><?php echo $m; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="font-size:12px">Fecha</label>
          <input type="date" name="refund_date" class="form-control input-sm" style="width:130px" value="<?php echo $r['refund_date']??date('Y-m-d'); ?>">
        </div>
        <button type="submit" name="save_refund" class="btn btn-sm btn-success"><i class="icon-save"></i> Guardar reembolso</button>
      </form>
    </div>
</div>
</td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

</div>
</div><!-- /.span9 -->
</div>
</div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
