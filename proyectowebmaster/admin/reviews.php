<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// ── Acciones ─────────────────────────────────────────────────────────────────
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    mysqli_query($con, "UPDATE productreviews SET status='approved' WHERE id=$id");
    header('location:reviews.php?ok=approved'); exit();
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    mysqli_query($con, "UPDATE productreviews SET status='rejected' WHERE id=$id");
    header('location:reviews.php?ok=rejected'); exit();
}
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    mysqli_query($con, "DELETE FROM productreviews WHERE id=$id");
    header('location:reviews.php?ok=deleted'); exit();
}

// ── Filtro ────────────────────────────────────────────────────────────────────
$filter = in_array($_GET['f'] ?? '', ['pending','approved','rejected']) ? $_GET['f'] : 'pending';
$where  = "WHERE r.status='$filter'";

// ── Lista ─────────────────────────────────────────────────────────────────────
$reviews = mysqli_query($con,
    "SELECT r.*, p.productName, u.name AS userName, u.email AS userEmail
     FROM productreviews r
     LEFT JOIN products p ON p.id = r.productId
     LEFT JOIN users u    ON u.id = r.userId
     $where
     ORDER BY r.reviewDate DESC");

// ── Conteos por estado ─────────────────────────────────────────────────────────
$counts = [];
foreach (['pending','approved','rejected'] as $s) {
    $counts[$s] = intval((($__r = mysqli_query($con, "SELECT COUNT(*) n FROM productreviews WHERE status='$s'")) ? mysqli_fetch_assoc($__r) : ['n'=>0])['n']);
}

$msgs = ['approved'=>'Reseña aprobada y publicada.','rejected'=>'Reseña rechazada.','deleted'=>'Reseña eliminada.'];
$ok_msg = $msgs[$_GET['ok'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Reseñas</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <style>
    .rv-filter-tabs { display:flex; gap:8px; margin-bottom:20px; }
    .rv-filter-tabs a {
        padding:6px 18px; border-radius:20px; font-size:.82em; font-weight:600;
        text-decoration:none; border:1.5px solid #ddd; color:#666;
        transition:all .15s;
    }
    .rv-filter-tabs a.active, .rv-filter-tabs a:hover { border-color:#337ab7; background:#337ab7; color:#fff; }
    .rv-filter-tabs .badge-cnt { font-size:.7em; background:rgba(255,255,255,.3); border-radius:10px; padding:1px 6px; margin-left:4px; }

    .rv-card {
        background:#fff; border:1px solid #e8e8e8; border-radius:8px;
        padding:16px 20px; margin-bottom:12px;
    }
    .rv-card:hover { box-shadow:0 3px 14px rgba(0,0,0,0.07); }

    .rv-card-top { display:flex; align-items:flex-start; gap:14px; }
    .rv-avatar {
        width:40px; height:40px; border-radius:50%; background:#337ab7; color:#fff;
        font-size:.9em; font-weight:700; display:flex; align-items:center; justify-content:center;
        flex-shrink:0;
    }
    .rv-meta { flex:1; min-width:0; }
    .rv-meta .rv-user { font-weight:600; font-size:.9em; color:#333; }
    .rv-meta .rv-email { font-size:.75em; color:#aaa; }
    .rv-meta .rv-prod { font-size:.78em; color:#555; margin-top:2px; }

    .rv-stars-disp { color:#c9a84c; font-size:1em; letter-spacing:1px; }
    .rv-verified { background:#e8f5e9; color:#2e7d32; border-radius:10px; padding:2px 8px; font-size:.7em; font-weight:700; }

    .rv-title   { font-size:.92em; font-weight:700; color:#222; margin:10px 0 4px; }
    .rv-comment { font-size:.85em; color:#555; line-height:1.5; }
    .rv-date    { font-size:.72em; color:#bbb; margin-top:6px; }

    .rv-actions { display:flex; gap:6px; margin-top:12px; flex-wrap:wrap; }

    .badge-pending  { background:#fff8e1; color:#f57f17; border-radius:10px; padding:2px 8px; font-size:.72em; font-weight:700; }
    .badge-approved { background:#e8f5e9; color:#2e7d32; border-radius:10px; padding:2px 8px; font-size:.72em; font-weight:700; }
    .badge-rejected { background:#ffebee; color:#c62828; border-radius:10px; padding:2px 8px; font-size:.72em; font-weight:700; }
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
    <h3><i class="icon-star" style="margin-right:6px"></i>Gestión de Reseñas <a href="export.php?type=reviews" class="btn btn-xs btn-default pull-right" style="margin-top:4px"><i class="icon-download"></i> Exportar CSV</a></h3>
</div>
<div class="module-body">

<?php if ($ok_msg): ?>
<div class="alert alert-success"><button class="close" data-dismiss="alert">×</button><?php echo $ok_msg; ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="rv-filter-tabs">
    <a href="?f=pending"  class="<?php echo $filter==='pending' ?'active':''; ?>">
        Pendientes <span class="badge-cnt"><?php echo $counts['pending']; ?></span>
    </a>
    <a href="?f=approved" class="<?php echo $filter==='approved'?'active':''; ?>">
        Aprobadas <span class="badge-cnt"><?php echo $counts['approved']; ?></span>
    </a>
    <a href="?f=rejected" class="<?php echo $filter==='rejected'?'active':''; ?>">
        Rechazadas <span class="badge-cnt"><?php echo $counts['rejected']; ?></span>
    </a>
</div>

<?php $total = $reviews ? mysqli_num_rows($reviews) : 0;
if ($total === 0): ?>
<p class="text-muted" style="font-style:italic;padding:20px 0">
    No hay reseñas <?php echo $filter==='pending'?'pendientes':($filter==='approved'?'aprobadas':'rechazadas'); ?>.
</p>
<?php else:
while ($reviews && $r = mysqli_fetch_assoc($reviews)):
    $initial = strtoupper(mb_substr($r['userName'] ?? 'U', 0, 1));
    $stars_filled = str_repeat('★', intval($r['quality'])) . str_repeat('☆', 5-intval($r['quality']));
?>
<div class="rv-card">
    <div class="rv-card-top">
        <div class="rv-avatar"><?php echo htmlspecialchars($initial); ?></div>
        <div class="rv-meta">
            <div>
                <span class="rv-user"><?php echo htmlspecialchars($r['userName'] ?? 'Usuario eliminado'); ?></span>
                <?php if ($r['verified']): ?>
                <span class="rv-verified" style="margin-left:6px"><i class="icon-ok"></i> Compra verificada</span>
                <?php endif; ?>
                <span class="rv-stars-disp" style="margin-left:8px"><?php echo $stars_filled; ?></span>
            </div>
            <div class="rv-email"><?php echo htmlspecialchars($r['userEmail'] ?? ''); ?></div>
            <div class="rv-prod"><i class="icon-tag"></i> <?php echo htmlspecialchars($r['productName'] ?? 'Producto eliminado'); ?></div>
        </div>
        <div>
            <?php if ($filter==='pending'):   ?><span class="badge-pending">Pendiente</span>
            <?php elseif($filter==='approved'):?><span class="badge-approved">Aprobada</span>
            <?php else:                       ?><span class="badge-rejected">Rechazada</span><?php endif; ?>
        </div>
    </div>

    <?php if ($r['title']): ?>
    <div class="rv-title">"<?php echo htmlspecialchars($r['title']); ?>"</div>
    <?php endif; ?>
    <div class="rv-comment"><?php echo nl2br(htmlspecialchars($r['review'])); ?></div>
    <?php if (!empty($r['review_photo'])): ?>
    <div style="margin-top:10px">
        <a href="../assets/review-photos/<?php echo htmlspecialchars($r['review_photo']); ?>" target="_blank">
            <img src="../assets/review-photos/<?php echo htmlspecialchars($r['review_photo']); ?>"
                 onerror="this.parentElement.style.display='none'"
                 style="max-width:180px;max-height:140px;border-radius:6px;border:1px solid #eee;object-fit:cover">
        </a>
        <div style="font-size:11px;color:#aaa;margin-top:3px"><i class="icon-camera"></i> Foto adjunta</div>
    </div>
    <?php endif; ?>
    <div class="rv-date"><i class="icon-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($r['reviewDate'])); ?></div>

    <div class="rv-actions">
        <?php if ($filter !== 'approved'): ?>
        <a href="?approve=<?php echo $r['id']; ?>" class="btn btn-small btn-success"
           onclick="return confirm('¿Aprobar y publicar esta reseña?')">
            <i class="icon-ok"></i> Aprobar
        </a>
        <?php endif; ?>
        <?php if ($filter !== 'rejected'): ?>
        <a href="?reject=<?php echo $r['id']; ?>" class="btn btn-small btn-warning"
           onclick="return confirm('¿Rechazar esta reseña?')">
            <i class="icon-remove"></i> Rechazar
        </a>
        <?php endif; ?>
        <a href="?del=<?php echo $r['id']; ?>" class="btn btn-small btn-danger"
           onclick="return confirm('¿Eliminar definitivamente?')">
            <i class="icon-trash"></i> Eliminar
        </a>
    </div>
</div>
<?php endwhile; endif; ?>

</div></div></div>
</div></div></div></div>
<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
