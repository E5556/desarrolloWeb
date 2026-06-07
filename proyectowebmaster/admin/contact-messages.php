<?php
session_start();
include('include/config.php');
if(empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Delete action
if (isset($_GET['del'])) {
    $did = intval($_GET['del']);
    mysqli_query($con, "DELETE FROM contact_messages WHERE id=$did");
    header('location:contact-messages.php?msg=deleted');
    exit();
}

// Mark as read
if (isset($_GET['read'])) {
    $rid = intval($_GET['read']);
    mysqli_query($con, "UPDATE contact_messages SET is_read=1 WHERE id=$rid");
    header('location:contact-messages.php');
    exit();
}

// Auto-create table
mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    subject VARCHAR(200) DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS is_read TINYINT DEFAULT 0");

$filter = $_GET['filter'] ?? 'all';
$where  = $filter === 'unread' ? "WHERE is_read=0" : ($filter === 'read' ? "WHERE is_read=1" : "");
$total  = intval(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) n FROM contact_messages"))['n']);
$unread = intval(mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) n FROM contact_messages WHERE is_read=0"))['n']);

$res = mysqli_query($con, "SELECT * FROM contact_messages $where ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Admin | Mensajes de Contacto</title>
    <link type="text/css" href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="css/theme.css" rel="stylesheet">
    <link type="text/css" href="images/icons/css/font-awesome.css" rel="stylesheet">
    <style>
        .msg-row-unread { background:#fffbf0 !important; font-weight:600; }
        .msg-preview { max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#666; font-size:12px; }
        .badge-unread { background:#e8233a; color:#fff; font-size:10px; padding:2px 7px; border-radius:10px; }
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
        <h3><i class="icon-envelope"></i> Mensajes de Contacto
            <?php if($unread > 0): ?>
            <span class="badge-unread"><?php echo $unread; ?> sin leer</span>
            <?php endif; ?>
        </h3>
    </div>
    <div class="module-body">

        <?php if(isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
        <div class="alert alert-success"><button class="close" data-dismiss="alert">×</button>Mensaje eliminado.</div>
        <?php endif; ?>

        <div style="margin-bottom:12px">
            <a href="contact-messages.php" class="btn btn-sm <?php echo $filter==='all'?'btn-primary':''; ?>">Todos (<?php echo $total; ?>)</a>
            <a href="contact-messages.php?filter=unread" class="btn btn-sm <?php echo $filter==='unread'?'btn-primary':''; ?>">Sin leer (<?php echo $unread; ?>)</a>
            <a href="contact-messages.php?filter=read" class="btn btn-sm <?php echo $filter==='read'?'btn-primary':''; ?>">Leídos</a>
        </div>

        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Nombre</th>
                    <th>Email / Teléfono</th>
                    <th>Asunto</th>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$res || mysqli_num_rows($res) === 0): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:#aaa">No hay mensajes.</td></tr>
            <?php else: while($row = mysqli_fetch_assoc($res)): ?>
            <tr class="<?php echo !$row['is_read'] ? 'msg-row-unread' : ''; ?>" id="msg-<?php echo $row['id']; ?>">
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?>
                    <?php if(!$row['is_read']): ?><span class="badge-unread">Nuevo</span><?php endif; ?></td>
                <td>
                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a>
                    <?php if(!empty($row['phone'])): ?><br><small><?php echo htmlspecialchars($row['phone']); ?></small><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['subject'] ?? '—'); ?></td>
                <td class="msg-preview" title="<?php echo htmlspecialchars($row['message']); ?>">
                    <a href="#" data-toggle="modal" data-target="#modal-<?php echo $row['id']; ?>" onclick="<?php if(!$row['is_read']): ?>markRead(<?php echo $row['id']; ?>)<?php endif; ?>">
                        <?php echo htmlspecialchars(substr($row['message'], 0, 80)); ?>…
                    </a>
                </td>
                <td style="white-space:nowrap;font-size:12px"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                <td style="white-space:nowrap">
                    <?php if(!$row['is_read']): ?>
                    <a href="contact-messages.php?read=<?php echo $row['id']; ?>" class="btn btn-mini" title="Marcar leído"><i class="icon-ok"></i></a>
                    <?php endif; ?>
                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="btn btn-mini btn-info" title="Responder"><i class="icon-reply"></i></a>
                    <a href="contact-messages.php?del=<?php echo $row['id']; ?>" class="btn btn-mini btn-danger" title="Eliminar"
                       onclick="return confirm('¿Eliminar este mensaje?')"><i class="icon-trash"></i></a>
                </td>
            </tr>

            <!-- Modal con mensaje completo -->
            <div class="modal hide fade" id="modal-<?php echo $row['id']; ?>" tabindex="-1">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h4><i class="icon-envelope"></i> Mensaje de <?php echo htmlspecialchars($row['name']); ?></h4>
                </div>
                <div class="modal-body">
                    <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></p>
                    <?php if(!empty($row['phone'])): ?><p><strong>Teléfono:</strong> <?php echo htmlspecialchars($row['phone']); ?></p><?php endif; ?>
                    <?php if($row['subject']): ?><p><strong>Asunto:</strong> <?php echo htmlspecialchars($row['subject']); ?></p><?php endif; ?>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></p>
                    <hr>
                    <p style="white-space:pre-wrap;font-size:13px"><?php echo htmlspecialchars($row['message']); ?></p>
                </div>
                <div class="modal-footer">
                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="btn btn-primary"><i class="icon-reply"></i> Responder por email</a>
                    <button class="btn" data-dismiss="modal">Cerrar</button>
                </div>
            </div>

            <?php endwhile; endif; ?>
            </tbody>
        </table>
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
<script>
function markRead(id) {
    $.get('contact-messages.php?read=' + id);
    $('#msg-' + id).removeClass('msg-row-unread');
}
</script>
</body>
</html>
