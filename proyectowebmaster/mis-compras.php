<?php
session_start();
include('includes/config.php');
if (empty($_SESSION['login'])) {
    $_SESSION['flash_info'] = 'Debes iniciar sesión para ver tus compras.';
    header('location:login.php'); exit();
}
$uid = intval($_SESSION['id']);

// ── Órdenes del usuario con producto ────────────────────────────────────────
$orders = mysqli_query($con,
    "SELECT o.id, o.orderDate, o.orderStatus, o.quantity, o.paymentMethod,
            p.id AS pid, p.productName, p.productImage1, p.productPrice
     FROM orders o
     JOIN products p ON p.id = o.productId
     WHERE o.userId = $uid
     ORDER BY o.orderDate DESC");

// ── Reseñas ya enviadas por el usuario ──────────────────────────────────────
$my_reviews = [];
$rv = mysqli_query($con, "SELECT productId, status FROM productreviews WHERE userId = $uid");
while ($r = mysqli_fetch_assoc($rv)) $my_reviews[$r['productId']] = $r['status'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
    body { background: #f7f7f7; font-family: 'Poppins', sans-serif; }

    .mc-wrap { max-width: 860px; margin: 40px auto; padding: 0 16px 60px; }

    /* ── Header section ── */
    .mc-hero { margin-bottom: 28px; }
    .mc-hero h1 { font-size: 1.6rem; font-weight: 700; color: #1a1a1a; margin: 0 0 4px; }
    .mc-hero p  { font-size: .88rem; color: #999; margin: 0; }

    /* ── Date separator ── */
    .mc-date { font-size: .75rem; font-weight: 600; letter-spacing: 1px;
               text-transform: uppercase; color: #bbb; margin: 28px 0 10px; }

    /* ── Order card ── */
    .mc-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        padding: 20px 24px;
        margin-bottom: 12px;
        display: flex; align-items: center; gap: 20px;
        transition: box-shadow .2s;
    }
    .mc-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.10); }

    .mc-img {
        width: 72px; height: 72px; border-radius: 12px;
        object-fit: cover; flex-shrink: 0;
        border: 1px solid #f0f0f0;
    }
    .mc-img-ph {
        width: 72px; height: 72px; border-radius: 12px;
        background: #f5f5f5; display: flex; align-items: center;
        justify-content: center; flex-shrink: 0;
    }
    .mc-img-ph i { font-size: 28px; color: #ddd; }

    .mc-info { flex: 1; min-width: 0; }
    .mc-info .prod-name {
        font-size: .95rem; font-weight: 600; color: #1a1a1a;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .mc-info .prod-meta {
        font-size: .78rem; color: #aaa; margin-top: 3px;
    }
    .mc-info .prod-price {
        font-size: .88rem; font-weight: 700; color: #333; margin-top: 6px;
    }

    /* status badge */
    .mc-status {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: .72rem; font-weight: 600; padding: 3px 10px;
        border-radius: 20px; margin-top: 5px;
    }
    .mc-status.delivered { background: #e8f5e9; color: #2e7d32; }
    .mc-status.pending   { background: #fff8e1; color: #f57f17; }
    .mc-status.other     { background: #f3f3f3; color: #777; }
    .mc-status i { font-size: .68rem; }

    /* ── Review button ── */
    .mc-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; flex-shrink: 0; }

    .btn-review {
        display: inline-flex; align-items: center; gap: 7px;
        background: #1a1a1a; color: #fff !important;
        font-size: .78rem; font-weight: 600; letter-spacing: .5px;
        text-transform: uppercase; text-decoration: none !important;
        padding: 9px 18px; border-radius: 50px;
        transition: background .2s, transform .15s;
        white-space: nowrap;
    }
    .btn-review:hover { background: #c0396b; transform: translateY(-1px); }
    .btn-review i { font-size: .8rem; }

    .btn-reviewed {
        display: inline-flex; align-items: center; gap: 7px;
        background: transparent; color: #aaa !important;
        font-size: .78rem; font-weight: 600; letter-spacing: .5px;
        text-transform: uppercase; text-decoration: none !important;
        padding: 9px 18px; border-radius: 50px;
        border: 1.5px solid #e5e5e5;
        white-space: nowrap; cursor: default;
    }
    .btn-reviewed i { font-size: .8rem; color: #f0ad4e; }

    /* ── Empty state ── */
    .mc-empty { text-align: center; padding: 80px 20px; }
    .mc-empty i { font-size: 52px; color: #e0e0e0; margin-bottom: 16px; display: block; }
    .mc-empty h3 { font-size: 1.1rem; font-weight: 600; color: #bbb; margin: 0 0 8px; }
    .mc-empty p  { font-size: .85rem; color: #ccc; }
    .mc-empty a  { margin-top: 16px; display: inline-block; }

    @media (max-width: 576px) {
        .mc-card { flex-wrap: wrap; gap: 14px; }
        .mc-actions { flex-direction: row; width: 100%; justify-content: flex-start; }
        .mc-info .prod-name { white-space: normal; }
    }
    </style>
</head>
<body>
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<div class="mc-wrap">
    <div class="mc-hero">
        <h1>Mis Compras</h1>
        <p>Historial de pedidos de <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>

    <?php
    $rows = mysqli_fetch_all($orders, MYSQLI_ASSOC);
    if (empty($rows)):
    ?>
    <div class="mc-empty">
        <i class="fa fa-shopping-bag"></i>
        <h3>Aún no tienes compras</h3>
        <p>Explora el catálogo y encuentra algo que te encante.</p>
        <a href="index2.php" class="btn-review"><i class="fa fa-arrow-left"></i> Ver productos</a>
    </div>
    <?php else:
        $last_date = null;
        foreach ($rows as $o):
            $date_str = date('j \d\e F \d\e Y', strtotime($o['orderDate']));
            // spanish month names
            $months_es = ['January'=>'enero','February'=>'febrero','March'=>'marzo',
                          'April'=>'abril','May'=>'mayo','June'=>'junio',
                          'July'=>'julio','August'=>'agosto','September'=>'septiembre',
                          'October'=>'octubre','November'=>'noviembre','December'=>'diciembre'];
            $date_str = str_replace(array_keys($months_es), array_values($months_es), $date_str);

            $is_delivered = ($o['orderStatus'] === 'Delivered');
            $already      = isset($my_reviews[$o['pid']]);
            $rev_status   = $my_reviews[$o['pid']] ?? null;

            if ($date_str !== $last_date):
                $last_date = $date_str;
    ?>
        <div class="mc-date"><?php echo $date_str; ?></div>
    <?php endif; ?>

        <div class="mc-card">
            <?php
            $img_path = "admin/productimages/{$o['pid']}/{$o['productImage1']}";
            if ($o['productImage1'] && file_exists($img_path)):
            ?>
            <img src="<?php echo $img_path; ?>" class="mc-img" alt="<?php echo htmlspecialchars($o['productName']); ?>">
            <?php else: ?>
            <div class="mc-img-ph"><i class="fa fa-image"></i></div>
            <?php endif; ?>

            <div class="mc-info">
                <div class="prod-name"><?php echo htmlspecialchars($o['productName']); ?></div>
                <div class="prod-meta"><?php echo $o['quantity']; ?> unidad(es) · <?php echo htmlspecialchars($o['paymentMethod'] ?? '—'); ?></div>
                <div class="prod-price">$<?php echo number_format($o['productPrice'], 0, '.', ','); ?></div>
                <?php if ($is_delivered): ?>
                <span class="mc-status delivered"><i class="fa fa-check-circle"></i> Entregado</span>
                <?php elseif (in_array($o['orderStatus'], ['Pending', null, ''])): ?>
                <span class="mc-status pending"><i class="fa fa-clock-o"></i> Pendiente</span>
                <?php else: ?>
                <span class="mc-status other"><i class="fa fa-refresh"></i> <?php echo htmlspecialchars($o['orderStatus']); ?></span>
                <?php endif; ?>
            </div>

            <div class="mc-actions">
                <?php if ($is_delivered && !$already): ?>
                <a href="review.php?oid=<?php echo $o['id']; ?>&pid=<?php echo $o['pid']; ?>" class="btn-review">
                    <i class="fa fa-star"></i> Escribir reseña
                </a>
                <?php elseif ($already): ?>
                <span class="btn-reviewed">
                    <i class="fa fa-star"></i>
                    <?php echo $rev_status === 'approved' ? 'Reseña publicada' : 'Reseña enviada'; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/scripts.js"></script>
</body>
</html>
