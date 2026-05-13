<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (empty($_SESSION['login'])) { header('location:login.php'); exit(); }

$oid = intval($_GET['oid'] ?? 0);
$uid = intval($_SESSION['id']);
if ($oid <= 0) { header('location:order-history.php'); exit(); }

// Verify order belongs to this user
$chk = mysqli_query($con, "SELECT id FROM orders WHERE id=$oid AND userId=$uid LIMIT 1");
if (!$chk || mysqli_num_rows($chk) === 0) { header('location:order-history.php'); exit(); }

// Get order rows
$rows_q = mysqli_query($con,
    "SELECT o.id as orderid, o.orderDate, o.orderStatus, o.paymentMethod, o.quantity,
            p.productName, p.productPrice, p.shippingCharge, p.id as pid
     FROM orders o JOIN products p ON o.productId=p.id
     WHERE o.id=$oid AND o.paymentMethod IS NOT NULL");

if (!$rows_q || mysqli_num_rows($rows_q) === 0) { header('location:order-history.php'); exit(); }

$order_rows = [];
while ($r = mysqli_fetch_assoc($rows_q)) $order_rows[] = $r;
$first = $order_rows[0];

// Customer info
$usr_q = mysqli_query($con, "SELECT firstName,lastName,email,billingAddress,billingCity,billingState,billingPincode FROM users WHERE id=$uid LIMIT 1");
$usr = mysqli_fetch_assoc($usr_q);

$subtotal = 0;
foreach ($order_rows as $r) $subtotal += $r['quantity'] * $r['productPrice'] + $r['shippingCharge'];

$site_name = $_SITE_NAME ?? 'Personal Shopper';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Factura #<?php echo $oid; ?> | <?php echo htmlspecialchars($site_name); ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 13px; color: #333; background:#f5f5f5; }
.invoice-wrap { max-width: 800px; margin: 30px auto; background:#fff; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
.inv-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:32px; border-bottom:2px solid #e8233a; padding-bottom:20px; }
.inv-logo { font-size: 26px; font-weight: 700; color: #e8233a; }
.inv-logo small { display:block; font-size:12px; color:#888; font-weight:400; }
.inv-title { text-align:right; }
.inv-title h2 { font-size:22px; color:#333; text-transform:uppercase; letter-spacing:2px; }
.inv-title .inv-num { color:#888; font-size:12px; margin-top:4px; }
.inv-meta { display:flex; justify-content:space-between; margin-bottom:28px; gap:20px; }
.inv-meta .col { flex:1; }
.inv-meta .col h4 { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#888; margin-bottom:6px; border-bottom:1px solid #eee; padding-bottom:4px; }
.inv-meta .col p { font-size:13px; line-height:1.6; }
table.inv-table { width:100%; border-collapse:collapse; margin-bottom:24px; }
table.inv-table thead tr { background:#e8233a; color:#fff; }
table.inv-table thead th { padding:9px 12px; text-align:left; font-size:12px; font-weight:600; }
table.inv-table tbody tr { border-bottom:1px solid #f0f0f0; }
table.inv-table tbody tr:nth-child(even) { background:#fafafa; }
table.inv-table tbody td { padding:9px 12px; }
.inv-totals { display:flex; justify-content:flex-end; }
.inv-totals table { width:260px; }
.inv-totals table td { padding:5px 10px; font-size:13px; }
.inv-totals table td:last-child { text-align:right; font-weight:600; }
.inv-totals .total-row td { border-top:2px solid #333; font-size:15px; padding-top:8px; }
.inv-totals .total-row td:last-child { color:#e8233a; }
.inv-footer { margin-top:32px; text-align:center; font-size:11px; color:#aaa; border-top:1px solid #eee; padding-top:14px; }
.status-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600;
    background:<?php echo ($first['orderStatus']==='Delivered'?'#d4edda':'#fff3cd'); ?>;
    color:<?php echo ($first['orderStatus']==='Delivered'?'#155724':'#856404'); ?>; }

.no-print { text-align:center; margin-bottom:20px; }
.no-print button { padding:10px 24px; background:#e8233a; color:#fff; border:none; border-radius:5px; font-size:14px; cursor:pointer; margin:0 6px; }
.no-print a { display:inline-block; padding:10px 20px; background:#6c757d; color:#fff; border-radius:5px; font-size:14px; text-decoration:none; margin:0 6px; }

@media print {
    body { background:#fff; }
    .no-print { display:none !important; }
    .invoice-wrap { box-shadow:none; margin:0; padding:20px; }
}
</style>
</head>
<body>
<div class="no-print">
    <br>
    <button onclick="window.print()"><i>&#128424;</i> Descargar / Imprimir PDF</button>
    <a href="order-history.php">← Volver a mis pedidos</a>
</div>
<div class="invoice-wrap">
    <div class="inv-header">
        <div class="inv-logo">
            <?php echo htmlspecialchars($site_name); ?>
            <small>Tu tienda de confianza</small>
        </div>
        <div class="inv-title">
            <h2>Factura</h2>
            <div class="inv-num">N° <?php echo str_pad($oid, 6, '0', STR_PAD_LEFT); ?></div>
            <div class="inv-num">Fecha: <?php echo date('d/m/Y', strtotime($first['orderDate'])); ?></div>
            <div style="margin-top:6px"><span class="status-badge"><?php echo htmlspecialchars($first['orderStatus'] ?? 'Procesando'); ?></span></div>
        </div>
    </div>

    <div class="inv-meta">
        <div class="col">
            <h4>Facturado a</h4>
            <p>
                <strong><?php echo htmlspecialchars(($usr['firstName']??'').' '.($usr['lastName']??'')); ?></strong><br>
                <?php echo htmlspecialchars($usr['email']??''); ?><br>
                <?php if (!empty($usr['billingAddress'])) echo htmlspecialchars($usr['billingAddress']).'<br>'; ?>
                <?php if (!empty($usr['billingCity'])) echo htmlspecialchars($usr['billingCity']); ?>
                <?php if (!empty($usr['billingState'])) echo ', '.htmlspecialchars($usr['billingState']); ?>
                <?php if (!empty($usr['billingPincode'])) echo ' '.htmlspecialchars($usr['billingPincode']); ?>
            </p>
        </div>
        <div class="col">
            <h4>Información de pago</h4>
            <p>
                <strong>Método:</strong> <?php echo htmlspecialchars($first['paymentMethod']??'—'); ?><br>
                <strong>Estado:</strong> <?php echo htmlspecialchars($first['orderStatus']??'Procesando'); ?><br>
                <strong>Orden #:</strong> <?php echo $oid; ?>
            </p>
        </div>
        <div class="col">
            <h4>Empresa</h4>
            <p>
                <?php echo htmlspecialchars($site_name); ?><br>
                <?php echo htmlspecialchars($_SITE_EMAIL ?? ''); ?>
            </p>
        </div>
    </div>

    <table class="inv-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Precio unit.</th>
                <th>Cantidad</th>
                <th>Envío</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php $n=1; foreach($order_rows as $r): $row_total = $r['quantity']*$r['productPrice']+$r['shippingCharge']; ?>
            <tr>
                <td><?php echo $n++; ?></td>
                <td><?php echo htmlspecialchars($r['productName']); ?></td>
                <td>$<?php echo number_format($r['productPrice'],0,'.',','); ?></td>
                <td><?php echo $r['quantity']; ?></td>
                <td>$<?php echo number_format($r['shippingCharge'],0,'.',','); ?></td>
                <td>$<?php echo number_format($row_total,0,'.',','); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="inv-totals">
        <table>
            <tr><td>Subtotal productos:</td><td>$<?php echo number_format($subtotal,0,'.',','); ?></td></tr>
            <tr class="total-row"><td><strong>TOTAL:</strong></td><td><strong>$<?php echo number_format($subtotal,0,'.',','); ?></strong></td></tr>
        </table>
    </div>

    <div class="inv-footer">
        Gracias por tu compra. Este documento es su comprobante de pago.<br>
        <?php echo htmlspecialchars($site_name); ?> — <?php echo htmlspecialchars($_SITE_EMAIL??''); ?>
    </div>
</div>
</body>
</html>
