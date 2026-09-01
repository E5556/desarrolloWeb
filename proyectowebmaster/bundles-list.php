<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/price-display.php');

$bundles_q = mysqli_query($con,
    "SELECT b.*,
            COUNT(bi.id) AS item_count,
            COALESCE(SUM(p.productPrice * bi.quantity), 0) AS regular_total
     FROM bundles b
     LEFT JOIN bundle_items bi ON bi.bundle_id = b.id
     LEFT JOIN products p ON p.id = bi.product_id
     WHERE b.is_active = 1
     GROUP BY b.id
     ORDER BY b.created_at DESC");

$bundles = [];
while ($bundles_q && $row = mysqli_fetch_assoc($bundles_q)) {
    // Cargar productos del bundle
    $iq = mysqli_query($con,
        "SELECT bi.quantity as bqty, p.id, p.productName, p.productPrice, p.productImage1
         FROM bundle_items bi JOIN products p ON p.id = bi.product_id
         WHERE bi.bundle_id = " . intval($row['id']));
    $row['items'] = [];
    while ($iq && $ir = mysqli_fetch_assoc($iq)) $row['items'][] = $ir;
    $bundles[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Packs Oferta | <?php echo htmlspecialchars($_config['site_name'] ?? 'Tienda'); ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/cosmetics.css">
<style>
.bundles-hero {
    background: linear-gradient(135deg, #e8233a 0%, #c0392b 60%, #8e1a1a 100%);
    color: #fff;
    padding: 52px 0 40px;
    text-align: center;
    margin-bottom: 40px;
}
.bundles-hero h1 { font-size: 2em; font-weight: 800; margin: 0 0 8px; letter-spacing: .5px; }
.bundles-hero p  { font-size: 15px; opacity: .88; margin: 0; }

.bundle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
    padding-bottom: 48px;
}

.b-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 18px rgba(0,0,0,.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .2s, box-shadow .2s;
}
.b-card:hover { transform: translateY(-4px); box-shadow: 0 8px 32px rgba(0,0,0,.13); }

.b-card-head {
    background: linear-gradient(135deg, #e8233a, #c0392b);
    color: #fff;
    padding: 20px 22px 16px;
}
.b-card-head h3 { margin: 0 0 4px; font-size: 16px; font-weight: 700; }
.b-card-head p  { margin: 0; font-size: 12px; opacity: .85; }

.b-items { padding: 16px 22px; flex: 1; }
.b-item  {
    display: flex; align-items: center; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid #f5f5f5;
}
.b-item:last-child { border-bottom: none; }
.b-item img { width: 44px; height: 52px; object-fit: cover; border-radius: 6px; background: #f5f5f5; flex-shrink: 0; }
.b-item-name  { font-size: 12px; font-weight: 600; color: #333; line-height: 1.3; }
.b-item-qty   { font-size: 11px; color: #888; margin-top: 2px; }
.b-item-price { font-size: 12px; color: #555; margin-left: auto; white-space: nowrap; }

.b-card-foot {
    padding: 16px 22px 20px;
    border-top: 1px solid #f0f0f0;
    background: #fafafa;
}
.b-prices { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 14px; }
.b-original { font-size: 12px; color: #aaa; text-decoration: line-through; }
.b-saving   { font-size: 11px; color: #27ae60; font-weight: 700; margin-top: 2px; }
.b-final    { font-size: 22px; font-weight: 800; color: #e8233a; line-height: 1; }
.b-final small { font-size: 12px; font-weight: 400; color: #888; display: block; margin-top: 2px; }

.btn-bundle {
    display: block; width: 100%;
    background: linear-gradient(135deg, #e8233a, #c0392b);
    color: #fff; border: none; border-radius: 8px;
    padding: 12px; font-size: 14px; font-weight: 700;
    text-align: center; text-decoration: none;
    cursor: pointer; transition: opacity .2s;
}
.btn-bundle:hover { opacity: .88; color: #fff; text-decoration: none; }

.empty-state {
    text-align: center; padding: 80px 20px; color: #bbb;
}
.empty-state i { font-size: 56px; display: block; margin-bottom: 16px; }
</style>
</head>
<body>
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>

<div class="bundles-hero">
    <div class="container">
        <h1><i class="fa fa-gift"></i> Packs Oferta</h1>
        <p>Conjuntos de productos seleccionados a un precio especial. ¡Ahorra comprando el pack completo!</p>
    </div>
</div>

<div class="container">
<?php if (empty($bundles)): ?>
<div class="empty-state">
    <i class="fa fa-gift"></i>
    <p style="font-size:16px;margin:0">No hay packs disponibles por el momento.</p>
    <a href="index2.php" style="color:#e8233a;font-size:13px;margin-top:8px;display:inline-block">Ver productos →</a>
</div>
<?php else: ?>
<div class="bundle-grid">
<?php foreach ($bundles as $b):
    $regular = floatval($b['regular_total']);
    $final   = floatval($b['bundle_price']);
    $saving  = $regular - $final;
    $pct     = $regular > 0 ? round($saving / $regular * 100) : 0;
?>
<div class="b-card">
    <div class="b-card-head">
        <h3><?php echo htmlspecialchars($b['name']); ?></h3>
        <?php if ($b['description']): ?>
        <p><?php echo htmlspecialchars($b['description']); ?></p>
        <?php endif; ?>
    </div>

    <div class="b-items">
        <?php foreach ($b['items'] as $it): ?>
        <div class="b-item">
            <img src="admin/productimages/<?php echo intval($it['id']); ?>/<?php echo htmlspecialchars($it['productImage1']); ?>"
                 onerror="this.style.display='none'" alt="">
            <div style="flex:1;min-width:0">
                <div class="b-item-name"><?php echo htmlspecialchars($it['productName']); ?></div>
                <div class="b-item-qty">Cantidad: <?php echo intval($it['bqty']); ?></div>
            </div>
            <div class="b-item-price">$<?php echo number_format($it['productPrice'] * $it['bqty'], 0, '.', ','); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="b-card-foot">
        <div class="b-prices">
            <div>
                <?php if ($regular > 0 && $saving > 0): ?>
                <div class="b-original">$<?php echo number_format($regular, 0, '.', ','); ?></div>
                <div class="b-saving"><i class="fa fa-arrow-down"></i> Ahorras $<?php echo number_format($saving, 0, '.', ','); ?> (<?php echo $pct; ?>%)</div>
                <?php endif; ?>
            </div>
            <div style="text-align:right">
                <div class="b-final">
                    $<?php echo number_format($final, 0, '.', ','); ?>
                    <small>precio del pack</small>
                </div>
            </div>
        </div>
        <a href="bundle.php?id=<?php echo intval($b['id']); ?>" class="btn-bundle">
            <i class="fa fa-shopping-cart"></i> Ver pack y agregar al carrito
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
